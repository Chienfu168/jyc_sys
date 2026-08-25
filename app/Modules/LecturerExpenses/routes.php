<?php

use App\Modules\LecturerExpenses\Controllers\LecturerExpenseController;

$router->get('/lecturer-expenses', [LecturerExpenseController::class, 'index']);
$router->get('/lecturer-expenses/create', [LecturerExpenseController::class, 'create']);
$router->post('/lecturer-expenses', [LecturerExpenseController::class, 'store']);
$router->get('/lecturer-expenses/{id}', [LecturerExpenseController::class, 'show']);
$router->get('/lecturer-expenses/{id}/edit', [LecturerExpenseController::class, 'edit']);
$router->post('/lecturer-expenses/{id}', [LecturerExpenseController::class, 'update']);
$router->post('/lecturer-expenses/{id}/voucher', [LecturerExpenseController::class, 'createVoucher']);
$router->post('/lecturer-expenses/{id}/mark-paid', [LecturerExpenseController::class, 'markPaid']);
$router->post('/lecturer-expenses/{id}/void', [LecturerExpenseController::class, 'void']);
$router->post('/lecturer-expenses/{id}/delete', [LecturerExpenseController::class, 'destroy']);
