# MiniTicket — Tests

## 1. Objectif des tests

Valider, sur l'application réellement déployée (environnement Docker Compose), que le P0 fonctionnel répond aux exigences de sécurité et de règles métier fixées en `01_cadrage.md`, `02_architecture.md` et `03_securite.md`. Deux niveaux : un scénario d'intégration automatisé au niveau Service (`tests/integration/test_27_final.php`), et une passe manuelle HTTP consolidée couvrant RBAC, CSRF, tampering, XSS, robustesse du routage, logs et secrets.

## 2. Environnement de test

- Environnement : conteneurs Docker Compose (`web` Apache+PHP 8.3, `db` MySQL, `mongo`), `APP_ENV=dev`.
- Comptes de test créés pour la campagne (suffixe `750d7647`) :
  - `test27a_750d7647@example.com` — USER (id 51), créateur du ticket de référence.
  - `test27b_750d7647@example.com` — USER (id 53), tiers sans lien avec le ticket de référence.
  - `test27tech_750d7647@example.com` — TECHNICIAN (id 52, promu manuellement en base après inscription, conformément au provisioning documenté en `05_deploiement.md`).
  - `test27admin_750d7647@example.com` — ADMIN, créé via le flux d'inscription HTTP réel puis promu manuellement en base.
- Ticket de référence : id 42, cycle de vie complet exécuté (NOUVEAU → EN_COURS → RESOLU → FERME) avant la passe HTTP.
- Ticket XSS dédié : id 44.

## 3. Tests d'intégration automatisés

### 3.1 Script `test_27_final.php`

Scénario CLI unique, exécuté au niveau Service (sans passer par le serveur HTTP), couvrant en une seule chaîne : inscription de 3 comptes, création de ticket avec calcul de priorité, listage (mes tickets / liste globale), contrôle d'accès (`canView`), commentaires (valide, vide, XSS, sur ticket EN_COURS et FERME), prise en charge, transitions de statut jusqu'à FERME, refus de re-transition post-FERME, historique MongoDB (4 événements, ordre chronologique), lecture des commentaires avec jointure pseudo.

### 3.2 Résultats

- Précondition : base MySQL et MongoDB vides de données de test préalables au run, schéma exécuté, 6 catégories présentes.
- Action : `docker compose exec web php tests/integration/test_27_final.php`.
- Résultat attendu : 100 % des assertions passent, aucune régression sur le cycle de vie complet.
- Résultat obtenu : 26/26 tests PASS. Ticket créé id 42.
- Statut : PASS

## 4. Authentification

- Précondition : 4 comptes existants (USER×2, TECHNICIAN, ADMIN), mots de passe hashés en base.
- Action : `POST /login` pour chacun des 4 comptes, avec jeton CSRF extrait du formulaire GET correspondant.
- Résultat attendu : redirection `/tickets` pour USER, `/tickets/all` pour TECHNICIAN et ADMIN ; session authentifiée créée.
- Résultat obtenu : redirections conformes pour les 4 comptes.
- Statut : PASS

## 5. Permissions — RBAC

- Précondition : sessions actives pour USER, TECHNICIAN, ADMIN, et requête anonyme (sans cookie).
- Action : `GET /tickets/all`, `POST /tickets/{id}/assign`, `POST /tickets/{id}/status` pour chaque rôle.
- Résultat attendu : anonyme → 302 vers `/login` (échec d'authentification, distinct d'un refus de rôle) ; USER → 403 sur les trois routes ; TECHNICIAN → 200 sur `/tickets/all`, comportement métier (200/409 selon état) sur `assign`/`status` ; ADMIN → 200 sur `/tickets/all`, 403 sur `assign`/`status` (droit non accordé par `01_cadrage.md` §3).
- Résultat obtenu :
  - `/tickets/all` : USER → 403, TECHNICIAN → 200, ADMIN → 200.
  - `assign`/`status` : USER → 403, ADMIN → 403, TECHNICIAN → atteint la logique métier (409 sur le ticket 42, déjà FERME).
- Statut : PASS

## 6. Propriété des ressources

- Précondition : ticket 42 appartient à USER A (id 51). USER B (id 53) n'est ni créateur ni assigné.
- Action : `GET /tickets/42` et `POST /tickets/42/comments` avec la session de USER B.
- Résultat attendu : 403 sur les deux routes (contrôle `canView()`, distinct du RBAC par rôle).
- Résultat obtenu : 403 sur la consultation et sur la tentative de commentaire.
- Statut : PASS

## 7. Création et calcul de priorité

- Précondition : session USER active, catégories existantes en base.
- Action : `POST /tickets/create` avec impact/urgence variés, y compris tentative de forcer `priorite` et `statut` en paramètres additionnels (voir §12).
- Résultat attendu : priorité calculée serveur selon la matrice impact × urgence (`01_cadrage.md` §5), jamais reprise d'une valeur soumise ; statut initial toujours NOUVEAU.
- Résultat obtenu : conforme, y compris sous tampering (voir §12).
- Statut : PASS

## 8. Workflow des tickets

- Précondition : ticket créé, statut NOUVEAU.
- Action : prise en charge (NOUVEAU → EN_COURS), transition EN_COURS → RESOLU, RESOLU → FERME, tentative de transition post-FERME.
- Résultat attendu : chaque transition respecte la machine à états (`01_cadrage.md` §4) ; toute transition hors ordre est rejetée.
- Résultat obtenu : cycle complet validé en intégration (§3) ; tentative de re-transition sur ticket FERME en HTTP → 409 Conflict.
- Statut : PASS

## 9. Commentaires

- Précondition : ticket à différents états (NOUVEAU/EN_COURS/FERME), commentaire vide, commentaire avec payload XSS.
- Action : ajout de commentaires par le créateur à chaque état ; ajout d'un commentaire vide ; ajout d'un commentaire `<script>alert(1)</script>`.
- Résultat attendu : commentaire toujours autorisé quel que soit le statut du ticket (aucune restriction dans `01_cadrage.md` §3) ; contenu vide refusé ; XSS stocké tel quel, échappé uniquement à l'affichage.
- Résultat obtenu : conforme sur les 4 cas testés en intégration (§3) ; confirmé en HTTP pour le XSS (voir §13).
- Statut : PASS

## 10. Historique MongoDB

- Précondition : ticket ayant traversé un cycle complet (création, prise en charge, 2 changements de statut).
- Action : lecture de l'historique via `TicketService::history()`.
- Résultat attendu : 4 événements, ordre chronologique CREATION → PRISE_EN_CHARGE → CHANGEMENT_STATUT → CHANGEMENT_STATUT.
- Résultat obtenu : conforme.
- Statut : PASS

## 11. Protection CSRF

- Précondition : 7 routes POST identifiées (`register`, `login`, `logout`, `tickets/create`, `tickets/{id}/comments`, `tickets/{id}/assign`, `tickets/{id}/status`).
- Action : rejeu de requêtes valides en omettant `csrf_token`, puis avec un jeton falsifié, sur `create`, `comments`, `assign`, `status`.
- Résultat attendu : 403 Forbidden dans tous les cas, aucun traitement métier déclenché.
- Résultat obtenu : 403 confirmé sur création sans token, commentaire avec faux token, assign sans token, status avec faux token.
- Statut : PASS

## 12. Protection contre le tampering

- Précondition : formulaires d'inscription et de création de ticket n'exposant pas les champs `role`, `priorite`, `statut`, `user_id`, `technician_id`.
- Action : injection de ces champs en plus des champs légitimes, sur `POST /register` (`role=ADMIN`) et `POST /tickets/create` (`priorite=P1`, `statut=FERME`, `user_id=53`, `technician_id=52`, impact/urgence FAIBLE/FAIBLE).
- Résultat attendu : `role` soumis ignoré (compte créé en USER) ; `user_id` remplacé par celui de la session ; `technician_id` NULL ; `statut` forcé à NOUVEAU ; `priorite` recalculée serveur (P4 pour FAIBLE/FAIBLE).
- Résultat obtenu : conforme sur tous les champs testés.
- Statut : PASS

## 13. Protection XSS

- Précondition : titre et commentaire acceptant du texte libre, affichés via `e()` dans `tickets/show.php`.
- Action : création d'un ticket (id 44) avec titre `<script>alert(1)</script>` ; ajout d'un commentaire avec le même payload (§3/§9).
- Résultat attendu : rendu échappé (`&lt;script&gt;alert(1)&lt;/script&gt;`), jamais de balise `<script>` interprétée.
- Résultat obtenu : conforme sur le titre (ticket 44) et sur le commentaire.
- Statut : PASS

## 14. Validation des entrées et routes invalides

- Précondition : routes `/tickets/{id}` avec `id` non numérique ou inexistant ; route non déclarée.
- Action : `GET /tickets/abc` avec USER authentifié, `GET /tickets/999999` avec USER authentifié, `GET /tickets/allXYZ`, `GET /route-qui-nexiste-pas` ; `POST /tickets/create` avec `category_id=9999`.
- Résultat attendu : 404 sur les identifiants invalides/inexistants et les routes non déclarées ; `category_id` invalide rejeté proprement côté validation métier, sans exception SQL exposée.
- Résultat obtenu : `/tickets/abc` → 404, `/tickets/999999` → 404, `/tickets/allXYZ` → 404, `/route-qui-nexiste-pas` → 404. `category_id=9999` → 200, formulaire réaffiché, message "Catégorie invalide.", aucun 500, aucune trace SQL.
- Statut : PASS

## 15. Sessions et cookies

- Précondition : authentification HTTP réalisée avec les comptes USER, TECHNICIAN et ADMIN.
- Action : inspection des en-têtes `Set-Cookie` retournés lors des connexions.
- Résultat attendu : cookie de session protégé par `HttpOnly` et `SameSite=Lax`. L'attribut `Secure` est réservé à l'environnement HTTPS de production.
- Résultat obtenu : les cookies `PHPSESSID` observés comportent `HttpOnly` et `SameSite=Lax`. L'attribut `Secure` n'est pas présent dans l'environnement local HTTP, conformément à la configuration de développement.
- `session_regenerate_id()` après authentification n'a pas fait l'objet d'un test dédié.
- Statut : PASS pour les attributs de cookie observés ; régénération de l'identifiant de session non couverte.

## 16. Logs et gestion des erreurs

- Précondition : ensemble des tests §4 à §14 exécutés.
- Action : recherche de la chaîne `Fichier :` (signature du bloc d'exception affiché en `APP_ENV=dev`) dans les corps de réponse capturés ; recherche de `warning|notice|deprecated|errorexception|fatal` dans les logs du conteneur `web`.
- Résultat attendu : aucune occurrence.
- Résultat obtenu : aucune erreur, warning, notice, exception ou fatal détecté sur les 100 dernières lignes de log.
- Statut : PASS

## 17. Vérification Git et secrets

- Précondition : dépôt Git du projet.
- Action : vérification des fichiers suivis (`.env`, `.log`, `vendor/`).
- Résultat attendu : aucun de ces fichiers/dossiers suivi par Git.
- Résultat obtenu : conforme — aucun `.env`, `.log` ou `vendor/` suivi.
- Statut : PASS

## 18. Bilan final

P0 validé sur l'ensemble des critères testés : intégration (26/26), authentification, RBAC, propriété des ressources, workflow, calcul de priorité, MongoDB, CSRF, tampering, XSS (titre et commentaire), robustesse du routage, validation des entrées, attributs de cookie de session, logs, hygiène Git. Un seul point non couvert : régénération de l'identifiant de session (`session_regenerate_id()`) après authentification, mesure présente dans `03_securite.md` mais non vérifiée par un test dédié.