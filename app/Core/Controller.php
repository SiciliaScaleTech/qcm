<?php
declare(strict_types=1);

namespace App\Core;

// Contrôleur de base : affichage des vues et redirections
abstract class Controller
{
    // Affiche app/Views/<vue>.php à l'intérieur du gabarit layout.php
    protected function render(string $view, array $data = [], string $title = 'QCM'): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require ROOT_PATH . '/app/Views/' . $view . '.php';
        $content = ob_get_clean();

        require ROOT_PATH . '/app/Views/layout.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function abortNotFound(): void
    {
        http_response_code(404);
        $this->render('404', [], 'Page introuvable');
        exit;
    }
}
