<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

/**
 * Logique métier US1 (inscription) et US2 (connexion).
 */
class UserService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /**
     * @return array{errors: array<string,string>, user_id: int|null}
     */
    public function register(string $pseudo, string $email, string $password): array
    {
        $pseudo = trim($pseudo);
        $email = trim($email);

        $errors = [];

        if ($pseudo === '') {
            $errors['pseudo'] = 'Le pseudo est requis.';
        } elseif (strlen($pseudo) > 50) {
            $errors['pseudo'] = 'Le pseudo est trop long.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        } elseif (strlen($email) > 255) {
            $errors['email'] = 'Email trop long.';
        }

        if ($password === '') {
            $errors['password'] = 'Le mot de passe est requis.';
        }

        if (
            $errors === []
            && $this->users->existsByEmailOrPseudo($email, $pseudo)
        ) {
            $errors['account'] = 'Cet email ou ce pseudo est déjà utilisé.';
        }

        if ($errors !== []) {
            return [
                'errors' => $errors,
                'user_id' => null,
            ];
        }

        $userId = $this->users->create([
            'pseudo' => $pseudo,
            'email' => $email,
            'password_hash' => password_hash(
                $password,
                PASSWORD_DEFAULT
            ),
            'role' => 'USER',
        ]);

        return [
            'errors' => [],
            'user_id' => $userId,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail(trim($email));

        if (
            $user === null
            || !password_verify($password, $user['password_hash'])
        ) {
            return null;
        }

        return $user;
    }
}