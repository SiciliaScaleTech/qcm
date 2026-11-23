<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Tentative;

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
        flash($tentative['prenom_etudiant'] . ' ' . $tentative['nom_etudiant'] . ' peut repasser le QCM.');
        $this->redirect('/qcm/' . $tentative['id_qcm']);
    }
}
