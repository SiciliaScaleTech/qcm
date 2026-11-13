<div class="auth-card">
<h1><?= e($qcm['titre']) ?></h1>
<?php if ($qcm['description']): ?>
    <p><?= nl2br(e($qcm['description'])) ?></p>
<?php endif; ?>
<p><?= (int) $nbQuestions ?> question<?= $nbQuestions > 1 ? 's' : '' ?></p>

<?php if ($errors): ?>
    <div class="alert" role="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/q/' . $qcm['slug'])) ?>" class="form">
    <?= csrf_field() ?>

    <label for="nom">Nom</label>
    <input type="text" id="nom" name="nom" value="<?= e($old['nom'] ?? '') ?>" maxlength="100" autocomplete="family-name" required>

    <label for="prenom">Prénom</label>
    <input type="text" id="prenom" name="prenom" value="<?= e($old['prenom'] ?? '') ?>" maxlength="100" autocomplete="given-name" required>

    <p class="note">
        Votre nom, votre prénom et vos réponses sont transmis uniquement au formateur, avec votre résultat,
        et effacés au plus tard <?= (int) \App\Models\Tentative::DUREE_CONSERVATION_MOIS ?> mois après. <a href="<?= e(url('/confidentialite')) ?>">Vos données et vos droits</a>
    </p>

    <button type="submit" class="btn">Commencer</button>
</form>
</div>
