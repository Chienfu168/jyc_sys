<?php

use App\Modules\PettyCash\Controllers\PettyCashController;
use App\Modules\PettyCash\Controllers\PettyCashItemController;

$router->get('/petty-cash', [PettyCashController::class, 'index']);
$router->get('/petty-cash/report', [PettyCashController::class, 'report']);
$router->get('/petty-cash/create', [PettyCashController::class, 'create']);
$router->post('/petty-cash', [PettyCashController::class, 'store']);
$router->post('/petty-cash/{id}/submit', [PettyCashController::class, 'submit']);
$router->post('/petty-cash/{id}/approve', [PettyCashController::class, 'approve']);
$router->post('/petty-cash/{id}/reject', [PettyCashController::class, 'reject']);
$router->post('/petty-cash/{id}/voucher', [PettyCashController::class, 'createVoucher']);
$router->get('/petty-cash/{id}/edit', [PettyCashController::class, 'edit']);
$router->post('/petty-cash/{id}', [PettyCashController::class, 'update']);
$router->get('/petty-cash/{id}', [PettyCashController::class, 'show']);

$router->get('/petty-cash-items', [PettyCashItemController::class, 'index']);
$router->get('/petty-cash-items/create', [PettyCashItemController::class, 'create']);
$router->post('/petty-cash-items', [PettyCashItemController::class, 'store']);
$router->get('/petty-cash-items/{id}/edit', [PettyCashItemController::class, 'edit']);
$router->post('/petty-cash-items/{id}', [PettyCashItemController::class, 'update']);
$router->post('/petty-cash-items/{id}/toggle', [PettyCashItemController::class, 'toggle']);
