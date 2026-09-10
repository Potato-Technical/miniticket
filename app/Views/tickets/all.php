<h1>Tous les tickets</h1>

<?php if ($tickets === []): ?>
    <p>Aucun ticket pour l'instant.</p>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Type</th>
                <th>Catégorie</th>
                <th>Impact</th>
                <th>Urgence</th>
                <th>Priorité</th>
                <th>Statut</th>
                <th>Créé le</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td>
                        <a href="/tickets/<?= e((string) $ticket['id']) ?>">
                            <?= e($ticket['titre']) ?>
                        </a>
                    </td>
                    <td><?= e($ticket['type']) ?></td>
                    <td><?= e($categoryLabels[(int) $ticket['category_id']] ?? (string) $ticket['category_id']) ?></td>
                    <td><?= e($ticket['impact']) ?></td>
                    <td><?= e($ticket['urgence']) ?></td>
                    <td><?= e($ticket['priorite']) ?></td>
                    <td><?= e($ticket['statut']) ?></td>
                    <td><?= e($ticket['created_at']) ?></td>
                    <td>
                        <?php if (($currentRole ?? null) === 'TECHNICIAN' && $ticket['statut'] === 'NOUVEAU'): ?>
                            <form method="post" action="/tickets/<?= e((string) $ticket['id']) ?>/assign">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Prendre en charge</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>