<?php
declare(strict_types=1);

namespace App\Services;

// Composant métier : validation des formulaires.
// Chaque méthode reçoit les données envoyées (le tableau $_POST) et renvoie
// [données nettoyées, liste des messages d'erreur]. Liste vide = formulaire valide.
// Aucune lecture de la base ici : les vérifications qui en ont besoin
// (email déjà utilisé, QCM déjà passé) restent dans les contrôleurs.
final class Validation
{
    // Inscription d'un formateur
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
        // 72 octets maximum : au-delà, password_hash (bcrypt) ignore la fin du mot de passe
        if (strlen($donnees['password']) < 8 || strlen($donnees['password']) > 72) {
            $erreurs[] = 'Le mot de passe doit contenir entre 8 et 72 caractères.';
        }
        if ($donnees['password'] !== $confirmation) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }
        return [$donnees, $erreurs];
    }

    // Création ou modification d'un QCM.
    // $categoriesAutorisees : ids des catégories existantes, fournis par le contrôleur.
    public static function qcm(array $saisie, array $categoriesAutorisees): array
    {
        $donnees = [
            'titre' => trim((string) ($saisie['titre'] ?? '')),
            'description' => trim((string) ($saisie['description'] ?? '')),
            'annee' => (string) ($saisie['annee'] ?? ''),
            'categorie' => (int) ($saisie['categorie'] ?? 0),
            // Cases cochées : ids des classes existantes
            'classes' => array_map('intval', (array) ($saisie['classes'] ?? [])),
            // Champ texte : nouvelles classes séparées par des virgules
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
        if (!self::anneeScolaireValide($donnees['annee'])) {
            $erreurs[] = "L'année scolaire n'est pas valide.";
        }

        foreach (explode(',', $donnees['nouvelles']) as $nom) {
            $nom = trim($nom);
            if ($nom === '') {
                continue;
            }
            if (mb_strlen($nom) > 50) {
                $erreurs[] = "Le nom d'une classe ne doit pas dépasser 50 caractères.";
                continue;
            }
            $donnees['nouvelles_classes'][] = $nom;
        }

        return [$donnees, $erreurs];
    }

    // Année au format "2026-2027" avec deux années qui se suivent
    public static function anneeScolaireValide(string $annee): bool
    {
        return preg_match('/^(\d{4})-(\d{4})$/', $annee, $m) === 1 && (int) $m[2] === (int) $m[1] + 1;
    }

    // Question : un énoncé, 4 réponses remplies et une bonne réponse (0 à 3)
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

    // Identité de l'étudiant avant de commencer un QCM
    public static function etudiant(array $saisie): array
    {
        $donnees = [
            'nom' => trim((string) ($saisie['nom'] ?? '')),
            'prenom' => trim((string) ($saisie['prenom'] ?? '')),
        ];

        $erreurs = [];
        if ($donnees['nom'] === '' || mb_strlen($donnees['nom']) > 100) {
            $erreurs[] = 'Le nom est obligatoire (100 caractères maximum).';
        }
        if ($donnees['prenom'] === '' || mb_strlen($donnees['prenom']) > 100) {
            $erreurs[] = 'Le prénom est obligatoire (100 caractères maximum).';
        }
        return [$donnees, $erreurs];
    }
}
