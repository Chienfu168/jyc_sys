<?php

use App\Modules\Charters\Controllers\CharterController;

$router->get('/charters', [CharterController::class, 'index']);
$router->get('/charters/create', [CharterController::class, 'create']);
$router->post('/charters', [CharterController::class, 'store']);
$router->get('/charters/{id}', [CharterController::class, 'show']);
$router->get('/charters/{id}/edit', [CharterController::class, 'edit']);
$router->post('/charters/{id}', [CharterController::class, 'update']);
$router->post('/charters/{id}/set-current', [CharterController::class, 'setCurrent']);
$router->get('/charters/{id}/file', [CharterController::class, 'downloadFile']);
$router->post('/charters/{id}/delete', [CharterController::class, 'destroy']);
