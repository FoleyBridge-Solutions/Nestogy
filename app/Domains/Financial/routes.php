<?php

// Financial module routes

use App\Domains\Contract\Controllers\ContractController;
use App\Domains\Financial\Controllers\InvoiceController;
use App\Domains\Financial\Controllers\QuoteController;
use App\Domains\Financial\Controllers\RecurringInvoiceController;
use Illuminate\Support\Facades\Route;

const BUILDER_DRAFT_PATH = '/builder/draft';

// IMPORTANT: Include 'web' middleware for session, CSRF, and other web-specific functionality
Route::middleware(['web', 'auth', 'verified'])->prefix('financial')->name('financial.')->group(function () {

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
        Route::resource('document-templates', \App\Domains\Knowledge\Http\Controllers\Api\DocumentTemplateController::class)->only(['store', 'index']);
        Route::post('document-templates/{template}/favorite', [\App\Domains\Knowledge\Http\Controllers\Api\DocumentTemplateController::class, 'toggleFavorite'])->name('document-templates.favorite');
    });

    // Invoice routes
    Route::resource('invoices', InvoiceController::class)->except(['show']);
    Route::get('invoices/{invoice}', \App\Livewire\Financial\InvoiceShow::class)->name('invoices.show');
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
        Route::post(BUILDER_DRAFT_PATH, [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'saveDraft'])->name('builder.draft.save');
        Route::get(BUILDER_DRAFT_PATH, [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'loadDraft'])->name('builder.draft.load');
        Route::delete(BUILDER_DRAFT_PATH, [\App\Domains\Contract\Controllers\ContractBuilderController::class, 'clearDraft'])->name('builder.draft.clear');
    });

    // Contract routes (resource route comes after specific routes to avoid conflicts)
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
    // Route::resource('payments', \App\Domains\Financial\Controllers\PaymentController::class);

    // Expense routes
    Route::resource('expenses', \App\Domains\Financial\Controllers\ExpenseController::class);

    // Recurring invoices routes
    Route::resource('recurring-invoices', \App\Domains\Client\Controllers\RecurringInvoiceController::class);

    // Recurring billing routes (for VoIP and usage-based billing)
    Route::resource('recurring', \App\Domains\Financial\Controllers\RecurringController::class);

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
    Route::resource('payments', \App\Domains\Financial\Controllers\PaymentController::class);

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

    // Accounting Routes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [\App\Domains\Financial\Controllers\AccountingController::class, 'chartOfAccounts'])->name('index');
        Route::get('/chart', [\App\Domains\Financial\Controllers\AccountingController::class, 'chartOfAccounts'])->name('chart');
        Route::post('/reconcile', [\App\Domains\Financial\Controllers\AccountingController::class, 'reconcile'])->name('reconcile');
    });

    // Journal Routes
    Route::prefix('journal')->name('journal.')->group(function () {
        Route::get('/', [\App\Domains\Financial\Controllers\AccountingController::class, 'journalEntries'])->name('index');
        Route::get('/entries', [\App\Domains\Financial\Controllers\AccountingController::class, 'journalEntries'])->name('entries');
        Route::post('/entry', [\App\Domains\Financial\Controllers\AccountingController::class, 'createJournalEntry'])->name('store');
    });

    // Tax Routes
    Route::prefix('tax')->name('tax.')->group(function () {
        Route::get('/', [\App\Domains\Financial\Controllers\TaxRateController::class, 'index'])->name('index');
        Route::get('/rates', [\App\Domains\Financial\Controllers\TaxRateController::class, 'index'])->name('rates');
        Route::get('/reports', [\App\Domains\Financial\Controllers\TaxRateController::class, 'reports'])->name('reports');
    });

    // Additional utility routes
    Route::get('clients/search', [QuoteController::class, 'searchClients'])->name('clients.search');
});
