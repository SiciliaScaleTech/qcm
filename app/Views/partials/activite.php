<?php
$styles = [];

if ($activite !== null) {
    $totalPassages = array_sum(array_column($activite, 'passages'));
    $sommeNotes = 0.0;
    foreach ($activite as $semaine) {
        $sommeNotes += $semaine['note'] !== null ? $semaine['note'] * $semaine['passages'] : 0;
    }
    $noteMoyenne = $totalPassages > 0 ? round($sommeNotes / $totalPassages, 1) : null;

    $max = max(array_column($activite, 'passages')) ?: 1;
    $pas = $max <= 4 ? 1 : ($max <= 10 ? 2 : (int) (ceil($max / 4 / 5) * 5));
    $plafond = (int) (ceil($max / $pas) * $pas);
    $graduations = range(0, $plafond, $pas);

    foreach ($activite as $i => $semaine) {
        $styles[] = '#barre-' . $i . '{height:' . round($semaine['passages'] * 100 / $plafond, 2) . '%}';
    }
    foreach ($graduations as $i => $valeur) {
        $styles[] = '#graduation-' . $i . '{bottom:' . round($valeur * 100 / $plafond, 2) . '%}';
    }
}

foreach ($reussite ?? [] as $i => $ligne) {
    $styles[] = '#reussite-' . $i . '{width:' . max(0, min(100, $ligne['taux'])) . '%}';
}
?>
<?php if ($styles): ?>
<style nonce="<?= e(csp_nonce()) ?>"><?= implode('', $styles) ?></style>
<?php endif; ?>

<section class="activite" aria-labelledby="titre-activite">
    <h2 id="titre-activite" class="visually-hidden">Activité de vos étudiants</h2>

    <?php if ($activite === null): ?>
        <div class="carte-graphique">
            <p class="empty">Les statistiques sont indisponibles pour le moment : la base MongoDB ne répond pas.</p>
        </div>
    <?php else: ?>
        <div class="carte-graphique">
            <div class="carte-tete">
                <div>
                    <h3>Passages par semaine</h3>
                    <p class="note">Sur les <?= count($activite) ?> dernières semaines</p>
                </div>
                <div class="chiffres">
                    <p><strong><?= (int) $totalPassages ?></strong><span><?= $totalPassages > 1 ? 'passages' : 'passage' ?></span></p>
                    <p><strong><?= $noteMoyenne !== null ? e(format_note($noteMoyenne)) : '-' ?></strong><span>note moyenne /20</span></p>
                </div>
            </div>

            <?php if ($totalPassages === 0): ?>
                <p class="empty">Aucun étudiant n'a passé de QCM sur cette période.</p>
            <?php else: ?>
                <div class="histogramme">
                    <div class="histogramme-zone">
                        <?php foreach ($graduations as $i => $valeur): ?>
                            <span class="graduation" id="graduation-<?= $i ?>" aria-hidden="true"><span><?= (int) $valeur ?></span></span>
                        <?php endforeach; ?>
                        <ol class="barres">
                            <?php foreach ($activite as $i => $semaine):
                                $infos = 'Semaine du ' . $semaine['debut']->format('d/m') . ' : ' . plural($semaine['passages'], 'passage')
                                    . ($semaine['note'] !== null ? ', note moyenne ' . format_note($semaine['note']) . '/20' : '');
                            ?>
                                <li class="barre-col" tabindex="0" data-info="<?= e($infos) ?>" aria-label="<?= e($infos) ?>">
                                    <span class="barre" id="barre-<?= $i ?>"></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                    <ol class="axe-x" aria-hidden="true">
                        <?php foreach ($activite as $semaine): ?>
                            <li><?= e($semaine['debut']->format('d/m')) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <details class="donnees">
                    <summary>Voir les données</summary>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th scope="col">Semaine du</th><th scope="col">Passages</th><th scope="col">Note moyenne</th></tr></thead>
                            <tbody>
                                <?php foreach ($activite as $semaine): ?>
                                    <tr>
                                        <td><?= e($semaine['debut']->format('d/m/Y')) ?></td>
                                        <td><?= (int) $semaine['passages'] ?></td>
                                        <td><?= $semaine['note'] !== null ? e(format_note($semaine['note'])) . ' / 20' : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endif; ?>
        </div>

        <div class="carte-graphique">
            <div class="carte-tete">
                <div>
                    <h3>Taux de bonnes réponses par QCM</h3>
                    <p class="note">Toutes questions confondues, depuis la création du QCM</p>
                </div>
            </div>
            <?php if (!$reussite): ?>
                <p class="empty">Les taux apparaîtront dès qu'un étudiant aura terminé un QCM.</p>
            <?php else: ?>
                <ul class="reussite">
                    <?php foreach ($reussite as $i => $ligne): ?>
                        <li>
                            <a href="<?= e(url('/qcm/' . $ligne['id'] . '/statistiques')) ?>" class="reussite-titre"><?= e($ligne['titre']) ?></a>
                            <span class="reussite-piste" aria-hidden="true"><span class="reussite-barre" id="reussite-<?= $i ?>"></span></span>
                            <span class="reussite-valeur"><?= (int) $ligne['taux'] ?> %</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
