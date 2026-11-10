<?php
declare(strict_types=1);

// Échappe une valeur avant de l'afficher (protection contre le XSS)
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Chemin de base de l'application : /qcm/public avec XAMPP, vide à la racine d'un domaine
function base_url(): string
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $dir;
}

// Construit une URL de l'application : url('assets/css/style.css')
function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

// URL complète (avec http://domaine), pour le lien à donner aux étudiants
function absolute_url(string $path): string
{
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

// Champ caché à placer dans chaque formulaire POST
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(\App\Core\Csrf::token()) . '">';
}
