# MiniTicket — Cadrage

## 1. Contexte

Projet personnel de gestion de tickets informatiques (helpdesk), développé pour démontrer les compétences back-end sécurisé du référentiel Studi. Périmètre, rôles et règles métier auto-définis (pas d'énoncé externe imposé). Durée cible : ~8-9 soirées (2-3h chacune) + une session weekend (~8h, extensible si nécessaire, avec baisse de rendement attendue en fin de session prolongée) — soit ~24-35h au total.

Domaine choisi pour son ancrage réel : expérience personnelle en support IT/helpdesk, donc vocabulaire métier (ticket, priorité, résolution, escalade) déjà maîtrisé — pas un domaine simulé.

**Périmètre** : gestion de tickets d'incidents et de demandes en interne (tout compte peut signaler un problème le concernant, un TECHNICIAN traite). Exclu explicitement : SLA contractuels, notifications temps réel, pièces jointes, base de connaissance, reporting statistique détaillé au-delà du tableau de bord ADMIN (§4, US10).

## 2. Compétences du référentiel visées

Bloc back-end sécurisé — RNCP37674BC02 « Développer la partie back-end d'une application web ou web mobile sécurisée » :
- Mettre en place une base de données relationnelle
- Développer des composants d'accès aux données SQL et NoSQL
- Développer des composants métier côté serveur
- Documenter le déploiement d'une application dynamique web ou web mobile

Aucune compétence du bloc front-end n'est visée par ce projet. Bootstrap (`02_architecture.md` §2) sert uniquement à la mise en forme des vues existantes ; il ne constitue pas un travail de front-end évalué et ne justifie l'ajout d'aucune compétence supplémentaire au périmètre ci-dessus.

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
| Consulter le tableau de bord (US10) | – | – | ✔ |
| Gérer les utilisateurs (US10, bonus) | – | – | ✔ |

RBAC porté par un champ `role` (ENUM) sur `users` — pas de table `roles` séparée.

## 4. User Stories

**US1 — Création de compte** (Visiteur) — pseudo, email, mot de passe hashé via `password_hash()` ; tout compte créé par inscription publique reçoit obligatoirement le rôle USER, quelle que soit la valeur soumise par le formulaire. Les comptes TECHNICIAN et ADMIN sont créés manuellement en base pour le MVP (hors application, pas de mécanisme applicatif de provisioning dans le chemin critique) — voir aussi le seed de démonstration, distinct de ce provisioning, en `05_deploiement.md`.

**US2 — Connexion** (Visiteur → USER/TECHNICIAN/ADMIN) — authentification, session sécurisée, redirection selon rôle.

**US3 — Créer un ticket** (USER, TECHNICIAN, ADMIN — pour soi-même) — titre, description, type (INCIDENT/DEMANDE), catégorie, impact, urgence. Statut initial NOUVEAU. Priorité calculée automatiquement. Pas de création au nom d'un autre compte dans le P0.

**US4 — Liste de ses propres tickets** (USER, TECHNICIAN, ADMIN) — visibilité restreinte aux tickets créés par le compte connecté. Pour TECHNICIAN et ADMIN, cette vue ("mes tickets") est distincte de la vue globale (US5).

**US5 — Liste des tickets côté TECHNICIAN** (TECHNICIAN) — visibilité globale, statut et priorité affichés.

**US6 — Détail d'un ticket + commentaires** (USER, TECHNICIAN, ADMIN) — fil de commentaires en SQL, rattaché au ticket.

**US7 — Prise en charge et changement de statut** (TECHNICIAN) — prise en charge = assignation + transition automatique NOUVEAU → EN_COURS. Transitions suivantes (EN_COURS → RÉSOLU → FERMÉ) distinctes. Un ticket FERMÉ n'accepte plus cette action.

**US8 — Calcul automatique de la priorité** (Système) — calcul serveur lors de la création du ticket à partir de l'impact et de l'urgence. Jamais saisie directement.

**US9 — Historique des événements** (Système) — chaque événement métier significatif concernant le cycle de vie d'un ticket génère un événement MongoDB, relu pour l'affichage.

**US10 — Tableau de bord ADMIN** (ADMIN) — route `/admin`, accessible au seul rôle ADMIN (Guard). Indicateurs en lecture seule : répartition des tickets par statut et par priorité (MySQL), volume d'événements récents par type (MongoDB `ticket_events`). Aucune action d'écriture depuis ce tableau de bord. La gestion des comptes utilisateurs reste hors chemin critique (bonus, non implémentée à ce stade) ; ADMIN conserve par ailleurs l'accès en lecture globale et le droit de commentaire déjà définis en §3 et §5.

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

MPD = `database/miniticket_schema.sql` (types, contraintes, index, seed des catégories — rédigé à partir du MLD validé ci-dessus, non encore exécuté).

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

Pas de jointure avec MySQL au niveau base : la mise en relation se fait applicativement via `ticket_id`, relu et affiché en détail de ticket (US9) et agrégé pour le tableau de bord ADMIN (US10).

### 6.3 Justification de la répartition

- **MySQL** : toute la donnée métier structurée (users, tickets, categories, comments) — intégrité référentielle nécessaire, relations fortes.
- **MongoDB** : collection unique `ticket_events` — historique en append-only, pas de jointure nécessaire, structure tolérante à l'évolution.

## 7. Diagrammes et maquettes — périmètre figé

- Diagramme de cas d'utilisation : 4 acteurs UML (VISITOR, USER, TECHNICIAN, ADMIN), mappé sur US1-US12 — produit dans ce fichier ou en annexe. VISITOR couvre la création de compte et la connexion, avant toute attribution de rôle ; le RBAC applicatif (§3) reste à 3 rôles, VISITOR n'y figurant pas puisqu'il ne possède aucune permission propre
- Diagramme de classes (domaine) — produit dans `02_architecture.md` §9.1
- Diagramme de séquence — un seul cas : création d'un ticket — produit dans `02_architecture.md` §9.2
- 3 maquettes clés uniquement : création de ticket, liste tickets (vue technicien), détail + commentaires + historique
- Personas écartés — les rôles (§3) suffisent

Diagramme de cas d'utilisation et 3 maquettes encore non produits (Step 08 de la roadmap toujours en attente). Diagramme de classes et diagramme de séquence produits (`02_architecture.md` §9.1, §9.2).

## 8. Definition of Done — P0 terminé

- [ ] Un USER peut créer un compte
- [ ] Il peut se connecter
- [ ] Il peut créer un ticket
- [ ] La priorité est calculée côté serveur
- [ ] Il ne voit que ses propres tickets
- [ ] Un TECHNICIAN et un ADMIN peuvent créer et consulter leurs propres tickets, en plus de leurs permissions respectives
- [ ] Un TECHNICIAN voit tous les tickets
- [ ] Il peut prendre en charge un ticket (assignation + passage auto à EN_COURS)
- [ ] Il peut faire progresser le statut (EN_COURS → RÉSOLU → FERMÉ)
- [ ] USER peut commenter ses tickets ; TECHNICIAN et ADMIN peuvent commenter tous les tickets autorisés
- [ ] Données métier en MySQL
- [ ] Événements en MongoDB
- [ ] Historique MongoDB relu et affiché
- [ ] ADMIN peut consulter le tableau de bord (US10) avec indicateurs MySQL et MongoDB à jour
- [ ] Autorisations (rôle + propriété) contrôlées serveur
- [ ] CSRF / XSS / injection SQL traités
- [ ] Application fonctionnelle en production
- [ ] Installation locale documentée
- [ ] Diagrammes (cas d'utilisation, classes, séquence) et 3 maquettes produits (§7)
- [ ] Déploiement documenté

*(Coche cette liste en continu dans Notion pendant le développement ; copie la version finale cochée ici à la fin, comme preuve pour le dossier.)*