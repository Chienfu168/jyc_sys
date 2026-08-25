<?php

use App\Modules\BalanceSheets\Controllers\BalanceSheetController;

$router->get('/balance-sheets', [BalanceSheetController::class, 'index']);
$router->get('/balance-sheets/create', [BalanceSheetController::class, 'create']);
$router->post('/balance-sheets', [BalanceSheetController::class, 'store']);
$router->get('/balance-sheets/{id}', [BalanceSheetController::class, 'show']);
$router->get('/balance-sheets/{id}/edit', [BalanceSheetController::class, 'edit']);
$router->post('/balance-sheets/{id}', [BalanceSheetController::class, 'update']);
$router->post('/balance-sheets/{id}/delete', [BalanceSheetController::class, 'destroy']);
