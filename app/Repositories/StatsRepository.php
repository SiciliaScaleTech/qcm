<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Mongo;
use MongoDB\BSON\UTCDateTime;
use Throwable;

// Accès aux données NoSQL (MongoDB).
// - stats_questions : un document par question, mis à jour à chaque passage terminé
// - journal : événements au format libre (connexion, QCM créé, passage terminé)
// Chaque méthode ignore MongoDB s'il est indisponible : l'application continue.
final class StatsRepository
{
    public const STATS = 'stats_questions';
    public const JOURNAL = 'journal';

    // Met à jour les statistiques de chaque question après un passage.
    // $choix : ids des réponses retenues par Scoring::corriger()
    public static function enregistrerPassage(int $qcmId, array $questions, array $choix): void
    {
        $db = Mongo::db();
        if ($db === null || !$questions) {
            return;
        }
        try {
            // Toutes les mises à jour partent en un seul aller-retour vers le serveur
            $db->selectCollection(self::STATS)->bulkWrite(self::operationsPassage($qcmId, $questions, $choix));
        } catch (Throwable $e) {
            Mongo::echec($e);
        }
    }

    // Prépare les opérations sans les exécuter (testable sans serveur MongoDB).
    // Pour chaque question : $inc des compteurs avec upsert (le document est créé
    // au premier passage), puis recalcul du taux de réussite à partir des compteurs.
    public static function operationsPassage(int $qcmId, array $questions, array $choix): array
    {
        $choix = array_map('intval', $choix);
        $increments = [];
        $recalculs = [];

        foreach ($questions as $question) {
            $filtre = ['id_qcm' => $qcmId, 'id_question' => (int) $question['id_question']];

            $choisie = null;
            $juste = false;
            foreach ($question['reponses'] as $reponse) {
                if (in_array((int) $reponse['id_reponse'], $choix, true)) {
                    $choisie = (int) $reponse['id_reponse'];
                    $juste = (bool) $reponse['est_correct'];
                }
            }

            $inc = ['total' => 1, 'justes' => $juste ? 1 : 0];
            if ($choisie !== null) {
                $inc['options.' . $choisie] = 1;
            } else {
                $inc['sans_reponse'] = 1;
            }

            $increments[] = ['updateOne' => [$filtre, ['$inc' => $inc], ['upsert' => true]]];

            // Mise à jour en « pipeline » : le taux est calculé par MongoDB à partir des
            // compteurs déjà à jour, donc juste même si deux passages arrivent en même temps
            $recalculs[] = ['updateOne' => [$filtre, [[
                '$set' => [
                    'taux_reussite' => ['$round' => [['$multiply' => [['$divide' => ['$justes', '$total']], 100]], 1]],
                    'mis_a_jour' => '$$NOW',
                ],
            ]]]];
        }

        return array_merge($increments, $recalculs);
    }

    // Statistiques d'un QCM, indexées par id de question. null si MongoDB est indisponible.
    public static function pourQcm(int $qcmId): ?array
    {
        $db = Mongo::db();
        if ($db === null) {
            return null;
        }
        try {
            $stats = [];
            foreach ($db->selectCollection(self::STATS)->find(['id_qcm' => $qcmId]) as $document) {
                $stats[(int) $document['id_question']] = $document;
            }
            return $stats;
        } catch (Throwable $e) {
            Mongo::echec($e);
            return null;
        }
    }

    public static function supprimerQcm(int $qcmId): void
    {
        self::executer(fn($db) => $db->selectCollection(self::STATS)->deleteMany(['id_qcm' => $qcmId]));
    }

    public static function supprimerQuestion(int $questionId): void
    {
        self::executer(fn($db) => $db->selectCollection(self::STATS)->deleteMany(['id_question' => $questionId]));
    }

    // Ajoute un événement au journal. Le format est libre : $details varie selon le type.
    public static function journaliser(string $type, ?int $formateurId, array $details = []): void
    {
        self::executer(fn($db) => $db->selectCollection(self::JOURNAL)->insertOne([
            'type' => $type,
            'date' => new UTCDateTime(),
            'id_formateur' => $formateurId,
        ] + $details));
    }

    private static function executer(callable $action): void
    {
        $db = Mongo::db();
        if ($db === null) {
            return;
        }
        try {
            $action($db);
        } catch (Throwable $e) {
            Mongo::echec($e);
        }
    }
}
