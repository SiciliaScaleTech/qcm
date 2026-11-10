<div class="no-copy" data-no-copy>
<h1>Résultat</h1>

<div class="result-head">
    <div class="donut" data-donut="<?= (int) $pourcentage ?>">
        <svg viewBox="0 0 36 36" aria-hidden="true">
            <circle class="track" cx="18" cy="18" r="15.915" />
            <circle class="value" cx="18" cy="18" r="15.915" pathLength="100" stroke-dasharray="<?= (int) $pourcentage ?> 100" />
        </svg>
        <span class="label"><span><?= format_note($note) ?><small>/20</small></span></span>
    </div>
    <p class="score">
        <?= e($tentative['prenom_etudiant']) ?> <?= e($tentative['nom_etudiant']) ?>,
        vous avez obtenu <strong><?= format_note($note) ?> / 20</strong>
        au QCM « <?= e($qcm['titre']) ?> » (<?= (int) $tentative['score'] ?> sur <?= (int) $tentative['nb_questions'] ?> bonnes réponses).
    </p>
</div>

<h2>Correction</h2>

<ol class="questions">
    <?php foreach ($questions as $question): ?>
        <?php
        $repondue = false;
        $juste = false;
        foreach ($question['reponses'] as $reponse) {
            if (in_array((int) $reponse['id_reponse'], $choix, true)) {
                $repondue = true;
                $juste = (bool) $reponse['est_correct'];
            }
        }
        ?>
        <li class="card">
            <p>
                <strong><?= e($question['enonce']) ?></strong>
                <?php if (!$repondue): ?>
                    <span class="badge">Sans réponse</span>
                <?php elseif ($juste): ?>
                    <span class="badge badge-ok">Correct</span>
                <?php else: ?>
                    <span class="badge badge-ko">Incorrect</span>
                <?php endif; ?>
            </p>
            <ul class="reponses">
                <?php foreach ($question['reponses'] as $reponse): ?>
                    <li class="<?= $reponse['est_correct'] ? 'correcte' : '' ?>">
                        <?= e($reponse['texte']) ?>
                        <?php if ($reponse['est_correct']): ?>
                            <span class="badge badge-ok">Bonne réponse</span>
                        <?php endif; ?>
                        <?php if (in_array((int) $reponse['id_reponse'], $choix, true)): ?>
                            <span class="badge">Votre réponse</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php endforeach; ?>
</ol>
</div>
