<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Report routes
    Route::resource('reports', \App\Domains\Report\Controllers\ReportController::class);
    
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('financial', [\App\Domains\Report\Controllers\ReportController::class, 'financial'])->name('financial');
        Route::get('tickets', [\App\Domains\Report\Controllers\ReportController::class, 'tickets'])->name('tickets');
        Route::get('assets', [\App\Domains\Report\Controllers\ReportController::class, 'assets'])->name('assets');
        Route::get('clients', [\App\Domains\Report\Controllers\ReportController::class, 'clients'])->name('clients');
        Route::get('projects', [\App\Domains\Report\Controllers\ReportController::class, 'projects'])->name('projects');
        Route::get('users', [\App\Domains\Report\Controllers\ReportController::class, 'users'])->name('users');
        Route::get('sentiment-analytics', [\App\Domains\Report\Http\Controllers\SentimentAnalyticsController::class, 'index'])->name('sentiment-analytics');

        // Additional report routes
        Route::get('category/{category}', [\App\Domains\Report\Controllers\ReportController::class, 'category'])->name('category');
        Route::get('builder/{reportId}', [\App\Domains\Report\Controllers\ReportController::class, 'builder'])->name('builder');
        Route::post('generate/{reportId}', [\App\Domains\Report\Controllers\ReportController::class, 'generate'])->name('generate');
        Route::post('save', [\App\Domains\Report\Controllers\ReportController::class, 'save'])->name('save');
        Route::post('schedule', [\App\Domains\Report\Controllers\ReportController::class, 'schedule'])->name('schedule');
        Route::get('scheduled', [\App\Domains\Report\Controllers\ReportController::class, 'scheduled'])->name('scheduled');

        // Tax Reporting Routes
        Route::prefix('tax')->name('tax.')->group(function () {
            Route::get('/', [\App\Domains\Financial\Controllers\TaxReportController::class, 'index'])->name('index');
            Route::get('/summary', [\App\Domains\Financial\Controllers\TaxReportController::class, 'summary'])->name('summary');
            Route::get('/jurisdictions', [\App\Domains\Financial\Controllers\TaxReportController::class, 'jurisdictions'])->name('jurisdictions');
            Route::get('/compliance', [\App\Domains\Financial\Controllers\TaxReportController::class, 'compliance'])->name('compliance');
            Route::get('/performance', [\App\Domains\Financial\Controllers\TaxReportController::class, 'performance'])->name('performance');
            Route::get('/export', [\App\Domains\Financial\Controllers\TaxReportController::class, 'export'])->name('export');
            Route::get('/api-data', [\App\Domains\Financial\Controllers\TaxReportController::class, 'apiData'])->name('api-data');
        });

        // Search route
        Route::get('/search', [\App\Domains\Core\Controllers\SearchController::class, 'search'])->name('search');

        // Global AJAX utility routes (authenticated)
        Route::get('shortcuts/active', [App\Domains\Financial\Controllers\QuoteController::class, 'getActiveShortcuts'])->name('shortcuts.active');
    });
});
