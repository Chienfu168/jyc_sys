<?php

use App\Modules\LecturerExpenses\Controllers\LecturerExpenseController;

$router->get('/lecturer-expenses', [LecturerExpenseController::class, 'index']);
$router->get('/lecturer-expenses/create', [LecturerExpenseController::class, 'create']);
$router->get('/lecturer-expenses/reports/monthly', [LecturerExpenseController::class, 'monthlyReport']);
$router->get('/lecturer-expenses/reports/bank-payout', [LecturerExpenseController::class, 'bankPayoutReport']);
$router->post('/lecturer-expenses', [LecturerExpenseController::class, 'store']);
$router->get('/lecturer-expenses/{id}', [LecturerExpenseController::class, 'show']);
$router->get('/lecturer-expenses/{id}/edit', [LecturerExpenseController::class, 'edit']);
$router->post('/lecturer-expenses/{id}', [LecturerExpenseController::class, 'update']);
$router->post('/lecturer-expenses/{id}/voucher', [LecturerExpenseController::class, 'createVoucher']);
$router->post('/lecturer-expenses/{id}/mark-paid', [LecturerExpenseController::class, 'markPaid']);
$router->post('/lecturer-expenses/{id}/void', [LecturerExpenseController::class, 'void']);
$router->get('/lecturer-expenses/{id}/sessions/create', [LecturerExpenseController::class, 'sessionCreate']);
$router->post('/lecturer-expenses/{id}/sessions', [LecturerExpenseController::class, 'sessionStore']);
$router->get('/lecturer-expenses/{id}/sessions/{sessionId}/edit', [LecturerExpenseController::class, 'sessionEdit']);
$router->post('/lecturer-expenses/{id}/sessions/{sessionId}', [LecturerExpenseController::class, 'sessionUpdate']);
$router->post('/lecturer-expenses/{id}/sessions/{sessionId}/delete', [LecturerExpenseController::class, 'sessionDestroy']);
$router->get('/lecturer-expenses/{id}/copy', [LecturerExpenseController::class, 'copyCreate']);
$router->post('/lecturer-expenses/{id}/copy', [LecturerExpenseController::class, 'copyStore']);
$router->post('/lecturer-expenses/{id}/delete', [LecturerExpenseController::class, 'destroy']);
