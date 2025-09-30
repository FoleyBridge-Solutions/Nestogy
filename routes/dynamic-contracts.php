<?php

use App\Domains\Contract\Controllers\Api\DynamicContractApiController;
use App\Domains\Contract\Controllers\DynamicContractController;
use App\Http\Middleware\DynamicRouteMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dynamic Contract Routes
|--------------------------------------------------------------------------
|
| These routes are automatically generated based on company navigation
| configuration. They provide extensible contract management without
| hardcoded route definitions.
|
*/

// Web Routes for Dynamic Contracts
Route::middleware(['web', 'auth', 'company', DynamicRouteMiddleware::class])
    ->prefix('contracts')
    ->name('contracts.')
    ->group(function () {

        // Default fallback routes - these will be overridden by dynamic routes
        Route::get('/', [DynamicContractController::class, 'index'])->name('index');
        Route::get('/create', [DynamicContractController::class, 'create'])->name('create');
        Route::post('/', [DynamicContractController::class, 'store'])->name('store');
        Route::get('/{id}', [DynamicContractController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [DynamicContractController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DynamicContractController::class, 'update'])->name('update');
        Route::delete('/{id}', [DynamicContractController::class, 'destroy'])->name('destroy');

        // Bulk operations
        Route::post('/bulk-action', [DynamicContractController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/export', [DynamicContractController::class, 'export'])->name('export');
        Route::post('/import', [DynamicContractController::class, 'import'])->name('import');

        // Dynamic contract type routes will be registered here by DynamicRouteServiceProvider
        // Format: contracts/{type}/{action}
        // Examples:
        // - contracts/service-agreements
        // - contracts/service-agreements/create
        // - contracts/service-agreements/{id}
        // - contracts/maintenance-contracts
        // - contracts/voip-services

    });

// API Routes for Dynamic Contracts
Route::middleware(['api', 'auth:sanctum', 'company'])
    ->prefix('api/contracts')
    ->name('api.contracts.')
    ->group(function () {

        // Default API endpoints
        Route::get('/', [DynamicContractApiController::class, 'index'])->name('index');
        Route::post('/', [DynamicContractApiController::class, 'store'])->name('store');
        Route::get('/{id}', [DynamicContractApiController::class, 'show'])->name('show');
        Route::put('/{id}', [DynamicContractApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [DynamicContractApiController::class, 'destroy'])->name('destroy');

        // Bulk operations
        Route::post('/bulk-action', [DynamicContractApiController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [DynamicContractApiController::class, 'export'])->name('export');
        Route::post('/import', [DynamicContractApiController::class, 'import'])->name('import');

        // Schema and configuration endpoints
        Route::get('/schema', [DynamicContractApiController::class, 'schema'])->name('schema');
        Route::get('/config', [DynamicContractApiController::class, 'config'])->name('config');
        Route::get('/types', [DynamicContractApiController::class, 'types'])->name('types');

        // Dynamic API routes will be registered here
        // Format: api/contracts/{type}

    });

// Admin routes for managing dynamic contract configuration
Route::middleware(['web', 'auth', 'company', 'can:manage-contracts'])
    ->prefix('admin/contracts')
    ->name('admin.contracts.')
    ->group(function () {

        // Navigation management
        Route::get('/navigation', [DynamicContractController::class, 'navigationBuilder'])->name('navigation.index');
        Route::post('/navigation', [DynamicContractController::class, 'saveNavigation'])->name('navigation.store');
        Route::get('/navigation/preview', [DynamicContractController::class, 'previewNavigation'])->name('navigation.preview');
        Route::post('/navigation/import', [DynamicContractController::class, 'importNavigation'])->name('navigation.import');
        Route::get('/navigation/export', [DynamicContractController::class, 'exportNavigation'])->name('navigation.export');

        // Form configuration
        Route::get('/forms', [DynamicContractController::class, 'formsIndex'])->name('forms.index');
        Route::get('/forms/{type}/designer', [DynamicContractController::class, 'formDesigner'])->name('forms.designer');
        Route::post('/forms/{type}', [DynamicContractController::class, 'saveForm'])->name('forms.store');
        Route::get('/forms/{type}/preview', [DynamicContractController::class, 'previewForm'])->name('forms.preview');

        // View configuration
        Route::get('/views', [DynamicContractController::class, 'viewsIndex'])->name('views.index');
        Route::get('/views/{type}/configurator', [DynamicContractController::class, 'viewConfigurator'])->name('views.configurator');
        Route::post('/views/{type}', [DynamicContractController::class, 'saveView'])->name('views.store');
        Route::get('/views/{type}/preview', [DynamicContractController::class, 'previewView'])->name('views.preview');

        // Widget configuration
        Route::get('/widgets', [DynamicContractController::class, 'widgetsIndex'])->name('widgets.index');
        Route::post('/widgets', [DynamicContractController::class, 'saveWidget'])->name('widgets.store');
        Route::delete('/widgets/{id}', [DynamicContractController::class, 'deleteWidget'])->name('widgets.destroy');

        // Type management
        Route::get('/types', [DynamicContractController::class, 'typesIndex'])->name('types.index');
        Route::post('/types', [DynamicContractController::class, 'createType'])->name('types.store');
        Route::put('/types/{id}', [DynamicContractController::class, 'updateType'])->name('types.update');
        Route::delete('/types/{id}', [DynamicContractController::class, 'deleteType'])->name('types.destroy');

        // Workflow management
        Route::get('/workflows', [DynamicContractController::class, 'workflowsIndex'])->name('workflows.index');
        Route::post('/workflows', [DynamicContractController::class, 'createWorkflow'])->name('workflows.store');
        Route::put('/workflows/{id}', [DynamicContractController::class, 'updateWorkflow'])->name('workflows.update');

        // Plugin management
        Route::get('/plugins', [DynamicContractController::class, 'pluginsIndex'])->name('plugins.index');
        Route::post('/plugins/install', [DynamicContractController::class, 'installPlugin'])->name('plugins.install');
        Route::post('/plugins/{id}/activate', [DynamicContractController::class, 'activatePlugin'])->name('plugins.activate');
        Route::post('/plugins/{id}/deactivate', [DynamicContractController::class, 'deactivatePlugin'])->name('plugins.deactivate');

        // System tools
        Route::post('/rebuild-routes', [DynamicContractController::class, 'rebuildRoutes'])->name('rebuild-routes');
        Route::post('/clear-cache', [DynamicContractController::class, 'clearCache'])->name('clear-cache');
        Route::get('/system-status', [DynamicContractController::class, 'systemStatus'])->name('system-status');

    });

// Webhook routes for dynamic contract events
Route::middleware(['api', 'webhook-signature'])
    ->prefix('webhooks/contracts')
    ->name('webhooks.contracts.')
    ->group(function () {

        Route::post('/status-changed', [DynamicContractController::class, 'statusChangedWebhook'])->name('status-changed');
        Route::post('/created', [DynamicContractController::class, 'createdWebhook'])->name('created');
        Route::post('/updated', [DynamicContractController::class, 'updatedWebhook'])->name('updated');
        Route::post('/deleted', [DynamicContractController::class, 'deletedWebhook'])->name('deleted');
        Route::post('/expired', [DynamicContractController::class, 'expiredWebhook'])->name('expired');
        Route::post('/renewal-due', [DynamicContractController::class, 'renewalDueWebhook'])->name('renewal-due');

    });
