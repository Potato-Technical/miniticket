<h1>Créer un compte</h1>

<?php if (!empty($errors['account'])): ?>
    <div class="alert alert-danger">
        <?= e($errors['account']) ?>
    </div>
<?php endif; ?>

<form method="post" action="/register" class="col-md-6">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="pseudo" class="form-label">Pseudo</label>

        <input
            type="text"
            class="form-control"
            id="pseudo"
            name="pseudo"
            value="<?= e($old['pseudo'] ?? '') ?>"
        >

        <?php if (!empty($errors['pseudo'])): ?>
            <div class="text-danger small">
                <?= e($errors['pseudo']) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>

        <input
            type="email"
            class="form-control"
            id="email"
            name="email"
            value="<?= e($old['email'] ?? '') ?>"
        >

        <?php if (!empty($errors['email'])): ?>
            <div class="text-danger small">
                <?= e($errors['email']) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">
            Mot de passe
        </label>

        <input
            type="password"
            class="form-control"
            id="password"
            name="password"
        >

        <?php if (!empty($errors['password'])): ?>
            <div class="text-danger small">
                <?= e($errors['password']) ?>
            </div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">
        Créer mon compte
    </button>
</form>

<p class="mt-3">
    Déjà un compte ? <a href="/login">Se connecter</a>
</p>