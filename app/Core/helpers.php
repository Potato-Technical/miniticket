<?php

declare(strict_types=1);

use App\Core\Csrf;

/**
 * Échappe une valeur avant son insertion dans du HTML.
 *
 * @param string|null $value Valeur à sécuriser.
 * @return string Valeur échappée.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Génère le champ caché contenant le jeton CSRF de la session courante.
 *
 * @return string Balise <input> prête à insérer dans un formulaire.
 */
function csrf_field(): string
{
    $token = (new Csrf())->generateToken();

    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Libellé humain d'un statut de ticket, pour affichage uniquement.
 * La valeur interne (colonne statut) n'est jamais modifiée.
 */
function ticket_status_label(string $statut): string
{
    return match ($statut) {
        'NOUVEAU' => 'Nouveau',
        'EN_COURS' => 'En cours',
        'RESOLU' => 'Résolu',
        'FERME' => 'Fermé',
        default => $statut,
    };
}

/**
 * Libellé humain d'un type d'événement d'historique, pour affichage uniquement.
 */
function ticket_event_label(string $type): string
{
    return match ($type) {
        'CREATION' => 'Création du ticket',
        'PRISE_EN_CHARGE' => 'Prise en charge',
        'CHANGEMENT_STATUT' => 'Changement de statut',
        default => $type,
    };
}

/**
 * Classe CSS du badge de statut (voir public/assets/css/app.css).
 */
function ticket_status_badge_class(string $statut): string
{
    $known = ['NOUVEAU', 'EN_COURS', 'RESOLU', 'FERME'];

    return 'badge-pill badge-status-' . (in_array($statut, $known, true) ? $statut : 'NOUVEAU');
}

/**
 * Classe CSS du badge de priorité (voir public/assets/css/app.css).
 */
function ticket_priority_badge_class(string $priorite): string
{
    $known = ['P1', 'P2', 'P3', 'P4'];

    return 'badge-pill badge-priority-' . (in_array($priorite, $known, true) ? $priorite : 'P4');
}

/**
 * Résumé lisible d'un événement d'historique MongoDB, pour le fil d'activité du
 * tableau de bord ADMIN. Pure mise en forme d'affichage, aucune donnée modifiée.
 *
 * @param array<string, mixed> $event
 */
function admin_event_summary(array $event): string
{
    $type = (string) ($event['type_evenement'] ?? '');

    if (
        $type === 'CHANGEMENT_STATUT'
        && isset($event['donnees']['ancien_statut'], $event['donnees']['nouveau_statut'])
    ) {
        return sprintf(
            '%s : %s → %s',
            ticket_event_label($type),
            ticket_status_label($event['donnees']['ancien_statut']),
            ticket_status_label($event['donnees']['nouveau_statut'])
        );
    }

    return ticket_event_label($type);
}