<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Mongo;
use DateTimeImmutable;
use DateTimeZone;
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

    public static function reconstruireQcm(int $qcmId, array $questions, array $choixParTentative): void
    {
        $documents = self::documentsQcm($qcmId, $questions, $choixParTentative);
        self::executer(function ($db) use ($qcmId, $documents): void {
            $collection = $db->selectCollection(self::STATS);
            $collection->deleteMany(['id_qcm' => $qcmId]);
            if ($documents) {
                $maintenant = new UTCDateTime();
                $collection->insertMany(array_map(fn(array $d): array => ['options' => (object) $d['options'], 'mis_a_jour' => $maintenant] + $d, $documents));
            }
        });
    }

    public static function documentsQcm(int $qcmId, array $questions, array $choixParTentative): array
    {
        $total = count($choixParTentative);
        if ($total === 0) {
            return [];
        }

        $documents = [];
        foreach ($questions as $question) {
            $document = ['id_qcm' => $qcmId, 'id_question' => (int) $question['id_question'], 'total' => $total, 'justes' => 0, 'sans_reponse' => 0, 'options' => []];
            foreach ($choixParTentative as $choix) {
                $choisie = null;
                foreach ($question['reponses'] as $reponse) {
                    if (in_array((int) $reponse['id_reponse'], $choix, true)) {
                        $choisie = $reponse;
                    }
                }
                if ($choisie === null) {
                    $document['sans_reponse']++;
                    continue;
                }
                $cle = (string) $choisie['id_reponse'];
                $document['options'][$cle] = ($document['options'][$cle] ?? 0) + 1;
                if ($choisie['est_correct']) {
                    $document['justes']++;
                }
            }
            $document['taux_reussite'] = round($document['justes'] * 100 / $total, 1);
            $documents[] = $document;
        }
        return $documents;
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

    public const SEMAINES_ACTIVITE = 12;

    public static function activiteHebdomadaire(int $intervenantId): ?array
    {
        $db = Mongo::db();
        if ($db === null) {
            return null;
        }

        $fuseau = new DateTimeZone('Europe/Paris');
        $lundi = new DateTimeImmutable('monday this week', $fuseau);
        $debut = $lundi->modify('-' . (self::SEMAINES_ACTIVITE - 1) . ' weeks');

        try {
            $resultats = $db->selectCollection(self::JOURNAL)->aggregate([
                ['$match' => [
                    'type' => 'passage_termine',
                    'id_intervenant' => $intervenantId,
                    'date' => ['$gte' => new UTCDateTime($debut)],
                    'nb_questions' => ['$gt' => 0],
                ]],
                ['$group' => [
                    '_id' => ['$dateTrunc' => ['date' => '$date', 'unit' => 'week', 'startOfWeek' => 'monday', 'timezone' => 'Europe/Paris']],
                    'passages' => ['$sum' => 1],
                    'note' => ['$avg' => ['$multiply' => [['$divide' => ['$score', '$nb_questions']], 20]]],
                ]],
            ]);
        } catch (Throwable $e) {
            Mongo::echec($e);
            return null;
        }

        $parSemaine = [];
        foreach ($resultats as $ligne) {
            $cle = $ligne['_id']->toDateTime()->setTimezone($fuseau)->format('Y-m-d');
            $parSemaine[$cle] = ['passages' => (int) $ligne['passages'], 'note' => round((float) $ligne['note'], 1)];
        }

        $semaines = [];
        for ($i = 0; $i < self::SEMAINES_ACTIVITE; $i++) {
            $semaine = $debut->modify("+$i weeks");
            $cle = $semaine->format('Y-m-d');
            $semaines[] = ['debut' => $semaine, 'passages' => $parSemaine[$cle]['passages'] ?? 0, 'note' => $parSemaine[$cle]['note'] ?? null];
        }
        return $semaines;
    }

    public static function reussiteParQcm(array $qcmIds): ?array
    {
        $db = Mongo::db();
        if ($db === null) {
            return null;
        }
        if (!$qcmIds) {
            return [];
        }

        try {
            $resultats = $db->selectCollection(self::STATS)->aggregate([
                ['$match' => ['id_qcm' => ['$in' => array_map('intval', $qcmIds)], 'total' => ['$gt' => 0]]],
                ['$group' => ['_id' => '$id_qcm', 'justes' => ['$sum' => '$justes'], 'reponses' => ['$sum' => '$total']]],
                ['$project' => ['taux' => ['$round' => [['$multiply' => [['$divide' => ['$justes', '$reponses']], 100]], 0]]]],
                ['$sort' => ['taux' => -1]],
            ]);
        } catch (Throwable $e) {
            Mongo::echec($e);
            return null;
        }

        $taux = [];
        foreach ($resultats as $ligne) {
            $taux[(int) $ligne['_id']] = (int) $ligne['taux'];
        }
        return $taux;
    }

    public static function supprimerQcm(int $qcmId): void
    {
        self::executer(fn($db) => $db->selectCollection(self::STATS)->deleteMany(['id_qcm' => $qcmId]));
    }

    public static function supprimerQuestion(int $questionId): void
    {
        self::executer(fn($db) => $db->selectCollection(self::STATS)->deleteMany(['id_question' => $questionId]));
    }

    public static function journaliser(string $type, ?int $intervenantId, array $details = []): void
    {
        self::executer(fn($db) => $db->selectCollection(self::JOURNAL)->insertOne([
            'type' => $type,
            'date' => new UTCDateTime(),
            'id_intervenant' => $intervenantId,
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
