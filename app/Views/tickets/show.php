<?php
$backHref = in_array($currentRole, ['TECHNICIAN', 'ADMIN'], true) ? '/tickets/all' : '/tickets';
$backLabel = in_array($currentRole, ['TECHNICIAN', 'ADMIN'], true) ? 'Tous les tickets' : 'Mes tickets';
?>

<a href="<?= e($backHref) ?>" class="d-inline-block mb-3 text-decoration-none">
    &larr; Retour à <?= e($backLabel) ?>
</a>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
    <div>
        <div class="text-muted small mb-1">
            Ticket #<?= e((string) $ticket['id']) ?> ·
            <?= e($ticket['type']) ?> ·
            créé le <?= e($ticket['created_at']) ?>
        </div>
        <h1 class="h3 mb-0"><?= e($ticket['titre']) ?></h1>
    </div>

    <div class="d-flex gap-2 align-items-start flex-shrink-0">
        <span class="<?= e(ticket_status_badge_class($ticket['statut'])) ?>">
            <?= e(ticket_status_label($ticket['statut'])) ?>
        </span>
        <span class="<?= e(ticket_priority_badge_class($ticket['priorite'])) ?>">
            <?= e($ticket['priorite']) ?>
        </span>
    </div>
</div>

<hr class="mt-3 mb-4">

<div class="row g-4">
    <div class="col-lg-8 order-2 order-lg-1">
        <div class="section-card p-3 p-md-4 mb-4">
            <h2 class="h6 text-muted text-uppercase mb-3">Description</h2>
            <p class="mb-0"><?= nl2br(e($ticket['description'])) ?></p>
        </div>

        <h2 class="h5 mb-3">Commentaires</h2>

        <?php if ($comments === []): ?>
            <p class="text-muted">Aucun commentaire pour l'instant.</p>
        <?php else: ?>
            <div class="d-flex flex-column gap-3 mb-4">
                <?php foreach ($comments as $comment): ?>
                    <div class="section-card p-3">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <strong><?= e($comment['pseudo']) ?></strong>
                            <small class="text-muted"><?= e($comment['created_at']) ?></small>
                        </div>
                        <p class="mb-0"><?= nl2br(e($comment['contenu'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section-card p-3 p-md-4 mb-4">
            <h3 class="h6 text-uppercase text-muted mb-3">Ajouter un commentaire</h3>

            <form method="post" action="/tickets/<?= e((string) $ticket['id']) ?>/comments">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <textarea
                        class="form-control"
                        name="contenu"
                        rows="3"
                        placeholder="Écrire un commentaire..."
                    ><?= e($oldComment) ?></textarea>

                    <?php if (!empty($commentErrors['contenu'])): ?>
                        <div class="text-danger small mt-1">
                            <?= e($commentErrors['contenu']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Publier</button>
            </form>
        </div>

        <h2 class="h5 mb-3">Historique</h2>

        <?php if ($history === []): ?>
            <p class="text-muted">Aucun événement enregistré.</p>
        <?php else: ?>
            <ul class="list-unstyled section-card p-3 p-md-4 mb-0">
                <?php foreach ($history as $event): ?>
                    <li class="pb-2 mb-2 border-bottom">
                        <span class="text-muted small"><?= e($event['horodatage_formate']) ?></span>
                        — <strong><?= e(ticket_event_label($event['type_evenement'])) ?></strong>
                        <span class="text-muted">(<?= e($event['acteur']['role']) ?>)</span>
                        <?php if (isset($event['donnees']['ancien_statut'], $event['donnees']['nouveau_statut'])): ?>
                            : <?= e(ticket_status_label($event['donnees']['ancien_statut'])) ?>
                            &rarr; <?= e(ticket_status_label($event['donnees']['nouveau_statut'])) ?>
                        <?php elseif ($event['type_evenement'] === 'CREATION' && isset($event['donnees']['statut'])): ?>
                            : statut initial <?= e(ticket_status_label($event['donnees']['statut'])) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="col-lg-4 order-1 order-lg-2">
        <div class="section-card p-3 p-md-4 mb-4">
            <h2 class="h6 text-uppercase text-muted mb-3">Informations</h2>

            <dl class="row mb-0 small">
                <dt class="col-6 text-muted fw-normal">Type</dt>
                <dd class="col-6 text-end"><?= e($ticket['type']) ?></dd>

                <dt class="col-6 text-muted fw-normal">Catégorie</dt>
                <dd class="col-6 text-end"><?= e($categoryLabel) ?></dd>

                <dt class="col-6 text-muted fw-normal">Impact</dt>
                <dd class="col-6 text-end"><?= e($ticket['impact']) ?></dd>

                <dt class="col-6 text-muted fw-normal">Urgence</dt>
                <dd class="col-6 text-end"><?= e($ticket['urgence']) ?></dd>

                <dt class="col-6 text-muted fw-normal">Priorité</dt>
                <dd class="col-6 text-end"><?= e($ticket['priorite']) ?></dd>

                <dt class="col-6 text-muted fw-normal">Créé le</dt>
                <dd class="col-6 text-end"><?= e($ticket['created_at']) ?></dd>

                <?php if (!empty($ticket['updated_at'])): ?>
                    <dt class="col-6 text-muted fw-normal">Mis à jour le</dt>
                    <dd class="col-6 text-end mb-0"><?= e($ticket['updated_at']) ?></dd>
                <?php endif; ?>
            </dl>
        </div>

        <?php if ($currentRole === 'TECHNICIAN' && in_array($ticket['statut'], ['EN_COURS', 'RESOLU'], true)): ?>
            <div class="section-card p-3 p-md-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3">Actions technicien</h2>

                <?php if ($ticket['statut'] === 'EN_COURS'): ?>
                    <form method="post" action="/tickets/<?= e((string) $ticket['id']) ?>/status">
                        <?= csrf_field() ?>
                        <input type="hidden" name="statut" value="RESOLU">
                        <button type="submit" class="btn btn-outline-success w-100">Marquer résolu</button>
                    </form>
                <?php elseif ($ticket['statut'] === 'RESOLU'): ?>
                    <form method="post" action="/tickets/<?= e((string) $ticket['id']) ?>/status">
                        <?= csrf_field() ?>
                        <input type="hidden" name="statut" value="FERME">
                        <button type="submit" class="btn btn-outline-secondary w-100">Fermer le ticket</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
