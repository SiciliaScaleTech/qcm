<?php
$modification = $qcm !== null;
$action = $modification ? '/qcm/' . $qcm['id_qcm'] . '/modifier' : '/qcm';
$titre = $old['titre'] ?? $qcm['titre'] ?? '';
$description = $old['description'] ?? $qcm['description'] ?? '';
$categorieChoisie = (int) ($old['categorie'] ?? $qcm['id_categorie'] ?? 0);
$anneeChoisie = $old['annee'] ?? $qcm['annee_scolaire'] ?? annee_scolaire_courante();
$nouvelles = $old['nouvelles'] ?? '';

// Un QCM d'une ancienne année doit pouvoir garder son année dans la liste
if (!in_array($anneeChoisie, $annees, true)) {
    $annees[] = $anneeChoisie;
}
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
    <input type="text" id="titre" name="titre" value="<?= e($titre) ?>" maxlength="150" required aria-describedby="titre-aide">
    <p class="note" id="titre-aide">
        Le lien donné aux étudiants reprend le titre<?php if ($modification): ?> : <strong><?= e(absolute_url('/q/' . $qcm['slug'])) ?></strong>.
        Si vous changez le titre, ce lien change aussi<?php else: ?>, par exemple « Les bases du HTML » donne /q/les-bases-du-html<?php endif; ?>.
    </p>

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

    <label for="annee">Année scolaire</label>
    <select id="annee" name="annee" required>
        <?php foreach ($annees as $annee): ?>
            <option value="<?= e($annee) ?>" <?= $annee === $anneeChoisie ? 'selected' : '' ?>><?= e($annee) ?></option>
        <?php endforeach; ?>
    </select>

    <fieldset>
        <legend>Classes concernées (facultatif)</legend>
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
        <p class="note" id="classes-aide">Séparez les noms par des virgules. Une classe sans QCM est supprimée automatiquement.</p>
    </fieldset>

    <label for="description">Description (facultatif)</label>
    <textarea id="description" name="description" rows="4" maxlength="1000"><?= e($description) ?></textarea>

    <button type="submit" class="btn"><?= $modification ? 'Enregistrer' : 'Créer le QCM' ?></button>
</form>

<p>
    <a href="<?= e(url($modification ? '/qcm/' . $qcm['id_qcm'] : '/')) ?>">Annuler</a>
</p>
