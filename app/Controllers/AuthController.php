<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Formateur;
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

        $nom = trim((string) ($_POST['nom'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        $errors = [];
        if ($nom === '' || mb_strlen($nom) > 100) {
            $errors[] = 'Le nom est obligatoire (100 caractères maximum).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors[] = 'L\'adresse email n\'est pas valide.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Le mot de passe doit contenir entre 8 et 72 caractères.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Les deux mots de passe ne correspondent pas.';
        }
        if (!$errors && Formateur::findByEmail($email)) {
            $errors[] = 'Un compte existe déjà avec cet email.';
        }

        if (!$errors) {
            try {
                $id = Formateur::create($nom, $email, password_hash($password, PASSWORD_DEFAULT));
                Auth::login($id, $nom);
                $this->redirect('/tableau-de-bord');
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
        if ($formateur && password_verify($password, $formateur['mot_de_passe'])) {
            Auth::login((int) $formateur['id_formateur'], $formateur['nom']);
            $this->redirect('/tableau-de-bord');
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
        $this->redirect('/');
    }

    private function redirectIfLogged(): void
    {
        if (Auth::check()) {
            $this->redirect('/tableau-de-bord');
        }
    }
}
