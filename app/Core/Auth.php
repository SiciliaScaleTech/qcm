<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    // Formateur déconnecté après 2 heures sans activité
    public const DUREE_INACTIVITE = 7200;

    public static function login(int $id, string $nom): void
    {
        session_regenerate_id(true);
        $_SESSION['formateur_id'] = $id;
        $_SESSION['formateur_nom'] = $nom;
        $_SESSION['derniere_activite'] = time();
    }

    // Appelée à chaque page : déconnecte un formateur resté inactif trop longtemps
    // (protège un compte laissé ouvert sur un ordinateur partagé)
    public static function verifierInactivite(): void
    {
        if (!self::check()) {
            return;
        }
        if (time() - ($_SESSION['derniere_activite'] ?? 0) > self::DUREE_INACTIVITE) {
            self::logout();
            flash('Vous avez été déconnecté après 2 heures d\'inactivité.', 'error');
            return;
        }
        $_SESSION['derniere_activite'] = time();
    }

    // Vide la session mais la garde ouverte, pour pouvoir afficher un message
    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return isset($_SESSION['formateur_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['formateur_id'] ?? null;
    }

    public static function nom(): string
    {
        return $_SESSION['formateur_nom'] ?? '';
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: ' . url('/connexion'));
            exit;
        }
    }
}
