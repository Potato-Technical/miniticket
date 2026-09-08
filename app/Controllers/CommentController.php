<?php

declare(strict_types=1);

namespace App\Controllers;

class CommentController
{
    public function store(string $id): void
    {
        echo 'CommentController::store ticket_id=' . $id;
    }
}