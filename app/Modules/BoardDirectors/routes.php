<?php

use App\Modules\BoardDirectors\Controllers\BoardDirectorController;

$router->get('/board-directors', [BoardDirectorController::class, 'index']);
$router->get('/board-directors/create', [BoardDirectorController::class, 'create']);
$router->post('/board-directors', [BoardDirectorController::class, 'store']);
$router->get('/board-directors/{id}', [BoardDirectorController::class, 'show']);
$router->get('/board-directors/{id}/edit', [BoardDirectorController::class, 'edit']);
$router->post('/board-directors/{id}', [BoardDirectorController::class, 'update']);
$router->post('/board-directors/{id}/toggle', [BoardDirectorController::class, 'toggle']);
$router->post('/board-directors/{id}/delete', [BoardDirectorController::class, 'destroy']);
