<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\StatsRepository;
use PHPUnit\Framework\TestCase;

// Préparation des mises à jour MongoDB après un passage (sans serveur MongoDB)
final class StatsRepositoryTest extends TestCase
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
        ];
    }

    // Opération n de la liste : [filtre, mise à jour, options]
    private function operation(array $operations, int $n): array
    {
        return $operations[$n]['updateOne'];
    }

    public function testUneMiseAJourDesCompteursPuisUnRecalculParQuestion(): void
    {
        $operations = StatsRepository::operationsPassage(7, $this->questions(), [11, 22]);

        $this->assertCount(4, $operations);
        $this->assertSame(['id_qcm' => 7, 'id_question' => 1], $this->operation($operations, 0)[0]);
        $this->assertSame(['id_qcm' => 7, 'id_question' => 2], $this->operation($operations, 1)[0]);
    }

    public function testLesCompteursSontCreesSiBesoin(): void
    {
        $operations = StatsRepository::operationsPassage(7, $this->questions(), [11, 22]);

        $this->assertSame(['upsert' => true], $this->operation($operations, 0)[2]);
    }

    public function testBonneReponse(): void
    {
        $operations = StatsRepository::operationsPassage(7, $this->questions(), [11, 22]);

        $this->assertSame(
            ['$inc' => ['total' => 1, 'justes' => 1, 'options.11' => 1]],
            $this->operation($operations, 0)[1]
        );
    }

    public function testMauvaiseReponse(): void
    {
        $operations = StatsRepository::operationsPassage(7, $this->questions(), [11, 22]);

        $this->assertSame(
            ['$inc' => ['total' => 1, 'justes' => 0, 'options.22' => 1]],
            $this->operation($operations, 1)[1]
        );
    }

    public function testQuestionSansReponse(): void
    {
        $operations = StatsRepository::operationsPassage(7, $this->questions(), [11]);

        $this->assertSame(
            ['$inc' => ['total' => 1, 'justes' => 0, 'sans_reponse' => 1]],
            $this->operation($operations, 1)[1]
        );
    }

    public function testLeTauxDeReussiteEstRecalculeParMongoDB(): void
    {
        $operations = StatsRepository::operationsPassage(7, $this->questions(), [11]);
        $pipeline = $this->operation($operations, 2)[1];

        $this->assertSame(['id_qcm' => 7, 'id_question' => 1], $this->operation($operations, 2)[0]);
        $this->assertArrayHasKey('taux_reussite', $pipeline[0]['$set']);
    }

    public function testAucuneQuestion(): void
    {
        $this->assertSame([], StatsRepository::operationsPassage(7, [], []));
    }
}
