<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Qcm
{
    public static function forFormateur(int $formateurId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT q.*, COUNT(qu.id_question) AS nb_questions
             FROM qcm q
             LEFT JOIN question qu ON qu.id_qcm = q.id_qcm
             WHERE q.id_formateur = :formateur
             GROUP BY q.id_qcm
             ORDER BY q.id_qcm DESC'
        );
        $stmt->execute(['formateur' => $formateurId]);
        return $stmt->fetchAll();
    }

    // Renvoie le QCM seulement s'il appartient à ce formateur
    public static function findOwned(int $id, int $formateurId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM qcm WHERE id_qcm = :id AND id_formateur = :formateur'
        );
        $stmt->execute(['id' => $id, 'formateur' => $formateurId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $formateurId, string $titre, ?string $description, string $jeton): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO qcm (titre, description, jeton_partage, id_formateur)
             VALUES (:titre, :description, :jeton, :formateur)'
        );
        $stmt->execute([
            'titre' => $titre,
            'description' => $description,
            'jeton' => $jeton,
            'formateur' => $formateurId,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $titre, ?string $description, bool $actif): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE qcm SET titre = :titre, description = :description, actif = :actif WHERE id_qcm = :id'
        );
        $stmt->execute([
            'titre' => $titre,
            'description' => $description,
            'actif' => $actif ? 1 : 0,
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM qcm WHERE id_qcm = :id');
        $stmt->execute(['id' => $id]);
    }
}
