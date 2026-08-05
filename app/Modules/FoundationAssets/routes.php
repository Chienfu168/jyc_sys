<?php

use App\Modules\FoundationAssets\Controllers\FoundationAssetController;

$router->get('/foundation-assets', [FoundationAssetController::class, 'index']);
$router->get('/foundation-assets/create', [FoundationAssetController::class, 'create']);
$router->post('/foundation-assets', [FoundationAssetController::class, 'store']);
$router->get('/foundation-assets/{id}', [FoundationAssetController::class, 'show']);
$router->get('/foundation-assets/{id}/edit', [FoundationAssetController::class, 'edit']);
$router->post('/foundation-assets/{id}', [FoundationAssetController::class, 'update']);
$router->post('/foundation-assets/{id}/dispose', [FoundationAssetController::class, 'dispose']);
