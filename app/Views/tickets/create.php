<?php $cancelHref = in_array($currentRole ?? null, ['TECHNICIAN', 'ADMIN'], true) ? '/tickets/all' : '/tickets'; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-1">Créer un ticket</h1>
        <p class="text-muted mb-4">
            Décrivez votre problème ou votre demande, un technicien le prendra en charge.
        </p>

        <div class="section-card p-3 p-md-4">
            <form method="post" action="/tickets/create">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="titre" class="form-label">Titre</label>

                    <input
                        type="text"
                        class="form-control"
                        id="titre"
                        name="titre"
                        placeholder="Ex : Imprimante du 2e étage ne répond plus"
                        value="<?= e($old['titre'] ?? '') ?>"
                    >

                    <?php if (!empty($errors['titre'])): ?>
                        <div class="text-danger small">
                            <?= e($errors['titre']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Description</label>

                    <textarea
                        class="form-control"
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="Décrire le problème, les messages d'erreur, ce qui a déjà été tenté..."
                    ><?= e($old['description'] ?? '') ?></textarea>

                    <?php if (!empty($errors['description'])): ?>
                        <div class="text-danger small">
                            <?= e($errors['description']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="type" class="form-label">Type</label>

                        <select class="form-select" id="type" name="type">
                            <option value="">-- Choisir --</option>
                            <option value="INCIDENT" <?= ($old['type'] ?? '') === 'INCIDENT' ? 'selected' : '' ?>>Incident</option>
                            <option value="DEMANDE" <?= ($old['type'] ?? '') === 'DEMANDE' ? 'selected' : '' ?>>Demande</option>
                        </select>

                        <?php if (!empty($errors['type'])): ?>
                            <div class="text-danger small">
                                <?= e($errors['type']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Catégorie</label>

                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">-- Choisir --</option>
                            <?php foreach ($categories as $category): ?>
                                <option
                                    value="<?= e((string) $category['id']) ?>"
                                    <?= (string) ($old['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>
                                >
                                    <?= e($category['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (!empty($errors['category_id'])): ?>
                            <div class="text-danger small">
                                <?= e($errors['category_id']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="impact" class="form-label">Impact</label>

                        <select class="form-select" id="impact" name="impact">
                            <option value="">-- Choisir --</option>
                            <option value="FAIBLE" <?= ($old['impact'] ?? '') === 'FAIBLE' ? 'selected' : '' ?>>Faible</option>
                            <option value="MOYEN" <?= ($old['impact'] ?? '') === 'MOYEN' ? 'selected' : '' ?>>Moyen</option>
                            <option value="ELEVE" <?= ($old['impact'] ?? '') === 'ELEVE' ? 'selected' : '' ?>>Élevé</option>
                        </select>

                        <?php if (!empty($errors['impact'])): ?>
                            <div class="text-danger small">
                                <?= e($errors['impact']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label for="urgence" class="form-label">Urgence</label>

                        <select class="form-select" id="urgence" name="urgence">
                            <option value="">-- Choisir --</option>
                            <option value="FAIBLE" <?= ($old['urgence'] ?? '') === 'FAIBLE' ? 'selected' : '' ?>>Faible</option>
                            <option value="MOYENNE" <?= ($old['urgence'] ?? '') === 'MOYENNE' ? 'selected' : '' ?>>Moyenne</option>
                            <option value="ELEVEE" <?= ($old['urgence'] ?? '') === 'ELEVEE' ? 'selected' : '' ?>>Élevée</option>
                        </select>

                        <?php if (!empty($errors['urgence'])): ?>
                            <div class="text-danger small">
                                <?= e($errors['urgence']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="text-muted small mb-4">
                    La priorité est calculée automatiquement à partir de l'impact et de l'urgence.
                </p>

                <div class="d-flex gap-3 align-items-center">
                    <button type="submit" class="btn btn-primary">
                        Créer le ticket
                    </button>

                    <a href="<?= e($cancelHref) ?>" class="text-decoration-none">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
