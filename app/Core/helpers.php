<?php
declare(strict_types=1);

// Échappe une valeur avant de l'afficher (protection contre le XSS)
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Lit une valeur de config/config.php, avec une valeur par défaut
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $file = ROOT_PATH . '/config/config.php';
        $config = is_file($file) ? require $file : [];
    }
    return $config[$key] ?? $default;
}

// Chemin de base de l'application : /qcm/public avec XAMPP, vide à la racine d'un domaine
function base_url(): string
{
    return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
}

// Construit une URL de l'application : url('assets/css/style.css')
function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

// URL complète pour le lien donné aux étudiants.
// En production, app_url (config.php) évite de dépendre de l'en-tête Host.
function absolute_url(string $path): string
{
    $appUrl = trim((string) config('app_url', ''));
    if ($appUrl !== '') {
        return rtrim($appUrl, '/') . '/' . ltrim($path, '/');
    }
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

// Champ caché à placer dans chaque formulaire POST
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(\App\Core\Csrf::token()) . '">';
}

// Message affiché une seule fois à la page suivante
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

// plural(3, 'question') donne "3 questions"
function plural(int $n, string $word): string
{
    return $n . ' ' . $word . ($n > 1 ? 's' : '');
}

// Transforme un titre en morceau d'URL : "Les bases du HTML !" donne "les-bases-du-html"
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
    // Tout ce qui n'est ni lettre ni chiffre devient un tiret
    $texte = trim((string) preg_replace('/[^a-z0-9]+/', '-', $texte), '-');
    $texte = rtrim(substr($texte, 0, 150), '-');
    return $texte !== '' ? $texte : 'qcm';
}

// Année scolaire en cours : à partir d'août on passe à l'année suivante
function annee_scolaire_courante(): string
{
    $annee = (int) date('Y');
    $debut = (int) date('n') >= 8 ? $annee : $annee - 1;
    return $debut . '-' . ($debut + 1);
}

// Années proposées dans le formulaire : l'année précédente, l'actuelle et la suivante
function annees_scolaires_proposees(): array
{
    $debut = (int) substr(annee_scolaire_courante(), 0, 4);
    $annees = [];
    for ($a = $debut + 1; $a >= $debut - 1; $a--) {
        $annees[] = $a . '-' . ($a + 1);
    }
    return $annees;
}

// Affiche une note à la française : 14.5 donne "14,5" et 20.0 donne "20".
// Le calcul de la note est dans le composant métier App\Services\Scoring.
function format_note(float $note): string
{
    return str_replace('.', ',', (string) $note);
}
