<h1>Connexion</h1>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<form method="post" action="/login" class="col-md-6">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>

        <input
            type="email"
            class="form-control"
            id="email"
            name="email"
            value="<?= e($old['email'] ?? '') ?>"
        >
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
    </div>

    <button type="submit" class="btn btn-primary">
        Se connecter
    </button>
</form>