<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Accès aux données de la table comments.
 */
class CommentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array<string, mixed> $data ticket_id, user_id, contenu.
     * @return int Identifiant inséré.
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (ticket_id, user_id, contenu, created_at)
             VALUES (?, ?, ?, NOW())'
        );

        $stmt->execute([
            $data['ticket_id'],
            $data['user_id'],
            $data['contenu'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>> Commentaires du ticket, ordre chronologique.
     */
    public function findByTicketId(int $ticketId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM comments WHERE ticket_id = ? ORDER BY created_at ASC');
        $stmt->execute([$ticketId]);

        return $stmt->fetchAll();
    }
}