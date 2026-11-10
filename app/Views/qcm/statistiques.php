<?php $passages = $lignes ? max(array_column($lignes, 'total')) : 0; ?>
<a class="back" href="<?= e(url('/qcm/' . $qcm['id_qcm'])) ?>">&larr; Retour au QCM</a>

<h1>Statistiques</h1>
<p class="page-sub"><?= e($qcm['titre']) ?></p>

<?php if (!$disponible): ?>
    <div class="alert" role="status">
        Les statistiques sont indisponibles pour le moment : la base MongoDB ne répond pas.
        Le reste du site fonctionne normalement.
    </div>
<?php elseif (!$lignes): ?>
    <p class="empty">Ce QCM ne contient aucune question.</p>
<?php elseif ($passages === 0): ?>
    <p class="empty">Aucun passage enregistré pour l'instant. Les statistiques apparaîtront dès qu'un étudiant aura terminé le QCM.</p>
<?php else: ?>
    <p class="note">Calculées sur <?= plural($passages, 'passage') ?>, y compris ceux dont le résultat a été supprimé pour permettre un nouveau passage.</p>

    <div class="table-wrap">
        <table class="stats-table">
            <thead>
                <tr>
                    <th scope="col">N°</th>
                    <th scope="col">Question</th>
                    <th scope="col">Taux de réussite</th>
                    <th scope="col">Option la plus choisie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $i => $ligne): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <th scope="row"><?= e($ligne['enonce']) ?></th>
                        <td>
                            <?php if ($ligne['taux'] === null): ?>
                                <span class="muted">Pas encore de réponse</span>
                            <?php else: ?>
                                <div class="taux">
                                    <progress max="100" value="<?= e((string) $ligne['taux']) ?>"
                                              class="<?= $ligne['taux'] < 50 ? 'taux-faible' : '' ?>"></progress>
                                    <strong><?= format_note($ligne['taux']) ?> %</strong>
                                </div>
                                <span class="muted"><?= plural($ligne['total'], 'réponse') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ligne['plus_choisie'] === null): ?>
                                <span class="muted">Aucune</span>
                            <?php else: ?>
                                <?= e($ligne['plus_choisie']['texte']) ?>
                                <span class="muted">(<?= (int) $ligne['nb_plus_choisie'] ?>)</span>
                                <?php if ($ligne['plus_choisie']['est_correct']): ?>
                                    <span class="badge badge-ok">Bonne réponse</span>
                                <?php else: ?>
                                    <span class="badge badge-ko">Mauvaise réponse</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
