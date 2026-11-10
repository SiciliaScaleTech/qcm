<a class="back" href="<?= e(url('/')) ?>">&larr; Mes QCM</a>

<h1><?= e($qcm['titre']) ?></h1>
<div class="qcm-meta">
    <span class="badge badge-categorie"><?= e($qcm['categorie']) ?></span>
    <span class="badge badge-annee"><?= e($qcm['annee_scolaire']) ?></span>
    <?php foreach ($classes as $classe): ?>
        <span class="badge badge-classe"><?= e($classe['nom']) ?></span>
    <?php endforeach; ?>
</div>
<?php if ($qcm['description']): ?>
    <p><?= nl2br(e($qcm['description'])) ?></p>
<?php endif; ?>

<section class="card" aria-labelledby="partage">
    <h2 id="partage">Lien de partage</h2>
    <div class="switch-row">
        <form method="post" action="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/statut')) ?>" data-toggle>
            <?= csrf_field() ?>
            <button type="submit" class="switch" role="switch" id="actif"
                    aria-checked="<?= $qcm['actif'] ? 'true' : 'false' ?>" aria-labelledby="actif-texte"
                    data-text-on="Lien actif : les étudiants peuvent passer le QCM"
                    data-text-off="Lien désactivé : les étudiants ne peuvent pas l'ouvrir"></button>
        </form>
        <span id="actif-texte"><?= $qcm['actif'] ? 'Lien actif : les étudiants peuvent passer le QCM' : 'Lien désactivé : les étudiants ne peuvent pas l\'ouvrir' ?></span>
    </div>
    <div class="share-row">
        <label for="lien" class="visually-hidden">Lien à donner aux étudiants</label>
        <input type="text" id="lien" class="share-input" value="<?= e($lien) ?>" readonly data-select>
        <button type="button" class="btn" data-copy="#lien">Copier le lien</button>
    </div>
</section>

<div class="actions">
    <a class="btn btn-secondary btn-link" href="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/statistiques')) ?>">Statistiques</a>
    <a class="btn btn-secondary btn-link" href="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/modifier')) ?>">Modifier le QCM</a>
    <form method="post" action="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/supprimer')) ?>"
          data-confirm="Cette action supprime aussi ses <?= count($questions) ?> question(s) et les <?= count($tentatives) ?> résultat(s) des étudiants. Elle est définitive."
          data-confirm-title="Supprimer « <?= e($qcm['titre']) ?> » ?" data-confirm-label="Supprimer le QCM">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Supprimer le QCM</button>
    </form>
</div>

<h2>Questions (<?= count($questions) ?>)</h2>

<p><a class="btn btn-link" href="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/questions/nouvelle')) ?>">Ajouter une question</a></p>

<?php if (!$questions): ?>
    <p class="empty">Ce QCM ne contient aucune question. Ajoutez la première.</p>
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
                      data-confirm="La question et ses réponses seront définitivement supprimées. Les résultats déjà enregistrés ne changent pas."
                      data-confirm-title="Supprimer cette question ?" data-confirm-label="Supprimer la question">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </li>
    <?php endforeach; ?>
</ol>

<h2>Résultats (<?= count($tentatives) ?>)</h2>

<?php if (!$tentatives): ?>
    <p class="empty">Aucun étudiant n'a encore passé ce QCM.</p>
<?php else: ?>
    <p class="moyenne">Moyenne : <strong><?= format_note($moyenne) ?> / 20</strong></p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">Nom</th>
                    <th scope="col">Prénom</th>
                    <th scope="col">Note</th>
                    <th scope="col">Bonnes réponses</th>
                    <th scope="col">Date</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tentatives as $tentative): ?>
                    <tr>
                        <th scope="row"><?= e($tentative['nom_etudiant']) ?></th>
                        <td><?= e($tentative['prenom_etudiant']) ?></td>
                        <td><strong class="note-20"><?= format_note($tentative['note']) ?> / 20</strong></td>
                        <td class="muted"><?= (int) $tentative['score'] ?> / <?= (int) $tentative['nb_questions'] ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($tentative['date_passage']))) ?></td>
                        <td>
                            <form method="post" action="<?= e(url('/tentatives/' . $tentative['id_tentative'] . '/supprimer')) ?>"
                                  data-confirm="Le résultat de <?= e($tentative['prenom_etudiant'] . ' ' . $tentative['nom_etudiant']) ?> sera supprimé définitivement. L'étudiant pourra repasser le QCM avec le même lien."
                                  data-confirm-title="Autoriser un nouveau passage ?" data-confirm-label="Supprimer le résultat">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger btn-small">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
