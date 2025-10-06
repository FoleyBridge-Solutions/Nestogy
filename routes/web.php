<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', [\App\Domains\Core\Controllers\WelcomeController::class, 'index']);

// Setup Wizard Routes (when no companies exist)
Route::prefix('setup')->name('setup.wizard.')->group(function () {
    Route::get('/', [\App\Domains\Core\Controllers\SetupWizardController::class, 'index'])->name('index');
    Route::get('/company', \App\Livewire\Setup\SetupWizard::class)->name('company-form');
    // Legacy routes for backward compatibility
    Route::post('/company', [\App\Domains\Core\Controllers\SetupWizardController::class, 'processSetup'])->name('process');
    Route::post('/test-smtp', [\App\Domains\Core\Controllers\SetupWizardController::class, 'testSmtp'])->name('test-smtp');
});

// Redirect /register to our SaaS signup form
Route::get('/register', function () {
    return redirect()->route('signup.form');
});

// Company Registration Routes (pre-login)
Route::prefix('signup')->name('signup.')->group(function () {
    Route::get('/', [\App\Domains\Client\Controllers\CompanyRegistrationController::class, 'showRegistrationForm'])->name('form');
    Route::post('/', [\App\Domains\Client\Controllers\CompanyRegistrationController::class, 'register'])->name('submit');
    Route::get('plans', [\App\Domains\Client\Controllers\CompanyRegistrationController::class, 'getPlans'])->name('plans');
    Route::post('validate-step', [\App\Domains\Client\Controllers\CompanyRegistrationController::class, 'validateStep'])->name('validate-step');
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
Route::post('/auth/check-suspicious-login', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'checkSuspiciousLoginApproval'])->name('auth.check-suspicious-login');

// Custom secure authentication routes (override Fortify)
Route::middleware('guest')->group(function () {
    // Override Fortify's login routes with our secure implementation
    Route::get('/login', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'login']);

    // Company selection routes for multi-company users
    Route::get('/auth/select-company', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'showCompanySelection'])->name('auth.company-select');
    Route::post('/auth/select-company', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'selectCompany']);
});

// Logout route (authenticated users only)
Route::post('/logout', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// New Livewire Dashboard
Route::get('/dashboard', \App\Livewire\Dashboard\MainDashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Debug route to test financial access
Route::get('/test-financial', function () {
    if (! auth()->check()) {
        return 'Not authenticated';
    }

    $client = \App\Domains\Core\Services\NavigationService::getSelectedClient();
    $invoicesUrl = '/financial/invoices'; // Direct URL instead of route helper

    return 'Authenticated as: '.auth()->user()->email.
           '<br>Selected client: '.($client ? $client->name : 'None').
           '<br><a href="'.$invoicesUrl.'">Go to Invoices (Direct URL)</a>'.
           '<br>Direct URL: '.$invoicesUrl;
})->middleware(['auth']);

Route::get('/dashboard-enhanced', function () {
    return view('dashboard-enhanced');
})->middleware(['auth', 'verified'])->name('dashboard.enhanced');

// Dashboard API endpoints
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/api/dashboard/stats', [\App\Domains\Core\Controllers\DashboardController::class, 'getData'])->name('dashboard.stats');
    Route::get('/api/dashboard/realtime', [\App\Domains\Core\Controllers\DashboardController::class, 'getRealtimeData'])->name('dashboard.realtime');
    Route::get('/api/dashboard/export', [\App\Domains\Core\Controllers\DashboardController::class, 'exportData'])->name('dashboard.export');
    Route::get('/api/dashboard/notifications', [\App\Domains\Core\Controllers\DashboardController::class, 'getNotifications'])->name('dashboard.notifications');
    Route::post('/api/dashboard/notifications/{id}/read', [\App\Domains\Core\Controllers\DashboardController::class, 'markNotificationRead'])->name('dashboard.notifications.read');

    // Widget endpoints
    Route::get('/api/dashboard/widget', [\App\Domains\Core\Controllers\DashboardController::class, 'getWidgetData'])->name('dashboard.widget');
    Route::post('/api/dashboard/widgets/multiple', [\App\Domains\Core\Controllers\DashboardController::class, 'getMultipleWidgetData'])->name('dashboard.widgets.multiple');

    // Configuration endpoints
    Route::post('/api/dashboard/config/save', [\App\Domains\Core\Controllers\DashboardController::class, 'saveDashboardConfig'])->name('dashboard.config.save');
    Route::get('/api/dashboard/config/load', [\App\Domains\Core\Controllers\DashboardController::class, 'loadDashboardConfig'])->name('dashboard.config.load');

    // Preset endpoints
    Route::get('/api/dashboard/presets', [\App\Domains\Core\Controllers\DashboardController::class, 'getPresets'])->name('dashboard.presets');
    Route::post('/api/dashboard/preset/apply', [\App\Domains\Core\Controllers\DashboardController::class, 'applyPreset'])->name('dashboard.preset.apply');
});

// Company switching route
Route::post('/switch-company', [\App\Http\Middleware\SubsidiaryAccessMiddleware::class, 'handleCompanySwitch'])
    ->middleware(['auth', 'verified'])
    ->name('company.switch');

// Subsidiary Management Routes
Route::middleware(['auth', 'verified', 'subsidiary.access'])->group(function () {
    Route::prefix('subsidiaries')->name('subsidiaries.')->group(function () {
        // Main subsidiary management
        Route::get('/', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'store'])->name('store');
        Route::get('/{subsidiary}', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'show'])->name('show');
        Route::get('/{subsidiary}/edit', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'edit'])->name('edit');
        Route::put('/{subsidiary}', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'update'])->name('update');
        Route::delete('/{subsidiary}', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'destroy'])->name('destroy');

        // Hierarchy visualization
        Route::get('/hierarchy/tree', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'hierarchyTree'])->name('hierarchy.tree');

        // Permission management
        Route::get('/{subsidiary}/permissions', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'permissions'])->name('permissions');
        Route::post('/permissions/grant', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'grantPermission'])->name('grant-permission');
        Route::delete('/permissions/{permission}/revoke', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'revokePermission'])->name('revoke-permission');

        // User management
        Route::get('/{subsidiary}/users', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'users'])->name('users');
        Route::post('/users/grant-access', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'grantUserAccess'])->name('grant-user-access');
        Route::delete('/users/{crossCompanyUser}/revoke', [\App\Domains\Client\Controllers\SubsidiaryManagementController::class, 'revokeUserAccess'])->name('revoke-user-access');
    });
});

// Core application routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Client Management Routes - Defined in app/Domains/Client/routes.php
    // (Duplicate routes removed to avoid conflicts)

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

    // Navigation API routes
    Route::prefix('api/navigation')->name('api.navigation.')->group(function () {
        Route::get('tree', [\App\Domains\Core\Controllers\NavigationController::class, 'getNavigationTree'])->name('tree');
        Route::get('badges', [\App\Domains\Core\Controllers\NavigationController::class, 'getBadgeCounts'])->name('badges');
        Route::get('suggestions', [\App\Domains\Core\Controllers\NavigationController::class, 'getSuggestions'])->name('suggestions');
        Route::get('recent', [\App\Domains\Core\Controllers\NavigationController::class, 'getRecentItems'])->name('recent');
        Route::get('workflow-highlights', [\App\Domains\Core\Controllers\NavigationController::class, 'getWorkflowHighlights'])->name('workflow-highlights');
        Route::post('command', [\App\Domains\Core\Controllers\NavigationController::class, 'executeCommand'])->name('command');
        Route::post('workflow', [\App\Domains\Core\Controllers\NavigationController::class, 'setWorkflow'])->name('workflow');
    });

    // Search API routes
    Route::prefix('api/search')->name('api.search.')->group(function () {
        Route::get('query', [\App\Domains\Core\Controllers\NavigationController::class, 'search'])->name('query');
        Route::post('command-palette', [\App\Domains\Core\Controllers\SearchController::class, 'commandPalette'])->name('command-palette');
    });

    // User routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/profile', [\App\Domains\Security\Controllers\UserController::class, 'profile'])->name('profile');
        Route::put('/profile', [\App\Domains\Security\Controllers\UserController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [\App\Domains\Security\Controllers\UserController::class, 'updateOwnPassword'])->name('password.update');
        Route::put('/settings', [\App\Domains\Security\Controllers\UserController::class, 'updateSettings'])->name('settings.update');
        Route::put('/preferences', [\App\Domains\Security\Controllers\UserController::class, 'updatePreferences'])->name('preferences.update');
        Route::delete('/account', [\App\Domains\Security\Controllers\UserController::class, 'destroyAccount'])->name('account.destroy');
        Route::get('/', [\App\Domains\Security\Controllers\UserController::class, 'index'])->name('index');
        Route::get('/export', [\App\Domains\Security\Controllers\UserController::class, 'export'])->name('export.csv');
        Route::get('/create', [\App\Domains\Security\Controllers\UserController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Security\Controllers\UserController::class, 'store'])->name('store')->middleware('subscription.limits');
        Route::get('/{user}', [\App\Domains\Security\Controllers\UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [\App\Domains\Security\Controllers\UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [\App\Domains\Security\Controllers\UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [\App\Domains\Security\Controllers\UserController::class, 'destroy'])->name('destroy');
    });

    // Mail Queue Management
    Route::prefix('mail-queue')->name('mail-queue.')->middleware(['auth'])->group(function () {
        Route::get('/', [\App\Domains\Email\Controllers\MailQueueController::class, 'index'])->name('index');
        Route::get('/{mailQueue}', [\App\Domains\Email\Controllers\MailQueueController::class, 'show'])->name('show');
        Route::post('/{mailQueue}/retry', [\App\Domains\Email\Controllers\MailQueueController::class, 'retry'])->name('retry');
        Route::delete('/{mailQueue}/cancel', [\App\Domains\Email\Controllers\MailQueueController::class, 'cancel'])->name('cancel');
        Route::post('/process', [\App\Domains\Email\Controllers\MailQueueController::class, 'process'])->name('process');
        Route::get('/export/csv', [\App\Domains\Email\Controllers\MailQueueController::class, 'export'])->name('export');
    });

    // Client Management Routes
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [\App\Domains\Client\Controllers\ClientController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Client\Controllers\ClientController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Client\Controllers\ClientController::class, 'store'])->name('store');
        Route::get('/{client}', [\App\Domains\Client\Controllers\ClientController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [\App\Domains\Client\Controllers\ClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [\App\Domains\Client\Controllers\ClientController::class, 'update'])->name('update');
        Route::delete('/{client}', [\App\Domains\Client\Controllers\ClientController::class, 'destroy'])->name('destroy');
        
        // Client Actions
        Route::post('/validate-batch', [\App\Domains\Client\Controllers\ClientController::class, 'validateBatch'])->name('validate-batch');
        Route::post('/{client}/convert-lead', [\App\Domains\Client\Controllers\ClientController::class, 'convertLead'])->name('convert-lead');
        Route::get('/export/csv', [\App\Domains\Client\Controllers\ClientController::class, 'exportCsv'])->name('export.csv');
        Route::get('/import-template', [\App\Domains\Client\Controllers\ClientController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/download-template', [\App\Domains\Client\Controllers\ClientController::class, 'downloadTemplate'])->name('download-template');
        Route::get('/leads/import', [\App\Domains\Client\Controllers\ClientController::class, 'leadsImportForm'])->name('leads.import');
        Route::post('/leads/import', [\App\Domains\Client\Controllers\ClientController::class, 'leadsImport'])->name('leads.import.process');
        Route::get('/leads/import-template', [\App\Domains\Client\Controllers\ClientController::class, 'leadsImportTemplate'])->name('leads.import.template');
        Route::post('/{client}/select', [\App\Domains\Client\Controllers\ClientController::class, 'selectClient'])->name('select');
        Route::get('/clear-selection', [\App\Domains\Client\Controllers\ClientController::class, 'clearSelection'])->name('clear-selection');
        Route::post('/{client}/update-notes', [\App\Domains\Client\Controllers\ClientController::class, 'updateNotes'])->name('update-notes');
    });

    // Ticket Management Routes
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [\App\Domains\Ticket\Controllers\TicketController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Ticket\Controllers\TicketController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Ticket\Controllers\TicketController::class, 'store'])->name('store');
        Route::get('/export', [\App\Domains\Ticket\Controllers\TicketController::class, 'export'])->name('export');
        Route::get('/search', [\App\Domains\Ticket\Controllers\TicketController::class, 'search'])->name('search');
        Route::get('/{ticket}', [\App\Domains\Ticket\Controllers\TicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [\App\Domains\Ticket\Controllers\TicketController::class, 'edit'])->name('edit');
        Route::put('/{ticket}', [\App\Domains\Ticket\Controllers\TicketController::class, 'update'])->name('update');
        Route::delete('/{ticket}', [\App\Domains\Ticket\Controllers\TicketController::class, 'destroy'])->name('destroy');
        
        // Ticket Actions
        Route::post('/{ticket}/assign', [\App\Domains\Ticket\Controllers\TicketController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/update-status', [\App\Domains\Ticket\Controllers\TicketController::class, 'updateStatus'])->name('update-status');
        Route::post('/{ticket}/update-priority', [\App\Domains\Ticket\Controllers\TicketController::class, 'updatePriority'])->name('update-priority');
        Route::post('/{ticket}/schedule', [\App\Domains\Ticket\Controllers\TicketController::class, 'schedule'])->name('schedule');
        Route::post('/{ticket}/merge', [\App\Domains\Ticket\Controllers\TicketController::class, 'merge'])->name('merge');
        Route::post('/{ticket}/resolve', [\App\Domains\Ticket\Controllers\TicketController::class, 'resolve'])->name('resolve');
        Route::post('/{ticket}/reopen', [\App\Domains\Ticket\Controllers\TicketController::class, 'reopen'])->name('reopen');
        Route::post('/{ticket}/reply', [\App\Domains\Ticket\Controllers\TicketController::class, 'storeReply'])->name('reply');
        Route::get('/{ticket}/viewers', [\App\Domains\Ticket\Controllers\TicketController::class, 'getViewers'])->name('viewers');
        Route::get('/{ticket}/pdf', [\App\Domains\Ticket\Controllers\TicketController::class, 'generatePdf'])->name('pdf');
        
        // Time Tracking
        Route::post('/{ticket}/start-timer', [\App\Domains\Ticket\Controllers\TicketController::class, 'startSmartTimer'])->name('start-timer');
        Route::post('/{ticket}/stop-timer', [\App\Domains\Ticket\Controllers\TicketController::class, 'stopTimer'])->name('stop-timer');
        Route::post('/{ticket}/pause-timer', [\App\Domains\Ticket\Controllers\TicketController::class, 'pauseTimer'])->name('pause-timer');
    });

    // Include unified settings routes
    require __DIR__.'/settings.php';
    // Main settings page
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
    Route::get('/collections/dashboard', [\App\Domains\Financial\Controllers\CollectionDashboardController::class, 'index'])->name('collections.dashboard');

    // Customer Billing Portal
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [\App\Domains\Financial\Controllers\BillingController::class, 'index'])->name('index');
        Route::get('/subscription', [\App\Domains\Financial\Controllers\BillingController::class, 'subscription'])->name('subscription');
        Route::get('/payment-methods', [\App\Domains\Financial\Controllers\BillingController::class, 'paymentMethods'])->name('payment-methods');
        Route::get('/change-plan', [\App\Domains\Financial\Controllers\BillingController::class, 'changePlan'])->name('change-plan');
        Route::patch('/update-plan', [\App\Domains\Financial\Controllers\BillingController::class, 'updatePlan'])->name('update-plan');
        Route::get('/invoices', [\App\Domains\Financial\Controllers\BillingController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}/download', [\App\Domains\Financial\Controllers\BillingController::class, 'downloadInvoice'])->name('invoices.download');
        Route::get('/usage', [\App\Domains\Financial\Controllers\BillingController::class, 'usage'])->name('usage');
        Route::post('/cancel-subscription', [\App\Domains\Financial\Controllers\BillingController::class, 'cancelSubscription'])->name('cancel-subscription');
        Route::post('/reactivate-subscription', [\App\Domains\Financial\Controllers\BillingController::class, 'reactivateSubscription'])->name('reactivate-subscription');
        Route::get('/portal', [\App\Domains\Financial\Controllers\BillingController::class, 'billingPortal'])->name('portal');
        Route::get('/time-entries', \App\Livewire\Billing\TimeEntryApproval::class)->name('time-entries');
    });
});

// Authentication routes will be handled by Laravel Fortify

// Email tracking routes (public, no auth required)
Route::get('marketing/email/track-open/{tracking_id}', function ($trackingId) {
    app(\App\Domains\Marketing\Services\CampaignEmailService::class)->trackEmailOpen(
        $trackingId,
        request()->header('User-Agent'),
        request()->ip()
    );

    // Return 1x1 transparent pixel
    return response()->make(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200)
        ->header('Content-Type', 'image/gif');
})->name('marketing.email.track-open');

Route::get('marketing/email/track-click/{tracking_id}', function ($trackingId) {
    $url = urldecode(request('url'));

    app(\App\Domains\Marketing\Services\CampaignEmailService::class)->trackEmailClick(
        $trackingId,
        $url,
        request()->header('User-Agent'),
        request()->ip()
    );

    return redirect($url);
})->name('marketing.email.track-click');

Route::get('marketing/unsubscribe', function () {
    $enrollmentId = request('enrollment');
    $token = request('token');

    // Verify token
    $expectedToken = hash('sha256', $enrollmentId.request('campaign').config('app.key'));
    if (! hash_equals($expectedToken, $token)) {
        abort(403);
    }

    $enrollment = \App\Domains\Marketing\Models\CampaignEnrollment::find($enrollmentId);
    if ($enrollment) {
        $enrollment->unsubscribe();
    }

    return view('marketing.unsubscribed');
})->name('marketing.unsubscribe');

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
    Route::post('/calculate-tax', [\App\Domains\Financial\Controllers\Api\ServiceTaxController::class, 'calculateTax'])->name('calculate-tax');
    Route::get('/customer/{customer}/address', [\App\Domains\Financial\Controllers\Api\ServiceTaxController::class, 'getCustomerAddress'])->name('customer-address');
});

// Comprehensive Tax Engine API routes
Route::middleware(['auth', 'verified'])->prefix('api/tax-engine')->name('tax-engine.')->group(function () {
    // Basic calculation endpoints
    Route::post('/calculate', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'calculateTax'])->name('calculate');
    Route::post('/calculate-line', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'calculateLineItemTax'])->name('calculate-line');

    // Enhanced bulk calculation endpoints
    Route::post('/calculate-bulk', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'calculateBulkTax'])->name('calculate-bulk');
    Route::post('/preview-quote', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'previewQuoteTax'])->name('preview-quote');
    Route::post('/preview-invoice', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'previewQuoteTax'])->name('preview-invoice'); // Same as quote for now

    // Profile and configuration endpoints
    Route::get('/profile', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'getTaxProfile'])->name('profile');
    Route::get('/required-fields', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'getRequiredFields'])->name('required-fields');
    Route::post('/validate', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'validateTaxData'])->name('validate');
    Route::get('/customer/{customer}/address', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'getCustomerAddress'])->name('customer-address');
    Route::get('/profiles', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'getAvailableProfiles'])->name('profiles');
    Route::get('/tax-types', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'getApplicableTaxTypes'])->name('tax-types');

    // Performance and cache management endpoints
    Route::post('/cache/clear', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'clearCaches'])->name('cache.clear');
    Route::post('/cache/warm', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'warmCaches'])->name('cache.warm');
    Route::get('/statistics', [\App\Domains\Financial\Controllers\Api\TaxEngineController::class, 'getStatistics'])->name('statistics');
});

// Tax Administration Routes (Admin only)
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin/tax')->name('admin.tax.')->group(function () {
    Route::get('/', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'index'])->name('index');
    Route::get('/profiles', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'profiles'])->name('profiles');
    Route::get('/rates', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'rates'])->name('rates');
    Route::get('/jurisdictions', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'jurisdictions'])->name('jurisdictions');
    Route::get('/performance', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'performance'])->name('performance');

    // Management actions
    Route::post('/bulk-operations', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'bulkOperations'])->name('bulk-operations');
    Route::post('/clear-caches', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'clearCaches'])->name('clear-caches');
    Route::post('/warm-caches', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'warmCaches'])->name('warm-caches');

    // Export and testing
    Route::get('/export-config', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'exportConfig'])->name('export-config');
    Route::post('/test-calculation', [\App\Domains\Financial\Controllers\TaxAdminController::class, 'testCalculation'])->name('test-calculation');
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
    Route::post('docusign', [\App\Domains\Contract\Controllers\ContractController::class, 'docusignWebhook'])->name('docusign');
    Route::post('hellosign', [\App\Domains\Contract\Controllers\ContractController::class, 'hellosignWebhook'])->name('hellosign');
    Route::post('adobe-sign', [\App\Domains\Contract\Controllers\ContractController::class, 'adobeSignWebhook'])->name('adobe-sign');

    // Stripe webhooks
    Route::post('stripe', [\App\Domains\Financial\Http\Controllers\Webhooks\StripeWebhookController::class, 'handle'])->name('stripe');
});

// Email Tracking Routes (public)
Route::prefix('email')->name('email.')->group(function () {
    Route::get('/track/open/{token}', [\App\Domains\Email\Controllers\EmailTrackingController::class, 'trackOpen'])->name('track.open');
    Route::get('/track/click/{token}', [\App\Domains\Email\Controllers\EmailTrackingController::class, 'trackClick'])->name('track.click');
    Route::get('/view/{uuid}', [\App\Domains\Email\Controllers\EmailTrackingController::class, 'viewEmail'])->name('view');
    Route::get('/unsubscribe/{token}', [\App\Domains\Email\Controllers\EmailTrackingController::class, 'unsubscribe'])->name('unsubscribe');
});

// Public routes for client portal
Route::prefix('client-portal')->name('client.')->group(function () {
    // Guest routes (login, etc.)
    Route::get('login', [\App\Domains\Client\Controllers\ClientPortalController::class, 'showLogin'])->name('login');
    Route::post('login', [\App\Domains\Client\Controllers\ClientPortalController::class, 'login'])->name('login.submit');

    // Invitation routes
    Route::prefix('invitation')->name('invitation.')->group(function () {
        Route::get('{token}', [\App\Domains\Client\Controllers\Portal\PortalInvitationController::class, 'show'])->name('show');
        Route::post('{token}/accept', [\App\Domains\Client\Controllers\Portal\PortalInvitationController::class, 'accept'])->name('accept');
        Route::get('expired', [\App\Domains\Client\Controllers\Portal\PortalInvitationController::class, 'expired'])->name('expired');
    });

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
        Route::get('tickets/{ticket}/survey', \App\Livewire\Portal\TicketSatisfactionSurvey::class)->name('tickets.survey');

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

// Physical Mail Settings Route
Route::middleware(['auth', 'verified'])->get('/settings/physical-mail', function () {
    return view('settings.physical-mail');
})->name('settings.physical-mail');
Route::get('/notifications', function () { 
    return redirect()->route('settings.notifications'); 
})->name('notifications.index')->middleware(['auth', 'verified']);
Route::get('/mobile/time-tracker/{ticketId?}', \App\Livewire\MobileTimeTracker::class)->name('mobile.time-tracker')->middleware(['auth', 'verified']);
