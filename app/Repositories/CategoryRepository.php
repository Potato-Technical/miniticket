<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Accès en lecture seule à la table categories (liste fixe, 01_cadrage.md §5).
 * Pas de create/update/delete : aucun CRUD applicatif sur les catégories.
 */
class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>> Catégories disponibles, triées par libellé.
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, libelle FROM categories ORDER BY libelle');

        return $stmt->fetchAll();
    }

    /**
     * Vérifie qu'une catégorie existe réellement avant insertion d'un ticket
     * (aucune confiance accordée à un category_id soumis côté client — 02_architecture.md §1).
     */
    public function existsById(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() !== false;
    }
}