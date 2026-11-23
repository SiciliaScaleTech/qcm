<?php
declare(strict_types=1);

namespace App\Services;

final class Scoring
{
    public const NOTE_MAX = 20;

    public static function corriger(array $questions, array $envoyees): array
    {
        $score = 0;
        $choix = [];

        foreach ($questions as $question) {
            $choisie = filter_var($envoyees[$question['id_question']] ?? null, FILTER_VALIDATE_INT);
            if ($choisie === false) {
                continue;
            }

            foreach ($question['reponses'] as $reponse) {
                if ((int) $reponse['id_reponse'] === $choisie) {
                    $choix[] = $choisie;
                    if ($reponse['est_correct']) {
                        $score++;
                    }
                }
            }
        }

        return ['score' => $score, 'choix' => $choix];
    }

    public static function noteSur20(int $score, int $nbQuestions): float
    {
        if ($nbQuestions <= 0) {
            return 0.0;
        }
        return round($score * self::NOTE_MAX / $nbQuestions, 1);
    }

    public static function pourcentage(int $score, int $nbQuestions): int
    {
        if ($nbQuestions <= 0) {
            return 0;
        }
        return (int) round(100 * $score / $nbQuestions);
    }

    public static function moyenne(array $tentatives): float
    {
        if (!$tentatives) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($tentatives as $tentative) {
            $nb = (int) $tentative['nb_questions'];
            $total += $nb > 0 ? (int) $tentative['score'] * self::NOTE_MAX / $nb : 0;
        }
        return round($total / count($tentatives), 1);
    }
}
