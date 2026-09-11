<?php

declare(strict_types=1);

/**
 * Scénario CLI, niveau Service (sans passer par le serveur HTTP) — même convention
 * que test_27_final.php. Couvre : agrégation MySQL (TicketRepository::countByStatusGroups),
 * agrégation MongoDB (TicketEventRepository::countByType/findRecent), assemblage
 * TicketService::dashboardStats(), et le seed de démonstration (database/seed_demo.php) :
 * comptes créés, rôles corrects, mots de passe utilisables, rejouabilité sans doublon.
 *
 * Le contrôle RBAC HTTP de /admin (ADMIN autorisé, USER/TECHNICIAN/anonyme refusés) et
 * la vérification visuelle du tableau de bord relèvent de la passe manuelle HTTP décrite
 * en 04_tests.md (même convention que le RBAC existant sur /tickets/all) — non dupliqués
 * ici en automatisé, cf. rapport de livraison.
 */

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\MongoConnection;
use App\Repositories\CategoryRepository;
use App\Repositories\TicketEventRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Services\TicketService;
use App\Services\UserService;

$dotenv = Dotenv\Dotenv::createImmutable($root);
$dotenv->load();

// Même source de vérité que database/seed_demo.php — jamais de mot de passe en dur
// dans un fichier versionné. Doit rester identique à la valeur utilisée par le seed
// pour que password_verify() ait un sens sur les comptes de démonstration.
$demoPasswordCheck = $_ENV['DEMO_PASSWORD'] ?? '';

if (!is_string($demoPasswordCheck) || trim($demoPasswordCheck) === '') {
    fwrite(STDERR, 'DEMO_PASSWORD absent ou vide dans .env — requis pour vérifier les comptes de démonstration (voir .env.example et 05_deploiement.md).' . PHP_EOL);
    exit(1);
}

$pdo = (new Database())->getConnection();
$categoriesRepo = new CategoryRepository($pdo);
$ticketsRepo = new TicketRepository($pdo);
$eventsRepo = new TicketEventRepository((new MongoConnection())->getDatabase());
$ticketService = new TicketService($ticketsRepo, $categoriesRepo, $eventsRepo);
$userRepo = new UserRepository($pdo);
$userService = new UserService($userRepo);

$failures = 0;

function check(string $label, bool $condition, int &$failures): void
{
    echo ($condition ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$condition) {
        $failures++;
    }
}

$suffix = bin2hex(random_bytes(4));

// Baselines mesurées avant toute écriture de ce scénario, pour isoler exactement
// les deltas MySQL/Mongo produits par les opérations ci-dessous.
$statsBefore = $ticketsRepo->countByStatusGroups();
$eventsBefore = $eventsRepo->countByType();

$creatorId = $userService->register("test28_{$suffix}", "test28_{$suffix}@example.com", 'Test1234!')['user_id'];
$technicianId = $userService->register("test28tech_{$suffix}", "test28tech_{$suffix}@example.com", 'Test1234!')['user_id'];
$categoryId = (string) $categoriesRepo->findAll()[0]['id'];

// --- 1. Statistiques MySQL (TicketRepository::countByStatusGroups) ---
$created = $ticketService->create($creatorId, 'USER', "Stats {$suffix}", 'Description.', 'INCIDENT', $categoryId, 'FAIBLE', 'FAIBLE');
check('Stats MySQL : création du ticket de test sans erreur', $created['errors'] === [], $failures);

$statsAfterCreate = $ticketsRepo->countByStatusGroups();

check('Stats MySQL : total incrémenté de 1', $statsAfterCreate['total'] === $statsBefore['total'] + 1, $failures);
check('Stats MySQL : nouveau incrémenté de 1', $statsAfterCreate['nouveau'] === $statsBefore['nouveau'] + 1, $failures);
check('Stats MySQL : en_cours inchangé', $statsAfterCreate['en_cours'] === $statsBefore['en_cours'], $failures);
check(
    'Stats MySQL : total = nouveau + en_cours + termines',
    $statsAfterCreate['total'] === $statsAfterCreate['nouveau'] + $statsAfterCreate['en_cours'] + $statsAfterCreate['termines'],
    $failures
);

// --- 2. Agrégation MongoDB par type (TicketEventRepository::countByType) ---
$ticket = $ticketService->findById($created['ticket_id']);

$ticketService->assign($ticket, $technicianId);
$ticket = $ticketService->findById($created['ticket_id']);
$ticketService->transitionTo($ticket, 'RESOLU', $technicianId);

$eventsAfter = $eventsRepo->countByType();

check(
    'Mongo : comptage CREATION incrémenté de 1',
    $eventsAfter['CREATION'] === ($eventsBefore['CREATION'] ?? 0) + 1,
    $failures
);
check(
    'Mongo : comptage PRISE_EN_CHARGE incrémenté de 1',
    $eventsAfter['PRISE_EN_CHARGE'] === ($eventsBefore['PRISE_EN_CHARGE'] ?? 0) + 1,
    $failures
);
check(
    'Mongo : comptage CHANGEMENT_STATUT incrémenté de 1',
    $eventsAfter['CHANGEMENT_STATUT'] === ($eventsBefore['CHANGEMENT_STATUT'] ?? 0) + 1,
    $failures
);

// --- 3. Activité récente triée du plus récent au plus ancien (TicketEventRepository::findRecent) ---
$recent = $eventsRepo->findRecent(5);
check('Mongo : findRecent() retourne des événements', count($recent) > 0, $failures);

$sorted = true;
for ($i = 1, $count = count($recent); $i < $count; $i++) {
    if ($recent[$i - 1]['horodatage']->toDateTime() < $recent[$i]['horodatage']->toDateTime()) {
        $sorted = false;
        break;
    }
}
check('Mongo : activité récente triée du plus récent au plus ancien', $sorted, $failures);

// --- 4. TicketService::dashboardStats() — assemblage bout en bout ---
$stats = $ticketService->dashboardStats(5);
check(
    'dashboardStats() : indicateurs MySQL présents',
    isset($stats['tickets']['total'], $stats['tickets']['nouveau'], $stats['tickets']['en_cours'], $stats['tickets']['termines']),
    $failures
);
check('dashboardStats() : indicateurs Mongo présents', isset($stats['events']), $failures);
check(
    'dashboardStats() : activité récente formatée (horodatage_formate)',
    $stats['recent'] === [] || isset($stats['recent'][0]['horodatage_formate']),
    $failures
);

// --- 5. Seed de démonstration : rejouable, comptes corrects ---
$seedCommand = 'php ' . escapeshellarg($root . '/database/seed_demo.php') . ' 2>&1';
$seedOutput1 = shell_exec($seedCommand);
$seedOutput2 = shell_exec($seedCommand);

check('Seed : premier run sans erreur fatale', $seedOutput1 !== null && !str_contains($seedOutput1, 'Fatal error'), $failures);
check('Seed : second run sans erreur fatale', $seedOutput2 !== null && !str_contains($seedOutput2, 'Fatal error'), $failures);
check(
    'Seed : second run ne recrée aucun compte (idempotent)',
    $seedOutput2 !== null && str_contains($seedOutput2, 'Compte déjà présent : user@miniticket.local'),
    $failures
);
check(
    'Seed : second run ne recrée aucun ticket (idempotent)',
    $seedOutput2 !== null && substr_count($seedOutput2, 'Ticket déjà présent') === 3,
    $failures
);

$demoUser = $userRepo->findByEmail('user@miniticket.local');
$demoTechnician = $userRepo->findByEmail('technician@miniticket.local');
$demoAdmin = $userRepo->findByEmail('admin@miniticket.local');

check('Seed : compte demo_user existe, rôle USER', $demoUser !== null && $demoUser['role'] === 'USER', $failures);
check('Seed : compte demo_technician existe, rôle TECHNICIAN', $demoTechnician !== null && $demoTechnician['role'] === 'TECHNICIAN', $failures);
check('Seed : compte demo_admin existe, rôle ADMIN', $demoAdmin !== null && $demoAdmin['role'] === 'ADMIN', $failures);

check(
    'Seed : mot de passe demo_user utilisable',
    $demoUser !== null && password_verify($demoPasswordCheck, $demoUser['password_hash']),
    $failures
);
check(
    'Seed : mot de passe demo_technician utilisable',
    $demoTechnician !== null && password_verify($demoPasswordCheck, $demoTechnician['password_hash']),
    $failures
);
check(
    'Seed : mot de passe demo_admin utilisable',
    $demoAdmin !== null && password_verify($demoPasswordCheck, $demoAdmin['password_hash']),
    $failures
);

$demoTickets = array_filter(
    $ticketService->listMine((int) $demoUser['id']),
    static fn (array $t): bool => str_starts_with($t['titre'], '[DEMO]')
);
check('Seed : exactement 3 tickets [DEMO] pour demo_user après 2 runs (pas de doublon)', count($demoTickets) === 3, $failures);

echo PHP_EOL;
echo $failures === 0 ? 'TOUS LES TESTS SONT PASSES.' : "{$failures} TEST(S) EN ECHEC.";
echo PHP_EOL;

exit($failures === 0 ? 0 : 1);
