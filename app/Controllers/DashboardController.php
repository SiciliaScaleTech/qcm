<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Qcm;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::require();
        $this->render('dashboard', [
            'nom' => Auth::nom(),
            'qcms' => Qcm::forFormateur(Auth::id()),
        ], 'Tableau de bord');
    }
}
