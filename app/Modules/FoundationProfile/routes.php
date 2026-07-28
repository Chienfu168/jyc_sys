<?php

use App\Modules\FoundationProfile\Controllers\FoundationProfileController;

$router->get('/foundation-profile', [FoundationProfileController::class, 'edit']);
$router->post('/foundation-profile', [FoundationProfileController::class, 'update']);
