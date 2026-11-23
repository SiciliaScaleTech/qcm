<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Mongo;
use MongoDB\BSON\UTCDateTime;
use Throwable;

final class StatsRepository
{
    public const STATS = 'stats_questions';
    public const JOURNAL = 'journal';

    public static function enregistrerPassage(int $qcmId, array $questions, array $choix): void
    {
        $db = Mongo::db();
        if ($db === null || !$questions) {
            return;
        }
        try {
            $db->selectCollection(self::STATS)->bulkWrite(self::operationsPassage($qcmId, $questions, $choix));
        } catch (Throwable $e) {
            Mongo::echec($e);
        }
    }

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

            $recalculs[] = ['updateOne' => [$filtre, [[
                '$set' => [
                    'taux_reussite' => ['$round' => [['$multiply' => [['$divide' => ['$justes', '$total']], 100]], 1]],
                    'mis_a_jour' => '$$NOW',
                ],
            ]]]];
        }

        return array_merge($increments, $recalculs);
    }

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
