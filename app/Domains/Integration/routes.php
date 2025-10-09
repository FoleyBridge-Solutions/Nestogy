<?php

// RMM Integration API (for settings page AJAX calls)

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'company'])->prefix('api/rmm')->name('api.rmm.')->group(function () {
    // RMM Integration CRUD
    Route::get('integrations', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'index'])->name('integrations.index');
    Route::post('integrations', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'store'])->name('integrations.store');
    Route::get('integrations/{integration}', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'show'])->name('integrations.show');
    Route::put('integrations/{integration}', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'update'])->name('integrations.update');
    Route::delete('integrations/{integration}', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'destroy'])->name('integrations.destroy');

    // RMM Integration Actions
    Route::post('test-connection', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'testConnection'])->name('test-connection');
    Route::post('integrations/{integration}/test-connection', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'testExistingConnection'])->name('integrations.test-connection');
    Route::post('integrations/{integration}/sync-agents', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'syncAgents'])->name('integrations.sync-agents');
    Route::post('integrations/{integration}/sync-alerts', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'syncAlerts'])->name('integrations.sync-alerts');
    Route::patch('integrations/{integration}/toggle', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'toggleStatus'])->name('integrations.toggle');

    // Simplified routes for frontend - bypass authorization since we filter by company
    Route::post('sync-agents', [App\Domains\Integration\Controllers\RmmClientController::class, 'syncAgents'])->name('sync-agents');
    Route::post('sync-alerts', [App\Domains\Integration\Controllers\RmmClientController::class, 'syncAlerts'])->name('sync-alerts');

    // Get available RMM types
    Route::get('types', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'getAvailableTypes'])->name('types');

    // Get integration statistics
    Route::get('stats', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'getStats'])->name('stats');

    // Client mapping endpoints
    Route::get('clients/nestogy', [App\Domains\Integration\Controllers\RmmClientController::class, 'getNestogyClients'])->name('clients.nestogy');
    Route::get('clients/rmm', [App\Domains\Integration\Controllers\RmmClientController::class, 'getRmmClients'])->name('clients.rmm');
    Route::post('client-mappings', [App\Domains\Integration\Controllers\RmmClientController::class, 'storeClientMapping'])->name('client-mappings.store');
    Route::delete('client-mappings/{mappingId}', [App\Domains\Integration\Controllers\RmmClientController::class, 'destroyClientMapping'])->name('client-mappings.destroy');
});
