<?php

use App\Modules\Certificates\Controllers\CertificateController;

$router->get('/certificates', [CertificateController::class, 'index']);
$router->get('/certificates/create', [CertificateController::class, 'create']);
$router->post('/certificates', [CertificateController::class, 'store']);
$router->get('/certificates/{id}', [CertificateController::class, 'show']);
$router->get('/certificates/{id}/edit', [CertificateController::class, 'edit']);
$router->get('/certificates/{id}/duplicate', [CertificateController::class, 'duplicate']);
$router->post('/certificates/{id}', [CertificateController::class, 'update']);
$router->post('/certificates/{id}/delete', [CertificateController::class, 'destroy']);
