<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\UserController;
use App\Controllers\TicketController;
use App\Controllers\CommentController;
use App\Core\View;

/** @var App\Core\Router $router */

$router->get('/', fn() => View::render('home'));

$router->get('/admin', [AdminController::class, 'dashboard']);

$router->get('/register', [UserController::class, 'showRegister']);
$router->post('/register', [UserController::class, 'register']);

$router->get('/login', [UserController::class, 'showLogin']);
$router->post('/login', [UserController::class, 'login']);
$router->post('/logout', [UserController::class, 'logout']);

$router->get('/tickets', [TicketController::class, 'myTickets']);
$router->get('/tickets/all', [TicketController::class, 'allTickets']);
$router->get('/tickets/create', [TicketController::class, 'showCreate']);
$router->post('/tickets/create', [TicketController::class, 'create']);
$router->get('/tickets/{id}', [TicketController::class, 'show']);

$router->post('/tickets/{id}/assign', [TicketController::class, 'assign']);
$router->post('/tickets/{id}/status', [TicketController::class, 'updateStatus']);

$router->post('/tickets/{id}/comments', [CommentController::class, 'store']);