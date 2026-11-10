<?php
declare(strict_types=1);

use App\Controllers\HomeController;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);

// À ajouter au fil du développement :
// $router->get('/inscription', [AuthController::class, 'registerForm']);
// $router->post('/inscription', [AuthController::class, 'register']);
// $router->get('/q/{token}', [PassageController::class, 'start']);
