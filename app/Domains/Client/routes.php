<?php
// Client routes

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
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
    
    // Resource routes - register create/edit/store/update/destroy routes BEFORE the show route
    Route::resource('clients', \App\Domains\Client\Controllers\ClientController::class)->except(['index', 'show', 'edit']);
    
    // Use Livewire component for client edit
    Route::get('clients/{client}/edit', \App\Livewire\Clients\EditClient::class)->name('clients.edit');
    
    // Client-specific routes (using session-based client context) - MUST come BEFORE the {client} route
    Route::prefix('clients')->name('clients.')->middleware('require-client')->group(function () {
        Route::get('switch', [\App\Domains\Client\Controllers\ClientController::class, 'switch'])->name('switch');
        Route::match(['get', 'post'], 'tags', [\App\Domains\Client\Controllers\ClientController::class, 'tags'])->name('tags');
        Route::patch('notes', [\App\Domains\Client\Controllers\ClientController::class, 'updateNotes'])->name('update-notes');
        Route::post('archive', [\App\Domains\Client\Controllers\ClientController::class, 'archive'])->name('archive');
        Route::post('restore', [\App\Domains\Client\Controllers\ClientController::class, 'restore'])->name('restore');
        
        // Contacts routes (using session-based client context)
        Route::get('contacts', [\App\Domains\Client\Controllers\ContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/create', [\App\Domains\Client\Controllers\ContactController::class, 'create'])->name('contacts.create');
        Route::post('contacts', [\App\Domains\Client\Controllers\ContactController::class, 'store'])->name('contacts.store');
        Route::get('contacts/export', [\App\Domains\Client\Controllers\ContactController::class, 'export'])->name('contacts.export');
        Route::get('contacts/{contact}', [\App\Domains\Client\Controllers\ContactController::class, 'show'])->name('contacts.show');
        Route::get('contacts/{contact}/edit', [\App\Domains\Client\Controllers\ContactController::class, 'edit'])->name('contacts.edit');
        Route::put('contacts/{contact}', [\App\Domains\Client\Controllers\ContactController::class, 'update'])->name('contacts.update');
        Route::delete('contacts/{contact}', [\App\Domains\Client\Controllers\ContactController::class, 'destroy'])->name('contacts.destroy');
        
        // Contact API routes for modal functionality
        Route::prefix('contacts/{contact}')->name('contacts.')->group(function () {
            Route::put('portal-access', [\App\Domains\Client\Controllers\ContactController::class, 'updatePortalAccess'])->name('portal-access.update');
            Route::put('security', [\App\Domains\Client\Controllers\ContactController::class, 'updateSecurity'])->name('security.update');
            Route::put('permissions', [\App\Domains\Client\Controllers\ContactController::class, 'updatePermissions'])->name('permissions.update');
            Route::post('lock', [\App\Domains\Client\Controllers\ContactController::class, 'lockAccount'])->name('lock');
            Route::post('unlock', [\App\Domains\Client\Controllers\ContactController::class, 'unlockAccount'])->name('unlock');
            // Portal invitation routes
            Route::post('send-invitation', [\App\Domains\Client\Controllers\ContactController::class, 'sendInvitation'])->name('send-invitation');
            Route::post('resend-invitation', [\App\Domains\Client\Controllers\ContactController::class, 'resendInvitation'])->name('resend-invitation');
            Route::post('revoke-invitation', [\App\Domains\Client\Controllers\ContactController::class, 'revokeInvitation'])->name('revoke-invitation');
        });
        // Locations routes (using session-based client context)
        Route::get('locations/export', [\App\Domains\Client\Controllers\LocationController::class, 'export'])->name('locations.export');
        Route::resource('locations', \App\Domains\Client\Controllers\LocationController::class);
        Route::resource('files', \App\Domains\Client\Controllers\FileController::class);
        Route::resource('documents', \App\Domains\Client\Controllers\DocumentController::class);
        Route::resource('vendors', \App\Domains\Client\Controllers\VendorController::class);
        Route::resource('licenses', \App\Domains\Client\Controllers\LicenseController::class);
        Route::resource('credentials', \App\Domains\Client\Controllers\CredentialController::class);
        Route::resource('domains', \App\Domains\Client\Controllers\DomainController::class);
        Route::resource('services', \App\Domains\Client\Controllers\ServiceController::class);

        // Asset routes (using session-based client context)
        Route::get('assets', [\App\Domains\Asset\Controllers\AssetController::class, 'clientIndex'])->name('assets.index');
        Route::get('assets/create', [\App\Domains\Asset\Controllers\AssetController::class, 'clientCreate'])->name('assets.create');
        Route::post('assets', [\App\Domains\Asset\Controllers\AssetController::class, 'clientStore'])->name('assets.store');
        Route::get('assets/{asset}', [\App\Domains\Asset\Controllers\AssetController::class, 'clientShow'])->name('assets.show');
        Route::get('assets/{asset}/edit', [\App\Domains\Asset\Controllers\AssetController::class, 'clientEdit'])->name('assets.edit');
        Route::put('assets/{asset}', [\App\Domains\Asset\Controllers\AssetController::class, 'clientUpdate'])->name('assets.update');
        Route::delete('assets/{asset}', [\App\Domains\Asset\Controllers\AssetController::class, 'clientDestroy'])->name('assets.destroy');
        
        // IT Documentation routes (using session-based client context)
        Route::get('it-documentation', [\App\Domains\Client\Controllers\ITDocumentationController::class, 'clientIndex'])->name('it-documentation.client-index');
        
        // Communication Log routes (using session-based client context)
        Route::get('communications', [\App\Domains\Client\Controllers\CommunicationLogController::class, 'index'])->name('communications.index');
        Route::get('communications/export', [\App\Domains\Client\Controllers\CommunicationLogController::class, 'export'])->name('communications.export');
        Route::get('communications/create', [\App\Domains\Client\Controllers\CommunicationLogController::class, 'create'])->name('communications.create');
        Route::post('communications', [\App\Domains\Client\Controllers\CommunicationLogController::class, 'store'])->name('communications.store');
        Route::get('communications/{communication}', [\App\Domains\Client\Controllers\CommunicationLogController::class, 'show'])->name('communications.show');
        Route::get('communications/{communication}/edit', [\App\Domains\Client\Controllers\CommunicationLogController::class, 'edit'])->name('communications.edit');
        Route::put('communications/{communication}', [\App\Domains\Client\Controllers\CommunicationLogController::class, 'update'])->name('communications.update');
        Route::delete('communications/{communication}', [\App\Domains\Client\Controllers\CommunicationLogController::class, 'destroy'])->name('communications.destroy');
    });
    
    // Client show route - display specific client dashboard
    // This MUST come AFTER all other client routes to avoid catching specific routes like /clients/contacts
    Route::get('clients/{client}', [\App\Domains\Client\Controllers\ClientController::class, 'show'])->name('clients.show');
});