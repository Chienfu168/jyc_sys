<?php

use App\Modules\Payroll\Controllers\PayrollController;

$router->get('/payroll', [PayrollController::class, 'index']);
$router->get('/payroll/create', [PayrollController::class, 'create']);
$router->post('/payroll', [PayrollController::class, 'store']);
$router->get('/payroll/{id}', [PayrollController::class, 'show']);
$router->get('/payroll/{id}/edit', [PayrollController::class, 'edit']);
$router->post('/payroll/{id}', [PayrollController::class, 'update']);
$router->post('/payroll/{id}/confirm', [PayrollController::class, 'confirm']);
$router->post('/payroll/{id}/mark-paid', [PayrollController::class, 'markPaid']);
$router->post('/payroll/{id}/void', [PayrollController::class, 'void']);
