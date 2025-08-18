# Nestogy Deduplication Training Guide

## 🎯 Overview

This guide provides comprehensive training for developers on the new deduplication framework implemented in the Nestogy MSP platform. The framework eliminates 60-80% of code duplication while enhancing security, performance, and maintainability.

## 📚 Table of Contents

1. [Quick Start](#quick-start)
2. [Core Concepts](#core-concepts)
3. [Base Classes Deep Dive](#base-classes-deep-dive)
4. [Traits and Their Powers](#traits-and-their-powers)
5. [Frontend Components](#frontend-components)
6. [Migration Patterns](#migration-patterns)
7. [Common Scenarios](#common-scenarios)
8. [Troubleshooting](#troubleshooting)
9. [Best Practices](#best-practices)
10. [Performance Tips](#performance-tips)

---

## 🚀 Quick Start

### New Controller (5 minutes)

```php
<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Client;
use App\Domains\Client\Services\ClientService;

class ClientController extends BaseController
{
    protected function initializeController(): void
    {
        $this->modelClass = Client::class;
        $this->serviceClass = ClientService::class;
        $this->resourceName = 'clients';
        $this->viewPrefix = 'clients';
        $this->eagerLoadRelations = ['primaryContact', 'primaryLocation'];
    }

    // That's it! You get index, show, create, store, edit, update, destroy for free
    
    // Add custom methods only when needed
    public function customAction(Request $request, Client $client)
    {
        $this->authorize('update', $client);
        
        // Your custom logic here
        
        return $this->successResponse('Action completed');
    }
}
```

### New Service (3 minutes)

```php
<?php

namespace App\Domains\Client\Services;

use App\Services\BaseService;
use App\Models\Client;

class ClientService extends BaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = Client::class;
        $this->searchableFields = ['name', 'email', 'company_name'];
    }

    // You get create, update, delete, search, paginate for free
    
    // Add hooks for custom logic
    protected function afterCreate(Model $model, array $data): void
    {
        // Custom post-creation logic
        $this->createWelcomeEmail($model);
    }
}
```

### New Model (2 minutes)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\{BelongsToCompany, HasCompanyScope, HasSearch, HasFilters, HasArchiving, HasActivity};

class Client extends Model
{
    use BelongsToCompany, HasCompanyScope, HasSearch, HasFilters, HasArchiving, HasActivity;

    protected array $searchableFields = ['name', 'email', 'company_name'];
    
    // Company scoping, search, filters, archiving, and activity logging work automatically!
}
```

---

## 🧠 Core Concepts

### 1. **Inheritance Over Duplication**

**Before:** Every controller had 200+ lines of duplicate CRUD logic
**After:** Extend BaseController and get it all for free

### 2. **Traits for Common Functionality**

**Before:** Copy-paste company scoping in every model query
**After:** Add `HasCompanyScope` trait and it's automatic

### 3. **Convention Over Configuration**

**Before:** Manual route definitions, authorization checks, response formatting
**After:** Follow naming conventions and it works automatically

### 4. **Hooks for Customization**

**Before:** Override entire methods to add small customizations
**After:** Use hook methods like `afterCreate()`, `beforeDelete()`

---

## 🏗️ Base Classes Deep Dive

### BaseController Powers

```php
// You get these methods for FREE:
public function index(Request $request)     // List with filtering, search, pagination
public function show(Model $model)         // Show single record with eager loading
public function create()                   // Show create form
public function store(FormRequest $request) // Create new record with validation
public function edit(Model $model)         // Show edit form
public function update(FormRequest $request, Model $model) // Update record
public function destroy(Model $model)      // Delete record with authorization
```

#### Customization Points

```php
class MyController extends BaseController
{
    // Required: Set up your controller
    protected function initializeController(): void
    {
        $this->modelClass = MyModel::class;
        $this->serviceClass = MyService::class;
        $this->resourceName = 'my_resources';
        $this->viewPrefix = 'my_resources';
        $this->eagerLoadRelations = ['relation1', 'relation2'];
    }

    // Optional: Customize filtering
    protected function getFilters(Request $request): array
    {
        return $request->only(['search', 'status', 'type', 'created_after']);
    }

    // Optional: Add custom filters
    protected function applyCustomFilters($query, Request $request)
    {
        if ($request->filled('created_after')) {
            $query->whereDate('created_at', '>=', $request->created_after);
        }
        return $query;
    }

    // Optional: Add data to index view
    protected function getIndexViewData(Request $request): array
    {
        return [
            'categories' => Category::all(),
            'statistics' => $this->getStatistics()
        ];
    }

    // Optional: Add data to show view
    protected function getShowViewData(Model $model): array
    {
        return [
            'relatedRecords' => $model->relatedRecords()->take(5)->get()
        ];
    }
}
```

### BaseService Powers

```php
// You get these methods for FREE:
public function create(array $data): Model
public function update(Model $model, array $data): Model
public function delete(Model $model): bool
public function archive(Model $model): bool
public function restore(Model $model): bool
public function search(string $term, array $filters = []): Collection
public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
```

#### Service Hooks

```php
class MyService extends BaseService
{
    // Before hooks (can modify data)
    protected function beforeCreate(array &$data): void
    {
        $data['status'] = 'pending';
    }

    protected function beforeUpdate(Model $model, array &$data): void
    {
        if ($model->status === 'locked') {
            throw new \Exception('Cannot update locked record');
        }
    }

    protected function beforeDelete(Model $model): void
    {
        if ($model->hasActiveRelations()) {
            throw new \Exception('Cannot delete record with active relations');
        }
    }

    // After hooks (for side effects)
    protected function afterCreate(Model $model, array $data): void
    {
        $this->sendNotification($model);
        $this->createAuditLog($model, 'created');
    }

    protected function afterUpdate(Model $model, array $originalData, array $newData): void
    {
        $this->syncRelatedData($model, $newData);
    }

    protected function afterDelete(Model $model): void
    {
        $this->cleanupRelatedData($model);
    }
}
```

### BaseFormRequest Powers

```php
// You get these features for FREE:
- Automatic authorization based on model policies
- Common field validation (email, phone, currency, etc.)
- Company-scoped relationship validation
- Consistent error messages
- CSRF protection
```

#### Form Request Customization

```php
class StoreClientRequest extends BaseFormRequest
{
    protected function initializeRequest(): void
    {
        $this->modelClass = Client::class;
    }

    // Only define rules specific to your model
    protected function getSpecificRules(): array
    {
        return [
            'company_name' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'referral_source' => 'nullable|string|max:100'
        ];
    }

    // Optional: Custom field preparation
    protected function prepareSpecificFields(): void
    {
        $this->merge([
            'company_name' => ucwords($this->company_name ?? ''),
            'lead' => $this->boolean('lead', false)
        ]);
    }

    // Optional: Custom validation messages
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'company_name.required' => 'Company name is required for business clients'
        ]);
    }
}
```

---

## 🔧 Traits and Their Powers

### HasCompanyScope

**Automatic multi-tenancy - No more manual company scoping!**

```php
// Before (EVERY SINGLE QUERY):
$clients = Client::where('company_id', Auth::user()->company_id)->get();

// After (AUTOMATIC):
$clients = Client::all(); // Automatically scoped to current company
```

**What it does:**
- Adds global scope to all queries
- Automatically sets company_id on creation
- Prevents cross-company data leakage

### HasSearch

**Full-text search across multiple fields and relationships**

```php
class Client extends Model
{
    use HasSearch;
    
    protected array $searchableFields = [
        'name',
        'email', 
        'company_name',
        'primaryContact.name',  // Search in relationships!
        'primaryContact.email'
    ];
}

// Usage:
$results = Client::search('john smith')->get();
// Finds clients with name/email containing "john" OR "smith"
// Also searches in related contact names/emails
```

### HasFilters

**Standardized filtering with custom extensions**

```php
// Built-in filters work automatically:
$clients = Client::filter([
    'status' => 'active',
    'type' => 'customer',
    'created_after' => '2024-01-01'
])->get();

// Custom filters in your model:
protected function applyCustomFilter($query, string $key, $value)
{
    switch ($key) {
        case 'has_overdue_invoices':
            return $query->whereHas('invoices', function($q) {
                $q->where('status', 'sent')->where('due_date', '<', now());
            });
    }
    return $query;
}
```

### HasArchiving

**Soft delete on steroids**

```php
// Standard operations:
$client->archive();           // Soft delete with archived_at timestamp
$client->restore();           // Restore from archive
$client->forceArchive();      // Hard delete

// Queries:
$active = Client::active()->get();        // Non-archived records
$archived = Client::archived()->get();    // Archived records
$all = Client::withArchived()->get();     // All records
```

### HasActivity

**Automatic audit logging**

```php
// Logs are created automatically for:
// - Model creation, updates, deletion
// - Archiving and restoration
// - Custom activities you define

// View activity:
$activities = $client->activities()->get();

// Log custom activity:
$client->logActivity('status_changed', [
    'from' => 'pending',
    'to' => 'active',
    'reason' => 'Payment received'
]);
```

---

## 🎨 Frontend Components

### Base Component

**Common Alpine.js functionality for all components**

```javascript
import baseComponent from './components/base-component.js';

function clientList() {
    return {
        ...baseComponent({
            apiEndpoint: '/api/clients',
            defaultFilters: { status: 'active' }
        }),
        
        // Your custom properties
        selectedClients: [],
        
        // Your custom methods
        bulkAction(action) {
            this.makeRequest(`/api/clients/bulk`, {
                method: 'POST',
                body: {
                    action: action,
                    client_ids: this.selectedClients
                }
            });
        }
    };
}
```

### DataTable Component

**Reusable table with server-side processing**

```javascript
import { dataTable } from './components/data-table.js';

function clientTable() {
    return dataTable({
        endpoint: '/api/clients',
        columns: [
            { key: 'name', label: 'Name', sortable: true },
            { key: 'email', label: 'Email', sortable: true },
            { key: 'status', label: 'Status', filterable: true },
            { key: 'created_at', label: 'Created', sortable: true, type: 'date' }
        ],
        filters: [
            { key: 'status', type: 'select', options: ['active', 'inactive'] },
            { key: 'type', type: 'select', options: ['customer', 'lead'] }
        ],
        bulkActions: [
            { key: 'activate', label: 'Activate' },
            { key: 'archive', label: 'Archive' }
        ]
    });
}
```

### Form Handler

**Advanced form handling with validation and auto-save**

```javascript
import { formHandler } from './components/form-handler.js';

function clientForm(clientId = null) {
    return formHandler({
        endpoint: clientId ? `/api/clients/${clientId}` : '/api/clients',
        method: clientId ? 'PUT' : 'POST',
        autoSave: true,
        autoSaveDelay: 2000,
        
        // Custom validation
        customValidation: {
            email: (value) => {
                if (!value.includes('@')) {
                    return 'Invalid email format';
                }
            }
        },
        
        // Custom hooks
        beforeSubmit: (data) => {
            data.company_name = data.company_name?.toUpperCase();
            return data;
        },
        
        afterSubmit: (response) => {
            if (response.data.client) {
                this.showSuccessMessage('Client saved successfully');
            }
        }
    });
}
```

---

## 🔄 Migration Patterns

### Controller Migration Steps

1. **Backup Original File**
   ```bash
   cp app/Domains/Client/Controllers/ClientController.php app/Domains/Client/Controllers/ClientController.php.backup
   ```

2. **Change Extends Clause**
   ```php
   // From:
   class ClientController extends Controller
   
   // To:
   class ClientController extends BaseController
   ```

3. **Add BaseController Import**
   ```php
   use App\Http\Controllers\BaseController;
   ```

4. **Add initializeController Method**
   ```php
   protected function initializeController(): void
   {
       $this->modelClass = Client::class;
       $this->serviceClass = ClientService::class;
       $this->resourceName = 'clients';
       $this->viewPrefix = 'clients';
   }
   ```

5. **Remove Standard CRUD Methods**
   - Delete index(), show(), create(), store(), edit(), update(), destroy()
   - Keep only custom domain-specific methods

6. **Convert Filtering to Protected Methods**
   ```php
   protected function getFilters(Request $request): array
   {
       return $request->only(['search', 'status', 'type']);
   }
   
   protected function applyCustomFilters($query, Request $request)
   {
       // Move custom filtering logic here
       return $query;
   }
   ```

### Service Migration Steps

1. **Extend BaseService**
   ```php
   class ClientService extends BaseService
   ```

2. **Add initializeService Method**
   ```php
   protected function initializeService(): void
   {
       $this->modelClass = Client::class;
       $this->searchableFields = ['name', 'email'];
   }
   ```

3. **Convert Methods to Hooks**
   ```php
   // From:
   public function create(array $data)
   {
       $client = Client::create($data);
       $this->sendWelcomeEmail($client);
       return $client;
   }
   
   // To:
   protected function afterCreate(Model $model, array $data): void
   {
       $this->sendWelcomeEmail($model);
   }
   ```

### Model Migration Steps

1. **Add New Traits**
   ```php
   use App\Traits\{HasCompanyScope, HasSearch, HasFilters, HasArchiving, HasActivity};
   
   class Client extends Model
   {
       use HasFactory, SoftDeletes, BelongsToCompany,
           HasCompanyScope, HasSearch, HasFilters, HasArchiving, HasActivity;
   }
   ```

2. **Define Searchable Fields**
   ```php
   protected array $searchableFields = [
       'name', 'email', 'company_name', 'primaryContact.name'
   ];
   ```

3. **Remove Manual Company Scoping**
   ```php
   // Remove these manual scopes:
   public function scopeForCurrentCompany($query) { ... }
   
   // The HasCompanyScope trait handles this automatically
   ```

---

## 🛠️ Common Scenarios

### Scenario 1: Creating a New Domain

**Step 1: Create the Model**
```php
<?php

namespace App\Domains\Project\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\{BelongsToCompany, HasCompanyScope, HasSearch, HasFilters, HasArchiving, HasActivity};

class Project extends Model
{
    use BelongsToCompany, HasCompanyScope, HasSearch, HasFilters, HasArchiving, HasActivity;

    protected array $searchableFields = ['name', 'description', 'client.name'];
    
    protected $fillable = ['name', 'description', 'client_id', 'status', 'deadline'];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
```

**Step 2: Create the Service**
```php
<?php

namespace App\Domains\Project\Services;

use App\Services\BaseService;
use App\Domains\Project\Models\Project;

class ProjectService extends BaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = Project::class;
        $this->searchableFields = ['name', 'description'];
    }

    protected function afterCreate(Model $model, array $data): void
    {
        // Send project creation notification
        $this->notifyTeam($model);
    }

    private function notifyTeam(Project $project)
    {
        // Notification logic
    }
}
```

**Step 3: Create Form Requests**
```php
<?php

namespace App\Domains\Project\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Domains\Project\Models\Project;

class StoreProjectRequest extends BaseFormRequest
{
    protected function initializeRequest(): void
    {
        $this->modelClass = Project::class;
    }

    protected function getSpecificRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date|after:today',
        ];
    }
}
```

**Step 4: Create the Controller**
```php
<?php

namespace App\Domains\Project\Controllers;

use App\Http\Controllers\BaseController;
use App\Domains\Project\Models\Project;
use App\Domains\Project\Services\ProjectService;

class ProjectController extends BaseController
{
    protected function initializeController(): void
    {
        $this->modelClass = Project::class;
        $this->serviceClass = ProjectService::class;
        $this->resourceName = 'projects';
        $this->viewPrefix = 'projects';
        $this->eagerLoadRelations = ['client'];
    }

    protected function getFilters(Request $request): array
    {
        return $request->only(['search', 'status', 'client_id']);
    }

    // Custom method for project-specific functionality
    public function updateStatus(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        
        $request->validate([
            'status' => 'required|in:active,completed,on_hold'
        ]);
        
        $service = app($this->serviceClass);
        $service->update($project, $request->only('status'));
        
        return $this->successResponse('Project status updated');
    }
}
```

### Scenario 2: Adding Complex Filtering

```php
class ProductController extends BaseController
{
    protected function applyCustomFilters($query, Request $request)
    {
        // Price range filter
        if ($request->filled(['price_min', 'price_max'])) {
            $query->whereBetween('price', [$request->price_min, $request->price_max]);
        }
        
        // Category hierarchy filter
        if ($request->filled('category_id')) {
            $categoryIds = Category::descendantsAndSelf($request->category_id)->pluck('id');
            $query->whereIn('category_id', $categoryIds);
        }
        
        // Availability filter
        if ($request->filled('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }
        
        // Date range filter
        if ($request->filled(['created_from', 'created_to'])) {
            $query->whereBetween('created_at', [
                $request->created_from,
                $request->created_to
            ]);
        }
        
        return $query;
    }
}
```

### Scenario 3: Handling File Uploads

```php
class DocumentService extends BaseService
{
    protected function beforeCreate(array &$data): void
    {
        if (isset($data['file'])) {
            $data['file_path'] = $this->storeFile($data['file']);
            $data['file_size'] = $data['file']->getSize();
            $data['mime_type'] = $data['file']->getMimeType();
            unset($data['file']); // Remove file object before database storage
        }
    }

    protected function afterDelete(Model $model): void
    {
        // Clean up file when document is deleted
        if ($model->file_path && Storage::exists($model->file_path)) {
            Storage::delete($model->file_path);
        }
    }

    private function storeFile($file): string
    {
        return $file->store('documents', 'local');
    }
}
```

### Scenario 4: Complex Validation

```php
class InvoiceRequest extends BaseFormRequest
{
    protected function getSpecificRules(): array
    {
        return [
            'invoice_number' => [
                'required',
                'string',
                Rule::unique('invoices', 'invoice_number')
                    ->where('company_id', Auth::user()->company_id)
                    ->ignore($this->invoice)
            ],
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('company_id', Auth::user()->company_id);
                })
            ],
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    protected function prepareSpecificFields(): void
    {
        // Calculate total automatically
        $total = 0;
        if ($this->has('items')) {
            foreach ($this->items as $item) {
                $total += ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
            }
        }
        
        $this->merge(['total' => $total]);
    }
}
```

---

## 🐛 Troubleshooting

### Common Issues and Solutions

#### Issue 1: "Class not found" errors

**Problem:** Missing imports for base classes
```
Class 'App\Http\Controllers\BaseController' not found
```

**Solution:** Add proper use statements
```php
use App\Http\Controllers\BaseController;
use App\Services\BaseService;
use App\Http\Requests\BaseFormRequest;
```

#### Issue 2: Authorization failures

**Problem:** BaseFormRequest authorization method conflicts
```
This action is unauthorized.
```

**Solution:** Remove custom authorize() method
```php
// Remove this:
public function authorize(): bool
{
    return Auth::check();
}

// BaseFormRequest handles authorization automatically using policies
```

#### Issue 3: Company scoping issues

**Problem:** Seeing data from other companies
```
Query returns records from all companies
```

**Solution:** Ensure model uses HasCompanyScope trait
```php
use App\Traits\HasCompanyScope;

class MyModel extends Model
{
    use HasCompanyScope; // This trait is required
}
```

#### Issue 4: Search not working

**Problem:** Search returns no results
```
Model::search('term') returns empty collection
```

**Solution:** Define searchableFields array
```php
class MyModel extends Model
{
    use HasSearch;
    
    protected array $searchableFields = ['name', 'email']; // Add this
}
```

#### Issue 5: Filters not applying

**Problem:** Custom filters ignored in BaseController
```
Filters passed but not applied to query
```

**Solution:** Implement getFilters() method
```php
protected function getFilters(Request $request): array
{
    return $request->only(['status', 'type', 'search']); // Define allowed filters
}
```

#### Issue 6: Frontend components not working

**Problem:** Alpine.js components throwing errors
```
Cannot read property 'makeRequest' of undefined
```

**Solution:** Import base component correctly
```javascript
import baseComponent from './components/base-component.js';

function myComponent() {
    return {
        ...baseComponent(), // Spread base functionality
        
        // Your custom properties
    };
}
```

### Debugging Tips

1. **Enable Query Logging**
   ```php
   // In your controller method:
   DB::enableQueryLog();
   $results = MyModel::search('term')->get();
   dd(DB::getQueryLog()); // See actual SQL queries
   ```

2. **Check Trait Loading**
   ```php
   // Verify traits are loaded:
   dd(class_uses_recursive(MyModel::class));
   ```

3. **Validate Service Initialization**
   ```php
   // In your service constructor:
   public function __construct()
   {
       parent::__construct();
       dd($this->modelClass, $this->searchableFields); // Check initialization
   }
   ```

---

## 💡 Best Practices

### 1. Naming Conventions

```php
// Models: Singular, PascalCase
class Client extends Model {}

// Controllers: Plural + Controller
class ClientsController extends BaseController {}

// Services: Singular + Service  
class ClientService extends BaseService {}

// Requests: Action + Model + Request
class StoreClientRequest extends BaseFormRequest {}
class UpdateClientRequest extends BaseFormRequest {}

// Views: Plural, kebab-case
resources/views/clients/index.blade.php
resources/views/clients/show.blade.php
```

### 2. Service Layer Usage

```php
// ✅ Good: Business logic in services
class ClientService extends BaseService
{
    public function convertToCustomer(Client $client): Client
    {
        return DB::transaction(function () use ($client) {
            $client->update(['lead' => false]);
            $this->createWelcomePackage($client);
            $this->notifyAccount($client);
            return $client;
        });
    }
}

// ❌ Bad: Business logic in controllers
class ClientController extends BaseController
{
    public function convertToCustomer(Client $client)
    {
        $client->lead = false;
        $client->save();
        // Lots of business logic here...
    }
}
```

### 3. Efficient Querying

```php
// ✅ Good: Use eager loading
protected function initializeController(): void
{
    $this->eagerLoadRelations = ['client', 'assignedUser', 'category'];
}

// ✅ Good: Define searchable fields thoughtfully
protected array $searchableFields = [
    'name',           // Direct field
    'email',          // Direct field  
    'client.name',    // Relationship field
    'tags.name'       // Many-to-many relationship
];

// ❌ Bad: N+1 queries
foreach ($tickets as $ticket) {
    echo $ticket->client->name; // This causes N+1 queries
}
```

### 4. Security First

```php
// ✅ Good: Let traits handle company scoping
$clients = Client::all(); // Automatically scoped to current company

// ✅ Good: Use policies for authorization
$this->authorize('update', $client);

// ✅ Good: Validate all inputs
class StoreClientRequest extends BaseFormRequest
{
    protected function getSpecificRules(): array
    {
        return [
            'email' => 'required|email|unique:clients,email'
        ];
    }
}

// ❌ Bad: Manual company scoping (error-prone)
$clients = Client::where('company_id', Auth::user()->company_id)->get();
```

### 5. Error Handling

```php
// ✅ Good: Use service hooks for error handling
protected function beforeDelete(Model $model): void
{
    if ($model->hasActiveInvoices()) {
        throw new \Exception('Cannot delete client with active invoices');
    }
}

// ✅ Good: Return meaningful responses
return $this->errorResponse('Client not found', 404);
return $this->successResponse('Client created successfully', $client);

// ❌ Bad: Silent failures
try {
    $client->delete();
} catch (\Exception $e) {
    // Silent failure
}
```

---

## ⚡ Performance Tips

### 1. Query Optimization

```php
// ✅ Good: Eager load relationships
$this->eagerLoadRelations = ['client', 'assignedUser', 'replies.user'];

// ✅ Good: Use specific selects when possible
protected function buildIndexQuery(Request $request)
{
    return parent::buildIndexQuery($request)
        ->select(['id', 'name', 'email', 'status', 'created_at']);
}

// ✅ Good: Add database indexes for searchable fields
// In your migration:
$table->index(['name', 'email']);
$table->index(['company_id', 'status']);
```

### 2. Caching Strategies

```php
// ✅ Good: Cache expensive calculations
public function getStatistics(): array
{
    return Cache::remember(
        "client_statistics_{$this->getCurrentCompanyId()}",
        3600, // 1 hour
        function () {
            return [
                'total_clients' => Client::count(),
                'active_clients' => Client::where('status', 'active')->count(),
                'revenue_this_month' => $this->calculateMonthlyRevenue()
            ];
        }
    );
}
```

### 3. Pagination Best Practices

```php
// ✅ Good: Use reasonable page sizes
protected function getPerPage(Request $request): int
{
    $perPage = min($request->get('per_page', 15), 100); // Max 100 items
    return $perPage;
}

// ✅ Good: Use cursor pagination for large datasets
protected function buildIndexQuery(Request $request)
{
    $query = parent::buildIndexQuery($request);
    
    if ($request->filled('cursor')) {
        return $query->cursorPaginate($this->getPerPage($request));
    }
    
    return $query->paginate($this->getPerPage($request));
}
```

### 4. Memory Management

```php
// ✅ Good: Process large datasets in chunks
public function processLargeDataset(): void
{
    Client::chunk(1000, function ($clients) {
        foreach ($clients as $client) {
            $this->processClient($client);
        }
    });
}

// ✅ Good: Clear unnecessary data
protected function afterBulkOperation(): void
{
    // Clear model cache
    Model::clearBootedModels();
    
    // Force garbage collection
    gc_collect_cycles();
}
```

---

## 🚀 Advanced Techniques

### Custom Global Scopes

```php
// For more complex scoping beyond company
class ActiveScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('is_active', true);
    }
}

// In your model:
protected static function booted()
{
    static::addGlobalScope(new ActiveScope);
}
```

### Dynamic Service Loading

```php
// For polymorphic relationships
class NotificationService extends BaseService
{
    public function send(Model $notifiable, string $message): void
    {
        $serviceName = class_basename($notifiable) . 'NotificationService';
        $serviceClass = "App\\Services\\{$serviceName}";
        
        if (class_exists($serviceClass)) {
            app($serviceClass)->sendNotification($notifiable, $message);
        } else {
            $this->sendGenericNotification($notifiable, $message);
        }
    }
}
```

### Event-Driven Architecture

```php
// Use model events with the HasActivity trait
class Client extends Model
{
    use HasActivity;
    
    protected static function booted()
    {
        static::created(function ($client) {
            event(new ClientCreated($client));
        });
        
        static::updated(function ($client) {
            if ($client->wasChanged('status')) {
                event(new ClientStatusChanged($client));
            }
        });
    }
}
```

---

## 📖 Resources

### Quick Reference Cards

**BaseController Methods:**
- `index()` - List resources with filtering
- `show()` - Display single resource
- `store()` - Create new resource
- `update()` - Update existing resource
- `destroy()` - Delete resource

**Service Hooks:**
- `beforeCreate()` - Pre-creation logic
- `afterCreate()` - Post-creation logic
- `beforeUpdate()` - Pre-update logic
- `afterUpdate()` - Post-update logic
- `beforeDelete()` - Pre-deletion logic
- `afterDelete()` - Post-deletion logic

**Trait Capabilities:**
- `HasCompanyScope` - Automatic multi-tenancy
- `HasSearch` - Full-text search
- `HasFilters` - Standardized filtering
- `HasArchiving` - Soft delete management
- `HasActivity` - Audit logging

### Helper Commands

```bash
# Automated refactoring
php refactor-automation.php --domain=Client --type=controller

# Performance benchmarking
php performance-benchmarks.php --iterations=1000

# Validation
php validate-deduplication.php

# Code quality check
vendor/bin/phpstan analyse app/Domains --level=5
```

### Migration Checklist

- [ ] Models updated with new traits
- [ ] Controllers extending BaseController
- [ ] Services extending BaseService
- [ ] Form requests extending BaseFormRequest
- [ ] Frontend components using base components
- [ ] Tests updated for new structure
- [ ] Documentation updated
- [ ] Performance validated

---

## 🎓 Conclusion

The Nestogy deduplication framework represents a major evolution in our codebase architecture. By mastering these patterns, you'll write:

- **60-80% less code** for standard operations
- **More secure** applications with automatic company scoping
- **Better performing** applications with optimized queries
- **More maintainable** code with consistent patterns

Remember: The framework does the heavy lifting, so you can focus on business logic that adds value to our MSP platform.

**Happy coding! 🚀**

---

*For questions or clarifications, refer to the example files in each domain or consult the migration guide.*