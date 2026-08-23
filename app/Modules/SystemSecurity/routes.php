<?php

use App\Modules\SystemSecurity\Controllers\SecurityCheckController;

$router->get('/system-security', [SecurityCheckController::class, 'index']);
