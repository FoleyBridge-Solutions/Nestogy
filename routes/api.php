<?php

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
    Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login');
    Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('register');
    Route::post('forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('forgot-password');
    
    // Authenticated Auth Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
        Route::post('refresh', [App\Http\Controllers\Auth\LoginController::class, 'refresh'])->name('refresh');
        Route::get('me', function (Request $request) { return $request->user(); })->name('me');
        Route::post('change-password', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('change-password');
    });
});

// Protected API Routes (Authenticated + Company Scoped)
Route::middleware(['auth:sanctum', 'company', 'throttle:120,1'])->group(function () {
    
    // Dashboard API
    Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
        Route::get('stats', [App\Http\Controllers\DashboardController::class, 'getData'])->name('stats');
        Route::get('notifications', [App\Http\Controllers\DashboardController::class, 'getNotifications'])->name('notifications');
        Route::patch('notifications/{id}/read', [App\Http\Controllers\DashboardController::class, 'markNotificationRead'])->name('notifications.read');
        Route::get('recent-activity', [App\Http\Controllers\DashboardController::class, 'getData'])->name('recent-activity');
    });
    
    // Client Management API
    Route::prefix('clients')->name('api.clients.')->group(function () {
        // Standard CRUD
        Route::get('/', [App\Http\Controllers\ClientController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\ClientController::class, 'store'])->name('store');
        Route::get('{client}', [App\Http\Controllers\ClientController::class, 'show'])->name('show');
        Route::put('{client}', [App\Http\Controllers\ClientController::class, 'update'])->name('update');
        Route::delete('{client}', [App\Http\Controllers\ClientController::class, 'destroy'])->name('destroy');
        
        // Client Actions
        Route::patch('{client}/archive', [App\Http\Controllers\ClientController::class, 'archive'])->name('archive');
        Route::patch('{client}/restore', [App\Http\Controllers\ClientController::class, 'restore'])->name('restore');
        Route::patch('{client}/notes', [App\Http\Controllers\ClientController::class, 'updateNotes'])->name('notes.update');
        
        // Client Relationships
        Route::get('{client}/contacts', [\Foleybridge\Nestogy\Domains\Client\Controllers\ContactController::class, 'index'])->name('contacts.index');
        Route::post('{client}/contacts', [\Foleybridge\Nestogy\Domains\Client\Controllers\ContactController::class, 'store'])->name('contacts.store');
        Route::put('{client}/contacts/{contact}', [\Foleybridge\Nestogy\Domains\Client\Controllers\ContactController::class, 'update'])->name('contacts.update');
        Route::delete('{client}/contacts/{contact}', [\Foleybridge\Nestogy\Domains\Client\Controllers\ContactController::class, 'destroy'])->name('contacts.destroy');
        
        Route::get('{client}/locations', [\Foleybridge\Nestogy\Domains\Client\Controllers\LocationController::class, 'index'])->name('locations.index');
        Route::post('{client}/locations', [\Foleybridge\Nestogy\Domains\Client\Controllers\LocationController::class, 'store'])->name('locations.store');
        Route::put('{client}/locations/{location}', [\Foleybridge\Nestogy\Domains\Client\Controllers\LocationController::class, 'update'])->name('locations.update');
        Route::delete('{client}/locations/{location}', [\Foleybridge\Nestogy\Domains\Client\Controllers\LocationController::class, 'destroy'])->name('locations.destroy');
        
        Route::get('{client}/tickets', [App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index');
        Route::get('{client}/invoices', [App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('{client}/assets', [App\Http\Controllers\AssetController::class, 'index'])->name('assets.index');
        
        // Quick Access
        Route::get('active', [App\Http\Controllers\ClientController::class, 'getActiveClients'])->name('active');
        Route::get('search', [App\Http\Controllers\SearchController::class, 'clients'])->name('search');
    });

    // Ticket System API
    Route::prefix('tickets')->name('api.tickets.')->group(function () {
        // Standard CRUD
        Route::get('/', [App\Http\Controllers\TicketController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\TicketController::class, 'store'])->name('store');
        Route::get('{ticket}', [App\Http\Controllers\TicketController::class, 'show'])->name('show');
        Route::put('{ticket}', [App\Http\Controllers\TicketController::class, 'update'])->name('update');
        Route::delete('{ticket}', [App\Http\Controllers\TicketController::class, 'destroy'])->name('destroy');
        
        // Ticket Actions
        Route::post('{ticket}/replies', [App\Http\Controllers\TicketController::class, 'addReply'])->name('replies.store');
        Route::patch('{ticket}/assign', [App\Http\Controllers\TicketController::class, 'assign'])->name('assign');
        Route::patch('{ticket}/status', [App\Http\Controllers\TicketController::class, 'updateStatus'])->name('status.update');
        Route::patch('{ticket}/priority', [App\Http\Controllers\TicketController::class, 'updatePriority'])->name('priority.update');
        Route::patch('{ticket}/schedule', [App\Http\Controllers\TicketController::class, 'schedule'])->name('schedule');
        Route::post('{ticket}/watchers', [App\Http\Controllers\TicketController::class, 'addWatcher'])->name('watchers.add');
        Route::post('{ticket}/merge', [App\Http\Controllers\TicketController::class, 'merge'])->name('merge');
        
        // Ticket File Management
        Route::post('{ticket}/attachments', [App\Http\Controllers\TicketController::class, 'uploadAttachment'])->name('attachments.upload');
        Route::delete('{ticket}/attachments/{attachment}', [App\Http\Controllers\TicketController::class, 'deleteAttachment'])->name('attachments.destroy');
        
        // Ticket Viewers and Activity
        Route::get('{ticket}/viewers', [App\Http\Controllers\TicketController::class, 'getViewers'])->name('viewers');
        Route::post('{ticket}/view', [App\Http\Controllers\TicketController::class, 'markAsViewed'])->name('view');
        
        // Time Tracking API
        Route::prefix('{ticket}/time')->name('time.')->group(function () {
            Route::get('/', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'index'])->name('index');
            Route::post('/', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'store'])->name('store');
            Route::put('{timeEntry}', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'update'])->name('update');
            Route::delete('{timeEntry}', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'destroy'])->name('destroy');
            Route::post('start', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'startTimer'])->name('start');
            Route::post('stop', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'stopTimer'])->name('stop');
        });
        
        // Quick Access
        Route::get('search', [App\Http\Controllers\SearchController::class, 'tickets'])->name('search');
        Route::get('my-tickets', [App\Http\Controllers\TicketController::class, 'myTickets'])->name('my-tickets');
    });

    // Asset Management API
    Route::prefix('assets')->name('api.assets.')->group(function () {
        // Standard CRUD
        Route::get('/', [App\Http\Controllers\AssetController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\AssetController::class, 'store'])->name('store');
        Route::get('{asset}', [App\Http\Controllers\AssetController::class, 'show'])->name('show');
        Route::put('{asset}', [App\Http\Controllers\AssetController::class, 'update'])->name('update');
        Route::delete('{asset}', [App\Http\Controllers\AssetController::class, 'destroy'])->name('destroy');
        
        // Asset Actions
        Route::patch('{asset}/archive', [App\Http\Controllers\AssetController::class, 'archive'])->name('archive');
        Route::patch('{asset}/notes', [App\Http\Controllers\AssetController::class, 'updateNotes'])->name('notes.update');
        
        // Bulk Operations
        Route::patch('bulk/location', [App\Http\Controllers\AssetController::class, 'bulkAssignLocation'])->name('bulk.location');
        Route::patch('bulk/contact', [App\Http\Controllers\AssetController::class, 'bulkAssignContact'])->name('bulk.contact');
        Route::patch('bulk/status', [App\Http\Controllers\AssetController::class, 'bulkUpdateStatus'])->name('bulk.status');
        
        // Asset Data
        Route::get('types', [App\Http\Controllers\AssetController::class, 'getAssetTypes'])->name('types');
        Route::get('warranties/expiring', [App\Http\Controllers\AssetController::class, 'getExpiringWarranties'])->name('warranties.expiring');
        Route::get('search', [App\Http\Controllers\SearchController::class, 'assets'])->name('search');
    });

    // Financial Management API
    Route::prefix('financial')->name('api.financial.')->group(function () {
        
        // Invoice API
        Route::prefix('invoices')->name('invoices.')->group(function () {
            // Standard CRUD
            Route::get('/', [App\Http\Controllers\InvoiceController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\InvoiceController::class, 'store'])->name('store');
            Route::get('{invoice}', [App\Http\Controllers\InvoiceController::class, 'show'])->name('show');
            Route::put('{invoice}', [App\Http\Controllers\InvoiceController::class, 'update'])->name('update');
            Route::delete('{invoice}', [App\Http\Controllers\InvoiceController::class, 'destroy'])->name('destroy');
            
            // Invoice Items
            Route::post('{invoice}/items', [App\Http\Controllers\InvoiceController::class, 'addItem'])->name('items.store');
            Route::put('{invoice}/items/{item}', [App\Http\Controllers\InvoiceController::class, 'updateItem'])->name('items.update');
            Route::delete('{invoice}/items/{item}', [App\Http\Controllers\InvoiceController::class, 'deleteItem'])->name('items.destroy');
            
            // Invoice Actions
            Route::patch('{invoice}/status', [App\Http\Controllers\InvoiceController::class, 'updateStatus'])->name('status.update');
            Route::post('{invoice}/send', [App\Http\Controllers\InvoiceController::class, 'sendEmail'])->name('send');
            Route::post('{invoice}/copy', [App\Http\Controllers\InvoiceController::class, 'copy'])->name('copy');
            Route::patch('{invoice}/notes', [App\Http\Controllers\InvoiceController::class, 'updateNotes'])->name('notes.update');
            
            // Invoice Payments
            Route::get('{invoice}/payments', [App\Http\Controllers\InvoiceController::class, 'getPayments'])->name('payments.index');
            Route::post('{invoice}/payments', [App\Http\Controllers\InvoiceController::class, 'addPayment'])->name('payments.store');
            
            // Quick Access
            Route::get('overdue', [App\Http\Controllers\InvoiceController::class, 'index'])->name('overdue');
            Route::get('draft', [App\Http\Controllers\InvoiceController::class, 'index'])->name('draft');
            Route::get('search', [App\Http\Controllers\SearchController::class, 'invoices'])->name('search');
        });
        
        // Payment API
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'index'])->name('index');
            Route::post('/', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'store'])->name('store');
            Route::get('{payment}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'show'])->name('show');
            Route::put('{payment}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'update'])->name('update');
            Route::delete('{payment}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'destroy'])->name('destroy');
            
            // Payment Actions
            Route::post('{payment}/refund', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'refund'])->name('refund');
            Route::get('recent', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'recent'])->name('recent');
        });
        
        // Expense API
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'index'])->name('index');
            Route::post('/', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'store'])->name('store');
            Route::get('{expense}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'show'])->name('show');
            Route::put('{expense}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'update'])->name('update');
            Route::delete('{expense}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'destroy'])->name('destroy');
            
            // Expense Categories
            Route::get('categories', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'categories'])->name('categories');
            Route::get('monthly-summary', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'monthlySummary'])->name('monthly-summary');
        });
    });

    // Project Management API
    Route::prefix('projects')->name('api.projects.')->group(function () {
        // Standard CRUD
        Route::get('/', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'index'])->name('index');
        Route::post('/', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'store'])->name('store');
        Route::get('{project}', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'show'])->name('show');
        Route::put('{project}', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'update'])->name('update');
        Route::delete('{project}', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'destroy'])->name('destroy');
        
        // Project Actions
        Route::patch('{project}/status', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'updateStatus'])->name('status.update');
        Route::get('{project}/progress', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'getProgress'])->name('progress');
        
        // Project Tasks API
        Route::prefix('{project}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'index'])->name('index');
            Route::post('/', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'store'])->name('store');
            Route::get('{task}', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'show'])->name('show');
            Route::put('{task}', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'update'])->name('update');
            Route::delete('{task}', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'destroy'])->name('destroy');
            
            Route::patch('{task}/status', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'updateStatus'])->name('status.update');
            Route::patch('{task}/assign', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'assign'])->name('assign');
        });
        
        // Quick Access
        Route::get('active', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'active'])->name('active');
        Route::get('my-projects', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'myProjects'])->name('my-projects');
    });

    // Reports API
    Route::prefix('reports')->name('api.reports.')->group(function () {
        Route::get('dashboard', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('financial', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'financial'])->name('financial');
        Route::get('tickets', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'tickets'])->name('tickets');
        Route::get('assets', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'assets'])->name('assets');
        Route::get('clients', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'clients'])->name('clients');
        Route::get('projects', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'projects'])->name('projects');
        Route::get('users', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'users'])->name('users');
        
        // Custom Reports
        Route::post('custom', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'generateCustomReport'])->name('custom');
        Route::get('export/{type}', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'exportReport'])->name('export');
    });

    // User Management API
    Route::prefix('users')->name('api.users.')->group(function () {
        // Profile Routes (All Users)
        Route::get('profile', [App\Http\Controllers\UserController::class, 'profile'])->name('profile');
        Route::put('profile', [App\Http\Controllers\UserController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/settings', [App\Http\Controllers\UserController::class, 'updateSettings'])->name('profile.settings.update');
        
        // User Management (Admin/Manager Only)
        Route::middleware('role:manager')->group(function () {
            Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\UserController::class, 'store'])->name('store');
            Route::get('{user}', [App\Http\Controllers\UserController::class, 'show'])->name('show');
            Route::put('{user}', [App\Http\Controllers\UserController::class, 'update'])->name('update');
            Route::delete('{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
            
            // User Actions
            Route::patch('{user}/role', [App\Http\Controllers\UserController::class, 'updateRole'])->name('role.update');
            Route::patch('{user}/status', [App\Http\Controllers\UserController::class, 'updateStatus'])->name('status.update');
            Route::patch('{user}/archive', [App\Http\Controllers\UserController::class, 'archive'])->name('archive');
            Route::patch('{user}/restore', [App\Http\Controllers\UserController::class, 'restore'])->name('restore');
            
            Route::get('{user}/activity', [App\Http\Controllers\UserController::class, 'getActivityLog'])->name('activity');
        });
        
        // Quick Access
        Route::get('technicians', [App\Http\Controllers\UserController::class, 'getActiveTechnicians'])->name('technicians');
        Route::get('online', [App\Http\Controllers\UserController::class, 'getOnlineUsers'])->name('online');
    });
    
    // File Management API
    Route::prefix('files')->name('api.files.')->group(function () {
        Route::post('upload', [\Foleybridge\Nestogy\Http\Controllers\FileController::class, 'upload'])->name('upload');
        Route::get('{file}', [\Foleybridge\Nestogy\Http\Controllers\FileController::class, 'show'])->name('show');
        Route::delete('{file}', [\Foleybridge\Nestogy\Http\Controllers\FileController::class, 'destroy'])->name('destroy');
        
        // Bulk File Operations
        Route::post('bulk-upload', [\Foleybridge\Nestogy\Http\Controllers\FileController::class, 'bulkUpload'])->name('bulk-upload');
        Route::delete('bulk-delete', [\Foleybridge\Nestogy\Http\Controllers\FileController::class, 'bulkDelete'])->name('bulk-delete');
    });
    
    // Search API
    Route::prefix('search')->name('api.search.')->group(function () {
        Route::get('global', [App\Http\Controllers\SearchController::class, 'global'])->name('global');
        Route::get('clients', [App\Http\Controllers\SearchController::class, 'clients'])->name('clients');
        Route::get('tickets', [App\Http\Controllers\SearchController::class, 'tickets'])->name('tickets');
        Route::get('assets', [App\Http\Controllers\SearchController::class, 'assets'])->name('assets');
        Route::get('invoices', [App\Http\Controllers\SearchController::class, 'invoices'])->name('invoices');
        Route::get('users', [App\Http\Controllers\SearchController::class, 'users'])->name('users');
        Route::get('projects', [App\Http\Controllers\SearchController::class, 'projects'])->name('projects');
    });
    
    // Settings API (Admin Only)
    Route::prefix('settings')->name('api.settings.')->middleware('role:admin')->group(function () {
        Route::get('/', [App\Http\Controllers\SettingsController::class, 'index'])->name('index');
        Route::put('/', [App\Http\Controllers\SettingsController::class, 'update'])->name('update');
        
        Route::get('company', [App\Http\Controllers\SettingsController::class, 'company'])->name('company');
        Route::put('company', [App\Http\Controllers\SettingsController::class, 'updateCompany'])->name('company.update');
        
        Route::get('email', [App\Http\Controllers\SettingsController::class, 'email'])->name('email');
        Route::put('email', [App\Http\Controllers\SettingsController::class, 'updateEmail'])->name('email.update');
        Route::post('email/test', [App\Http\Controllers\SettingsController::class, 'testEmail'])->name('email.test');
        
        Route::get('integrations', [App\Http\Controllers\SettingsController::class, 'integrations'])->name('integrations');
        Route::put('integrations', [App\Http\Controllers\SettingsController::class, 'updateIntegrations'])->name('integrations.update');
        
        Route::post('backup', [App\Http\Controllers\SettingsController::class, 'createBackup'])->name('backup.create');
        Route::get('logs', [App\Http\Controllers\SettingsController::class, 'logs'])->name('logs');
    });
});

// Integration Webhooks (No Authentication Required)
Route::prefix('webhooks')->name('api.webhooks.')->middleware('throttle:60,1')->group(function () {
    Route::post('stripe', [\Foleybridge\Nestogy\Domains\Integration\Controllers\StripeWebhookController::class, 'handle'])->name('stripe');
    Route::post('plaid', [\Foleybridge\Nestogy\Domains\Integration\Controllers\PlaidWebhookController::class, 'handle'])->name('plaid');
    Route::post('email', [\Foleybridge\Nestogy\Domains\Integration\Controllers\EmailWebhookController::class, 'handle'])->name('email');
    Route::post('sms', [\Foleybridge\Nestogy\Domains\Integration\Controllers\SmsWebhookController::class, 'handle'])->name('sms');
});

// Public API Endpoints (Rate Limited)
Route::prefix('public')->name('api.public.')->middleware('throttle:30,1')->group(function () {
    Route::get('status', function () {
        return response()->json([
            'status' => 'online',
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toISOString()
        ]);
    })->name('status');
    
    Route::get('health', function () {
        return response()->json([
            'database' => 'connected',
            'cache' => 'active',
            'queue' => 'running'
        ]);
    })->name('health');
});