<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Validation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Tests des règles de saisie des formulaires
final class ValidationTest extends TestCase
{
    // ---------- Inscription du formateur ----------

    private function inscriptionValide(): array
    {
        return [
            'nom' => 'Marie Durand',
            'email' => 'marie.durand@example.com',
            'password' => 'MotDePasse123',
            'password_confirm' => 'MotDePasse123',
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

    // Chaque cas modifie un seul champ d'une inscription valide
    public static function inscriptionsInvalides(): array
    {
        return [
            'nom vide' => [['nom' => '   '], 'Le nom est obligatoire (100 caractères maximum).'],
            'nom trop long' => [['nom' => str_repeat('a', 101)], 'Le nom est obligatoire (100 caractères maximum).'],
            'email sans @' => [['email' => 'marie.example.com'], "L'adresse email n'est pas valide."],
            'mot de passe de 7 caractères' => [['password' => 'Abc1234', 'password_confirm' => 'Abc1234'], 'Le mot de passe doit contenir entre 8 et 72 caractères.'],
            'mot de passe de 73 caractères' => [['password' => str_repeat('a', 73), 'password_confirm' => str_repeat('a', 73)], 'Le mot de passe doit contenir entre 8 et 72 caractères.'],
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

        // nom, email, longueur du mot de passe
        // (pas d'erreur de confirmation : deux champs vides sont identiques)
        $this->assertCount(3, $erreurs);
    }

    // ---------- QCM ----------

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

    // ---------- Question ----------

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

    // Un formulaire modifié à la main qui envoie un texte au lieu d'une liste ne doit pas planter
    public function testReponsesQuiNeSontPasUneListe(): void
    {
        [$donnees, $erreurs] = Validation::question(['reponses' => 'texte'] + $this->questionValide());

        $this->assertSame(['', '', '', ''], $donnees['reponses']);
        $this->assertContains('Les 4 réponses sont obligatoires (255 caractères maximum chacune).', $erreurs);
    }

    // ---------- Identité de l'étudiant ----------

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
}
