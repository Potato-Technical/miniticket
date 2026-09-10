<h1>Créer un ticket</h1>

<form method="post" action="/tickets/create" class="col-md-8">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="titre" class="form-label">Titre</label>

        <input
            type="text"
            class="form-control"
            id="titre"
            name="titre"
            value="<?= e($old['titre'] ?? '') ?>"
        >

        <?php if (!empty($errors['titre'])): ?>
            <div class="text-danger small">
                <?= e($errors['titre']) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description</label>

        <textarea
            class="form-control"
            id="description"
            name="description"
            rows="5"
        ><?= e($old['description'] ?? '') ?></textarea>

        <?php if (!empty($errors['description'])): ?>
            <div class="text-danger small">
                <?= e($errors['description']) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
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

    <div class="mb-3">
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

    <div class="mb-3">
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

    <div class="mb-3">
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

    <button type="submit" class="btn btn-primary">
        Créer le ticket
    </button>
</form>