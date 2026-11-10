<?php
declare(strict_types=1);

// Serveur PHP intégré (php -S) : on laisse servir les fichiers existants (css, js)
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    if (is_file($file)) {
        return false;
    }
}

// Point d'entrée unique de l'application (contrôleur frontal)
define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/app/Core/helpers.php';

// Chargement automatique des classes : App\Core\Router => app/Core/Router.php
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = ROOT_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

date_default_timezone_set('Europe/Paris');

// Erreurs : affichées seulement en développement ('debug' => true dans config.php).
// En ligne, elles vont dans le journal du serveur et ne montrent rien du code aux visiteurs.
ini_set('display_errors', config('debug', false) ? '1' : '0');
ini_set('log_errors', '1');

// En-têtes de sécurité envoyés avec chaque page
header_remove('X-Powered-By');                 // ne pas révéler la version de PHP
header('X-Content-Type-Options: nosniff');     // le navigateur respecte le type des fichiers
header('X-Frame-Options: DENY');               // le site ne peut pas être affiché dans une iframe (clickjacking)
header('Referrer-Policy: same-origin');        // l'adresse des pages n'est pas transmise aux autres sites
// CSP : seuls les scripts, styles et images du site lui-même sont autorisés.
// Si du code malveillant était quand même injecté dans une page, le navigateur refuserait de l'exécuter.
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; object-src 'none'; "
    . "base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

// Sessions : cookie inaccessible au JavaScript, envoyé en HTTPS quand il est actif
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

$router = new App\Core\Router();
require ROOT_PATH . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
