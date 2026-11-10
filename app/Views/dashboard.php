<h1>Mes QCM</h1>
<p>Bonjour <?= e($nom) ?>.</p>

<p><a class="btn btn-link" href="<?= e(url('/qcm/nouveau')) ?>">Nouveau QCM</a></p>

<?php if (!$qcms): ?>
    <p>Vous n'avez pas encore de QCM.</p>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">Titre</th>
                    <th scope="col">Questions</th>
                    <th scope="col">Lien de partage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($qcms as $qcm): ?>
                    <tr>
                        <th scope="row">
                            <a href="<?= e(url('/qcm/' . $qcm['id_qcm'])) ?>"><?= e($qcm['titre']) ?></a>
                        </th>
                        <td><?= (int) $qcm['nb_questions'] ?></td>
                        <td>
                            <?php if ($qcm['actif']): ?>
                                <span class="badge badge-ok">Actif</span>
                            <?php else: ?>
                                <span class="badge">Désactivé</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
