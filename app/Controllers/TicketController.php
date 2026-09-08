<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

class TicketController
{
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
        View::render('tickets/create');
    }

    public function create(): void
    {
        echo 'TicketController::create';
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