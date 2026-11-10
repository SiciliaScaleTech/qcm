<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

// Classes d'étudiants d'un formateur, reliées aux QCM par la table "concerner"
class Classe
{
    // Toutes les classes du formateur, avec le nombre de QCM de chacune
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

    // Classes reliées à un QCM
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

    // Renvoie l'id de la classe portant ce nom, en la créant si elle n'existe pas encore
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

    // Remplace les classes d'un QCM par celles données.
    // $ids : classes existantes cochées, $nouvelles : noms des classes à créer.
    public static function syncForQcm(int $formateurId, int $qcmId, array $ids, array $nouvelles): void
    {
        $pdo = Database::pdo();

        // On ne garde que les classes qui appartiennent bien à ce formateur
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

    // Supprime les classes qui ne sont plus reliées à aucun QCM
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
