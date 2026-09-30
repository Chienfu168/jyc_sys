<?php

use App\Modules\SystemUpdate\Controllers\SystemUpdateController;

$router->get('/system-update', [SystemUpdateController::class, 'index']);
$router->get('/system-update/database', [SystemUpdateController::class, 'database']);
$router->post('/system-update/migrate', [SystemUpdateController::class, 'migrate']);
$router->post('/system-update/check', [SystemUpdateController::class, 'check']);
$router->post('/system-update/download', [SystemUpdateController::class, 'download']);
$router->post('/system-update/apply', [SystemUpdateController::class, 'apply']);
$router->post('/system-update/clear-maintenance-lock', [SystemUpdateController::class, 'clearMaintenanceLock']);
