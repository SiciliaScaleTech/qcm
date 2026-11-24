<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class ErrorController extends Controller
{
    public function notFound(): void
    {
        http_response_code(404);
        $this->render('404', [], 'Page introuvable');
    }

    public function serverError(): void
    {
        http_response_code(500);
        $this->render('500', [], 'Erreur');
    }
}
