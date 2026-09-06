<?php

use App\Modules\BankSlips\Controllers\BankSlipController;

$router->get('/bank-slips', [BankSlipController::class, 'index']);
$router->get('/bank-slips/settings', [BankSlipController::class, 'settings']);
$router->post('/bank-slips/settings', [BankSlipController::class, 'saveSettings']);
$router->get('/bank-slips/create', [BankSlipController::class, 'create']);
$router->post('/bank-slips', [BankSlipController::class, 'store']);
$router->get('/bank-slips/{id}', [BankSlipController::class, 'show']);
$router->post('/bank-slips/{id}/delete', [BankSlipController::class, 'destroy']);
