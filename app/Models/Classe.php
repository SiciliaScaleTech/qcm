<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Classe
{
    public static function forFormateur(int $formateurId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id_classe, c.nom,
                    (SELECT COUNT(*) FROM concerner co WHERE co.id_classe = c.id_classe) AS nb_qcm
             FROM classe c
             WHERE c.id_formateur = :formateur
             ORDER BY c.nom'
        );
        $stmt->execute(['formateur' => $formateurId]);
        return $stmt->fetchAll();
    }

    public static function forQcm(int $qcmId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id_classe, c.nom
             FROM classe c
             JOIN concerner co ON co.id_classe = c.id_classe
             WHERE co.id_qcm = :qcm
             ORDER BY c.nom'
        );
        $stmt->execute(['qcm' => $qcmId]);
        return $stmt->fetchAll();
    }

    public static function findOrCreate(int $formateurId, string $nom): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id_classe FROM classe WHERE id_formateur = :formateur AND nom = :nom');
        $stmt->execute(['formateur' => $formateurId, 'nom' => $nom]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $pdo->prepare('INSERT INTO classe (nom, id_formateur) VALUES (:nom, :formateur)');
        $stmt->execute(['nom' => $nom, 'formateur' => $formateurId]);
        return (int) $pdo->lastInsertId();
    }

    public static function syncForQcm(int $formateurId, int $qcmId, array $ids, array $nouvelles): void
    {
        $pdo = Database::pdo();

        $siennes = array_map('intval', array_column(self::forFormateur($formateurId), 'id_classe'));
        $ids = array_values(array_intersect($ids, $siennes));

        foreach ($nouvelles as $nom) {
            $ids[] = self::findOrCreate($formateurId, $nom);
        }

        $stmt = $pdo->prepare('DELETE FROM concerner WHERE id_qcm = :qcm');
        $stmt->execute(['qcm' => $qcmId]);

        $stmt = $pdo->prepare('INSERT INTO concerner (id_qcm, id_classe) VALUES (:qcm, :classe)');
        foreach (array_unique($ids) as $id) {
            $stmt->execute(['qcm' => $qcmId, 'classe' => $id]);
        }

        self::deleteUnused($formateurId);
    }

    public static function deleteUnused(int $formateurId): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM classe
             WHERE id_formateur = :formateur
               AND id_classe NOT IN (SELECT id_classe FROM concerner)'
        );
        $stmt->execute(['formateur' => $formateurId]);
    }
}
