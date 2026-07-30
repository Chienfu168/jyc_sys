<?php

use App\Modules\AnnualBudgets\Controllers\AnnualBudgetController;

$router->get('/annual-budgets', [AnnualBudgetController::class, 'index']);
$router->get('/annual-budgets/create', [AnnualBudgetController::class, 'create']);
$router->post('/annual-budgets', [AnnualBudgetController::class, 'store']);
$router->get('/annual-budgets/{id}/execution', [AnnualBudgetController::class, 'execution']);
$router->get('/annual-budgets/{id}/statement', [AnnualBudgetController::class, 'statement']);
$router->get('/annual-budgets/{id}', [AnnualBudgetController::class, 'show']);
$router->get('/annual-budgets/{id}/edit', [AnnualBudgetController::class, 'edit']);
$router->post('/annual-budgets/{id}', [AnnualBudgetController::class, 'update']);
$router->post('/annual-budgets/{id}/submit', [AnnualBudgetController::class, 'submit']);
$router->post('/annual-budgets/{id}/approve', [AnnualBudgetController::class, 'approve']);
$router->post('/annual-budgets/{id}/reject', [AnnualBudgetController::class, 'reject']);
