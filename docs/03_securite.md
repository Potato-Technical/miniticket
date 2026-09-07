# MiniTicket — Sécurité

| Mesure | Justification |
|---|---|
| `password_hash` / `password_verify` | Aucun mot de passe en clair, même en cas de fuite de la base |
| `session_regenerate_id()` après login | Empêche la fixation de session |
| Déconnexion explicite (destruction de session) | Évite qu'une session reste valide après un logout apparent |
| Cookies `httponly` | Empêche l'accès au cookie de session via JS |
| Cookies `secure` en production | Empêche la transmission du cookie en clair sur HTTP |
| Cookies `samesite` | Limite l'envoi du cookie dans les requêtes cross-site et réduit le risque CSRF |
| Token CSRF sur les formulaires | Empêche qu'un site tiers déclenche une action au nom de l'utilisateur connecté |
| Validation serveur systématique | Le front peut être contourné, seule la validation serveur fait foi |
| PDO + requêtes préparées | Empêche l'injection SQL sur les valeurs correctement passées comme paramètres, sans concaténation de données utilisateur |
| `htmlspecialchars` à l'affichage | Encode les données dynamiques lors de leur insertion dans du contenu HTML afin d'empêcher leur interprétation comme balisage ou script |
| RBAC USER/TECHNICIAN/ADMIN, contrôlé par le Guard | Empêche un utilisateur d'accéder à une action réservée à un autre rôle (`02_architecture.md` §7) |
| Rôle imposé à `USER` lors de l'inscription publique, ignoré si soumis par le formulaire | Empêche l'élévation de privilège via un champ manipulé côté client (`01_cadrage.md` §4, US1) |
| Contrôle de propriété des tickets, porté par les Services | Empêche un compte de consulter/modifier les tickets d'un autre compte hors de son propre périmètre, quel que soit son rôle — sauf lecture globale TECHNICIAN/ADMIN (US5) ; distinct du RBAC par rôle, vérifié dans `CommentService` et `TicketService` (`02_architecture.md` §7) |
| Secrets dans `.env`, exclu de Git | Aucun identifiant sensible dans l'historique du dépôt |
| Erreurs détaillées désactivées en production | Évite de divulguer stack traces ou requêtes SQL |
| Journalisation serveur des erreurs | Permet le diagnostic sans exposer l'information au client |

Bonus (hors P0) : limitation des tentatives de connexion (rate-limiting) contre le brute-force.
