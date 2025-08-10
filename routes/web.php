<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Financial\Controllers\{
    QuoteController,
    InvoiceController,
    ContractController,
    ReportController,
    ContractAnalyticsController
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Dashboard API endpoints
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/api/dashboard/realtime', [\App\Http\Controllers\DashboardController::class, 'getRealtimeData'])->name('dashboard.realtime');
    Route::get('/api/dashboard/export', [\App\Http\Controllers\DashboardController::class, 'exportData'])->name('dashboard.export');
    Route::get('/api/dashboard/notifications', [\App\Http\Controllers\DashboardController::class, 'getNotifications'])->name('dashboard.notifications');
    Route::post('/api/dashboard/notifications/{id}/read', [\App\Http\Controllers\DashboardController::class, 'markNotificationRead'])->name('dashboard.notifications.read');
});

// Core application routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Client routes
    Route::get('clients/data', [\App\Domains\Client\Controllers\ClientController::class, 'data'])->name('clients.data');
    Route::get('clients/import', [\App\Domains\Client\Controllers\ClientController::class, 'importForm'])->name('clients.import.form');
    Route::post('clients/import', [\App\Domains\Client\Controllers\ClientController::class, 'import'])->name('clients.import');
    Route::get('clients/import/template', [\App\Domains\Client\Controllers\ClientController::class, 'downloadTemplate'])->name('clients.import.template');
    Route::get('clients/export/csv', [\App\Domains\Client\Controllers\ClientController::class, 'exportCsv'])->name('clients.export.csv');
    Route::get('clients/leads', [\App\Domains\Client\Controllers\ClientController::class, 'leads'])->name('clients.leads');
    Route::get('clients/active', [\App\Domains\Client\Controllers\ClientController::class, 'getActiveClients'])->name('clients.active');
    Route::post('clients/select/{client}', [\App\Domains\Client\Controllers\ClientController::class, 'selectClient'])->name('clients.select');
    Route::get('clients/clear-selection', [\App\Domains\Client\Controllers\ClientController::class, 'clearSelection'])->name('clients.clear-selection');
    Route::post('clients/{client}/convert-lead', [\App\Domains\Client\Controllers\ClientController::class, 'convertLead'])->name('clients.convert-lead');
    Route::resource('clients', \App\Domains\Client\Controllers\ClientController::class);
    Route::get('clients/switch', [\App\Domains\Client\Controllers\ClientController::class, 'switch'])->name('clients.switch');
    Route::prefix('clients/{client}')->name('clients.')->group(function () {
        Route::match(['get', 'post'], 'tags', [\App\Domains\Client\Controllers\ClientController::class, 'tags'])->name('tags');
        Route::patch('notes', [\App\Domains\Client\Controllers\ClientController::class, 'updateNotes'])->name('update-notes');
        Route::post('archive', [\App\Domains\Client\Controllers\ClientController::class, 'archive'])->name('archive');
        Route::post('restore', [\App\Domains\Client\Controllers\ClientController::class, 'restore'])->name('restore');
        Route::get('contacts/export', [\App\Domains\Client\Controllers\ContactController::class, 'export'])->name('contacts.export');
        Route::resource('contacts', \App\Domains\Client\Controllers\ContactController::class);
        Route::get('locations/export', [\App\Domains\Client\Controllers\LocationController::class, 'export'])->name('locations.export');
        Route::resource('locations', \App\Domains\Client\Controllers\LocationController::class);
        Route::resource('files', \App\Domains\Client\Controllers\FileController::class);
        Route::resource('documents', \App\Domains\Client\Controllers\DocumentController::class);
    });
    
    // Ticket routes
    Route::resource('tickets', \App\Domains\Ticket\Controllers\TicketController::class);
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::resource('calendar', \App\Domains\Ticket\Controllers\CalendarController::class);
        Route::resource('templates', \App\Domains\Ticket\Controllers\TemplateController::class);
        Route::resource('recurring', \App\Domains\Ticket\Controllers\RecurringTicketController::class);
        Route::resource('time-tracking', \App\Domains\Ticket\Controllers\TimeTrackingController::class);
        Route::resource('priority-queue', \App\Domains\Ticket\Controllers\PriorityQueueController::class);
        Route::resource('workflows', \App\Domains\Ticket\Controllers\WorkflowController::class);
        Route::resource('assignments', \App\Domains\Ticket\Controllers\AssignmentController::class);
        Route::get('export/csv', [\App\Domains\Ticket\Controllers\TicketController::class, 'exportCsv'])->name('export.csv');
    });
    
    // Asset routes
    Route::resource('assets', \App\Domains\Asset\Controllers\AssetController::class);
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::resource('maintenance', \App\Domains\Asset\Controllers\MaintenanceController::class);
        Route::resource('warranties', \App\Domains\Asset\Controllers\WarrantyController::class);
        Route::resource('depreciation', \App\Domains\Asset\Controllers\DepreciationController::class);
        Route::get('checkinout', [\App\Domains\Asset\Controllers\AssetController::class, 'checkinout'])->name('checkinout');
        Route::get('bulk', [\App\Domains\Asset\Controllers\AssetController::class, 'bulk'])->name('bulk');
        Route::get('import', [\App\Domains\Asset\Controllers\AssetController::class, 'importForm'])->name('import.form');
        Route::post('import', [\App\Domains\Asset\Controllers\AssetController::class, 'import'])->name('import');
        Route::get('export', [\App\Domains\Asset\Controllers\AssetController::class, 'export'])->name('export');
        Route::get('template/download', [\App\Domains\Asset\Controllers\AssetController::class, 'downloadTemplate'])->name('template.download');
    });
    
    // Project routes
    Route::resource('projects', \App\Domains\Project\Controllers\ProjectController::class);
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::resource('{project}/tasks', \App\Domains\Project\Controllers\TaskController::class);
    });
    
    // Report routes
    Route::resource('reports', \App\Domains\Report\Controllers\ReportController::class);
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('financial', [\App\Domains\Report\Controllers\ReportController::class, 'financial'])->name('financial');
        Route::get('tickets', [\App\Domains\Report\Controllers\ReportController::class, 'tickets'])->name('tickets');
        Route::get('assets', [\App\Domains\Report\Controllers\ReportController::class, 'assets'])->name('assets');
        Route::get('clients', [\App\Domains\Report\Controllers\ReportController::class, 'clients'])->name('clients');
        Route::get('projects', [\App\Domains\Report\Controllers\ReportController::class, 'projects'])->name('projects');
        Route::get('users', [\App\Domains\Report\Controllers\ReportController::class, 'users'])->name('users');
        
        // Additional report routes
        Route::get('category/{category}', [\App\Domains\Report\Controllers\ReportController::class, 'category'])->name('category');
        Route::get('builder/{reportId}', [\App\Domains\Report\Controllers\ReportController::class, 'builder'])->name('builder');
        Route::post('generate/{reportId}', [\App\Domains\Report\Controllers\ReportController::class, 'generate'])->name('generate');
        Route::post('save', [\App\Domains\Report\Controllers\ReportController::class, 'save'])->name('save');
        Route::post('schedule', [\App\Domains\Report\Controllers\ReportController::class, 'schedule'])->name('schedule');
        Route::get('scheduled', [\App\Domains\Report\Controllers\ReportController::class, 'scheduled'])->name('scheduled');
    });
    
    // Search route
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'search'])->name('search');
    
    // User routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\UserController::class, 'profile'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\UserController::class, 'updateProfile'])->name('profile.update');
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\UserController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\UserController::class, 'store'])->name('store');
        Route::get('/{user}', [\App\Http\Controllers\UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
    });
    
    // Settings routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', fn() => view('settings.index'))->name('index');
        Route::get('/general', fn() => view('settings.general'))->name('general');
        Route::get('/security', fn() => view('settings.security'))->name('security');
        Route::get('/email', fn() => view('settings.email'))->name('email');
        Route::get('/integrations', fn() => view('settings.integrations'))->name('integrations');
    });
    
    // Collections Dashboard
    Route::get('/collections/dashboard', [\App\Http\Controllers\CollectionDashboardController::class, 'index'])->name('collections.dashboard');
});

// Authentication routes will be handled by Laravel Fortify

// Financial module routes
Route::middleware(['auth', 'verified'])->prefix('financial')->name('financial.')->group(function () {
    
    // Quote routes
    Route::resource('quotes', QuoteController::class);
    Route::prefix('quotes/{quote}')->name('quotes.')->group(function () {
        Route::post('approve', [QuoteController::class, 'approve'])->name('approve');
        Route::post('reject', [QuoteController::class, 'reject'])->name('reject');
        Route::post('send', [QuoteController::class, 'send'])->name('send');
        Route::get('pdf', [QuoteController::class, 'generatePdf'])->name('pdf');
        Route::post('duplicate', [QuoteController::class, 'duplicate'])->name('duplicate');
        Route::post('convert-to-invoice', [QuoteController::class, 'convertToInvoice'])->name('convert-to-invoice');
        Route::post('convert-to-contract', [QuoteController::class, 'convertToContract'])->name('convert-to-contract');
        Route::get('approval-history', [QuoteController::class, 'approvalHistory'])->name('approval-history');
        Route::get('versions', [QuoteController::class, 'versions'])->name('versions');
        Route::post('versions/{version}/restore', [QuoteController::class, 'restoreVersion'])->name('versions.restore');
    });

    // Invoice routes
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/export/csv', [InvoiceController::class, 'exportCsv'])->name('invoices.export.csv');
    Route::prefix('invoices/{invoice}')->name('invoices.')->group(function () {
        Route::post('send', [InvoiceController::class, 'send'])->name('send');
        Route::get('pdf', [InvoiceController::class, 'generatePdf'])->name('pdf');
        Route::post('duplicate', [InvoiceController::class, 'duplicate'])->name('duplicate');
        Route::post('items', [InvoiceController::class, 'addItem'])->name('items.store');
        Route::put('items/{item}', [InvoiceController::class, 'updateItem'])->name('items.update');
        Route::delete('items/{item}', [InvoiceController::class, 'deleteItem'])->name('items.destroy');
        Route::post('payments', [InvoiceController::class, 'addPayment'])->name('payments.store');
        Route::patch('status', [InvoiceController::class, 'updateStatus'])->name('update-status');
        Route::get('timeline', [InvoiceController::class, 'timeline'])->name('timeline');
    });

    // Contract routes
    Route::resource('contracts', ContractController::class);
    Route::prefix('contracts/{contract}')->name('contracts.')->group(function () {
        Route::post('approve', [ContractController::class, 'approve'])->name('approve');
        Route::post('reject', [ContractController::class, 'reject'])->name('reject');
        Route::post('send-for-signature', [ContractController::class, 'sendForSignature'])->name('send-for-signature');
        Route::post('activate', [ContractController::class, 'activate'])->name('activate');
        Route::post('terminate', [ContractController::class, 'terminate'])->name('terminate');
        Route::post('renew', [ContractController::class, 'renew'])->name('renew');
        Route::get('pdf', [ContractController::class, 'generatePdf'])->name('pdf');
        Route::post('duplicate', [ContractController::class, 'duplicate'])->name('duplicate');
        Route::get('approval-history', [ContractController::class, 'approvalHistory'])->name('approval-history');
        Route::get('audit-trail', [ContractController::class, 'auditTrail'])->name('audit-trail');
        Route::post('milestones', [ContractController::class, 'addMilestone'])->name('milestones.store');
        Route::put('milestones/{milestone}', [ContractController::class, 'updateMilestone'])->name('milestones.update');
        Route::delete('milestones/{milestone}', [ContractController::class, 'deleteMilestone'])->name('milestones.destroy');
        Route::post('milestones/{milestone}/complete', [ContractController::class, 'completeMilestone'])->name('milestones.complete');
        Route::post('convert-to-invoice', [ContractController::class, 'convertToInvoice'])->name('convert-to-invoice');
        Route::get('compliance-status', [ContractController::class, 'complianceStatus'])->name('compliance-status');
    });

    // Payment routes
    Route::resource('payments', \App\Http\Controllers\PaymentController::class);
    
    // Expense routes
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class);

    // Contract Analytics routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [ContractAnalyticsController::class, 'index'])->name('index');
        Route::get('revenue', [ContractAnalyticsController::class, 'revenueAnalytics'])->name('revenue');
        Route::get('performance', [ContractAnalyticsController::class, 'performanceMetrics'])->name('performance');
        Route::get('clients', [ContractAnalyticsController::class, 'clientAnalytics'])->name('clients');
        Route::get('forecast', [ContractAnalyticsController::class, 'revenueForecast'])->name('forecast');
        Route::get('risk', [ContractAnalyticsController::class, 'riskAnalytics'])->name('risk');
        Route::get('lifecycle', [ContractAnalyticsController::class, 'lifecycleAnalytics'])->name('lifecycle');
        Route::post('export', [ContractAnalyticsController::class, 'exportReport'])->name('export');
    });

    // Additional utility routes
    Route::get('clients/search', [QuoteController::class, 'searchClients'])->name('clients.search');
    Route::get('products/search', [QuoteController::class, 'searchProducts'])->name('products.search');
    Route::get('templates/{type}', [ContractController::class, 'getTemplates'])->name('templates.index');
});

// API routes for contract analytics (for AJAX calls)
Route::middleware(['auth:sanctum'])->prefix('api/financial/analytics')->name('api.analytics.')->group(function () {
    Route::get('overview', [ContractAnalyticsController::class, 'revenueAnalytics']);
    Route::get('revenue/{period?}', [ContractAnalyticsController::class, 'revenueAnalytics']);
    Route::get('performance', [ContractAnalyticsController::class, 'performanceMetrics']);
    Route::get('clients', [ContractAnalyticsController::class, 'clientAnalytics']);
    Route::get('forecast', [ContractAnalyticsController::class, 'revenueForecast']);
    Route::get('risk', [ContractAnalyticsController::class, 'riskAnalytics']);
    Route::get('lifecycle', [ContractAnalyticsController::class, 'lifecycleAnalytics']);
});

// Webhook routes for external integrations (digital signatures, etc.)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('docusign', [ContractController::class, 'docusignWebhook'])->name('docusign');
    Route::post('hellosign', [ContractController::class, 'hellosignWebhook'])->name('hellosign');
    Route::post('adobe-sign', [ContractController::class, 'adobeSignWebhook'])->name('adobe-sign');
});

// Public routes for client portal
Route::prefix('client-portal')->name('client.')->group(function () {
    // Guest routes (login, etc.)
    Route::get('login', [\App\Domains\Client\Controllers\ClientPortalController::class, 'showLogin'])->name('login');
    Route::post('login', [\App\Domains\Client\Controllers\ClientPortalController::class, 'login'])->name('login.submit');
    
    // Authenticated client routes
    Route::middleware('auth:client')->group(function () {
        Route::get('dashboard', [\App\Domains\Client\Controllers\ClientPortalController::class, 'dashboard'])->name('dashboard');
        Route::post('logout', [\App\Domains\Client\Controllers\ClientPortalController::class, 'logout'])->name('logout');
        
        // Contracts
        Route::get('contracts', [\App\Domains\Client\Controllers\ClientPortalController::class, 'contracts'])->name('contracts');
        Route::get('contracts/{contract}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'viewContract'])->name('contracts.show');
        Route::post('contracts/{contract}/sign', [\App\Domains\Client\Controllers\ClientPortalController::class, 'signContract'])->name('contracts.sign');
        Route::get('contracts/{contract}/download', [\App\Domains\Client\Controllers\ClientPortalController::class, 'downloadContract'])->name('contracts.download');
        
        // Milestones
        Route::get('contracts/{contract}/milestones/{milestone}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'viewMilestone'])->name('milestones.show');
        Route::post('contracts/{contract}/milestones/{milestone}/progress', [\App\Domains\Client\Controllers\ClientPortalController::class, 'updateMilestoneProgress'])->name('milestones.progress');
        
        // Invoices
        Route::get('contracts/{contract}/invoices', [\App\Domains\Client\Controllers\ClientPortalController::class, 'contractInvoices'])->name('invoices.index');
        Route::get('contracts/{contract}/invoices/{invoice}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'viewInvoice'])->name('invoices.show');
        Route::get('contracts/{contract}/invoices/{invoice}/download', [\App\Domains\Client\Controllers\ClientPortalController::class, 'downloadInvoice'])->name('invoices.download');
        
        // Profile
        Route::get('profile', [\App\Domains\Client\Controllers\ClientPortalController::class, 'profile'])->name('profile');
        Route::put('profile', [\App\Domains\Client\Controllers\ClientPortalController::class, 'updateProfile'])->name('profile.update');
    });
});
