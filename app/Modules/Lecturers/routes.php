<?php

use App\Modules\Lecturers\Controllers\LecturerController;

$router->get('/lecturers', [LecturerController::class, 'index']);
$router->get('/lecturers/create', [LecturerController::class, 'create']);
$router->post('/lecturers', [LecturerController::class, 'store']);
$router->get('/lecturers/{id}', [LecturerController::class, 'show']);
$router->get('/lecturers/{id}/edit', [LecturerController::class, 'edit']);
$router->post('/lecturers/{id}', [LecturerController::class, 'update']);
$router->post('/lecturers/{id}/toggle', [LecturerController::class, 'toggle']);
$router->post('/lecturers/{id}/delete', [LecturerController::class, 'destroy']);
