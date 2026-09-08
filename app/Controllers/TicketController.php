<?php

declare(strict_types=1);

namespace App\Controllers;

class TicketController
{
    public function myTickets(): void
    {
        echo 'TicketController::myTickets';
    }

    public function allTickets(): void
    {
        echo 'TicketController::allTickets';
    }

    public function showCreate(): void
    {
        echo 'TicketController::showCreate';
    }

    public function create(): void
    {
        echo 'TicketController::create';
    }

    public function show(string $id): void
    {
        echo 'TicketController::show id=' . $id;
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