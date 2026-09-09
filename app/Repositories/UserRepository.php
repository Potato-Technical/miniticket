<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Accès aux données de la table users.
 */
class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<string, mixed>|null Utilisateur trouvé, ou null si absent.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    /**
     * @return array<string, mixed>|null Utilisateur trouvé, ou null si absent.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);

        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    /**
     * Insère un nouvel utilisateur. Le mot de passe est déjà haché par l'appelant (Service).
     *
     * @param array<string, mixed> $data Doit contenir pseudo, email, password_hash, role.
     * @return int Identifiant inséré.
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (pseudo, email, password_hash, role, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );

        $stmt->execute([
            $data['pseudo'],
            $data['email'],
            $data['password_hash'],
            $data['role'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Vérifie la disponibilité d'un email et d'un pseudo avant insertion (contraintes UNIQUE, 01_cadrage.md §6.1).
     */
    public function existsByEmailOrPseudo(string $email, string $pseudo): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ? OR pseudo = ? LIMIT 1');
        $stmt->execute([$email, $pseudo]);

        return $stmt->fetch() !== false;
    }
}