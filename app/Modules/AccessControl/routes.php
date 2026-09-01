<?php

use App\Modules\AccessControl\Controllers\AccessControlController;

$router->get('/access-control', [AccessControlController::class, 'index']);
$router->post('/access-control', [AccessControlController::class, 'update']);
$router->post('/access-control/log/clear', [AccessControlController::class, 'clearLog']);
$router->get('/access-control/blocked', [AccessControlController::class, 'blockedIps']);
$router->post('/access-control/blocked/block', [AccessControlController::class, 'block']);
$router->post('/access-control/blocked/unblock', [AccessControlController::class, 'unblock']);
