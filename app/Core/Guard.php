<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Contrôle générique d'authentification et d'autorisation par rôle,
 * à partir de l'état de session ($_SESSION['user_id'], $_SESSION['role']).
 * Ne connaît aucune règle de propriété contextuelle (voir les Services, 02_architecture.md §7).
 */
class Guard
{
    /**
     * Une session est considérée authentifiée uniquement si les deux clés
     * du contrat sont présentes — une session partielle (user_id sans role,
     * ou l'inverse) est refusée plutôt que tolérée.
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id'], $_SESSION['role']);
    }

    public function currentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public function currentRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    /**
     * @param array<int, string> $allowedRoles
     */
    public function hasRole(array $allowedRoles): bool
    {
        $role = $this->currentRole();

        return $role !== null && in_array($role, $allowedRoles, true);
    }

    /**
     * Redirige vers /login si aucune session active. Termine le script.
     */
    public function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Exige une authentification puis un rôle autorisé. Renvoie 403 si le rôle
     * ne correspond pas — distinct d'une redirection : l'utilisateur est connu,
     * simplement non autorisé pour cette action précise.
     *
     * @param array<int, string> $allowedRoles
     */
    public function requireRole(array $allowedRoles): void
    {
        $this->requireAuth();

        if (!$this->hasRole($allowedRoles)) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }
}