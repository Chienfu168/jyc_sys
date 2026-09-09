<?php

use App\Modules\Contacts\Controllers\ContactController;

$router->get('/contacts', [ContactController::class, 'index']);
$router->get('/contacts/create', [ContactController::class, 'create']);
$router->post('/contacts', [ContactController::class, 'store']);
$router->get('/contacts/{id}', [ContactController::class, 'show']);
$router->get('/contacts/{id}/edit', [ContactController::class, 'edit']);
$router->post('/contacts/{id}', [ContactController::class, 'update']);
$router->post('/contacts/{id}/toggle', [ContactController::class, 'toggle']);
$router->post('/contacts/{id}/delete', [ContactController::class, 'destroy']);
