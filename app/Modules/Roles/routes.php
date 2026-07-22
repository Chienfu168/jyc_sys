<?php

use App\Modules\Roles\Controllers\RoleController;

$router->get('/roles', [RoleController::class, 'index']);
