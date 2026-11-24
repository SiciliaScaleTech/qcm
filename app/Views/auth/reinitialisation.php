<div class="auth-card">
<h1>Nouveau mot de passe</h1>

<?php if (!$valide): ?>
    <div class="alert" role="alert">Ce lien n'est plus valable : il a expiré ou a déjà été utilisé.</div>
    <p><a class="btn btn-link" href="<?= e(url('/mot-de-passe-oublie')) ?>">Demander un nouveau lien</a></p>
<?php else: ?>
    <?php if ($errors): ?>
        <div class="alert" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/reinitialisation/' . $jeton)) ?>" class="form">
        <?= csrf_field() ?>

        <label for="password">Nouveau mot de passe</label>
        <input type="password" id="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required aria-describedby="password-aide">
        <p class="note" id="password-aide">12 caractères minimum, avec une minuscule, une majuscule, un chiffre et un caractère spécial.</p>

        <label for="password_confirm">Confirmer le mot de passe</label>
        <input type="password" id="password_confirm" name="password_confirm" minlength="12" maxlength="72" autocomplete="new-password" required>

        <button type="submit" class="btn">Enregistrer le mot de passe</button>
    </form>
<?php endif; ?>
</div>
