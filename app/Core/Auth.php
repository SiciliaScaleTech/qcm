<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Intervenant;

class Auth
{
    public const DUREE_INACTIVITE = 7200;

    public static function login(int $id, string $nom, string $hashMotDePasse): void
    {
        session_regenerate_id(true);
        unset($_SESSION['csrf']);
        $_SESSION['intervenant_id'] = $id;
        $_SESSION['intervenant_nom'] = $nom;
        $_SESSION['derniere_activite'] = time();
        self::memoriserMotDePasse($hashMotDePasse);
    }

    public static function memoriserMotDePasse(string $hashMotDePasse): void
    {
        $_SESSION['empreinte_mdp'] = hash('sha256', $hashMotDePasse);
    }

    // Une session ouverte avant un changement de mot de passe est fermée
    public static function verifierMotDePasse(): void
    {
        if (!self::check()) {
            return;
        }
        $intervenant = Intervenant::findById((int) self::id());
        if ($intervenant && hash_equals($_SESSION['empreinte_mdp'] ?? '', hash('sha256', $intervenant['mot_de_passe']))) {
            return;
        }
        self::logout();
        flash('Votre session a expiré. Reconnectez-vous.', 'error');
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
        return isset($_SESSION['intervenant_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['intervenant_id'] ?? null;
    }

    public static function nom(): string
    {
        return $_SESSION['intervenant_nom'] ?? '';
    }

    public static function renommer(string $nom): void
    {
        $_SESSION['intervenant_nom'] = $nom;
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: ' . url('/connexion'));
            exit;
        }
    }
}
