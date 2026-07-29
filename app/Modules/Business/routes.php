<?php

use App\Modules\Business\Controllers\BusinessController;

$router->get('/finance', [BusinessController::class, 'finance']);
$router->get('/operations', [BusinessController::class, 'operations']);
$router->get('/activities', [BusinessController::class, 'activities']);
$router->get('/projects', [BusinessController::class, 'projects']);
$router->get('/calendar', [BusinessController::class, 'calendar']);
$router->get('/volunteers', [BusinessController::class, 'volunteers']);
