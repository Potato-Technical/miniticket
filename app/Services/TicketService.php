<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Repositories\TicketEventRepository;
use App\Repositories\TicketRepository;
use MongoDB\BSON\UTCDateTime;

/**
 * Logique métier US3 (création de ticket) et US8 (calcul de priorité).
 */
class TicketService
{
    private const TYPES = ['INCIDENT', 'DEMANDE'];
    private const IMPACTS = ['FAIBLE', 'MOYEN', 'ELEVE'];
    private const URGENCES = ['FAIBLE', 'MOYENNE', 'ELEVEE'];

    /**
     * Matrice impact × urgence → priorité (01_cadrage.md §5). Clé de premier niveau : impact.
     */
    private const PRIORITY_MATRIX = [
        'ELEVE' => ['ELEVEE' => 'P1', 'MOYENNE' => 'P2', 'FAIBLE' => 'P3'],
        'MOYEN' => ['ELEVEE' => 'P2', 'MOYENNE' => 'P3', 'FAIBLE' => 'P4'],
        'FAIBLE' => ['ELEVEE' => 'P3', 'MOYENNE' => 'P4', 'FAIBLE' => 'P4'],
    ];

    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly CategoryRepository $categories,
        private readonly TicketEventRepository $events
    ) {
    }

    /**
     * @return array{errors: array<string,string>, ticket_id: int|null}
     */
    public function create(
        int $userId,
        string $role,
        string $titre,
        string $description,
        string $type,
        string $categoryId,
        string $impact,
        string $urgence
    ): array {
        $titre = trim($titre);
        $description = trim($description);

        $errors = [];

        if ($titre === '') {
            $errors['titre'] = 'Le titre est requis.';
        } elseif (strlen($titre) > 150) {
            $errors['titre'] = 'Le titre est trop long.';
        }

        if ($description === '') {
            $errors['description'] = 'La description est requise.';
        }

        if (!in_array($type, self::TYPES, true)) {
            $errors['type'] = 'Type invalide.';
        }

        if (!in_array($impact, self::IMPACTS, true)) {
            $errors['impact'] = 'Impact invalide.';
        }

        if (!in_array($urgence, self::URGENCES, true)) {
            $errors['urgence'] = 'Urgence invalide.';
        }

        $categoryIdInt = ctype_digit($categoryId) ? (int) $categoryId : null;

        if ($categoryIdInt === null || !$this->categories->existsById($categoryIdInt)) {
            $errors['category_id'] = 'Catégorie invalide.';
        }

        if ($errors !== []) {
            return [
                'errors' => $errors,
                'ticket_id' => null,
            ];
        }

        $ticketId = $this->tickets->create([
            'user_id' => $userId,
            'category_id' => $categoryIdInt,
            'type' => $type,
            'impact' => $impact,
            'urgence' => $urgence,
            'priorite' => $this->calculatePriority($impact, $urgence),
            'statut' => 'NOUVEAU',
            'titre' => $titre,
            'description' => $description,
        ]);

        $this->events->logEvent([
            'ticket_id' => $ticketId,
            'type_evenement' => 'CREATION',
            'acteur' => [
                'user_id' => $userId,
                'role' => $role,
            ],
            'horodatage' => new UTCDateTime(),
            'donnees' => [
                'statut' => 'NOUVEAU',
            ],
        ]);

        return [
            'errors' => [],
            'ticket_id' => $ticketId,
        ];
    }

    /**
     * @return array<int, array<string, mixed>> Tickets créés par cet utilisateur (US4).
     */
    public function listMine(int $userId): array
    {
        return $this->tickets->findByUserId($userId);
    }

    /**
     * @return array<int, array<string, mixed>> Tous les tickets, vue globale TECHNICIAN/ADMIN (US5).
     */
    public function listAll(): array
    {
        return $this->tickets->findAll();
    }

    /**
     * @return array<string, mixed>|null Ticket trouvé, ou null si absent (US6).
     */
    public function findById(int $ticketId): ?array
    {
        return $this->tickets->findById($ticketId);
    }

    /**
     * Règle de propriété contextuelle (US6, 02_architecture.md §7) : le créateur voit
     * son propre ticket ; TECHNICIAN et ADMIN disposent d'un accès en lecture globale (US5).
     * Ne tranche que l'autorisation — l'existence du ticket est déjà vérifiée par l'appelant
     * via findById(), pour garder la distinction 404 (absent) / 403 (refusé) côté Controller.
     *
     * @param array<string, mixed> $ticket
     */
    public function canView(array $ticket, int $userId, string $role): bool
    {
        if (in_array($role, ['TECHNICIAN', 'ADMIN'], true)) {
            return true;
        }

        return (int) $ticket['user_id'] === $userId;
    }

    /**
     * @return array<int, array<string, mixed>> Catégories disponibles, pour affichage.
     */
    public function listCategories(): array
    {
        return $this->categories->findAll();
    }

    /**
     * Calcul serveur de la priorité (US8) — jamais saisi par l'utilisateur.
     * Appelée uniquement après validation stricte de $impact et $urgence.
     */
    private function calculatePriority(string $impact, string $urgence): string
    {
        return self::PRIORITY_MATRIX[$impact][$urgence];
    }

    /**
     * Prise en charge d'un ticket par un TECHNICIAN (US7) : assignation + passage
     * NOUVEAU → EN_COURS en une seule opération atomique. Retourne false si le ticket
     * n'était pas NOUVEAU (précontrôle) ou si l'UPDATE atomique n'a touché aucune ligne
     * (changement concurrent) — dans les deux cas, le Controller traduit en 409.
     * Le rôle TECHNICIAN est déjà imposé par Guard::requireRole() côté Controller.
     *
     * @param array<string, mixed> $ticket
     */
    public function assign(array $ticket, int $technicianId): bool
    {
        if ($ticket['statut'] !== 'NOUVEAU') {
            return false;
        }

        if (!$this->tickets->assignAndStart((int) $ticket['id'], $technicianId)) {
            return false;
        }

        $this->events->logEvent([
            'ticket_id' => (int) $ticket['id'],
            'type_evenement' => 'PRISE_EN_CHARGE',
            'acteur' => [
                'user_id' => $technicianId,
                'role' => 'TECHNICIAN',
            ],
            'horodatage' => new UTCDateTime(),
            'donnees' => [
                'ancien_statut' => 'NOUVEAU',
                'nouveau_statut' => 'EN_COURS',
                'technician_id' => $technicianId,
            ],
        ]);

        return true;
    }
}