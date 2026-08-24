<?php

use App\Modules\OperatingStatements\Controllers\OperatingStatementController;

$router->get('/operating-statements', [OperatingStatementController::class, 'index']);
$router->get('/operating-statements/create', [OperatingStatementController::class, 'create']);
$router->post('/operating-statements', [OperatingStatementController::class, 'store']);
$router->get('/operating-statements/{id}', [OperatingStatementController::class, 'show']);
$router->get('/operating-statements/{id}/edit', [OperatingStatementController::class, 'edit']);
$router->post('/operating-statements/{id}', [OperatingStatementController::class, 'update']);
$router->post('/operating-statements/{id}/delete', [OperatingStatementController::class, 'destroy']);
