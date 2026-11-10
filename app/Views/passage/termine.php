<div class="auth-card">
<h1><?= e($qcm['titre']) ?></h1>
<p>Vous avez déjà terminé ce QCM. Il ne peut être passé qu'une seule fois.</p>
<?php if ($aUnResultat): ?>
    <p><a class="btn btn-link" href="<?= e(url('/q/' . $qcm['slug'] . '/resultat')) ?>">Revoir mon résultat</a></p>
<?php else: ?>
    <p>Contactez votre formateur en cas d'erreur.</p>
<?php endif; ?>
</div>
