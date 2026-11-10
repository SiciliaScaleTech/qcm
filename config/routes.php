<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\QcmController;
use App\Controllers\QuestionController;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);

$router->get('/inscription', [AuthController::class, 'registerForm']);
$router->post('/inscription', [AuthController::class, 'register']);
$router->get('/connexion', [AuthController::class, 'loginForm']);
$router->post('/connexion', [AuthController::class, 'login']);
$router->post('/deconnexion', [AuthController::class, 'logout']);

$router->get('/tableau-de-bord', [DashboardController::class, 'index']);

$router->get('/qcm/nouveau', [QcmController::class, 'create']);
$router->post('/qcm', [QcmController::class, 'store']);
$router->get('/qcm/{id:int}', [QcmController::class, 'show']);
$router->get('/qcm/{id:int}/modifier', [QcmController::class, 'edit']);
$router->post('/qcm/{id:int}/modifier', [QcmController::class, 'update']);
$router->post('/qcm/{id:int}/supprimer', [QcmController::class, 'destroy']);

$router->get('/qcm/{qcmId:int}/questions/nouvelle', [QuestionController::class, 'create']);
$router->post('/qcm/{qcmId:int}/questions', [QuestionController::class, 'store']);
$router->get('/questions/{id:int}/modifier', [QuestionController::class, 'edit']);
$router->post('/questions/{id:int}/modifier', [QuestionController::class, 'update']);
$router->post('/questions/{id:int}/supprimer', [QuestionController::class, 'destroy']);
