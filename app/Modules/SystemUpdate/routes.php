<?php

use App\Modules\SystemUpdate\Controllers\SystemUpdateController;

$router->get('/system-update', [SystemUpdateController::class, 'index']);
$router->post('/system-update/check', [SystemUpdateController::class, 'check']);
$router->post('/system-update/download', [SystemUpdateController::class, 'download']);
