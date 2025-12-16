<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client Portal Routes
|--------------------------------------------------------------------------
|
| These routes are for the client-facing portal where customers can:
| - View their contracts, invoices, and quotes
| - Submit and track support tickets
| - View their assets and projects
| - Manage their profile
|
*/

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
        Route::get('invoices/summary', [\App\Domains\Client\Controllers\ClientPortalController::class, 'invoicesSummary'])->name('invoices.summary');
        Route::get('invoices/statistics', [\App\Domains\Client\Controllers\ClientPortalController::class, 'invoicesStatistics'])->name('invoices.statistics');
        Route::get('invoices/{invoice}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'showInvoice'])->name('invoices.show');
        Route::get('invoices/{invoice}/download', [\App\Domains\Client\Controllers\ClientPortalController::class, 'downloadClientInvoice'])->name('invoices.download');
        Route::get('invoices/{invoice}/pdf', [\App\Domains\Client\Controllers\ClientPortalController::class, 'viewClientInvoicePdf'])->name('invoices.pdf');
        Route::get('invoices/{invoice}/print', [\App\Domains\Client\Controllers\ClientPortalController::class, 'viewClientInvoicePdf'])->name('invoices.print');
        Route::get('invoices/{invoice}/pay', \App\Livewire\Portal\InvoicePayment::class)->name('invoices.pay');

        // Quotes
        Route::get('quotes', [\App\Domains\Client\Controllers\ClientPortalController::class, 'quotes'])->name('quotes');
        Route::get('quotes/{quote}', [\App\Domains\Client\Controllers\ClientPortalController::class, 'showQuote'])->name('quotes.show');
        Route::get('quotes/{quote}/pdf', [\App\Domains\Client\Controllers\ClientPortalController::class, 'downloadQuotePdf'])->name('quotes.pdf');

        // Tickets
        Route::get('tickets', \App\Livewire\Portal\TicketIndex::class)->name('tickets');
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

        // Reports
        Route::get('reports', \App\Livewire\Portal\Reports::class)->name('reports');

        // Profile
        Route::get('profile', [\App\Domains\Client\Controllers\ClientPortalController::class, 'profile'])->name('profile');
        Route::put('profile', [\App\Domains\Client\Controllers\ClientPortalController::class, 'updateProfile'])->name('profile.update');

        // Notifications
        Route::post('notifications/{notification}/read', [\App\Domains\Client\Controllers\ClientPortalController::class, 'markNotificationAsRead'])->name('notifications.read');

        // Payments
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', \App\Livewire\Portal\PaymentHistory::class)->name('index');
            Route::get('{payment}/confirmation', [\App\Domains\Client\Controllers\ClientPortalController::class, 'paymentConfirmation'])->name('confirmation');
            Route::get('{payment}/receipt', [\App\Domains\Client\Controllers\ClientPortalController::class, 'paymentReceipt'])->name('receipt');
        });

        // Payment Methods
        Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
            Route::get('/', \App\Livewire\Portal\PaymentMethods::class)->name('index');
        });
    });
});
