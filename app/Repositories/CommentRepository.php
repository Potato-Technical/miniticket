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
     * @return array<int, array<string, mixed>> Commentaires du ticket, avec le pseudo de l'auteur, ordre chronologique.
     */
    public function findByTicketId(int $ticketId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT comments.id, comments.ticket_id, comments.user_id, comments.contenu, comments.created_at, users.pseudo
             FROM comments
             JOIN users ON users.id = comments.user_id
             WHERE comments.ticket_id = ?
             ORDER BY comments.created_at ASC'
        );
        $stmt->execute([$ticketId]);

        return $stmt->fetchAll();
    }
}