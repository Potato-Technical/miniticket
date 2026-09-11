# MiniTicket — Déploiement

## Prérequis

- PHP 8.3
- Composer
- Docker et Docker Compose (environnement de développement : app, MySQL, MongoDB)
- Git

## Variables d'environnement

Deux fichiers, jamais confondus (`02_architecture.md` §10) :
- `.env.example` — versionné, documente les variables attendues sans valeur réelle
- `.env` — non versionné, contient les valeurs réelles de l'environnement local

Variables attendues (nom exact à fixer à l'implémentation) : connexion MySQL (hôte, port, base, utilisateur, mot de passe), URI MongoDB (permettant de cibler l'instance conteneurisée en développement ou une instance distante selon l'environnement), `DEMO_PASSWORD` (mot de passe des comptes créés par le seed de démonstration, §Seed ci-dessous).

## Création de la base SQL

Exécution de `database/miniticket_schema.sql` sur l'instance MySQL cible (conteneur en développement, ou instance de production une fois la cible arrêtée). Le script inclut la structure des tables et le seed des 6 catégories fixes (`01_cadrage.md` §5).

**État actuel : script exécuté avec succès sur l'instance MySQL de développement. Les tables et les 6 catégories fixes ont été vérifiées.**

## Provisioning des comptes TECHNICIAN et ADMIN (production / campagne réelle)

L'inscription publique (US1) ne crée que des comptes USER. Pour un environnement destiné à durer (production, ou compte réel utilisé au-delà d'une démonstration ponctuelle), les comptes TECHNICIAN et ADMIN sont créés manuellement en base (insertion SQL directe ou requête ponctuelle), hors de tout mécanisme applicatif — aucune interface de provisioning n'est prévue dans le chemin critique, et cette procédure n'est délibérément pas versionnée sous forme de script exécutable avec identifiants (repository public).

**Procédure reproductible** (à exécuter après `docker compose up -d` et l'exécution du schéma) — aucune valeur réelle ci-dessous, à remplacer localement :

1. Générer le hash du mot de passe choisi, pour chaque compte :
```
docker compose exec web php -r "echo password_hash('<mot_de_passe_dev>', PASSWORD_DEFAULT) . PHP_EOL;"
```

2. Se connecter à MySQL dans le conteneur :
```
docker compose exec db mysql -u<DB_USER> -p<DB_PASSWORD> <DB_NAME>
```

3. Insérer le compte avec le hash généré à l'étape 1 :
```sql
INSERT INTO users (pseudo, email, password_hash, role, created_at) VALUES
('<pseudo>', '<email>', '<hash_généré>', 'TECHNICIAN', NOW());
```
Répéter pour `ADMIN`.

Cette procédure n'est pas versionnée sous forme de script SQL exécutable — seule la méthode est documentée, pas les identifiants ni les hashs réels.

## Seed de démonstration (développement local uniquement)

Distinct du provisioning ci-dessus. `database/seed_demo.php` est un script versionné qui crée (ou corrige le rôle de, si déjà présents) 3 comptes de démonstration — un par rôle — et 3 tickets `[DEMO]` représentatifs, à seule fin de peupler rapidement une instance de développement locale. Rejouable sans créer de doublons.

Ce script ne contredit pas le principe énoncé ci-dessus car il ne versionne aucun identifiant réel : le mot de passe des comptes créés est lu depuis `DEMO_PASSWORD`, une variable d'environnement définie dans `.env` (non versionné, §Variables d'environnement) et absente de `.env.example`. Le script ne l'affiche jamais en sortie, ne le journalise pas, et échoue explicitement si la variable est absente plutôt que d'utiliser une valeur par défaut en dur.

Cette garantie dépend entièrement de `.env` restant hors Git dans le dépôt local — condition déjà vérifiée en `04_tests.md` §17, à revérifier après toute modification de `.gitignore`.

**Ce seed est réservé au développement local.** Il ne doit pas être exécuté sur une instance exposée publiquement : les comptes créés portent des identifiants prévisibles (`user@miniticket.local`, etc.) et un mot de passe partagé entre les trois rôles.

```bash
# dans .env : DEMO_PASSWORD=<mot de passe local, jamais versionné>
docker compose exec web php database/seed_demo.php
```

## Configuration MongoDB

En développement : service MongoDB défini dans `compose.yaml`. Le code applicatif se connecte via l'URI définie dans `.env`, ce qui permet de basculer vers une instance distante sans modification du code, le moment venu.

## Déploiement

**Développement** : `docker compose up` démarre les services applicatif (Apache + PHP), MySQL et MongoDB. Reproductible d'une machine à l'autre.

**Production** : cible non arrêtée à ce stade (hébergement, usage ou non de Docker) — voir `02_architecture.md` §10. Cette section sera complétée une fois la cible choisie. Le seed de démonstration ne doit en aucun cas être exécuté sur cette cible (§Seed ci-dessus) ; le provisioning TECHNICIAN/ADMIN suit exclusivement la procédure manuelle décrite plus haut.

## Vérifications post-déploiement

- La base MySQL contient bien les 6 catégories fixes après exécution du script
- Connexion MongoDB fonctionnelle (écriture d'un événement de test dans `ticket_events`)
- Un compte USER peut se créer, se connecter, créer un ticket
- Un compte TECHNICIAN peut le prendre en charge et le faire progresser jusqu'à `FERMÉ`
- Un compte ADMIN peut consulter `/admin` et obtenir des indicateurs à jour (US10, `02_architecture.md` §11)