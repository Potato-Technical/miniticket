<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Configuration, démarrage, régénération et destruction techniques de la session.
 * Ne contient aucune logique métier ni décision d'autorisation (voir Guard).
 */
class Session
{
    /**
     * Configure les paramètres du cookie de session puis démarre la session.
     * Sans effet si une session est déjà active.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            // secure=false en dev (HTTP local), true en production (03_securite.md).
            'secure' => ($_ENV['APP_ENV'] ?? 'dev') === 'production',
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Régénère l'identifiant de session, en supprimant l'ancienne session côté serveur.
     * Sans effet si aucune session n'est active.
     * Primitive technique uniquement : à invoquer après un login réussi (étape 18).
     */
    public function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_regenerate_id(true);
    }

    /**
     * Vide les données de session, invalide le cookie côté client et détruit la session.
     * Sans effet si aucune session n'est active.
     * Primitive technique uniquement : à invoquer lors d'un logout réel (étape 18).
     */
    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'],
                ]
            );
        }

        session_destroy();
    }
}