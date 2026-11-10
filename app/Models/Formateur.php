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

    public static function create(string $nom, string $email, string $motDePasseHash): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO formateur (nom, email, mot_de_passe) VALUES (:nom, :email, :hash)'
        );
        $stmt->execute(['nom' => $nom, 'email' => $email, 'hash' => $motDePasseHash]);
        return (int) Database::pdo()->lastInsertId();
    }
}
