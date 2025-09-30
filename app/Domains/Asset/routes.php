<?php

// Asset routes

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Define specific routes before resource routes to avoid conflicts
    Route::get('assets/export', [\App\Domains\Asset\Controllers\AssetController::class, 'export'])->name('assets.export');
    Route::get('assets/import', [\App\Domains\Asset\Controllers\AssetController::class, 'importForm'])->name('assets.import.form');
    Route::post('assets/import', [\App\Domains\Asset\Controllers\AssetController::class, 'import'])->name('assets.import');
    Route::get('assets/template/download', [\App\Domains\Asset\Controllers\AssetController::class, 'downloadTemplate'])->name('assets.template.download');
    Route::get('assets/checkinout', [\App\Domains\Asset\Controllers\AssetController::class, 'checkinoutManagement'])->name('assets.checkinout');
    Route::post('assets/bulk-checkinout', [\App\Domains\Asset\Controllers\AssetController::class, 'bulkCheckinout'])->name('assets.bulk-checkinout');
    Route::get('assets/filter', [\App\Domains\Asset\Controllers\AssetController::class, 'getAssetsByFilter'])->name('assets.filter');
    Route::get('assets/metrics', [\App\Domains\Asset\Controllers\AssetController::class, 'getMetrics'])->name('assets.metrics');
    Route::get('assets/bulk', [\App\Domains\Asset\Controllers\AssetController::class, 'bulk'])->name('assets.bulk');

    Route::resource('assets', \App\Domains\Asset\Controllers\AssetController::class);
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::resource('maintenance', \App\Domains\Asset\Controllers\MaintenanceController::class);
        Route::resource('warranties', \App\Domains\Asset\Controllers\WarrantyController::class);
        Route::resource('depreciation', \App\Domains\Asset\Controllers\DepreciationController::class);
        Route::match(['POST', 'PATCH'], 'bulk/update', [\App\Domains\Asset\Controllers\AssetController::class, 'bulkUpdate'])->name('bulk.update');
        Route::get('{asset}/qr-code', [\App\Domains\Asset\Controllers\AssetController::class, 'qrCode'])->name('qr-code');
        Route::get('{asset}/label', [\App\Domains\Asset\Controllers\AssetController::class, 'label'])->name('label');
        Route::post('{asset}/archive', [\App\Domains\Asset\Controllers\AssetController::class, 'archive'])->name('archive');
        Route::post('{asset}/check-in-out', [\App\Domains\Asset\Controllers\AssetController::class, 'checkInOut'])->name('check-in-out');
    });
});
