<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Intervenant;
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

        [$donnees, $errors] = Validation::inscription($_POST);
        ['nom' => $nom, 'email' => $email, 'password' => $password] = $donnees;

        if (!$errors && Intervenant::findByEmail($email)) {
            $errors[] = 'Un compte existe déjà avec cet email.';
        }

        if (!$errors) {
            try {
                $id = Intervenant::create($nom, $email, password_hash($password, PASSWORD_DEFAULT));
                Auth::login($id, $nom);
                flash('Bienvenue sur ' . app_name() . ', ' . $nom . '. Votre compte est créé.');
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

        $intervenant = Intervenant::findByEmail($email);

        if ($intervenant && Intervenant::estBloque($intervenant)) {
            http_response_code(429);
            $this->render('auth/login', [
                'errors' => ['Trop d\'essais incorrects. Réessayez dans ' . Intervenant::MINUTES_BLOCAGE . ' minutes.'],
                'old' => ['email' => $email],
            ], 'Connexion');
            return;
        }

        // Même temps de réponse que l'email existe ou non
        $hash = $intervenant['mot_de_passe'] ?? '$2y$10$CwTycUXWue0Thq9StjUM0uJ8.uWm5VxRpb1V8Z6s0cJh9b7yQ6qGe';
        $motDePasseValide = password_verify($password, $hash);

        if ($intervenant && $motDePasseValide) {
            Intervenant::reinitialiserEchecs((int) $intervenant['id_intervenant']);
            if (password_needs_rehash($intervenant['mot_de_passe'], PASSWORD_DEFAULT)) {
                Intervenant::changerMotDePasse((int) $intervenant['id_intervenant'], password_hash($password, PASSWORD_DEFAULT));
            }
            Auth::login((int) $intervenant['id_intervenant'], $intervenant['nom']);
            StatsRepository::journaliser('connexion', (int) $intervenant['id_intervenant']);

            Tentative::purgerAnciennes();
            flash('Content de vous revoir, ' . $intervenant['nom'] . '.');
            $this->redirect('/');
        }

        if ($intervenant) {
            Intervenant::enregistrerEchec($intervenant);
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
