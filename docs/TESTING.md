# Nestogy MSP Platform - Testing Guide

This guide covers testing practices, methodologies, and tools used in the Nestogy MSP Platform to ensure code quality, reliability, and maintainability.

## Table of Contents

1. [Testing Philosophy](#testing-philosophy)
2. [Test Environment Setup](#test-environment-setup)
3. [Test Types](#test-types)
4. [Writing Effective Tests](#writing-effective-tests)
5. [Test Organization](#test-organization)
6. [Database Testing](#database-testing)
7. [API Testing](#api-testing)
8. [Frontend Testing](#frontend-testing)
9. [Performance Testing](#performance-testing)
10. [Security Testing](#security-testing)
11. [Continuous Integration](#continuous-integration)
12. [Test Coverage](#test-coverage)
13. [Troubleshooting](#troubleshooting)

## Testing Philosophy

### Our Testing Approach

The Nestogy MSP Platform follows these testing principles:

- **Test-Driven Development (TDD)**: Write tests before implementing features
- **Comprehensive Coverage**: Aim for 80%+ test coverage on critical paths
- **Fast Feedback**: Tests should run quickly and provide immediate feedback
- **Reliable Tests**: Tests should be deterministic and not flaky
- **Maintainable Tests**: Tests should be easy to read, update, and debug

### Testing Pyramid

We follow the testing pyramid approach:

```
    /\
   /  \  E2E Tests (Few)
  /____\
 /      \  Integration/Feature Tests (Some)
/__________\
Unit Tests (Many)
```

- **Unit Tests (70%)**: Fast, isolated tests of individual components
- **Feature Tests (20%)**: Test complete workflows and integrations
- **End-to-End Tests (10%)**: Full application testing through the UI

## Test Environment Setup

### Configuration

Create a dedicated test environment:

```bash
# Copy environment file for testing
cp .env .env.testing
```

Update `.env.testing`:

```env
APP_ENV=testing
APP_DEBUG=true

# Use in-memory SQLite for faster tests
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Use array driver for faster tests
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
SESSION_DRIVER=array

# Disable external services in tests
STRIPE_ENABLED=false
TWILIO_ENABLED=false
SLACK_ENABLED=false
```

### Test Database

For MySQL-based tests (when needed):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nestogy_test
DB_USERNAME=test_user
DB_PASSWORD=test_password
```

Create test database:

```sql
CREATE DATABASE nestogy_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'test_user'@'localhost' IDENTIFIED BY 'test_password';
GRANT ALL PRIVILEGES ON nestogy_test.* TO 'test_user'@'localhost';
FLUSH PRIVILEGES;
```

## Test Types

### 1. Unit Tests

Test individual classes and methods in isolation.

**Location**: `tests/Unit/`

**Example**: Testing a service class

```php
<?php

namespace Tests\Unit\Services;

use App\Services\ClientService;
use App\Models\Client;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected ClientService $clientService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->clientService = new ClientService();
    }
    
    public function test_creates_client_with_valid_data()
    {
        $data = [
            'name' => 'Acme Corporation',
            'email' => 'contact@acme.com',
            'phone' => '555-0123'
        ];
        
        $client = $this->clientService->create($data);
        
        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('Acme Corporation', $client->name);
        $this->assertEquals('contact@acme.com', $client->email);
        $this->assertDatabaseHas('clients', $data);
    }
    
    public function test_throws_exception_with_invalid_data()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->clientService->create([]);
    }
    
    public function test_formats_client_name_properly()
    {
        $data = [
            'name' => '  acme corporation  ',
            'email' => 'contact@acme.com'
        ];
        
        $client = $this->clientService->create($data);
        
        $this->assertEquals('Acme Corporation', $client->name);
    }
}
```

### 2. Feature Tests

Test complete application workflows through HTTP requests.

**Location**: `tests/Feature/`

**Example**: Testing client management

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Company;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Company $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->for($this->company)->create();
    }
    
    public function test_authenticated_user_can_view_clients_index()
    {
        Client::factory()->count(3)->for($this->company)->create();
        
        $response = $this->actingAs($this->user)
            ->get(route('clients.index'));
        
        $response->assertOk()
            ->assertViewIs('clients.index')
            ->assertViewHas('clients');
    }
    
    public function test_user_can_create_client()
    {
        $clientData = [
            'name' => 'New Client Corp',
            'email' => 'contact@newclient.com',
            'phone' => '555-0199',
            'address' => '123 Business St',
            'city' => 'Business City',
            'state' => 'BC',
            'zip' => '12345'
        ];
        
        $response = $this->actingAs($this->user)
            ->post(route('clients.store'), $clientData);
        
        $response->assertRedirect()
            ->assertSessionHas('success');
        
        $this->assertDatabaseHas('clients', [
            'name' => 'New Client Corp',
            'company_id' => $this->company->id
        ]);
    }
    
    public function test_user_cannot_create_client_with_invalid_data()
    {
        $response = $this->actingAs($this->user)
            ->post(route('clients.store'), [
                'name' => '', // Required field
                'email' => 'invalid-email' // Invalid format
            ]);
        
        $response->assertSessionHasErrors(['name', 'email']);
    }
    
    public function test_user_can_update_existing_client()
    {
        $client = Client::factory()->for($this->company)->create();
        
        $updateData = [
            'name' => 'Updated Client Name',
            'email' => $client->email,
            'phone' => '555-9999'
        ];
        
        $response = $this->actingAs($this->user)
            ->put(route('clients.update', $client), $updateData);
        
        $response->assertRedirect()
            ->assertSessionHas('success');
        
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Client Name',
            'phone' => '555-9999'
        ]);
    }
    
    public function test_user_cannot_access_other_company_clients()
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->for($otherCompany)->create();
        
        $response = $this->actingAs($this->user)
            ->get(route('clients.show', $otherClient));
        
        $response->assertForbidden();
    }
}
```

### 3. Browser Tests (Dusk)

Test the application through a real browser for complex UI interactions.

**Setup**:
```bash
php artisan dusk:install
```

**Example**: Testing ticket creation workflow

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Company;
use App\Models\Client;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TicketCreationTest extends DuskTestCase
{
    public function test_user_can_create_ticket_through_ui()
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        $client = Client::factory()->for($company)->create();
        
        $this->browse(function (Browser $browser) use ($user, $client) {
            $browser->loginAs($user)
                ->visit('/tickets/create')
                ->select('client_id', $client->id)
                ->type('title', 'Test ticket from browser')
                ->type('description', 'This is a test ticket created through browser testing')
                ->select('priority', 'high')
                ->click('@submit-button')
                ->assertPathIs('/tickets')
                ->assertSee('Ticket created successfully')
                ->assertSee('Test ticket from browser');
        });
    }
    
    public function test_ticket_form_validation_works()
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/tickets/create')
                ->click('@submit-button')
                ->assertSee('The title field is required')
                ->assertSee('The client field is required');
        });
    }
}
```

## Writing Effective Tests

### Test Structure (Arrange-Act-Assert)

```php
public function test_service_processes_data_correctly()
{
    // Arrange: Set up test data and conditions
    $user = User::factory()->create();
    $inputData = ['key' => 'value'];
    
    // Act: Execute the code under test
    $result = $this->service->processData($user, $inputData);
    
    // Assert: Verify the expected outcome
    $this->assertEquals('expected_value', $result);
    $this->assertDatabaseHas('table', ['column' => 'value']);
}
```

### Test Naming Conventions

Use descriptive test names that explain the scenario:

```php
// Good: Descriptive and clear
public function test_user_can_create_ticket_with_valid_data()
public function test_throws_exception_when_client_not_found()
public function test_sends_notification_when_ticket_status_changes()

// Bad: Vague and unclear
public function test_create()
public function test_exception()
public function test_notification()
```

### Data Providers

Use data providers for testing multiple scenarios:

```php
/**
 * @dataProvider validClientDataProvider
 */
public function test_creates_client_with_various_valid_data($data, $expectedName)
{
    $client = $this->clientService->create($data);
    
    $this->assertEquals($expectedName, $client->name);
}

public function validClientDataProvider()
{
    return [
        'standard name' => [
            ['name' => 'Acme Corp', 'email' => 'test@acme.com'],
            'Acme Corp'
        ],
        'name with spaces' => [
            ['name' => '  Acme Corp  ', 'email' => 'test@acme.com'],
            'Acme Corp'
        ],
        'lowercase name' => [
            ['name' => 'acme corp', 'email' => 'test@acme.com'],
            'Acme Corp'
        ],
    ];
}
```

## Test Organization

### Directory Structure

```
tests/
├── Browser/           # Dusk browser tests
├── Feature/           # HTTP feature tests
├── Unit/             # Unit tests
├── Fixtures/         # Test data files
├── Helpers/          # Test helper classes
└── TestCase.php      # Base test case
```

### Test Traits

Create reusable test functionality:

```php
<?php

namespace Tests\Traits;

use App\Models\Company;
use App\Models\User;

trait CreatesUsers
{
    protected function createUserWithCompany(array $userAttributes = [], array $companyAttributes = []): User
    {
        $company = Company::factory()->create($companyAttributes);
        return User::factory()->for($company)->create($userAttributes);
    }
    
    protected function createAdminUser(): User
    {
        return $this->createUserWithCompany(['role' => 'admin']);
    }
}
```

### Base Test Classes

Create specialized base classes for different test types:

```php
<?php

namespace Tests;

use Tests\Traits\CreatesUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase, CreatesUsers;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Common setup for feature tests
        $this->artisan('db:seed', ['--class' => 'TestSeeder']);
    }
}
```

## Database Testing

### Using Factories

Create comprehensive model factories:

```php
<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;
    
    public function definition()
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->company(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip' => $this->faker->postcode(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    public function withContacts(int $count = 2)
    {
        return $this->afterCreating(function (Client $client) use ($count) {
            Contact::factory($count)->for($client)->create();
        });
    }
    
    public function inactive()
    {
        return $this->state(['status' => 'inactive']);
    }
}
```

### Database Assertions

```php
// Test database state
$this->assertDatabaseHas('clients', ['name' => 'Acme Corp']);
$this->assertDatabaseMissing('clients', ['name' => 'Deleted Corp']);
$this->assertDatabaseCount('clients', 5);

// Test soft deletes
$this->assertSoftDeleted('clients', ['id' => $client->id]);

// Test model state
$this->assertTrue($client->is($expectedClient));
$this->assertEquals(3, $client->tickets()->count());
```

### Transaction Testing

```php
public function test_transaction_rollback_on_failure()
{
    $initialCount = Client::count();
    
    try {
        DB::transaction(function () {
            Client::factory()->create();
            throw new \Exception('Simulated error');
        });
    } catch (\Exception $e) {
        // Expected exception
    }
    
    $this->assertEquals($initialCount, Client::count());
}
```

## API Testing

### Testing API Endpoints

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Client;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }
    
    public function test_can_get_clients_list()
    {
        Client::factory()->count(3)->for($this->user->company)->create();
        
        $response = $this->getJson('/api/clients');
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'created_at']
                ],
                'meta' => ['current_page', 'total']
            ]);
    }
    
    public function test_can_create_client_via_api()
    {
        $clientData = [
            'name' => 'API Test Client',
            'email' => 'api@test.com',
            'phone' => '555-0123'
        ];
        
        $response = $this->postJson('/api/clients', $clientData);
        
        $response->assertCreated()
            ->assertJsonFragment(['name' => 'API Test Client']);
        
        $this->assertDatabaseHas('clients', $clientData);
    }
    
    public function test_validates_client_data()
    {
        $response = $this->postJson('/api/clients', [
            'name' => '', // Invalid
            'email' => 'invalid-email' // Invalid
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }
    
    public function test_returns_404_for_nonexistent_client()
    {
        $response = $this->getJson('/api/clients/99999');
        
        $response->assertNotFound();
    }
}
```

### API Rate Limiting Tests

```php
public function test_api_rate_limiting_works()
{
    // Make requests up to the limit
    for ($i = 0; $i < 60; $i++) {
        $response = $this->getJson('/api/clients');
        $response->assertOk();
    }
    
    // Next request should be rate limited
    $response = $this->getJson('/api/clients');
    $response->assertTooManyRequests();
}
```

## Frontend Testing

### Testing Alpine.js Components

Create JavaScript tests for complex frontend logic:

```javascript
// tests/js/components/ticket-form.test.js
import { createApp } from 'alpinejs';

describe('TicketForm Component', () => {
    let component;
    
    beforeEach(() => {
        document.body.innerHTML = `
            <div x-data="ticketForm()">
                <input x-model="title" data-testid="title-input">
                <button @click="submit()" data-testid="submit-button">Submit</button>
            </div>
        `;
        
        component = createApp().mount('[x-data]');
    });
    
    test('validates required fields', () => {
        const submitButton = screen.getByTestId('submit-button');
        fireEvent.click(submitButton);
        
        expect(component.$data.errors.title).toBeDefined();
    });
    
    test('submits form with valid data', async () => {
        const titleInput = screen.getByTestId('title-input');
        const submitButton = screen.getByTestId('submit-button');
        
        fireEvent.input(titleInput, { target: { value: 'Test ticket' } });
        fireEvent.click(submitButton);
        
        expect(mockSubmit).toHaveBeenCalledWith({
            title: 'Test ticket'
        });
    });
});
```

## Performance Testing

### Database Query Testing

```php
public function test_eager_loading_prevents_n_plus_one_queries()
{
    Client::factory()->count(10)->create();
    
    // Test without eager loading
    DB::enableQueryLog();
    $clients = Client::all();
    foreach ($clients as $client) {
        $client->tickets; // This would trigger N+1 queries
    }
    $queriesWithoutEager = count(DB::getQueryLog());
    DB::flushQueryLog();
    
    // Test with eager loading
    $clients = Client::with('tickets')->get();
    foreach ($clients as $client) {
        $client->tickets; // This should not trigger additional queries
    }
    $queriesWithEager = count(DB::getQueryLog());
    
    $this->assertLessThan($queriesWithoutEager, $queriesWithEager);
}
```

### Memory Usage Testing

```php
public function test_bulk_operations_memory_usage()
{
    $startMemory = memory_get_usage();
    
    // Perform bulk operation
    Client::factory()->count(1000)->create();
    
    $endMemory = memory_get_usage();
    $memoryUsed = $endMemory - $startMemory;
    
    // Assert reasonable memory usage (adjust threshold as needed)
    $this->assertLessThan(50 * 1024 * 1024, $memoryUsed); // 50MB
}
```

## Security Testing

### Authentication Tests

```php
public function test_unauthenticated_users_cannot_access_protected_routes()
{
    $response = $this->get('/clients');
    $response->assertRedirect('/login');
}

public function test_users_cannot_access_other_company_data()
{
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    
    $user1 = User::factory()->for($company1)->create();
    $client2 = Client::factory()->for($company2)->create();
    
    $response = $this->actingAs($user1)->get("/clients/{$client2->id}");
    $response->assertForbidden();
}
```

### Input Validation Tests

```php
public function test_prevents_xss_attacks()
{
    $maliciousInput = '<script>alert("XSS")</script>';
    
    $response = $this->actingAs($this->user)
        ->post('/clients', [
            'name' => $maliciousInput,
            'email' => 'test@example.com'
        ]);
    
    $client = Client::latest()->first();
    $this->assertNotContains('<script>', $client->name);
}

public function test_prevents_sql_injection()
{
    $maliciousInput = "'; DROP TABLE clients; --";
    
    $response = $this->actingAs($this->user)
        ->get('/clients?search=' . urlencode($maliciousInput));
    
    // Ensure tables still exist
    $this->assertDatabaseHas('clients', []);
}
```

## Continuous Integration

### GitHub Actions Configuration

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: nestogy_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite, pdo_mysql
        coverage: xdebug
    
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
    
    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.testing', '.env');"
    
    - name: Install dependencies
      run: |
        composer install --prefer-dist --no-progress
        npm ci
    
    - name: Generate key
      run: php artisan key:generate
    
    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache
    
    - name: Run tests
      run: php artisan test --coverage --min=80
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
```

## Test Coverage

### Running Coverage Reports

```bash
# Generate HTML coverage report
php artisan test --coverage-html coverage

# Generate text coverage report
php artisan test --coverage-text

# Generate coverage with minimum threshold
php artisan test --coverage --min=80

# Generate coverage for specific paths
php artisan test --coverage-text --path=app/Services
```

### Coverage Configuration

Configure coverage in `phpunit.xml`:

```xml
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <exclude>
        <directory>./app/Console/Commands</directory>
        <file>./app/Http/Middleware/TrustProxies.php</file>
    </exclude>
    <report>
        <html outputDirectory="coverage-html"/>
        <text outputFile="coverage.txt"/>
    </report>
</coverage>
```

## Troubleshooting

### Common Test Issues

1. **Database not resetting between tests**
   ```php
   // Add RefreshDatabase trait
   use Illuminate\Foundation\Testing\RefreshDatabase;
   
   class MyTest extends TestCase
   {
       use RefreshDatabase;
   }
   ```

2. **Time-dependent tests failing**
   ```php
   // Use Carbon for time manipulation
   Carbon::setTestNow('2024-01-01 12:00:00');
   
   // Or use the travel helper
   $this->travel(5)->minutes();
   ```

3. **External API calls in tests**
   ```php
   // Use HTTP fake
   Http::fake([
       'api.example.com/*' => Http::response(['status' => 'ok'], 200)
   ]);
   ```

4. **File system operations**
   ```php
   // Use Storage fake
   Storage::fake('local');
   
   // Test file uploads
   $file = UploadedFile::fake()->image('test.jpg');
   ```

### Debugging Failed Tests

```bash
# Run specific test with verbose output
php artisan test tests/Feature/ClientTest.php --verbose

# Stop on first failure
php artisan test --stop-on-failure

# Run tests in specific order
php artisan test --order-by=defects

# Display test output
php artisan test --display-errors --display-warnings
```

### Performance Issues

```bash
# Run tests in parallel
php artisan test --parallel

# Use faster drivers for tests
# In .env.testing:
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

---

**Version**: 1.0.0 | **Last Updated**: January 2024 | **Platform**: Laravel 11 + PHP 8.2+