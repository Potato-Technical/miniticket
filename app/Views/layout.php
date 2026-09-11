<?php
$navGuard = new \App\Core\Guard();
$navAuthenticated = $navGuard->isAuthenticated();
$navRole = $navGuard->currentRole();
$navIsStaff = in_array($navRole, ['TECHNICIAN', 'ADMIN'], true);

$navLogoHref = '/';
if ($navAuthenticated) {
    $navLogoHref = match ($navRole) {
        'USER' => '/tickets',
        'ADMIN' => '/admin',
        default => '/tickets/all',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniTicket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= e($navLogoHref) ?>">MiniTicket</a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav"
                aria-controls="mainNav"
                aria-expanded="false"
                aria-label="Basculer la navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <?php if (!$navAuthenticated): ?>
                        <li class="nav-item my-1 my-lg-0">
                            <a class="nav-link" href="/">Accueil</a>
                        </li>
                        <li class="nav-item my-1 my-lg-0">
                            <a class="nav-link" href="/login">Connexion</a>
                        </li>
                        <li class="nav-item my-1 my-lg-0 ms-lg-2">
                            <a class="btn btn-primary btn-sm" href="/register">Créer un compte</a>
                        </li>
                    <?php else: ?>
                        <?php if ($navRole === 'ADMIN'): ?>
                            <li class="nav-item my-1 my-lg-0">
                                <a class="nav-link" href="/admin">Administration</a>
                            </li>
                        <?php endif; ?>
                        <?php if ($navIsStaff): ?>
                            <li class="nav-item my-1 my-lg-0">
                                <a class="nav-link" href="/tickets/all">Tous les tickets</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item my-1 my-lg-0">
                            <a class="nav-link" href="/tickets">Mes tickets</a>
                        </li>
                        <li class="nav-item my-1 my-lg-0 ms-lg-2">
                            <a class="btn btn-primary btn-sm" href="/tickets/create">Nouveau ticket</a>
                        </li>
                        <li class="nav-item my-1 my-lg-0 ms-lg-3">
                            <form method="post" action="/logout" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-link nav-link p-0 text-decoration-none">
                                    Déconnexion
                                </button>
                            </form>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-4">
        <?= $content ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
