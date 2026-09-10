<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CommentRepository;

/**
 * Logique métier des commentaires (US6, partie commentaires).
 * La règle de propriété (qui peut commenter un ticket donné) n'est pas dupliquée ici :
 * elle est déjà portée par TicketService::canView() et vérifiée en amont par le Controller.
 */
class CommentService
{
    public function __construct(private readonly CommentRepository $comments)
    {
    }

    /**
     * @return array{errors: array<string,string>, comment_id: int|null}
     */
    public function add(int $ticketId, int $userId, string $contenu): array
    {
        $contenu = trim($contenu);

        if ($contenu === '') {
            return [
                'errors' => ['contenu' => 'Le commentaire ne peut pas être vide.'],
                'comment_id' => null,
            ];
        }

        $commentId = $this->comments->create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'contenu' => $contenu,
        ]);

        return [
            'errors' => [],
            'comment_id' => $commentId,
        ];
    }

    /**
     * @return array<int, array<string, mixed>> Commentaires du ticket, avec le pseudo de l'auteur.
     */
    public function listByTicket(int $ticketId): array
    {
        return $this->comments->findByTicketId($ticketId);
    }
}