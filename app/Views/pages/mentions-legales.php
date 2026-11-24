<div class="page-texte">
<h1>Mentions légales</h1>

<h2>Éditeur du site</h2>
<p>
    Le site <?= e(app_name()) ?> est édité par <?= info_legale($legal, 'editeur') ?>,
    <?= info_legale($legal, 'statut') ?>, qui exerce son activité sous le nom <?= info_legale($legal, 'nom_commercial') ?>.
</p>
<ul>
    <li>Établissement : <?= info_legale($legal, 'etablissement') ?></li>
    <li>SIREN : <?= info_legale($legal, 'siren') ?></li>
    <li>SIRET : <?= info_legale($legal, 'siret') ?></li>
    <li>Code APE : <?= info_legale($legal, 'ape') ?></li>
    <li><?= info_legale($legal, 'tva') ?></li>
    <?php if (($legal['telephone'] ?? '') !== ''): ?>
        <li>Téléphone : <?= info_legale($legal, 'telephone') ?></li>
    <?php endif; ?>
    <li>Email : <?= info_legale($legal, 'email') ?></li>
</ul>
<p>Responsable de la publication : <?= info_legale($legal, 'directeur_publication') ?></p>

<h2>Hébergement</h2>
<p>
    <?= info_legale($legal, 'hebergeur') ?><br>
    <?= info_legale($legal, 'hebergeur_adresse') ?><br>
    <?= info_legale($legal, 'hebergeur_contact') ?>
</p>
<p>
    Les statistiques anonymes et le journal technique sont stockés par
    <?= info_legale($legal, 'mongodb') ?>, région <?= info_legale($legal, 'mongodb_region') ?>.
</p>

<h2>Propriété intellectuelle</h2>
<p>
    La marque <?= e(app_name()) ?>, son logo, le code, la mise en page et les textes du site appartiennent à
    <?= info_legale($legal, 'nom_commercial') ?>. Les questions des QCM appartiennent aux intervenants qui les ont rédigées.
    Toute reproduction ou réutilisation sans autorisation écrite est interdite.
</p>

<h2>Responsabilité</h2>
<p>
    L'éditeur s'efforce d'assurer l'exactitude et la disponibilité du site, sans pouvoir garantir l'absence
    d'interruption ou d'erreur. Les résultats affichés aux étudiants sont calculés automatiquement à partir
    des réponses saisies par les intervenants.
</p>

<h2>Données personnelles et cookies</h2>
<p>
    Les données collectées, leur durée de conservation et vos droits sont détaillés dans la
    <a href="<?= e(url('/confidentialite')) ?>">politique de confidentialité</a>.
</p>

<h2>Droit applicable</h2>
<p>Les présentes mentions sont soumises au droit français.</p>
</div>
