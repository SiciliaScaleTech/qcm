<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

class Tentative
{
    public static function create(int $qcmId, string $nom, string $prenom, int $score, int $nbQuestions, array $reponseIds): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO tentative (nom_etudiant, prenom_etudiant, score, nb_questions, id_qcm)
                 VALUES (:nom, :prenom, :score, :nb, :qcm)'
            );
            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'score' => $score,
                'nb' => $nbQuestions,
                'qcm' => $qcmId,
            ]);
            $tentativeId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO choisir (id_tentative, id_reponse) VALUES (:tentative, :reponse)');
            foreach ($reponseIds as $reponseId) {
                $stmt->execute(['tentative' => $tentativeId, 'reponse' => $reponseId]);
            }
            $pdo->commit();
            return $tentativeId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function trouver(int $qcmId, string $nom, string $prenom): ?int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id_tentative FROM tentative
             WHERE id_qcm = :qcm AND nom_etudiant = :nom AND prenom_etudiant = :prenom'
        );
        $stmt->execute(['qcm' => $qcmId, 'nom' => $nom, 'prenom' => $prenom]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    public static function findOwned(int $id, int $formateurId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.* FROM tentative t
             JOIN qcm q ON q.id_qcm = t.id_qcm
             WHERE t.id_tentative = :id AND q.id_formateur = :formateur'
        );
        $stmt->execute(['id' => $id, 'formateur' => $formateurId]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM tentative WHERE id_tentative = :id');
        $stmt->execute(['id' => $id]);
    }

    public const DUREE_CONSERVATION_MOIS = 12;

    public static function purgerAnciennes(): int
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM tentative WHERE date_passage < NOW() - INTERVAL ' . self::DUREE_CONSERVATION_MOIS . ' MONTH'
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function forQcm(int $qcmId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM tentative WHERE id_qcm = :qcm ORDER BY date_passage DESC, id_tentative DESC'
        );
        $stmt->execute(['qcm' => $qcmId]);
        return $stmt->fetchAll();
    }

    public static function findForQcm(int $id, int $qcmId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM tentative WHERE id_tentative = :id AND id_qcm = :qcm'
        );
        $stmt->execute(['id' => $id, 'qcm' => $qcmId]);
        return $stmt->fetch() ?: null;
    }

    public static function choixIds(int $tentativeId): array
    {
        $stmt = Database::pdo()->prepare('SELECT id_reponse FROM choisir WHERE id_tentative = :id');
        $stmt->execute(['id' => $tentativeId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
