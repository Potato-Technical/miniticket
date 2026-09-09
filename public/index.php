<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

(new App\Core\ErrorHandler())->register();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

(new App\Core\Session())->start();
$router = new App\Core\Router();

require __DIR__ . '/../routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);