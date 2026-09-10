# MiniTicket — Architecture technique

## 1. Objectifs d'architecture

- **Simplicité** — architecture proportionnée au périmètre (3 rôles, ~15 routes) ; pas de couche ou de pattern non justifié par un besoin réel.
- **Séparation des responsabilités** — chaque couche (Controller, Service, Repository, Model, Core) a un rôle unique et ne déborde pas sur les autres.
- **Sécurité** — contrôle d'accès centralisé (Guard), validation systématique des entrées, aucune confiance accordée aux données côté client.
- **Maintenabilité** — code lisible et structuré pour permettre une évolution ultérieure sans réécriture (ex. US11, US12 en bonus).
- **Déploiement reproductible** — environnement de développement identique pour toute reprise du projet, via Docker Compose.

## 2. Stack technique

- **PHP 8.3, sans framework** — maîtrisé via EcoRide ; élimine le risque d'apprentissage, cohérent avec l'objectif de simplicité (§1).
- **MySQL + PDO** — stockage relationnel des données métier (users, tickets, categories, comments) ; PDO pour requêtes préparées natives, sans ORM.
- **MongoDB** — stockage de la collection `ticket_events` (append-only). Instance MongoDB conteneurisée en développement via Docker Compose. La connexion est configurée par variables d'environnement afin de permettre l'utilisation ultérieure d'une instance MongoDB distante sans modification du code applicatif.
- **Docker / Docker Compose** — environnement de développement reproductible ; usage limité au développement, la cible de production reste ouverte (§10).
- **Composer** — gestion des dépendances (`mongodb/mongodb` pour l'accès MongoDB, `vlucas/phpdotenv` pour le chargement des variables d'environnement) et autoload PSR-4 du code applicatif.
- **Git / GitHub** — versionnement et hébergement du code source.

## 3. Architecture générale
MVC en couches.
Client HTTP → Router → Controller → Service → Repository → MySQL/MongoDB.
Models simples pour représenter les données échangées.

Schéma général de l'architecture (voir diagramme `miniticket_architecture_generale_v3`) :
client HTTP → Router → Controllers → Services → Repositories → MySQL / MongoDB.

Le Router, situé dans Core, dirige chaque requête vers le Controller approprié. Les Controllers orchestrent le traitement HTTP et utilisent le Guard centralisé de Core lorsque la route nécessite un contrôle d'autorisation. Les Services portent la logique métier et délèguent la persistance aux Repositories. Les Repositories isolent l'accès à MySQL et MongoDB. Les Models représentent les structures de données échangées entre les différentes couches.

## 4. Organisation du code
```
miniticket/
├── app/
│   ├── Controllers/
│   │   ├── TicketController.php
│   │   ├── UserController.php
│   │   └── CommentController.php
│   ├── Services/
│   │   ├── TicketService.php
│   │   ├── UserService.php
│   │   └── CommentService.php
│   ├── Repositories/
│   │   ├── TicketRepository.php
│   │   ├── UserRepository.php
│   │   ├── CommentRepository.php
│   │   └── TicketEventRepository.php
│   ├── Models/
│   │   ├── Ticket.php
│   │   ├── User.php
│   │   └── Comment.php
│   ├── Core/
│   │   ├── Router.php
│   │   ├── Database.php      # connexion PDO MySQL
│   │   ├── MongoConnection.php
│   │   ├── Session.php        # configuration/démarrage des sessions PHP, régénération et destruction techniques
│   │   ├── Csrf.php            # génération et vérification d'un jeton CSRF unique par session
│   │   ├── ErrorHandler.php    # capture centralisée des erreurs/exceptions, journalisation, affichage selon APP_ENV

│   │   └── Guard.php          # contrôle d'autorisation 
│   │   └── helpers.php       # fonctions globales : e() et csrf_field()
│   └── Views/
│       └── errors/
│           └── 500.php         # page générique affichée en production
├── config/
│   └── config.php
├── database/
│   └── miniticket_schema.sql
├── public/
│   └── index.php              # point d'entrée
├── routes.php
├── composer.json
├── compose.yaml                # Docker Compose, environnement de dev uniquement
├── .env
└── .env.example
```

## 5. Responsabilités des couches
### 5.1 Controllers

Les Controllers assurent l'orchestration HTTP. Ils reçoivent les requêtes après leur résolution par le Router, déclenchent les contrôles d'accès via le Guard lorsque nécessaire, transmettent les données utiles au Service concerné, puis produisent la réponse HTTP sous forme de vue ou de redirection.

Ils ne contiennent ni logique métier ni accès direct aux bases de données.

Exemple : lors de la création d'un ticket, le Controller contrôle l'accès à l'action, transmet les données de la requête à `TicketService`, puis redirige vers le ticket créé. Le calcul de priorité et la persistance sont délégués aux couches correspondantes.

### 5.2 Services

Les Services portent la logique métier de l'application. Ils appliquent les règles définies par le domaine, orchestrent les opérations nécessaires à un cas d'utilisation et délèguent la lecture ou la persistance des données aux Repositories.

Ils ne gèrent ni les requêtes/réponses HTTP ni les accès directs aux bases de données.

Exemple : lors du traitement d'un ticket, un Service peut calculer sa priorité à partir de l'impact et de l'urgence, vérifier qu'un changement de statut respecte les transitions autorisées ou contrôler les règles métier liées à son assignation. Une fois ces règles appliquées, il délègue la lecture ou l'écriture des données au Repository concerné.

### 5.3 Repositories

Les Repositories isolent l'accès aux données, qu'elles soient stockées en MySQL ou en MongoDB. Chaque Repository correspond à une entité (Ticket, User, Comment) ou, pour MongoDB, à la collection `ticket_events`. Ils exécutent des requêtes préparées côté MySQL et ne contiennent aucune logique métier — uniquement de la lecture, de l'écriture et de la traduction entre les données persistées et les Models.

Exemple : `TicketRepository` insère ou met à jour un ticket via PDO, en requête préparée ; `TicketEventRepository` écrit un document dans `ticket_events` après réussite de la persistance SQL, et journalise l'erreur sans bloquer l'opération en cas d'échec MongoDB.

### 5.4 Models

Les Models sont de simples conteneurs de données : propriétés et accesseurs, sans logique métier ni validation. Ils représentent la structure d'une entité persistée en MySQL (Ticket, User, Comment) et servent de support d'échange entre Repositories, Services et Controllers.

Les documents de la collection `ticket_events` (MongoDB) ne disposent pas de Model dédié : leur structure reste un tableau associatif, sans classe, la collection étant en append-only sans logique de manipulation associée.

### 5.5 Core

Core regroupe les composants techniques transversaux, communs à toute l'application : Router (résolution des routes vers un Controller), Database (connexion PDO à MySQL), MongoConnection (connexion à la bibliothèque PHP MongoDB), Session, Csrf et Guard.

Session configure et démarre la session PHP (paramètres sécurisés du cookie : `httponly`, `secure` selon l'environnement, `samesite`), et fournit les primitives techniques de régénération et de destruction de session. Elle ne contient aucune décision d'authentification ou d'autorisation — ce rôle reste exclusivement celui du Guard.
 
Csrf génère et vérifie un jeton CSRF unique par session, comparé en temps constant (`hash_equals`). Comme Session, c'est un mécanisme technique pur : il ne décide d'aucune autorisation, il fournit seulement le moyen de protéger un formulaire contre une soumission forgée depuis un autre site.

Rôle et portée du Guard → §7.

## 6. Gestion technique des données

### 6.1 MySQL

L'accès à MySQL passe exclusivement par les Repositories (§5.3), via PDO. Chaque opération utilise des requêtes préparées ; aucune donnée issue de l'application n'est concaténée directement dans une requête SQL. Les opérations nécessitant plusieurs écritures qui doivent réussir ou échouer ensemble sont exécutées dans une transaction afin de garantir leur atomicité.

### 6.2 MongoDB

L'accès à MongoDB passe exclusivement par `TicketEventRepository` (§5.3), via la bibliothèque PHP MongoDB. Un document est écrit dans `ticket_events` après réussite de la persistance SQL correspondante, jamais avant : MySQL constitue la source de vérité sur l'état du ticket, tandis que MongoDB conserve son historique événementiel. Le document référence le ticket via `ticket_id`, sans contrainte de clé étrangère entre les deux bases ; cette relation est gérée par l'application. En cas d'échec d'écriture dans MongoDB, l'erreur est journalisée, l'opération métier n'est pas bloquée et aucune erreur technique n'est exposée à l'utilisateur.

## 7. Sécurité et autorisation

L'autorisation repose sur deux niveaux distincts. Le Guard (§5.5), appelé depuis les Controllers avant toute délégation au Service, tranche l'autorisation générale : l'utilisateur possède-t-il la permission d'effectuer ce type d'action, compte tenu de son rôle. Les Services (§5.2) tranchent la règle métier contextuelle : cet utilisateur précis peut-il agir sur cette ressource précise, compte tenu de son propriétaire, de son état ou de son assignation — par exemple, le créateur d'un ticket limité à ses propres tickets, quel que soit son rôle (TECHNICIAN et ADMIN disposant en complément d'un accès global, cf. `01_cadrage.md` §5), vérifié dans `CommentService` et `TicketService`.

Le Guard s'appuie sur la matrice de permissions actée en `01_cadrage.md` §3 (USER, TECHNICIAN, ADMIN). Aucune donnée provenant du client (paramètres, formulaire, session) n'est considérée fiable sans validation serveur.

Détails d'implémentation (mécanisme de session, structure des contrôles, protections CSRF/XSS/injection) → `03_securite.md`.

## 8. Flux métier principaux

### 8.1 Création d'un ticket

Le Controller reçoit la requête après résolution par le Router, vérifie via le Guard que l'utilisateur a la permission de créer un ticket, puis transmet les données au Service. Le Service applique les règles métier : calcul de la priorité à partir de l'impact et de l'urgence (`01_cadrage.md` §5), affectation du statut initial `NOUVEAU`. Le Service délègue la persistance du ticket à `TicketRepository`. Une fois la persistance MySQL réussie, il délègue l'enregistrement de l'événement de création à `TicketEventRepository` (§6.2). Le Controller redirige ensuite vers le détail du ticket créé.

Déroulement détaillé → diagramme de séquence, §9.2 (seul cas couvert par ce diagramme).

### 8.2 Prise en charge d'un ticket

Le Controller vérifie via le Guard que l'utilisateur dispose de la permission nécessaire, puis transmet la demande au Service. Le Service contrôle la règle métier d'assignation (§5.2, distincte de la vérification de propriété USER) : le ticket est assigné au technicien et son statut passe automatiquement de `NOUVEAU` à `EN_COURS` — ces deux changements forment une seule opération métier. Le Service délègue ensuite la persistance de l'assignation et du nouveau statut à `TicketRepository`. Une fois la persistance MySQL réussie, il délègue l'enregistrement de l'événement correspondant à `TicketEventRepository`. Le Controller redirige vers le détail du ticket mis à jour.

### 8.3 Changement de statut et résolution

Le Controller vérifie via le Guard que l'utilisateur dispose de la permission nécessaire, puis transmet la demande au Service. Le Service contrôle que la transition demandée respecte la machine à états définie en `01_cadrage.md` §4 (US7) (`EN_COURS` → `RÉSOLU` → `FERMÉ`) : toute transition hors de cet ordre est rejetée avant d'atteindre le Repository. Un ticket au statut `FERMÉ` n'accepte plus cette action, sauf réouverture explicite (US12, bonus, hors chemin critique). Le Service délègue ensuite la persistance du nouveau statut à `TicketRepository`. Une fois la persistance MySQL réussie, il délègue l'enregistrement de l'événement correspondant à `TicketEventRepository`. Le Controller redirige vers le détail du ticket mis à jour.

## 9. Diagrammes UML techniques

### 9.1 Diagramme de classes

Domaine complet : `User`, `Category`, `Ticket`, `Comment`, attributs alignés sur le MLD (`01_cadrage.md` §6.1). Relations (notation UML — la multiplicité près d'une classe indique combien d'instances de cette classe sont associées à une instance de l'autre classe ; ne pas confondre avec la notation Merise du MCD en `01_cadrage.md` §6.1, qui place les cardinalités en sens inverse) :
- `User` 1 ←→ 0..n `Ticket` (créateur, obligatoire)
- `User` 0..1 ←→ 0..n `Ticket` (technicien assigné, facultatif) — relation distincte de la précédente
- `Category` 1 ←→ 0..n `Ticket`
- `Ticket` 1 ←→ 0..n `Comment`
- `User` 1 ←→ 0..n `Comment`

Voir diagramme `miniticket_diagramme_classes_v4`.

### 9.2 Diagramme de séquence

Seul cas couvert (`01_cadrage.md` §7) : création d'un ticket, scénario nominal et branche d'échec MongoDB. Acteurs : Client, Router, `TicketController`, Guard, `TicketService`, `TicketRepository`, MySQL, `TicketEventRepository`, MongoDB — `TicketRepository` et `TicketEventRepository` apparaissent comme deux lignes de vie distinctes, pour rendre visible que MongoDB n'est jamais appelé directement par `TicketRepository`.

Séquence : requête HTTP → résolution de route → contrôle de permission via Guard → délégation au Service (calcul de priorité, statut initial `NOUVEAU`) → persistance MySQL via `TicketRepository` (requête préparée, validation de l'écriture SQL) → retour du ticket créé au Service → enregistrement de l'événement de création via `TicketEventRepository`. La branche `alt` isole les deux issues possibles de l'écriture MongoDB : en cas de succès, aucune action supplémentaire ; en cas d'échec, l'erreur est journalisée sans interrompre l'opération métier (§6.2). Dans les deux cas, le ticket est considéré créé et le Controller redirige le Client vers le détail du ticket.

Voir diagramme `miniticket_sequence_creation_ticket_v3`.

## 10. Configuration et déploiement

L'environnement de développement est conteneurisé via Docker Compose, pour garantir sa reproductibilité. `compose.yaml` définit trois services : un service applicatif (Apache + PHP), un service MySQL et un service MongoDB. La configuration de connexion repose sur les variables d'environnement, ce qui permet de changer d'instance MongoDB (locale ou distante) sans modification du code.

Deux fichiers distincts gèrent la configuration : `.env.example`, versionné, documente les variables attendues sans valeur sensible ; `.env`, non versionné, contient les valeurs réelles (identifiants MySQL, URI MongoDB) propres à chaque environnement.

La cible de production (hébergement, usage ou non de Docker en production) n'est pas encore arrêtée. Détails et procédure complète → `05_deploiement.md`.
