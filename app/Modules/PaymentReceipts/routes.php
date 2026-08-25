<?php

use App\Modules\PaymentReceipts\Controllers\PaymentReceiptController;

$router->post('/income-expenses/{id}/payment-receipt', [PaymentReceiptController::class, 'createFromIncomeExpense']);
$router->post('/lecturer-expenses/{id}/payment-receipt', [PaymentReceiptController::class, 'createFromLecturerExpense']);
$router->get('/payment-receipts', [PaymentReceiptController::class, 'index']);
$router->get('/payment-receipts/create', [PaymentReceiptController::class, 'create']);
$router->post('/payment-receipts', [PaymentReceiptController::class, 'store']);
$router->get('/payment-receipts/{id}/print', [PaymentReceiptController::class, 'printForm']);
$router->get('/payment-receipts/{id}/edit', [PaymentReceiptController::class, 'edit']);
$router->post('/payment-receipts/{id}', [PaymentReceiptController::class, 'update']);
$router->post('/payment-receipts/{id}/issue', [PaymentReceiptController::class, 'issue']);
$router->post('/payment-receipts/{id}/void', [PaymentReceiptController::class, 'void']);
$router->post('/payment-receipts/{id}/delete', [PaymentReceiptController::class, 'destroy']);
$router->get('/payment-receipts/{id}', [PaymentReceiptController::class, 'show']);
