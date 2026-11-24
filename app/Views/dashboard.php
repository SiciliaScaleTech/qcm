<?php
$totalQuestions = array_sum(array_column($qcms, 'nb_questions'));
$totalPassages = array_sum(array_column($qcms, 'nb_tentatives'));

$lienOnglet = function (?int $categorie, ?string $annee, ?int $classe): string {
    $params = array_filter(['categorie' => $categorie, 'annee' => $annee, 'classe' => $classe]);
    return url('/') . ($params ? '?' . http_build_query($params) : '');
};
$lienNouveau = url('/qcm/nouveau') . ($categorieActive ? '?categorie=' . $categorieActive : '');
?>
<div class="page-head">
    <div>
        <h1>Bonjour <?= e($nom) ?></h1>
        <p>Voici vos QCM.</p>
    </div>
    <a class="btn btn-link" href="<?= e($lienNouveau) ?>">Nouveau QCM</a>
</div>

<div class="stats">
    <div class="stat"><strong><?= count($qcms) ?></strong><span>QCM</span></div>
    <div class="stat"><strong><?= (int) $totalQuestions ?></strong><span>Questions</span></div>
    <div class="stat"><strong><?= (int) $totalPassages ?></strong><span>Passages d'étudiants</span></div>
</div>

<?php if ($aucunQcm): ?>
    <div class="grid">
        <a class="qcm-new" href="<?= e(url('/qcm/nouveau')) ?>">
            <span><span class="plus" aria-hidden="true">+</span>Créer mon premier QCM</span>
        </a>
    </div>
<?php else: ?>
    <section class="filtres" aria-label="Filtrer mes QCM">
        <div class="filtres-haut">
            <div class="recherche">
                <label for="recherche" class="visually-hidden">Rechercher un QCM</label>
                <input type="search" id="recherche" placeholder="Rechercher un QCM" data-search>
            </div>
            <div class="segment" role="group" aria-label="Filtrer par statut">
                <button type="button" data-filter="tous" aria-pressed="true">Tous</button>
                <button type="button" data-filter="actif" aria-pressed="false">Actifs</button>
                <button type="button" data-filter="inactif" aria-pressed="false">Désactivés</button>
            </div>
        </div>

        <div class="filtres-groupes">
            <nav class="filtre" aria-label="Filtrer par catégorie">
                <span class="filtre-titre">Catégorie</span>
                <div class="pilules">
                    <a class="pilule" href="<?= e($lienOnglet(null, null, $classeActive)) ?>" <?= $categorieActive === null ? 'aria-current="page"' : '' ?>>Toutes</a>
                    <?php foreach ($categories as $categorie): ?>
                        <a class="pilule" href="<?= e($lienOnglet((int) $categorie['id_categorie'], null, $classeActive)) ?>" <?= $categorieActive === (int) $categorie['id_categorie'] ? 'aria-current="page"' : '' ?>>
                            <?= e($categorie['nom']) ?> <span class="pilule-nb"><?= (int) $categorie['nb_qcm'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>

            <?php if ($annees): ?>
                <nav class="filtre" aria-label="Filtrer par année scolaire">
                    <span class="filtre-titre">Année</span>
                    <div class="pilules">
                        <a class="pilule" href="<?= e($lienOnglet($categorieActive, null, $classeActive)) ?>" <?= $anneeActive === null ? 'aria-current="page"' : '' ?>>Toutes</a>
                        <?php foreach ($annees as $annee): ?>
                            <a class="pilule" href="<?= e($lienOnglet($categorieActive, $annee, $classeActive)) ?>" <?= $anneeActive === $annee ? 'aria-current="page"' : '' ?>><?= e($annee) ?></a>
                        <?php endforeach; ?>
                    </div>
                </nav>
            <?php endif; ?>

            <?php if ($classes): ?>
                <nav class="filtre" aria-label="Filtrer par classe">
                    <span class="filtre-titre">Classe</span>
                    <div class="pilules">
                        <a class="pilule" href="<?= e($lienOnglet($categorieActive, $anneeActive, null)) ?>" <?= $classeActive === null ? 'aria-current="page"' : '' ?>>Toutes</a>
                        <?php foreach ($classes as $classe): ?>
                            <a class="pilule" href="<?= e($lienOnglet($categorieActive, $anneeActive, (int) $classe['id_classe'])) ?>" <?= $classeActive === (int) $classe['id_classe'] ? 'aria-current="page"' : '' ?>>
                                <?= e($classe['nom']) ?> <span class="pilule-nb"><?= (int) $classe['nb_qcm'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </nav>
            <?php endif; ?>
        </div>

        <div class="filtres-bas">
            <p class="filtres-total" aria-live="polite"><span data-count><?= plural(count($qcms), 'QCM affiché') ?></span></p>
            <?php if ($categorieActive !== null || $anneeActive !== null || $classeActive !== null): ?>
                <a class="filtres-raz" href="<?= e(url('/')) ?>">Réinitialiser les filtres</a>
            <?php endif; ?>
        </div>
    </section>

    <div class="grid" data-cards>
        <?php foreach ($qcms as $qcm): ?>
            <article class="qcm-card <?= $qcm['actif'] ? '' : 'inactif' ?>"
                     data-titre="<?= e(mb_strtolower($qcm['titre'])) ?>"
                     data-statut="<?= $qcm['actif'] ? 'actif' : 'inactif' ?>">
                <h2><a href="<?= e(url('/qcm/' . $qcm['id_qcm'])) ?>"><?= e($qcm['titre']) ?></a></h2>
                <?php if ($qcm['description']): ?>
                    <p class="desc"><?= e(mb_strimwidth($qcm['description'], 0, 110, '…')) ?></p>
                <?php else: ?>
                    <p class="desc">Aucune description.</p>
                <?php endif; ?>
                <div class="qcm-meta">
                    <span class="badge badge-categorie"><?= e($qcm['categorie']) ?></span>
                    <span class="badge badge-annee"><?= e(date_fr($qcm['date_evaluation'])) ?></span>
                    <?php foreach ($qcm['liens'] as $lien): ?>
                        <span class="badge badge-classe"><?= e($lien['classe']) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="qcm-meta">
                    <?php if ($qcm['actif']): ?>
                        <span class="badge badge-ok">Actif</span>
                    <?php else: ?>
                        <span class="badge">Désactivé</span>
                    <?php endif; ?>
                    <span><?= plural((int) $qcm['nb_questions'], 'question') ?></span>
                    <span><?= plural((int) $qcm['nb_tentatives'], 'passage') ?></span>
                </div>
                <div class="actions">
                    <a class="btn btn-secondary btn-small btn-link" href="<?= e(url('/qcm/' . $qcm['id_qcm'])) ?>">Ouvrir</a>
                    <?php foreach ($qcm['liens'] as $lien): ?>
                        <button type="button" class="btn btn-small" data-copy="<?= e($lien['url']) ?>">Copier le lien <?= e($lien['classe']) ?></button>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <a class="qcm-new" href="<?= e($lienNouveau) ?>" data-keep>
            <span><span class="plus" aria-hidden="true">+</span>Nouveau QCM</span>
        </a>
    </div>
    <?php if (!$qcms): ?>
        <p class="empty">Aucun QCM pour ce filtre.</p>
    <?php endif; ?>
    <p class="empty" data-empty hidden>Aucun QCM ne correspond à votre recherche.</p>
<?php endif; ?>
