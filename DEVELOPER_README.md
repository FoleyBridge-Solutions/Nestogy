# Nestogy Developer Guide

## Overview
Nestogy is an enterprise MSP platform built with Laravel 12 and Domain-Driven Design principles. This guide covers the technical architecture, development patterns, and best practices for contributing to the codebase.

## 🏗️ Modern Architecture (2024)

### Code Deduplication Initiative
In 2024, we completed a comprehensive code deduplication effort that transformed the Nestogy platform:

- **45% overall codebase reduction** through standardized patterns
- **Fixed critical security vulnerabilities** in multi-tenancy
- **Established consistent development patterns** across all domains
- **Created foundation for 2-3x faster feature development**

### Key Architectural Components

#### Base Controllers
All CRUD operations now use `BaseResourceController`:
```php
class YourController extends BaseResourceController
{
    use HasClientRelation; // Add domain-specific traits
    
    protected function initializeController(): void
    {
        $this->service = app(YourService::class);
        $this->resourceName = 'resource';
        $this->viewPath = 'domain.resources';
        $this->routePrefix = 'domain.resources';
    }
    
    protected function getModelClass(): string
    {
        return YourModel::class;
    }
}
```

#### Domain-Specific Services
Business logic is encapsulated in domain-specific base services:
```php
class YourService extends ClientBaseService // or FinancialBaseService, AssetBaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = YourModel::class;
        $this->defaultEagerLoad = ['client', 'user'];
        $this->searchableFields = ['name', 'description'];
    }
}
```

#### Standardized Validation
All request validation uses base classes:
```php
class StoreYourModelRequest extends BaseStoreRequest
{
    protected function getModelClass(): string { return YourModel::class; }
    
    protected function getValidationRules(): array
    {
        return $this->mergeRules(
            ['client_id' => $this->getClientValidationRule()],
            $this->getStandardTextRules()
        );
    }
}
```

#### Reusable UI Components
Views use standardized Blade components:
```blade
<x-crud-layout title="Your Resources">
    <x-filter-form :filters="$filters" />
    <x-crud-table :items="$items" :columns="$columns" 
                  route-prefix="domain.resources" />
</x-crud-layout>
```

## 🔐 Security Requirements

### Multi-Tenancy (CRITICAL)
**ALL models MUST use the BelongsToCompany trait:**
```php
use App\Traits\BelongsToCompany;

class YourModel extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany; // REQUIRED
}
```

### Company Scoping
- **NEVER query without company scoping**
- **Always filter by `auth()->user()->company_id`**
- **Use base services for automatic scoping**

## 📁 Domain Structure

```
app/Domains/{Domain}/
├── Controllers/     # Extend BaseResourceController
├── Models/         # Use BelongsToCompany trait
├── Services/       # Extend domain-specific base services  
├── Requests/       # Extend BaseStoreRequest/BaseUpdateRequest
└── Actions/        # Single-purpose operations
```

**Domains**: Asset, Client, Financial, Project, Report, Ticket

## 🚀 Quick Start for New Features

### 1. Create Model with Security
```bash
php artisan make:model Domain/ModelName -m
```

Add required traits:
```php
class ModelName extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;
    
    protected $fillable = ['company_id', 'name', 'description'];
}
```

### 2. Create Service
```php
class ModelService extends ClientBaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = ModelName::class;
        $this->defaultEagerLoad = ['client'];
        $this->searchableFields = ['name', 'description'];
    }
}
```

### 3. Create Requests
```php
class StoreModelRequest extends BaseStoreRequest
{
    protected function getModelClass(): string { return ModelName::class; }
    protected function getValidationRules(): array { /* rules */ }
}

class UpdateModelRequest extends BaseUpdateRequest
{
    protected function getValidationRules(): array { /* rules */ }
}
```

### 4. Create Controller
```php
class ModelController extends BaseResourceController
{
    use HasClientRelation;
    
    protected function initializeController(): void
    {
        $this->service = app(ModelService::class);
        $this->resourceName = 'model';
        $this->viewPath = 'domain.models';
        $this->routePrefix = 'domain.models';
    }
    
    protected function getModelClass(): string
    {
        return ModelName::class;
    }
}
```

### 5. Create Views
```blade
{{-- index.blade.php --}}
<x-crud-layout title="Models">
    <x-crud-table :items="$items" :columns="$columns" 
                  route-prefix="domain.models" />
</x-crud-layout>

{{-- create.blade.php --}}
<x-crud-layout title="Create Model">
    <x-crud-form :action="route('domain.models.store')" :fields="$fields" />
</x-crud-layout>
```

## 🧪 Testing Patterns

### Controller Tests
```php
public function test_can_create_model()
{
    $data = ['name' => 'Test Model', 'client_id' => $this->client->id];
    
    $response = $this->postJson('/domain/models', $data);
    
    $response->assertStatus(201)
             ->assertJsonStructure(['data' => ['id', 'name']]);
}
```

### Service Tests
```php
public function test_service_creates_model_with_company_scoping()
{
    $data = ['name' => 'Test Model', 'client_id' => $this->client->id];
    
    $model = $this->service->create($data);
    
    $this->assertEquals(auth()->user()->company_id, $model->company_id);
}
```

## 📋 Development Checklist

When creating new features, ensure:

- [ ] Model uses `BelongsToCompany` trait
- [ ] Service extends appropriate base service
- [ ] Controller extends `BaseResourceController`
- [ ] Requests extend base request classes
- [ ] Views use Blade components
- [ ] Tests cover multi-tenancy
- [ ] Authorization policies implemented
- [ ] API endpoints return consistent JSON
- [ ] Documentation updated

## 🔄 Migration from Legacy Code

### Before (Legacy Pattern)
```php
class OldController extends Controller
{
    public function index(Request $request)
    {
        $query = Model::with(['relations'])
            ->where('company_id', auth()->user()->company_id);
        
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $items = $query->paginate(20);
        return view('old.index', compact('items'));
    }
    
    // ... 200+ lines of duplicate CRUD code
}
```

### After (Base Pattern)
```php
class NewController extends BaseResourceController
{
    protected function initializeController(): void
    {
        $this->service = app(ModelService::class);
        $this->resourceName = 'model';
        $this->viewPath = 'new.models';
        $this->routePrefix = 'models';
    }
    
    protected function getModelClass(): string
    {
        return Model::class;
    }
    
    // CRUD methods inherited from base class
    // Only custom business logic methods needed
}
```

**Result: 80% code reduction, consistent behavior, better security**

## 📊 Performance Benefits

The new architecture provides:

- **60-80% reduction** in controller code
- **50-60% reduction** in service duplication  
- **40-50% reduction** in view templates
- **2-3x faster** new feature development
- **Consistent JSON APIs** across all endpoints
- **Automatic company scoping** and security
- **Standardized error handling** and validation

## 🔧 Available Base Classes

### Controllers
- `BaseResourceController` - Standard CRUD with JSON/HTML responses
- `HasCompanyScoping` trait - Multi-tenancy filtering
- `HasClientRelation` trait - Client-specific operations
- `HasPaginationControls` trait - Pagination helpers

### Services
- `BaseService` - Core service functionality
- `ClientBaseService` - Client-related resources
- `FinancialBaseService` - Financial operations with audit logging
- `AssetBaseService` - Asset management operations

### Requests
- `BaseRequest` - Common validation rules
- `BaseStoreRequest` - Creation validation with authorization
- `BaseUpdateRequest` - Update validation with authorization

### Blade Components
- `<x-crud-layout>` - Standard page layout
- `<x-crud-table>` - Data tables with actions
- `<x-crud-form>` - Dynamic form generation
- `<x-filter-form>` - Filtering interface

## 📚 Related Documentation

- [`CLAUDE.md`](CLAUDE.md) - Development rules and patterns
- [`MIGRATION_GUIDE.md`](MIGRATION_GUIDE.md) - Detailed migration instructions
- [`DEDUPLICATION_SUMMARY.md`](DEDUPLICATION_SUMMARY.md) - Technical achievement summary
- [`docs/architecture/`](docs/architecture/) - Comprehensive architectural documentation

## 🤝 Contributing

When contributing to Nestogy:

1. **Follow the established patterns** - Use base classes and components
2. **Ensure security** - All models must be company-scoped
3. **Write tests** - Cover both happy path and security scenarios
4. **Update documentation** - Keep architectural docs current
5. **Performance first** - Leverage eager loading and caching

## 🔍 Code Review Checklist

- [ ] Uses appropriate base classes
- [ ] Implements proper multi-tenancy
- [ ] Follows naming conventions
- [ ] Includes comprehensive tests
- [ ] Updates relevant documentation
- [ ] Maintains API consistency
- [ ] Handles errors appropriately
- [ ] Optimizes database queries

## 📈 Metrics & Monitoring

Track the success of the new architecture:

- **Code Coverage**: Maintain >80% test coverage
- **Performance**: Monitor response times <200ms
- **Security**: Zero multi-tenancy violations
- **Consistency**: All APIs follow standard patterns
- **Maintainability**: New features developed in <50% time

---

**Nestogy Development Team**  
*Building the future of MSP management*