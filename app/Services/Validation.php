<?php
declare(strict_types=1);

namespace App\Services;

final class Validation
{
    public const MDP_MIN = 12;
    public const MDP_MAX = 72;
    public const MESSAGE_MDP = 'Le mot de passe doit contenir entre 12 et 72 caractères, dont une minuscule, une majuscule, un chiffre et un caractère spécial.';

    public static function inscription(array $saisie): array
    {
        $donnees = [
            'nom' => trim((string) ($saisie['nom'] ?? '')),
            'email' => strtolower(trim((string) ($saisie['email'] ?? ''))),
            'password' => (string) ($saisie['password'] ?? ''),
        ];
        $confirmation = (string) ($saisie['password_confirm'] ?? '');

        $erreurs = [];
        if ($donnees['nom'] === '' || mb_strlen($donnees['nom']) > 100) {
            $erreurs[] = 'Le nom est obligatoire (100 caractères maximum).';
        }
        if (!filter_var($donnees['email'], FILTER_VALIDATE_EMAIL) || strlen($donnees['email']) > 255) {
            $erreurs[] = "L'adresse email n'est pas valide.";
        }
        if (!self::motDePasseRobuste($donnees['password'])) {
            $erreurs[] = self::MESSAGE_MDP;
        }
        if ($donnees['password'] !== $confirmation) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }
        return [$donnees, $erreurs];
    }

    public static function nouveauMotDePasse(array $saisie): array
    {
        $motDePasse = is_string($saisie['password'] ?? null) ? $saisie['password'] : '';
        $confirmation = is_string($saisie['password_confirm'] ?? null) ? $saisie['password_confirm'] : '';

        $erreurs = [];
        if (!self::motDePasseRobuste($motDePasse)) {
            $erreurs[] = self::MESSAGE_MDP;
        }
        if ($motDePasse !== $confirmation) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }
        return [$motDePasse, $erreurs];
    }

    // bcrypt ignore tout ce qui dépasse 72 octets
    public static function motDePasseRobuste(string $mdp): bool
    {
        return strlen($mdp) >= self::MDP_MIN
            && strlen($mdp) <= self::MDP_MAX
            && preg_match('/\p{Ll}/u', $mdp) === 1
            && preg_match('/\p{Lu}/u', $mdp) === 1
            && preg_match('/\d/', $mdp) === 1
            && preg_match('/[^\p{L}\d]/u', $mdp) === 1;
    }

    public static function qcm(array $saisie, array $categoriesAutorisees, array $classesAutorisees): array
    {
        $donnees = [
            'titre' => trim((string) ($saisie['titre'] ?? '')),
            'description' => trim((string) ($saisie['description'] ?? '')),
            'date' => (string) ($saisie['date_evaluation'] ?? ''),
            'annee' => '',
            'categorie' => (int) ($saisie['categorie'] ?? 0),
            'classes' => array_values(array_intersect(
                array_map('intval', (array) ($saisie['classes'] ?? [])),
                array_map('intval', $classesAutorisees)
            )),
            'nouvelles' => trim((string) ($saisie['nouvelles_classes'] ?? '')),
            'nouvelles_classes' => [],
        ];

        $erreurs = [];
        if ($donnees['titre'] === '' || mb_strlen($donnees['titre']) > 150) {
            $erreurs[] = 'Le titre est obligatoire (150 caractères maximum).';
        }
        if (mb_strlen($donnees['description']) > 1000) {
            $erreurs[] = 'La description ne doit pas dépasser 1000 caractères.';
        }
        if (!in_array($donnees['categorie'], array_map('intval', $categoriesAutorisees), true)) {
            $erreurs[] = 'Choisissez une catégorie.';
        }
        if (self::dateValide($donnees['date'])) {
            $donnees['annee'] = self::anneeScolaireDe($donnees['date']);
        } else {
            $erreurs[] = "La date de l'évaluation n'est pas valide.";
        }

        foreach (explode(',', $donnees['nouvelles']) as $nom) {
            $nom = trim((string) preg_replace('/\s+/u', ' ', $nom));
            if ($nom === '') {
                continue;
            }
            if (mb_strlen($nom) > 50 || !preg_match('/[\p{L}\d]/u', $nom)) {
                $erreurs[] = "Le nom d'une classe doit contenir au moins une lettre ou un chiffre (50 caractères maximum).";
                continue;
            }
            $donnees['nouvelles_classes'][] = $nom;
        }

        if (!$donnees['classes'] && !$donnees['nouvelles_classes']) {
            $erreurs[] = 'Choisissez ou ajoutez au moins une classe : chaque classe reçoit son propre lien.';
        }

        return [$donnees, $erreurs];
    }

    public static function dateValide(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date && $d->format('Y') >= '2000' && $d->format('Y') <= '2100';
    }

    public static function anneeScolaireDe(string $date): string
    {
        $annee = (int) substr($date, 0, 4);
        $debut = (int) substr($date, 5, 2) >= 8 ? $annee : $annee - 1;
        return $debut . '-' . ($debut + 1);
    }

    public static function anneeScolaireValide(string $annee): bool
    {
        return preg_match('/^(\d{4})-(\d{4})$/', $annee, $m) === 1 && (int) $m[2] === (int) $m[1] + 1;
    }

    public static function question(array $saisie): array
    {
        $reponses = $saisie['reponses'] ?? [];
        $reponses = is_array($reponses) ? array_slice(array_values($reponses), 0, 4) : [];
        $textes = array_map(fn($r) => is_string($r) ? trim($r) : '', $reponses);

        $correcte = filter_var($saisie['correct'] ?? '', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 3],
        ]);

        $donnees = [
            'enonce' => trim((string) ($saisie['enonce'] ?? '')),
            'reponses' => array_pad($textes, 4, ''),
            'correcte' => $correcte === false ? -1 : $correcte,
        ];

        $erreurs = [];
        if ($donnees['enonce'] === '' || mb_strlen($donnees['enonce']) > 1000) {
            $erreurs[] = 'La question est obligatoire (1000 caractères maximum).';
        }
        foreach ($donnees['reponses'] as $texte) {
            if ($texte === '' || mb_strlen($texte) > 255) {
                $erreurs[] = 'Les 4 réponses sont obligatoires (255 caractères maximum chacune).';
                break;
            }
        }
        if ($correcte === false) {
            $erreurs[] = 'Choisissez la bonne réponse.';
        }
        return [$donnees, $erreurs];
    }

    public static function etudiant(array $saisie): array
    {
        $donnees = [
            'nom' => self::nettoyerNom($saisie['nom'] ?? ''),
            'prenom' => self::nettoyerNom($saisie['prenom'] ?? ''),
        ];

        $erreurs = [];
        foreach (['nom' => 'Le nom', 'prenom' => 'Le prénom'] as $champ => $libelle) {
            $valeur = $donnees[$champ];
            if ($valeur === '' || mb_strlen($valeur) > 100) {
                $erreurs[] = $libelle . ' est obligatoire (100 caractères maximum).';
            } elseif (!preg_match("/^\p{L}[\p{L}\p{M}'’ .-]*$/u", $valeur)) {
                $erreurs[] = $libelle . ' ne peut contenir que des lettres, des espaces, des tirets et des apostrophes.';
            }
        }
        return [$donnees, $erreurs];
    }

    private static function nettoyerNom(mixed $valeur): string
    {
        return is_string($valeur) ? trim((string) preg_replace('/\s+/u', ' ', $valeur)) : '';
    }
}
