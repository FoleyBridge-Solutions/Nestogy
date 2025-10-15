# Route Middleware Implementation Guide

This guide provides instructions for applying the new permission middleware to all routes in the Nestogy Laravel application.

## Middleware Application Strategy

### 1. Global Route Groups

The existing routes in `web.php` are already protected with basic authentication and company middleware:

```php
Route::middleware(['auth', 'verified', 'company'])->group(function () {
    // All protected routes
});
```

### 2. Permission Middleware Application

Apply permission middleware to route groups based on domains:

#### Client Routes
```php
Route::middleware(['auth', 'verified', 'company'])->group(function () {
    
    // Client Management Routes
    Route::prefix('clients')->name('clients.')->group(function () {
        // Apply permission middleware to main client routes
        Route::middleware('permission:clients.view')->group(function () {
            Route::get('/', [ClientController::class, 'index'])->name('index');
            Route::get('{client}', [ClientController::class, 'show'])->name('show');
            Route::get('data', [ClientController::class, 'data'])->name('data');
        });
        
        Route::middleware('permission:clients.create')->group(function () {
            Route::get('create', [ClientController::class, 'create'])->name('create');
            Route::post('/', [ClientController::class, 'store'])->name('store');
        });
        
        Route::middleware('permission:clients.edit')->group(function () {
            Route::get('{client}/edit', [ClientController::class, 'edit'])->name('edit');
            Route::put('{client}', [ClientController::class, 'update'])->name('update');
        });
        
        Route::middleware('permission:clients.delete')->group(function () {
            Route::delete('{client}', [ClientController::class, 'destroy'])->name('destroy');
        });
        
        Route::middleware('permission:clients.export')->group(function () {
            Route::get('export', [ClientController::class, 'export'])->name('export');
            Route::get('export/csv', [ClientController::class, 'exportCsv'])->name('export.csv');
        });
        
        Route::middleware('permission:clients.import')->group(function () {
            Route::get('import', [ClientController::class, 'importForm'])->name('import.form');
            Route::post('import', [ClientController::class, 'import'])->name('import');
        });
        
        // Client sub-modules
        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::middleware('permission:clients.contacts.view')->group(function () {
                Route::get('/', [ContactController::class, 'index'])->name('standalone.index');
                Route::get('{contact}', [ContactController::class, 'show'])->name('standalone.show');
            });
            
            Route::middleware('permission:clients.contacts.manage')->group(function () {
                Route::get('create', [ContactController::class, 'create'])->name('standalone.create');
                Route::post('/', [ContactController::class, 'store'])->name('standalone.store');
                Route::get('{contact}/edit', [ContactController::class, 'edit'])->name('standalone.edit');
                Route::put('{contact}', [ContactController::class, 'update'])->name('standalone.update');
                Route::delete('{contact}', [ContactController::class, 'destroy'])->name('standalone.destroy');
            });
            
            Route::middleware('permission:clients.contacts.export')->group(function () {
                Route::get('export/csv', [ContactController::class, 'export'])->name('standalone.export');
            });
        });
        
        // Apply similar pattern for other sub-modules...
    });
});
```

#### Asset Routes
```php
Route::middleware(['auth', 'verified', 'company'])->group(function () {
    
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::middleware('permission:assets.view')->group(function () {
            Route::get('/', [AssetController::class, 'index'])->name('index');
            Route::get('{asset}', [AssetController::class, 'show'])->name('show');
        });
        
        Route::middleware('permission:assets.create')->group(function () {
            Route::get('create', [AssetController::class, 'create'])->name('create');
            Route::post('/', [AssetController::class, 'store'])->name('store');
        });
        
        Route::middleware('permission:assets.edit')->group(function () {
            Route::get('{asset}/edit', [AssetController::class, 'edit'])->name('edit');
            Route::put('{asset}', [AssetController::class, 'update'])->name('update');
        });
        
        Route::middleware('permission:assets.delete')->group(function () {
            Route::delete('{asset}', [AssetController::class, 'destroy'])->name('destroy');
        });
        
        Route::middleware('permission:assets.export')->group(function () {
            Route::get('export', [AssetController::class, 'export'])->name('export');
        });
        
        // Asset Maintenance Routes
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::middleware('permission:assets.maintenance.view')->group(function () {
                Route::get('/', [MaintenanceController::class, 'index'])->name('index');
                Route::get('{maintenance}', [MaintenanceController::class, 'show'])->name('show');
            });
            
            Route::middleware('permission:assets.maintenance.manage')->group(function () {
                Route::get('create', [MaintenanceController::class, 'create'])->name('create');
                Route::post('/', [MaintenanceController::class, 'store'])->name('store');
                Route::get('{maintenance}/edit', [MaintenanceController::class, 'edit'])->name('edit');
                Route::put('{maintenance}', [MaintenanceController::class, 'update'])->name('update');
                Route::delete('{maintenance}', [MaintenanceController::class, 'destroy'])->name('destroy');
                Route::patch('{maintenance}/complete', [MaintenanceController::class, 'markCompleted'])->name('complete');
            });
            
            Route::middleware('permission:assets.maintenance.export')->group(function () {
                Route::get('export/csv', [MaintenanceController::class, 'export'])->name('export');
            });
        });
        
        // Warranties and Depreciation routes follow similar pattern...
    });
});
```

#### Financial Routes
```php
Route::middleware(['auth', 'verified', 'company'])->group(function () {
    
    Route::prefix('financial')->name('financial.')->group(function () {
        
        // Payment Routes
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::middleware('permission:financial.payments.view')->group(function () {
                Route::get('/', [PaymentController::class, 'index'])->name('index');
                Route::get('{payment}', [PaymentController::class, 'show'])->name('show');
            });
            
            Route::middleware('permission:financial.payments.manage')->group(function () {
                Route::get('create', [PaymentController::class, 'create'])->name('create');
                Route::post('/', [PaymentController::class, 'store'])->name('store');
                Route::get('{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
                Route::put('{payment}', [PaymentController::class, 'update'])->name('update');
                Route::delete('{payment}', [PaymentController::class, 'destroy'])->name('destroy');
            });
        });
        
        // Expense Routes with Approval Workflow
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::middleware('permission:financial.expenses.view')->group(function () {
                Route::get('/', [ExpenseController::class, 'index'])->name('index');
                Route::get('{expense}', [ExpenseController::class, 'show'])->name('show');
            });
            
            Route::middleware('permission:financial.expenses.manage')->group(function () {
                Route::get('create', [ExpenseController::class, 'create'])->name('create');
                Route::post('/', [ExpenseController::class, 'store'])->name('store');
                Route::get('{expense}/edit', [ExpenseController::class, 'edit'])->name('edit');
                Route::put('{expense}', [ExpenseController::class, 'update'])->name('update');
                Route::delete('{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
                Route::post('{expense}/submit', [ExpenseController::class, 'submit'])->name('submit');
            });
            
            Route::middleware('permission:financial.expenses.approve')->group(function () {
                Route::post('{expense}/approve', [ExpenseController::class, 'approve'])->name('approve');
                Route::post('{expense}/reject', [ExpenseController::class, 'reject'])->name('reject');
            });
        });
    });
});
```

#### Project Routes
```php
Route::middleware(['auth', 'verified', 'company'])->group(function () {
    
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::middleware('permission:projects.view')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('{project}', [ProjectController::class, 'show'])->name('show');
        });
        
        Route::middleware('permission:projects.create')->group(function () {
            Route::get('create', [ProjectController::class, 'create'])->name('create');
            Route::post('/', [ProjectController::class, 'store'])->name('store');
        });
        
        Route::middleware('permission:projects.edit')->group(function () {
            Route::get('{project}/edit', [ProjectController::class, 'edit'])->name('edit');
            Route::put('{project}', [ProjectController::class, 'update'])->name('update');
        });
        
        Route::middleware('permission:projects.delete')->group(function () {
            Route::delete('{project}', [ProjectController::class, 'destroy'])->name('destroy');
        });
        
        // Project Tasks
        Route::prefix('{project}/tasks')->name('tasks.')->group(function () {
            Route::middleware('permission:projects.tasks.view')->group(function () {
                Route::get('/', [TaskController::class, 'index'])->name('index');
                Route::get('{task}', [TaskController::class, 'show'])->name('show');
            });
            
            Route::middleware('permission:projects.tasks.manage')->group(function () {
                Route::post('/', [TaskController::class, 'store'])->name('store');
                Route::put('{task}', [TaskController::class, 'update'])->name('update');
                Route::delete('{task}', [TaskController::class, 'destroy'])->name('destroy');
            });
        });
    });
});
```

#### Reports Routes
```php
Route::middleware(['auth', 'verified', 'company'])->group(function () {
    
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::middleware('permission:reports.view')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
        });
        
        Route::middleware('permission:reports.financial')->group(function () {
            Route::get('financial', [ReportController::class, 'financial'])->name('financial');
        });
        
        Route::middleware('permission:reports.tickets')->group(function () {
            Route::get('tickets', [ReportController::class, 'tickets'])->name('tickets');
        });
        
        Route::middleware('permission:reports.assets')->group(function () {
            Route::get('assets', [ReportController::class, 'assets'])->name('assets');
        });
        
        Route::middleware('permission:reports.clients')->group(function () {
            Route::get('clients', [ReportController::class, 'clients'])->name('clients');
        });
        
        Route::middleware('permission:reports.projects')->group(function () {
            Route::get('projects', [ReportController::class, 'projects'])->name('projects');
        });
        
        Route::middleware('permission:reports.users')->group(function () {
            Route::get('users', [ReportController::class, 'users'])->name('users');
        });
    });
});
```

#### User Management Routes
```php
Route::middleware(['auth', 'verified', 'company'])->group(function () {
    
    Route::prefix('users')->name('users.')->group(function () {
        // Profile routes (all users)
        Route::get('profile', [UserController::class, 'profile'])->name('profile');
        Route::put('profile', [UserController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [UserController::class, 'updatePassword'])->name('profile.password.update');
        
        // User management routes (permission-controlled)
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('{user}', [UserController::class, 'show'])->name('show');
        });
        
        Route::middleware('permission:users.create')->group(function () {
            Route::get('create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
        });
        
        Route::middleware('permission:users.edit')->group(function () {
            Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('{user}', [UserController::class, 'update'])->name('update');
            Route::patch('{user}/status', [UserController::class, 'updateStatus'])->name('status.update');
        });
        
        Route::middleware('permission:users.manage')->group(function () {
            Route::patch('{user}/role', [UserController::class, 'updateRole'])->name('role.update');
            Route::patch('{user}/archive', [UserController::class, 'archive'])->name('archive');
            Route::patch('{user}/restore', [UserController::class, 'restore'])->name('restore');
        });
        
        Route::middleware('permission:users.delete')->group(function () {
            Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
        });
        
        Route::middleware('permission:users.export')->group(function () {
            Route::get('export/csv', [UserController::class, 'exportCsv'])->name('export.csv');
        });
    });
});
```

### 3. API Routes

For API routes in `routes/api.php`, apply similar middleware patterns:

```php
Route::middleware(['auth:sanctum', 'company'])->group(function () {
    
    // Client API routes
    Route::prefix('clients')->name('api.clients.')->group(function () {
        Route::middleware('permission:clients.view')->group(function () {
            Route::get('/', [Api\ClientController::class, 'index']);
            Route::get('{client}', [Api\ClientController::class, 'show']);
        });
        
        Route::middleware('permission:clients.create')->group(function () {
            Route::post('/', [Api\ClientController::class, 'store']);
        });
        
        Route::middleware('permission:clients.edit')->group(function () {
            Route::put('{client}', [Api\ClientController::class, 'update']);
        });
        
        Route::middleware('permission:clients.delete')->group(function () {
            Route::delete('{client}', [Api\ClientController::class, 'destroy']);
        });
    });
    
    // Apply similar patterns for other domains...
});
```

### 4. Implementation Steps

1. **Backup Current Routes**: Before making changes, backup your current `web.php` and `api.php` files
2. **Gradual Implementation**: Apply middleware to one domain at a time
3. **Testing**: Test each domain thoroughly after applying middleware
4. **Fallback**: Keep the existing role-based middleware as a fallback during transition

### 5. Permission Combinations

For routes requiring multiple permissions, use the `|` (OR) or `&` (AND) operators:

```php
// User needs either permission (OR)
Route::middleware('permission:clients.edit|clients.manage')->group(function () {
    // Edit routes
});

// User needs both permissions (AND)
Route::middleware('permission:financial.view&financial.export')->group(function () {
    // Sensitive financial operations
});
```

### 6. Route-Specific Middleware

For individual routes that need special handling:

```php
Route::get('/clients/{client}/sensitive-data', [ClientController::class, 'sensitiveData'])
    ->middleware(['permission:clients.manage', 'throttle:5,1'])
    ->name('clients.sensitive-data');
```

## Migration Notes

- The existing role-based middleware (`role:admin`, `role:tech`, etc.) can run alongside the new permission middleware during transition
- Controllers already using the `HasAuthorization` trait have additional checks that complement route-level middleware
- Navigation will automatically respect the new permissions once routes are properly protected

## Testing Commands

After implementing route middleware, test with:

```bash
# Test route access
php artisan route:list --name=clients

# Test middleware stack
php artisan route:list --columns=name,method,uri,middleware

# Test permissions in browser
# Login as different users and test access to various routes