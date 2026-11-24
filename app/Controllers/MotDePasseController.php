<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Intervenant;
use App\Repositories\StatsRepository;
use App\Services\Mailer;
use App\Services\Validation;

class MotDePasseController extends Controller
{
    private const MESSAGE_ENVOI = 'Si un compte existe avec cette adresse, un email vient de vous être envoyé. Le lien est valable 1 heure.';

    public function demandeForm(): void
    {
        $this->redirigerSiConnecte();
        $this->render('auth/mot-de-passe-oublie', ['errors' => [], 'old' => []], 'Mot de passe oublié');
    }

    public function demande(): void
    {
        $this->redirigerSiConnecte();
        Csrf::check();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            $this->render('auth/mot-de-passe-oublie', [
                'errors' => ["L'adresse email n'est pas valide."],
                'old' => ['email' => $email],
            ], 'Mot de passe oublié');
            return;
        }

        // Même réponse que le compte existe ou non : on ne révèle pas les adresses inscrites
        $intervenant = Intervenant::findByEmail($email);
        if ($intervenant && !Intervenant::demandeRecente($intervenant)) {
            $jeton = bin2hex(random_bytes(32));
            Intervenant::enregistrerJeton((int) $intervenant['id_intervenant'], hash('sha256', $jeton));
            $this->envoyerLien($intervenant, absolute_url('/reinitialisation/' . $jeton));
        }

        flash(self::MESSAGE_ENVOI);
        $this->redirect('/connexion');
    }

    public function reinitialisationForm(string $jeton): void
    {
        $this->redirigerSiConnecte();
        $this->render('auth/reinitialisation', [
            'jeton' => $jeton,
            'valide' => $this->intervenantDuJeton($jeton) !== null,
            'errors' => [],
        ], 'Nouveau mot de passe');
    }

    public function reinitialisation(string $jeton): void
    {
        $this->redirigerSiConnecte();
        Csrf::check();

        $intervenant = $this->intervenantDuJeton($jeton);
        if ($intervenant === null) {
            http_response_code(410);
            $this->render('auth/reinitialisation', ['jeton' => $jeton, 'valide' => false, 'errors' => []], 'Nouveau mot de passe');
            return;
        }

        [$motDePasse, $errors] = Validation::nouveauMotDePasse($_POST);
        if ($errors) {
            http_response_code(422);
            $this->render('auth/reinitialisation', ['jeton' => $jeton, 'valide' => true, 'errors' => $errors], 'Nouveau mot de passe');
            return;
        }

        Intervenant::reinitialiserMotDePasse((int) $intervenant['id_intervenant'], password_hash($motDePasse, PASSWORD_DEFAULT));
        StatsRepository::journaliser('mot_de_passe_reinitialise', (int) $intervenant['id_intervenant']);

        flash('Votre mot de passe a été modifié. Vous pouvez vous connecter.');
        $this->redirect('/connexion');
    }

    private function intervenantDuJeton(string $jeton): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $jeton)) {
            return null;
        }
        return Intervenant::findByJeton(hash('sha256', $jeton));
    }

    private function envoyerLien(array $intervenant, string $lien): void
    {
        $nom = (string) $intervenant['nom'];
        $site = app_name();
        $minutes = Intervenant::MINUTES_VALIDITE_JETON;

        $texte = "Bonjour $nom,\n\n"
            . "Vous avez demandé à changer le mot de passe de votre compte $site.\n"
            . "Choisissez un nouveau mot de passe en ouvrant ce lien (valable $minutes minutes, utilisable une seule fois) :\n\n"
            . "$lien\n\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : votre mot de passe actuel reste valable.\n";

        $html = '<p>Bonjour ' . e($nom) . ',</p>'
            . '<p>Vous avez demandé à changer le mot de passe de votre compte ' . e($site) . '.</p>'
            . '<p><a href="' . e($lien) . '">Choisir un nouveau mot de passe</a></p>'
            . '<p>Ce lien est valable ' . $minutes . ' minutes et ne peut servir qu\'une fois.</p>'
            . '<p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email : votre mot de passe actuel reste valable.</p>';

        Mailer::envoyer((string) $intervenant['email'], 'Réinitialisation de votre mot de passe - ' . $site, $texte, $html);
    }

    private function redirigerSiConnecte(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
    }
}
