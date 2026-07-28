<?php

use App\Modules\WorkPlans\Controllers\WorkPlanController;

$router->get('/work-plans', [WorkPlanController::class, 'index']);
$router->get('/work-plans/create', [WorkPlanController::class, 'create']);
$router->post('/work-plans', [WorkPlanController::class, 'store']);
$router->get('/work-plans/{id}', [WorkPlanController::class, 'show']);
$router->get('/work-plans/{id}/edit', [WorkPlanController::class, 'edit']);
$router->post('/work-plans/{id}', [WorkPlanController::class, 'update']);
$router->post('/work-plans/{id}/approve', [WorkPlanController::class, 'approve']);
