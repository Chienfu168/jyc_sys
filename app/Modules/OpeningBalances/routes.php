<?php

use App\Modules\OpeningBalances\Controllers\OpeningBalanceController;

$router->get('/opening-balances', [OpeningBalanceController::class, 'index']);
$router->post('/opening-balances', [OpeningBalanceController::class, 'save']);
$router->post('/opening-balances/delete', [OpeningBalanceController::class, 'destroy']);
