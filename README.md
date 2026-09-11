# MiniTicket

Application de gestion de tickets informatiques (helpdesk) — création, suivi et
traitement des demandes de support, avec trois rôles applicatifs (USER,
TECHNICIAN, ADMIN) et un visiteur non authentifié.

PHP 8.3 sans framework, MySQL + MongoDB, Bootstrap 5.3.3. Projet développé pour
démontrer les compétences back-end sécurisé du bloc RNCP37674BC02 (voir
[`docs/01_cadrage.md`](docs/01_cadrage.md) pour le contexte complet).

## Fonctionnalités

- **Visiteur** : page d'accueil publique, inscription, connexion.
- **USER** : création de ticket (priorité calculée automatiquement à partir de
  l'impact et de l'urgence), consultation de ses propres tickets, détail avec
  commentaires et historique.
- **TECHNICIAN** : vue globale de tous les tickets, prise en charge, suivi du
  cycle de vie du statut (`NOUVEAU → EN_COURS → RESOLU → FERME`).
- **ADMIN** : mêmes droits de lecture globale que TECHNICIAN (sans prise en
  charge ni changement de statut), plus un tableau de bord de supervision
  (`/admin`) avec indicateurs MySQL sur l'état des tickets et indicateurs
  d'activité MongoDB.

Détail des rôles et permissions : [`docs/01_cadrage.md`](docs/01_cadrage.md) §3.

## Stack technique

| Composant | Choix |
|---|---|
| Langage | PHP 8.3, sans framework |
| Données relationnelles | MySQL 8.4, via PDO (requêtes préparées) |
| Données événementielles | MongoDB (collection `ticket_events`, append-only) |
| Front | Bootstrap 5.3.3 + CSS d'appoint (`public/assets/css/app.css`) |
| Environnement | Docker Compose (`web` Apache+PHP, `db`, `mongo`) |

Détails d'architecture : [`docs/02_architecture.md`](docs/02_architecture.md).

## Démarrage rapide

```bash
git clone -b dev <url_du_depot>
cd miniticket
cp .env.example .env   # renseigner les identifiants MySQL/MongoDB locaux
docker compose up -d --build
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < database/miniticket_schema.sql
```

Application accessible sur **http://localhost:8081**.

Par défaut, l'inscription publique (`/register`) ne crée que des comptes
`USER`. Pour obtenir un compte `TECHNICIAN` ou `ADMIN`, ou pour peupler
rapidement l'application avec des données de démonstration (3 comptes + 3
tickets à différents statuts), voir le seed local ci-dessous.

Procédure complète, provisioning manuel des rôles, variables d'environnement :
[`docs/05_deploiement.md`](docs/05_deploiement.md).

## Seed de démonstration (local uniquement)

```bash
# dans .env : DEMO_PASSWORD=<mot de passe local, jamais versionné>
docker compose exec web php database/seed_demo.php
```

Crée (ou corrige le rôle de, si déjà présents) 3 comptes — `user@miniticket.local`
(USER), `technician@miniticket.local` (TECHNICIAN), `admin@miniticket.local`
(ADMIN) — et 3 tickets `[DEMO]` représentatifs (NOUVEAU, EN_COURS, FERME).
Rejouable sans créer de doublons. Le mot de passe n'est jamais affiché en
sortie ni versionné — voir `.env.example` et
[`docs/05_deploiement.md`](docs/05_deploiement.md).

## Tests

```bash
docker compose exec web php tests/integration/test_27_final.php
docker compose exec web php tests/integration/test_28_admin_dashboard.php
```

Scénarios d'intégration niveau Service (inscription, création de ticket,
RBAC applicatif, commentaires, cycle de vie complet, historique MongoDB,
statistiques du tableau de bord ADMIN, seed). Le contrôle RBAC/CSRF/XSS au
niveau HTTP est couvert par une passe manuelle documentée dans
[`docs/04_tests.md`](docs/04_tests.md).

## Documentation

| Document | Contenu |
|---|---|
| [`docs/01_cadrage.md`](docs/01_cadrage.md) | Périmètre, rôles, user stories, règles métier, modèle de données, parcours de navigation |
| [`docs/02_architecture.md`](docs/02_architecture.md) | Couches applicatives, gestion MySQL/MongoDB, diagrammes UML |
| [`docs/03_securite.md`](docs/03_securite.md) | Mesures de sécurité et justification |
| [`docs/04_tests.md`](docs/04_tests.md) | Campagne de tests (automatisée et manuelle) |
| [`docs/05_deploiement.md`](docs/05_deploiement.md) | Installation, provisioning des rôles, seed, déploiement |
| [`docs/export/`](docs/export/) | Maquettes desktop/mobile et diagrammes UML exportés |

## Structure du projet

```
app/
├── Controllers/   # Orchestration HTTP (Guard, délégation aux Services)
├── Services/      # Logique métier (validation, calcul de priorité, transitions)
├── Repositories/  # Accès MySQL (PDO) et MongoDB, sans logique métier
├── Core/          # Router, Database, MongoConnection, Session, Csrf, Guard
└── Views/         # Vues PHP, layout commun

database/
├── miniticket_schema.sql   # Schéma MySQL + seed des 6 catégories fixes
└── seed_demo.php           # Seed de démonstration (comptes + tickets)

tests/integration/           # Scénarios d'intégration niveau Service
docs/                        # Documentation complète du projet
```
