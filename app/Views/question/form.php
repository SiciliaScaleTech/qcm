<?php
$modification = $question !== null;
$action = $modification
    ? '/questions/' . $question['id_question'] . '/modifier'
    : '/qcm/' . $qcm['id_qcm'] . '/questions';

$enonce = $old['enonce'] ?? $question['enonce'] ?? '';
$reponses = $old['reponses'] ?? array_column($question['reponses'] ?? [], 'texte');
$reponses = array_pad($reponses, 4, '');

$correcte = $old['correct'] ?? null;
if ($correcte === null && $modification) {
    foreach ($question['reponses'] as $i => $reponse) {
        if ($reponse['est_correct']) {
            $correcte = $i;
        }
    }
}
?>
<p><a href="<?= e(url('/qcm/' . $qcm['id_qcm'])) ?>">Retour au QCM <?= e($qcm['titre']) ?></a></p>

<h1><?= $modification ? 'Modifier la question' : 'Nouvelle question' ?></h1>

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

    <label for="enonce">Question</label>
    <textarea id="enonce" name="enonce" rows="3" maxlength="1000" required><?= e($enonce) ?></textarea>

    <fieldset>
        <legend>Les 4 réponses, avec la bonne réponse cochée</legend>
        <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="reponse-row">
                <label for="reponse<?= $i ?>">Réponse <?= $i + 1 ?></label>
                <input type="text" id="reponse<?= $i ?>" name="reponses[]" value="<?= e($reponses[$i]) ?>" maxlength="255" required>
                <label class="check">
                    <input type="radio" name="correct" value="<?= $i ?>" <?= $correcte === $i ? 'checked' : '' ?> required>
                    Bonne réponse
                </label>
            </div>
        <?php endfor; ?>
    </fieldset>

    <button type="submit" class="btn"><?= $modification ? 'Enregistrer' : 'Ajouter la question' ?></button>
</form>
