<div class="auth-card">
<h1>Inscription formateur</h1>

<?php if ($errors): ?>
    <div class="alert" role="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/inscription')) ?>" class="form">
    <?= csrf_field() ?>

    <label for="nom">Nom</label>
    <input type="text" id="nom" name="nom" value="<?= e($old['nom'] ?? '') ?>" maxlength="100" autocomplete="name" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" maxlength="255" autocomplete="email" required>

    <label for="password">Mot de passe (8 caractères minimum)</label>
    <input type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>

    <label for="password_confirm">Confirmer le mot de passe</label>
    <input type="password" id="password_confirm" name="password_confirm" minlength="8" maxlength="72" autocomplete="new-password" required>

    <p class="note">
        Votre nom et votre e-mail servent uniquement à gérer votre compte.
        <a href="<?= e(url('/confidentialite')) ?>">Politique de confidentialité</a>
    </p>

    <button type="submit" class="btn">Créer mon compte</button>
</form>

<p>Déjà inscrit ? <a href="<?= e(url('/connexion')) ?>">Se connecter</a></p>
</div>
