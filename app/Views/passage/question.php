<?php $total = count($questions); ?>
<div class="quiz-head">
    <h1><?= e($qcm['titre']) ?></h1>
    <p class="muted"><?= plural($total, 'question') ?>. Répondez dans l'ordre de votre choix et revenez sur une question à tout moment avant de terminer.</p>
</div>

<form method="post" action="<?= e(url('/q/' . $qcm['slug'] . '/terminer')) ?>" id="quiz" class="quiz no-copy" data-no-copy data-storage="qcm-<?= (int) $qcm['id_qcm'] ?>">
    <?= csrf_field() ?>

    <div class="quiz-questions">
        <?php foreach ($questions as $i => $question): ?>
            <fieldset class="question" id="q-<?= $i + 1 ?>" data-question data-numero="<?= $i + 1 ?>">
                <legend>
                    <span class="question-num">Question <?= $i + 1 ?><span class="visually-hidden"> sur <?= $total ?></span></span>
                    <span class="question-text"><?= e($question['enonce']) ?></span>
                </legend>
                <?php foreach ($question['reponses'] as $k => $reponse): ?>
                    <label class="choix">
                        <input type="radio" name="reponse[<?= (int) $question['id_question'] ?>]" value="<?= (int) $reponse['id_reponse'] ?>">
                        <span class="choix-lettre" aria-hidden="true"><?= chr(65 + $k) ?></span>
                        <span><?= e($reponse['texte']) ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        <?php endforeach; ?>
    </div>

    <aside class="quiz-panel" aria-label="Avancement du QCM">
        <p class="quiz-count"><strong id="nb-repondues">0</strong> / <?= $total ?> réponses</p>
        <div class="progress" aria-hidden="true"><div class="progress-bar" id="barre"></div></div>

        <details class="quiz-nav" open>
            <summary>Toutes les questions</summary>
            <ol class="quiz-grid">
                <?php for ($n = 1; $n <= $total; $n++): ?>
                    <li><a href="#q-<?= $n ?>" data-nav="<?= $n ?>" aria-label="Question <?= $n ?>, sans réponse"><?= $n ?></a></li>
                <?php endfor; ?>
            </ol>
            <p class="quiz-legend">
                <span class="dot dot-ok" aria-hidden="true"></span>Répondue
                <span class="dot" aria-hidden="true"></span>Sans réponse
            </p>
        </details>

        <button type="button" class="btn btn-secondary btn-block" id="sans-reponse" hidden>Aller à une question sans réponse</button>
        <button type="submit" class="btn btn-block" id="terminer">Terminer le QCM</button>
    </aside>
</form>

<dialog id="quiz-dialog" class="modal" aria-labelledby="quiz-dialog-title">
    <form method="dialog">
        <h2 id="quiz-dialog-title">Terminer le QCM ?</h2>
        <p id="quiz-dialog-text"></p>
        <div class="actions">
            <button type="submit" value="cancel" class="btn btn-secondary" id="quiz-dialog-cancel">Revenir aux questions</button>
            <button type="submit" value="ok" class="btn">Terminer</button>
        </div>
    </form>
</dialog>
