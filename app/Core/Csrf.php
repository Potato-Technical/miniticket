<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Génération et vérification d'un jeton CSRF unique par session.
 * Mécanisme technique pur, sans logique métier ni décision d'autorisation.
 */
class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    /**
     * Retourne le jeton existant en session, ou en génère un nouveau
     * si absent ou corrompu (valeur non-string). Un seul jeton par
     * session (pas un par formulaire).
     */
    public function generateToken(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Compare le jeton soumis à celui de la session, en temps constant.
     * Rejette proprement toute valeur non-string, côté soumission comme
     * côté session, plutôt que de provoquer une erreur de type.
     */
    public function verifyToken(mixed $submittedToken): bool
    {
        if (
            !is_string($submittedToken)
            || !isset($_SESSION[self::SESSION_KEY])
            || !is_string($_SESSION[self::SESSION_KEY])
        ) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $submittedToken);
    }
}