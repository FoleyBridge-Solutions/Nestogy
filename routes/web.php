<?php

use Illuminate\Support\Facades\Route;
use App\Models\Client;
use App\Domains\Financial\Controllers\{
    QuoteController,
    InvoiceController,
    ReportController
    // ContractAnalyticsController - TODO: Create this controller
};
use App\Domains\Lead\Controllers\{
    LeadController
};
use App\Domains\Marketing\Controllers\{
    CampaignController
};
use App\Domains\Contract\Controllers\{
    ContractController
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

Route::get('/', [\App\Http\Controllers\WelcomeController::class, 'index']);

// Setup Wizard Routes (when no companies exist)
Route::prefix('setup')->name('setup.wizard.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SetupWizardController::class, 'index'])->name('index');
    Route::get('/company', [\App\Http\Controllers\SetupWizardController::class, 'showSetup'])->name('company-form');
    Route::post('/company', [\App\Http\Controllers\SetupWizardController::class, 'processSetup'])->name('process');
    Route::post('/test-smtp', [\App\Http\Controllers\SetupWizardController::class, 'testSmtp'])->name('test-smtp');
});

// Redirect /register to our SaaS signup form
Route::get('/register', function () {
    return redirect()->route('signup.form');
});

// Company Registration Routes (pre-login)
Route::prefix('signup')->name('signup.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CompanyRegistrationController::class, 'showRegistrationForm'])->name('form');
    Route::post('/', [\App\Http\Controllers\CompanyRegistrationController::class, 'register'])->name('submit');
    Route::get('plans', [\App\Http\Controllers\CompanyRegistrationController::class, 'getPlans'])->name('plans');
    Route::post('validate-step', [\App\Http\Controllers\CompanyRegistrationController::class, 'validateStep'])->name('validate-step');
});

// Security verification routes (suspicious login handling)
Route::prefix('security')->name('security.')->group(function () {
    Route::prefix('suspicious-login')->name('suspicious-login.')->group(function () {
        Route::match(['GET', 'POST'], 'approve/{token}', [\App\Domains\Security\Controllers\SuspiciousLoginController::class, 'approve'])->name('approve');
        Route::match(['GET', 'POST'], 'deny/{token}', [\App\Domains\Security\Controllers\SuspiciousLoginController::class, 'deny'])->name('deny');
        Route::get('status/{token}', [\App\Domains\Security\Controllers\SuspiciousLoginController::class, 'status'])->name('status');
        Route::post('check-approval', [\App\Domains\Security\Controllers\SuspiciousLoginController::class, 'checkApproval'])->name('check-approval');
    });
    
    // Security Dashboard Routes (requires authentication)
    Route::middleware(['auth', 'verified'])->prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [\App\Domains\Security\Controllers\SecurityDashboardController::class, 'index'])->name('index');
        Route::get('/suspicious-logins', [\App\Domains\Security\Controllers\SecurityDashboardController::class, 'suspiciousLogins'])->name('suspicious-logins');
        Route::get('/ip-intelligence', [\App\Domains\Security\Controllers\SecurityDashboardController::class, 'ipIntelligence'])->name('ip-intelligence');
        Route::get('/trusted-devices', [\App\Domains\Security\Controllers\SecurityDashboardController::class, 'trustedDevices'])->name('trusted-devices');
        Route::patch('/trusted-devices/{device}/revoke', [\App\Domains\Security\Controllers\SecurityDashboardController::class, 'revokeDevice'])->name('trusted-devices.revoke');
        Route::post('/block-ip', [\App\Domains\Security\Controllers\SecurityDashboardController::class, 'blockIp'])->name('block-ip');
        Route::post('/unblock-ip', [\App\Domains\Security\Controllers\SecurityDashboardController::class, 'unblockIp'])->name('unblock-ip');
    });
});

// Additional auth route for checking suspicious login approval
Route::post('/auth/check-suspicious-login', [\App\Http\Controllers\Auth\LoginController::class, 'checkSuspiciousLoginApproval'])->name('auth.check-suspicious-login');

// Custom secure authentication routes (override Fortify)
Route::middleware('guest')->group(function() {
    // Override Fortify's login routes with our secure implementation
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
    
    // Company selection routes for multi-company users
    Route::get('/auth/select-company', [\App\Http\Controllers\Auth\LoginController::class, 'showCompanySelection'])->name('auth.company-select');
    Route::post('/auth/select-company', [\App\Http\Controllers\Auth\LoginController::class, 'selectCompany']);
});

// Logout route (authenticated users only)
Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// New Livewire Dashboard
Route::get('/dashboard', \App\Livewire\Dashboard\MainDashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard-enhanced', function () {
    return view('dashboard-enhanced');
})->middleware(['auth', 'verified'])->name('dashboard.enhanced');

// Dashboard API endpoints
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/api/dashboard/realtime', [\App\Http\Controllers\DashboardController::class, 'getRealtimeData'])->name('dashboard.realtime');
    Route::get('/api/dashboard/export', [\App\Http\Controllers\DashboardController::class, 'exportData'])->name('dashboard.export');
    Route::get('/api/dashboard/notifications', [\App\Http\Controllers\DashboardController::class, 'getNotifications'])->name('dashboard.notifications');
    Route::post('/api/dashboard/notifications/{id}/read', [\App\Http\Controllers\DashboardController::class, 'markNotificationRead'])->name('dashboard.notifications.read');
    
    // Widget endpoints
    Route::get('/api/dashboard/widget', [\App\Http\Controllers\DashboardController::class, 'getWidgetData'])->name('dashboard.widget');
    Route::post('/api/dashboard/widgets/multiple', [\App\Http\Controllers\DashboardController::class, 'getMultipleWidgetData'])->name('dashboard.widgets.multiple');
    
    // Configuration endpoints
    Route::post('/api/dashboard/config/save', [\App\Http\Controllers\DashboardController::class, 'saveDashboardConfig'])->name('dashboard.config.save');
    Route::get('/api/dashboard/config/load', [\App\Http\Controllers\DashboardController::class, 'loadDashboardConfig'])->name('dashboard.config.load');
    
    // Preset endpoints
    Route::get('/api/dashboard/presets', [\App\Http\Controllers\DashboardController::class, 'getPresets'])->name('dashboard.presets');
    Route::post('/api/dashboard/preset/apply', [\App\Http\Controllers\DashboardController::class, 'applyPreset'])->name('dashboard.preset.apply');
});

// Company switching route
Route::post('/switch-company', [\App\Http\Middleware\SubsidiaryAccessMiddleware::class, 'handleCompanySwitch'])
    ->middleware(['auth', 'verified'])
    ->name('company.switch');

// Subsidiary Management Routes
Route::middleware(['auth', 'verified', 'subsidiary.access'])->group(function () {
    Route::prefix('subsidiaries')->name('subsidiaries.')->group(function () {
        // Main subsidiary management
        Route::get('/', [\App\Http\Controllers\SubsidiaryManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SubsidiaryManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SubsidiaryManagementController::class, 'store'])->name('store');
        Route::get('/{subsidiary}', [\App\Http\Controllers\SubsidiaryManagementController::class, 'show'])->name('show');
        Route::get('/{subsidiary}/edit', [\App\Http\Controllers\SubsidiaryManagementController::class, 'edit'])->name('edit');
        Route::put('/{subsidiary}', [\App\Http\Controllers\SubsidiaryManagementController::class, 'update'])->name('update');
        Route::delete('/{subsidiary}', [\App\Http\Controllers\SubsidiaryManagementController::class, 'destroy'])->name('destroy');
        
        // Hierarchy visualization
        Route::get('/hierarchy/tree', [\App\Http\Controllers\SubsidiaryManagementController::class, 'hierarchyTree'])->name('hierarchy.tree');
        
        // Permission management
        Route::get('/{subsidiary}/permissions', [\App\Http\Controllers\SubsidiaryManagementController::class, 'permissions'])->name('permissions');
        Route::post('/permissions/grant', [\App\Http\Controllers\SubsidiaryManagementController::class, 'grantPermission'])->name('grant-permission');
        Route::delete('/permissions/{permission}/revoke', [\App\Http\Controllers\SubsidiaryManagementController::class, 'revokePermission'])->name('revoke-permission');
        
        // User management
        Route::get('/{subsidiary}/users', [\App\Http\Controllers\SubsidiaryManagementController::class, 'users'])->name('users');
        Route::post('/users/grant-access', [\App\Http\Controllers\SubsidiaryManagementController::class, 'grantUserAccess'])->name('grant-user-access');
        Route::delete('/users/{crossCompanyUser}/revoke', [\App\Http\Controllers\SubsidiaryManagementController::class, 'revokeUserAccess'])->name('revoke-user-access');
    });
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
    Route::get('clients/leads/import', [\App\Domains\Client\Controllers\ClientController::class, 'leadsImportForm'])->name('clients.leads.import.form');
    Route::post('clients/leads/import', [\App\Domains\Client\Controllers\ClientController::class, 'leadsImport'])->name('clients.leads.import');
    Route::get('clients/leads/import/template', [\App\Domains\Client\Controllers\ClientController::class, 'leadsImportTemplate'])->name('clients.leads.import.template');
    Route::get('clients/active', [\App\Domains\Client\Controllers\ClientController::class, 'getActiveClients'])->name('clients.active');
    Route::post('clients/select/{client}', [\App\Domains\Client\Controllers\ClientController::class, 'selectClient'])->name('clients.select');
    Route::get('clients/clear-selection', [\App\Domains\Client\Controllers\ClientController::class, 'clearSelection'])->name('clients.clear-selection');
    Route::get('clients/select-screen', [\App\Domains\Client\Controllers\ClientController::class, 'selectScreen'])->name('clients.select-screen');
    Route::post('clients/{client}/convert-lead', [\App\Domains\Client\Controllers\ClientController::class, 'convertLead'])->name('clients.convert-lead');
    
    // Dynamic clients route - show list or specific client dashboard based on query/session
    Route::get('clients', [\App\Domains\Client\Controllers\ClientController::class, 'dynamicIndex'])->name('clients.index');
    
    // Backward compatibility route for clients.show - redirect to dynamic route
    Route::get('clients/{client}', function(Client $client) {
        return redirect()->route('clients.index', ['client' => $client->id]);
    })->name('clients.show');
    
    Route::resource('clients', \App\Domains\Client\Controllers\ClientController::class)->except(['index', 'show']);
    Route::get('clients/switch', [\App\Domains\Client\Controllers\ClientController::class, 'switch'])->name('clients.switch');
    Route::prefix('clients/{client}')->name('clients.')->group(function () {
        Route::match(['get', 'post'], 'tags', [\App\Domains\Client\Controllers\ClientController::class, 'tags'])->name('tags');
        Route::patch('notes', [\App\Domains\Client\Controllers\ClientController::class, 'updateNotes'])->name('update-notes');
        Route::post('archive', [\App\Domains\Client\Controllers\ClientController::class, 'archive'])->name('archive');
        Route::post('restore', [\App\Domains\Client\Controllers\ClientController::class, 'restore'])->name('restore');
        Route::get('contacts/export', [\App\Domains\Client\Controllers\ContactController::class, 'export'])->name('contacts.export');
        Route::resource('contacts', \App\Domains\Client\Controllers\ContactController::class);
        
        // Contact API routes for modal functionality
        Route::prefix('contacts/{contact}')->name('contacts.')->group(function () {
            Route::put('portal-access', [\App\Domains\Client\Controllers\ContactController::class, 'updatePortalAccess'])->name('portal-access.update');
            Route::put('security', [\App\Domains\Client\Controllers\ContactController::class, 'updateSecurity'])->name('security.update');
            Route::put('permissions', [\App\Domains\Client\Controllers\ContactController::class, 'updatePermissions'])->name('permissions.update');
            Route::post('lock', [\App\Domains\Client\Controllers\ContactController::class, 'lockAccount'])->name('lock');
            Route::post('unlock', [\App\Domains\Client\Controllers\ContactController::class, 'unlockAccount'])->name('unlock');
            Route::post('reset-failed-attempts', [\App\Domains\Client\Controllers\ContactController::class, 'resetFailedAttempts'])->name('reset-failed-attempts');
        });
        Route::get('locations/export', [\App\Domains\Client\Controllers\LocationController::class, 'export'])->name('locations.export');
        Route::resource('locations', \App\Domains\Client\Controllers\LocationController::class);
         Route::resource('files', \App\Domains\Client\Controllers\FileController::class);
         Route::resource('documents', \App\Domains\Client\Controllers\DocumentController::class);
         Route::resource('vendors', \App\Domains\Client\Controllers\VendorController::class);
         Route::resource('licenses', \App\Domains\Client\Controllers\LicenseController::class);
         Route::resource('credentials', \App\Domains\Client\Controllers\CredentialController::class);
         Route::resource('domains', \App\Domains\Client\Controllers\DomainController::class);
         Route::resource('services', \App\Domains\Client\Controllers\ServiceController::class);

         // Asset routes for specific client
         Route::get('assets', [\App\Domains\Asset\Controllers\AssetController::class, 'clientIndex'])->name('assets.index');
        Route::get('assets/create', [\App\Domains\Asset\Controllers\AssetController::class, 'clientCreate'])->name('assets.create');
        Route::post('assets', [\App\Domains\Asset\Controllers\AssetController::class, 'clientStore'])->name('assets.store');
        Route::get('assets/{asset}', [\App\Domains\Asset\Controllers\AssetController::class, 'clientShow'])->name('assets.show');
        Route::get('assets/{asset}/edit', [\App\Domains\Asset\Controllers\AssetController::class, 'clientEdit'])->name('assets.edit');
        Route::put('assets/{asset}', [\App\Domains\Asset\Controllers\AssetController::class, 'clientUpdate'])->name('assets.update');
        Route::delete('assets/{asset}', [\App\Domains\Asset\Controllers\AssetController::class, 'clientDestroy'])->name('assets.destroy');
        
        // IT Documentation routes for specific client
        Route::get('it-documentation', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'clientIndex'])->name('it-documentation.client-index');
    });
    
    // IT Documentation routes (global)
    Route::prefix('it-documentation')->name('clients.it-documentation.')->group(function () {
        Route::get('/', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'store'])->name('store');
        Route::get('/export', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'export'])->name('export');
        Route::get('/overdue-reviews', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'overdueReviews'])->name('overdue-reviews');
        Route::post('/bulk-update-access', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'bulkUpdateAccess'])->name('bulk-update-access');
        Route::get('/{itDocumentation}', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'show'])->name('show');
        Route::get('/{itDocumentation}/edit', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'edit'])->name('edit');
        Route::put('/{itDocumentation}', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'update'])->name('update');
        Route::delete('/{itDocumentation}', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'destroy'])->name('destroy');
        Route::get('/{itDocumentation}/download', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'download'])->name('download');
        Route::post('/{itDocumentation}/version', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'createVersion'])->name('create-version');
        Route::post('/{itDocumentation}/duplicate', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'duplicate'])->name('duplicate');
        Route::post('/{itDocumentation}/complete-review', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'completeReview'])->name('complete-review');
    });
    
    // Ticket routes - specific routes first, then resource routes
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::resource('calendar', \App\Domains\Ticket\Controllers\CalendarController::class);
        Route::resource('templates', \App\Domains\Ticket\Controllers\TemplateController::class);
        Route::resource('recurring', \App\Domains\Ticket\Controllers\RecurringTicketController::class);
        Route::resource('time-tracking', \App\Domains\Ticket\Controllers\TimeTrackingController::class);
        
        // Custom time-tracking routes
        Route::post('time-tracking/start-timer', [\App\Domains\Ticket\Controllers\TimeTrackingController::class, 'startTimer'])->name('time-tracking.start-timer');
        Route::post('time-tracking/stop-timer', [\App\Domains\Ticket\Controllers\TimeTrackingController::class, 'stopTimer'])->name('time-tracking.stop-timer');
        
        Route::resource('priority-queue', \App\Domains\Ticket\Controllers\PriorityQueueController::class);
        Route::resource('workflows', \App\Domains\Ticket\Controllers\WorkflowController::class);
        Route::resource('assignments', \App\Domains\Ticket\Controllers\AssignmentController::class);
        
        // Custom assignment routes
        Route::get('{ticket}/assignments/assign', [\App\Domains\Ticket\Controllers\AssignmentController::class, 'assignToMe'])->name('assignments.assign');
        Route::post('{ticket}/assignments/watchers/add', [\App\Domains\Ticket\Controllers\AssignmentController::class, 'addWatcher'])->name('assignments.watchers.add');
        
        // Ticket replies/comments routes
        Route::post('{ticket}/replies', [\App\Domains\Ticket\Controllers\TicketController::class, 'storeReply'])->name('replies.store');
        Route::post('{ticket}/comments', [\App\Domains\Ticket\Controllers\TicketController::class, 'storeReply'])->name('comments.store');
        
        // Resolution routes
        Route::post('{ticket}/resolve', [\App\Domains\Ticket\Controllers\TicketController::class, 'resolve'])->name('resolve');
        Route::post('{ticket}/reopen', [\App\Domains\Ticket\Controllers\TicketController::class, 'reopen'])->name('reopen');
        
        // Ticket PDF export
        Route::get('{ticket}/pdf', [\App\Domains\Ticket\Controllers\TicketController::class, 'generatePdf'])->name('pdf');
        
        // Ticket status update
        Route::patch('{ticket}/status', [\App\Domains\Ticket\Controllers\TicketController::class, 'updateStatus'])->name('status.update');
        
        // Ticket assignment
        Route::patch('{ticket}/assign', [\App\Domains\Ticket\Controllers\TicketController::class, 'assign'])->name('assign');
        
        // Ticket scheduling
        Route::patch('{ticket}/schedule', [\App\Domains\Ticket\Controllers\TicketController::class, 'schedule'])->name('schedule');
        
        // Ticket merging
        Route::post('{ticket}/merge', [\App\Domains\Ticket\Controllers\TicketController::class, 'merge'])->name('merge');
        
        // Ticket search for merge functionality
        Route::get('search', [\App\Domains\Ticket\Controllers\TicketController::class, 'search'])->name('search');
        
        // Ticket viewers (collision detection)
        Route::get('{ticket}/viewers', [\App\Domains\Ticket\Controllers\TicketController::class, 'getViewers'])->name('viewers');
        
        // Smart Time Tracking Routes
        Route::get('{ticket}/smart-tracking-info', [\App\Domains\Ticket\Controllers\TicketController::class, 'getSmartTrackingInfo'])->name('smart-tracking-info');
        Route::post('{ticket}/start-smart-timer', [\App\Domains\Ticket\Controllers\TicketController::class, 'startSmartTimer'])->name('start-smart-timer');
        Route::post('{ticket}/pause-timer', [\App\Domains\Ticket\Controllers\TicketController::class, 'pauseTimer'])->name('pause-timer');
        Route::post('{ticket}/stop-timer', [\App\Domains\Ticket\Controllers\TicketController::class, 'stopTimer'])->name('stop-timer');
        Route::post('{ticket}/create-time-from-template', [\App\Domains\Ticket\Controllers\TicketController::class, 'createTimeFromTemplate'])->name('create-time-from-template');
        Route::get('{ticket}/work-type-suggestions', [\App\Domains\Ticket\Controllers\TicketController::class, 'getWorkTypeSuggestions'])->name('work-type-suggestions');
        
        // Smart Time Tracking API Routes
        Route::get('api/billing-dashboard', [\App\Domains\Ticket\Controllers\TicketController::class, 'getBillingDashboard'])->name('api.billing-dashboard');
        Route::post('api/validate-time-entry', [\App\Domains\Ticket\Controllers\TicketController::class, 'validateTimeEntry'])->name('api.validate-time-entry');
        Route::get('api/current-rate-info', [\App\Domains\Ticket\Controllers\TicketController::class, 'getCurrentRateInfo'])->name('api.current-rate-info');
        Route::get('api/time-templates', [\App\Domains\Ticket\Controllers\TicketController::class, 'getTimeTemplates'])->name('api.time-templates');
        
        Route::get('export/csv', [\App\Domains\Ticket\Controllers\TicketController::class, 'exportCsv'])->name('export.csv');
    });
    
    // Main tickets resource routes (must come after specific prefixed routes)
    Route::resource('tickets', \App\Domains\Ticket\Controllers\TicketController::class);
    
    // Asset routes
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
        Route::get('sentiment-analytics', [\App\Http\Controllers\Domains\Report\Controllers\SentimentAnalyticsController::class, 'index'])->name('sentiment-analytics');
        
        // Additional report routes
        Route::get('category/{category}', [\App\Domains\Report\Controllers\ReportController::class, 'category'])->name('category');
        Route::get('builder/{reportId}', [\App\Domains\Report\Controllers\ReportController::class, 'builder'])->name('builder');
        Route::post('generate/{reportId}', [\App\Domains\Report\Controllers\ReportController::class, 'generate'])->name('generate');
        Route::post('save', [\App\Domains\Report\Controllers\ReportController::class, 'save'])->name('save');
        Route::post('schedule', [\App\Domains\Report\Controllers\ReportController::class, 'schedule'])->name('schedule');
        Route::get('scheduled', [\App\Domains\Report\Controllers\ReportController::class, 'scheduled'])->name('scheduled');
        
        // Tax Reporting Routes
        Route::prefix('tax')->name('tax.')->group(function () {
            Route::get('/', [\App\Http\Controllers\TaxReportController::class, 'index'])->name('index');
            Route::get('/summary', [\App\Http\Controllers\TaxReportController::class, 'summary'])->name('summary');
            Route::get('/jurisdictions', [\App\Http\Controllers\TaxReportController::class, 'jurisdictions'])->name('jurisdictions');
            Route::get('/compliance', [\App\Http\Controllers\TaxReportController::class, 'compliance'])->name('compliance');
            Route::get('/performance', [\App\Http\Controllers\TaxReportController::class, 'performance'])->name('performance');
            Route::get('/export', [\App\Http\Controllers\TaxReportController::class, 'export'])->name('export');
            Route::get('/api-data', [\App\Http\Controllers\TaxReportController::class, 'apiData'])->name('api-data');
        });
    
    // Search route
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'search'])->name('search');
    
    // Global AJAX utility routes (authenticated)
    Route::get('shortcuts/active', [App\Domains\Financial\Controllers\QuoteController::class, 'getActiveShortcuts'])->name('shortcuts.active');
    
});
    
    // Navigation API routes
    Route::prefix('api/navigation')->name('api.navigation.')->group(function () {
        Route::get('tree', [\App\Http\Controllers\NavigationController::class, 'getNavigationTree'])->name('tree');
        Route::get('badges', [\App\Http\Controllers\NavigationController::class, 'getBadgeCounts'])->name('badges');
        Route::get('suggestions', [\App\Http\Controllers\NavigationController::class, 'getSuggestions'])->name('suggestions');
        Route::get('recent', [\App\Http\Controllers\NavigationController::class, 'getRecentItems'])->name('recent');
        Route::get('workflow-highlights', [\App\Http\Controllers\NavigationController::class, 'getWorkflowHighlights'])->name('workflow-highlights');
        Route::post('command', [\App\Http\Controllers\NavigationController::class, 'executeCommand'])->name('command');
        Route::post('workflow', [\App\Http\Controllers\NavigationController::class, 'setWorkflow'])->name('workflow');
    });
    
    // Search API routes
    Route::prefix('api/search')->name('api.search.')->group(function () {
        Route::get('query', [\App\Http\Controllers\NavigationController::class, 'search'])->name('query');
        Route::post('command-palette', [\App\Http\Controllers\SearchController::class, 'commandPalette'])->name('command-palette');
    });
    
    // User routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\UserController::class, 'profile'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\UserController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [\App\Http\Controllers\UserController::class, 'updateOwnPassword'])->name('password.update');
        Route::put('/settings', [\App\Http\Controllers\UserController::class, 'updateSettings'])->name('settings.update');
        Route::put('/preferences', [\App\Http\Controllers\UserController::class, 'updatePreferences'])->name('preferences.update');
        Route::delete('/account', [\App\Http\Controllers\UserController::class, 'destroyAccount'])->name('account.destroy');
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
        // Main settings page
        Route::get('/', [\App\Http\Controllers\SettingsController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\SettingsController::class, 'update'])->name('update');
        
        // General Settings
        Route::get('/general', [\App\Http\Controllers\SettingsController::class, 'general'])->name('general');
        Route::put('/general', [\App\Http\Controllers\SettingsController::class, 'updateGeneral'])->name('general.update');
        
        // Security Settings
        Route::get('/security', [\App\Http\Controllers\SettingsController::class, 'security'])->name('security');
        Route::put('/security', [\App\Http\Controllers\SettingsController::class, 'updateSecurity'])->name('security.update');
        
        // Email & Communication Settings
        Route::get('/email', [\App\Http\Controllers\SettingsController::class, 'email'])->name('email');
        Route::put('/email', [\App\Http\Controllers\SettingsController::class, 'updateEmail'])->name('email.update');
        Route::post('/email/test-connection', [\App\Http\Controllers\SettingsController::class, 'testEmailConnection'])->name('email.test-connection');
        Route::get('/email/provider-presets', [\App\Http\Controllers\SettingsController::class, 'getEmailProviderPresets'])->name('email.provider-presets');
        Route::get('/email/config-status', [\App\Http\Controllers\SettingsController::class, 'getMailConfigStatus'])->name('email.config-status');
        Route::post('/email/send-test', [\App\Http\Controllers\SettingsController::class, 'sendRealTestEmail'])->name('email.send-test');
        
        // User Management Settings
        Route::get('/user-management', [\App\Http\Controllers\SettingsController::class, 'userManagement'])->name('user-management');
        Route::put('/user-management', [\App\Http\Controllers\SettingsController::class, 'updateUserManagement'])->name('user-management.update');
        
        // Billing & Financial Settings
        Route::get('/billing-financial', [\App\Http\Controllers\SettingsController::class, 'billingFinancial'])->name('billing-financial');
        Route::put('/billing-financial', [\App\Http\Controllers\SettingsController::class, 'updateBillingFinancial'])->name('billing-financial.update');
        
        // RMM & Monitoring Settings
        Route::get('/rmm-monitoring', [\App\Http\Controllers\SettingsController::class, 'rmmMonitoring'])->name('rmm-monitoring');
        Route::put('/rmm-monitoring', [\App\Http\Controllers\SettingsController::class, 'updateRmmMonitoring'])->name('rmm-monitoring.update');
        
        // Roles & Permissions Management
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [\App\Http\Controllers\RoleController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\RoleController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\RoleController::class, 'store'])->name('store');
            Route::get('/{role}', [\App\Http\Controllers\RoleController::class, 'show'])->name('show');
            Route::get('/{role}/edit', [\App\Http\Controllers\RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [\App\Http\Controllers\RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('destroy');
            Route::post('/{role}/duplicate', [\App\Http\Controllers\RoleController::class, 'duplicate'])->name('duplicate');
            Route::post('/apply-template', [\App\Http\Controllers\RoleController::class, 'applyTemplate'])->name('apply-template');
        });
        
        // Ticketing & Service Desk Settings
        Route::get('/ticketing-service-desk', [\App\Http\Controllers\SettingsController::class, 'ticketingServiceDesk'])->name('ticketing-service-desk');
        Route::put('/ticketing-service-desk', [\App\Http\Controllers\SettingsController::class, 'updateTicketingServiceDesk'])->name('ticketing-service-desk.update');
        
        // Contract Clauses Management
        Route::get('/contract-clauses', [\App\Http\Controllers\SettingsController::class, 'contractClauses'])->name('contract-clauses');
        Route::post('/contract-clauses', [\App\Http\Controllers\SettingsController::class, 'storeContractClause'])->name('contract-clauses.store');
        Route::get('/contract-clauses/{clause}/edit', [\App\Http\Controllers\SettingsController::class, 'editContractClause'])->name('contract-clauses.edit');
        Route::put('/contract-clauses/{clause}', [\App\Http\Controllers\SettingsController::class, 'updateContractClause'])->name('contract-clauses.update');
        Route::delete('/contract-clauses/{clause}', [\App\Http\Controllers\SettingsController::class, 'destroyContractClause'])->name('contract-clauses.destroy');
        Route::post('/contract-clauses/{clause}/duplicate', [\App\Http\Controllers\SettingsController::class, 'duplicateContractClause'])->name('contract-clauses.duplicate');
        Route::post('/contract-clauses/{clause}/update-content', [\App\Http\Controllers\SettingsController::class, 'updateContractClauseContent'])->name('contract-clauses.update-content');
        Route::post('/contract-clauses/bulk-action', [\App\Http\Controllers\SettingsController::class, 'bulkActionContractClauses'])->name('contract-clauses.bulk-action');
        
        // Contract Templates CRUD
        Route::resource('contract-templates', \App\Domains\Contract\Controllers\ContractTemplateController::class)->names('contract-templates');
        
        // Additional Contract Template Actions
        Route::post('/contract-templates/{template}/toggle-default', [\App\Domains\Contract\Controllers\ContractTemplateController::class, 'toggleDefault'])->name('contract-templates.toggle-default');
        Route::post('/contract-templates/{template}/create-version', [\App\Domains\Contract\Controllers\ContractTemplateController::class, 'createVersion'])->name('contract-templates.create-version');
        Route::post('/contract-templates/{template}/duplicate', [\App\Domains\Contract\Controllers\ContractTemplateController::class, 'duplicate'])->name('contract-templates.duplicate');
        Route::get('/contract-templates/{template}/validate', [\App\Domains\Contract\Controllers\ContractTemplateController::class, 'validate'])->name('contract-templates.validate');
        Route::get('/contract-templates/{template}/statistics', [\App\Domains\Contract\Controllers\ContractTemplateController::class, 'statistics'])->name('contract-templates.statistics');

        // Template Clause Management
        Route::get('/template-clauses/{template}', [\App\Http\Controllers\SettingsController::class, 'templateClauses'])->name('template-clauses');
        Route::post('/template-clauses/{template}/attach', [\App\Http\Controllers\SettingsController::class, 'attachTemplateClauses'])->name('template-clauses.attach');
        Route::delete('/template-clauses/{template}/{clause}', [\App\Http\Controllers\SettingsController::class, 'detachTemplateClause'])->name('template-clauses.detach');
        Route::post('/template-clauses/{template}/reorder', [\App\Http\Controllers\SettingsController::class, 'reorderTemplateClauses'])->name('template-clauses.reorder');
        Route::put('/template-clauses/{template}/{clause}', [\App\Http\Controllers\SettingsController::class, 'updateTemplateClause'])->name('template-clauses.update');
        Route::post('/template-clauses/{template}/bulk-attach', [\App\Http\Controllers\SettingsController::class, 'bulkAttachTemplateClauses'])->name('template-clauses.bulk-attach');
        Route::get('/template-clauses/{template}/preview', [\App\Http\Controllers\SettingsController::class, 'previewTemplateWithClauses'])->name('template-clauses.preview');
        
        // SLA Management Routes
        Route::resource('slas', \App\Domains\Ticket\Controllers\SLAController::class)->only(['store', 'show', 'edit', 'update', 'destroy']);
        Route::get('/slas/{sla}/edit', [\App\Domains\Ticket\Controllers\SLAController::class, 'edit'])->name('slas.edit');
        Route::get('/slas/clients', [\App\Domains\Ticket\Controllers\SLAController::class, 'clientAssignments'])->name('slas.clients');
        Route::post('/slas/{sla}/set-default', [\App\Domains\Ticket\Controllers\SLAController::class, 'setDefault'])->name('slas.set-default');
        Route::post('/slas/{sla}/toggle-active', [\App\Domains\Ticket\Controllers\SLAController::class, 'toggleActive'])->name('slas.toggle-active');
        
        // Compliance & Audit Settings
        Route::get('/compliance-audit', [\App\Http\Controllers\SettingsController::class, 'complianceAudit'])->name('compliance-audit');
        Route::put('/compliance-audit', [\App\Http\Controllers\SettingsController::class, 'updateComplianceAudit'])->name('compliance-audit.update');
        
        // Legacy integrations route
        Route::get('/integrations', [\App\Http\Controllers\SettingsController::class, 'integrations'])->name('integrations');
        Route::put('/integrations', [\App\Http\Controllers\SettingsController::class, 'updateIntegrations'])->name('integrations.update');
        
        // Missing Settings Categories
        Route::get('/accounting', [\App\Http\Controllers\SettingsController::class, 'accounting'])->name('accounting');
        Route::put('/accounting', [\App\Http\Controllers\SettingsController::class, 'updateAccounting'])->name('accounting.update');
        
        Route::get('/payment-gateways', [\App\Http\Controllers\SettingsController::class, 'paymentGateways'])->name('payment-gateways');
        Route::put('/payment-gateways', [\App\Http\Controllers\SettingsController::class, 'updatePaymentGateways'])->name('payment-gateways.update');
        
        Route::get('/project-management', [\App\Http\Controllers\SettingsController::class, 'projectManagement'])->name('project-management');
        Route::put('/project-management', [\App\Http\Controllers\SettingsController::class, 'updateProjectManagement'])->name('project-management.update');
        
        Route::get('/asset-inventory', [\App\Http\Controllers\SettingsController::class, 'assetInventory'])->name('asset-inventory');
        Route::put('/asset-inventory', [\App\Http\Controllers\SettingsController::class, 'updateAssetInventory'])->name('asset-inventory.update');
        
        Route::get('/client-portal', [\App\Http\Controllers\SettingsController::class, 'clientPortal'])->name('client-portal');
        Route::put('/client-portal', [\App\Http\Controllers\SettingsController::class, 'updateClientPortal'])->name('client-portal.update');
        
        Route::get('/automation-workflows', [\App\Http\Controllers\SettingsController::class, 'automationWorkflows'])->name('automation-workflows');
        Route::put('/automation-workflows', [\App\Http\Controllers\SettingsController::class, 'updateAutomationWorkflows'])->name('automation-workflows.update');
        
        Route::get('/api-webhooks', [\App\Http\Controllers\SettingsController::class, 'apiWebhooks'])->name('api-webhooks');
        Route::put('/api-webhooks', [\App\Http\Controllers\SettingsController::class, 'updateApiWebhooks'])->name('api-webhooks.update');
        
        Route::get('/performance-optimization', [\App\Http\Controllers\SettingsController::class, 'performanceOptimization'])->name('performance-optimization');
        Route::put('/performance-optimization', [\App\Http\Controllers\SettingsController::class, 'updatePerformanceOptimization'])->name('performance-optimization.update');
        
        Route::get('/reporting-analytics', [\App\Http\Controllers\SettingsController::class, 'reportingAnalytics'])->name('reporting-analytics');
        Route::put('/reporting-analytics', [\App\Http\Controllers\SettingsController::class, 'updateReportingAnalytics'])->name('reporting-analytics.update');
        
        Route::get('/notifications-alerts', [\App\Http\Controllers\SettingsController::class, 'notificationsAlerts'])->name('notifications-alerts');
        Route::put('/notifications-alerts', [\App\Http\Controllers\SettingsController::class, 'updateNotificationsAlerts'])->name('notifications-alerts.update');
        
        Route::get('/mobile-remote', [\App\Http\Controllers\SettingsController::class, 'mobileRemote'])->name('mobile-remote');
        Route::put('/mobile-remote', [\App\Http\Controllers\SettingsController::class, 'updateMobileRemote'])->name('mobile-remote.update');
        
        Route::get('/training-documentation', [\App\Http\Controllers\SettingsController::class, 'trainingDocumentation'])->name('training-documentation');
        Route::put('/training-documentation', [\App\Http\Controllers\SettingsController::class, 'updateTrainingDocumentation'])->name('training-documentation.update');
        
        Route::get('/knowledge-base', [\App\Http\Controllers\SettingsController::class, 'knowledgeBase'])->name('knowledge-base');
        Route::put('/knowledge-base', [\App\Http\Controllers\SettingsController::class, 'updateKnowledgeBase'])->name('knowledge-base.update');
        
        Route::get('/backup-recovery', [\App\Http\Controllers\SettingsController::class, 'backupRecovery'])->name('backup-recovery');
        Route::put('/backup-recovery', [\App\Http\Controllers\SettingsController::class, 'updateBackupRecovery'])->name('backup-recovery.update');
        
        Route::get('/data-management', [\App\Http\Controllers\SettingsController::class, 'dataManagement'])->name('data-management');
        Route::put('/data-management', [\App\Http\Controllers\SettingsController::class, 'updateDataManagement'])->name('data-management.update');
        
        // Settings Management
        Route::get('/templates', [\App\Http\Controllers\SettingsController::class, 'templates'])->name('templates');
        Route::post('/apply-template', [\App\Http\Controllers\SettingsController::class, 'applyTemplate'])->name('apply-template');
        Route::get('/export', [\App\Http\Controllers\SettingsController::class, 'export'])->name('export');
        Route::post('/import', [\App\Http\Controllers\SettingsController::class, 'import'])->name('import');
        
        // Color Customization Routes
        Route::put('/colors', [\App\Http\Controllers\SettingsController::class, 'updateColors'])->name('colors.update');
        Route::post('/colors/preset', [\App\Http\Controllers\SettingsController::class, 'applyColorPreset'])->name('colors.preset');
        Route::post('/colors/reset', [\App\Http\Controllers\SettingsController::class, 'resetColors'])->name('colors.reset');
        
        // Lazy Loading Demo
        Route::get('/lazy-demo', function () {
            return view('settings.lazy-demo');
        })->name('lazy-demo');
        
        // AJAX API endpoints for lazy loading
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/content/{section}', [\App\Http\Controllers\SettingsController::class, 'getContent'])->name('content');
            Route::get('/content/{section}/{tab}', [\App\Http\Controllers\SettingsController::class, 'getTabContent'])->name('tab-content');
            Route::get('/section/{section}', [\App\Http\Controllers\SettingsController::class, 'getSectionData'])->name('section');
            Route::get('/tabs/{section}', [\App\Http\Controllers\SettingsController::class, 'getTabsConfiguration'])->name('tabs');
            Route::get('/navigation-tree', [\App\Http\Controllers\SettingsController::class, 'getNavigationTree'])->name('navigation-tree');
        });

        // Platform-only routes (Company 1 users only)
        Route::middleware(['platform-company'])->prefix('platform')->name('platform.')->group(function () {
            // Subscription plan management
            Route::get('/subscription-plans', [\App\Http\Controllers\SettingsController::class, 'getSubscriptionPlans'])->name('subscription-plans.index');
            Route::post('/subscription-plans', [\App\Http\Controllers\SettingsController::class, 'storeSubscriptionPlan'])->name('subscription-plans.store');
            Route::put('/subscription-plans/{plan}', [\App\Http\Controllers\SettingsController::class, 'updateSubscriptionPlan'])->name('subscription-plans.update');
            Route::delete('/subscription-plans/{plan}', [\App\Http\Controllers\SettingsController::class, 'deleteSubscriptionPlan'])->name('subscription-plans.destroy');
        });
    });
    
    // Admin routes (Company 1 super-admins only)
    Route::prefix('admin')->name('admin.')->middleware('can:manage-subscriptions')->group(function () {
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'index'])->name('index');
            Route::get('/analytics', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'analytics'])->name('analytics');
            Route::get('/export', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'export'])->name('export');
            Route::get('/{client}', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'show'])->name('show');
            Route::post('/{client}/create-tenant', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'createTenant'])->name('create-tenant');
            Route::patch('/{client}/change-plan', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'changePlan'])->name('change-plan');
            Route::delete('/{client}/cancel', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'cancel'])->name('cancel');
            Route::patch('/{client}/reactivate', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'reactivate'])->name('reactivate');
            Route::patch('/{client}/suspend-tenant', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'suspendTenant'])->name('suspend-tenant');
            Route::patch('/{client}/reactivate-tenant', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'reactivateTenant'])->name('reactivate-tenant');
        });
    });
    
    // Collections Dashboard
    Route::get('/collections/dashboard', [\App\Http\Controllers\CollectionDashboardController::class, 'index'])->name('collections.dashboard');
    
    // Customer Billing Portal
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BillingController::class, 'index'])->name('index');
        Route::get('/subscription', [\App\Http\Controllers\BillingController::class, 'subscription'])->name('subscription');
        Route::get('/payment-methods', [\App\Http\Controllers\BillingController::class, 'paymentMethods'])->name('payment-methods');
        Route::get('/change-plan', [\App\Http\Controllers\BillingController::class, 'changePlan'])->name('change-plan');
        Route::patch('/update-plan', [\App\Http\Controllers\BillingController::class, 'updatePlan'])->name('update-plan');
        Route::get('/invoices', [\App\Http\Controllers\BillingController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\BillingController::class, 'downloadInvoice'])->name('invoices.download');
        Route::get('/usage', [\App\Http\Controllers\BillingController::class, 'usage'])->name('usage');
        Route::post('/cancel-subscription', [\App\Http\Controllers\BillingController::class, 'cancelSubscription'])->name('cancel-subscription');
        Route::post('/reactivate-subscription', [\App\Http\Controllers\BillingController::class, 'reactivateSubscription'])->name('reactivate-subscription');
        Route::get('/portal', [\App\Http\Controllers\BillingController::class, 'billingPortal'])->name('portal');
    });
});

// Authentication routes will be handled by Laravel Fortify

// Lead Management routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Temporary route for Livewire Contract Wizard (for development and testing)
    Route::get('/contracts/wizard', \App\Livewire\ContractWizard::class)->name('contracts.wizard');
    
    // Lead resource routes
    Route::resource('leads', LeadController::class);
    
    // Additional lead routes
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('dashboard', [LeadController::class, 'dashboard'])->name('dashboard');
        Route::post('bulk-assign', [LeadController::class, 'bulkAssign'])->name('bulk-assign');
        Route::post('bulk-status', [LeadController::class, 'bulkUpdateStatus'])->name('bulk-status');
        Route::get('export/csv', [LeadController::class, 'exportCsv'])->name('export.csv');
        
        // Import routes
        Route::get('import', [LeadController::class, 'importForm'])->name('import.form');
        Route::post('import', [LeadController::class, 'import'])->name('import');
        Route::get('import/template', [LeadController::class, 'downloadTemplate'])->name('import.template');
        
        Route::prefix('{lead}')->group(function () {
            Route::post('convert', [LeadController::class, 'convertToClient'])->name('convert');
            Route::post('update-score', [LeadController::class, 'updateScore'])->name('update-score');
        });
    });
});

// Marketing Campaign routes
Route::middleware(['auth', 'verified'])->prefix('marketing')->name('marketing.')->group(function () {
    // Campaign management
    Route::resource('campaigns', CampaignController::class);
    
    Route::prefix('campaigns/{campaign}')->name('campaigns.')->group(function () {
        Route::post('start', [CampaignController::class, 'start'])->name('start');
        Route::post('pause', [CampaignController::class, 'pause'])->name('pause');
        Route::post('complete', [CampaignController::class, 'complete'])->name('complete');
        Route::post('clone', [CampaignController::class, 'clone'])->name('clone');
        Route::get('analytics', [CampaignController::class, 'analytics'])->name('analytics');
        
        // Sequence management
        Route::post('sequences', [CampaignController::class, 'addSequence'])->name('sequences.store');
        Route::post('sequences/{sequence}/test-email', [CampaignController::class, 'sendTestEmail'])->name('sequences.test-email');
        
        // Enrollment management
        Route::post('enroll-leads', [CampaignController::class, 'enrollLeads'])->name('enroll-leads');
        Route::post('enroll-contacts', [CampaignController::class, 'enrollContacts'])->name('enroll-contacts');
    });
});

// Email tracking routes (public, no auth required)
Route::get('marketing/email/track-open/{tracking_id}', function($trackingId) {
    app(\App\Domains\Marketing\Services\CampaignEmailService::class)->trackEmailOpen(
        $trackingId,
        request()->header('User-Agent'),
        request()->ip()
    );
    
    // Return 1x1 transparent pixel
    return response()->make(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200)
        ->header('Content-Type', 'image/gif');
})->name('marketing.email.track-open');

Route::get('marketing/email/track-click/{tracking_id}', function($trackingId) {
    $url = urldecode(request('url'));
    
    app(\App\Domains\Marketing\Services\CampaignEmailService::class)->trackEmailClick(
        $trackingId,
        $url,
        request()->header('User-Agent'),
        request()->ip()
    );
    
    return redirect($url);
})->name('marketing.email.track-click');

Route::get('marketing/unsubscribe', function() {
    $enrollmentId = request('enrollment');
    $token = request('token');
    
    // Verify token
    $expectedToken = hash('sha256', $enrollmentId . request('campaign') . config('app.key'));
    if (!hash_equals($expectedToken, $token)) {
        abort(403);
    }
    
    $enrollment = \App\Domains\Marketing\Models\CampaignEnrollment::find($enrollmentId);
    if ($enrollment) {
        $enrollment->unsubscribe();
    }
    
    return view('marketing.unsubscribed');
})->name('marketing.unsubscribe');

// Financial module routes
Route::middleware(['auth', 'verified'])->prefix('financial')->name('financial.')->group(function () {
    
    // Dashboard routes
    Route::get('/', [\App\Domains\Financial\Controllers\FinancialDashboardController::class, 'index'])->name('index');
    Route::get('/dashboard', [\App\Domains\Financial\Controllers\FinancialDashboardController::class, 'index'])->name('dashboard');
    
    // Quote routes
    Route::resource('quotes', QuoteController::class);
    Route::prefix('quotes/{quote}')->name('quotes.')->group(function () {
        Route::post('approve', [QuoteController::class, 'approve'])->name('approve');
        Route::post('reject', [QuoteController::class, 'reject'])->name('reject');
        Route::post('send', [QuoteController::class, 'send'])->name('send');
        Route::post('cancel', [QuoteController::class, 'cancel'])->name('cancel');
        Route::get('pdf', [QuoteController::class, 'generatePdf'])->name('pdf');
        Route::get('copy', [QuoteController::class, 'copy'])->name('copy');
        Route::post('duplicate', [QuoteController::class, 'duplicate'])->name('duplicate');
        Route::post('convert-to-invoice', [QuoteController::class, 'convertToInvoice'])->name('convert-to-invoice');
        Route::get('approval-history', [QuoteController::class, 'approvalHistory'])->name('approval-history');
        Route::get('versions', [QuoteController::class, 'versions'])->name('versions');
        Route::post('versions/{version}/restore', [QuoteController::class, 'restoreVersion'])->name('versions.restore');
    });

    // API routes for enhanced quote/invoice builder
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('quotes/auto-save', [QuoteController::class, 'autoSave'])->name('quotes.auto-save');
        Route::post('quotes/preview-pdf', [QuoteController::class, 'previewPdf'])->name('quotes.preview-pdf');
        Route::post('quotes/email-pdf', [QuoteController::class, 'emailPdf'])->name('quotes.email-pdf');
        Route::post('invoices/auto-save', [InvoiceController::class, 'autoSave'])->name('invoices.auto-save');
        Route::post('invoices/preview-pdf', [InvoiceController::class, 'previewPdf'])->name('invoices.preview-pdf');
        Route::post('invoices/email-pdf', [InvoiceController::class, 'emailPdf'])->name('invoices.email-pdf');
        Route::resource('document-templates', \App\Http\Controllers\Api\DocumentTemplateController::class)->only(['store', 'index']);
        Route::post('document-templates/{template}/favorite', [\App\Http\Controllers\Api\DocumentTemplateController::class, 'toggleFavorite'])->name('document-templates.favorite');
    });

    // Invoice routes
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/overdue', [InvoiceController::class, 'overdue'])->name('invoices.overdue');
    Route::get('invoices/draft', [InvoiceController::class, 'draft'])->name('invoices.draft');
    Route::get('invoices/sent', [InvoiceController::class, 'sent'])->name('invoices.sent');
    Route::get('invoices/paid', [InvoiceController::class, 'paid'])->name('invoices.paid');
    Route::get('invoices/recurring', [InvoiceController::class, 'recurring'])->name('invoices.recurring');
    Route::get('invoices/export/csv', [InvoiceController::class, 'exportCsv'])->name('invoices.export.csv');
    
    // Recurring Invoices
    Route::resource('recurring-invoices', RecurringInvoiceController::class);
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

    // Contract Templates routes (must come before resource routes to avoid conflicts)
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [ContractController::class, 'templatesIndex'])->name('index');
            Route::get('/create', [ContractController::class, 'templatesCreate'])->name('create');
            Route::post('/', [ContractController::class, 'templatesStore'])->name('store');
            Route::get('/{template}', [ContractController::class, 'templatesShow'])->name('show');
            Route::get('/{template}/edit', [ContractController::class, 'templatesEdit'])->name('edit');
            Route::put('/{template}', [ContractController::class, 'templatesUpdate'])->name('update');
            Route::delete('/{template}', [ContractController::class, 'templatesDestroy'])->name('destroy');
        });
    });

    // Dynamic Contract Builder
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/builder/create', [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'index'])->name('builder.index');
        Route::post('/builder/store', [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'store'])->name('builder.store');
        Route::get('/builder/component/{component}', [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'getComponent'])->name('builder.component');
        Route::post('/builder/calculate-pricing', [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'calculatePricing'])->name('builder.calculate');
        Route::get('/builder/template/{template}', [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'loadTemplate'])->name('builder.template');
        Route::post('/builder/preview', [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'preview'])->name('builder.preview');
        Route::post('/builder/draft', [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'saveDraft'])->name('builder.draft.save');
        Route::get('/builder/draft', [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'loadDraft'])->name('builder.draft.load');
        Route::delete('/builder/draft', [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'clearDraft'])->name('builder.draft.clear');
    });

    // Contract routes (resource route comes after specific routes to avoid conflicts)
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('wizard', \App\Livewire\ContractWizard::class)->name('wizard');
    });
    
    Route::resource('contracts', ContractController::class);
    
    // Additional contract routes
    Route::get('contracts-dashboard', [ContractController::class, 'dashboard'])->name('contracts.dashboard');
    Route::get('contracts-expiring', [ContractController::class, 'expiring'])->name('contracts.expiring');
    Route::get('contracts-search', [ContractController::class, 'search'])->name('contracts.search');
    
    Route::prefix('contracts/{contract}')->name('contracts.')->group(function () {
        Route::post('approve', [ContractController::class, 'approve'])->name('approve');
        Route::post('reject', [ContractController::class, 'reject'])->name('reject');
        Route::post('send-for-signature', [ContractController::class, 'sendForSignature'])->name('send-for-signature');
        Route::patch('status', [ContractController::class, 'updateStatus'])->name('update-status');
        Route::post('activate', [ContractController::class, 'activate'])->name('activate');
        Route::post('reactivate', [ContractController::class, 'reactivate'])->name('reactivate');
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
        Route::post('amendments', [ContractController::class, 'createAmendment'])->name('amendments.store');
        Route::post('convert-to-invoice', [ContractController::class, 'convertToInvoice'])->name('convert-to-invoice');
        Route::get('compliance-status', [ContractController::class, 'complianceStatus'])->name('compliance-status');
        
        // Programmable contract features
        Route::get('asset-assignments', [ContractController::class, 'assetAssignments'])->name('asset-assignments');
        Route::get('contact-assignments', [ContractController::class, 'contactAssignments'])->name('contact-assignments');
        Route::get('usage-dashboard', [ContractController::class, 'usageDashboard'])->name('usage-dashboard');
    });

    // Payment routes
    Route::resource('payments', \App\Http\Controllers\PaymentController::class);

    // Expense routes
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class);

    // Recurring invoices routes
    Route::resource('recurring-invoices', \App\Domains\Client\Controllers\RecurringInvoiceController::class);

    // Contract Analytics routes - TODO: Create ContractAnalyticsController
    // Route::prefix('analytics')->name('analytics.')->group(function () {
    //     Route::get('/', [ContractAnalyticsController::class, 'index'])->name('index');
    //     Route::get('revenue', [ContractAnalyticsController::class, 'revenueAnalytics'])->name('revenue');
    //     Route::get('performance', [ContractAnalyticsController::class, 'performanceMetrics'])->name('performance');
    //     Route::get('clients', [ContractAnalyticsController::class, 'clientAnalytics'])->name('clients');
    //     Route::get('forecast', [ContractAnalyticsController::class, 'revenueForecast'])->name('forecast');
    //     Route::get('risk', [ContractAnalyticsController::class, 'riskAnalytics'])->name('risk');
    //     Route::get('lifecycle', [ContractAnalyticsController::class, 'lifecycleAnalytics'])->name('lifecycle');
    //     Route::post('export', [ContractAnalyticsController::class, 'exportReport'])->name('export');
    // });

    // Billing Management Routes
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/schedules', [\App\Domains\Financial\Controllers\BillingController::class, 'schedules'])->name('schedules');
        Route::get('/usage', [\App\Domains\Financial\Controllers\BillingController::class, 'usage'])->name('usage');
    });
    
    // Payment Methods Routes
    Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
        Route::get('/', [\App\Domains\Financial\Controllers\PaymentMethodController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Financial\Controllers\PaymentMethodController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Financial\Controllers\PaymentMethodController::class, 'store'])->name('store');
    });
    
    // Payments Routes
    Route::resource('payments', PaymentController::class);
    
    // Collections Routes
    Route::prefix('collections')->name('collections.')->group(function () {
        Route::get('/', [\App\Domains\Financial\Controllers\CollectionController::class, 'overdue'])->name('index');
        Route::get('/overdue', [\App\Domains\Financial\Controllers\CollectionController::class, 'overdue'])->name('overdue');
        Route::get('/disputes', [\App\Domains\Financial\Controllers\CollectionController::class, 'disputes'])->name('disputes');
        Route::get('/reminders', [\App\Domains\Financial\Controllers\CollectionController::class, 'reminders'])->name('reminders');
        Route::post('/invoices/{invoice}/reminder', [\App\Domains\Financial\Controllers\CollectionController::class, 'sendReminder'])->name('send-reminder');
        Route::post('/invoices/{invoice}/dispute', [\App\Domains\Financial\Controllers\CollectionController::class, 'markDisputed'])->name('mark-disputed');
        Route::post('/invoices/{invoice}/resolve', [\App\Domains\Financial\Controllers\CollectionController::class, 'resolveDispute'])->name('resolve-dispute');
    });
    
    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/revenue', [\App\Domains\Financial\Controllers\ReportController::class, 'revenue'])->name('revenue');
        Route::get('/profit-loss', [\App\Domains\Financial\Controllers\ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/cash-flow', [\App\Domains\Financial\Controllers\ReportController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/aging', [\App\Domains\Financial\Controllers\ReportController::class, 'aging'])->name('aging');
        Route::get('/tax', [\App\Domains\Financial\Controllers\ReportController::class, 'tax'])->name('tax');
    });
    
    // Accounting Routes
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/chart', [\App\Domains\Financial\Controllers\AccountingController::class, 'chartOfAccounts'])->name('chart');
        Route::get('/journal', [\App\Domains\Financial\Controllers\AccountingController::class, 'journalEntries'])->name('journal');
        Route::post('/journal', [\App\Domains\Financial\Controllers\AccountingController::class, 'createJournalEntry'])->name('journal.store');
        Route::get('/reconciliation', [\App\Domains\Financial\Controllers\AccountingController::class, 'reconciliation'])->name('reconciliation');
        Route::post('/reconciliation/reconcile', [\App\Domains\Financial\Controllers\AccountingController::class, 'reconcileTransaction'])->name('reconciliation.reconcile');
        Route::get('/export/trial-balance', [\App\Domains\Financial\Controllers\AccountingController::class, 'exportTrialBalance'])->name('export.trial-balance');
        Route::get('/export/general-ledger', [\App\Domains\Financial\Controllers\AccountingController::class, 'exportGeneralLedger'])->name('export.general-ledger');
    });
    
    // Product & Service Management
    Route::resource('products', \App\Domains\Financial\Controllers\ProductController::class);
    Route::resource('services', \App\Domains\Financial\Controllers\ServiceController::class);
    Route::resource('pricing', \App\Domains\Financial\Controllers\PricingController::class);
    Route::resource('discounts', \App\Domains\Financial\Controllers\DiscountController::class);
    
    // Vendor & Expense Management
    Route::resource('vendors', \App\Domains\Financial\Controllers\VendorController::class);
    Route::resource('expenses', \App\Domains\Financial\Controllers\ExpenseController::class);
    Route::get('expenses/categories', [\App\Domains\Financial\Controllers\ExpenseController::class, 'categories'])->name('expenses.categories');
    Route::post('expenses/{expense}/approve', [\App\Domains\Financial\Controllers\ExpenseController::class, 'approve'])->name('expenses.approve');
    Route::post('expenses/{expense}/reject', [\App\Domains\Financial\Controllers\ExpenseController::class, 'reject'])->name('expenses.reject');
    
    // Purchase Orders
    Route::resource('purchase-orders', \App\Domains\Financial\Controllers\PurchaseOrderController::class);
    
    // Tax Management
    Route::resource('tax-rates', \App\Domains\Financial\Controllers\TaxRateController::class);
    
    // Payment Reminders  
    Route::resource('reminders', \App\Domains\Financial\Controllers\ReminderController::class);
    
    // Budget Management
    Route::resource('budgets', \App\Domains\Financial\Controllers\BudgetController::class);
    Route::get('budgets/comparison', [\App\Domains\Financial\Controllers\BudgetController::class, 'comparison'])->name('budgets.comparison');
    Route::get('budgets/{budget}/forecast', [\App\Domains\Financial\Controllers\BudgetController::class, 'forecast'])->name('budgets.forecast');
    
    // Financial Forecasting
    Route::prefix('forecasts')->name('forecasts.')->group(function () {
        Route::get('/', [\App\Domains\Financial\Controllers\ForecastController::class, 'index'])->name('index');
        Route::get('/revenue', [\App\Domains\Financial\Controllers\ForecastController::class, 'revenue'])->name('revenue');
        Route::get('/cash-flow', [\App\Domains\Financial\Controllers\ForecastController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/growth', [\App\Domains\Financial\Controllers\ForecastController::class, 'growth'])->name('growth');
        Route::get('/scenarios', [\App\Domains\Financial\Controllers\ForecastController::class, 'scenarios'])->name('scenarios');
    });
    
    // Audit & Compliance
    Route::prefix('audits')->name('audits.')->group(function () {
        Route::get('/', [\App\Domains\Financial\Controllers\AuditController::class, 'index'])->name('index');
        Route::get('/transactions', [\App\Domains\Financial\Controllers\AuditController::class, 'transactions'])->name('transactions');
        Route::get('/changes', [\App\Domains\Financial\Controllers\AuditController::class, 'changes'])->name('changes');
        Route::get('/compliance', [\App\Domains\Financial\Controllers\AuditController::class, 'compliance'])->name('compliance');
        Route::get('/trail/{entity}/{id}', [\App\Domains\Financial\Controllers\AuditController::class, 'trail'])->name('trail');
        Route::post('/export', [\App\Domains\Financial\Controllers\AuditController::class, 'export'])->name('export');
    });
    
    // Analytics Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/contracts', [\App\Domains\Financial\Controllers\AnalyticsController::class, 'contracts'])->name('contracts');
    });
    
    // Integrations Routes
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/quickbooks', [\App\Domains\Financial\Controllers\IntegrationController::class, 'quickbooks'])->name('quickbooks');
    });
    
    // Export Routes
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/', [\App\Domains\Financial\Controllers\ExportController::class, 'index'])->name('index');
        Route::post('/invoices', [\App\Domains\Financial\Controllers\ExportController::class, 'exportInvoices'])->name('invoices');
        Route::post('/quotes', [\App\Domains\Financial\Controllers\ExportController::class, 'exportQuotes'])->name('quotes');
        Route::post('/payments', [\App\Domains\Financial\Controllers\ExportController::class, 'exportPayments'])->name('payments');
        Route::post('/expenses', [\App\Domains\Financial\Controllers\ExportController::class, 'exportExpenses'])->name('expenses');
        Route::post('/reports', [\App\Domains\Financial\Controllers\ExportController::class, 'exportReports'])->name('reports');
    });
    
    // Additional utility routes
    Route::get('clients/search', [QuoteController::class, 'searchClients'])->name('clients.search');
});

// Product Management routes
Route::middleware(['auth', 'verified'])->prefix('products')->name('products.')->group(function () {
    // AJAX routes (must be before parameterized routes)
    Route::get('search', [App\Domains\Financial\Controllers\QuoteController::class, 'searchProducts'])->name('search');
    Route::get('categories', [App\Domains\Financial\Controllers\QuoteController::class, 'getProductCategories'])->name('categories');
    
    Route::get('/', [\App\Domains\Product\Controllers\ProductController::class, 'index'])->name('index');
    Route::get('/create', [\App\Domains\Product\Controllers\ProductController::class, 'create'])->name('create');
    Route::post('/', [\App\Domains\Product\Controllers\ProductController::class, 'store'])->name('store');
    Route::get('/{product}', [\App\Domains\Product\Controllers\ProductController::class, 'show'])->name('show');
    Route::get('/{product}/edit', [\App\Domains\Product\Controllers\ProductController::class, 'edit'])->name('edit');
    Route::put('/{product}', [\App\Domains\Product\Controllers\ProductController::class, 'update'])->name('update');
    Route::delete('/{product}', [\App\Domains\Product\Controllers\ProductController::class, 'destroy'])->name('destroy');
    Route::post('/{product}/duplicate', [\App\Domains\Product\Controllers\ProductController::class, 'duplicate'])->name('duplicate');
    Route::post('/bulk-update', [\App\Domains\Product\Controllers\ProductController::class, 'bulkUpdate'])->name('bulk-update');
    Route::get('/export/csv', [\App\Domains\Product\Controllers\ProductController::class, 'export'])->name('export');
    Route::get('/import/form', [\App\Domains\Product\Controllers\ProductController::class, 'import'])->name('import');
    Route::post('/import/process', [\App\Domains\Product\Controllers\ProductController::class, 'processImport'])->name('import.process');
});

// Bundle Management routes
Route::middleware(['auth', 'verified'])->prefix('bundles')->name('bundles.')->group(function () {
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
Route::middleware(['auth', 'verified'])->prefix('pricing-rules')->name('pricing-rules.')->group(function () {
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
Route::middleware(['auth', 'verified'])->prefix('services')->name('services.')->group(function () {
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
    Route::get('/export/csv', [\App\Domains\Product\Controllers\ServiceController::class, 'export'])->name('export');
    
    // Tax calculation routes (legacy - kept for backwards compatibility)
    Route::post('/calculate-tax', [\App\Http\Controllers\Api\ServiceTaxController::class, 'calculateTax'])->name('calculate-tax');
    Route::get('/customer/{customer}/address', [\App\Http\Controllers\Api\ServiceTaxController::class, 'getCustomerAddress'])->name('customer-address');
});

// Comprehensive Tax Engine API routes
Route::middleware(['auth', 'verified'])->prefix('api/tax-engine')->name('tax-engine.')->group(function () {
    // Basic calculation endpoints
    Route::post('/calculate', [\App\Http\Controllers\Api\TaxEngineController::class, 'calculateTax'])->name('calculate');
    Route::post('/calculate-line', [\App\Http\Controllers\Api\TaxEngineController::class, 'calculateLineItemTax'])->name('calculate-line');
    
    // Enhanced bulk calculation endpoints
    Route::post('/calculate-bulk', [\App\Http\Controllers\Api\TaxEngineController::class, 'calculateBulkTax'])->name('calculate-bulk');
    Route::post('/preview-quote', [\App\Http\Controllers\Api\TaxEngineController::class, 'previewQuoteTax'])->name('preview-quote');
    Route::post('/preview-invoice', [\App\Http\Controllers\Api\TaxEngineController::class, 'previewQuoteTax'])->name('preview-invoice'); // Same as quote for now
    
    // Profile and configuration endpoints
    Route::get('/profile', [\App\Http\Controllers\Api\TaxEngineController::class, 'getTaxProfile'])->name('profile');
    Route::get('/required-fields', [\App\Http\Controllers\Api\TaxEngineController::class, 'getRequiredFields'])->name('required-fields');
    Route::post('/validate', [\App\Http\Controllers\Api\TaxEngineController::class, 'validateTaxData'])->name('validate');
    Route::get('/customer/{customer}/address', [\App\Http\Controllers\Api\TaxEngineController::class, 'getCustomerAddress'])->name('customer-address');
    Route::get('/profiles', [\App\Http\Controllers\Api\TaxEngineController::class, 'getAvailableProfiles'])->name('profiles');
    Route::get('/tax-types', [\App\Http\Controllers\Api\TaxEngineController::class, 'getApplicableTaxTypes'])->name('tax-types');
    
    // Performance and cache management endpoints
    Route::post('/cache/clear', [\App\Http\Controllers\Api\TaxEngineController::class, 'clearCaches'])->name('cache.clear');
    Route::post('/cache/warm', [\App\Http\Controllers\Api\TaxEngineController::class, 'warmCaches'])->name('cache.warm');
    Route::get('/statistics', [\App\Http\Controllers\Api\TaxEngineController::class, 'getStatistics'])->name('statistics');
});

// Tax Administration Routes (Admin only)
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin/tax')->name('admin.tax.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TaxAdminController::class, 'index'])->name('index');
    Route::get('/profiles', [\App\Http\Controllers\TaxAdminController::class, 'profiles'])->name('profiles');
    Route::get('/rates', [\App\Http\Controllers\TaxAdminController::class, 'rates'])->name('rates');
    Route::get('/jurisdictions', [\App\Http\Controllers\TaxAdminController::class, 'jurisdictions'])->name('jurisdictions');
    Route::get('/performance', [\App\Http\Controllers\TaxAdminController::class, 'performance'])->name('performance');
    
    // Management actions
    Route::post('/bulk-operations', [\App\Http\Controllers\TaxAdminController::class, 'bulkOperations'])->name('bulk-operations');
    Route::post('/clear-caches', [\App\Http\Controllers\TaxAdminController::class, 'clearCaches'])->name('clear-caches');
    Route::post('/warm-caches', [\App\Http\Controllers\TaxAdminController::class, 'warmCaches'])->name('warm-caches');
    
    // Export and testing
    Route::get('/export-config', [\App\Http\Controllers\TaxAdminController::class, 'exportConfig'])->name('export-config');
    Route::post('/test-calculation', [\App\Http\Controllers\TaxAdminController::class, 'testCalculation'])->name('test-calculation');
});

// RMM Integration API (for settings page AJAX calls)
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
    Route::post('sync-agents', function(\Illuminate\Http\Request $request) {
        $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }
        
        // Check if at least one client has mapping (optional requirement)
        $mappedClients = \App\Models\Client::where('company_id', auth()->user()->company_id)
            ->whereHas('rmmClientMappings')
            ->count();
            
        if ($mappedClients === 0) {
            return response()->json([
                'success' => false, 
                'message' => 'At least one client mapping is required before syncing agents. Please map at least one Nestogy client to an RMM client.',
                'requires_mapping' => true,
                'mapped_count' => $mappedClients
            ], 422);
        }
        
        // Dispatch sync job directly without authorization
        try {
            \App\Jobs\SyncRmmAgents::dispatch($integration);
            return response()->json(['success' => true, 'message' => 'Agents sync job queued successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to trigger sync: ' . $e->getMessage()], 500);
        }
    })->name('sync-agents');
    
    Route::post('sync-alerts', function(\Illuminate\Http\Request $request) {
        $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }
        
        // Check if at least one client has mapping (optional requirement)
        $mappedClients = \App\Models\Client::where('company_id', auth()->user()->company_id)
            ->whereHas('rmmClientMappings')
            ->count();
            
        if ($mappedClients === 0) {
            return response()->json([
                'success' => false, 
                'message' => 'At least one client mapping is required before syncing alerts. Please map at least one Nestogy client to an RMM client.',
                'requires_mapping' => true,
                'mapped_count' => $mappedClients
            ], 422);
        }
        
        // Dispatch sync job directly without authorization
        $filters = $request->only(['from_date', 'to_date', 'severity']);
        try {
            \App\Jobs\SyncRmmAlerts::dispatch($integration, $filters);
            return response()->json(['success' => true, 'message' => 'Alerts sync job queued successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to trigger sync: ' . $e->getMessage()], 500);
        }
    })->name('sync-alerts');
    
    // Get available RMM types
    Route::get('types', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'getAvailableTypes'])->name('types');
    
    // Get integration statistics
    Route::get('stats', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'getStats'])->name('stats');
    
    // Client mapping endpoints
    Route::get('clients/nestogy', function(\Illuminate\Http\Request $request) {
        return response()->json([
            'success' => true,
            'clients' => \App\Models\Client::where('company_id', auth()->user()->company_id)
                ->select('id', 'name', 'company_name', 'status')
                ->with(['rmmClientMappings' => function($query) {
                    $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
                    if ($integration) {
                        $query->where('integration_id', $integration->id);
                    }
                }])
                ->orderBy('name')
                ->get()
        ]);
    })->name('clients.nestogy');
    
    Route::get('clients/rmm', function(\Illuminate\Http\Request $request) {
        $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }
        
        try {
            $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)->make($integration);
            $clientsResult = $rmmService->getClients();
            
            return response()->json([
                'success' => true,
                'clients' => $clientsResult['data'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch RMM clients: ' . $e->getMessage()], 500);
        }
    })->name('clients.rmm');
    
    Route::post('client-mappings', function(\Illuminate\Http\Request $request) {
        $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }
        
        // Log the request data for debugging
        \Illuminate\Support\Facades\Log::info('Client mapping request data:', $request->all());
        
        try {
            $validated = $request->validate([
                'client_id' => 'required',
                'rmm_client_id' => 'required',
                'rmm_client_name' => 'required|string',
            ]);
            
            // Convert rmm_client_id to string if it's not already
            $validated['rmm_client_id'] = (string) $validated['rmm_client_id'];
            
            // Manually check if client exists and belongs to company
            $client = \App\Models\Client::where('id', $validated['client_id'])
                ->where('company_id', auth()->user()->company_id)
                ->first();
                
            if (!$client) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Client not found or access denied'
                ], 422);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Client mapping validation failed:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'company_id' => auth()->user()->company_id
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
        
        try {
            $mapping = \App\Domains\Integration\Models\RmmClientMapping::createOrUpdateMapping([
                'company_id' => auth()->user()->company_id,
                'client_id' => $validated['client_id'],
                'integration_id' => $integration->id,
                'rmm_client_id' => $validated['rmm_client_id'],
                'rmm_client_name' => $validated['rmm_client_name'],
                'is_active' => true,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Client mapping created successfully',
                'mapping' => $mapping->load('client')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create mapping: ' . $e->getMessage()], 500);
        }
    })->name('client-mappings.store');
    
    Route::delete('client-mappings/{mappingId}', function(\Illuminate\Http\Request $request, $mappingId) {
        // Find the mapping with proper company scoping
        $mapping = \App\Domains\Integration\Models\RmmClientMapping::where('id', $mappingId)
            ->where('company_id', auth()->user()->company_id)
            ->first();
            
        if (!$mapping) {
            return response()->json(['success' => false, 'message' => 'Client mapping not found or access denied'], 404);
        }
        
        try {
            $mapping->delete();
            return response()->json(['success' => true, 'message' => 'Client mapping deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete mapping: ' . $e->getMessage()], 500);
        }
    })->name('client-mappings.destroy');
});

// API routes for contract analytics (for AJAX calls) - TODO: Create ContractAnalyticsController
// Route::middleware(['auth:sanctum'])->prefix('api/financial/analytics')->name('api.analytics.')->group(function () {
//     Route::get('overview', [ContractAnalyticsController::class, 'revenueAnalytics']);
//     Route::get('revenue/{period?}', [ContractAnalyticsController::class, 'revenueAnalytics']);
//     Route::get('performance', [ContractAnalyticsController::class, 'performanceMetrics']);
//     Route::get('clients', [ContractAnalyticsController::class, 'clientAnalytics']);
//     Route::get('forecast', [ContractAnalyticsController::class, 'revenueForecast']);
//     Route::get('risk', [ContractAnalyticsController::class, 'riskAnalytics']);
//     Route::get('lifecycle', [ContractAnalyticsController::class, 'lifecycleAnalytics']);
// });

// Webhook routes for external integrations (digital signatures, etc.)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('docusign', [ContractController::class, 'docusignWebhook'])->name('docusign');
    Route::post('hellosign', [ContractController::class, 'hellosignWebhook'])->name('hellosign');
    Route::post('adobe-sign', [ContractController::class, 'adobeSignWebhook'])->name('adobe-sign');
    
    // Stripe webhooks
    Route::post('stripe', [\App\Http\Controllers\Api\Webhooks\StripeWebhookController::class, 'handle'])->name('stripe');
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
        
        // Invoices (contract-specific)
        Route::get('contracts/{contract}/invoices', [\App\Domains\Client\Controllers\ClientPortalController::class, 'contractInvoices'])->name('contract.invoices');
        Route::get('contracts/{contract}/invoices/{invoice}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'viewInvoice'])->name('contract.invoices.show');
        Route::get('contracts/{contract}/invoices/{invoice}/download', [\App\Domains\Client\Controllers\ClientPortalController::class, 'downloadInvoice'])->name('contract.invoices.download');
        
        // General invoices (all invoices for client)
        Route::get('invoices', [\App\Domains\Client\Controllers\ClientPortalController::class, 'invoices'])->name('invoices');
        Route::get('invoices/{invoice}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'showInvoice'])->name('invoices.show');
        Route::get('invoices/{invoice}/download', [\App\Domains\Client\Controllers\ClientPortalController::class, 'downloadClientInvoice'])->name('invoices.download');
        
        // Quotes
        Route::get('quotes', [\App\Domains\Client\Controllers\ClientPortalController::class, 'quotes'])->name('quotes');
        Route::get('quotes/{quote}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'showQuote'])->name('quotes.show');
        Route::get('quotes/{quote}/pdf', [\App\Domains\Client\Controllers\ClientPortalController::class, 'downloadQuotePdf'])->name('quotes.pdf');
        
        // Tickets
        Route::get('tickets', [\App\Domains\Client\Controllers\ClientPortalController::class, 'tickets'])->name('tickets');
        Route::get('tickets/create', [\App\Domains\Client\Controllers\ClientPortalController::class, 'createTicket'])->name('tickets.create');
        Route::post('tickets', [\App\Domains\Client\Controllers\ClientPortalController::class, 'storeTicket'])->name('tickets.store');
        Route::get('tickets/{ticket}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'showTicket'])->name('tickets.show');
        Route::post('tickets/{ticket}/comment', [\App\Domains\Client\Controllers\ClientPortalController::class, 'addTicketComment'])->name('tickets.comment');
        
        // Assets
        Route::get('assets', [\App\Domains\Client\Controllers\ClientPortalController::class, 'assets'])->name('assets');
        Route::get('assets/{asset}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'showAsset'])->name('assets.show');
        
        // Projects
        Route::get('projects', [\App\Domains\Client\Controllers\ClientPortalController::class, 'projects'])->name('projects');
        Route::get('projects/{project}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'showProject'])->name('projects.show');
        
        // Profile
        Route::get('profile', [\App\Domains\Client\Controllers\ClientPortalController::class, 'profile'])->name('profile');
        Route::put('profile', [\App\Domains\Client\Controllers\ClientPortalController::class, 'updateProfile'])->name('profile.update');
        
        // Notifications
        Route::post('notifications/{notification}/read', [\App\Domains\Client\Controllers\ClientPortalController::class, 'markNotificationAsRead'])->name('notifications.read');
    });
});
