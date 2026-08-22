<?php

use App\Modules\PurchaseRequests\Controllers\PurchaseRequestController;

$router->get('/purchase-requests', [PurchaseRequestController::class, 'index']);
$router->get('/purchase-requests/create', [PurchaseRequestController::class, 'create']);
$router->post('/purchase-requests', [PurchaseRequestController::class, 'store']);
$router->get('/purchase-requests/{id}/print', [PurchaseRequestController::class, 'printForm']);
$router->get('/purchase-requests/{id}/edit', [PurchaseRequestController::class, 'edit']);
$router->post('/purchase-requests/{id}', [PurchaseRequestController::class, 'update']);
$router->post('/purchase-requests/{id}/submit', [PurchaseRequestController::class, 'submit']);
$router->post('/purchase-requests/{id}/approve', [PurchaseRequestController::class, 'approve']);
$router->post('/purchase-requests/{id}/reject', [PurchaseRequestController::class, 'reject']);
$router->post('/purchase-requests/{id}/mark-ordered', [PurchaseRequestController::class, 'markOrdered']);
$router->post('/purchase-requests/{id}/mark-received', [PurchaseRequestController::class, 'markReceived']);
$router->post('/purchase-requests/{id}/void', [PurchaseRequestController::class, 'void']);
$router->get('/purchase-requests/{id}', [PurchaseRequestController::class, 'show']);
