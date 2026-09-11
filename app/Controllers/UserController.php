<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Session;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\UserService;
use RuntimeException;

class UserController
{
    private function userService(): UserService
    {
        return new UserService(
            new UserRepository(
                (new Database())->getConnection()
            )
        );
    }

    public function showRegister(): void
    {
        View::render('user/register', [
            'errors' => [],
            'old' => [],
        ]);
    }

    public function register(): void
    {
        if (!(new Csrf())->verifyToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        $pseudo = is_string($_POST['pseudo'] ?? null)
            ? $_POST['pseudo']
            : '';

        $email = is_string($_POST['email'] ?? null)
            ? $_POST['email']
            : '';

        $password = is_string($_POST['password'] ?? null)
            ? $_POST['password']
            : '';

        $result = $this->userService()->register(
            $pseudo,
            $email,
            $password
        );

        if ($result['errors'] !== []) {
            View::render('user/register', [
                'errors' => $result['errors'],
                'old' => [
                    'pseudo' => $pseudo,
                    'email' => $email,
                ],
            ]);

            return;
        }

        header('Location: /login');
    }

    public function showLogin(): void
    {
        View::render('user/login', [
            'error' => null,
            'old' => [],
        ]);
    }

    public function login(): void
    {
        if (!(new Csrf())->verifyToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        $email = is_string($_POST['email'] ?? null)
            ? $_POST['email']
            : '';

        $password = is_string($_POST['password'] ?? null)
            ? $_POST['password']
            : '';

        $user = $this->userService()->attemptLogin(
            $email,
            $password
        );

        if ($user === null) {
            View::render('user/login', [
                'error' => 'Identifiants invalides.',
                'old' => [
                    'email' => $email,
                ],
            ]);

            return;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = (string) $user['role'];

        (new Session())->regenerate();

        $destination = match ($user['role']) {
            'USER' => '/tickets',
            'TECHNICIAN' => '/tickets/all',
            'ADMIN' => '/admin',
            default => throw new RuntimeException(
                'Rôle utilisateur invalide.'
            ),
        };

        header('Location: ' . $destination);
    }

    public function logout(): void
    {
        (new Guard())->requireAuth();

        if (!(new Csrf())->verifyToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        (new Session())->destroy();

        header('Location: /login');
    }
}