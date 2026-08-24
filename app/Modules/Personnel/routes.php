<?php

use App\Modules\Personnel\Controllers\PersonnelController;

$router->get('/personnel', [PersonnelController::class, 'index']);
$router->get('/personnel/create', [PersonnelController::class, 'create']);
$router->post('/personnel', [PersonnelController::class, 'store']);
$router->get('/personnel/{id}', [PersonnelController::class, 'show']);
$router->get('/personnel/{id}/edit', [PersonnelController::class, 'edit']);
$router->post('/personnel/{id}', [PersonnelController::class, 'update']);
$router->post('/personnel/{id}/status', [PersonnelController::class, 'updateStatus']);
$router->post('/personnel/{id}/delete', [PersonnelController::class, 'destroy']);
