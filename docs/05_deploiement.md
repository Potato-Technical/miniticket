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

Variables attendues (nom exact à fixer à l'implémentation) : connexion MySQL (hôte, port, base, utilisateur, mot de passe), URI MongoDB (permettant de cibler l'instance conteneurisée en développement ou une instance distante selon l'environnement), secret de session.

## Création de la base SQL

Exécution de `database/miniticket_schema.sql` sur l'instance MySQL cible (conteneur en développement, ou instance de production une fois la cible arrêtée). Le script inclut la structure des tables et le seed des 6 catégories fixes (`01_cadrage.md` §5).

**État actuel : script rédigé à partir du MLD (`01_cadrage.md` §6.1), non encore exécuté.**

## Provisioning des comptes TECHNICIAN et ADMIN

L'inscription publique (US1) ne crée que des comptes USER. Pour le MVP, les comptes TECHNICIAN et ADMIN sont créés manuellement en base (insertion SQL directe ou requête ponctuelle), hors de tout mécanisme applicatif — aucune interface de provisioning n'est prévue dans le chemin critique.

## Configuration MongoDB

En développement : service MongoDB défini dans `compose.yaml`. Le code applicatif se connecte via l'URI définie dans `.env`, ce qui permet de basculer vers une instance distante sans modification du code, le moment venu.

## Déploiement

**Développement** : `docker compose up` démarre les services applicatif (Apache + PHP), MySQL et MongoDB. Reproductible d'une machine à l'autre.

**Production** : cible non arrêtée à ce stade (hébergement, usage ou non de Docker) — voir `02_architecture.md` §10. Cette section sera complétée une fois la cible choisie.

## Vérifications post-déploiement

- La base MySQL contient bien les 6 catégories fixes après exécution du script
- Connexion MongoDB fonctionnelle (écriture d'un événement de test dans `ticket_events`)
- Un compte USER peut se créer, se connecter, créer un ticket
- Un compte TECHNICIAN peut le prendre en charge et le faire progresser jusqu'à `FERMÉ`

