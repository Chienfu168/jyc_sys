<?php

use App\Modules\Business\Controllers\BusinessController;

$router->get('/finance', [BusinessController::class, 'finance']);
$router->get('/operations', [BusinessController::class, 'operations']);
