<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Guard;
use App\Core\MongoConnection;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\CommentRepository;
use App\Repositories\TicketEventRepository;
use App\Repositories\TicketRepository;
use App\Services\CommentService;
use App\Services\TicketService;

class TicketController
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

    /**
     * Affiche les tickets créés par l'utilisateur connecté.
     */
    public function myTickets(): void
    {
        $guard = new Guard();
        $guard->requireAuth();

        $service = $this->ticketService();

        $tickets = $service->listMine((int) $guard->currentUserId());

        $categoryLabels = [];
        foreach ($service->listCategories() as $category) {
            $categoryLabels[(int) $category['id']] = $category['libelle'];
        }

        View::render('tickets/my', [
            'tickets' => $tickets,
            'categoryLabels' => $categoryLabels,
        ]);
    }

    /**
     * Affiche la vue globale des tickets (US5, TECHNICIAN/ADMIN).
     */
    public function allTickets(): void
    {
        (new Guard())->requireRole(['TECHNICIAN', 'ADMIN']);

        $service = $this->ticketService();

        $tickets = $service->listAll();

        $categoryLabels = [];
        foreach ($service->listCategories() as $category) {
            $categoryLabels[(int) $category['id']] = $category['libelle'];
        }

        View::render('tickets/all', [
            'tickets' => $tickets,
            'categoryLabels' => $categoryLabels,
        ]);
    }

    /**
     * Affiche le formulaire de création de ticket.
     */
    public function showCreate(): void
    {
        (new Guard())->requireAuth();

        $categories = (new CategoryRepository(
            (new Database())->getConnection()
        ))->findAll();

        View::render('tickets/create', [
            'categories' => $categories,
            'errors' => [],
            'old' => [],
        ]);
    }

    public function create(): void
    {
        $guard = new Guard();
        $guard->requireAuth();

        if (!(new Csrf())->verifyToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        $titre = is_string($_POST['titre'] ?? null) ? $_POST['titre'] : '';
        $description = is_string($_POST['description'] ?? null) ? $_POST['description'] : '';
        $type = is_string($_POST['type'] ?? null) ? $_POST['type'] : '';
        $categoryId = is_string($_POST['category_id'] ?? null) ? $_POST['category_id'] : '';
        $impact = is_string($_POST['impact'] ?? null) ? $_POST['impact'] : '';
        $urgence = is_string($_POST['urgence'] ?? null) ? $_POST['urgence'] : '';

        $result = $this->ticketService()->create(
            (int) $guard->currentUserId(),
            (string) $guard->currentRole(),
            $titre,
            $description,
            $type,
            $categoryId,
            $impact,
            $urgence
        );

        if ($result['errors'] !== []) {
            $categories = (new CategoryRepository(
                (new Database())->getConnection()
            ))->findAll();

            View::render('tickets/create', [
                'categories' => $categories,
                'errors' => $result['errors'],
                'old' => [
                    'titre' => $titre,
                    'description' => $description,
                    'type' => $type,
                    'category_id' => $categoryId,
                    'impact' => $impact,
                    'urgence' => $urgence,
                ],
            ]);

            return;
        }

        header('Location: /tickets/' . $result['ticket_id']);
    }

    private function commentService(): CommentService
    {
        return new CommentService(new CommentRepository((new Database())->getConnection()));
    }

    /**
     * Affiche le détail d'un ticket, sous contrôle de propriété (US6).
     *
     * @param string $id Identifiant du ticket, issu du paramètre dynamique de route.
     */
    public function show(string $id): void
    {
        $guard = new Guard();
        $guard->requireAuth();

        if (!ctype_digit($id) || (int) $id === 0) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $service = $this->ticketService();
        $ticket = $service->findById((int) $id);

        if ($ticket === null) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (!$service->canView($ticket, (int) $guard->currentUserId(), (string) $guard->currentRole())) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        $this->renderShow($ticket);
    }

    /**
     * Rend le détail d'un ticket (catégorie + commentaires), avec une éventuelle erreur de
     * formulaire de commentaire. Réutilisé par CommentController::store() pour éviter de
     * dupliquer la récupération des données d'affichage (étape 22).
     *
     * @param array<string, mixed> $ticket
     * @param array<string, string> $commentErrors
     */
    public function renderShow(array $ticket, array $commentErrors = [], string $oldComment = ''): void
    {
        $ticketService = $this->ticketService();

        $categoryLabels = [];
        foreach ($ticketService->listCategories() as $category) {
            $categoryLabels[(int) $category['id']] = $category['libelle'];
        }

        View::render('tickets/show', [
            'ticket' => $ticket,
            'categoryLabel' => $categoryLabels[(int) $ticket['category_id']] ?? 'Catégorie inconnue',
            'comments' => $this->commentService()->listByTicket((int) $ticket['id']),
            'commentErrors' => $commentErrors,
            'oldComment' => $oldComment,
        ]);
    }

    public function assign(string $id): void
    {
        echo 'TicketController::assign id=' . $id;
    }

    public function updateStatus(string $id): void
    {
        echo 'TicketController::updateStatus id=' . $id;
    }
}