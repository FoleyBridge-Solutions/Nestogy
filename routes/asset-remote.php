<?php

use App\Domains\Asset\Controllers\AssetRemoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Asset Remote Management Routes
|--------------------------------------------------------------------------
|
| Routes for comprehensive remote asset management through RMM systems.
| These routes provide device control without needing to access RMM directly.
|
*/

Route::middleware(['auth', 'company'])->group(function () {
    
    // Asset remote management dashboard
    Route::get('/assets/{asset}/remote', [AssetRemoteController::class, 'dashboard'])
        ->name('assets.remote.dashboard');
    
    // Device status and information
    Route::get('/assets/{asset}/remote/status', [AssetRemoteController::class, 'getStatus'])
        ->name('assets.remote.status');
    
    Route::get('/assets/{asset}/remote/inventory', [AssetRemoteController::class, 'getInventory'])
        ->name('assets.remote.inventory');
    
    Route::post('/assets/{asset}/remote/sync', [AssetRemoteController::class, 'sync'])
        ->name('assets.remote.sync');
    
    // Process management
    Route::get('/assets/{asset}/remote/processes', [AssetRemoteController::class, 'getProcesses'])
        ->name('assets.remote.processes');
    
    Route::delete('/assets/{asset}/remote/processes', [AssetRemoteController::class, 'killProcess'])
        ->name('assets.remote.kill-process');
    
    // Service management
    Route::get('/assets/{asset}/remote/services', [AssetRemoteController::class, 'getServices'])
        ->name('assets.remote.services');
    
    Route::post('/assets/{asset}/remote/services', [AssetRemoteController::class, 'manageService'])
        ->name('assets.remote.manage-service');
    
    // Windows updates
    Route::get('/assets/{asset}/remote/updates', [AssetRemoteController::class, 'getUpdates'])
        ->name('assets.remote.updates');
    
    Route::post('/assets/{asset}/remote/updates/scan', [AssetRemoteController::class, 'scanUpdates'])
        ->name('assets.remote.scan-updates');
    
    Route::post('/assets/{asset}/remote/updates/install', [AssetRemoteController::class, 'installUpdates'])
        ->name('assets.remote.install-updates');
    
    // Remote control
    Route::post('/assets/{asset}/remote/command', [AssetRemoteController::class, 'executeCommand'])
        ->name('assets.remote.execute-command');
    
    Route::post('/assets/{asset}/remote/reboot', [AssetRemoteController::class, 'reboot'])
        ->name('assets.remote.reboot');
    
});