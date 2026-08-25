<?php

use App\Modules\CashFlowStatements\Controllers\CashFlowStatementController;

$router->get('/cash-flow-statements', [CashFlowStatementController::class, 'index']);
$router->get('/cash-flow-statements/create', [CashFlowStatementController::class, 'create']);
$router->post('/cash-flow-statements', [CashFlowStatementController::class, 'store']);
$router->get('/cash-flow-statements/{id}', [CashFlowStatementController::class, 'show']);
$router->get('/cash-flow-statements/{id}/edit', [CashFlowStatementController::class, 'edit']);
$router->post('/cash-flow-statements/{id}', [CashFlowStatementController::class, 'update']);
$router->post('/cash-flow-statements/{id}/delete', [CashFlowStatementController::class, 'destroy']);
