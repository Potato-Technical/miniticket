# MiniTicket — Déploiement

## Périmètre de ce document

Ce document couvre exclusivement l'environnement local Docker Compose, seul environnement effectivement mis en place et validé dans le cadre de ce projet. **Aucune cible de production n'a été déployée** — voir §7.

## Prérequis

- PHP 8.3 (fourni par l'image `web`, construite depuis le `Dockerfile` du projet — aucune installation PHP locale requise)
- Composer (fourni dans l'image `web` via `COPY --from=composer:2`)
- Docker et Docker Compose
- Git

## Procédure d'installation complète (environnement local)

1. Cloner le dépôt, branche `dev` (branche de référence — voir §8) :
   ```
   git clone -b dev <url_du_depot>
   cd miniticket
   ```

2. Copier le fichier d'environnement et renseigner les valeurs locales :
   ```
   cp .env.example .env
   ```
   Éditer `.env` pour fixer les identifiants MySQL et MongoDB (voir §"Variables d'environnement" ci-dessous).

3. Construire et démarrer les conteneurs :
   ```
   docker compose up -d --build
   ```
   Trois services démarrent : `web` (Apache + PHP 8.3, construit depuis le `Dockerfile` local), `db` (MySQL 8.4.11), `mongo` (MongoDB 8.0.29). Les healthchecks MySQL et MongoDB font attendre `web` jusqu'à ce que les deux bases soient prêtes.

4. Exécuter le schéma SQL (création des tables + seed des 6 catégories) :
   ```
   docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/miniticket_schema.sql
   ```
   `MYSQL_ROOT_PASSWORD` et `MYSQL_DATABASE` sont déjà présentes dans l'environnement du conteneur `db` (définies par `compose.yaml` à partir de `.env`) — aucune variable supplémentaire à exporter côté hôte.

   **Cette commande doit être exécutée sur une base nouvellement créée / non initialisée — le script n'est pas idempotent.** Testée et confirmée reproductible sur une base vierge (`miniticket_install_test`) : import sans erreur, tables `categories`, `comments`, `tickets`, `users` créées, 6 catégories seedées correctement. Ne pas la rejouer sur une base déjà initialisée.

5. Provisionner les comptes TECHNICIAN et ADMIN — voir §"Provisioning des comptes" ci-dessous.

6. Accéder à l'application : **http://localhost:8081** (port hôte mappé, voir `compose.yaml` — `8081:80`, jamais `80` directement).

## Variables d'environnement

Deux fichiers, jamais confondus (`02_architecture.md` §10) :
- `.env.example` — versionné, documente les variables attendues sans valeur réelle
- `.env` — non versionné, contient les valeurs réelles de l'environnement local, sous les noms `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `MONGO_ROOT_USER`, `MONGO_ROOT_PASSWORD`, `MONGO_APP_USER`, `MONGO_APP_PASSWORD`.

À l'intérieur du conteneur `db`, ces valeurs sont exposées sous les noms attendus par l'image officielle MySQL — `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` — traduction faite par `compose.yaml`, jamais par le nom `DB_*` d'origine. Toute commande exécutée via `docker compose exec db ...` doit utiliser les noms `MYSQL_*`, pas `DB_*`.

## Création de la base SQL

Exécution de `database/miniticket_schema.sql` sur l'instance MySQL cible (conteneur `db` en développement), via la commande de l'étape 4 ci-dessus. Le script inclut la structure des tables et le seed des 6 catégories fixes (`01_cadrage.md` §5).

**État actuel : le script a été exécuté avec succès sur l'instance MySQL de développement. Sa procédure d'installation a également été vérifiée sur une base vierge temporaire : les tables `categories`, `comments`, `tickets` et `users` ont été créées et les 6 catégories fixes correctement insérées.** (confirmé également par la campagne de tests, `04_tests.md`).

## Initialisation de l'utilisateur applicatif MongoDB

Le conteneur `mongo` monte `docker/mongo-init/init-app-user.sh` dans `/docker-entrypoint-initdb.d/`, exécuté automatiquement par l'image officielle MongoDB **une seule fois, au tout premier démarrage du volume `mongo_data`**. Ce script crée l'utilisateur applicatif (`MONGO_APP_USER`/`MONGO_APP_PASSWORD`, définis dans `.env`), distinct du compte root (`MONGO_ROOT_USER`/`MONGO_ROOT_PASSWORD`) utilisé pour l'administration et le healthcheck.

Si le volume `mongo_data` est supprimé (`docker compose down -v`), ce script se réexécute au prochain démarrage et recrée l'utilisateur applicatif à partir des valeurs courantes de `.env`. Aucune action manuelle n'est requise dans ce cas.

## Provisioning des comptes TECHNICIAN et ADMIN

L'inscription publique (US1) ne crée que des comptes USER. Pour le MVP, les comptes TECHNICIAN et ADMIN sont créés manuellement en base (insertion via le flux d'inscription HTTP standard, puis élévation de rôle par requête SQL directe), hors de tout mécanisme applicatif — aucune interface de provisioning n'est prévue dans le chemin critique.

**Procédure reproductible**, telle qu'effectivement exécutée pendant la campagne de tests (`04_tests.md`) :

1. Créer le compte via le flux d'inscription normal (`POST /register` ou formulaire web) — il est créé avec le rôle `USER` par défaut.

2. Récupérer son identifiant :
   ```
   docker compose exec db sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT id, pseudo, role FROM users WHERE email='"'"'<email_du_compte>'"'"';"'
   ```

3. Élever son rôle :
   ```
   docker compose exec db sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "UPDATE users SET role='"'"'TECHNICIAN'"'"' WHERE id=<id>;"'
   ```
   Remplacer `TECHNICIAN` par `ADMIN` selon le rôle voulu.

Cette procédure n'est pas versionnée sous forme de script SQL exécutable (cohérent avec "hors de tout mécanisme applicatif" ci-dessus) — seule la méthode est documentée, pas les identifiants ni les hashs réels, le repository étant public.

## Déploiement

**Développement (seul environnement mis en place et validé à ce jour)** : `docker compose up -d --build` démarre les services applicatif (Apache + PHP, construit depuis le `Dockerfile` local), MySQL et MongoDB. Application accessible sur `http://localhost:8081`. Reproductible d'une machine à l'autre, à condition de disposer de Docker et Docker Compose.

## Production

**Aucun déploiement en production n'a été effectué dans le cadre de ce projet.** La cible d'hébergement (fournisseur, usage ou non de Docker en production, gestion HTTPS) n'a pas été arrêtée et ne fait l'objet d'aucune tentative de déploiement réel. Cette absence est assumée : le périmètre du projet, tel que fixé en `01_cadrage.md`, porte sur les compétences back-end sécurisé (RNCP37674BC02), démontrées et validées sur l'environnement Docker Compose local (voir `04_tests.md`).

Si une mise en production devait être envisagée ultérieurement, les points suivants resteraient à traiter et ne sont pas couverts par ce document : choix d'un hébergeur, gestion des secrets hors `.env` local, activation de l'attribut `Secure` sur le cookie de session (actuellement absent, cohérent avec l'environnement HTTP local — voir `04_tests.md` §15), certificat HTTPS, et bascule de l'URI MongoDB vers une instance distante (le code applicatif le permet déjà sans modification, `02_architecture.md` §10, mais cela n'a jamais été testé en pratique).

## Vérifications post-déploiement

Vérifications réellement effectuées sur l'environnement local (détail complet : `04_tests.md`) :
- La base MySQL contient bien les 6 catégories fixes après exécution du script
- Connexion MongoDB fonctionnelle (écriture d'événements de test dans `ticket_events`, confirmée par le scénario d'intégration et l'historique affiché)
- Un compte USER peut se créer, se connecter, créer un ticket
- Un compte TECHNICIAN peut le prendre en charge et le faire progresser jusqu'à `FERME`