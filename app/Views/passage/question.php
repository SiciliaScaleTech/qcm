<h1><?= e($qcm['titre']) ?></h1>

<div class="quiz-wrap no-copy" data-no-copy>
<p id="progression" class="progression" aria-live="polite"></p>
<div class="progress" aria-hidden="true"><div class="progress-bar" id="barre"></div></div>

<form method="post" action="<?= e(url('/q/' . $qcm['slug'] . '/terminer')) ?>" id="quiz" class="quiz">
    <?= csrf_field() ?>

    <?php foreach ($questions as $i => $question): ?>
        <fieldset class="question" data-question>
            <legend tabindex="-1">Question <?= $i + 1 ?> sur <?= count($questions) ?> : <?= e($question['enonce']) ?></legend>
            <?php foreach ($question['reponses'] as $k => $reponse): ?>
                <label class="choix">
                    <input type="radio" name="reponse[<?= (int) $question['id_question'] ?>]" value="<?= (int) $reponse['id_reponse'] ?>">
                    <span><?= e($reponse['texte']) ?></span>
                    <kbd aria-hidden="true"><?= $k + 1 ?></kbd>
                </label>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>

    <p id="erreur-reponse" class="alert" role="alert" hidden>Choisissez une réponse pour continuer.</p>

    <div class="actions">
        <button type="button" class="btn btn-secondary" id="precedent" hidden>Précédent</button>
        <button type="button" class="btn" id="suivant" hidden>Suivant</button>
        <button type="submit" class="btn" id="terminer">Terminer</button>
    </div>
</form>
</div>
