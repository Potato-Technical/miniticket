<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Point de capture centralisé pour toute erreur ou exception non gérée.
 * PHP ne doit jamais afficher lui-même une erreur (display_errors désactivé) :
 * seul ErrorHandler décide de ce qui est montré, selon APP_ENV.
 */
class ErrorHandler
{
    /**
     * Erreurs fatales natives qui échappent à set_error_handler et ne sont
     * visibles qu'au shutdown, via error_get_last().
     */
    private const FATAL_ERROR_TYPES = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    public function register(): void
    {
        ini_set('display_errors', '0');

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Convertit une erreur PHP classique en exception. Respecte error_reporting() :
     * une erreur volontairement supprimée (ex. opérateur @) est ignorée.
     */
    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Journalise systématiquement via error_log(), puis affiche selon APP_ENV :
     * détail complet en dev, page générique en production. Fail-safe sur
     * production si APP_ENV n'est pas encore disponible.
     */
    public function handleException(Throwable $e): void
    {
        error_log(sprintf(
            '%s: %s in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        http_response_code(500);

        if (($_ENV['APP_ENV'] ?? 'production') === 'dev') {
            echo '<pre>';
            echo get_class($e) . ': ' . e($e->getMessage()) . "\n";
            echo 'Fichier : ' . e($e->getFile()) . ':' . $e->getLine() . "\n\n";
            echo e($e->getTraceAsString());
            echo '</pre>';
            return;
        }

        View::render('errors/500');
    }

    /**
     * Point d'entrée réel du shutdown : lit error_get_last() et délègue
     * la décision de filtrage à handleFatalError() (séparée pour rester testable
     * en CLI sans provoquer une vraie erreur fatale).
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null) {
            $this->handleFatalError($error);
        }
    }

    /**
     * Ne retraite que les types réellement fatals (liste ci-dessus) — jamais
     * un warning/notice déjà passé par handleError(), pour éviter un double traitement.
     *
     * @param array{type: int, message: string, file: string, line: int} $error
     */
    public function handleFatalError(array $error): void
    {
        if (!in_array($error['type'], self::FATAL_ERROR_TYPES, true)) {
            return;
        }

        $this->handleException(new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        ));
    }
}