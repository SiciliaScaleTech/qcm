<div class="page-texte">
<h1>Mentions légales</h1>

<h2>Éditeur du site</h2>
<p>
    <?= info_legale($legal, 'editeur') ?><br>
    <?= info_legale($legal, 'adresse') ?><br>
    Contact : <?= info_legale($legal, 'email') ?>
</p>
<p>Directeur ou directrice de la publication : <?= info_legale($legal, 'directeur_publication') ?></p>
<p>Ce site est réalisé dans le cadre du titre professionnel Développeur Web et Web Mobile (niveau 6).</p>

<h2>Hébergement</h2>
<p>
    <?= info_legale($legal, 'hebergeur') ?><br>
    <?= info_legale($legal, 'hebergeur_adresse') ?><br>
    Téléphone : <?= info_legale($legal, 'hebergeur_telephone') ?>
</p>

<h2>Propriété intellectuelle</h2>
<p>
    Le code, la mise en page et les textes du site appartiennent à leur auteur.
    Les questions des QCM appartiennent aux formateurs qui les ont rédigées.
    Toute reproduction sans autorisation est interdite.
</p>

<h2>Données personnelles et cookies</h2>
<p>
    Les informations sur les données collectées, leur durée de conservation et vos droits
    sont détaillées dans la <a href="<?= e(url('/confidentialite')) ?>">politique de confidentialité</a>.
</p>
</div>
