<?php

use App\Modules\Business\Controllers\BusinessController;

$router->get('/work-plans', [BusinessController::class, 'workPlans']);
$router->get('/personnel', [BusinessController::class, 'personnel']);
$router->get('/activities', [BusinessController::class, 'activities']);
$router->get('/projects', [BusinessController::class, 'projects']);
$router->get('/lecturers', [BusinessController::class, 'lecturers']);
$router->get('/calendar', [BusinessController::class, 'calendar']);
$router->get('/accounting', [BusinessController::class, 'accounting']);
$router->get('/petty-cash', [BusinessController::class, 'pettyCash']);
$router->get('/income-expenses', [BusinessController::class, 'incomeExpenses']);
$router->get('/lecturer-expenses', [BusinessController::class, 'lecturerExpenses']);
$router->get('/travel-expenses', [BusinessController::class, 'travelExpenses']);
$router->get('/payroll', [BusinessController::class, 'payroll']);
$router->get('/leave-requests', [BusinessController::class, 'leaveRequests']);
$router->get('/volunteers', [BusinessController::class, 'volunteers']);
