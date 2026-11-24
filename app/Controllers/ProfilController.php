<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Intervenant;
use App\Repositories\StatsRepository;
use App\Services\Validation;
use PDOException;

class ProfilController extends Controller
{
    public function index(): void
    {
        Auth::require();
        $intervenant = $this->intervenant();
        $this->afficher($intervenant, ['nom' => $intervenant['nom'], 'email' => $intervenant['email']]);
    }

    public function informations(): void
    {
        Auth::require();
        Csrf::check();
        $intervenant = $this->intervenant();

        [$donnees, $erreurs] = Validation::profil($_POST);
        $erreurs = array_merge($erreurs, $this->verifierMotDePasseActuel($intervenant));

        if (!$erreurs && $donnees['email'] !== $intervenant['email'] && Intervenant::findByEmail($donnees['email'])) {
            $erreurs[] = 'Un compte existe déjà avec cet email.';
        }

        if (!$erreurs) {
            try {
                Intervenant::modifierProfil((int) $intervenant['id_intervenant'], $donnees['nom'], $donnees['email']);
                Auth::renommer($donnees['nom']);
                StatsRepository::journaliser('profil_modifie', (int) $intervenant['id_intervenant']);
                flash('Vos informations sont enregistrées.');
                $this->redirect('/profil');
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Un compte existe déjà avec cet email.';
            }
        }

        http_response_code(422);
        $this->afficher($intervenant, $donnees, $erreurs);
    }

    public function motDePasse(): void
    {
        Auth::require();
        Csrf::check();
        $intervenant = $this->intervenant();

        $erreurs = $this->verifierMotDePasseActuel($intervenant);
        [$nouveau, $erreursNouveau] = Validation::nouveauMotDePasse($_POST);
        $erreurs = array_merge($erreurs, $erreursNouveau);

        if (!$erreurs && password_verify($nouveau, $intervenant['mot_de_passe'])) {
            $erreurs[] = "Le nouveau mot de passe doit être différent de l'actuel.";
        }

        if (!$erreurs) {
            $hash = password_hash($nouveau, PASSWORD_DEFAULT);
            Intervenant::reinitialiserMotDePasse((int) $intervenant['id_intervenant'], $hash);
            session_regenerate_id(true);
            Auth::memoriserMotDePasse($hash);
            StatsRepository::journaliser('mot_de_passe_modifie', (int) $intervenant['id_intervenant']);
            flash('Votre mot de passe est modifié.');
            $this->redirect('/profil');
        }

        http_response_code(422);
        $this->afficher($intervenant, ['nom' => $intervenant['nom'], 'email' => $intervenant['email']], [], $erreurs);
    }

    private function intervenant(): array
    {
        $intervenant = Intervenant::findById((int) Auth::id());
        if (!$intervenant) {
            Auth::logout();
            $this->redirect('/connexion');
        }
        return $intervenant;
    }

    private function verifierMotDePasseActuel(array $intervenant): array
    {
        if (Intervenant::estBloque($intervenant)) {
            return ["Trop d'essais incorrects. Réessayez dans " . Intervenant::MINUTES_BLOCAGE . ' minutes.'];
        }
        $saisi = is_string($_POST['mot_de_passe_actuel'] ?? null) ? $_POST['mot_de_passe_actuel'] : '';
        if (!password_verify($saisi, $intervenant['mot_de_passe'])) {
            Intervenant::enregistrerEchec($intervenant);
            return ['Le mot de passe actuel est incorrect.'];
        }
        return [];
    }

    private function afficher(array $intervenant, array $old, array $erreursInfos = [], array $erreursMdp = []): void
    {
        $this->render('profil', [
            'old' => $old,
            'inscritLe' => $intervenant['date_creation'],
            'erreursInfos' => $erreursInfos,
            'erreursMdp' => $erreursMdp,
        ], 'Mon profil');
    }
}
