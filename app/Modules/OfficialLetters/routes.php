<?php

use App\Modules\OfficialLetters\Controllers\OfficialLetterController;

$router->get('/official-letters', [OfficialLetterController::class, 'index']);
$router->get('/official-letters/create', [OfficialLetterController::class, 'create']);
$router->post('/official-letters', [OfficialLetterController::class, 'store']);
$router->get('/official-letters/{id}', [OfficialLetterController::class, 'show']);
$router->get('/official-letters/{id}/edit', [OfficialLetterController::class, 'edit']);
$router->post('/official-letters/{id}', [OfficialLetterController::class, 'update']);
$router->post('/official-letters/{id}/issue', [OfficialLetterController::class, 'issue']);
$router->post('/official-letters/{id}/delete', [OfficialLetterController::class, 'destroy']);
