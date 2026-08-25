<?php

use App\Modules\Donors\Controllers\DonorController;

$router->get('/donors', [DonorController::class, 'index']);
$router->get('/donors/create', [DonorController::class, 'create']);
$router->post('/donors', [DonorController::class, 'store']);
$router->get('/donors/{id}', [DonorController::class, 'show']);
$router->get('/donors/{id}/edit', [DonorController::class, 'edit']);
$router->post('/donors/{id}', [DonorController::class, 'update']);
$router->post('/donors/{id}/toggle', [DonorController::class, 'toggle']);
$router->post('/donors/{id}/delete', [DonorController::class, 'destroy']);
