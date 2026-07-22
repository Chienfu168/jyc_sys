<?php

use App\Modules\Dashboard\Controllers\DashboardController;

$router->get('/', [DashboardController::class, 'index']);
