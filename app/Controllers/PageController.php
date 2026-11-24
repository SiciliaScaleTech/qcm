<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Tentative;

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

    private function legal(): array
    {
        $valeurs = (array) require ROOT_PATH . '/config/legal.php';
        return array_map(fn($valeur): string => trim((string) $valeur), $valeurs);
    }
}
