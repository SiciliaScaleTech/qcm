<p><a href="<?= e(url('/tableau-de-bord')) ?>">Retour à mes QCM</a></p>

<h1><?= e($qcm['titre']) ?></h1>
<?php if ($qcm['description']): ?>
    <p><?= nl2br(e($qcm['description'])) ?></p>
<?php endif; ?>

<section class="card" aria-labelledby="partage">
    <h2 id="partage">Lien de partage</h2>
    <p>
        <?php if ($qcm['actif']): ?>
            <span class="badge badge-ok">Actif</span>
        <?php else: ?>
            <span class="badge">Désactivé : les étudiants ne peuvent pas l'ouvrir</span>
        <?php endif; ?>
    </p>
    <label for="lien" class="visually-hidden">Lien à donner aux étudiants</label>
    <input type="text" id="lien" class="share-input" value="<?= e($lien) ?>" readonly data-select>
</section>

<div class="actions">
    <a class="btn btn-secondary btn-link" href="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/modifier')) ?>">Modifier le QCM</a>
    <form method="post" action="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/supprimer')) ?>"
          data-confirm="Supprimer ce QCM, ses questions et tous les résultats ?">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Supprimer le QCM</button>
    </form>
</div>

<h2>Questions (<?= count($questions) ?>)</h2>

<p><a class="btn btn-link" href="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/questions/nouvelle')) ?>">Ajouter une question</a></p>

<?php if (!$questions): ?>
    <p>Ce QCM ne contient aucune question.</p>
<?php endif; ?>

<ol class="questions">
    <?php foreach ($questions as $question): ?>
        <li class="card">
            <p><strong><?= e($question['enonce']) ?></strong></p>
            <ul class="reponses">
                <?php foreach ($question['reponses'] as $reponse): ?>
                    <li class="<?= $reponse['est_correct'] ? 'correcte' : '' ?>">
                        <?= e($reponse['texte']) ?>
                        <?php if ($reponse['est_correct']): ?>
                            <span class="badge badge-ok">Bonne réponse</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="actions">
                <a class="btn btn-secondary btn-link" href="<?= e(url('/questions/' . $question['id_question'] . '/modifier')) ?>">Modifier</a>
                <form method="post" action="<?= e(url('/questions/' . $question['id_question'] . '/supprimer')) ?>"
                      data-confirm="Supprimer cette question ?">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </li>
    <?php endforeach; ?>
</ol>
