<?php
$modification = $qcm !== null;
$action = $modification ? '/qcm/' . $qcm['id_qcm'] . '/modifier' : '/qcm';
$titre = $old['titre'] ?? $qcm['titre'] ?? '';
$description = $old['description'] ?? $qcm['description'] ?? '';
$categorieChoisie = (int) ($old['categorie'] ?? $qcm['id_categorie'] ?? 0);
$dateChoisie = $old['date'] ?? '';
$nouvelles = $old['nouvelles'] ?? '';
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

    <label for="date_evaluation">Date de l'évaluation</label>
    <input type="date" id="date_evaluation" name="date_evaluation" value="<?= e($dateChoisie) ?>" required aria-describedby="lien-aide">

    <fieldset>
        <legend>Catégorie</legend>
        <?php foreach ($categories as $categorie): ?>
            <label class="check">
                <input type="radio" name="categorie" value="<?= (int) $categorie['id_categorie'] ?>" required
                    <?= (int) $categorie['id_categorie'] === $categorieChoisie ? 'checked' : '' ?>>
                <?= e($categorie['nom']) ?>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <fieldset>
        <legend>Classes</legend>
        <?php foreach ($classes as $classe): ?>
            <label class="check">
                <input type="checkbox" name="classes[]" value="<?= (int) $classe['id_classe'] ?>"
                    <?= in_array((int) $classe['id_classe'], $classesCochees, true) ? 'checked' : '' ?>>
                <?= e($classe['nom']) ?>
            </label>
        <?php endforeach; ?>

        <label for="nouvelles_classes"><?= $classes ? "Ajouter d'autres classes" : 'Classes' ?></label>
        <input type="text" id="nouvelles_classes" name="nouvelles_classes" value="<?= e($nouvelles) ?>"
               placeholder="BTS SIO 1, BTS SIO 2" aria-describedby="classes-aide">
        <p class="note" id="classes-aide">Séparez les noms par des virgules.</p>
    </fieldset>

    <p class="note" id="lien-aide">
        Chaque classe reçoit son propre lien, construit avec le titre, la classe et la date :
        « Les bases du HTML », BTS SIO 1, 24/11/2026 donne /q/les-bases-du-html-bts-sio-1-24-11-2026.
        <?php if ($modification): ?>Si vous changez le titre ou la date, les liens changent aussi.<?php endif; ?>
    </p>

    <label for="description">Description (facultatif)</label>
    <textarea id="description" name="description" rows="4" maxlength="1000"><?= e($description) ?></textarea>

    <button type="submit" class="btn"><?= $modification ? 'Enregistrer' : 'Créer le QCM' ?></button>
</form>

<p>
    <a href="<?= e(url($modification ? '/qcm/' . $qcm['id_qcm'] : '/')) ?>">Annuler</a>
</p>
