# Nestogy MSP Platform - Development Guide

This guide helps developers set up a local development environment for the Nestogy MSP Platform and covers development workflows, coding standards, and best practices.

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Development Workflow](#development-workflow)
3. [Coding Standards](#coding-standards)
4. [Testing](#testing)
5. [Debugging](#debugging)
6. [Database Development](#database-development)
7. [Frontend Development](#frontend-development)
8. [API Development](#api-development)
9. [Performance Testing](#performance-testing)
10. [Troubleshooting](#troubleshooting)

## Development Environment Setup

### Prerequisites

- **PHP** 8.2+ with required extensions
- **Composer** 2.0+
- **Node.js** 18.0+ & npm
- **MySQL** 8.0+ or **MariaDB** 10.5+
- **Redis** (optional, for caching and queues)
- **Git** for version control

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
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nestogy_dev
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   # Use database for sessions in development
   SESSION_DRIVER=database
   CACHE_DRIVER=database
   QUEUE_CONNECTION=database
   
   # Mail settings for local development
   MAIL_MAILER=log
   MAIL_HOST=localhost
   MAIL_PORT=1025
   ```

5. **Database setup**
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

# Asset compilation (watch mode)
npm run dev

# Queue worker
php artisan queue:work

# Logs viewer
php artisan pail --timeout=0
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

### Laravel Best Practices

1. **Model Conventions**
   ```php
   // Use proper relationships
   class Client extends Model
   {
       protected $fillable = ['name', 'email'];
       
       public function tickets()
       {
           return $this->hasMany(Ticket::class);
       }
   }
   ```

2. **Controller Structure**
   ```php
   // Keep controllers thin
   class ClientController extends Controller
   {
       public function store(CreateClientRequest $request)
       {
           $client = $this->clientService->create($request->validated());
           
           return redirect()
               ->route('clients.show', $client)
               ->with('success', 'Client created successfully');
       }
   }
   ```

3. **Service Layer Pattern**
   ```php
   // Business logic in services
   class ClientService
   {
       public function create(array $data): Client
       {
           // Business logic here
           return Client::create($data);
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

1. **Feature Tests**
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
           $this->assertDatabaseHas('clients', ['name' => 'Test Client']);
       }
   }
   ```

2. **Unit Tests**
   ```php
   class ClientServiceTest extends TestCase
   {
       public function test_creates_client_with_valid_data()
       {
           $service = new ClientService();
           $data = ['name' => 'Test Client', 'email' => 'test@example.com'];
           
           $client = $service->create($data);
           
           $this->assertInstanceOf(Client::class, $client);
           $this->assertEquals('Test Client', $client->name);
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

**Version**: 1.0.0 | **Last Updated**: January 2024 | **Platform**: Laravel 11 + PHP 8.2+