<?php
$modification = $qcm !== null;
$action = $modification ? '/qcm/' . $qcm['id_qcm'] . '/modifier' : '/qcm';
$titre = $old['titre'] ?? $qcm['titre'] ?? '';
$description = $old['description'] ?? $qcm['description'] ?? '';
$actif = $old['actif'] ?? (bool) ($qcm['actif'] ?? true);
?>
<h1><?= $modification ? 'Modifier le QCM' : 'Nouveau QCM' ?></h1>

<?php if ($errors): ?>
    <div class="alert" role="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url($action)) ?>" class="form">
    <?= csrf_field() ?>

    <label for="titre">Titre</label>
    <input type="text" id="titre" name="titre" value="<?= e($titre) ?>" maxlength="150" required>

    <label for="description">Description (facultatif)</label>
    <textarea id="description" name="description" rows="4" maxlength="1000"><?= e($description) ?></textarea>

    <?php if ($modification): ?>
        <label class="check">
            <input type="checkbox" name="actif" value="1" <?= $actif ? 'checked' : '' ?>>
            Lien de partage actif
        </label>
    <?php endif; ?>

    <button type="submit" class="btn"><?= $modification ? 'Enregistrer' : 'Créer le QCM' ?></button>
</form>

<p>
    <a href="<?= e(url($modification ? '/qcm/' . $qcm['id_qcm'] : '/tableau-de-bord')) ?>">Annuler</a>
</p>
