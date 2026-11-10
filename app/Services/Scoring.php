<?php
declare(strict_types=1);

namespace App\Services;

// Composant métier : correction d'un QCM et calcul des notes.
// Cette classe ne lit ni la base de données, ni $_POST, ni la session :
// elle reçoit des tableaux et renvoie des résultats. On peut donc la tester seule
// (voir tests/ScoringTest.php).
final class Scoring
{
    public const NOTE_MAX = 20;

    // Corrige les réponses d'un étudiant.
    // $questions : questions du QCM, chacune avec ses réponses (id_reponse, est_correct)
    // $envoyees  : réponses envoyées par le formulaire, [id_question => id_reponse]
    // Renvoie le nombre de bonnes réponses et la liste des réponses retenues.
    public static function corriger(array $questions, array $envoyees): array
    {
        $score = 0;
        $choix = [];

        foreach ($questions as $question) {
            $choisie = filter_var($envoyees[$question['id_question']] ?? null, FILTER_VALIDATE_INT);
            if ($choisie === false) {
                continue; // question sans réponse, ou valeur qui n'est pas un nombre
            }

            // La réponse doit appartenir à cette question, sinon elle est ignorée
            // (protection contre un formulaire modifié à la main)
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

    // Note ramenée sur 20 et arrondie au dixième : 29 bonnes réponses sur 40 donne 14.5
    public static function noteSur20(int $score, int $nbQuestions): float
    {
        if ($nbQuestions <= 0) {
            return 0.0;
        }
        return round($score * self::NOTE_MAX / $nbQuestions, 1);
    }

    // Pourcentage de bonnes réponses, arrondi à l'entier (pour l'anneau de score)
    public static function pourcentage(int $score, int $nbQuestions): int
    {
        if ($nbQuestions <= 0) {
            return 0;
        }
        return (int) round(100 * $score / $nbQuestions);
    }

    // Moyenne sur 20 de plusieurs passages (chacun avec score et nb_questions)
    public static function moyenne(array $tentatives): float
    {
        if (!$tentatives) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($tentatives as $tentative) {
            // On ne cumule pas les notes déjà arrondies, pour ne pas fausser la moyenne
            $nb = (int) $tentative['nb_questions'];
            $total += $nb > 0 ? (int) $tentative['score'] * self::NOTE_MAX / $nb : 0;
        }
        return round($total / count($tentatives), 1);
    }
}
