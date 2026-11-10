<?php $flashes = pull_flash(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f2540">
    <title><?= e($title) ?> - Horizon QCM</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='3' fill='%230f2540'/%3E%3Ccircle cx='16' cy='20' r='7' fill='%23f28c28'/%3E%3Crect y='20' width='32' height='12' fill='%230f2540'/%3E%3Crect y='20' width='32' height='2' fill='%23fff'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
    <a class="skip-link" href="#contenu">Aller au contenu</a>

    <header class="site-header">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="Horizon QCM, accueil">
            <svg viewBox="0 0 40 40" aria-hidden="true">
                <rect width="40" height="40" rx="4" fill="#17375e"/>
                <path d="M8 25a12 12 0 0 1 24 0z" fill="#f28c28"/>
                <path d="M20 6v4M9 11l3 3M31 11l-3 3" stroke="#f28c28" stroke-width="2" stroke-linecap="round"/>
                <path d="M6 28h28M11 33h18" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span><span class="brand-name">Horizon</span><span class="brand-sub">QCM</span></span>
        </a>
        <?php if (empty($hideNav)): ?>
        <nav aria-label="Navigation principale">
            <?php if (\App\Core\Auth::check()): ?>
                <a href="<?= e(url('/')) ?>">Mes QCM</a>
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
        <p>Horizon, centre de formation. Plateforme de QCM, projet du titre Développeur Web et Web Mobile.</p>
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
