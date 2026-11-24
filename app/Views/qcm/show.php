<a class="back" href="<?= e(url('/')) ?>">&larr; Mes QCM</a>

<h1><?= e($qcm['titre']) ?></h1>
<div class="qcm-meta">
    <span class="badge badge-categorie"><?= e($qcm['categorie']) ?></span>
    <span class="badge badge-annee">Évaluation du <?= e(date_fr($qcm['date_evaluation'])) ?></span>
    <span class="badge"><?= e($qcm['annee_scolaire']) ?></span>
</div>
<?php if ($qcm['description']): ?>
    <p><?= nl2br(e($qcm['description'])) ?></p>
<?php endif; ?>

<section class="card" aria-labelledby="partage">
    <h2 id="partage">Liens de partage</h2>
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
    <?php foreach ($classes as $classe): ?>
        <div class="share-block">
            <label for="lien-<?= (int) $classe['id_classe'] ?>" class="share-label"><?= e($classe['nom']) ?></label>
            <div class="share-row">
                <input type="text" id="lien-<?= (int) $classe['id_classe'] ?>" class="share-input" value="<?= e($classe['lien']) ?>" readonly data-select>
                <button type="button" class="btn" data-copy="#lien-<?= (int) $classe['id_classe'] ?>">Copier le lien</button>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<div class="actions">
    <a class="btn btn-secondary btn-link" href="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/statistiques')) ?>">Statistiques</a>
    <a class="btn btn-secondary btn-link" href="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/modifier')) ?>">Modifier le QCM</a>
    <form method="post" action="<?= e(url('/qcm/' . $qcm['id_qcm'] . '/supprimer')) ?>"
          data-confirm="Cette action supprime aussi ses <?= count($questions) ?> question(s) et les <?= (int) $nbTentatives ?> résultat(s) des étudiants. Elle est définitive."
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

<h2 id="resultats">Résultats (<?= (int) $nbTentatives ?>)</h2>

<?php if (count($classes) > 1): ?>
    <nav class="tabs" aria-label="Filtrer les résultats par classe">
        <span class="tabs-label">Classe</span>
        <a href="<?= e(url('/qcm/' . $qcm['id_qcm']) . '#resultats') ?>" <?= $classeActive === null ? 'aria-current="page"' : '' ?>>Toutes</a>
        <?php foreach ($classes as $classe): ?>
            <a href="<?= e(url('/qcm/' . $qcm['id_qcm']) . '?classe=' . (int) $classe['id_classe'] . '#resultats') ?>" <?= $classeActive === (int) $classe['id_classe'] ? 'aria-current="page"' : '' ?>>
                <?= e($classe['nom']) ?> <span class="tabs-count"><?= (int) $classe['nb_tentatives'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<?php if (!$tentatives): ?>
    <p class="empty"><?= $classeActive === null ? "Aucun étudiant n'a encore passé ce QCM." : "Aucun étudiant de cette classe n'a encore passé ce QCM." ?></p>
<?php else: ?>
    <p class="moyenne">Moyenne : <strong><?= format_note($moyenne) ?> / 20</strong></p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">Nom</th>
                    <th scope="col">Prénom</th>
                    <th scope="col">Classe</th>
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
                        <td><?= e($tentative['classe'] ?? '-') ?></td>
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
