<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Accès aux données de la table tickets. Ne contient aucune règle métier
 * (calcul de priorité, validation de transition, contrôle de propriété) —
 * ces décisions appartiennent aux Services.
 */
class TicketRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Insère un nouveau ticket. Priorité et statut initial sont déjà déterminés
     * par l'appelant (US8 : calcul serveur, jamais ici).
     *
     * @param array<string, mixed> $data user_id, category_id, type, impact, urgence, priorite, statut, titre, description.
     * @return int Identifiant inséré.
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tickets
                (user_id, category_id, type, impact, urgence, priorite, statut, titre, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $data['user_id'],
            $data['category_id'],
            $data['type'],
            $data['impact'],
            $data['urgence'],
            $data['priorite'],
            $data['statut'],
            $data['titre'],
            $data['description'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null Ticket trouvé, ou null si absent.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tickets WHERE id = ?');
        $stmt->execute([$id]);

        $ticket = $stmt->fetch();

        return $ticket !== false ? $ticket : null;
    }

    /**
     * @return array<int, array<string, mixed>> Tickets créés par cet utilisateur (US4).
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>> Tous les tickets, vue globale (US5).
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM tickets ORDER BY created_at DESC');

        return $stmt->fetchAll();
    }

    /**
     * Met à jour uniquement le technicien assigné. Ne touche pas au statut :
     * combiner cette opération avec un changement de statut (US7, prise en charge)
     * reste une décision du Service appelant, pas de ce Repository.
     */
    public function assignTechnician(int $ticketId, int $technicianId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tickets SET technician_id = ?, updated_at = NOW() WHERE id = ?'
        );

        $stmt->execute([$technicianId, $ticketId]);
    }

    /**
     * Met à jour uniquement le statut. La validité de la transition (US7)
     * est vérifiée en amont par le Service, pas ici.
     */
    public function updateStatus(int $ticketId, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tickets SET statut = ?, updated_at = NOW() WHERE id = ?'
        );

        $stmt->execute([$status, $ticketId]);
    }

    /**
     * Prise en charge atomique (US7) : assigne le technicien et passe le ticket à
     * EN_COURS en une seule requête, protégée par WHERE statut = 'NOUVEAU' contre
     * une prise en charge concurrente. Retourne false si aucune ligne n'a été affectée
     * (ticket déjà assigné/statut différent entre-temps) — la décision (409, etc.)
     * reste au Service/Controller appelant.
     */
    public function assignAndStart(int $ticketId, int $technicianId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tickets SET technician_id = ?, statut = 'EN_COURS', updated_at = NOW()
             WHERE id = ? AND statut = 'NOUVEAU'"
        );
        $stmt->execute([$technicianId, $ticketId]);

        return $stmt->rowCount() === 1;
    }
}