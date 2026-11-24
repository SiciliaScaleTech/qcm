<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Question;
use App\Models\Tentative;
use App\Repositories\StatsRepository;

class TentativeController extends Controller
{
    public function destroy(int $id): void
    {
        Auth::require();
        $tentative = Tentative::findOwned($id, Auth::id());
        if (!$tentative) {
            $this->abortNotFound();
        }
        Csrf::check();

        Tentative::delete($id);
        $qcmId = (int) $tentative['id_qcm'];
        StatsRepository::reconstruireQcm($qcmId, Question::forQcm($qcmId), Tentative::choixParTentative($qcmId));
        flash($tentative['prenom_etudiant'] . ' ' . $tentative['nom_etudiant'] . ' peut repasser le QCM.');
        $this->redirect('/qcm/' . $tentative['id_qcm']);
    }
}
