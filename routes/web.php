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

// Public Routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Guest Invoice View (Public)
Route::get('invoice/{invoice}/view/{token}', [App\Http\Controllers\InvoiceController::class, 'guestView'])
    ->name('invoice.guest.view')
    ->middleware('signed');

// Guest Payment Routes (Public)
Route::prefix('pay')->name('payment.')->group(function () {
    Route::get('invoice/{invoice}/{token}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'showPaymentForm'])
        ->name('form')
        ->middleware('signed');
    Route::post('invoice/{invoice}/{token}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'processPayment'])
        ->name('process')
        ->middleware('signed');
    Route::get('success/{payment}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'success'])
        ->name('success');
    Route::get('cancel/{invoice}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'cancel'])
        ->name('cancel');
});

// Add route alias for Laravel's default auth system
Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');

// Authentication Routes (Guest Only)
Route::middleware('guest')->prefix('auth')->name('auth.')->group(function () {
    Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
    
    Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
    
    Route::get('password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authentication Routes (Authenticated Users)
Route::middleware('auth')->group(function () {
    Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
    
    // Email Verification Routes
    Route::get('email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'show'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])
        ->name('verification.verify')
        ->middleware('signed');
    Route::post('email/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])
        ->name('verification.resend')
        ->middleware('throttle:6,1');
});

// Protected Routes (Authenticated + Verified + Company Scoped)
Route::middleware(['auth', 'verified', 'company'])->group(function () {
    
    // Dashboard Routes
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [App\Http\Controllers\DashboardController::class, 'getData'])->name('dashboard.data');
    Route::get('/dashboard/notifications', [App\Http\Controllers\DashboardController::class, 'getNotifications'])->name('dashboard.notifications');
    Route::patch('/dashboard/notifications/{id}/read', [App\Http\Controllers\DashboardController::class, 'markNotificationRead'])->name('dashboard.notifications.read');
    
    // Client Management Routes
    Route::prefix('clients')->name('clients.')->group(function () {
        // DataTables (must be before resource routes)
        Route::get('data', [App\Http\Controllers\ClientController::class, 'data'])->name('data');
        
        // Client Data Export
        Route::get('export', [App\Http\Controllers\ClientController::class, 'export'])->name('export');
        Route::get('export/csv', [App\Http\Controllers\ClientController::class, 'exportCsv'])->name('export.csv');
        
        // Client Import
        Route::get('import', [App\Http\Controllers\ClientController::class, 'importForm'])->name('import.form');
        Route::post('import', [App\Http\Controllers\ClientController::class, 'import'])->name('import');
        Route::get('import/template', [App\Http\Controllers\ClientController::class, 'downloadTemplate'])->name('import.template');
        Route::get('template/download', [App\Http\Controllers\ClientController::class, 'downloadTemplate'])->name('template.download');
        
        // Client Leads
        Route::get('leads', [App\Http\Controllers\ClientController::class, 'leads'])->name('leads');
        
        // Standard Resource Routes
        Route::get('/', [App\Http\Controllers\ClientController::class, 'index'])->name('index');
        Route::get('create', [App\Http\Controllers\ClientController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\ClientController::class, 'store'])->name('store');
        Route::get('{client}', [App\Http\Controllers\ClientController::class, 'show'])->name('show');
        Route::get('{client}/edit', [App\Http\Controllers\ClientController::class, 'edit'])->name('edit');
        Route::put('{client}', [App\Http\Controllers\ClientController::class, 'update'])->name('update');
        Route::delete('{client}', [App\Http\Controllers\ClientController::class, 'destroy'])->name('destroy');
        
        // Additional Client Routes (must be after resource routes)
        Route::patch('{client}/archive', [App\Http\Controllers\ClientController::class, 'archive'])->name('archive');
        Route::patch('{client}/restore', [App\Http\Controllers\ClientController::class, 'restore'])->name('restore');
        Route::patch('{client}/notes', [App\Http\Controllers\ClientController::class, 'updateNotes'])->name('notes.update');
        Route::post('{client}/convert', [App\Http\Controllers\ClientController::class, 'convertLead'])->name('convert');
        Route::post('{client}/convert-lead', [App\Http\Controllers\ClientController::class, 'convertLead'])->name('convert-lead');
        
        // Client Tags
        Route::match(['get', 'post'], '{client}/tags', [App\Http\Controllers\ClientController::class, 'tags'])->name('tags');
        
        // Client Locations and Contacts
        Route::get('{client}/locations', [\Foleybridge\Nestogy\Domains\Client\Controllers\LocationController::class, 'index'])->name('locations.index');
        Route::post('{client}/locations', [\Foleybridge\Nestogy\Domains\Client\Controllers\LocationController::class, 'store'])->name('locations.store');
        Route::get('{client}/contacts', [\Foleybridge\Nestogy\Domains\Client\Controllers\ContactController::class, 'index'])->name('contacts.index');
        Route::post('{client}/contacts', [\Foleybridge\Nestogy\Domains\Client\Controllers\ContactController::class, 'store'])->name('contacts.store');
    });
    
    // Ticket System Routes
    Route::prefix('tickets')->name('tickets.')->group(function () {
        // Standard Resource Routes
        Route::get('/', [App\Http\Controllers\TicketController::class, 'index'])->name('index');
        Route::get('create', [App\Http\Controllers\TicketController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\TicketController::class, 'store'])->name('store');
        Route::get('{ticket}', [App\Http\Controllers\TicketController::class, 'show'])->name('show');
        Route::get('{ticket}/edit', [App\Http\Controllers\TicketController::class, 'edit'])->name('edit');
        Route::put('{ticket}', [App\Http\Controllers\TicketController::class, 'update'])->name('update');
        Route::delete('{ticket}', [App\Http\Controllers\TicketController::class, 'destroy'])->name('destroy');
        
        // Ticket Management Actions
        Route::post('{ticket}/replies', [App\Http\Controllers\TicketController::class, 'addReply'])->name('replies.store');
        Route::patch('{ticket}/assign', [App\Http\Controllers\TicketController::class, 'assign'])->name('assign');
        Route::patch('{ticket}/status', [App\Http\Controllers\TicketController::class, 'updateStatus'])->name('status.update');
        Route::patch('{ticket}/priority', [App\Http\Controllers\TicketController::class, 'updatePriority'])->name('priority.update');
        Route::patch('{ticket}/schedule', [App\Http\Controllers\TicketController::class, 'schedule'])->name('schedule');
        Route::post('{ticket}/watchers', [App\Http\Controllers\TicketController::class, 'addWatcher'])->name('watchers.add');
        Route::post('{ticket}/merge', [App\Http\Controllers\TicketController::class, 'merge'])->name('merge');
        
        // Ticket File Management
        Route::post('{ticket}/attachments', [App\Http\Controllers\TicketController::class, 'uploadAttachment'])->name('attachments.upload');
        Route::get('{ticket}/pdf', [App\Http\Controllers\TicketController::class, 'generatePdf'])->name('pdf');
        
        // Ticket Data Export
        Route::get('export/csv', [App\Http\Controllers\TicketController::class, 'exportCsv'])->name('export.csv');
        
        // Ticket Viewers
        Route::get('{ticket}/viewers', [App\Http\Controllers\TicketController::class, 'getViewers'])->name('viewers');
        
        // Time Tracking
        Route::prefix('{ticket}/time')->name('time.')->group(function () {
            Route::get('/', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'index'])->name('index');
            Route::post('/', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'store'])->name('store');
            Route::put('{timeEntry}', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'update'])->name('update');
            Route::delete('{timeEntry}', [\Foleybridge\Nestogy\Domains\Ticket\Controllers\TimeEntryController::class, 'destroy'])->name('destroy');
        });
    });
    
    // Asset Management Routes
    Route::prefix('assets')->name('assets.')->group(function () {
        // Standard Resource Routes
        Route::get('/', [App\Http\Controllers\AssetController::class, 'index'])->name('index');
        Route::get('create', [App\Http\Controllers\AssetController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\AssetController::class, 'store'])->name('store');
        Route::get('{asset}', [App\Http\Controllers\AssetController::class, 'show'])->name('show');
        Route::get('{asset}/edit', [App\Http\Controllers\AssetController::class, 'edit'])->name('edit');
        Route::put('{asset}', [App\Http\Controllers\AssetController::class, 'update'])->name('update');
        Route::delete('{asset}', [App\Http\Controllers\AssetController::class, 'destroy'])->name('destroy');
        
        // Asset Management Actions
        Route::patch('{asset}/archive', [App\Http\Controllers\AssetController::class, 'archive'])->name('archive');
        Route::patch('{id}/restore', [App\Http\Controllers\AssetController::class, 'restore'])->name('restore');
        
        // Bulk Operations
        Route::post('bulk/update', [App\Http\Controllers\AssetController::class, 'bulkUpdate'])->name('bulk.update');
        
        // Import/Export
        Route::get('export', [App\Http\Controllers\AssetController::class, 'export'])->name('export');
        Route::get('import', [App\Http\Controllers\AssetController::class, 'importForm'])->name('import.form');
        Route::post('import', [App\Http\Controllers\AssetController::class, 'import'])->name('import');
        Route::get('template/download', [App\Http\Controllers\AssetController::class, 'downloadTemplate'])->name('template.download');
        
        // Special Features
        Route::get('{asset}/qr-code', [App\Http\Controllers\AssetController::class, 'qrCode'])->name('qr-code');
        Route::get('{asset}/label', [App\Http\Controllers\AssetController::class, 'printLabel'])->name('label');
        Route::post('{asset}/check-in-out', [App\Http\Controllers\AssetController::class, 'checkInOut'])->name('check-in-out');
    });
    
    // Financial Management Routes
    Route::prefix('financial')->name('financial.')->group(function () {
        
        // Invoice Management
        Route::prefix('invoices')->name('invoices.')->group(function () {
            // Standard Resource Routes
            Route::get('/', [App\Http\Controllers\InvoiceController::class, 'index'])->name('index');
            Route::get('create', [App\Http\Controllers\InvoiceController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\InvoiceController::class, 'store'])->name('store');
            Route::get('{invoice}', [App\Http\Controllers\InvoiceController::class, 'show'])->name('show');
            Route::get('{invoice}/edit', [App\Http\Controllers\InvoiceController::class, 'edit'])->name('edit');
            Route::put('{invoice}', [App\Http\Controllers\InvoiceController::class, 'update'])->name('update');
            Route::delete('{invoice}', [App\Http\Controllers\InvoiceController::class, 'destroy'])->name('destroy');
            
            // Invoice Item Management
            Route::post('{invoice}/items', [App\Http\Controllers\InvoiceController::class, 'addItem'])->name('items.store');
            Route::put('{invoice}/items/{item}', [App\Http\Controllers\InvoiceController::class, 'updateItem'])->name('items.update');
            Route::delete('{invoice}/items/{item}', [App\Http\Controllers\InvoiceController::class, 'deleteItem'])->name('items.destroy');
            
            // Invoice Actions
            Route::patch('{invoice}/status', [App\Http\Controllers\InvoiceController::class, 'updateStatus'])->name('status.update');
            Route::post('{invoice}/send', [App\Http\Controllers\InvoiceController::class, 'sendEmail'])->name('send');
            Route::get('{invoice}/pdf', [App\Http\Controllers\InvoiceController::class, 'generatePdf'])->name('pdf');
            Route::post('{invoice}/copy', [App\Http\Controllers\InvoiceController::class, 'copy'])->name('copy');
            Route::patch('{invoice}/notes', [App\Http\Controllers\InvoiceController::class, 'updateNotes'])->name('notes.update');
            
            // Invoice Payments
            Route::post('{invoice}/payments', [App\Http\Controllers\InvoiceController::class, 'addPayment'])->name('payments.store');
            
            // Invoice Data Export
            Route::get('export/csv', [App\Http\Controllers\InvoiceController::class, 'exportCsv'])->name('export.csv');
        });
        
        // Payment Management
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'index'])->name('index');
            Route::get('create', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'create'])->name('create');
            Route::post('/', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'store'])->name('store');
            Route::get('{payment}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'show'])->name('show');
            Route::get('{payment}/edit', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'edit'])->name('edit');
            Route::put('{payment}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'update'])->name('update');
            Route::delete('{payment}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\PaymentController::class, 'destroy'])->name('destroy');
        });
        
        // Expense Management
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'index'])->name('index');
            Route::get('create', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'create'])->name('create');
            Route::post('/', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'store'])->name('store');
            Route::get('{expense}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'show'])->name('show');
            Route::get('{expense}/edit', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'edit'])->name('edit');
            Route::put('{expense}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'update'])->name('update');
            Route::delete('{expense}', [\Foleybridge\Nestogy\Domains\Financial\Controllers\ExpenseController::class, 'destroy'])->name('destroy');
        });
    });
    
    // Project Management Routes
    Route::prefix('projects')->name('projects.')->group(function () {
        // Standard Resource Routes
        Route::get('/', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'index'])->name('index');
        Route::get('create', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'create'])->name('create');
        Route::post('/', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'store'])->name('store');
        Route::get('{project}', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'show'])->name('show');
        Route::get('{project}/edit', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'edit'])->name('edit');
        Route::put('{project}', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'update'])->name('update');
        Route::delete('{project}', [\Foleybridge\Nestogy\Domains\Project\Controllers\ProjectController::class, 'destroy'])->name('destroy');
        
        // Project Tasks
        Route::prefix('{project}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'index'])->name('index');
            Route::post('/', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'store'])->name('store');
            Route::get('{task}', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'show'])->name('show');
            Route::put('{task}', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'update'])->name('update');
            Route::delete('{task}', [\Foleybridge\Nestogy\Domains\Project\Controllers\TaskController::class, 'destroy'])->name('destroy');
        });
    });
    
    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('financial', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'financial'])->name('financial');
        Route::get('tickets', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'tickets'])->name('tickets');
        Route::get('assets', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'assets'])->name('assets');
        Route::get('clients', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'clients'])->name('clients');
        Route::get('projects', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'projects'])->name('projects');
        Route::get('users', [\Foleybridge\Nestogy\Domains\Report\Controllers\ReportController::class, 'users'])->name('users');
    });
    
    // User Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        // Profile Routes (All Users)
        Route::get('profile', [App\Http\Controllers\UserController::class, 'profile'])->name('profile');
        Route::put('profile', [App\Http\Controllers\UserController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [App\Http\Controllers\UserController::class, 'updatePassword'])->name('profile.password.update');
        Route::put('profile/settings', [App\Http\Controllers\UserController::class, 'updateSettings'])->name('profile.settings.update');
        
        // User Management Routes (Admin/Manager Only)
        Route::middleware('role:manager')->group(function () {
            Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('index');
            Route::get('create', [App\Http\Controllers\UserController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\UserController::class, 'store'])->name('store');
            Route::get('{user}', [App\Http\Controllers\UserController::class, 'show'])->name('show');
            Route::get('{user}/edit', [App\Http\Controllers\UserController::class, 'edit'])->name('edit');
            Route::put('{user}', [App\Http\Controllers\UserController::class, 'update'])->name('update');
            Route::delete('{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
            
            // User Management Actions
            Route::patch('{user}/password', [App\Http\Controllers\UserController::class, 'updatePassword'])->name('password.update');
            Route::patch('{user}/role', [App\Http\Controllers\UserController::class, 'updateRole'])->name('role.update');
            Route::patch('{user}/status', [App\Http\Controllers\UserController::class, 'updateStatus'])->name('status.update');
            Route::patch('{user}/archive', [App\Http\Controllers\UserController::class, 'archive'])->name('archive');
            Route::patch('{user}/restore', [App\Http\Controllers\UserController::class, 'restore'])->name('restore');
            
            // User Activity and Export
            Route::get('{user}/activity', [App\Http\Controllers\UserController::class, 'getActivityLog'])->name('activity');
            Route::get('export/csv', [App\Http\Controllers\UserController::class, 'exportCsv'])->name('export.csv');
        });
    });
    
    // System Settings Routes (Admin Only)
    Route::prefix('settings')->name('settings.')->middleware('role:admin')->group(function () {
        Route::get('/', [App\Http\Controllers\SettingsController::class, 'index'])->name('index');
        Route::put('/', [App\Http\Controllers\SettingsController::class, 'update'])->name('update');
        
        // Company Settings
        Route::get('company', [App\Http\Controllers\SettingsController::class, 'company'])->name('company');
        Route::put('company', [App\Http\Controllers\SettingsController::class, 'updateCompany'])->name('company.update');
        
        // Email Settings
        Route::get('email', [App\Http\Controllers\SettingsController::class, 'email'])->name('email');
        Route::put('email', [App\Http\Controllers\SettingsController::class, 'updateEmail'])->name('email.update');
        Route::post('email/test', [App\Http\Controllers\SettingsController::class, 'testEmail'])->name('email.test');
        
        // Integration Settings
        Route::get('integrations', [App\Http\Controllers\SettingsController::class, 'integrations'])->name('integrations');
        Route::put('integrations', [App\Http\Controllers\SettingsController::class, 'updateIntegrations'])->name('integrations.update');
        
        // Backup and Maintenance
        Route::get('maintenance', [App\Http\Controllers\SettingsController::class, 'maintenance'])->name('maintenance');
        Route::post('backup', [App\Http\Controllers\SettingsController::class, 'createBackup'])->name('backup.create');
        Route::get('logs', [App\Http\Controllers\SettingsController::class, 'logs'])->name('logs');
    });
    
    // File Management Routes
    Route::prefix('files')->name('files.')->group(function () {
        Route::post('upload', [App\Http\Controllers\FileController::class, 'upload'])->name('upload');
        Route::get('{file}/download', [App\Http\Controllers\FileController::class, 'download'])->name('download');
        Route::delete('{file}', [App\Http\Controllers\FileController::class, 'destroy'])->name('destroy');
    });
    
    // Search Routes
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', [App\Http\Controllers\SearchController::class, 'global'])->name('global');
        Route::get('clients', [App\Http\Controllers\SearchController::class, 'clients'])->name('clients');
        Route::get('tickets', [App\Http\Controllers\SearchController::class, 'tickets'])->name('tickets');
        Route::get('assets', [App\Http\Controllers\SearchController::class, 'assets'])->name('assets');
        Route::get('invoices', [App\Http\Controllers\SearchController::class, 'invoices'])->name('invoices');
    });
    
    // Quick Actions Routes
    Route::prefix('quick')->name('quick.')->group(function () {
        Route::get('clients/active', [App\Http\Controllers\ClientController::class, 'getActiveClients'])->name('clients.active');
        Route::get('users/technicians', [App\Http\Controllers\UserController::class, 'getActiveTechnicians'])->name('users.technicians');
    });
});

// Plugin Replacement Examples (for migration reference)
Route::prefix('examples')->name('examples.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [App\Http\Controllers\ExamplePluginReplacementController::class, 'frontendExamples'])->name('index');
    Route::post('email/send', [App\Http\Controllers\ExamplePluginReplacementController::class, 'sendEmail'])->name('email.send');
    Route::get('email/check', [App\Http\Controllers\ExamplePluginReplacementController::class, 'checkEmails'])->name('email.check');
    Route::post('pdf/generate', [App\Http\Controllers\ExamplePluginReplacementController::class, 'generatePdf'])->name('pdf.generate');
    Route::post('file/upload', [App\Http\Controllers\ExamplePluginReplacementController::class, 'uploadFile'])->name('file.upload');
});
