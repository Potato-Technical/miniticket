<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

class UserController
{
    /**
     * Affiche le formulaire d'inscription.
     */
    public function showRegister(): void
    {
        View::render('user/register');
    }

    public function register(): void
    {
        echo 'UserController::register';
    }

    /**
     * Affiche le formulaire de connexion.
     */
    public function showLogin(): void
    {
        View::render('user/login');
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