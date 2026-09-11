# MiniTicket — Cadrage

## 1. Contexte

Projet personnel de gestion de tickets informatiques (helpdesk), développé pour démontrer les compétences back-end sécurisé du référentiel Studi. Périmètre, rôles et règles métier auto-définis (pas d'énoncé externe imposé). Durée cible : ~8-9 soirées (2-3h chacune) + une session weekend (~8h, extensible si nécessaire, avec baisse de rendement attendue en fin de session prolongée) — soit ~24-35h au total.

Domaine choisi pour son ancrage réel : expérience personnelle en support IT/helpdesk, donc vocabulaire métier (ticket, priorité, résolution, escalade) déjà maîtrisé — pas un domaine simulé.

**Périmètre** : gestion de tickets d'incidents et de demandes en interne (tout compte peut signaler un problème le concernant, un TECHNICIAN traite). Exclu explicitement : SLA contractuels, notifications temps réel, pièces jointes, base de connaissance, reporting statistique.

## 2. Compétences du référentiel visées

Bloc back-end sécurisé uniquement (pas de bloc front-end pour ce projet) — RNCP37674BC02 « Développer la partie back-end d'une application web ou web mobile sécurisée » :
- Mettre en place une base de données relationnelle
- Développer des composants d'accès aux données SQL et NoSQL
- Développer des composants métier côté serveur
- Documenter le déploiement d'une application dynamique web ou web mobile

## 3. Rôles

| Permission | USER | TECHNICIAN | ADMIN |
|---|---|---|---|
| Se connecter | ✔ | ✔ | ✔ |
| Créer un ticket (pour soi-même) | ✔ | ✔ | ✔ |
| Voir ses propres tickets | ✔ | ✔ | ✔ |
| Voir tous les tickets | – | ✔ | ✔ |
| Commenter un ticket | ✔ (ses tickets) | ✔ (tous) | ✔ (tous) |
| Prendre en charge un ticket | – | ✔ | – |
| Changer le statut d'un ticket | – | ✔ | – |
| Rouvrir un ticket fermé (US12, bonus) | ✔ (créateur) | ✔ | – |
| Gérer les utilisateurs (US10, bonus) | – | – | ✔ |

RBAC porté par un champ `role` (ENUM) sur `users` — pas de table `roles` séparée.

## 4. User Stories

**US1 — Création de compte** (Visiteur) — pseudo, email, mot de passe hashé via `password_hash()` ; tout compte créé par inscription publique reçoit obligatoirement le rôle USER, quelle que soit la valeur soumise par le formulaire. Les comptes TECHNICIAN et ADMIN sont créés manuellement en base pour le MVP (hors application, pas de mécanisme applicatif de provisioning dans le chemin critique).

**US2 — Connexion** (Visiteur → USER/TECHNICIAN/ADMIN) — authentification, session sécurisée, redirection selon rôle.

**US3 — Créer un ticket** (USER, TECHNICIAN, ADMIN — pour soi-même) — titre, description, type (INCIDENT/DEMANDE), catégorie, impact, urgence. Statut initial NOUVEAU. Priorité calculée automatiquement. Pas de création au nom d'un autre compte dans le P0.

**US4 — Liste de ses propres tickets** (USER, TECHNICIAN, ADMIN) — visibilité restreinte aux tickets créés par le compte connecté. Pour TECHNICIAN et ADMIN, cette vue ("mes tickets") est distincte de la vue globale (US5).

**US5 — Liste des tickets côté TECHNICIAN** (TECHNICIAN) — visibilité globale, statut et priorité affichés.

**US6 — Détail d'un ticket + commentaires** (USER, TECHNICIAN, ADMIN) — fil de commentaires en SQL, rattaché au ticket.

**US7 — Prise en charge et changement de statut** (TECHNICIAN) — prise en charge = assignation + transition automatique NOUVEAU → EN_COURS. Transitions suivantes (EN_COURS → RÉSOLU → FERMÉ) distinctes. Un ticket FERMÉ n'accepte plus cette action.

**US8 — Calcul automatique de la priorité** (Système) — calcul serveur lors de la création du ticket à partir de l'impact et de l'urgence. Jamais saisie directement.

**US9 — Historique des événements** (Système) — chaque événement métier significatif concernant le cycle de vie d'un ticket génère un événement MongoDB, relu pour l'affichage.

**US10 — Admin utilisateurs et supervision tickets** (ADMIN, bonus) — gestion des utilisateurs ; accès en lecture à tous les tickets et possibilité de commenter, au même titre que TECHNICIAN. Hors chemin critique.

**US11 — Pré-remplissage assisté** (bonus) — suggestions front-end d'impact/urgence, recalcul serveur qui fait foi. N'ajoute aucune compétence par rapport à US8.

**US12 — Réouverture d'un ticket fermé** (bonus, hors P0) — ajoute permission, transition, événement et route supplémentaires sans démontrer de compétence nouvelle. À ne traiter que si le P0 est fini en avance.

## 5. Règles métier

**Matrice de priorité** (impact × urgence → priorité) :

| Impact \ Urgence | Faible | Moyenne | Élevée |
|---|---|---|---|
| Élevé | P3 | P2 | P1 |
| Moyen | P4 | P3 | P2 |
| Faible | P4 | P4 | P3 |

Nommage : P1 Critique, P2 Haute, P3 Normale, P4 Faible. Pas de SLA dans le MVP.

**Catégories** — liste fixe : Matériel, Logiciel, Réseau / Internet, Compte / Accès, Messagerie, Autre. Pas de CRUD applicatif.

**Cohérence SQL/MongoDB** — le SQL fait foi pour le statut. Un échec d'écriture Mongo après une persistance SQL réussie est journalisé, jamais bloquant.

**Autres règles fixes** — chaque compte ne voit, via "ses tickets", que ceux qu'il a lui-même créés, quel que soit son rôle ; TECHNICIAN et ADMIN disposent en complément d'un accès en lecture globale (US5) ; un commentaire référence obligatoirement un ticket existant ; ADMIN dispose de la même portée d'accès aux tickets que TECHNICIAN (lecture globale, commentaire), sans droit de prise en charge ni de changement de statut ; TECHNICIAN peut prendre en charge un ticket qu'il a lui-même créé — aucune règle d'exclusion créateur/assigné n'est appliquée dans le P0 ; pas de création de ticket au nom d'un autre compte dans le P0.

## 6. Modèle de données — partie relationnelle + partie NoSQL

### 6.1 Partie relationnelle (MySQL)

**MCD — entités et cardinalités**
- `USERS (0,n) ─── (1,1) TICKETS` — créateur
- `USERS (0,n) ─── (0,1) TICKETS` — technicien assigné
- `CATEGORIES (0,n) ─── (1,1) TICKETS`
- `TICKETS (0,n) ─── (1,1) COMMENTS`
- `USERS (0,n) ─── (1,1) COMMENTS`

**MLD**
```
USERS (id PK, pseudo UNIQUE NOT NULL, email UNIQUE NOT NULL, password_hash NOT NULL,
       role NOT NULL DEFAULT 'USER', created_at NOT NULL)

CATEGORIES (id PK, libelle UNIQUE NOT NULL)

TICKETS (id PK, user_id FK→USERS.id NOT NULL, technician_id FK→USERS.id NULL,
         category_id FK→CATEGORIES.id NOT NULL, type NOT NULL, impact NOT NULL,
         urgence NOT NULL, priorite NOT NULL, statut NOT NULL DEFAULT 'NOUVEAU',
         titre NOT NULL, description NOT NULL, created_at NOT NULL, updated_at NOT NULL)

COMMENTS (id PK, ticket_id FK→TICKETS.id NOT NULL, user_id FK→USERS.id NOT NULL,
          contenu NOT NULL, created_at NOT NULL)
```

**Contraintes et types ENUM** (alignés exactement sur `database/miniticket_schema.sql`) :
- `users.role` : ENUM('USER','TECHNICIAN','ADMIN')
- `tickets.type` : ENUM('INCIDENT','DEMANDE')
- `tickets.impact` : ENUM('FAIBLE','MOYEN','ELEVE')
- `tickets.urgence` : ENUM('FAIBLE','MOYENNE','ELEVEE')
- `tickets.priorite` : ENUM('P1','P2','P3','P4') — jamais renseigné par l'utilisateur, toujours calculé serveur
- `tickets.statut` : ENUM('NOUVEAU','EN_COURS','RESOLU','FERME')
- Toutes les FK en `ON DELETE RESTRICT` — aucune suppression de user/catégorie/ticket prévue dans le MVP
- `categories` : 6 lignes fixes insérées par seed SQL, pas de CRUD applicatif

MPD = `database/miniticket_schema.sql` (types, contraintes, index, seed des catégories — rédigé à partir du MLD validé ci-dessus et validé par exécution sur une base MySQL vierge).

### 6.2 Partie NoSQL (MongoDB)

Collection unique `ticket_events`, append-only, un document par événement métier significatif concernant le cycle de vie d'un ticket :

```
ticket_events {
  ticket_id       (référence vers TICKETS.id, non contrainte au niveau base)
  type_evenement  (ex. CREATION, CHANGEMENT_STATUT, PRISE_EN_CHARGE, REOUVERTURE)
  acteur           (user_id + role au moment de l'action)
  horodatage
  donnees         (ex. ancien_statut, nouveau_statut — variable selon type_evenement)
}
```

Pas de jointure avec MySQL au niveau base : la mise en relation se fait applicativement via `ticket_id`, relu et affiché en détail de ticket (US9).

### 6.3 Justification de la répartition

- **MySQL** : toute la donnée métier structurée (users, tickets, categories, comments) — intégrité référentielle nécessaire, relations fortes.
- **MongoDB** : collection unique `ticket_events` — historique en append-only, pas de jointure nécessaire, structure tolérante à l'évolution.

## 7. Diagrammes et maquettes — périmètre figé

- Diagramme de cas d'utilisation : 4 acteurs UML (VISITOR, USER, TECHNICIAN, ADMIN), mappé sur US1-US12 — produit dans ce fichier ou en annexe. VISITOR couvre la création de compte et la connexion, avant toute attribution de rôle ; le RBAC applicatif (§3) reste à 3 rôles, VISITOR n'y figurant pas puisqu'il ne possède aucune permission propre
- Diagramme de classes (domaine) — produit dans `02_architecture.md` §9.1
- Diagramme de séquence — un seul cas : création d'un ticket — produit dans `02_architecture.md` §9.2
- 3 maquettes clés uniquement : création de ticket, liste tickets (vue technicien), détail + commentaires + historique
- Personas écartés — les rôles (§3) suffisent

Diagramme de cas d'utilisation, diagramme de classes et diagramme de séquence produits (`02_architecture.md` §9.1, §9.2 ; diagramme de cas d'utilisation en annexe). Les 3 maquettes clés (création de ticket, liste tickets vue technicien, détail + commentaires + historique) produites, avec déclinaisons desktop et mobile pour chacune.

## 8. Parcours de navigation (user flows)

Ces parcours décrivent les déplacements et actions de navigation réellement disponibles dans l'interface (menus, liens, boutons) à la suite de la passe UX/UI. Ils ne modifient aucune règle métier ni aucun cas d'utilisation (§4) — ils documentent uniquement comment ces cas d'utilisation sont désormais atteints depuis l'application. Rôles inchangés : VISITOR, USER, TECHNICIAN, ADMIN (§3).

### 8.1 VISITOR

```
Accueil MiniTicket
   │
   ├──→ Créer un compte
   │        ↓
   │     Compte créé
   │        ↓
   │     Connexion
   │
   └──→ Connexion
            ↓
        Authentification
            ↓
        Redirection selon rôle
```

`/` est désormais une page d'accueil publique (landing page), accessible sans authentification, qui présente MiniTicket et propose les deux points d'entrée Créer un compte et Se connecter. Le visiteur peut aussi passer de Connexion à Créer un compte et inversement, via les liens présents sur chacune des deux pages.

### 8.2 USER

```
Connexion
   ↓
Mes tickets
   │
   ├──→ Nouveau ticket
   │       ↓
   │   Création du ticket
   │       ↓
   │   Détail du ticket
   │       │
   │       ├──→ Consulter informations
   │       ├──→ Consulter historique
   │       └──→ Ajouter commentaire
   │
   └──→ Ouvrir un ticket existant
           ↓
       Détail du ticket
           │
           ├──→ Consulter informations
           ├──→ Consulter historique
           └──→ Ajouter commentaire
```

Depuis toute page authentifiée :

```
Déconnexion
    ↓
Connexion
```

### 8.3 TECHNICIAN

```
Connexion
   ↓
Tous les tickets
   │
   ├──→ Ouvrir un ticket
   │       ↓
   │   Détail du ticket
   │       │
   │       └──→ Ajouter commentaire
   │
   ├──→ Si ticket NOUVEAU : Prendre en charge (bouton sur la liste)
   │               ↓
   │            EN_COURS
   │               ↓
   │   Détail du ticket → Marquer résolu
   │               ↓
   │            RESOLU
   │               ↓
   │   Détail du ticket → Fermer
   │               ↓
   │            FERME
   │
   ├──→ Mes tickets
   │       ↓
   │   Ouvrir un de ses tickets
   │
   └──→ Nouveau ticket
           ↓
       Créer un ticket
           ↓
       Détail du ticket
```

Navigation disponible :

```
Tous les tickets ←→ Mes tickets
        │
        └────────→ Nouveau ticket
```

Note — « Prendre en charge » se déclenche depuis la liste **Tous les tickets** (bouton affiché pour chaque ticket au statut NOUVEAU), pas depuis le détail du ticket. Une fois le ticket EN_COURS ou RESOLU, les actions « Marquer résolu » et « Fermer » apparaissent dans le détail du ticket (panneau Actions technicien).

Depuis toute page authentifiée :

```
Déconnexion
    ↓
Connexion
```

### 8.4 ADMIN

Rappel (§3) : ADMIN consulte tous les tickets et peut commenter, mais ne peut ni prendre en charge un ticket ni changer son statut. Le tableau de bord `/admin` est un point d'entrée de supervision en lecture seule (indicateurs MySQL sur l'état des tickets, indicateurs d'activité MongoDB) — il n'ajoute aucune de ces deux permissions.

```
Connexion
   ↓
Administration (/admin)
   │
   ├──→ Tous les tickets
   │       │
   │       ├──→ Ouvrir un ticket
   │       │       ↓
   │       │   Détail du ticket
   │       │       │
   │       │       ├──→ Consulter informations
   │       │       ├──→ Consulter historique
   │       │       └──→ Ajouter commentaire
   │       │
   │       └──→ (aucune action de prise en charge ni de changement de statut)
   │
   ├──→ Mes tickets
   │       ↓
   │   Ouvrir ses tickets
   │
   └──→ Nouveau ticket
           ↓
       Créer un ticket
           ↓
       Détail du ticket
```

`/admin` devient le point d'entrée après connexion (redirection automatique) et la destination du logo MiniTicket dans la navbar pour ce rôle. Les liens « Tous les tickets », « Mes tickets » et « Nouveau ticket » restent accessibles à tout moment depuis la navbar, comme pour TECHNICIAN.

Depuis toute page authentifiée :

```
Déconnexion
    ↓
Connexion
```

## 9. Definition of Done — P0 terminé

- [x] Un USER peut créer un compte
- [x] Il peut se connecter
- [x] Il peut créer un ticket
- [x] La priorité est calculée côté serveur
- [x] Il ne voit que ses propres tickets
- [x] Un TECHNICIAN et un ADMIN peuvent créer et consulter leurs propres tickets, en plus de leurs permissions respectives
- [x] Un TECHNICIAN voit tous les tickets
- [x] Il peut prendre en charge un ticket (assignation + passage auto à EN_COURS)
- [x] Il peut faire progresser le statut (EN_COURS → RÉSOLU → FERMÉ)
- [x] USER peut commenter ses tickets ; TECHNICIAN et ADMIN peuvent commenter tous les tickets autorisés
- [x] Données métier en MySQL
- [x] Événements en MongoDB
- [x] Historique MongoDB relu et affiché
- [x] Autorisations (rôle + propriété) contrôlées serveur
- [x] CSRF traité et testé activement
- [x] XSS traité et testé activement
- [x] Injection SQL : protection structurelle par PDO + requêtes préparées systématiques (§6.1, `03_securite.md`) ; non vérifiée par un test d'injection actif
- [ ] Application fonctionnelle en production
- [x] Installation locale documentée
- [x] Diagrammes (cas d'utilisation, classes, séquence) et 3 maquettes produits (§7)
- [x] Déploiement documenté

*(Coche cette liste en continu dans Notion pendant le développement ; copie la version finale cochée ici à la fin, comme preuve pour le dossier.)*