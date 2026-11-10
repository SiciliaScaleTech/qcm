<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function login(int $id, string $nom): void
    {
        session_regenerate_id(true);
        $_SESSION['formateur_id'] = $id;
        $_SESSION['formateur_nom'] = $nom;
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
