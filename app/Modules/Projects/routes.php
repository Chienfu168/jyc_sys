<?php

use App\Modules\Projects\Controllers\ProjectController;

$router->get('/projects', [ProjectController::class, 'index']);
$router->get('/projects/create', [ProjectController::class, 'create']);
$router->post('/projects', [ProjectController::class, 'store']);
$router->get('/projects/{id}', [ProjectController::class, 'show']);
$router->get('/projects/{id}/edit', [ProjectController::class, 'edit']);
$router->post('/projects/{id}', [ProjectController::class, 'update']);
$router->post('/projects/{id}/status', [ProjectController::class, 'updateStatus']);
$router->post('/projects/{id}/generate-sessions', [ProjectController::class, 'generateSessions']);
$router->post('/projects/{id}/delete', [ProjectController::class, 'destroy']);
