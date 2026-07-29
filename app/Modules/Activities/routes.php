<?php

use App\Modules\Activities\Controllers\ActivityController;

$router->get('/activities', [ActivityController::class, 'index']);
$router->get('/activities/create', [ActivityController::class, 'create']);
$router->post('/activities', [ActivityController::class, 'store']);
$router->get('/activities/{id}', [ActivityController::class, 'show']);
$router->get('/activities/{id}/edit', [ActivityController::class, 'edit']);
$router->post('/activities/{id}', [ActivityController::class, 'update']);
$router->post('/activities/{id}/status', [ActivityController::class, 'updateStatus']);
$router->post('/activities/{id}/participants', [ActivityController::class, 'storeParticipant']);
$router->post('/activities/{id}/participants/{participantId}/status', [ActivityController::class, 'updateParticipantStatus']);
$router->post('/activities/{id}/outcome', [ActivityController::class, 'updateOutcome']);
$router->post('/activities/{id}/attachments', [ActivityController::class, 'storeAttachment']);
$router->get('/activities/{id}/attachments/{attachmentId}/download', [ActivityController::class, 'downloadAttachment']);
