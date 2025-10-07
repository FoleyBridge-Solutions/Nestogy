<?php

use App\Domains\Financial\Controllers\Api\VoIPTaxController;
use App\Domains\Financial\Controllers\Api\VoIPTaxReportController;
use App\Domains\Financial\Http\Controllers\Webhooks\StripeWebhookController;
use App\Domains\Integration\Http\Controllers\Webhooks\ConnectWiseWebhookController;
use App\Domains\Integration\Http\Controllers\Webhooks\DattoWebhookController;
use App\Domains\Integration\Http\Controllers\Webhooks\GenericRMMWebhookController;
use App\Domains\Integration\Http\Controllers\Webhooks\NinjaOneWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Client Assets API endpoint for contract wizard
Route::middleware(['auth:sanctum'])->get('/clients/{client}/assets',
    [\App\Domains\Asset\Controllers\AssetController::class, 'clientAssetsApi']
)->name('api.clients.assets');

// VoIP Tax Management API Routes
Route::middleware(['auth:sanctum'])->prefix('voip-tax')->name('api.voip-tax.')->group(function () {
    // Tax calculation endpoints
    Route::post('/calculate', [VoIPTaxController::class, 'calculateTaxes'])->name('calculate');

    // Tax rates management
    Route::get('/rates', [VoIPTaxController::class, 'getTaxRates'])->name('rates');
    Route::post('/rates', [VoIPTaxController::class, 'createOrUpdateTaxRate'])->name('rates.store');
    Route::post('/rates/initialize-defaults', [VoIPTaxController::class, 'initializeDefaultRates'])->name('rates.initialize');

    // Jurisdictions
    Route::get('/jurisdictions', [VoIPTaxController::class, 'getJurisdictions'])->name('jurisdictions');

    // Tax categories
    Route::get('/categories', [VoIPTaxController::class, 'getCategories'])->name('categories');

    // Client exemptions
    Route::get('/exemptions', [VoIPTaxController::class, 'getClientExemptions'])->name('exemptions');

    // Compliance and reporting
    Route::post('/compliance/report', [VoIPTaxController::class, 'generateComplianceReport'])->name('compliance.report');
    Route::get('/compliance/status', [VoIPTaxController::class, 'checkComplianceStatus'])->name('compliance.status');
    Route::post('/compliance/export', [VoIPTaxController::class, 'exportComplianceData'])->name('compliance.export');

    // Utility endpoints
    Route::get('/service-types', [VoIPTaxController::class, 'getServiceTypes'])->name('service-types');
    Route::post('/cache/clear', [VoIPTaxController::class, 'clearCache'])->name('cache.clear');

    // Tax Reporting endpoints
    Route::prefix('reports')->name('reports.')->group(function () {
        // Dashboard and summary reports
        Route::get('/dashboard', [VoIPTaxReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/tax-summary', [VoIPTaxReportController::class, 'taxSummary'])->name('tax-summary');

        // Specialized reports
        Route::get('/jurisdiction/{jurisdictionId}', [VoIPTaxReportController::class, 'jurisdictionReport'])->name('jurisdiction');
        Route::get('/service-type-analysis', [VoIPTaxReportController::class, 'serviceTypeAnalysis'])->name('service-type-analysis');
        Route::get('/exemption-usage', [VoIPTaxReportController::class, 'exemptionReport'])->name('exemption-usage');
        Route::get('/rate-effectiveness', [VoIPTaxReportController::class, 'rateEffectiveness'])->name('rate-effectiveness');

        // Export functionality
        Route::post('/export', [VoIPTaxReportController::class, 'exportReport'])->name('export');

        // Metadata and configuration
        Route::get('/jurisdictions', [VoIPTaxReportController::class, 'availableJurisdictions'])->name('jurisdictions');
        Route::get('/metadata', [VoIPTaxReportController::class, 'reportMetadata'])->name('metadata');
    });
});

/*
|--------------------------------------------------------------------------
| Nestogy ERP API Routes
|--------------------------------------------------------------------------
|
| API routes organized by business domains for the ERP system
| All routes are rate limited and require proper authentication
|
*/

// Authentication API Routes (Public)
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('login', [App\Domains\Security\Controllers\Auth\LoginController::class, 'login'])->name('login');
    Route::post('register', [App\Domains\Security\Controllers\Auth\RegisterController::class, 'register'])->name('register');
    // Route::post('forgot-password', [App\Domains\Security\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('forgot-password');

    // Authenticated Auth Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [App\Domains\Security\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
        Route::post('refresh', [App\Domains\Security\Controllers\Auth\LoginController::class, 'refresh'])->name('refresh');
        Route::get('me', function (Request $request) {
            return $request->user();
        })->name('me');
        // Route::post('change-password', [App\Domains\Security\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('change-password');
    });
});

// Protected API Routes (Authenticated + Company Scoped)
Route::middleware(['auth:sanctum', 'company', 'throttle:120,1'])->group(function () {

    // Dashboard API
    Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
        Route::get('stats', [App\Domains\Core\Controllers\DashboardController::class, 'getData'])->name('stats');
        Route::get('notifications', [App\Domains\Core\Controllers\DashboardController::class, 'getNotifications'])->name('notifications');
        Route::patch('notifications/{id}/read', [App\Domains\Core\Controllers\DashboardController::class, 'markNotificationRead'])->name('notifications.read');
        Route::get('recent-activity', [App\Domains\Core\Controllers\DashboardController::class, 'getData'])->name('recent-activity');
    });

    // Client Management API
    Route::prefix('clients')->name('api.clients.')->group(function () {
        // Standard CRUD
        Route::get('/', [App\Domains\Client\Controllers\ClientController::class, 'index'])->name('index');
        Route::post('/', [App\Domains\Client\Controllers\ClientController::class, 'store'])->name('store');
        Route::get('{client}', [App\Domains\Client\Controllers\ClientController::class, 'show'])->name('show');
        Route::put('{client}', [App\Domains\Client\Controllers\ClientController::class, 'update'])->name('update');
        Route::delete('{client}', [App\Domains\Client\Controllers\ClientController::class, 'destroy'])->name('destroy');

        // Client Actions
        Route::patch('{client}/archive', [App\Domains\Client\Controllers\ClientController::class, 'archive'])->name('archive');
        Route::patch('{client}/restore', [App\Domains\Client\Controllers\ClientController::class, 'restore'])->name('restore');
        Route::patch('{client}/notes', [App\Domains\Client\Controllers\ClientController::class, 'updateNotes'])->name('notes.update');

        // Client Relationships
        Route::get('{client}/contacts', [App\Domains\Client\Controllers\ContactController::class, 'index'])->name('contacts.index');
        Route::post('{client}/contacts', [App\Domains\Client\Controllers\ContactController::class, 'store'])->name('contacts.store');
        Route::put('{client}/contacts/{contact}', [App\Domains\Client\Controllers\ContactController::class, 'update'])->name('contacts.update');
        Route::delete('{client}/contacts/{contact}', [App\Domains\Client\Controllers\ContactController::class, 'destroy'])->name('contacts.destroy');

        Route::get('{client}/locations', [App\Domains\Client\Controllers\LocationController::class, 'index'])->name('locations.index');
        Route::post('{client}/locations', [App\Domains\Client\Controllers\LocationController::class, 'store'])->name('locations.store');
        Route::put('{client}/locations/{location}', [App\Domains\Client\Controllers\LocationController::class, 'update'])->name('locations.update');
        Route::delete('{client}/locations/{location}', [App\Domains\Client\Controllers\LocationController::class, 'destroy'])->name('locations.destroy');

        Route::get('{client}/tickets', [App\Domains\Ticket\Controllers\TicketController::class, 'index'])->name('tickets.index');
        Route::get('{client}/invoices', [App\Domains\Financial\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('{client}/assets', [App\Domains\Asset\Controllers\AssetController::class, 'index'])->name('assets.index');

        // Quick Access
        Route::get('active', [App\Domains\Client\Controllers\ClientController::class, 'getActiveClients'])->name('api.active');
        Route::get('search', [App\Domains\Core\Controllers\SearchController::class, 'clients'])->name('api.search');
        Route::post('{client}/mark-accessed', [App\Domains\Client\Controllers\ClientController::class, 'markAsAccessed'])->name('api.mark-accessed');

        // Client Billing Settings
        Route::get('{client}/billing-settings', [App\Domains\Client\Controllers\ClientController::class, 'getBillingSettings'])->name('billing-settings');
    });

    // Ticket System API
    Route::prefix('tickets')->name('api.tickets.')->group(function () {
        // Standard CRUD
        Route::get('/', [App\Domains\Ticket\Controllers\Api\TicketsController::class, 'index'])->name('index');
        Route::post('/', [App\Domains\Ticket\Controllers\Api\TicketsController::class, 'store'])->name('store');
        Route::get('{ticket}', [App\Domains\Ticket\Controllers\Api\TicketsController::class, 'show'])->name('show');
        Route::put('{ticket}', [App\Domains\Ticket\Controllers\Api\TicketsController::class, 'update'])->name('update');
        Route::delete('{ticket}', [App\Domains\Ticket\Controllers\Api\TicketsController::class, 'destroy'])->name('destroy');
        Route::post('bulk-assign', [App\Domains\Ticket\Controllers\Api\TicketsController::class, 'bulkAssign'])->name('bulk-assign');
        Route::post('bulk-status', [App\Domains\Ticket\Controllers\Api\TicketsController::class, 'bulkUpdateStatus'])->name('bulk-status');
        Route::get('sla-performance', [App\Domains\Ticket\Controllers\Api\TicketsController::class, 'slaPerformance'])->name('sla-performance');
        Route::post('check-escalations', [App\Domains\Ticket\Controllers\Api\TicketsController::class, 'checkEscalations'])->name('check-escalations');
    });

    // Asset Management API
    Route::prefix('assets')->name('api.assets.')->group(function () {
        // Standard CRUD
        Route::get('/', [App\Domains\Asset\Controllers\AssetController::class, 'index'])->name('index');
        Route::post('/', [App\Domains\Asset\Controllers\AssetController::class, 'store'])->name('store');
        Route::get('{asset}', [App\Domains\Asset\Controllers\AssetController::class, 'show'])->name('show');
        Route::put('{asset}', [App\Domains\Asset\Controllers\AssetController::class, 'update'])->name('update');
        Route::delete('{asset}', [App\Domains\Asset\Controllers\AssetController::class, 'destroy'])->name('destroy');

        // Asset Actions
        Route::patch('{asset}/archive', [App\Domains\Asset\Controllers\AssetController::class, 'archive'])->name('archive');
        Route::patch('{asset}/notes', [App\Domains\Asset\Controllers\AssetController::class, 'updateNotes'])->name('notes.update');

        // Bulk Operations
        Route::patch('bulk/location', [App\Domains\Asset\Controllers\AssetController::class, 'bulkAssignLocation'])->name('bulk.location');
        Route::patch('bulk/contact', [App\Domains\Asset\Controllers\AssetController::class, 'bulkAssignContact'])->name('bulk.contact');
        Route::patch('bulk/status', [App\Domains\Asset\Controllers\AssetController::class, 'bulkUpdateStatus'])->name('bulk.status');

        // Asset Data
        Route::get('types', [App\Domains\Asset\Controllers\AssetController::class, 'getAssetTypes'])->name('types');
        Route::get('warranties/expiring', [App\Domains\Asset\Controllers\AssetController::class, 'getExpiringWarranties'])->name('warranties.expiring');
        Route::get('search', [App\Domains\Core\Controllers\SearchController::class, 'assets'])->name('search');
    });

    // Financial Management API
    Route::prefix('financial')->name('api.financial.')->group(function () {

        // Quote API
        Route::prefix('quotes')->name('quotes.')->group(function () {
            // Standard CRUD
            Route::get('/', [App\Domains\Financial\Controllers\QuoteController::class, 'index'])->name('index');
            Route::post('/', [App\Domains\Financial\Controllers\QuoteController::class, 'store'])->name('store');
            Route::get('{quote}', [App\Domains\Financial\Controllers\QuoteController::class, 'show'])->name('show');
            Route::put('{quote}', [App\Domains\Financial\Controllers\QuoteController::class, 'update'])->name('update');
            Route::delete('{quote}', [App\Domains\Financial\Controllers\QuoteController::class, 'destroy'])->name('destroy');

            // Quote Items
            Route::post('{quote}/items', [App\Domains\Financial\Controllers\QuoteController::class, 'addItem'])->name('items.store');
            Route::put('{quote}/items/{item}', [App\Domains\Financial\Controllers\QuoteController::class, 'updateItem'])->name('items.update');
            Route::delete('{quote}/items/{item}', [App\Domains\Financial\Controllers\QuoteController::class, 'deleteItem'])->name('items.destroy');

            // Quote Actions
            Route::post('{quote}/send', [App\Domains\Financial\Controllers\QuoteController::class, 'sendEmail'])->name('send');
            Route::post('{quote}/duplicate', [App\Domains\Financial\Controllers\QuoteController::class, 'duplicate'])->name('duplicate');
            Route::post('{quote}/convert-to-invoice', [App\Domains\Financial\Controllers\QuoteController::class, 'convertToInvoice'])->name('convert-to-invoice');

            // Auto-save and PDF
            Route::post('auto-save', [App\Domains\Financial\Controllers\QuoteController::class, 'autoSave'])->name('auto-save');
            Route::post('preview-pdf', [App\Domains\Financial\Controllers\QuoteController::class, 'previewPdf'])->name('preview-pdf');
            Route::post('email-pdf', [App\Domains\Financial\Controllers\QuoteController::class, 'emailPdf'])->name('email-pdf');
        });

        // Invoice API
        Route::prefix('invoices')->name('invoices.')->group(function () {
            // Standard CRUD
            Route::get('/', [App\Domains\Financial\Controllers\Api\InvoicesController::class, 'index'])->name('index');
            Route::post('/', [App\Domains\Financial\Controllers\Api\InvoicesController::class, 'store'])->name('store');
            Route::get('{invoice}', [App\Domains\Financial\Controllers\Api\InvoicesController::class, 'show'])->name('show');
            Route::put('{invoice}', [App\Domains\Financial\Controllers\Api\InvoicesController::class, 'update'])->name('update');
            Route::delete('{invoice}', [App\Domains\Financial\Controllers\Api\InvoicesController::class, 'destroy'])->name('destroy');

            // Invoice Items
            Route::post('{invoice}/items', [App\Domains\Financial\Controllers\InvoiceController::class, 'addItem'])->name('items.store');
            Route::put('{invoice}/items/{item}', [App\Domains\Financial\Controllers\InvoiceController::class, 'updateItem'])->name('items.update');
            Route::delete('{invoice}/items/{item}', [App\Domains\Financial\Controllers\InvoiceController::class, 'deleteItem'])->name('items.destroy');

            // Invoice Actions
            Route::patch('{invoice}/status', [App\Domains\Financial\Controllers\InvoiceController::class, 'updateStatus'])->name('status.update');
            Route::post('{invoice}/send', [App\Domains\Financial\Controllers\InvoiceController::class, 'sendEmail'])->name('send');
            Route::post('{invoice}/copy', [App\Domains\Financial\Controllers\InvoiceController::class, 'copy'])->name('copy');
            Route::patch('{invoice}/notes', [App\Domains\Financial\Controllers\InvoiceController::class, 'updateNotes'])->name('notes.update');

            // Invoice Payments
            Route::get('{invoice}/payments', [App\Domains\Financial\Controllers\InvoiceController::class, 'getPayments'])->name('payments.index');
            Route::post('{invoice}/payments', [App\Domains\Financial\Controllers\InvoiceController::class, 'addPayment'])->name('payments.store');

            // Quick Access
            Route::get('overdue', [App\Domains\Financial\Controllers\InvoiceController::class, 'index'])->name('overdue');
            Route::get('draft', [App\Domains\Financial\Controllers\InvoiceController::class, 'index'])->name('draft');
            Route::get('search', [App\Domains\Core\Controllers\SearchController::class, 'invoices'])->name('search');

            // New Recurring and Automation
            Route::post('generate-recurring', [App\Domains\Financial\Controllers\Api\InvoicesController::class, 'generateRecurring'])->name('generate-recurring');
            Route::post('{invoice}/retry-payment', [App\Domains\Financial\Controllers\Api\InvoicesController::class, 'retryPayment'])->name('retry-payment');
            Route::get('forecast', [App\Domains\Financial\Controllers\Api\InvoicesController::class, 'forecast'])->name('forecast');
            Route::post('{invoice}/send-email', [App\Domains\Financial\Controllers\Api\InvoicesController::class, 'sendEmail'])->name('send-email');
        });

        // Payment API - Commented out until PaymentController is implemented
        // Route::prefix('payments')->name('payments.')->group(function () {
        //     Route::get('/', [App\Domains\Financial\Controllers\PaymentController::class, 'index'])->name('index');
        //     Route::post('/', [App\Domains\Financial\Controllers\PaymentController::class, 'store'])->name('store');
        //     Route::get('{payment}', [App\Domains\Financial\Controllers\PaymentController::class, 'show'])->name('show');
        //     Route::put('{payment}', [App\Domains\Financial\Controllers\PaymentController::class, 'update'])->name('update');
        //     Route::delete('{payment}', [App\Domains\Financial\Controllers\PaymentController::class, 'destroy'])->name('destroy');

        //     // Payment Actions
        //     Route::post('{payment}/refund', [App\Domains\Financial\Controllers\PaymentController::class, 'refund'])->name('refund');
        //     Route::get('recent', [App\Domains\Financial\Controllers\PaymentController::class, 'recent'])->name('recent');
        // });

        // Expense API
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', [App\Domains\Financial\Controllers\ExpenseController::class, 'index'])->name('index');
            Route::post('/', [App\Domains\Financial\Controllers\ExpenseController::class, 'store'])->name('store');
            Route::get('{expense}', [App\Domains\Financial\Controllers\ExpenseController::class, 'show'])->name('show');
            Route::put('{expense}', [App\Domains\Financial\Controllers\ExpenseController::class, 'update'])->name('update');
            Route::delete('{expense}', [App\Domains\Financial\Controllers\ExpenseController::class, 'destroy'])->name('destroy');

            // Expense Categories
            Route::get('categories', [App\Domains\Financial\Controllers\ExpenseController::class, 'categories'])->name('categories');
            Route::get('monthly-summary', [App\Domains\Financial\Controllers\ExpenseController::class, 'monthlySummary'])->name('monthly-summary');
        });
    });

    // Product & Service Management API
    Route::prefix('products')->name('api.products.')->group(function () {
        // Product Search & Discovery
        Route::get('search', [App\Domains\Product\Controllers\Api\ProductController::class, 'search'])->name('search');
        Route::get('quick-search', [App\Domains\Product\Controllers\Api\ProductController::class, 'quickSearch'])->name('quick-search');
        Route::get('categories', [App\Domains\Product\Controllers\Api\ProductController::class, 'categories'])->name('categories');
        Route::get('category/{category}', [App\Domains\Product\Controllers\Api\ProductController::class, 'byCategory'])->name('by-category');

        // Product Pricing
        Route::post('pricing', [App\Domains\Product\Controllers\Api\ProductController::class, 'pricing'])->name('pricing');
        Route::post('calculate-price', [App\Domains\Product\Controllers\Api\ProductController::class, 'calculatePrice'])->name('calculate-price');
        Route::post('best-price', [App\Domains\Product\Controllers\Api\ProductController::class, 'bestPrice'])->name('best-price');
        Route::post('apply-promo', [App\Domains\Product\Controllers\Api\ProductController::class, 'applyPromo'])->name('apply-promo');

        // Product Details & Recommendations
        Route::get('{product}/details', [App\Domains\Product\Controllers\Api\ProductController::class, 'details'])->name('details');
        Route::get('recommendations', [App\Domains\Product\Controllers\Api\ProductController::class, 'recommendations'])->name('recommendations');
    });

    // Service Tax Calculation API
    Route::prefix('services')->name('api.services.')->group(function () {
        Route::post('calculate-tax', [App\Domains\Financial\Controllers\Api\ServiceTaxController::class, 'calculateTax'])->name('calculate-tax');
        Route::post('calculate-quote-tax', [App\Domains\Financial\Controllers\Api\ServiceTaxController::class, 'calculateQuoteTax'])->name('calculate-quote-tax');
        Route::get('customer/{customer}/address', [App\Domains\Financial\Controllers\Api\ServiceTaxController::class, 'getCustomerAddress'])->name('customer-address');
    });

    // Bundle Management API
    Route::prefix('bundles')->name('api.bundles.')->group(function () {
        // Bundle Discovery
        Route::get('/', [App\Domains\Product\Controllers\Api\BundleController::class, 'index'])->name('index');
        Route::get('search', [App\Domains\Product\Controllers\Api\BundleController::class, 'search'])->name('search');

        // Bundle Details & Configuration
        Route::get('{bundle}/details', [App\Domains\Product\Controllers\Api\BundleController::class, 'details'])->name('details');
        Route::get('{bundle}/configurable-options', [App\Domains\Product\Controllers\Api\BundleController::class, 'configurableOptions'])->name('configurable-options');

        // Bundle Pricing & Validation
        Route::post('calculate-price', [App\Domains\Product\Controllers\Api\BundleController::class, 'calculatePrice'])->name('calculate-price');
        Route::post('validate-selection', [App\Domains\Product\Controllers\Api\BundleController::class, 'validateSelection'])->name('validate-selection');

        // Bundle Management
        Route::post('{bundle}/duplicate', [App\Domains\Product\Controllers\Api\BundleController::class, 'duplicate'])->name('duplicate');
    });

    // Promo Code Management API
    Route::prefix('promo-codes')->name('api.promo-codes.')->group(function () {
        Route::post('validate', [App\Domains\Product\Controllers\Api\ProductController::class, 'applyPromo'])->name('validate');
    });

    // Project Management API
    Route::prefix('projects')->name('api.projects.')->group(function () {
        // Standard CRUD
        Route::get('/', [\App\Domains\Project\Controllers\ProjectController::class, 'index'])->name('index');
        Route::post('/', [\App\Domains\Project\Controllers\ProjectController::class, 'store'])->name('store');
        Route::get('{project}', [\App\Domains\Project\Controllers\ProjectController::class, 'show'])->name('show');
        Route::put('{project}', [\App\Domains\Project\Controllers\ProjectController::class, 'update'])->name('update');
        Route::delete('{project}', [\App\Domains\Project\Controllers\ProjectController::class, 'destroy'])->name('destroy');

        // Project Actions
        Route::patch('{project}/status', [\App\Domains\Project\Controllers\ProjectController::class, 'updateStatus'])->name('status.update');
        Route::get('{project}/progress', [\App\Domains\Project\Controllers\ProjectController::class, 'getProgress'])->name('progress');

        // Project Tasks API
        Route::prefix('{project}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [\App\Domains\Project\Controllers\TaskController::class, 'index'])->name('index');
            Route::post('/', [\App\Domains\Project\Controllers\TaskController::class, 'store'])->name('store');
            Route::get('{task}', [\App\Domains\Project\Controllers\TaskController::class, 'show'])->name('show');
            Route::put('{task}', [\App\Domains\Project\Controllers\TaskController::class, 'update'])->name('update');
            Route::delete('{task}', [\App\Domains\Project\Controllers\TaskController::class, 'destroy'])->name('destroy');

            Route::patch('{task}/status', [\App\Domains\Project\Controllers\TaskController::class, 'updateStatus'])->name('status.update');
            Route::patch('{task}/assign', [\App\Domains\Project\Controllers\TaskController::class, 'assign'])->name('assign');
        });

        // Quick Access
        Route::get('active', [\App\Domains\Project\Controllers\ProjectController::class, 'active'])->name('active');
        Route::get('my-projects', [\App\Domains\Project\Controllers\ProjectController::class, 'myProjects'])->name('my-projects');
    });

    // Reports API
    Route::prefix('reports')->name('api.reports.')->group(function () {
        Route::get('dashboard', [\App\Domains\Report\Controllers\ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('financial', [\App\Domains\Report\Controllers\ReportController::class, 'financial'])->name('financial');
        Route::get('tickets', [\App\Domains\Report\Controllers\ReportController::class, 'tickets'])->name('tickets');
        Route::get('assets', [\App\Domains\Report\Controllers\ReportController::class, 'assets'])->name('assets');
        Route::get('clients', [\App\Domains\Report\Controllers\ReportController::class, 'clients'])->name('clients');
        Route::get('projects', [\App\Domains\Report\Controllers\ReportController::class, 'projects'])->name('projects');
        Route::get('users', [\App\Domains\Report\Controllers\ReportController::class, 'users'])->name('users');

        // Custom Reports
        Route::post('custom', [\App\Domains\Report\Controllers\ReportController::class, 'generateCustomReport'])->name('custom');
        Route::get('export/{type}', [\App\Domains\Report\Controllers\ReportController::class, 'exportReport'])->name('export');
    });

    // Documentation Template API
    Route::prefix('documentation-templates')->name('api.documentation-templates.')->group(function () {
        Route::get('/tabs', [App\Domains\Knowledge\Controllers\Api\DocumentationTemplateController::class, 'getAvailableTabs'])->name('tabs');
        Route::get('/tabs/{category}', [App\Domains\Knowledge\Controllers\Api\DocumentationTemplateController::class, 'getDefaultTabs'])->name('tabs.category');
        Route::get('/{templateKey}', [App\Domains\Knowledge\Controllers\Api\DocumentationTemplateController::class, 'getTemplate'])->name('template');
    });

    // User Management API
    Route::prefix('users')->name('api.users.')->group(function () {
        $userParam = '{user}';

        // Profile Routes (All Users)
        Route::get('profile', [App\Domains\Security\Controllers\UserController::class, 'profile'])->name('profile');
        Route::put('profile', [App\Domains\Security\Controllers\UserController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/settings', [App\Domains\Security\Controllers\UserController::class, 'updateSettings'])->name('profile.settings.update');

        // User Management (Admin/Manager Only)
        Route::middleware('role:manager')->group(function () use ($userParam) {
            Route::get('/', [App\Domains\Security\Controllers\UserController::class, 'index'])->name('index');
            Route::post('/', [App\Domains\Security\Controllers\UserController::class, 'store'])->name('store')->middleware('subscription.limits');
            Route::get($userParam, [App\Domains\Security\Controllers\UserController::class, 'show'])->name('show');
            Route::put($userParam, [App\Domains\Security\Controllers\UserController::class, 'update'])->name('update');
            Route::delete($userParam, [App\Domains\Security\Controllers\UserController::class, 'destroy'])->name('destroy');

            // User Actions
            Route::patch("{$userParam}/role", [App\Domains\Security\Controllers\UserController::class, 'updateRole'])->name('role.update');
            Route::patch("{$userParam}/status", [App\Domains\Security\Controllers\UserController::class, 'updateStatus'])->name('status.update');
            Route::patch("{$userParam}/archive", [App\Domains\Security\Controllers\UserController::class, 'archive'])->name('archive');
            Route::patch("{$userParam}/restore", [App\Domains\Security\Controllers\UserController::class, 'restore'])->name('restore');

            Route::get("{$userParam}/activity", [App\Domains\Security\Controllers\UserController::class, 'getActivityLog'])->name('activity');
        });

        // Quick Access
        Route::get('technicians', [App\Domains\Security\Controllers\UserController::class, 'getActiveTechnicians'])->name('technicians');
        Route::get('online', [App\Domains\Security\Controllers\UserController::class, 'getOnlineUsers'])->name('online');
    });

    // File Management API
    Route::prefix('files')->name('api.files.')->group(function () {
        Route::post('upload', [\App\Domains\Client\Controllers\FileController::class, 'upload'])->name('upload');
        Route::get('{file}', [\App\Domains\Client\Controllers\FileController::class, 'show'])->name('show');
        Route::delete('{file}', [\App\Domains\Client\Controllers\FileController::class, 'destroy'])->name('destroy');

        // Bulk File Operations
        Route::post('bulk-upload', [\App\Domains\Client\Controllers\FileController::class, 'bulkUpload'])->name('bulk-upload');
        Route::delete('bulk-delete', [\App\Domains\Client\Controllers\FileController::class, 'bulkDelete'])->name('bulk-delete');
    });

    // Navigation API (for command palette and workflow navigation)
    Route::prefix('navigation')->name('api.navigation.')->group(function () {
        Route::get('tree', [App\Domains\Core\Controllers\NavigationController::class, 'getNavigationTree'])->name('tree');
        Route::get('badges', [App\Domains\Core\Controllers\NavigationController::class, 'getBadgeCounts'])->name('badges');
        Route::get('suggestions', [App\Domains\Core\Controllers\NavigationController::class, 'getSuggestions'])->name('suggestions');
        Route::post('command', [App\Domains\Core\Controllers\NavigationController::class, 'executeCommand'])->name('command');
        Route::post('workflow', [App\Domains\Core\Controllers\NavigationController::class, 'setWorkflow'])->name('workflow');
        Route::get('workflow-highlights', [App\Domains\Core\Controllers\NavigationController::class, 'getWorkflowHighlights'])->name('workflow-highlights');
        Route::get('recent', [App\Domains\Core\Controllers\NavigationController::class, 'getRecentItems'])->name('recent');
    });

    // Keyboard Shortcuts API
    Route::prefix('shortcuts')->name('api.shortcuts.')->group(function () {
        Route::get('active', [App\Domains\Core\Controllers\Api\ShortcutsController::class, 'active'])->name('active');
        Route::post('execute', [App\Domains\Core\Controllers\ShortcutController::class, 'executeShortcutCommand'])->name('execute');
        Route::get('help', [App\Domains\Core\Controllers\ShortcutController::class, 'getShortcutHelp'])->name('help');
    });

    // Product Catalog API (using the main products API above)

    // Services API (using the main services API above)

    // Bundles API (using the main bundles API above)

    // Categories API
    Route::prefix('categories')->name('api.categories.')->group(function () {
        Route::get('/', [App\Domains\Product\Controllers\Api\CategoriesController::class, 'index'])->name('index');
    });

    // Search API
    Route::prefix('search')->name('api.search.')->group(function () {
        Route::get('global', [App\Domains\Core\Controllers\SearchController::class, 'global'])->name('global');
        Route::get('clients', [App\Domains\Core\Controllers\SearchController::class, 'clients'])->name('clients');
        Route::get('tickets', [App\Domains\Core\Controllers\SearchController::class, 'tickets'])->name('tickets');
        Route::get('assets', [App\Domains\Core\Controllers\SearchController::class, 'assets'])->name('assets');
        Route::get('invoices', [App\Domains\Core\Controllers\SearchController::class, 'invoices'])->name('invoices');
        Route::get('users', [App\Domains\Core\Controllers\SearchController::class, 'users'])->name('users');
        Route::get('projects', [App\Domains\Core\Controllers\SearchController::class, 'projects'])->name('projects');
        Route::get('query', [App\Domains\Core\Controllers\NavigationController::class, 'search'])->name('query');
        Route::get('suggestions', [App\Domains\Core\Controllers\SearchController::class, 'suggestions'])->name('suggestions');
    });

    // Settings API (Admin Only)
    Route::prefix('settings')->name('api.settings.')->middleware('role:admin')->group(function () {
        Route::get('/', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'index'])->name('index');
        Route::put('/', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'update'])->name('update');

        Route::get('company', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'company'])->name('company');
        Route::put('company', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'updateCompany'])->name('company.update');

        Route::get('email', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'email'])->name('email');
        Route::put('email', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'updateEmail'])->name('email.update');
        Route::post('email/test', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'testEmail'])->name('email.test');

        Route::get('integrations', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'integrations'])->name('integrations');
        Route::put('integrations', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'updateIntegrations'])->name('integrations.update');

        Route::post('backup', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'createBackup'])->name('backup.create');
        Route::get('logs', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'logs'])->name('logs');

        // Billing Settings
        Route::get('billing-defaults', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'billingDefaults'])->name('billing-defaults');
        Route::get('tax', [App\Domains\Core\Controllers\Settings\UnifiedSettingsController::class, 'taxSettings'])->name('tax');
    });

    // RMM Integration Management API (Admin Only)
    Route::prefix('integrations')->name('api.integrations.')->middleware('role:admin')->group(function () {
        // Integration CRUD
        Route::get('/', [App\Domains\Integration\Controllers\IntegrationController::class, 'index'])->name('index');
        Route::post('/', [App\Domains\Integration\Controllers\IntegrationController::class, 'store'])->name('store');
        Route::get('{integration}', [App\Domains\Integration\Controllers\IntegrationController::class, 'show'])->name('show');
        Route::put('{integration}', [App\Domains\Integration\Controllers\IntegrationController::class, 'update'])->name('update');
        Route::delete('{integration}', [App\Domains\Integration\Controllers\IntegrationController::class, 'destroy'])->name('destroy');

        // Integration actions
        Route::patch('{integration}/toggle', [App\Domains\Integration\Controllers\IntegrationController::class, 'toggle'])->name('toggle');
        Route::post('{integration}/test', [App\Domains\Integration\Controllers\IntegrationController::class, 'testConnection'])->name('test');
        Route::post('{integration}/sync', [App\Domains\Integration\Controllers\IntegrationController::class, 'syncDevices'])->name('sync');

        // Alert management (TODO: Create AlertController)
        // Route::get('{integration}/alerts', [App\Domains\Integration\Controllers\AlertController::class, 'index'])->name('alerts.index');
        // Route::get('{integration}/alerts/{alert}', [App\Domains\Integration\Controllers\AlertController::class, 'show'])->name('alerts.show');
        // Route::delete('{integration}/alerts/{alert}', [App\Domains\Integration\Controllers\AlertController::class, 'destroy'])->name('alerts.destroy');
        // Route::post('{integration}/alerts/{alert}/reprocess', [App\Domains\Integration\Controllers\AlertController::class, 'reprocess'])->name('alerts.reprocess');

        // Device mappings (TODO: Create DeviceMappingController)
        // Route::get('{integration}/devices', [App\Domains\Integration\Controllers\DeviceMappingController::class, 'index'])->name('devices.index');
        // Route::post('{integration}/devices/{device}/link', [App\Domains\Integration\Controllers\DeviceMappingController::class, 'linkToAsset'])->name('devices.link');
        // Route::delete('{integration}/devices/{device}/unlink', [App\Domains\Integration\Controllers\DeviceMappingController::class, 'unlinkFromAsset'])->name('devices.unlink');

        // Provider information
        Route::get('providers', [App\Domains\Integration\Controllers\IntegrationController::class, 'getProviders'])->name('providers');
        Route::get('providers/{provider}/defaults', [App\Domains\Integration\Controllers\IntegrationController::class, 'getProviderDefaults'])->name('provider-defaults');

        // Statistics and monitoring
        Route::get('stats', [App\Domains\Integration\Controllers\IntegrationController::class, 'getStats'])->name('stats');
        Route::get('{integration}/stats', [App\Domains\Integration\Controllers\IntegrationController::class, 'getIntegrationStats'])->name('integration-stats');
    });

    // RMM Client Management API
    Route::prefix('rmm')->name('api.rmm.')->group(function () {
        // Client fetching routes for RMM mapping interface
        Route::prefix('clients')->name('clients.')->group(function () {
            // Get Nestogy clients with RMM mappings
            Route::get('nestogy', function (\Illuminate\Http\Request $request) {
                $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();

                $query = \App\Models\Client::where('company_id', auth()->user()->company_id)
                    ->with(['rmmClientMappings' => function ($query) use ($integration) {
                        if ($integration) {
                            $query->where('integration_id', $integration->id);
                        }
                    }])
                    ->orderBy('name');

                $clients = $query->get();

                return response()->json([
                    'success' => true,
                    'clients' => $clients->map(function ($client) {
                        return [
                            'id' => $client->id,
                            'name' => $client->name,
                            'company_name' => $client->company_name,
                            'status' => $client->status,
                            'rmm_client_mappings' => $client->rmmClientMappings,
                        ];
                    }),
                ]);
            })->name('nestogy');

            // Get RMM clients from external system
            Route::get('rmm', function (\Illuminate\Http\Request $request) {
                $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
                if (! $integration) {
                    return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
                }

                try {
                    $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)->make($integration);
                    $clientsResult = $rmmService->getClients();

                    return response()->json([
                        'success' => true,
                        'clients' => $clientsResult['data'] ?? [],
                    ]);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Failed to fetch RMM clients: '.$e->getMessage()], 500);
                }
            })->name('rmm');
        });

        // Client mapping management routes
        Route::prefix('client-mappings')->name('client-mappings.')->group(function () {
            // Get existing mappings
            Route::get('/', function (\Illuminate\Http\Request $request) {
                $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();

                if (! $integration) {
                    return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
                }

                $mappings = \App\Domains\Integration\Models\RmmClientMapping::where('integration_id', $integration->id)
                    ->with('client')
                    ->get();

                return response()->json([
                    'success' => true,
                    'mappings' => $mappings,
                ]);
            })->name('index');

            // Create or update client mapping
            Route::post('/', function (\Illuminate\Http\Request $request) {
                $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();

                if (! $integration) {
                    return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
                }

                $validated = $request->validate([
                    'client_id' => 'required|exists:clients,id',
                    'rmm_client_id' => 'required',
                    'rmm_client_name' => 'required|string',
                ]);

                // Convert rmm_client_id to string if it's not already
                $validated['rmm_client_id'] = (string) $validated['rmm_client_id'];

                $mapping = \App\Domains\Integration\Models\RmmClientMapping::createOrUpdateMapping([
                    'integration_id' => $integration->id,
                    'client_id' => $validated['client_id'],
                    'rmm_client_id' => $validated['rmm_client_id'],
                    'rmm_client_name' => $validated['rmm_client_name'],
                ]);

                return response()->json([
                    'success' => true,
                    'mapping' => $mapping,
                ]);
            })->name('store');

            // Delete client mapping
            Route::delete('{mappingId}', function (\Illuminate\Http\Request $request, $mappingId) {
                $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();

                if (! $integration) {
                    return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
                }

                $mapping = \App\Domains\Integration\Models\RmmClientMapping::where('id', $mappingId)
                    ->where('integration_id', $integration->id)
                    ->first();

                if (! $mapping) {
                    return response()->json(['success' => false, 'message' => 'Mapping not found'], 404);
                }

                $mapping->delete();

                return response()->json(['success' => true]);
            })->name('destroy');
        });
    });

});

// Integration Webhooks (No Authentication Required)
Route::prefix('webhooks')->name('api.webhooks.')->middleware('throttle:60,1')->group(function () {
    // Legacy webhooks
    Route::post('stripe', [StripeWebhookController::class, 'handle'])->name('stripe');
    // Route::post('plaid', [App\Http\Controllers\Integration\Controllers\PlaidWebhookController::class, 'handle'])->name('plaid'); // TODO: Check if exists
    // Route::post('email', [App\Http\Controllers\Integration\Controllers\EmailWebhookController::class, 'handle'])->name('email'); // TODO: Check if exists
    // Route::post('sms', [App\Http\Controllers\Integration\Controllers\SmsWebhookController::class, 'handle'])->name('sms'); // TODO: Check if exists

    // RMM Integration Webhooks - High rate limit for production workloads
    Route::middleware('throttle:1000,1')->group(function () {
        // ConnectWise Automate webhooks
        Route::prefix('connectwise')->name('connectwise.')->group(function () {
            Route::post('{integration}', [ConnectWiseWebhookController::class, 'handle'])->name('webhook');
            Route::get('{integration}/health', [ConnectWiseWebhookController::class, 'health'])->name('health');
            Route::post('{integration}/test', [ConnectWiseWebhookController::class, 'test'])->name('test');
        });

        // Datto RMM webhooks
        Route::prefix('datto')->name('datto.')->group(function () {
            Route::post('{integration}', [DattoWebhookController::class, 'handle'])->name('webhook');
            Route::get('{integration}/health', [DattoWebhookController::class, 'health'])->name('health');
            Route::post('{integration}/test', [DattoWebhookController::class, 'test'])->name('test');
        });

        // NinjaOne webhooks
        Route::prefix('ninja')->name('ninja.')->group(function () {
            Route::post('{integration}', [NinjaOneWebhookController::class, 'handle'])->name('webhook');
            Route::get('{integration}/health', [NinjaOneWebhookController::class, 'health'])->name('health');
            Route::post('{integration}/test', [NinjaOneWebhookController::class, 'test'])->name('test');
        });

        // Generic RMM webhooks
        Route::prefix('generic')->name('generic.')->group(function () {
            Route::post('{integration}', [GenericRMMWebhookController::class, 'handle'])->name('webhook');
            Route::get('{integration}/health', [GenericRMMWebhookController::class, 'health'])->name('health');
            Route::post('{integration}/test', [GenericRMMWebhookController::class, 'test'])->name('test');
            Route::post('{integration}/suggest-mappings', [GenericRMMWebhookController::class, 'suggestFieldMappings'])->name('suggest-mappings');
        });
    });
});

// Public API Endpoints (Rate Limited)
Route::prefix('public')->name('api.public.')->middleware('throttle:30,1')->group(function () {
    Route::get('status', function () {
        return response()->json([
            'status' => 'online',
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toISOString(),
        ]);
    })->name('status');

    Route::get('health', function () {
        return response()->json([
            'database' => 'connected',
            'cache' => 'active',
            'queue' => 'running',
        ]);
    })->name('health');
});
