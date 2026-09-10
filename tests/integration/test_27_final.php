<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\MongoConnection;
use App\Repositories\CategoryRepository;
use App\Repositories\CommentRepository;
use App\Repositories\TicketEventRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Services\CommentService;
use App\Services\TicketService;
use App\Services\UserService;

$dotenv = Dotenv\Dotenv::createImmutable($root);
$dotenv->load();

$pdo = (new Database())->getConnection();
$categoriesRepo = new CategoryRepository($pdo);
$ticketsRepo = new TicketRepository($pdo);
$eventsRepo = new TicketEventRepository((new MongoConnection())->getDatabase());
$ticketService = new TicketService($ticketsRepo, $categoriesRepo, $eventsRepo);
$commentService = new CommentService(new CommentRepository($pdo));
$userService = new UserService(new UserRepository($pdo));

$failures = 0;

function check(string $label, bool $condition, int &$failures): void
{
    echo ($condition ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$condition) {
        $failures++;
    }
}

$suffix = bin2hex(random_bytes(4));

// --- 0. Inscription (rôle toujours forcé à USER, même si on force ensuite en base pour tester TECHNICIAN) ---
$creatorId = $userService->register("test27a_{$suffix}", "test27a_{$suffix}@example.com", 'Test1234!')['user_id'];
$technicianId = $userService->register("test27tech_{$suffix}", "test27tech_{$suffix}@example.com", 'Test1234!')['user_id'];
$outsiderId = $userService->register("test27b_{$suffix}", "test27b_{$suffix}@example.com", 'Test1234!')['user_id'];
check('Inscription : 3 comptes créés', is_int($creatorId) && is_int($technicianId) && is_int($outsiderId), $failures);

$categoryId = (string) $categoriesRepo->findAll()[0]['id'];

// --- 1. Création + priorité ---
$created = $ticketService->create($creatorId, 'USER', "Ticket final {$suffix}", 'Description finale.', 'INCIDENT', $categoryId, 'ELEVE', 'ELEVEE');
check('Création : aucune erreur', $created['errors'] === [], $failures);
$ticketId = $created['ticket_id'];
$ticket = $ticketService->findById($ticketId);
check('Création : priorité calculée P1 (ELEVE/ELEVEE)', $ticket['priorite'] === 'P1', $failures);
check('Création : statut initial NOUVEAU', $ticket['statut'] === 'NOUVEAU', $failures);

// --- 2. Listing : mes tickets / liste globale ---
$mine = $ticketService->listMine($creatorId);
check('listMine() : contient le ticket créé', in_array($ticketId, array_column($mine, 'id'), true), $failures);
$all = $ticketService->listAll();
check('listAll() : contient le ticket créé', in_array($ticketId, array_column($all, 'id'), true), $failures);

// --- 3. Contrôle d'accès ---
check('canView() : créateur autorisé', $ticketService->canView($ticket, $creatorId, 'USER'), $failures);
check('canView() : tiers refusé', !$ticketService->canView($ticket, $outsiderId, 'USER'), $failures);
check('canView() : TECHNICIAN autorisé', $ticketService->canView($ticket, $technicianId, 'TECHNICIAN'), $failures);
check('canView() : ADMIN autorisé', $ticketService->canView($ticket, $outsiderId, 'ADMIN'), $failures);

// --- 4. Commentaires : propriétaire OK, contenu vide refusé, XSS/multiligne préservés ---
$comment = $commentService->add($ticketId, $creatorId, "Premier commentaire.\nDeuxième ligne.");
check('Commentaire valide : aucune erreur', $comment['errors'] === [], $failures);
$emptyComment = $commentService->add($ticketId, $creatorId, '   ');
check('Commentaire vide : refusé', isset($emptyComment['errors']['contenu']), $failures);
$xssComment = $commentService->add($ticketId, $creatorId, '<script>alert(1)</script>');
check('Commentaire XSS : stocké tel quel (échappement = affichage, pas stockage)', $xssComment['errors'] === [], $failures);

// --- 5. Prise en charge ---
$assigned = $ticketService->assign($ticket, $technicianId);
check('Prise en charge : succès', $assigned === true, $failures);
$ticket = $ticketService->findById($ticketId);
check('Après prise en charge : statut EN_COURS', $ticket['statut'] === 'EN_COURS', $failures);
check('Après prise en charge : technician_id correct', (int) $ticket['technician_id'] === $technicianId, $failures);

// --- 5bis. Commentaire sur ticket EN_COURS toujours possible pour le créateur ---
$commentEnCours = $commentService->add($ticketId, $creatorId, 'Commentaire pendant le traitement.');
check('Commentaire pendant EN_COURS : toujours autorisé (créateur)', $commentEnCours['errors'] === [], $failures);

// --- 6. Transitions de statut : EN_COURS -> RESOLU -> FERME ---
$toResolu = $ticketService->transitionTo($ticket, 'RESOLU', $technicianId);
check('Transition EN_COURS -> RESOLU : succès', $toResolu === true, $failures);
$ticket = $ticketService->findById($ticketId);

$toFerme = $ticketService->transitionTo($ticket, 'FERME', $technicianId);
check('Transition RESOLU -> FERME : succès', $toFerme === true, $failures);
$ticket = $ticketService->findById($ticketId);
check('Statut final : FERME', $ticket['statut'] === 'FERME', $failures);

// --- 6bis. Vérification croisée : un commentaire reste possible même sur un ticket FERME ---
// (01_cadrage.md §3 ne restreint le droit de commenter par aucun statut — comportement attendu, pas un oubli)
$commentFerme = $commentService->add($ticketId, $creatorId, 'Commentaire après clôture.');
check('Commentaire sur ticket FERME : toujours autorisé (comportement attendu, cf. cadrage §3)', $commentFerme['errors'] === [], $failures);

// --- 6ter. Refus attendu : re-transition sur ticket déjà FERME ---
$refusedAgain = $ticketService->transitionTo($ticket, 'RESOLU', $technicianId);
check('Transition sur ticket FERME : refusée', $refusedAgain === false, $failures);

// --- 7. Historique complet, ordre chronologique ---
$history = $ticketService->history($ticketId);
$types = array_column($history, 'type_evenement');
check('Historique : 4 événements (CREATION, PRISE_EN_CHARGE, CHANGEMENT_STATUT x2)', count($history) === 4, $failures);
check('Historique : ordre CREATION -> PRISE_EN_CHARGE -> CHANGEMENT_STATUT -> CHANGEMENT_STATUT', $types === ['CREATION', 'PRISE_EN_CHARGE', 'CHANGEMENT_STATUT', 'CHANGEMENT_STATUT'], $failures);

// --- 8. Commentaires finaux : lecture avec pseudo (JOIN) ---
$allComments = $commentService->listByTicket($ticketId);
check('Commentaires : 4 au total (valide, EN_COURS, FERME, XSS — le vide n\'a jamais été inséré)', count($allComments) === 4, $failures);
check('Commentaires : pseudo présent sur chaque ligne', array_reduce($allComments, fn ($carry, $c) => $carry && isset($c['pseudo']) && $c['pseudo'] !== '', true), $failures);

echo PHP_EOL;
echo $failures === 0 ? 'TOUS LES TESTS SONT PASSES.' : "{$failures} TEST(S) EN ECHEC.";
echo PHP_EOL;
echo "TICKET_ID={$ticketId} SUFFIX={$suffix}" . PHP_EOL;

exit($failures === 0 ? 0 : 1);