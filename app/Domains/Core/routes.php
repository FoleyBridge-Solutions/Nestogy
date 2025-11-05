<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core Application Routes
|--------------------------------------------------------------------------
|
| Core routes including:
| - Homepage and setup wizard
| - Authentication (login, logout, company selection)
| - Security (suspicious login handling, security dashboard)
| - Dashboard and navigation API
| - User management
| - Admin/subscription management
| - Webhooks
| - Test/debug routes
|
*/

// Homepage
Route::middleware('web')->get('/', [\App\Domains\Core\Controllers\WelcomeController::class, 'index']);

// Setup Wizard Routes (when no companies exist)
Route::middleware('web')->prefix('setup')->name('setup.wizard.')->group(function () {
    Route::get('/', [\App\Domains\Core\Controllers\SetupWizardController::class, 'index'])->name('index');
    Route::get('/company', \App\Livewire\Setup\SetupWizard::class)->name('company-form');
    Route::post('/company', [\App\Domains\Core\Controllers\SetupWizardController::class, 'processSetup'])->name('process');
    Route::post('/test-smtp', [\App\Domains\Core\Controllers\SetupWizardController::class, 'testSmtp'])->name('test-smtp');
});

// Redirect /register to our SaaS signup form
Route::middleware('web')->get('/register', function () {
    return redirect()->route('signup.form');
});

// Company Registration Routes (pre-login)
Route::middleware('web')->prefix('signup')->name('signup.')->group(function () {
    Route::get('/', [\App\Domains\Client\Controllers\CompanyRegistrationController::class, 'showRegistrationForm'])->name('form');
    Route::post('/', [\App\Domains\Client\Controllers\CompanyRegistrationController::class, 'register'])->name('submit');
    Route::get('plans', [\App\Domains\Client\Controllers\CompanyRegistrationController::class, 'getPlans'])->name('plans');
    Route::post('validate-step', [\App\Domains\Client\Controllers\CompanyRegistrationController::class, 'validateStep'])->name('validate-step');
});

// Security verification routes (suspicious login handling)
Route::middleware('web')->prefix('security')->name('security.')->group(function () {
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
Route::middleware('web')->post('/auth/check-suspicious-login', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'checkSuspiciousLoginApproval'])->name('auth.check-suspicious-login');

// Custom secure authentication routes (override Fortify)
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/login', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'login']);
    Route::get('/auth/select-company', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'showCompanySelection'])->name('auth.company-select');
    Route::post('/auth/select-company', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'selectCompany']);
});

// Logout route (authenticated users only)
Route::post('/logout', [\App\Domains\Security\Controllers\Auth\LoginController::class, 'logout'])
    ->middleware(['web', 'auth'])
    ->name('logout');

// Email Verification Routes (required by Fortify when email verification feature is enabled)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');
    
    Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard')->with('success', 'Email verified successfully!');
    })->middleware(['signed'])->name('verification.verify');
    
    Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', 'Verification link sent!');
    })->middleware(['throttle:6,1'])->name('verification.send');
});

// Dashboard
Route::get('/dashboard', \App\Livewire\Dashboard\MainDashboard::class)
    ->middleware(['web', 'auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard-enhanced', function () {
    return view('dashboard-enhanced');
})->middleware(['web', 'auth', 'verified'])->name('dashboard.enhanced');

// Dashboard API endpoints
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/api/dashboard/stats', [\App\Domains\Core\Controllers\DashboardController::class, 'getData'])->name('api.dashboard.stats');
    Route::get('/api/dashboard/realtime', [\App\Domains\Core\Controllers\DashboardController::class, 'getRealtimeData'])->name('api.dashboard.realtime');
    Route::get('/api/dashboard/export', [\App\Domains\Core\Controllers\DashboardController::class, 'exportData'])->name('api.dashboard.export');
    Route::get('/api/dashboard/notifications', [\App\Domains\Core\Controllers\DashboardController::class, 'getNotifications'])->name('api.dashboard.notifications');
    Route::post('/api/dashboard/notifications/{id}/read', [\App\Domains\Core\Controllers\DashboardController::class, 'markNotificationRead'])->name('api.dashboard.notifications.read');
    Route::get('/api/dashboard/widget', [\App\Domains\Core\Controllers\DashboardController::class, 'getWidgetData'])->name('api.dashboard.widget');
    Route::post('/api/dashboard/widgets/multiple', [\App\Domains\Core\Controllers\DashboardController::class, 'getMultipleWidgetData'])->name('api.dashboard.widgets.multiple');
    Route::post('/api/dashboard/config/save', [\App\Domains\Core\Controllers\DashboardController::class, 'saveDashboardConfig'])->name('api.dashboard.config.save');
    Route::get('/api/dashboard/config/load', [\App\Domains\Core\Controllers\DashboardController::class, 'loadDashboardConfig'])->name('api.dashboard.config.load');
    Route::get('/api/dashboard/presets', [\App\Domains\Core\Controllers\DashboardController::class, 'getPresets'])->name('api.dashboard.presets');
    Route::post('/api/dashboard/preset/apply', [\App\Domains\Core\Controllers\DashboardController::class, 'applyPreset'])->name('api.dashboard.preset.apply');
});

// Company switching route
Route::post('/switch-company', [\App\Http\Middleware\SubsidiaryAccessMiddleware::class, 'handleCompanySwitch'])
    ->middleware(['web', 'auth', 'verified'])
    ->name('company.switch');

// Navigation API routes
Route::middleware(['web', 'auth', 'verified'])->group(function () {
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
        Route::get('/export', [\App\Domains\Security\Controllers\UserController::class, 'export'])->name('export.csv');
        Route::get('/profile', [\App\Domains\Security\Controllers\UserController::class, 'profile'])->name('profile');
        Route::put('/profile', [\App\Domains\Security\Controllers\UserController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [\App\Domains\Security\Controllers\UserController::class, 'updateOwnPassword'])->name('password.update');
        Route::put('/settings', [\App\Domains\Security\Controllers\UserController::class, 'updateSettings'])->name('settings.update');
        Route::put('/preferences', [\App\Domains\Security\Controllers\UserController::class, 'updatePreferences'])->name('preferences.update');
        Route::delete('/account', [\App\Domains\Security\Controllers\UserController::class, 'destroyAccount'])->name('account.destroy');
        Route::get('/', [\App\Domains\Security\Controllers\UserController::class, 'index'])->name('index');
        Route::get('/create', [\App\Domains\Security\Controllers\UserController::class, 'create'])->name('create');
        Route::post('/', [\App\Domains\Security\Controllers\UserController::class, 'store'])->name('store')->middleware('subscription.limits');
        Route::get('/{user}', [\App\Domains\Security\Controllers\UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [\App\Domains\Security\Controllers\UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [\App\Domains\Security\Controllers\UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [\App\Domains\Security\Controllers\UserController::class, 'destroy'])->name('destroy');
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

// Webhook routes for external integrations (digital signatures, etc.)
Route::middleware('web')->prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('docusign', [\App\Domains\Contract\Controllers\ContractController::class, 'docusignWebhook'])->name('docusign');
    Route::post('hellosign', [\App\Domains\Contract\Controllers\ContractController::class, 'hellosignWebhook'])->name('hellosign');
    Route::post('adobe-sign', [\App\Domains\Contract\Controllers\ContractController::class, 'adobeSignWebhook'])->name('adobe-sign');
    Route::post('stripe', [\App\Domains\Financial\Http\Controllers\Webhooks\StripeWebhookController::class, 'handle'])->name('stripe');
});

// Utility routes
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/settings/physical-mail', function () {
        return view('settings.physical-mail');
    })->name('settings.physical-mail');
    
    Route::get('/notifications', function () { 
        return redirect()->route('settings.notifications'); 
    })->name('notifications.index');
    
    Route::get('/mobile/time-tracker/{ticketId?}', \App\Livewire\MobileTimeTracker::class)->name('mobile.time-tracker');
});

// Test/Debug routes
Route::middleware('web')->group(function () {
    Route::get('/test-chart', function() { return view('test-chart'); });
    Route::get('/test-marketing-chart', function() { return view('test-marketing-chart'); });
    Route::get('/test-bar-chart', function() { return view('test-bar-chart'); });
    
    Route::get('/test-financial', function () {
        if (! auth()->check()) {
            return 'Not authenticated';
        }
        $client = \App\Domains\Core\Services\NavigationService::getSelectedClient();
        $invoicesUrl = '/financial/invoices';
        return 'Authenticated as: '.auth()->user()->email.
               '<br>Selected client: '.($client ? $client->name : 'None').
               '<br><a href="'.$invoicesUrl.'">Go to Invoices (Direct URL)</a>'.
               '<br>Direct URL: '.$invoicesUrl;
    })->middleware('auth');
});
