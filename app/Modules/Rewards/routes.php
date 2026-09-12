<?php

use App\Modules\Rewards\Controllers\RewardApplicationController;

$router->get('/rewards', [RewardApplicationController::class, 'index']);
$router->get('/rewards/create', [RewardApplicationController::class, 'create']);
$router->post('/rewards', [RewardApplicationController::class, 'store']);
$router->get('/rewards/{id}', [RewardApplicationController::class, 'show']);
$router->get('/rewards/{id}/edit', [RewardApplicationController::class, 'edit']);
$router->post('/rewards/{id}', [RewardApplicationController::class, 'update']);
$router->post('/rewards/{id}/submit', [RewardApplicationController::class, 'submit']);
$router->post('/rewards/{id}/approve', [RewardApplicationController::class, 'approve']);
$router->post('/rewards/{id}/reject', [RewardApplicationController::class, 'reject']);
$router->post('/rewards/{id}/delete', [RewardApplicationController::class, 'destroy']);
