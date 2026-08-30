<?php

use App\Modules\QuickLinks\Controllers\QuickLinkController;

$router->get('/quick-links', [QuickLinkController::class, 'edit']);
$router->post('/quick-links', [QuickLinkController::class, 'update']);
