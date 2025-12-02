<?php

use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\BillingDashboard;
use App\Livewire\Admin\CompanyDetail;
use App\Livewire\Admin\CompanyList;
use App\Livewire\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Admin Routes
|--------------------------------------------------------------------------
|
| Routes for super-admin platform administration.
| Only accessible to users with company_id = 1 and 'admin' role.
| URL: /admin/*
|
*/

Route::middleware(['auth', 'verified', 'super-admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard - Overview metrics for platform
    Route::get('/', Dashboard::class)->name('dashboard');

    // Companies Management
    Route::get('/companies', CompanyList::class)->name('companies.index');
    Route::get('/companies/{company}', CompanyDetail::class)->name('companies.show');

    // Billing Dashboard - Subscriptions, revenue, failed payments
    Route::get('/billing', BillingDashboard::class)->name('billing.index');

    // Analytics - Cohort analysis, LTV, ARPU, retention
    Route::get('/analytics', Analytics::class)->name('analytics.index');
});
