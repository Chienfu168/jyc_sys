<?php

use App\Modules\Activities\Controllers\ActivityController;

$router->get('/activities', [ActivityController::class, 'index']);
$router->get('/activities/create', [ActivityController::class, 'create']);
$router->post('/activities', [ActivityController::class, 'store']);
$router->get('/activities/{id}', [ActivityController::class, 'show']);
$router->get('/activities/{id}/edit', [ActivityController::class, 'edit']);
$router->post('/activities/{id}', [ActivityController::class, 'update']);
$router->post('/activities/{id}/status', [ActivityController::class, 'updateStatus']);
