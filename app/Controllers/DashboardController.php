<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Categorie;
use App\Models\Classe;
use App\Models\Qcm;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::require();

        $categories = Categorie::forFormateur(Auth::id());
        $classes = Classe::forFormateur(Auth::id());

        $categorieId = (int) ($_GET['categorie'] ?? 0);
        $categorieId = in_array($categorieId, array_map('intval', array_column($categories, 'id_categorie')), true) ? $categorieId : null;

        $annees = Qcm::anneesForFormateur(Auth::id(), $categorieId);
        $annee = (string) ($_GET['annee'] ?? '');
        $annee = in_array($annee, $annees, true) ? $annee : null;

        $classeId = (int) ($_GET['classe'] ?? 0);
        $classeId = in_array($classeId, array_map('intval', array_column($classes, 'id_classe')), true) ? $classeId : null;

        $qcms = Qcm::forFormateur(Auth::id(), $categorieId, $annee, $classeId);
        foreach ($qcms as &$qcm) {
            $qcm['lien'] = absolute_url('/q/' . $qcm['slug']);
        }
        unset($qcm);

        $this->render('dashboard', [
            'nom' => Auth::nom(),
            'qcms' => $qcms,
            'aucunQcm' => array_sum(array_column($categories, 'nb_qcm')) === 0,
            'categories' => $categories,
            'annees' => $annees,
            'classes' => $classes,
            'categorieActive' => $categorieId,
            'anneeActive' => $annee,
            'classeActive' => $classeId,
        ], 'Mes QCM');
    }
}
