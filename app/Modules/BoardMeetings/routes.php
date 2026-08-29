<?php

use App\Modules\BoardMeetings\Controllers\BoardMeetingController;

$router->get('/board-meetings', [BoardMeetingController::class, 'index']);
$router->get('/board-meetings/create', [BoardMeetingController::class, 'create']);
$router->post('/board-meetings', [BoardMeetingController::class, 'store']);
$router->get('/board-meetings/{id}', [BoardMeetingController::class, 'show']);
$router->get('/board-meetings/{id}/edit', [BoardMeetingController::class, 'edit']);
$router->post('/board-meetings/{id}', [BoardMeetingController::class, 'update']);
$router->post('/board-meetings/{id}/confirm', [BoardMeetingController::class, 'confirm']);
$router->get('/board-meetings/{id}/print', [BoardMeetingController::class, 'print']);
$router->post('/board-meetings/{id}/files', [BoardMeetingController::class, 'uploadFile']);
$router->get('/board-meetings/{id}/files/{fileId}', [BoardMeetingController::class, 'downloadFile']);
$router->post('/board-meetings/{id}/files/{fileId}/delete', [BoardMeetingController::class, 'deleteFile']);
$router->post('/board-meetings/{id}/delete', [BoardMeetingController::class, 'destroy']);
