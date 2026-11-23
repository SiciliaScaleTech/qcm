<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            (new DashboardController())->index();
            return;
        }
        $this->render('home', ['fullWidth' => true], 'Accueil');
    }
}
