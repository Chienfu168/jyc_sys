<?php

use App\Modules\BankAccounts\Controllers\BankAccountController;
use App\Modules\BankAccounts\Controllers\BankTransactionController;

$router->get('/bank-accounts', [BankAccountController::class, 'index']);
$router->get('/bank-accounts/create', [BankAccountController::class, 'create']);
$router->post('/bank-accounts', [BankAccountController::class, 'store']);
$router->get('/bank-accounts/{id}/edit', [BankAccountController::class, 'edit']);
$router->post('/bank-accounts/{id}', [BankAccountController::class, 'update']);
$router->post('/bank-accounts/{id}/toggle', [BankAccountController::class, 'toggle']);
$router->post('/bank-accounts/{id}/delete', [BankAccountController::class, 'destroy']);

$router->get('/bank-transactions', [BankTransactionController::class, 'index']);
$router->get('/bank-transactions/reconciliation', [BankTransactionController::class, 'reconciliation']);
$router->get('/bank-transactions/create', [BankTransactionController::class, 'create']);
$router->post('/bank-transactions', [BankTransactionController::class, 'store']);
$router->post('/bank-transactions/{id}/voucher', [BankTransactionController::class, 'createVoucher']);
$router->post('/bank-transactions/{id}/reconciliation', [BankTransactionController::class, 'updateReconciliation']);
