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

        $categories = Categorie::forIntervenant(Auth::id());
        $classes = Classe::forIntervenant(Auth::id());

        $categorieId = (int) ($_GET['categorie'] ?? 0);
        $categorieId = in_array($categorieId, array_map('intval', array_column($categories, 'id_categorie')), true) ? $categorieId : null;

        $annees = Qcm::anneesForIntervenant(Auth::id(), $categorieId);
        $annee = (string) ($_GET['annee'] ?? '');
        $annee = in_array($annee, $annees, true) ? $annee : null;

        $classeId = (int) ($_GET['classe'] ?? 0);
        $classeId = in_array($classeId, array_map('intval', array_column($classes, 'id_classe')), true) ? $classeId : null;

        $qcms = Qcm::forIntervenant(Auth::id(), $categorieId, $annee, $classeId);
        $liens = Classe::liensParQcm(array_column($qcms, 'id_qcm'));
        foreach ($qcms as &$qcm) {
            $qcm['liens'] = array_map(
                fn(array $l): array => ['classe' => $l['nom'], 'url' => absolute_url('/q/' . $l['slug'])],
                $liens[(int) $qcm['id_qcm']] ?? []
            );
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
