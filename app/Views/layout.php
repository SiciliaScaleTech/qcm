<?php
$flashes = pull_flash();
$logo = logo_url();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6a11cb">
    <title><?= e($title) ?> - <?= e(app_name()) ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%236a11cb'/%3E%3Cstop offset='1' stop-color='%232575fc'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='6' fill='url(%23g)'/%3E%3Ctext x='16' y='22' font-family='Georgia,serif' font-size='15' font-weight='700' fill='%23fff' text-anchor='middle'%3EZ%26amp;L%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <script src="<?= e(url('assets/js/init.js')) ?>"></script>
</head>
<body>
    <a class="skip-link" href="#contenu">Aller au contenu</a>

    <header class="site-header">
        <a class="brand" href="<?= e(url('/')) ?>">
            <?php if ($logo): ?>
                <img class="brand-logo" src="<?= e($logo) ?>" alt="" width="1161" height="655">
                <span class="brand-name"><?= e(app_name()) ?></span>
            <?php else: ?>
                <span class="brand-text"><span class="brand-mark">Z&amp;L</span><span class="brand-sub">Nexus Academy</span></span>
            <?php endif; ?>
        </a>
        <?php if (empty($hideNav)): ?>
        <button type="button" class="menu-bouton" aria-expanded="false" aria-controls="menu-principal" data-menu>
            <span class="menu-icone" aria-hidden="true"><span></span><span></span><span></span></span>
            <span class="visually-hidden">Menu</span>
        </button>
        <nav class="site-nav" id="menu-principal" aria-label="Navigation principale">
            <?php if (\App\Core\Auth::check()): ?>
                <a href="<?= e(url('/')) ?>">Mes QCM</a>
                <a href="<?= e(url('/profil')) ?>">Mon profil</a>
                <a href="<?= e(url('/qcm/nouveau')) ?>" class="nav-cta">Nouveau QCM</a>
                <form method="post" action="<?= e(url('/deconnexion')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-button">Déconnexion</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('/connexion')) ?>">Connexion</a>
                <a href="<?= e(url('/inscription')) ?>" class="nav-cta">Inscription</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </header>

    <div id="toasts" role="status" aria-live="polite">
        <?php foreach ($flashes as $f): ?>
            <p class="toast toast-<?= e($f['type']) ?>"><?= e($f['message']) ?></p>
        <?php endforeach; ?>
    </div>

    <main id="contenu"<?= !empty($fullWidth) ? ' class="full"' : '' ?>>
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> <?= e(app_name()) ?></p>
        <p class="footer-links">
            <a href="<?= e(url('/mentions-legales')) ?>">Mentions légales</a>
            <a href="<?= e(url('/confidentialite')) ?>">Confidentialité</a>
        </p>
    </footer>

    <dialog id="confirm-dialog" class="modal" aria-labelledby="confirm-title">
        <form method="dialog">
            <div class="modal-icon" aria-hidden="true">!</div>
            <h2 id="confirm-title">Confirmer</h2>
            <p id="confirm-text"></p>
            <div class="actions">
                <button type="submit" value="cancel" class="btn btn-secondary">Annuler</button>
                <button type="submit" value="ok" id="confirm-ok" class="btn btn-danger">Supprimer</button>
            </div>
        </form>
    </dialog>

    <script src="<?= e(url('assets/js/app.js')) ?>" defer></script>
    <?php foreach ($scripts ?? [] as $script): ?>
        <script src="<?= e(url($script)) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
