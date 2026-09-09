<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Fournit une connexion PDO MySQL réutilisable par les Repositories.
 */
class Database
{
    private ?PDO $connection = null;

    /**
     * @return PDO Connexion PDO active, créée au premier appel.
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_NAME']
            );

            $this->connection = new PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Force les requêtes préparées natives côté serveur MySQL.
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }

        return $this->connection;
    }
}