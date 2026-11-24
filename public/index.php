<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    if (is_file($file)) {
        return false;
    }
}

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/app/Core/helpers.php';

if (is_file(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

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

ini_set('display_errors', config('debug', false) ? '1' : '0');
ini_set('log_errors', '1');

header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; object-src 'none'; "
    . "base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

header('Cache-Control: no-store');
if (est_https()) {
    header('Strict-Transport-Security: max-age=31536000');
}

ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', (string) App\Core\Auth::DUREE_INACTIVITE);
session_name('qcm_session');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => est_https(),
]);
session_start();
App\Core\Auth::verifierInactivite();

$router = new App\Core\Router();
require ROOT_PATH . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
