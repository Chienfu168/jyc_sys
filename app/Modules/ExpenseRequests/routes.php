<?php

use App\Modules\ExpenseRequests\Controllers\ExpenseRequestController;

$router->get('/expense-requests', [ExpenseRequestController::class, 'index']);
$router->get('/expense-requests/create', [ExpenseRequestController::class, 'create']);
$router->post('/expense-requests', [ExpenseRequestController::class, 'store']);
$router->get('/expense-requests/{id}', [ExpenseRequestController::class, 'show']);
$router->get('/expense-requests/{id}/edit', [ExpenseRequestController::class, 'edit']);
$router->post('/expense-requests/{id}', [ExpenseRequestController::class, 'update']);
$router->post('/expense-requests/{id}/submit', [ExpenseRequestController::class, 'submit']);
$router->post('/expense-requests/{id}/approve', [ExpenseRequestController::class, 'approve']);
$router->post('/expense-requests/{id}/reject', [ExpenseRequestController::class, 'reject']);
$router->post('/expense-requests/{id}/pay', [ExpenseRequestController::class, 'pay']);
$router->post('/expense-requests/{id}/delete', [ExpenseRequestController::class, 'destroy']);
$router->post('/expense-requests/{id}/attachments', [ExpenseRequestController::class, 'uploadAttachment']);
$router->get('/expense-requests/{id}/attachments/{fileId}', [ExpenseRequestController::class, 'downloadAttachment']);
$router->post('/expense-requests/{id}/attachments/{fileId}/delete', [ExpenseRequestController::class, 'deleteAttachment']);
