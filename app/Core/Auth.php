<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    public const DUREE_INACTIVITE = 7200;

    public static function login(int $id, string $nom): void
    {
        session_regenerate_id(true);
        unset($_SESSION['csrf']);
        $_SESSION['formateur_id'] = $id;
        $_SESSION['formateur_nom'] = $nom;
        $_SESSION['derniere_activite'] = time();
    }

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
