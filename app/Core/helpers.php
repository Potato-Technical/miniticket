<?php

declare(strict_types=1);

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