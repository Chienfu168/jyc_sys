<?php

use App\Modules\Account\Controllers\ProfileController;

$router->get('/account/profile', [ProfileController::class, 'edit']);
$router->post('/account/profile', [ProfileController::class, 'update']);
