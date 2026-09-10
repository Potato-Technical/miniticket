<h1>Mes tickets</h1>

<p>
    <a href="/tickets/create" class="btn btn-primary btn-sm">
        Créer un ticket
    </a>
</p>

<?php if ($tickets === []): ?>
    <p>Vous n'avez créé aucun ticket pour l'instant.</p>
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
                    <td>
                        <?= e($categoryLabels[(int) $ticket['category_id']] ?? 'Catégorie inconnue') ?>
                    </td>
                    <td><?= e($ticket['impact']) ?></td>
                    <td><?= e($ticket['urgence']) ?></td>
                    <td><?= e($ticket['priorite']) ?></td>
                    <td><?= e($ticket['statut']) ?></td>
                    <td><?= e($ticket['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
