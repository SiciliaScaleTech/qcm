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
    // Liste des QCM avec trois rangées d'onglets :
    // catégorie (?categorie=1), puis année scolaire (?annee=2026-2027) et classe (?classe=3)
    public function index(): void
    {
        Auth::require();

        $categories = Categorie::forFormateur(Auth::id());
        $classes = Classe::forFormateur(Auth::id());

        // On n'accepte que des valeurs qui existent, sinon pas de filtre
        $categorieId = (int) ($_GET['categorie'] ?? 0);
        $categorieId = in_array($categorieId, array_map('intval', array_column($categories, 'id_categorie')), true) ? $categorieId : null;

        // Les années proposées sont celles de la catégorie choisie
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
