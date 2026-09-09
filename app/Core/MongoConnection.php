<?php

declare(strict_types=1);

namespace App\Core;

use MongoDB\Client;
use MongoDB\Database;

/**
 * Fournit un accès réutilisable à la base MongoDB.
 */
class MongoConnection
{
    private ?Database $database = null;

    /**
     * @return Database Base MongoDB créée au premier appel.
     */
    public function getDatabase(): Database
    {
        if ($this->database === null) {
            $client = new Client($_ENV['MONGODB_URI']);

            $this->database = $client->selectDatabase('miniticket');
        }

        return $this->database;
    }
}