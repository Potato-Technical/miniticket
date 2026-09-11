<?php $isStaff = in_array($currentRole ?? null, ['TECHNICIAN', 'ADMIN'], true); ?>

<div class="d-flex flex-wrap justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h3 mb-0">Tickets</h1>
    <a href="/tickets/create" class="btn btn-primary">+ Nouveau ticket</a>
</div>

<?php if ($isStaff): ?>
    <ul class="nav nav-pills ticket-list-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="/tickets/all">Tous les tickets</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="/tickets">Mes tickets</a>
        </li>
    </ul>
<?php else: ?>
    <p class="text-muted mb-4">Tickets que vous avez créés.</p>
<?php endif; ?>

<?php if ($tickets === []): ?>
    <p class="text-muted">Vous n'avez créé aucun ticket pour l'instant.</p>
<?php else: ?>
    <div class="table-responsive d-none d-md-block section-card">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Type</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Priorité</th>
                    <th>Créé le</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td>
                            <a href="/tickets/<?= e((string) $ticket['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= e($ticket['titre']) ?>
                            </a>
                        </td>
                        <td><?= e($ticket['type']) ?></td>
                        <td>
                            <?= e($categoryLabels[(int) $ticket['category_id']] ?? 'Catégorie inconnue') ?>
                        </td>
                        <td>
                            <span class="<?= e(ticket_status_badge_class($ticket['statut'])) ?>">
                                <?= e(ticket_status_label($ticket['statut'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?= e(ticket_priority_badge_class($ticket['priorite'])) ?>">
                                <?= e($ticket['priorite']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= e($ticket['created_at']) ?></td>
                        <td class="text-end">
                            <a href="/tickets/<?= e((string) $ticket['id']) ?>" class="btn btn-sm btn-outline-secondary">Voir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-md-none d-flex flex-column gap-2">
        <?php foreach ($tickets as $ticket): ?>
            <a href="/tickets/<?= e((string) $ticket['id']) ?>" class="ticket-card p-3 text-decoration-none text-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small">
                        #<?= e((string) $ticket['id']) ?> ·
                        <?= e($categoryLabels[(int) $ticket['category_id']] ?? '') ?>
                    </span>
                    <span class="<?= e(ticket_priority_badge_class($ticket['priorite'])) ?>">
                        <?= e($ticket['priorite']) ?>
                    </span>
                </div>
                <div class="fw-semibold mb-2"><?= e($ticket['titre']) ?></div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="<?= e(ticket_status_badge_class($ticket['statut'])) ?>">
                        <?= e(ticket_status_label($ticket['statut'])) ?>
                    </span>
                    <span class="text-muted small"><?= e($ticket['created_at']) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
