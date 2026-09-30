<?php

use App\Modules\Projects\Controllers\ProjectController;

$router->get('/projects', [ProjectController::class, 'index']);
$router->get('/projects/create', [ProjectController::class, 'create']);
$router->get('/projects/report', [ProjectController::class, 'report']);
$router->get('/projects/courses/report', [ProjectController::class, 'courseReport']);
$router->post('/projects', [ProjectController::class, 'store']);
$router->get('/projects/{id}', [ProjectController::class, 'show']);
$router->get('/projects/{id}/edit', [ProjectController::class, 'edit']);
$router->post('/projects/{id}', [ProjectController::class, 'update']);
$router->post('/projects/{id}/status', [ProjectController::class, 'updateStatus']);
$router->post('/projects/{id}/generate-sessions', [ProjectController::class, 'generateSessions']);
$router->get('/projects/{id}/courses/create', [ProjectController::class, 'courseCreate']);
$router->post('/projects/{id}/courses', [ProjectController::class, 'courseStore']);
$router->get('/projects/{id}/courses/{courseId}/edit', [ProjectController::class, 'courseEdit']);
$router->post('/projects/{id}/courses/{courseId}', [ProjectController::class, 'courseUpdate']);
$router->post('/projects/{id}/courses/{courseId}/delete', [ProjectController::class, 'courseDestroy']);
$router->post('/projects/{id}/delete', [ProjectController::class, 'destroy']);
