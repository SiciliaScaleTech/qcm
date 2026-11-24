<?php
$listeErreurs = function (array $erreurs): void {
    if (!$erreurs) {
        return;
    }
    echo '<div class="alert" role="alert"><ul>';
    foreach ($erreurs as $erreur) {
        echo '<li>' . e($erreur) . '</li>';
    }
    echo '</ul></div>';
};
?>
<div class="page-head">
    <div>
        <h1>Mon profil</h1>
        <p>Intervenant depuis le <?= e(date_fr($inscritLe)) ?>.</p>
    </div>
</div>

<div class="profil">
    <section class="form profil-bloc" aria-labelledby="titre-infos">
        <h2 id="titre-infos">Mes informations</h2>
        <?php $listeErreurs($erreursInfos); ?>
        <form method="post" action="<?= e(url('/profil')) ?>">
            <?= csrf_field() ?>

            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" value="<?= e($old['nom'] ?? '') ?>" maxlength="100" autocomplete="name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" maxlength="255" autocomplete="email" required>

            <label for="mdp-infos">Mot de passe actuel</label>
            <input type="password" id="mdp-infos" name="mot_de_passe_actuel" maxlength="72" autocomplete="current-password" required aria-describedby="mdp-infos-aide">
            <p class="note" id="mdp-infos-aide">Demandé pour confirmer que c'est bien vous.</p>

            <button type="submit" class="btn">Enregistrer mes informations</button>
        </form>
    </section>

    <section class="form profil-bloc" aria-labelledby="titre-mdp">
        <h2 id="titre-mdp">Changer mon mot de passe</h2>
        <?php $listeErreurs($erreursMdp); ?>
        <form method="post" action="<?= e(url('/profil/mot-de-passe')) ?>">
            <?= csrf_field() ?>

            <label for="mdp-actuel">Mot de passe actuel</label>
            <input type="password" id="mdp-actuel" name="mot_de_passe_actuel" maxlength="72" autocomplete="current-password" required>

            <label for="password">Nouveau mot de passe</label>
            <input type="password" id="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required aria-describedby="password-aide">
            <p class="note" id="password-aide">12 caractères minimum, avec une minuscule, une majuscule, un chiffre et un caractère spécial.</p>

            <label for="password_confirm">Confirmer le nouveau mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="12" maxlength="72" autocomplete="new-password" required>

            <button type="submit" class="btn">Changer mon mot de passe</button>
        </form>
    </section>
</div>
