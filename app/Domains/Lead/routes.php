<?php
// Lead management routes

use Illuminate\Support\Facades\Route;
use App\Domains\Lead\Controllers\LeadController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Lead management routes
    Route::resource('leads', LeadController::class);
    
    // Additional lead routes
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('dashboard', [LeadController::class, 'dashboard'])->name('dashboard');
        Route::post('bulk-assign', [LeadController::class, 'bulkAssign'])->name('bulk-assign');
        Route::post('bulk-status', [LeadController::class, 'bulkUpdateStatus'])->name('bulk-status');
        Route::get('export/csv', [LeadController::class, 'exportCsv'])->name('export.csv');
        
        // Lead import
        Route::get('import', [LeadController::class, 'importForm'])->name('import.form');
        Route::post('import', [LeadController::class, 'import'])->name('import');
        Route::get('import/template', [LeadController::class, 'downloadTemplate'])->name('import.template');
        
        Route::prefix('{lead}')->group(function () {
            Route::post('convert', [LeadController::class, 'convertToClient'])->name('convert');
            Route::post('update-score', [LeadController::class, 'updateScore'])->name('update-score');
            Route::post('activities', [LeadController::class, 'logActivity'])->name('activities.store');
            Route::post('notes', [LeadController::class, 'addNote'])->name('notes.store');
            Route::post('tags', [LeadController::class, 'manageTags'])->name('tags.manage');
            Route::get('timeline', [LeadController::class, 'timeline'])->name('timeline');
        });
    });
});