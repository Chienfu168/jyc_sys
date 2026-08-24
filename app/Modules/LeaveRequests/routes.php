<?php

use App\Modules\LeaveRequests\Controllers\LeaveRequestController;

$router->get('/leave-requests', [LeaveRequestController::class, 'index']);
$router->get('/leave-requests/create', [LeaveRequestController::class, 'create']);
$router->post('/leave-requests', [LeaveRequestController::class, 'store']);
$router->get('/leave-requests/{id}', [LeaveRequestController::class, 'show']);
$router->get('/leave-requests/{id}/edit', [LeaveRequestController::class, 'edit']);
$router->post('/leave-requests/{id}', [LeaveRequestController::class, 'update']);
$router->post('/leave-requests/{id}/submit', [LeaveRequestController::class, 'submit']);
$router->post('/leave-requests/{id}/approve', [LeaveRequestController::class, 'approve']);
$router->post('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject']);
$router->post('/leave-requests/{id}/cancel', [LeaveRequestController::class, 'cancel']);
$router->post('/leave-requests/{id}/delete', [LeaveRequestController::class, 'destroy']);
