<?php

use App\Domains\Core\Controllers\Settings\UnifiedSettingsController;
use App\Domains\Security\Controllers\PermissionController;
use App\Domains\Security\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings Routes - Clean Architecture (NO TECH DEBT)
|--------------------------------------------------------------------------
*/

Route::prefix('settings')->name('settings.')->middleware(['auth', 'verified'])->group(function () {

    // Main Settings Dashboard
    Route::get('/', [UnifiedSettingsController::class, 'index'])->name('index');

    // Legacy route aliases for backward compatibility
    Route::get('/general', function () {
        return redirect()->route('settings.category.show', ['domain' => 'company', 'category' => 'general']);
    })->name('general');

    Route::get('/security', function () {
        return redirect()->route('settings.category.show', ['domain' => 'security', 'category' => 'access']);
    })->name('security');

    Route::get('/email', function () {
        return view('settings.email-livewire');
    })->name('email');

    Route::get('/company-email-provider', function () {
        return redirect()->route('settings.category.show', ['domain' => 'communication', 'category' => 'email']);
    })->name('company-email-provider');

    Route::get('/user-management', function () {
        return redirect()->route('settings.category.show', ['domain' => 'company', 'category' => 'users']);
    })->name('user-management');

    Route::get('/billing-financial', function () {
        return redirect()->route('settings.category.show', ['domain' => 'financial', 'category' => 'billing']);
    })->name('billing-financial');

    Route::get('/integrations', function () {
        return redirect()->route('settings.category.show', ['domain' => 'integrations', 'category' => 'overview']);
    })->name('integrations');

    Route::get('/ticketing-service-desk', function () {
        return redirect()->route('settings.category.show', ['domain' => 'operations', 'category' => 'ticketing']);
    })->name('ticketing-service-desk');

    Route::get('/project-management', function () {
        return redirect()->route('settings.category.show', ['domain' => 'operations', 'category' => 'projects']);
    })->name('project-management');

    Route::get('/asset-inventory', function () {
        return redirect()->route('settings.category.show', ['domain' => 'operations', 'category' => 'assets']);
    })->name('asset-inventory');

    Route::get('/automation-workflows', function () {
        return redirect()->route('settings.category.show', ['domain' => 'system', 'category' => 'automation']);
    })->name('automation-workflows');

    Route::get('/api-webhooks', function () {
        return redirect()->route('settings.category.show', ['domain' => 'integrations', 'category' => 'api']);
    })->name('api-webhooks');

    Route::get('/data-management', function () {
        return redirect()->route('settings.category.show', ['domain' => 'system', 'category' => 'database']);
    })->name('data-management');

    // Additional legacy routes for all navigation items
    Route::get('/accounting', function () {
        return redirect()->route('settings.category.show', ['domain' => 'financial', 'category' => 'accounting']);
    })->name('accounting');

    Route::get('/backup-recovery', function () {
        return redirect()->route('settings.category.show', ['domain' => 'system', 'category' => 'backup']);
    })->name('backup-recovery');

    Route::get('/client-portal', function () {
        return redirect()->route('settings.category.show', ['domain' => 'operations', 'category' => 'portal']);
    })->name('client-portal');

    Route::get('/compliance-audit', function () {
        return redirect()->route('settings.category.show', ['domain' => 'security', 'category' => 'compliance']);
    })->name('compliance-audit');

    Route::get('/contract-clauses', function () {
        return redirect()->route('settings.category.show', ['domain' => 'operations', 'category' => 'clauses']);
    })->name('contract-clauses');

    Route::get('/knowledge-base', function () {
        return redirect()->route('settings.category.show', ['domain' => 'operations', 'category' => 'knowledge']);
    })->name('knowledge-base');

    Route::get('/mobile-remote', function () {
        return redirect()->route('settings.category.show', ['domain' => 'system', 'category' => 'mobile']);
    })->name('mobile-remote');

    Route::get('/notifications-alerts', function () {
        return redirect()->route('settings.category.show', ['domain' => 'communication', 'category' => 'notifications']);
    })->name('notifications-alerts');

    Route::get('/payment-gateways', function () {
        return redirect()->route('settings.category.show', ['domain' => 'financial', 'category' => 'payments']);
    })->name('payment-gateways');

    Route::get('/performance-optimization', function () {
        return redirect()->route('settings.category.show', ['domain' => 'system', 'category' => 'performance']);
    })->name('performance-optimization');

    Route::get('/physical-mail', function () {
        return redirect()->route('settings.category.show', ['domain' => 'communication', 'category' => 'physical-mail']);
    })->name('physical-mail');

    Route::get('/reporting-analytics', function () {
        return redirect()->route('settings.category.show', ['domain' => 'operations', 'category' => 'reports']);
    })->name('reporting-analytics');

    Route::get('/rmm-monitoring', function () {
        return redirect()->route('settings.category.show', ['domain' => 'integrations', 'category' => 'rmm']);
    })->name('rmm-monitoring');

    Route::get('/training-documentation', function () {
        return redirect()->route('settings.category.show', ['domain' => 'operations', 'category' => 'training']);
    })->name('training-documentation');

    // Contract Templates route
    Route::prefix('contract-templates')->name('contract-templates.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('settings.category.show', ['domain' => 'operations', 'category' => 'contracts']);
        })->name('index');
    });

    // Settings Import/Export
    Route::get('/export', [UnifiedSettingsController::class, 'export'])->name('export');
    Route::post('/import', [UnifiedSettingsController::class, 'import'])->name('import');

    // Domain-based Settings (Clean URLs)
    Route::prefix('{domain}')->group(function () {
        // Domain index
        Route::get('/', [UnifiedSettingsController::class, 'showDomain'])->name('domain.index');

        // Category-specific routes
        Route::prefix('{category}')->group(function () {
            Route::get('/', [UnifiedSettingsController::class, 'showCategory'])->name('category.show');
            Route::put('/', [UnifiedSettingsController::class, 'updateCategory'])->name('category.update');
            Route::post('/test', [UnifiedSettingsController::class, 'testCategory'])->name('category.test');
            Route::post('/reset', [UnifiedSettingsController::class, 'resetToDefaults'])->name('category.reset');
        });
    });

    // Roles & Permissions (these stay separate due to their complexity)
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        Route::post('/{role}/duplicate', [RoleController::class, 'duplicate'])->name('duplicate');
    });

    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/matrix', [PermissionController::class, 'matrix'])->name('matrix');
        Route::post('/matrix', [PermissionController::class, 'updateMatrix'])->name('matrix.update');
        Route::get('/user/{user}', [PermissionController::class, 'userPermissions'])->name('user');
        Route::put('/user/{user}', [PermissionController::class, 'updateUserPermissions'])->name('user.update');
    });
});
