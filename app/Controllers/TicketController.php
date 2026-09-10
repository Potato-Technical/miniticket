<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Guard;
use App\Core\MongoConnection;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\TicketEventRepository;
use App\Repositories\TicketRepository;
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
        View::render('tickets/my');
    }

    /**
     * Affiche la vue globale des tickets (TECHNICIAN/ADMIN).
     */
    public function allTickets(): void
    {
        View::render('tickets/all');
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

    /**
     * Affiche le détail d'un ticket.
     *
     * @param string $id Identifiant du ticket, issu du paramètre dynamique de route.
     */
    public function show(string $id): void
    {
        View::render('tickets/show', ['id' => $id]);
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