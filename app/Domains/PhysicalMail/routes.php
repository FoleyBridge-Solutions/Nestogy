<?php

use App\Domains\PhysicalMail\Controllers\PhysicalMailController;
use App\Domains\PhysicalMail\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// API routes for physical mail
Route::middleware(['api'])->prefix('api/physical-mail')->name('physical-mail.')->group(function () {
    // Test connection endpoint (no auth required for testing from settings page)
    Route::get('/test-connection', [PhysicalMailController::class, 'testConnection'])->name('test-connection');
});

Route::middleware(['api', 'auth:sanctum'])->prefix('api/physical-mail')->name('physical-mail.')->group(function () {

    // Mail orders
    Route::get('/', [PhysicalMailController::class, 'index'])->name('index');
    Route::post('/send', [PhysicalMailController::class, 'send'])->name('send');
    Route::get('/{order}', [PhysicalMailController::class, 'show'])->name('show');
    Route::post('/{order}/cancel', [PhysicalMailController::class, 'cancel'])->name('cancel');
    Route::get('/{order}/tracking', [PhysicalMailController::class, 'tracking'])->name('tracking');
    Route::post('/{order}/progress-test', [PhysicalMailController::class, 'progressTest'])->name('progress-test');

    // Special endpoints
    Route::post('/invoice/send', [PhysicalMailController::class, 'sendInvoice'])->name('invoice.send');
});

// Webhook endpoint (no auth required)
Route::post('/api/webhooks/postgrid', [WebhookController::class, 'handle'])->name('physical-mail.webhook');

// Web routes for physical mail management
Route::middleware(['web', 'auth'])->prefix('mail')->name('mail.')->group(function () {

    // Dashboard views with controller methods
    Route::get('/', [PhysicalMailController::class, 'webIndex'])->name('index');
    Route::get('/send', [PhysicalMailController::class, 'webSend'])->name('send');
    Route::get('/templates', [PhysicalMailController::class, 'webTemplates'])->name('templates');
    Route::get('/contacts', [PhysicalMailController::class, 'webContacts'])->name('contacts');
    Route::get('/tracking', [PhysicalMailController::class, 'webTracking'])->name('tracking');
});
