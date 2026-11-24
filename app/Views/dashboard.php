<?php
$totalQuestions = array_sum(array_column($qcms, 'nb_questions'));
$totalPassages = array_sum(array_column($qcms, 'nb_tentatives'));

$lienNouveau = url('/qcm/nouveau') . ($categorieActive ? '?categorie=' . $categorieActive : '');
?>
<div class="page-head">
    <div>
        <h1>Bonjour <?= e($nom) ?></h1>
        <p>Voici vos QCM.</p>
    </div>
</div>

<div class="stats">
    <div class="stat"><strong><?= count($qcms) ?></strong><span>QCM</span></div>
    <div class="stat"><strong><?= (int) $totalQuestions ?></strong><span>Questions</span></div>
    <div class="stat"><strong><?= (int) $totalPassages ?></strong><span>Passages d'étudiants</span></div>
</div>

<?php if (!$aucunQcm) {
    require __DIR__ . '/partials/activite.php';
} ?>

<?php if ($aucunQcm): ?>
    <div class="grid">
        <a class="qcm-new" href="<?= e(url('/qcm/nouveau')) ?>">
            <span><span class="plus" aria-hidden="true">+</span>Créer mon premier QCM</span>
        </a>
    </div>
<?php else: ?>
    <form class="filtres" method="get" action="<?= e(url('/')) ?>" aria-label="Filtrer mes QCM" data-filtres>
        <div class="filtres-ligne">
            <div class="recherche">
                <label for="recherche" class="visually-hidden">Rechercher un QCM</label>
                <input type="search" id="recherche" placeholder="Rechercher un QCM" data-search>
            </div>

            <label class="visually-hidden" for="filtre-categorie">Catégorie</label>
            <select id="filtre-categorie" name="categorie" class="liste <?= $categorieActive !== null ? 'liste-active' : '' ?>">
                <option value="">Toutes les catégories</option>
                <?php foreach ($categories as $categorie): ?>
                    <option value="<?= (int) $categorie['id_categorie'] ?>" <?= $categorieActive === (int) $categorie['id_categorie'] ? 'selected' : '' ?>>
                        <?= e($categorie['nom']) ?> (<?= (int) $categorie['nb_qcm'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($annees): ?>
                <label class="visually-hidden" for="filtre-annee">Année scolaire</label>
                <select id="filtre-annee" name="annee" class="liste <?= $anneeActive !== null ? 'liste-active' : '' ?>">
                    <option value="">Toutes les années</option>
                    <?php foreach ($annees as $annee): ?>
                        <option value="<?= e($annee) ?>" <?= $anneeActive === $annee ? 'selected' : '' ?>><?= e($annee) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <?php if ($classes): ?>
                <label class="visually-hidden" for="filtre-classe">Classe</label>
                <select id="filtre-classe" name="classe" class="liste <?= $classeActive !== null ? 'liste-active' : '' ?>">
                    <option value="">Toutes les classes</option>
                    <?php foreach ($classes as $classe): ?>
                        <option value="<?= (int) $classe['id_classe'] ?>" <?= $classeActive === (int) $classe['id_classe'] ? 'selected' : '' ?>>
                            <?= e($classe['nom']) ?> (<?= (int) $classe['nb_qcm'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <div class="segment" role="group" aria-label="Filtrer par statut">
                <button type="button" data-filter="tous" aria-pressed="true">Tous</button>
                <button type="button" data-filter="actif" aria-pressed="false">Actifs</button>
                <button type="button" data-filter="inactif" aria-pressed="false">Désactivés</button>
            </div>

            <button type="submit" class="btn btn-small filtres-valider">Filtrer</button>
        </div>

        <div class="filtres-bas">
            <p class="filtres-total" aria-live="polite"><span data-count><?= plural(count($qcms), 'QCM affiché') ?></span></p>
            <?php if ($categorieActive !== null || $anneeActive !== null || $classeActive !== null): ?>
                <a class="filtres-raz" href="<?= e(url('/')) ?>">Réinitialiser les filtres</a>
            <?php endif; ?>
        </div>
    </form>

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
