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