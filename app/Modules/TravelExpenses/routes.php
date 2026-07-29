<?php

use App\Modules\TravelExpenses\Controllers\TravelExpenseController;

$router->get('/travel-expenses', [TravelExpenseController::class, 'index']);
$router->get('/travel-expenses/create', [TravelExpenseController::class, 'create']);
$router->post('/travel-expenses', [TravelExpenseController::class, 'store']);
$router->get('/travel-expenses/{id}', [TravelExpenseController::class, 'show']);
$router->get('/travel-expenses/{id}/edit', [TravelExpenseController::class, 'edit']);
$router->post('/travel-expenses/{id}', [TravelExpenseController::class, 'update']);
$router->post('/travel-expenses/{id}/mark-paid', [TravelExpenseController::class, 'markPaid']);
$router->post('/travel-expenses/{id}/void', [TravelExpenseController::class, 'void']);
