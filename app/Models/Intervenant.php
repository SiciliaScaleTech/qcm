<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Intervenant
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM intervenant WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM intervenant WHERE id_intervenant = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function modifierProfil(int $id, string $nom, string $email): void
    {
        $stmt = Database::pdo()->prepare('UPDATE intervenant SET nom = :nom, email = :email WHERE id_intervenant = :id');
        $stmt->execute(['nom' => $nom, 'email' => $email, 'id' => $id]);
    }

    public const MAX_ECHECS = 5;
    public const MINUTES_BLOCAGE = 15;

    public static function enregistrerEchec(array $intervenant): void
    {
        $echecs = (int) $intervenant['echecs_connexion'] + 1;
        $bloqueJusqua = null;
        if ($echecs >= self::MAX_ECHECS) {
            $echecs = 0;
            $bloqueJusqua = date('Y-m-d H:i:s', time() + self::MINUTES_BLOCAGE * 60);
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE intervenant SET echecs_connexion = :echecs, bloque_jusqu_a = :bloque WHERE id_intervenant = :id'
        );
        $stmt->execute([
            'echecs' => $echecs,
            'bloque' => $bloqueJusqua,
            'id' => $intervenant['id_intervenant'],
        ]);
    }

    public static function reinitialiserEchecs(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE intervenant SET echecs_connexion = 0, bloque_jusqu_a = NULL WHERE id_intervenant = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function estBloque(array $intervenant): bool
    {
        return $intervenant['bloque_jusqu_a'] !== null && strtotime($intervenant['bloque_jusqu_a']) > time();
    }

    public static function create(string $nom, string $email, string $motDePasseHash): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO intervenant (nom, email, mot_de_passe) VALUES (:nom, :email, :hash)'
        );
        $stmt->execute(['nom' => $nom, 'email' => $email, 'hash' => $motDePasseHash]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function changerMotDePasse(int $id, string $hash): void
    {
        $stmt = Database::pdo()->prepare('UPDATE intervenant SET mot_de_passe = :hash WHERE id_intervenant = :id');
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }

    public const MINUTES_VALIDITE_JETON = 60;
    public const MINUTES_ENTRE_DEMANDES = 5;

    public static function demandeRecente(array $intervenant): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT jeton_expire_le > NOW() + INTERVAL :reste MINUTE FROM intervenant WHERE id_intervenant = :id'
        );
        $stmt->execute([
            'reste' => self::MINUTES_VALIDITE_JETON - self::MINUTES_ENTRE_DEMANDES,
            'id' => $intervenant['id_intervenant'],
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public static function enregistrerJeton(int $id, string $empreinte): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE intervenant
             SET jeton_reinitialisation = :empreinte, jeton_expire_le = NOW() + INTERVAL :minutes MINUTE
             WHERE id_intervenant = :id'
        );
        $stmt->execute(['empreinte' => $empreinte, 'minutes' => self::MINUTES_VALIDITE_JETON, 'id' => $id]);
    }

    public static function findByJeton(string $empreinte): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM intervenant WHERE jeton_reinitialisation = :empreinte AND jeton_expire_le > NOW()'
        );
        $stmt->execute(['empreinte' => $empreinte]);
        return $stmt->fetch() ?: null;
    }

    public static function reinitialiserMotDePasse(int $id, string $hash): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE intervenant
             SET mot_de_passe = :hash, jeton_reinitialisation = NULL, jeton_expire_le = NULL,
                 echecs_connexion = 0, bloque_jusqu_a = NULL
             WHERE id_intervenant = :id'
        );
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }
}
