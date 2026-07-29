<?php

use App\Modules\Volunteers\Controllers\VolunteerController;

$router->get('/volunteers', [VolunteerController::class, 'index']);
$router->get('/volunteers/create', [VolunteerController::class, 'create']);
$router->post('/volunteers', [VolunteerController::class, 'store']);
$router->get('/volunteers/{id}', [VolunteerController::class, 'show']);
$router->get('/volunteers/{id}/edit', [VolunteerController::class, 'edit']);
$router->post('/volunteers/{id}', [VolunteerController::class, 'update']);
$router->post('/volunteers/{id}/toggle', [VolunteerController::class, 'toggle']);
$router->get('/volunteers/{id}/service-logs/create', [VolunteerController::class, 'createServiceLog']);
$router->post('/volunteers/{id}/service-logs', [VolunteerController::class, 'storeServiceLog']);
$router->get('/volunteer-service-logs/{id}/edit', [VolunteerController::class, 'editServiceLog']);
$router->post('/volunteer-service-logs/{id}', [VolunteerController::class, 'updateServiceLog']);
