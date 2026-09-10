<h1><?= e($ticket['titre']) ?></h1>

<dl class="row">
    <dt class="col-sm-3">Type</dt>
    <dd class="col-sm-9"><?= e($ticket['type']) ?></dd>

    <dt class="col-sm-3">Catégorie</dt>
    <dd class="col-sm-9"><?= e($categoryLabel) ?></dd>

    <dt class="col-sm-3">Impact</dt>
    <dd class="col-sm-9"><?= e($ticket['impact']) ?></dd>

    <dt class="col-sm-3">Urgence</dt>
    <dd class="col-sm-9"><?= e($ticket['urgence']) ?></dd>

    <dt class="col-sm-3">Priorité</dt>
    <dd class="col-sm-9"><?= e($ticket['priorite']) ?></dd>

    <dt class="col-sm-3">Statut</dt>
    <dd class="col-sm-9"><?= e($ticket['statut']) ?></dd>

    <dt class="col-sm-3">Créé le</dt>
    <dd class="col-sm-9"><?= e($ticket['created_at']) ?></dd>
</dl>

<h2>Description</h2>
<p><?= nl2br(e($ticket['description'])) ?></p>

<h2>Commentaires</h2>

<?php if ($comments === []): ?>
    <p>Aucun commentaire pour l'instant.</p>
<?php else: ?>
    <ul class="list-unstyled">
        <?php foreach ($comments as $comment): ?>
            <li class="mb-3">
                <strong><?= e($comment['pseudo']) ?></strong>
                <small class="text-muted"><?= e($comment['created_at']) ?></small>
                <p><?= nl2br(e($comment['contenu'])) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h3>Ajouter un commentaire</h3>

<form method="post" action="/tickets/<?= e((string) $ticket['id']) ?>/comments">
    <?= csrf_field() ?>

    <div class="mb-3">
        <textarea class="form-control" name="contenu" rows="3"><?= e($oldComment) ?></textarea>

        <?php if (!empty($commentErrors['contenu'])): ?>
            <div class="text-danger small">
                <?= e($commentErrors['contenu']) ?>
            </div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Publier</button>
</form>