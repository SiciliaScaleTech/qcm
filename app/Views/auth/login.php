<h1>Connexion</h1>

<?php if ($errors): ?>
    <div class="alert" role="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/connexion')) ?>" class="form">
    <?= csrf_field() ?>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required>

    <label for="password">Mot de passe</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>

    <button type="submit" class="btn">Se connecter</button>
</form>

<p>Pas encore de compte ? <a href="<?= e(url('/inscription')) ?>">S'inscrire</a></p>
