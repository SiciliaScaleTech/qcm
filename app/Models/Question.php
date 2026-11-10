<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

class Question
{
    // Questions d'un QCM, chacune avec sa liste de réponses
    public static function forQcm(int $qcmId): array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT * FROM question WHERE id_qcm = :id ORDER BY ordre, id_question');
        $stmt->execute(['id' => $qcmId]);
        $questions = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT r.* FROM reponse r
             JOIN question q ON q.id_question = r.id_question
             WHERE q.id_qcm = :id
             ORDER BY r.id_reponse'
        );
        $stmt->execute(['id' => $qcmId]);
        $reponses = [];
        foreach ($stmt->fetchAll() as $reponse) {
            $reponses[$reponse['id_question']][] = $reponse;
        }

        foreach ($questions as &$question) {
            $question['reponses'] = $reponses[$question['id_question']] ?? [];
        }
        return $questions;
    }

    // Renvoie la question seulement si son QCM appartient à ce formateur
    public static function findOwned(int $id, int $formateurId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT q.* FROM question q
             JOIN qcm ON qcm.id_qcm = q.id_qcm
             WHERE q.id_question = :id AND qcm.id_formateur = :formateur'
        );
        $stmt->execute(['id' => $id, 'formateur' => $formateurId]);
        $question = $stmt->fetch();
        if (!$question) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT * FROM reponse WHERE id_question = :id ORDER BY id_reponse');
        $stmt->execute(['id' => $id]);
        $question['reponses'] = $stmt->fetchAll();
        return $question;
    }

    // Crée la question et ses 4 réponses en une seule transaction
    public static function create(int $qcmId, string $enonce, array $textes, int $correcte): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordre), 0) + 1 FROM question WHERE id_qcm = :id');
            $stmt->execute(['id' => $qcmId]);
            $ordre = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'INSERT INTO question (enonce, ordre, id_qcm) VALUES (:enonce, :ordre, :qcm)'
            );
            $stmt->execute(['enonce' => $enonce, 'ordre' => $ordre, 'qcm' => $qcmId]);
            $questionId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO reponse (texte, est_correct, id_question) VALUES (:texte, :correct, :question)'
            );
            foreach ($textes as $i => $texte) {
                $stmt->execute([
                    'texte' => $texte,
                    'correct' => $i === $correcte ? 1 : 0,
                    'question' => $questionId,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Met à jour les 4 réponses existantes sur place, pour ne pas perdre l'historique des étudiants
    public static function update(int $id, string $enonce, array $textes, int $correcte): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE question SET enonce = :enonce WHERE id_question = :id');
            $stmt->execute(['enonce' => $enonce, 'id' => $id]);

            $stmt = $pdo->prepare('SELECT id_reponse FROM reponse WHERE id_question = :id ORDER BY id_reponse');
            $stmt->execute(['id' => $id]);
            $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $stmt = $pdo->prepare(
                'UPDATE reponse SET texte = :texte, est_correct = :correct WHERE id_reponse = :id'
            );
            foreach ($ids as $i => $reponseId) {
                $stmt->execute([
                    'texte' => $textes[$i],
                    'correct' => $i === $correcte ? 1 : 0,
                    'id' => $reponseId,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM question WHERE id_question = :id');
        $stmt->execute(['id' => $id]);
    }
}
