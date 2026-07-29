<?php

use App\Modules\Calendar\Controllers\CalendarController;

$router->get('/calendar', [CalendarController::class, 'index']);
$router->get('/calendar/create', [CalendarController::class, 'create']);
$router->post('/calendar', [CalendarController::class, 'store']);
$router->get('/calendar/{id}', [CalendarController::class, 'show']);
$router->get('/calendar/{id}/edit', [CalendarController::class, 'edit']);
$router->post('/calendar/{id}', [CalendarController::class, 'update']);
$router->post('/calendar/{id}/status', [CalendarController::class, 'updateStatus']);
