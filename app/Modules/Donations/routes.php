<?php

use App\Modules\Donations\Controllers\DonationController;

$router->get('/donations', [DonationController::class, 'index']);
$router->get('/donations/report', [DonationController::class, 'report']);
$router->get('/donations/export', [DonationController::class, 'export']);
$router->get('/donations/create', [DonationController::class, 'create']);
$router->post('/donations', [DonationController::class, 'store']);
$router->get('/donations/{id}', [DonationController::class, 'show']);
$router->get('/donations/{id}/receipt', [DonationController::class, 'receipt']);
$router->get('/donations/{id}/edit', [DonationController::class, 'edit']);
$router->post('/donations/{id}', [DonationController::class, 'update']);
$router->post('/donations/{id}/void', [DonationController::class, 'void']);
$router->post('/donations/{id}/voucher', [DonationController::class, 'createVoucher']);
