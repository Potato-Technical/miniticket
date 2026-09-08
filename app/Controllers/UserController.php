<?php

declare(strict_types=1);

namespace App\Controllers;

class UserController
{
    public function showRegister(): void
    {
        echo 'UserController::showRegister';
    }

    public function register(): void
    {
        echo 'UserController::register';
    }

    public function showLogin(): void
    {
        echo 'UserController::showLogin';
    }

    public function login(): void
    {
        echo 'UserController::login';
    }

    public function logout(): void
    {
        echo 'UserController::logout';
    }
}