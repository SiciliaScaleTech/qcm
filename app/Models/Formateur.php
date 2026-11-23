<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Formateur
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM formateur WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public const MAX_ECHECS = 5;
    public const MINUTES_BLOCAGE = 15;

    public static function enregistrerEchec(array $formateur): void
    {
        $echecs = (int) $formateur['echecs_connexion'] + 1;
        $bloqueJusqua = null;
        if ($echecs >= self::MAX_ECHECS) {
            $echecs = 0;
            $bloqueJusqua = date('Y-m-d H:i:s', time() + self::MINUTES_BLOCAGE * 60);
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE formateur SET echecs_connexion = :echecs, bloque_jusqu_a = :bloque WHERE id_formateur = :id'
        );
        $stmt->execute([
            'echecs' => $echecs,
            'bloque' => $bloqueJusqua,
            'id' => $formateur['id_formateur'],
        ]);
    }

    public static function reinitialiserEchecs(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE formateur SET echecs_connexion = 0, bloque_jusqu_a = NULL WHERE id_formateur = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function estBloque(array $formateur): bool
    {
        return $formateur['bloque_jusqu_a'] !== null && strtotime($formateur['bloque_jusqu_a']) > time();
    }

    public static function create(string $nom, string $email, string $motDePasseHash): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO formateur (nom, email, mot_de_passe) VALUES (:nom, :email, :hash)'
        );
        $stmt->execute(['nom' => $nom, 'email' => $email, 'hash' => $motDePasseHash]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function changerMotDePasse(int $id, string $hash): void
    {
        $stmt = Database::pdo()->prepare('UPDATE formateur SET mot_de_passe = :hash WHERE id_formateur = :id');
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }
}
