<?php

use App\Modules\AccessControl\Controllers\AccessControlController;

$router->get('/access-control', [AccessControlController::class, 'index']);
$router->post('/access-control', [AccessControlController::class, 'update']);
$router->post('/access-control/log/clear', [AccessControlController::class, 'clearLog']);
