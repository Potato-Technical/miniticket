<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Rend une vue spécifique puis l'injecte dans le layout commun.
 */
class View
{
    /**
     * @param string $view Chemin de la vue relatif à app/Views, sans extension.
     * @param array<string, mixed> $data Données transmises à la vue.
     * @return void
     */
    public static function render(string $view, array $data = []): void
    {
        // EXTR_SKIP : une clé de $data ne doit jamais écraser une variable locale existante ($view, $data).
        extract($data, EXTR_SKIP);

        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        ob_start();

        try {
            require $viewFile;
            $content = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        require __DIR__ . '/../Views/layout.php';
    }
}