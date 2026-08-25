<?php

use App\Modules\Accounting\Controllers\AccountingController;
use App\Modules\Accounting\Controllers\AccountController;
use App\Modules\Accounting\Controllers\ReportController;
use App\Modules\Accounting\Controllers\VoucherController;

$router->get('/accounting', [AccountingController::class, 'index']);
$router->get('/accounting/reports/general-ledger', [ReportController::class, 'generalLedger']);
$router->get('/accounting/reports/detail-ledger', [ReportController::class, 'detailLedger']);
$router->get('/accounting/reports/trial-balance', [ReportController::class, 'trialBalance']);
$router->get('/accounting/reports/income-statement', [ReportController::class, 'incomeStatement']);
$router->get('/accounting/reports/balance-sheet', [ReportController::class, 'balanceSheet']);
$router->get('/accounting/reports/net-assets', [ReportController::class, 'netAssetsStatement']);
$router->get('/accounting/reports/cash-flow', [ReportController::class, 'cashFlowStatement']);

$router->get('/accounting/accounts', [AccountController::class, 'index']);
$router->get('/accounting/accounts/create', [AccountController::class, 'create']);
$router->post('/accounting/accounts', [AccountController::class, 'store']);
$router->get('/accounting/accounts/{id}/edit', [AccountController::class, 'edit']);
$router->post('/accounting/accounts/{id}', [AccountController::class, 'update']);
$router->post('/accounting/accounts/{id}/toggle', [AccountController::class, 'toggle']);
$router->post('/accounting/accounts/{id}/delete', [AccountController::class, 'destroy']);

$router->get('/accounting/vouchers', [VoucherController::class, 'index']);
$router->get('/accounting/vouchers/create', [VoucherController::class, 'create']);
$router->post('/accounting/vouchers', [VoucherController::class, 'store']);
$router->get('/accounting/vouchers/{id}', [VoucherController::class, 'show']);
$router->get('/accounting/vouchers/{id}/edit', [VoucherController::class, 'edit']);
$router->post('/accounting/vouchers/{id}', [VoucherController::class, 'update']);
$router->post('/accounting/vouchers/{id}/post', [VoucherController::class, 'post']);
$router->post('/accounting/vouchers/{id}/void', [VoucherController::class, 'void']);
$router->post('/accounting/vouchers/{id}/delete', [VoucherController::class, 'destroy']);
