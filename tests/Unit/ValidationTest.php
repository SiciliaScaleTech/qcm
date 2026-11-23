<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Validation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    private function inscriptionValide(): array
    {
        return [
            'nom' => 'Marie Durand',
            'email' => 'marie.durand@example.com',
            'password' => 'MotDePasse123!',
            'password_confirm' => 'MotDePasse123!',
        ];
    }

    public function testInscriptionValide(): void
    {
        [$donnees, $erreurs] = Validation::inscription($this->inscriptionValide());

        $this->assertSame([], $erreurs);
        $this->assertSame('marie.durand@example.com', $donnees['email']);
    }

    public function testEmailMisEnMinusculesEtEspacesRetires(): void
    {
        $saisie = ['email' => '  Marie.Durand@Example.COM '] + $this->inscriptionValide();
        [$donnees] = Validation::inscription($saisie);

        $this->assertSame('marie.durand@example.com', $donnees['email']);
    }

    public static function inscriptionsInvalides(): array
    {
        return [
            'nom vide' => [['nom' => '   '], 'Le nom est obligatoire (100 caractères maximum).'],
            'nom trop long' => [['nom' => str_repeat('a', 101)], 'Le nom est obligatoire (100 caractères maximum).'],
            'email sans @' => [['email' => 'marie.example.com'], "L'adresse email n'est pas valide."],
            'mot de passe de 11 caractères' => [['password' => 'Abcdefg12!x', 'password_confirm' => 'Abcdefg12!x'], Validation::MESSAGE_MDP],
            'mot de passe de 73 caractères' => [['password' => 'Aa1!' . str_repeat('a', 69), 'password_confirm' => 'Aa1!' . str_repeat('a', 69)], Validation::MESSAGE_MDP],
            'mot de passe sans majuscule' => [['password' => 'motdepasse123!', 'password_confirm' => 'motdepasse123!'], Validation::MESSAGE_MDP],
            'mot de passe sans minuscule' => [['password' => 'MOTDEPASSE123!', 'password_confirm' => 'MOTDEPASSE123!'], Validation::MESSAGE_MDP],
            'mot de passe sans chiffre' => [['password' => 'MotDePasseABC!', 'password_confirm' => 'MotDePasseABC!'], Validation::MESSAGE_MDP],
            'mot de passe sans caractère spécial' => [['password' => 'MotDePasse1234', 'password_confirm' => 'MotDePasse1234'], Validation::MESSAGE_MDP],
            'confirmation différente' => [['password_confirm' => 'AutreMotDePasse'], 'Les deux mots de passe ne correspondent pas.'],
        ];
    }

    #[DataProvider('inscriptionsInvalides')]
    public function testInscriptionInvalide(array $modification, string $erreurAttendue): void
    {
        [, $erreurs] = Validation::inscription($modification + $this->inscriptionValide());

        $this->assertSame([$erreurAttendue], $erreurs);
    }

    public function testFormulaireDInscriptionVideDonneTroisErreurs(): void
    {
        [, $erreurs] = Validation::inscription([]);

        $this->assertCount(3, $erreurs);
    }

    private const CATEGORIES = [1, 2];

    private function qcmValide(): array
    {
        return [
            'titre' => 'Les bases du HTML',
            'description' => '',
            'annee' => '2026-2027',
            'categorie' => '2',
            'nouvelles_classes' => '',
        ];
    }

    public function testQcmValide(): void
    {
        [$donnees, $erreurs] = Validation::qcm($this->qcmValide(), self::CATEGORIES);

        $this->assertSame([], $erreurs);
        $this->assertSame(2, $donnees['categorie']);
    }

    public static function qcmInvalides(): array
    {
        return [
            'titre vide' => [['titre' => ''], 'Le titre est obligatoire (150 caractères maximum).'],
            'titre de 151 caractères' => [['titre' => str_repeat('a', 151)], 'Le titre est obligatoire (150 caractères maximum).'],
            'description de 1001 caractères' => [['description' => str_repeat('a', 1001)], 'La description ne doit pas dépasser 1000 caractères.'],
            'catégorie absente' => [['categorie' => ''], 'Choisissez une catégorie.'],
            'catégorie inexistante' => [['categorie' => '99'], 'Choisissez une catégorie.'],
            'année mal écrite' => [['annee' => '2026'], "L'année scolaire n'est pas valide."],
            'années qui ne se suivent pas' => [['annee' => '2026-2030'], "L'année scolaire n'est pas valide."],
            'classe de 51 caractères' => [['nouvelles_classes' => str_repeat('a', 51)], "Le nom d'une classe ne doit pas dépasser 50 caractères."],
        ];
    }

    #[DataProvider('qcmInvalides')]
    public function testQcmInvalide(array $modification, string $erreurAttendue): void
    {
        [, $erreurs] = Validation::qcm($modification + $this->qcmValide(), self::CATEGORIES);

        $this->assertSame([$erreurAttendue], $erreurs);
    }

    public function testLesNouvellesClassesSontDecoupeesParVirgule(): void
    {
        $saisie = ['nouvelles_classes' => ' BTS SIO 1, ,BTS SIO 2 ,'] + $this->qcmValide();
        [$donnees, $erreurs] = Validation::qcm($saisie, self::CATEGORIES);

        $this->assertSame([], $erreurs);
        $this->assertSame(['BTS SIO 1', 'BTS SIO 2'], $donnees['nouvelles_classes']);
    }

    public static function anneesScolaires(): array
    {
        return [
            'année normale' => ['2026-2027', true],
            'années inversées' => ['2027-2026', false],
            'même année' => ['2026-2026', false],
            'format court' => ['26-27', false],
            'texte' => ['abcd-efgh', false],
            'vide' => ['', false],
        ];
    }

    #[DataProvider('anneesScolaires')]
    public function testAnneeScolaireValide(string $annee, bool $attendu): void
    {
        $this->assertSame($attendu, Validation::anneeScolaireValide($annee));
    }

    private function questionValide(): array
    {
        return [
            'enonce' => 'Quelle balise crée un lien ?',
            'reponses' => ['<a>', '<link>', '<href>', '<url>'],
            'correct' => '0',
        ];
    }

    public function testQuestionValide(): void
    {
        [$donnees, $erreurs] = Validation::question($this->questionValide());

        $this->assertSame([], $erreurs);
        $this->assertSame(0, $donnees['correcte']);
        $this->assertCount(4, $donnees['reponses']);
    }

    public static function questionsInvalides(): array
    {
        return [
            'énoncé vide' => [['enonce' => ' '], 'La question est obligatoire (1000 caractères maximum).'],
            'une réponse vide' => [['reponses' => ['<a>', '', '<href>', '<url>']], 'Les 4 réponses sont obligatoires (255 caractères maximum chacune).'],
            'seulement 3 réponses' => [['reponses' => ['<a>', '<link>', '<href>']], 'Les 4 réponses sont obligatoires (255 caractères maximum chacune).'],
            'réponse de 256 caractères' => [['reponses' => [str_repeat('a', 256), 'b', 'c', 'd']], 'Les 4 réponses sont obligatoires (255 caractères maximum chacune).'],
            'pas de bonne réponse' => [['correct' => ''], 'Choisissez la bonne réponse.'],
            'bonne réponse hors limites' => [['correct' => '4'], 'Choisissez la bonne réponse.'],
            'bonne réponse négative' => [['correct' => '-1'], 'Choisissez la bonne réponse.'],
        ];
    }

    #[DataProvider('questionsInvalides')]
    public function testQuestionInvalide(array $modification, string $erreurAttendue): void
    {
        [, $erreurs] = Validation::question($modification + $this->questionValide());

        $this->assertSame([$erreurAttendue], $erreurs);
    }

    public function testReponsesQuiNeSontPasUneListe(): void
    {
        [$donnees, $erreurs] = Validation::question(['reponses' => 'texte'] + $this->questionValide());

        $this->assertSame(['', '', '', ''], $donnees['reponses']);
        $this->assertContains('Les 4 réponses sont obligatoires (255 caractères maximum chacune).', $erreurs);
    }

    public function testEtudiantValide(): void
    {
        [$donnees, $erreurs] = Validation::etudiant(['nom' => ' Dupont ', 'prenom' => 'Léa']);

        $this->assertSame([], $erreurs);
        $this->assertSame(['nom' => 'Dupont', 'prenom' => 'Léa'], $donnees);
    }

    public function testEtudiantSansNomNiPrenom(): void
    {
        [, $erreurs] = Validation::etudiant(['nom' => '', 'prenom' => '   ']);

        $this->assertSame([
            'Le nom est obligatoire (100 caractères maximum).',
            'Le prénom est obligatoire (100 caractères maximum).',
        ], $erreurs);
    }

    public function testEtudiantEspacesMultiplesReduits(): void
    {
        [$donnees, $erreurs] = Validation::etudiant(['nom' => '  Le   Gall ', 'prenom' => 'Jean-Pierre']);

        $this->assertSame([], $erreurs);
        $this->assertSame('Le Gall', $donnees['nom']);
    }

    public function testEtudiantNomAvecApostrophe(): void
    {
        [, $erreurs] = Validation::etudiant(['nom' => "D'Artagnan", 'prenom' => 'Zoé']);

        $this->assertSame([], $erreurs);
    }

    public static function nomsEtudiantInvalides(): array
    {
        return [
            'balise HTML' => ['<script>alert(1)</script>'],
            'chiffres' => ['Dupont2'],
            'commence par un tiret' => ['-Dupont'],
            'accolades' => ['{$ne: null}'],
        ];
    }

    #[DataProvider('nomsEtudiantInvalides')]
    public function testEtudiantCaracteresInterdits(string $nom): void
    {
        [, $erreurs] = Validation::etudiant(['nom' => $nom, 'prenom' => 'Léa']);

        $this->assertSame(['Le nom ne peut contenir que des lettres, des espaces, des tirets et des apostrophes.'], $erreurs);
    }

    public function testEtudiantValeurQuiNestPasUnTexte(): void
    {
        [$donnees, $erreurs] = Validation::etudiant(['nom' => ['Dupont'], 'prenom' => 'Léa']);

        $this->assertSame('', $donnees['nom']);
        $this->assertCount(1, $erreurs);
    }
}
