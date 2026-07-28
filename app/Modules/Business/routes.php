<?php

use App\Modules\Business\Controllers\BusinessController;

$router->get('/work-plans', [BusinessController::class, 'workPlans']);
$router->get('/personnel', [BusinessController::class, 'personnel']);
$router->get('/activities', [BusinessController::class, 'activities']);
$router->get('/projects', [BusinessController::class, 'projects']);
$router->get('/lecturers', [BusinessController::class, 'lecturers']);
$router->get('/calendar', [BusinessController::class, 'calendar']);
