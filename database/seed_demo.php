<?php

declare(strict_types=1);

/**
 * Seed de démonstration — ENVIRONNEMENT LOCAL UNIQUEMENT, jamais à exécuter en production.
 *
 * Crée 3 comptes de démonstration (USER, TECHNICIAN, ADMIN) et quelques tickets
 * représentatifs (statuts et priorités variés), exclusivement via les Services
 * applicatifs (UserService, TicketService) — jamais d'INSERT SQL/Mongo indépendants —
 * pour garantir que MySQL et MongoDB restent cohérents entre eux.
 *
 * Rejouable sans créer de doublons : chaque compte est identifié par son email,
 * chaque ticket de démonstration par son titre exact.
 *
 * Usage : docker compose exec web php database/seed_demo.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\MongoConnection;
use App\Repositories\CategoryRepository;
use App\Repositories\TicketEventRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Services\TicketService;
use App\Services\UserService;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Mot de passe de démonstration, strictement local — jamais versionné (dépôt public),
// jamais utilisé hors de cet environnement de développement. Défini dans .env
// (non suivi par Git, voir .gitignore), documenté sans valeur réelle dans .env.example.
$demoPassword = $_ENV['DEMO_PASSWORD'] ?? '';

if (!is_string($demoPassword) || trim($demoPassword) === '') {
    fwrite(STDERR, 'DEMO_PASSWORD absent ou vide dans .env — voir .env.example et 05_deploiement.md.' . PHP_EOL);
    exit(1);
}

$pdo = (new Database())->getConnection();
$userRepo = new UserRepository($pdo);
$userService = new UserService($userRepo);
$categoryRepo = new CategoryRepository($pdo);
$ticketService = new TicketService(
    new TicketRepository($pdo),
    $categoryRepo,
    new TicketEventRepository((new MongoConnection())->getDatabase())
);

/**
 * Crée le compte s'il n'existe pas déjà (email = clé d'idempotence). L'inscription
 * publique (UserService::register) force toujours le rôle USER — l'élévation vers
 * TECHNICIAN/ADMIN est faite par la même procédure que le provisioning documenté
 * en 05_deploiement.md (« insertion via le flux d'inscription HTTP standard, puis
 * élévation de rôle par requête SQL directe »).
 *
 * Si le compte existe déjà avec un rôle différent de celui attendu, le rôle est
 * corrigé — ces 3 emails sont des comptes de démonstration entièrement possédés
 * par ce seed (jamais d'autre compte touché par cette fonction) : le contrat
 * « email de démo -> rôle attendu » doit rester garanti à chaque exécution.
 *
 * @return array<string, mixed>
 */
function ensureDemoAccount(
    UserRepository $userRepo,
    UserService $userService,
    PDO $pdo,
    string $pseudo,
    string $email,
    string $role,
    string $password
): array {
    $existing = $userRepo->findByEmail($email);

    if ($existing !== null) {
        if ($existing['role'] === $role) {
            echo "[=] Compte déjà présent : {$email} ({$existing['role']})" . PHP_EOL;

            return $existing;
        }

        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $existing['id']]);

        echo "[~] Rôle corrigé : {$email} ({$existing['role']} -> {$role})" . PHP_EOL;

        return $userRepo->findByEmail($email);
    }

    $result = $userService->register($pseudo, $email, $password);

    if ($result['errors'] !== []) {
        throw new RuntimeException("Échec création {$email} : " . implode(' ', $result['errors']));
    }

    if ($role !== 'USER') {
        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $result['user_id']]);
    }

    echo "[+] Compte créé : {$email} ({$role})" . PHP_EOL;

    return $userRepo->findByEmail($email);
}

/**
 * @return array<string, mixed>|null
 */
function findDemoTicket(TicketService $ticketService, int $creatorId, string $titre): ?array
{
    foreach ($ticketService->listMine($creatorId) as $ticket) {
        if ($ticket['titre'] === $titre) {
            return $ticket;
        }
    }

    return null;
}

/**
 * Crée le ticket s'il n'existe pas déjà (titre exact = clé d'idempotence), via
 * TicketService::create() — calcul de priorité, statut initial et événement
 * MongoDB CREATION gérés par le Service, comme pour tout ticket applicatif.
 *
 * @return array<string, mixed>
 */
function ensureDemoTicket(
    TicketService $ticketService,
    int $creatorId,
    string $titre,
    string $description,
    string $type,
    int $categoryId,
    string $impact,
    string $urgence
): array {
    $existing = findDemoTicket($ticketService, $creatorId, $titre);

    if ($existing !== null) {
        echo "[=] Ticket déjà présent : {$titre}" . PHP_EOL;

        return $existing;
    }

    $result = $ticketService->create(
        $creatorId,
        'USER',
        $titre,
        $description,
        $type,
        (string) $categoryId,
        $impact,
        $urgence
    );

    if ($result['errors'] !== []) {
        throw new RuntimeException("Échec création ticket « {$titre} » : " . implode(' ', $result['errors']));
    }

    echo "[+] Ticket créé : {$titre}" . PHP_EOL;

    return $ticketService->findById($result['ticket_id']);
}

echo '--- Comptes de démonstration ---' . PHP_EOL;

$demoUser = ensureDemoAccount($userRepo, $userService, $pdo, 'demo_user', 'user@miniticket.local', 'USER', $demoPassword);
$demoTechnician = ensureDemoAccount($userRepo, $userService, $pdo, 'demo_technician', 'technician@miniticket.local', 'TECHNICIAN', $demoPassword);
$demoAdmin = ensureDemoAccount($userRepo, $userService, $pdo, 'demo_admin', 'admin@miniticket.local', 'ADMIN', $demoPassword);

echo PHP_EOL . '--- Tickets de démonstration ---' . PHP_EOL;

$categories = [];
foreach ($categoryRepo->findAll() as $category) {
    $categories[$category['libelle']] = (int) $category['id'];
}

// Vérification stricte : les catégories nécessaires au seed doivent exister sous
// leur libellé exact avant toute création de ticket. Un ID numérique par défaut
// masquerait silencieusement une catégorie absente ou mal encodée.
foreach (['Matériel', 'Compte / Accès', 'Logiciel'] as $libelleRequis) {
    if (!isset($categories[$libelleRequis])) {
        throw new RuntimeException(
            "Catégorie de seed introuvable : « {$libelleRequis} ». " .
            'Vérifiez que database/miniticket_schema.sql a bien été importé (SET NAMES utf8mb4 inclus) ' .
            'et que la table categories contient les 6 libellés attendus.'
        );
    }
}

// Ticket 1 — reste NOUVEAU (non pris en charge), priorité P1 (impact ELEVE / urgence ELEVEE).
$ticket1 = ensureDemoTicket(
    $ticketService,
    (int) $demoUser['id'],
    '[DEMO] Le clavier ne répond plus',
    "Le clavier de mon poste ne répond plus depuis ce matin.\nDéjà testé sur un autre port USB, sans succès.",
    'INCIDENT',
    $categories['Matériel'],
    'ELEVE',
    'ELEVEE'
);

// Ticket 2 — pris en charge -> EN_COURS, priorité P3 (impact MOYEN / urgence MOYENNE).
$ticket2 = ensureDemoTicket(
    $ticketService,
    (int) $demoUser['id'],
    '[DEMO] Accès VPN à réinitialiser',
    'Impossible de me connecter au VPN depuis le changement de mot de passe.',
    'DEMANDE',
    $categories['Compte / Accès'],
    'MOYEN',
    'MOYENNE'
);

if ($ticket2['statut'] === 'NOUVEAU') {
    $ticketService->assign($ticket2, (int) $demoTechnician['id']);
    $ticket2 = $ticketService->findById((int) $ticket2['id']);
    echo "[+] Ticket pris en charge : {$ticket2['titre']}" . PHP_EOL;
}

// Ticket 3 — cycle complet jusqu'à FERME, priorité P4 (impact FAIBLE / urgence FAIBLE).
$ticket3 = ensureDemoTicket(
    $ticketService,
    (int) $demoUser['id'],
    '[DEMO] Installation du logiciel de facturation',
    "Merci d'installer le logiciel de facturation sur mon poste avant vendredi.",
    'DEMANDE',
    $categories['Logiciel'],
    'FAIBLE',
    'FAIBLE'
);

if ($ticket3['statut'] === 'NOUVEAU') {
    $ticketService->assign($ticket3, (int) $demoTechnician['id']);
    $ticket3 = $ticketService->findById((int) $ticket3['id']);
    echo "[+] Ticket pris en charge : {$ticket3['titre']}" . PHP_EOL;
}

if ($ticket3['statut'] === 'EN_COURS') {
    $ticketService->transitionTo($ticket3, 'RESOLU', (int) $demoTechnician['id']);
    $ticket3 = $ticketService->findById((int) $ticket3['id']);
    echo "[+] Ticket résolu : {$ticket3['titre']}" . PHP_EOL;
}

if ($ticket3['statut'] === 'RESOLU') {
    $ticketService->transitionTo($ticket3, 'FERME', (int) $demoTechnician['id']);
    $ticket3 = $ticketService->findById((int) $ticket3['id']);
    echo "[+] Ticket fermé : {$ticket3['titre']}" . PHP_EOL;
}

echo PHP_EOL . 'Seed de démonstration terminé.' . PHP_EOL;
echo 'Comptes : user@miniticket.local / technician@miniticket.local / admin@miniticket.local' . PHP_EOL;
echo 'Mot de passe : valeur de DEMO_PASSWORD dans .env (jamais affiché ici).' . PHP_EOL;
