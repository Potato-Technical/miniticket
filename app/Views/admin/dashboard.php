<h1 class="h3 mb-4">Administration</h1>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="section-card p-3 text-center h-100">
            <div class="display-6 fw-bold mb-1"><?= e((string) $tickets['total']) ?></div>
            <div class="text-muted small text-uppercase">Total tickets</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="section-card p-3 text-center h-100">
            <div class="display-6 fw-bold mb-1"><?= e((string) $tickets['nouveau']) ?></div>
            <div class="text-muted small text-uppercase">Nouveau — à traiter</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="section-card p-3 text-center h-100">
            <div class="display-6 fw-bold mb-1"><?= e((string) $tickets['en_cours']) ?></div>
            <div class="text-muted small text-uppercase">En cours</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="section-card p-3 text-center h-100">
            <div class="display-6 fw-bold mb-1"><?= e((string) $tickets['termines']) ?></div>
            <div class="text-muted small text-uppercase">Résolu / Fermé</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="section-card p-4 h-100">
            <h2 class="h6 text-uppercase text-muted mb-3">Activité MongoDB</h2>

            <dl class="row mb-2">
                <dt class="col-8 fw-normal">Créations</dt>
                <dd class="col-4 text-end mb-1"><?= e((string) ($events['CREATION'] ?? 0)) ?></dd>

                <dt class="col-8 fw-normal">Prises en charge</dt>
                <dd class="col-4 text-end mb-1"><?= e((string) ($events['PRISE_EN_CHARGE'] ?? 0)) ?></dd>

                <dt class="col-8 fw-normal">Changements de statut</dt>
                <dd class="col-4 text-end mb-0"><?= e((string) ($events['CHANGEMENT_STATUT'] ?? 0)) ?></dd>
            </dl>

            <p class="text-muted small mb-0">
                Indicateurs d'activité issus de MongoDB (écritures non bloquantes) —
                l'état actuel des tickets ci-dessus reste calculé depuis MySQL.
            </p>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="section-card p-4 h-100">
            <h2 class="h6 text-uppercase text-muted mb-3">Activité récente</h2>

            <?php if ($recent === []): ?>
                <p class="text-muted mb-0">Aucun événement enregistré.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($recent as $event): ?>
                        <li class="pb-2 mb-2 border-bottom small">
                            <span class="text-muted"><?= e($event['horodatage_formate']) ?></span>
                            — Ticket #<?= e((string) $event['ticket_id']) ?>
                            — <?= e(admin_event_summary($event)) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<h2 class="h6 text-uppercase text-muted mb-3">Accès rapides</h2>

<div class="d-flex flex-wrap gap-2">
    <a href="/tickets/all" class="btn btn-outline-dark">Tous les tickets</a>
    <a href="/tickets" class="btn btn-outline-dark">Mes tickets</a>
    <a href="/tickets/create" class="btn btn-primary">Nouveau ticket</a>
</div>
