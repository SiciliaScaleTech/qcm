<div class="auth-card">
<h1>Mot de passe oublié</h1>
<p class="note">Indiquez l'adresse email de votre compte intervenant. Vous recevrez un lien pour choisir un nouveau mot de passe.</p>

<?php if ($errors): ?>
    <div class="alert" role="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/mot-de-passe-oublie')) ?>" class="form">
    <?= csrf_field() ?>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" maxlength="255" autocomplete="email" required>

    <button type="submit" class="btn">Recevoir le lien</button>
</form>

<p><a href="<?= e(url('/connexion')) ?>">Retour à la connexion</a></p>
</div>
