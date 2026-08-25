<?php

use App\Modules\NetAssetStatements\Controllers\NetAssetStatementController;

$router->get('/net-asset-statements', [NetAssetStatementController::class, 'index']);
$router->get('/net-asset-statements/create', [NetAssetStatementController::class, 'create']);
$router->post('/net-asset-statements', [NetAssetStatementController::class, 'store']);
$router->get('/net-asset-statements/{id}', [NetAssetStatementController::class, 'show']);
$router->get('/net-asset-statements/{id}/edit', [NetAssetStatementController::class, 'edit']);
$router->post('/net-asset-statements/{id}', [NetAssetStatementController::class, 'update']);
$router->post('/net-asset-statements/{id}/delete', [NetAssetStatementController::class, 'destroy']);
