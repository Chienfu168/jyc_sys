<?php

use App\Modules\Roles\Controllers\RoleController;

$router->get('/roles', [RoleController::class, 'index']);
$router->get('/roles/create', [RoleController::class, 'create']);
$router->post('/roles', [RoleController::class, 'store']);
$router->get('/roles/{id}/edit', [RoleController::class, 'edit']);
$router->post('/roles/{id}', [RoleController::class, 'update']);
$router->post('/roles/{id}/delete', [RoleController::class, 'destroy']);
