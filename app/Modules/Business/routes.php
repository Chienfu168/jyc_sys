<?php

use App\Modules\Business\Controllers\BusinessController;

$router->get('/finance', [BusinessController::class, 'finance']);
$router->get('/operations', [BusinessController::class, 'operations']);
$router->get('/personnel', [BusinessController::class, 'personnel']);
$router->get('/activities', [BusinessController::class, 'activities']);
$router->get('/projects', [BusinessController::class, 'projects']);
$router->get('/lecturers', [BusinessController::class, 'lecturers']);
$router->get('/calendar', [BusinessController::class, 'calendar']);
$router->get('/lecturer-expenses', [BusinessController::class, 'lecturerExpenses']);
$router->get('/travel-expenses', [BusinessController::class, 'travelExpenses']);
$router->get('/payroll', [BusinessController::class, 'payroll']);
$router->get('/leave-requests', [BusinessController::class, 'leaveRequests']);
$router->get('/volunteers', [BusinessController::class, 'volunteers']);
