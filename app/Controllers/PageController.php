<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Tentative;

// Pages d'information : mentions légales et politique de confidentialité (RGPD)
class PageController extends Controller
{
    public function mentionsLegales(): void
    {
        $this->render('pages/mentions-legales', ['legal' => $this->legal()], 'Mentions légales');
    }

    public function confidentialite(): void
    {
        $this->render('pages/confidentialite', [
            'legal' => $this->legal(),
            'conservationMois' => Tentative::DUREE_CONSERVATION_MOIS,
            'inactiviteHeures' => intdiv(Auth::DUREE_INACTIVITE, 3600),
        ], 'Confidentialité');
    }

    // Valeurs de config.php ; une valeur manquante s'affiche « à compléter »
    private function legal(): array
    {
        $valeurs = (array) config('legal', []);
        $cles = ['editeur', 'adresse', 'email', 'directeur_publication', 'hebergeur', 'hebergeur_adresse', 'hebergeur_telephone'];
        $legal = [];
        foreach ($cles as $cle) {
            $legal[$cle] = trim((string) ($valeurs[$cle] ?? ''));
        }
        return $legal;
    }
}
