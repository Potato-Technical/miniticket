<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Guard;
use App\Core\MongoConnection;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\TicketEventRepository;
use App\Repositories\TicketRepository;
use App\Services\TicketService;

class AdminController
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
     * Tableau de bord de supervision ADMIN (lecture seule) : indicateurs MySQL sur
     * l'état actuel des tickets, indicateurs d'activité MongoDB, accès rapides.
     */
    public function dashboard(): void
    {
        (new Guard())->requireRole(['ADMIN']);

        View::render('admin/dashboard', $this->ticketService()->dashboardStats());
    }
}
