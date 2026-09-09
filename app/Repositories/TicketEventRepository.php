<?php

declare(strict_types=1);

namespace App\Repositories;

use MongoDB\Database;
use MongoDB\Driver\Exception\Exception as MongoDriverException;

/**
 * Seul point d'accès applicatif à la collection ticket_events (append-only).
 */
class TicketEventRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * Enregistre un événement métier. Un échec Mongo est journalisé mais
     * jamais relancé : la persistance SQL déjà réalisée ne doit jamais
     * être compromise par un incident MongoDB (02_architecture.md §6.2).
     *
     * @param array<string, mixed> $event ticket_id, type_evenement, acteur,
     *   horodatage (MongoDB\BSON\UTCDateTime), donnees.
     */
    public function logEvent(array $event): void
    {
        try {
            $this->database->ticket_events->insertOne($event);
        } catch (MongoDriverException $e) {
            error_log('Échec écriture ticket_events: ' . $e->getMessage());
        }
    }

    /**
     * @return array<int, array<string, mixed>> Événements du ticket, ordre chronologique (US9).
     */
    public function findByTicketId(int $ticketId): array
    {
        $cursor = $this->database->ticket_events->find(
            ['ticket_id' => $ticketId],
            [
                'sort' => ['horodatage' => 1],
                // Force des tableaux PHP natifs plutôt que des BSONDocument/BSONArray.
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
            ]
        );

        return $cursor->toArray();
    }
}