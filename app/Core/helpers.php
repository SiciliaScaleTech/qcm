<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $file = ROOT_PATH . '/config/config.php';
        $config = is_file($file) ? require $file : [];
    }
    return $config[$key] ?? $default;
}

function base_url(): string
{
    return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function absolute_url(string $path): string
{
    $appUrl = trim((string) config('app_url', ''));
    if ($appUrl !== '') {
        return rtrim($appUrl, '/') . '/' . ltrim($path, '/');
    }
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(\App\Core\Csrf::token()) . '">';
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function pull_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function plural(int $n, string $word): string
{
    return $n . ' ' . $word . ($n > 1 ? 's' : '');
}

function slugify(string $texte): string
{
    $accents = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
        'ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae',
    ];
    $texte = strtr(mb_strtolower($texte), $accents);

    $texte = trim((string) preg_replace('/[^a-z0-9]+/', '-', $texte), '-');
    $texte = rtrim(substr($texte, 0, 150), '-');
    return $texte !== '' ? $texte : 'qcm';
}

function annee_scolaire_courante(): string
{
    $annee = (int) date('Y');
    $debut = (int) date('n') >= 8 ? $annee : $annee - 1;
    return $debut . '-' . ($debut + 1);
}

function annees_scolaires_proposees(): array
{
    $debut = (int) substr(annee_scolaire_courante(), 0, 4);
    $annees = [];
    for ($a = $debut + 1; $a >= $debut - 1; $a--) {
        $annees[] = $a . '-' . ($a + 1);
    }
    return $annees;
}

function format_note(float $note): string
{
    return str_replace('.', ',', (string) $note);
}

function info_legale(array $legal, string $cle): string
{
    $valeur = $legal[$cle] ?? '';
    return $valeur !== '' ? e($valeur) : '<span class="a-completer">[à compléter]</span>';
}

function app_name(): string
{
    return (string) config('app_name', 'Z&L Nexus Academy');
}

function logo_url(): ?string
{
    foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
        if (is_file(ROOT_PATH . '/public/assets/img/logo.' . $ext)) {
            return url('assets/img/logo.' . $ext);
        }
    }
    return null;
}
