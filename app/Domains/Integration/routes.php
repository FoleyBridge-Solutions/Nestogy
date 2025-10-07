<?php

use App\Domains\Integration\Controllers\RmmIntegrationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'company'])->prefix('api/rmm')->name('api.rmm.')->group(function () {
    Route::get('integrations', [RmmIntegrationsController::class, 'index'])->name('integrations.index');
    Route::post('integrations', [RmmIntegrationsController::class, 'store'])->name('integrations.store');
    Route::get('integrations/{integration}', [RmmIntegrationsController::class, 'show'])->name('integrations.show');
    Route::put('integrations/{integration}', [RmmIntegrationsController::class, 'update'])->name('integrations.update');
    Route::delete('integrations/{integration}', [RmmIntegrationsController::class, 'destroy'])->name('integrations.destroy');

    Route::post('test-connection', [RmmIntegrationsController::class, 'testConnection'])->name('test-connection');
    Route::post('integrations/{integration}/test-connection', [RmmIntegrationsController::class, 'testExistingConnection'])->name('integrations.test-connection');
    Route::post('integrations/{integration}/sync-agents', [RmmIntegrationsController::class, 'syncAgents'])->name('integrations.sync-agents');
    Route::post('integrations/{integration}/sync-alerts', [RmmIntegrationsController::class, 'syncAlerts'])->name('integrations.sync-alerts');
    Route::patch('integrations/{integration}/toggle', [RmmIntegrationsController::class, 'toggleStatus'])->name('integrations.toggle');

    Route::post('sync-agents', [RmmIntegrationsController::class, 'quickSyncAgents'])->name('sync-agents');
    Route::post('sync-alerts', [RmmIntegrationsController::class, 'quickSyncAlerts'])->name('sync-alerts');

    Route::get('types', [RmmIntegrationsController::class, 'getAvailableTypes'])->name('types');
    Route::get('stats', [RmmIntegrationsController::class, 'getStats'])->name('stats');

    Route::get('clients/nestogy', [RmmIntegrationsController::class, 'getNestogyClients'])->name('clients.nestogy');
    Route::get('clients/rmm', [RmmIntegrationsController::class, 'getRmmClients'])->name('clients.rmm');
    Route::post('client-mappings', [RmmIntegrationsController::class, 'storeClientMapping'])->name('client-mappings.store');
    Route::delete('client-mappings/{mappingId}', [RmmIntegrationsController::class, 'destroyClientMapping'])->name('client-mappings.destroy');
});
