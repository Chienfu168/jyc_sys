<?php

use App\Modules\Approvals\Controllers\ApprovalController;

$router->get('/approvals', [ApprovalController::class, 'index']);
