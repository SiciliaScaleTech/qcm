<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - Plateforme de QCM</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
    <a class="skip-link" href="#contenu">Aller au contenu</a>

    <header class="site-header">
        <a class="logo" href="<?= e(url('/')) ?>">Plateforme de QCM</a>
        <nav aria-label="Navigation principale">
            <a href="<?= e(url('/')) ?>">Accueil</a>
            <?php if (\App\Core\Auth::check()): ?>
                <a href="<?= e(url('/tableau-de-bord')) ?>">Tableau de bord</a>
                <form method="post" action="<?= e(url('/deconnexion')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-button">Déconnexion</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('/connexion')) ?>">Connexion</a>
                <a href="<?= e(url('/inscription')) ?>">Inscription</a>
            <?php endif; ?>
        </nav>
    </header>

    <main id="contenu">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <p>Projet de formation - Titre RNCP Développeur Web et Web Mobile</p>
    </footer>

    <script src="<?= e(url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
