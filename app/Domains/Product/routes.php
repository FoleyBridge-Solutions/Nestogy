<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Product Management routes
    Route::prefix('products')->name('products.')->group(function () {
        // AJAX routes (must be before parameterized routes)
        Route::get('search', [App\Domains\Financial\Controllers\QuoteController::class, 'searchProducts'])->name('search');
        Route::get('categories', [App\Domains\Financial\Controllers\QuoteController::class, 'getProductCategories'])->name('categories');
        Route::get('/export/csv', [\App\Domains\Product\Controllers\ProductController::class, 'export'])->name('export');
        Route::get('/import/form', [\App\Domains\Product\Controllers\ProductController::class, 'import'])->name('import');
        Route::post('/import/process', [\App\Domains\Product\Controllers\ProductController::class, 'processImport'])->name('import.process');

        Route::get('/', [\App\Domains\Product\Controllers\ProductController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Product\Controllers\ProductController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Product\Controllers\ProductController::class, 'store'])->name('store');
        Route::get('/{product}', [\App\Domains\Product\Controllers\ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [\App\Domains\Product\Controllers\ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [\App\Domains\Product\Controllers\ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [\App\Domains\Product\Controllers\ProductController::class, 'destroy'])->name('destroy');
        Route::post('/{product}/duplicate', [\App\Domains\Product\Controllers\ProductController::class, 'duplicate'])->name('duplicate');
        Route::post('/bulk-update', [\App\Domains\Product\Controllers\ProductController::class, 'bulkUpdate'])->name('bulk-update');
    });

    // Bundle Management routes
    Route::prefix('bundles')->name('bundles.')->group(function () {
        Route::get('/', [\App\Domains\Product\Controllers\BundleController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Product\Controllers\BundleController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Product\Controllers\BundleController::class, 'store'])->name('store');
        Route::get('/{bundle}', [\App\Domains\Product\Controllers\BundleController::class, 'show'])->name('show');
        Route::get('/{bundle}/edit', [\App\Domains\Product\Controllers\BundleController::class, 'edit'])->name('edit');
        Route::put('/{bundle}', [\App\Domains\Product\Controllers\BundleController::class, 'update'])->name('update');
        Route::delete('/{bundle}', [\App\Domains\Product\Controllers\BundleController::class, 'destroy'])->name('destroy');
        Route::post('/{bundle}/calculate-price', [\App\Domains\Product\Controllers\BundleController::class, 'calculatePrice'])->name('calculate-price');
    });

    // Pricing Rules Management routes
    Route::prefix('pricing-rules')->name('pricing-rules.')->group(function () {
        Route::get('/', [\App\Domains\Product\Controllers\PricingRuleController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Product\Controllers\PricingRuleController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Product\Controllers\PricingRuleController::class, 'store'])->name('store');
        Route::get('/{pricingRule}', [\App\Domains\Product\Controllers\PricingRuleController::class, 'show'])->name('show');
        Route::get('/{pricingRule}/edit', [\App\Domains\Product\Controllers\PricingRuleController::class, 'edit'])->name('edit');
        Route::put('/{pricingRule}', [\App\Domains\Product\Controllers\PricingRuleController::class, 'update'])->name('update');
        Route::delete('/{pricingRule}', [\App\Domains\Product\Controllers\PricingRuleController::class, 'destroy'])->name('destroy');
        Route::post('/{pricingRule}/test', [\App\Domains\Product\Controllers\PricingRuleController::class, 'testRule'])->name('test');
        Route::post('/bulk-update', [\App\Domains\Product\Controllers\PricingRuleController::class, 'bulkUpdate'])->name('bulk-update');
    });

    // Service Management routes
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/export/csv', [\App\Domains\Product\Controllers\ServiceController::class, 'export'])->name('export');

        Route::get('/', [\App\Domains\Product\Controllers\ServiceController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Product\Controllers\ServiceController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Product\Controllers\ServiceController::class, 'store'])->name('store');
        Route::get('/{service}', [\App\Domains\Product\Controllers\ServiceController::class, 'show'])->name('show');
        Route::get('/{service}/edit', [\App\Domains\Product\Controllers\ServiceController::class, 'edit'])->name('edit');
        Route::put('/{service}', [\App\Domains\Product\Controllers\ServiceController::class, 'update'])->name('update');
        Route::delete('/{service}', [\App\Domains\Product\Controllers\ServiceController::class, 'destroy'])->name('destroy');
        Route::post('/{service}/duplicate', [\App\Domains\Product\Controllers\ServiceController::class, 'duplicate'])->name('duplicate');
        Route::post('/{service}/calculate-price', [\App\Domains\Product\Controllers\ServiceController::class, 'calculatePrice'])->name('calculate-price');
        Route::post('/bulk-update', [\App\Domains\Product\Controllers\ServiceController::class, 'bulkUpdate'])->name('bulk-update');

        // Tax calculation routes (legacy - kept for backwards compatibility)
        Route::post('/calculate-tax', [\App\Domains\Financial\Controllers\Api\ServiceTaxController::class, 'calculateTax'])->name('calculate-tax');
        Route::get('/customer/{customer}/address', [\App\Domains\Financial\Controllers\Api\ServiceTaxController::class, 'getCustomerAddress'])->name('customer-address');
    });
});
