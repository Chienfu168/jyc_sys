<?php

use App\Modules\Calendar\Controllers\CalendarController;
use App\Modules\Calendar\Controllers\CalendarFeedController;

$router->get('/calendar-feeds', [CalendarFeedController::class, 'index']);
$router->get('/calendar-feeds/create', [CalendarFeedController::class, 'create']);
$router->post('/calendar-feeds', [CalendarFeedController::class, 'store']);
$router->post('/calendar-feeds/sync-all', [CalendarFeedController::class, 'syncAll']);
$router->get('/calendar-feeds/{id}/edit', [CalendarFeedController::class, 'edit']);
$router->post('/calendar-feeds/{id}', [CalendarFeedController::class, 'update']);
$router->post('/calendar-feeds/{id}/toggle', [CalendarFeedController::class, 'toggle']);
$router->post('/calendar-feeds/{id}/sync', [CalendarFeedController::class, 'sync']);
$router->post('/calendar-feeds/{id}/delete', [CalendarFeedController::class, 'destroy']);

$router->get('/calendar', [CalendarController::class, 'index']);
$router->get('/calendar/create', [CalendarController::class, 'create']);
$router->post('/calendar', [CalendarController::class, 'store']);
$router->get('/calendar/{id}', [CalendarController::class, 'show']);
$router->get('/calendar/{id}/edit', [CalendarController::class, 'edit']);
$router->post('/calendar/{id}', [CalendarController::class, 'update']);
$router->post('/calendar/{id}/status', [CalendarController::class, 'updateStatus']);
$router->post('/calendar/{id}/delete', [CalendarController::class, 'destroy']);
