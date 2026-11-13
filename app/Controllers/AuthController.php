<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Formateur;
use App\Models\Tentative;
use App\Repositories\StatsRepository;
use App\Services\Validation;
use PDOException;

class AuthController extends Controller
{
    public function registerForm(): void
    {
        $this->redirectIfLogged();
        $this->render('auth/register', ['errors' => [], 'old' => []], 'Inscription');
    }

    public function register(): void
    {
        Csrf::check();

        // Règles de saisie : composant métier Validation
        [$donnees, $errors] = Validation::inscription($_POST);
        ['nom' => $nom, 'email' => $email, 'password' => $password] = $donnees;

        // Vérification qui a besoin de la base : reste dans le contrôleur
        if (!$errors && Formateur::findByEmail($email)) {
            $errors[] = 'Un compte existe déjà avec cet email.';
        }

        if (!$errors) {
            try {
                $id = Formateur::create($nom, $email, password_hash($password, PASSWORD_DEFAULT));
                Auth::login($id, $nom);
                flash('Bienvenue sur Horizon, ' . $nom . '. Votre compte est créé.');
                $this->redirect('/');
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $errors[] = 'Un compte existe déjà avec cet email.';
            }
        }

        http_response_code(422);
        $this->render('auth/register', ['errors' => $errors, 'old' => ['nom' => $nom, 'email' => $email]], 'Inscription');
    }

    public function loginForm(): void
    {
        $this->redirectIfLogged();
        $this->render('auth/login', ['errors' => [], 'old' => []], 'Connexion');
    }

    public function login(): void
    {
        Csrf::check();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $formateur = Formateur::findByEmail($email);

        // Protection contre les robots qui essaient des milliers de mots de passe :
        // tant que le compte est bloqué, le mot de passe n'est même pas vérifié
        if ($formateur && Formateur::estBloque($formateur)) {
            http_response_code(429);
            $this->render('auth/login', [
                'errors' => ['Trop d\'essais incorrects. Réessayez dans ' . Formateur::MINUTES_BLOCAGE . ' minutes.'],
                'old' => ['email' => $email],
            ], 'Connexion');
            return;
        }

        if ($formateur && password_verify($password, $formateur['mot_de_passe'])) {
            Formateur::reinitialiserEchecs((int) $formateur['id_formateur']);
            Auth::login((int) $formateur['id_formateur'], $formateur['nom']);
            StatsRepository::journaliser('connexion', (int) $formateur['id_formateur']);
            // RGPD : à chaque connexion, les résultats trop anciens sont effacés
            Tentative::purgerAnciennes();
            flash('Content de vous revoir, ' . $formateur['nom'] . '.');
            $this->redirect('/');
        }

        if ($formateur) {
            Formateur::enregistrerEchec($formateur);
        }

        http_response_code(401);
        $this->render('auth/login', [
            'errors' => ['Email ou mot de passe incorrect.'],
            'old' => ['email' => $email],
        ], 'Connexion');
    }

    public function logout(): void
    {
        Csrf::check();
        Auth::logout();
        flash('Vous avez été déconnecté.');
        $this->redirect('/');
    }

    private function redirectIfLogged(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
    }
}
