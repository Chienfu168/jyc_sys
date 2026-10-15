<?php

use App\Modules\Schools\Controllers\SchoolController;

$router->get('/schools', [SchoolController::class, 'index']);
$router->get('/schools/create', [SchoolController::class, 'create']);
$router->post('/schools', [SchoolController::class, 'store']);
$router->get('/schools/{id}', [SchoolController::class, 'show']);
$router->get('/schools/{id}/edit', [SchoolController::class, 'edit']);
$router->post('/schools/{id}', [SchoolController::class, 'update']);
$router->post('/schools/{id}/toggle', [SchoolController::class, 'toggle']);
$router->post('/schools/{id}/delete', [SchoolController::class, 'destroy']);

$router->post('/schools/{id}/staff', [SchoolController::class, 'storeStaff']);
$router->get('/schools/{id}/staff/{staffId}/edit', [SchoolController::class, 'editStaff']);
$router->post('/schools/{id}/staff/{staffId}', [SchoolController::class, 'updateStaff']);
$router->post('/schools/{id}/staff/{staffId}/toggle', [SchoolController::class, 'toggleStaff']);
$router->post('/schools/{id}/staff/{staffId}/delete', [SchoolController::class, 'destroyStaff']);
