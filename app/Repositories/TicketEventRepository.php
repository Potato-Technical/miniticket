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
    /**
     * Filtre structurel d'un événement MiniTicket réel — tout document produit par
     * logEvent() porte ces 4 champs. Appliqué en lecture par countByType() et
     * findRecent() pour ignorer tout document étranger à la collection (ex. document
     * de test technique) sans dépendre d'un nettoyage manuel de la base.
     */
    private const VALID_EVENT_FILTER = [
        'ticket_id' => ['$exists' => true],
        'type_evenement' => ['$exists' => true],
        'acteur' => ['$exists' => true],
        'horodatage' => ['$exists' => true],
    ];

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

    /**
     * Comptage agrégé par type d'événement, pour le tableau de bord ADMIN (indicateur
     * d'activité). Une seule requête d'agrégation Mongo — ne représente jamais l'état
     * actuel d'un ticket, seulement le volume d'événements journalisés.
     *
     * @return array<string, int> Clé = type_evenement (ex. CREATION), valeur = nombre d'occurrences.
     */
    public function countByType(): array
    {
        $pipeline = [
            ['$match' => self::VALID_EVENT_FILTER],
            ['$group' => ['_id' => '$type_evenement', 'count' => ['$sum' => 1]]],
        ];

        $cursor = $this->database->ticket_events->aggregate(
            $pipeline,
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
        );

        $counts = [];
        foreach ($cursor as $row) {
            $counts[(string) $row['_id']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>> Les $limit événements les plus récents,
     *   du plus récent au plus ancien, pour le fil d'activité du tableau de bord ADMIN.
     */
    public function findRecent(int $limit): array
    {
        $cursor = $this->database->ticket_events->find(
            self::VALID_EVENT_FILTER,
            [
                'sort' => ['horodatage' => -1],
                'limit' => $limit,
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
            ]
        );

        return $cursor->toArray();
    }
}