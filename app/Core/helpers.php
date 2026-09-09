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