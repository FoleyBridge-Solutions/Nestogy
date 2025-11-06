# Nestogy MSP Platform - Development Guide

This guide helps developers set up a local development environment for the Nestogy MSP Platform and covers development workflows, coding standards, and best practices using our modern Laravel 12 architecture with base classes and standardized patterns.

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Modern Architecture Overview](#modern-architecture-overview)
3. [Development Workflow](#development-workflow)
4. [Base Classes & Patterns](#base-classes--patterns)
5. [Coding Standards](#coding-standards)
6. [Testing](#testing)
7. [Debugging](#debugging)
8. [Database Development](#database-development)
9. [Frontend Development](#frontend-development)
10. [API Development](#api-development)
11. [Performance Testing](#performance-testing)
12. [Troubleshooting](#troubleshooting)

## Development Environment Setup

### Prerequisites

- **PHP** 8.4+ with required extensions
- **Composer** 2.0+
- **Node.js** 18.0+ & npm
- **PostgreSQL** 13+ (recommended) or **MySQL** 8.0+ / **MariaDB** 10.5+
- **Redis** (recommended, for caching and queues)
- **Git** for version control
- **Supervisor** (optional, for queue workers)

### Local Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/foleybridge/nestogy-erp.git
   cd nestogy-erp
   ```

2. **Install dependencies**
   ```bash
   # PHP dependencies
   composer install
   
   # Node.js dependencies
   npm install
   ```

3. **Environment setup**
   ```bash
   # Copy environment file
   cp .env.example .env
   
   # Generate application key
   php artisan key:generate
   ```

4. **Configure your .env file**
   ```env
   APP_NAME="Nestogy MSP Platform"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   
   # PostgreSQL (Recommended)
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=nestogy_dev
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   # OR MySQL/MariaDB
   # DB_CONNECTION=mysql
   # DB_HOST=127.0.0.1
   # DB_PORT=3306
   # DB_DATABASE=nestogy_dev
   # DB_USERNAME=your_username
   # DB_PASSWORD=your_password
   
   # Use Redis for better performance
   CACHE_DRIVER=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   
   # Mail settings for local development
   MAIL_MAILER=log
   MAIL_HOST=localhost
   MAIL_PORT=1025
   ```

5. **Database setup**
   
   For PostgreSQL:
   ```bash
   # Create database
   psql -U postgres -c "CREATE DATABASE nestogy_dev WITH ENCODING 'UTF8';"
   
   # Run migrations
   php artisan migrate
   
   # Seed database with sample data
   php artisan db:seed
   ```
   
   For MySQL/MariaDB:
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE nestogy_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # Run migrations
   php artisan migrate
   
   # Seed database with sample data
   php artisan db:seed
   ```

6. **Create storage symlink**
   ```bash
   php artisan storage:link
   ```

### Development Server

Start all development services with one command:

```bash
# Start all services (Laravel, Queue, Logs, Vite)
composer run dev
```

Or start services individually:

```bash
# Laravel development server
php artisan serve

# Asset compilation (watch mode) - Vite 5.x
npm run dev

# Queue worker
php artisan queue:work

# Logs viewer
php artisan pail --timeout=0
```

## Modern Architecture Overview

Nestogy uses a modern, deduplication-focused architecture built on Laravel 12 with standardized base classes and components.

### Key Architectural Benefits (2024)
- **45% code reduction** through base class standardization
- **2-3x faster development** using established patterns
- **Consistent security** with mandatory multi-tenancy
- **Reusable UI components** for rapid development
- **Standardized validation** across all domains

### Base Class Hierarchy
```
BaseResourceController (HTTP layer)
├── Domain-specific traits (HasClientRelation, HasCompanyScoping)
└── Standard CRUD operations with JSON/HTML responses

BaseService (Business logic layer)
├── ClientBaseService (Client-related operations)
├── FinancialBaseService (Financial operations + audit logging)  
├── AssetBaseService (Asset management operations)
└── Standard CRUD with company scoping

BaseRequest (Validation layer)
├── BaseStoreRequest (Creation validation + authorization)
└── BaseUpdateRequest (Update validation + authorization)
```

### Multi-Tenancy Security (CRITICAL)
**ALL models MUST use the BelongsToCompany trait:**
```php
use App\Traits\BelongsToCompany;

class YourModel extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany; // REQUIRED
}
```

### IDE Setup

#### VS Code Extensions

Recommended extensions for VS Code:

- **PHP Intelephense** - PHP language support
- **Laravel Extension Pack** - Laravel-specific tools
- **Prettier** - Code formatting
- **ESLint** - JavaScript linting
- **GitLens** - Git integration
- **Thunder Client** - API testing

#### PhpStorm

Configure PhpStorm for Laravel development:

1. Install Laravel Plugin
2. Configure PHP interpreter (8.2+)
3. Set up database connection
4. Configure Node.js interpreter
5. Enable Laravel code completion

## Development Workflow

### Git Workflow

We follow GitFlow with these branch types:

- **main**: Production-ready code
- **develop**: Integration branch for features
- **feature/**: Feature development (`feature/ticket-system`)
- **hotfix/**: Critical production fixes (`hotfix/security-patch`)
- **release/**: Release preparation (`release/v1.2.0`)

### Feature Development Process

1. **Create feature branch**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/your-feature-name
   ```

2. **Development cycle**
   ```bash
   # Make changes
   # Run tests
   composer run test
   
   # Check code style
   ./vendor/bin/pint --test
   
   # Commit changes
   git add .
   git commit -m "Add: New feature description"
   ```

3. **Push and create PR**
   ```bash
   git push origin feature/your-feature-name
   # Create pull request via GitHub
   ```

### Code Review Process

- All code must be reviewed before merging
- Ensure tests pass and coverage is maintained
- Follow coding standards
- Update documentation if needed
- Test locally before approving

## Coding Standards

### PHP Standards

We follow **PSR-12** coding standards enforced by Laravel Pint.

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

## Base Classes & Patterns

### Controller Pattern (REQUIRED)
**ALL controllers MUST extend BaseResourceController:**

```php
class YourController extends BaseResourceController
{
    use HasClientRelation; // Add domain-specific traits as needed
    
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
    
    // CRUD methods are inherited - only add custom business logic
}
```

### Service Pattern (REQUIRED)
**Use domain-specific base services:**

```php
class YourService extends ClientBaseService // or FinancialBaseService, AssetBaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = YourModel::class;
        $this->defaultEagerLoad = ['client', 'user'];
        $this->searchableFields = ['name', 'description'];
    }
    
    // Standard CRUD inherited - add custom business logic only
    public function customBusinessMethod(array $data): YourModel
    {
        // Custom logic here
        return $this->create($data);
    }
}
```

### Request Validation Pattern (REQUIRED)
**Use base request classes:**

```php
class StoreYourModelRequest extends BaseStoreRequest
{
    protected function getModelClass(): string 
    { 
        return YourModel::class; 
    }
    
    protected function getValidationRules(): array
    {
        return $this->mergeRules(
            [
                'client_id' => $this->getClientValidationRule(),
                'custom_field' => 'required|string|max:255',
            ],
            $this->getStandardTextRules()
        );
    }
    
    protected function getBooleanFields(): array
    {
        return ['is_active', 'is_featured'];
    }
}
```

### Index Pages with BaseIndexComponent (REQUIRED)

**ALL index pages MUST use BaseIndexComponent for consistency. DO NOT create custom index implementations.**

#### 1. Create Livewire Component

Extend BaseIndexComponent and implement required abstract methods:

```php
<?php

namespace App\Livewire\Marketing;

use App\Livewire\BaseIndexComponent;
use App\Domains\Marketing\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Builder;

class CampaignIndex extends BaseIndexComponent
{
    // Custom filters (optional)
    public $statusFilter = '';
    public $typeFilter = '';

    protected function getDefaultSort(): array
    {
        return ['field' => 'created_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'description'];
    }

    protected function getColumns(): array
    {
        return [
            'name' => [
                'label' => 'Campaign',
                'sortable' => true,
                'filterable' => false,
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => MarketingCampaign::getStatuses(),
            ],
            'created_at' => [
                'label' => 'Created',
                'sortable' => true,
                'type' => 'date',
            ],
        ];
    }

    protected function getStats(): array
    {
        $baseQuery = MarketingCampaign::where('company_id', $this->companyId);
        
        return [
            ['label' => 'Total', 'value' => $baseQuery->count(), 'icon' => 'megaphone', 'iconBg' => 'bg-blue-500'],
            ['label' => 'Active', 'value' => $baseQuery->clone()->where('status', 'active')->count(), 'icon' => 'play', 'iconBg' => 'bg-green-500'],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'title' => 'No Campaigns',
            'message' => 'Create your first campaign to get started.',
            'icon' => 'megaphone',
            'action' => ['label' => 'Create Campaign', 'href' => route('marketing.campaigns.create')],
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return MarketingCampaign::where('company_id', $this->companyId)
            ->with(['createdBy']);
    }

    protected function applyCustomFilters($query)
    {
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        return $query;
    }

    protected function getRowActions($item)
    {
        return [
            ['label' => 'View', 'href' => route('marketing.campaigns.show', $item->id), 'icon' => 'eye'],
            ['label' => 'Edit', 'href' => route('marketing.campaigns.edit', $item->id), 'icon' => 'pencil'],
        ];
    }

    protected function getBulkActions()
    {
        return [];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'statusFilter' => ['except' => ''],
            'sortField' => ['except' => 'created_at'],
            'sortDirection' => ['except' => 'desc'],
        ];
    }
}
```

#### 2. Inherited Features (Automatic)

From BaseIndexComponent you automatically get:
- Search (via `$search` property)
- Sorting (via `$sortField`, `$sortDirection`)
- Pagination (via `$perPage`)
- Bulk actions (via `$selected[]`, `$selectAll`)
- Company scoping (automatic)
- Archive filtering (automatic if `archived_at` column exists)
- Client context (automatic from NavigationService)

#### 3. NO Custom Blade View Needed

**DO NOT create a custom blade view** for your Livewire component. The component automatically uses `livewire.base-index.blade.php` which provides:
- Stats cards display
- Filter controls
- Table/Cards view toggle
- Empty states
- Pagination

#### 4. Update Controller

```php
public function index(Request $request)
{
    if ($request->wantsJson()) {
        // API logic here
    }
    
    return view('marketing.campaigns.index-livewire');
}
```

#### 5. Create Wrapper View

Create `resources/views/marketing/campaigns/index-livewire.blade.php`:

```blade
@extends('layouts.app')

@php
$pageTitle = 'Marketing Campaigns';
$pageSubtitle = 'Create and manage email campaigns';
$pageActions = [
    ['label' => 'Create Campaign', 'href' => route('marketing.campaigns.create'), 'icon' => 'plus', 'variant' => 'primary']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('marketing.campaign-index')
    </div>
@endsection
```

#### Variable Names (CRITICAL)

**ALWAYS use these exact variable names from traits:**
- `$sortField` (NOT `$sortBy`)
- `$sortDirection` (NOT `$sortOrder`, `$sortDir`)
- `$search`
- `$perPage`
- `$selected` (for bulk actions)

#### DO NOT

- ❌ Create custom pagination logic
- ❌ Implement sorting manually
- ❌ Add `company_id` filters manually (automatic via BaseIndexComponent)
- ❌ Create custom search implementations
- ❌ Use different variable names than the traits provide
- ❌ Create custom blade views for the Livewire component (use base-index.blade.php)
- ❌ Override the `render()` method unless absolutely necessary

### Laravel Best Practices

1. **Model Conventions (UPDATED)**
   ```php
   // ALWAYS use BelongsToCompany trait
   class Client extends Model
   {
       use HasFactory, SoftDeletes, BelongsToCompany; // REQUIRED
       
       protected $fillable = ['company_id', 'name', 'email'];
       
       public function tickets()
       {
           return $this->hasMany(Ticket::class);
       }
   }
   ```

2. **Controller Structure (MODERNIZED)**
   ```php
   // Controllers extend BaseResourceController
   class ClientController extends BaseResourceController
   {
       use HasClientRelation;
       
       protected function initializeController(): void
       {
           $this->service = app(ClientService::class);
           $this->resourceName = 'client';
           $this->viewPath = 'clients';
           $this->routePrefix = 'clients';
       }
       
       protected function getModelClass(): string
       {
           return Client::class;
       }
       
       // Only custom methods needed - CRUD is inherited
   }
   ```

3. **Service Layer Pattern (ENHANCED)**
   ```php
   // Services extend domain-specific base services
   class ClientService extends ClientBaseService
   {
       protected function initializeService(): void
       {
           $this->modelClass = Client::class;
           $this->defaultEagerLoad = ['contacts', 'locations'];
           $this->searchableFields = ['name', 'company_name', 'email'];
       }
       
       // Custom business logic only
       public function generateClientReport(Client $client): array
       {
           // Business logic here
           return $this->buildReport($client);
       }
   }
   ```

### Frontend Standards

1. **Alpine.js Components**
   ```html
   <!-- Use Alpine.js for interactivity -->
   <div x-data="ticketForm()" x-init="init()">
       <input x-model="title" @input="validateTitle">
       <span x-show="errors.title" x-text="errors.title"></span>
   </div>
   ```

2. **Tailwind CSS Classes**
   ```html
   <!-- Use consistent spacing and colors -->
   <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
       Save Changes
   </button>
   ```

## Testing

### Running Tests

```bash
# Run all tests
composer run test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test tests/Feature/ClientTest.php
```

### Writing Tests

1. **Feature Tests (Base Controller Testing)**
   ```php
   class ClientTest extends TestCase
   {
       use RefreshDatabase;
       
       public function test_user_can_create_client()
       {
           $user = User::factory()->create();
           
           $response = $this->actingAs($user)
               ->post('/clients', [
                   'name' => 'Test Client',
                   'email' => 'test@example.com'
               ]);
           
           $response->assertRedirect();
           $this->assertDatabaseHas('clients', [
               'name' => 'Test Client',
               'company_id' => $user->company_id // Test multi-tenancy
           ]);
       }
       
       public function test_json_api_response_follows_standard()
       {
           $user = User::factory()->create();
           
           $response = $this->actingAs($user)
               ->postJson('/clients', [
                   'name' => 'Test Client',
                   'email' => 'test@example.com'
               ]);
           
           $response->assertStatus(201)
                   ->assertJsonStructure([
                       'message',
                       'data' => ['id', 'name', 'email']
                   ]);
       }
   }
   ```

2. **Unit Tests (Base Service Testing)**
   ```php
   class ClientServiceTest extends TestCase
   {
       use RefreshDatabase;
       
       public function test_creates_client_with_company_scoping()
       {
           $user = User::factory()->create();
           $this->actingAs($user);
           
           $service = app(ClientService::class);
           $data = ['name' => 'Test Client', 'email' => 'test@example.com'];
           
           $client = $service->create($data);
           
           $this->assertInstanceOf(Client::class, $client);
           $this->assertEquals('Test Client', $client->name);
           $this->assertEquals($user->company_id, $client->company_id);
       }
       
       public function test_filters_by_company_automatically()
       {
           $user1 = User::factory()->create();
           $user2 = User::factory()->create();
           
           $client1 = Client::factory()->create(['company_id' => $user1->company_id]);
           $client2 = Client::factory()->create(['company_id' => $user2->company_id]);
           
           $this->actingAs($user1);
           $service = app(ClientService::class);
           $results = $service->getAll();
           
           $this->assertCount(1, $results);
           $this->assertEquals($client1->id, $results->first()->id);
       }
   }
   ```

3. **Request Validation Testing**
   ```php
   class StoreClientRequestTest extends TestCase
   {
       use RefreshDatabase;
       
       public function test_requires_client_from_same_company()
       {
           $user = User::factory()->create();
           $otherCompanyClient = Client::factory()->create(); // Different company
           
           $response = $this->actingAs($user)
               ->postJson('/resources', [
                   'client_id' => $otherCompanyClient->id,
                   'name' => 'Test Resource'
               ]);
           
           $response->assertStatus(422)
                   ->assertJsonValidationErrors(['client_id']);
       }
   }
   ```

### Test Database

Use a separate test database:

```env
# In .env.testing
DB_DATABASE=nestogy_test
```

## Debugging

### Laravel Debugbar

Laravel Debugbar is enabled in development:

- View database queries
- Check route information
- Monitor performance
- Inspect variables

### Logging

```php
// Use Laravel's logging
Log::info('User created client', ['client_id' => $client->id]);
Log::warning('Invalid file upload attempt');
Log::error('Database connection failed', ['error' => $e->getMessage()]);
```

### Ray Debugging

Ray is available for advanced debugging:

```php
// Debug variables
ray($user, $client);

// Monitor queries
ray()->showQueries();

// Measure performance
ray()->measure();
```

### Tinker Console

Use Tinker for interactive debugging:

```bash
php artisan tinker

# Test models
>>> $client = Client::first()
>>> $client->tickets

# Test services
>>> $service = app(ClientService::class)
>>> $service->create(['name' => 'Test'])
```

## Database Development

### Migrations

```bash
# Create migration
php artisan make:migration create_clients_table

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Reset database
php artisan migrate:fresh --seed
```

### Seeders

```php
// Create seeder
class ClientSeeder extends Seeder
{
    public function run()
    {
        Client::factory(50)->create();
    }
}
```

### Factories

```php
// Define model factories
class ClientFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
```

## Frontend Development

### Asset Compilation

```bash
# Watch for changes
npm run dev

# Build for production
npm run build

# Build with source maps
npm run build -- --sourcemap
```

### Adding Dependencies

```bash
# Add NPM package
npm install package-name

# Add development dependency
npm install --save-dev package-name
```

### Alpine.js Development

```javascript
// Create reusable components
document.addEventListener('alpine:init', () => {
    Alpine.data('ticketForm', () => ({
        title: '',
        description: '',
        
        init() {
            // Component initialization
        },
        
        submit() {
            // Form submission logic
        }
    }));
});
```

## API Development

### Creating API Routes

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('clients', ClientApiController::class);
});
```

### API Controllers

```php
class ClientApiController extends Controller
{
    public function index()
    {
        return ClientResource::collection(
            Client::paginate(20)
        );
    }
    
    public function store(CreateClientRequest $request)
    {
        $client = $this->clientService->create($request->validated());
        
        return new ClientResource($client);
    }
}
```

### API Resources

```php
class ClientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
        ];
    }
}
```

## Performance Testing

### Query Optimization

```bash
# Monitor queries with Debugbar
# Check N+1 query problems
# Use eager loading where appropriate

# Example: Optimize with eager loading
$clients = Client::with('tickets')->get();
```

### Profiling

```php
// Use Laravel's built-in profiler
Profiler::start('expensive-operation');
// ... expensive code
Profiler::end('expensive-operation');
```

### Load Testing

```bash
# Use Apache Bench for simple load testing
ab -n 1000 -c 10 http://localhost:8000/

# Use Laravel's built-in benchmarking
php artisan benchmark:run
```

## Troubleshooting

### Common Issues

1. **Composer dependencies**
   ```bash
   composer install
   composer dump-autoload
   ```

2. **NPM issues**
   ```bash
   rm -rf node_modules
   npm install
   ```

3. **Permission issues**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

4. **Cache issues**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

### Development Tools

- **Laravel Telescope** - Application monitoring
- **Laravel Debugbar** - Debug information
- **Laravel Ray** - Advanced debugging
- **PHP Storm** - IDE with Laravel plugin
- **Postman** - API testing

### Getting Help

- **Laravel Documentation**: https://laravel.com/docs
- **PHP Documentation**: https://php.net/manual
- **Stack Overflow**: Tag questions with `laravel`, `php`, `nestogy`
- **Team Chat**: Use project Slack/Discord
- **Code Reviews**: Ask for help in pull requests

---

**Version**: 2.0.0 | **Last Updated**: November 2025 | **Platform**: Laravel 12.36 + PHP 8.4 + Modern Architecture