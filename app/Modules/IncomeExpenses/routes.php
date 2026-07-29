<?php

use App\Modules\IncomeExpenses\Controllers\IncomeExpenseController;

$router->get('/income-expenses', [IncomeExpenseController::class, 'index']);
$router->get('/income-expenses/report', [IncomeExpenseController::class, 'report']);
$router->get('/income-expenses/create', [IncomeExpenseController::class, 'create']);
$router->post('/income-expenses', [IncomeExpenseController::class, 'store']);
$router->get('/income-expenses/{id}', [IncomeExpenseController::class, 'show']);
$router->get('/income-expenses/{id}/edit', [IncomeExpenseController::class, 'edit']);
$router->post('/income-expenses/{id}', [IncomeExpenseController::class, 'update']);
$router->post('/income-expenses/{id}/submit', [IncomeExpenseController::class, 'submit']);
$router->post('/income-expenses/{id}/approve', [IncomeExpenseController::class, 'approve']);
$router->post('/income-expenses/{id}/reject', [IncomeExpenseController::class, 'reject']);
$router->post('/income-expenses/{id}/void', [IncomeExpenseController::class, 'void']);
$router->post('/income-expenses/{id}/voucher', [IncomeExpenseController::class, 'createVoucher']);
