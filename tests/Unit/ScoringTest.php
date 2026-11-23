<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Scoring;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ScoringTest extends TestCase
{
    private function questions(): array
    {
        return [
            ['id_question' => 1, 'reponses' => [
                ['id_reponse' => 11, 'est_correct' => 1],
                ['id_reponse' => 12, 'est_correct' => 0],
            ]],
            ['id_question' => 2, 'reponses' => [
                ['id_reponse' => 21, 'est_correct' => 1],
                ['id_reponse' => 22, 'est_correct' => 0],
            ]],
            ['id_question' => 3, 'reponses' => [
                ['id_reponse' => 31, 'est_correct' => 1],
                ['id_reponse' => 32, 'est_correct' => 0],
            ]],
        ];
    }

    public function testToutesLesReponsesJustesDonnentLeScoreMaximum(): void
    {
        $resultat = Scoring::corriger($this->questions(), [1 => '11', 2 => '21', 3 => '31']);

        $this->assertSame(3, $resultat['score']);
        $this->assertSame([11, 21, 31], $resultat['choix']);
    }

    public function testToutesLesReponsesFaussesDonnentZero(): void
    {
        $resultat = Scoring::corriger($this->questions(), [1 => '12', 2 => '22', 3 => '32']);

        $this->assertSame(0, $resultat['score']);
        $this->assertSame([12, 22, 32], $resultat['choix']);
    }

    public function testUneQuestionSansReponseNeRapportePasDePoint(): void
    {
        $resultat = Scoring::corriger($this->questions(), [1 => '11', 3 => '32']);

        $this->assertSame(1, $resultat['score']);
        $this->assertSame([11, 32], $resultat['choix']);
    }

    public function testAucuneReponseEnvoyee(): void
    {
        $this->assertSame(['score' => 0, 'choix' => []], Scoring::corriger($this->questions(), []));
    }

    public function testUneReponseDUneAutreQuestionEstIgnoree(): void
    {
        $resultat = Scoring::corriger($this->questions(), [1 => '11', 2 => '11']);

        $this->assertSame(1, $resultat['score']);
        $this->assertSame([11], $resultat['choix']);
    }

    public function testPlusieursReponsesPourUneMemeQuestionSontRefusees(): void
    {
        $resultat = Scoring::corriger($this->questions(), [1 => ['11', '12'], 2 => '21']);

        $this->assertSame(1, $resultat['score']);
        $this->assertSame([21], $resultat['choix']);
    }

    public function testLeScoreNeDepassePasLeNombreDeQuestions(): void
    {
        $envoyees = [1 => '11', 2 => '21', 3 => '31', 4 => '11', 99 => '21'];
        $resultat = Scoring::corriger($this->questions(), $envoyees);

        $this->assertSame(3, $resultat['score']);
        $this->assertSame([11, 21, 31], $resultat['choix']);
    }

    public function testUneValeurQuiNEstPasUnNombreEstIgnoree(): void
    {
        $resultat = Scoring::corriger($this->questions(), [1 => 'abc', 2 => '21']);

        $this->assertSame(1, $resultat['score']);
        $this->assertSame([21], $resultat['choix']);
    }

    public function testUneReponseInexistanteEstIgnoree(): void
    {
        $this->assertSame(['score' => 0, 'choix' => []], Scoring::corriger($this->questions(), [1 => '999']));
    }

    public static function notes(): array
    {
        return [
            '29 sur 40' => [29, 40, 14.5],
            '40 sur 40' => [40, 40, 20.0],
            '0 sur 40' => [0, 40, 0.0],
            '2 sur 3, arrondi au dixième' => [2, 3, 13.3],
            '1 sur 3, arrondi au dixième' => [1, 3, 6.7],
            'QCM sans question' => [0, 0, 0.0],
        ];
    }

    #[DataProvider('notes')]
    public function testNoteSur20(int $score, int $nbQuestions, float $attendu): void
    {
        $this->assertSame($attendu, Scoring::noteSur20($score, $nbQuestions));
    }

    public function testPourcentage(): void
    {
        $this->assertSame(73, Scoring::pourcentage(29, 40));
        $this->assertSame(100, Scoring::pourcentage(3, 3));
        $this->assertSame(0, Scoring::pourcentage(0, 0));
    }

    public function testMoyenneDeDeuxPassages(): void
    {
        $tentatives = [
            ['score' => 10, 'nb_questions' => 10],
            ['score' => 5, 'nb_questions' => 10],
        ];
        $this->assertSame(15.0, Scoring::moyenne($tentatives));
    }

    public function testMoyenneAvecDesNombresDeQuestionsDifferents(): void
    {
        $tentatives = [
            ['score' => 1, 'nb_questions' => 3],
            ['score' => 2, 'nb_questions' => 3],
            ['score' => 29, 'nb_questions' => 40],
        ];
        $this->assertSame(11.5, Scoring::moyenne($tentatives));
    }

    public function testMoyenneSansPassage(): void
    {
        $this->assertSame(0.0, Scoring::moyenne([]));
    }
}
