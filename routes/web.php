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
        Route::get('/export', [\App\Http\Controllers\UserController::class, 'export'])->name('export.csv');
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

        // Company Email Provider Settings
        Route::get('/company-email-provider', [\App\Http\Controllers\Settings\CompanyEmailProviderController::class, 'show'])->name('company-email-provider');
        Route::put('/company-email-provider', [\App\Http\Controllers\Settings\CompanyEmailProviderController::class, 'update'])->name('company-email-provider.update');
        Route::post('/company-email-provider/test-connection', [\App\Http\Controllers\Settings\CompanyEmailProviderController::class, 'testConnection'])->name('company-email-provider.test-connection');
        
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
            Route::post('/apply-template', [\App\Http\Controllers\RoleController::class, 'applyTemplate'])->name('apply-template');
            Route::post('/{role}/duplicate', [\App\Http\Controllers\RoleController::class, 'duplicate'])->name('duplicate');
            Route::get('/{role}', [\App\Http\Controllers\RoleController::class, 'show'])->name('show');
            Route::get('/{role}/edit', [\App\Http\Controllers\RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [\App\Http\Controllers\RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('destroy');
        });
        
        // Permissions Management
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\PermissionController::class, 'index'])->name('index');
            Route::get('/matrix', [\App\Http\Controllers\PermissionController::class, 'matrix'])->name('matrix');
            Route::post('/matrix', [\App\Http\Controllers\PermissionController::class, 'updateMatrix'])->name('matrix.update');
            Route::get('/user/{user}', [\App\Http\Controllers\PermissionController::class, 'userPermissions'])->name('user');
            Route::put('/user/{user}', [\App\Http\Controllers\PermissionController::class, 'updateUserPermissions'])->name('user.update');
            Route::post('/bulk-assign', [\App\Http\Controllers\PermissionController::class, 'bulkAssign'])->name('bulk-assign');
            Route::get('/export', [\App\Http\Controllers\PermissionController::class, 'export'])->name('export');
            Route::post('/import', [\App\Http\Controllers\PermissionController::class, 'import'])->name('import');
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
