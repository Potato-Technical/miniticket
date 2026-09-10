<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Guard;
use App\Core\MongoConnection;
use App\Repositories\CategoryRepository;
use App\Repositories\CommentRepository;
use App\Repositories\TicketEventRepository;
use App\Repositories\TicketRepository;
use App\Services\CommentService;
use App\Services\TicketService;

class CommentController
{
    private function ticketService(): TicketService
    {
        $pdo = (new Database())->getConnection();

        return new TicketService(
            new TicketRepository($pdo),
            new CategoryRepository($pdo),
            new TicketEventRepository((new MongoConnection())->getDatabase())
        );
    }

    private function commentService(): CommentService
    {
        return new CommentService(new CommentRepository((new Database())->getConnection()));
    }

    /**
     * Ajoute un commentaire à un ticket (US6), sous la même règle de propriété que sa consultation.
     *
     * @param string $id Identifiant du ticket, issu du paramètre dynamique de route.
     */
    public function store(string $id): void
    {
        $guard = new Guard();
        $guard->requireAuth();

        if (!ctype_digit($id) || (int) $id === 0) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (!(new Csrf())->verifyToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        $ticketService = $this->ticketService();
        $ticket = $ticketService->findById((int) $id);

        if ($ticket === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (!$ticketService->canView($ticket, (int) $guard->currentUserId(), (string) $guard->currentRole())) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        $contenu = is_string($_POST['contenu'] ?? null) ? $_POST['contenu'] : '';

        $result = $this->commentService()->add((int) $id, (int) $guard->currentUserId(), $contenu);

        if ($result['errors'] !== []) {
            (new TicketController())->renderShow($ticket, $result['errors'], $contenu);
            return;
        }

        header('Location: /tickets/' . $id);
    }
}