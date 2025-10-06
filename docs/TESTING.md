# Nestogy MSP Platform - Testing Guide

This guide covers testing practices, methodologies, and tools used in the Nestogy MSP Platform (Laravel 12) to ensure code quality, reliability, and maintainability.

## Table of Contents

1. [Testing Philosophy](#testing-philosophy)
2. [Test Organization](#test-organization)
3. [Running Tests](#running-tests)
4. [Test Types](#test-types)
5. [Writing Tests](#writing-tests)
6. [Database Testing](#database-testing)
7. [Multi-Tenancy Testing](#multi-tenancy-testing)
8. [Financial Testing](#financial-testing)
9. [Test Coverage](#test-coverage)
10. [CI/CD Integration](#cicd-integration)

---

## Testing Philosophy

### Our Testing Approach

The Nestogy MSP Platform follows these testing principles:

- **Domain-Driven Testing**: Tests organized by business domain
- **Multi-Tenancy Required**: ALL tests verify proper company scoping and data isolation
- **Test-Driven Development (TDD)**: Write tests before implementing features when possible
- **Comprehensive Coverage**: Target 85%+ overall coverage, 99%+ on critical financial paths
- **Fast Feedback**: Tests run quickly and provide immediate feedback
- **Security-First Testing**: Every test verifies multi-tenant security constraints

### Testing Pyramid

```
     /\
    /  \  Browser Tests (Few) - 5%
   /____\
  /      \  Integration Tests (Some) - 15%
 /________\
/          \  Feature Tests (Medium) - 30%
/____________\
 Unit Tests (Many) - 50%
```

- **Unit Tests (50%)**: Fast, isolated tests of models, services, and helpers
- **Feature Tests (30%)**: HTTP/controller tests with database
- **Integration Tests (15%)**: External service integrations (RMM, Email, etc.)
- **Browser Tests (5%)**: E2E tests of critical user flows

---

## Test Organization

### Directory Structure

```
tests/
├── Unit/                      # Unit tests (isolated, fast)
│   ├── Models/               # Model tests by domain
│   │   ├── Asset/
│   │   ├── Client/
│   │   ├── Contract/
│   │   ├── Financial/
│   │   ├── Knowledge/
│   │   ├── Lead/
│   │   ├── Marketing/
│   │   ├── Product/
│   │   ├── Project/
│   │   ├── Security/
│   │   └── Ticket/
│   ├── Services/            # Service layer tests
│   │   ├── Core/
│   │   ├── Email/
│   │   ├── Financial/
│   │   ├── Client/
│   │   └── ...
│   ├── Policies/            # Authorization policy tests
│   ├── Observers/           # Model observer tests
│   ├── Rules/               # Validation rule tests
│   ├── Helpers/             # Helper function tests
│   └── Traits/              # Trait tests
├── Feature/                  # Feature/HTTP tests
│   ├── Api/                 # API endpoint tests
│   ├── Controllers/         # HTTP controller tests
│   ├── Livewire/           # Livewire component tests
│   └── Domains/            # Domain feature tests
├── Integration/             # Integration tests
│   ├── Financial/          # Financial accuracy suite
│   │   ├── CalculationAccuracy/
│   │   ├── TaxCompliance/
│   │   └── AuditTrail/
│   ├── RMM/                # RMM integrations
│   ├── Email/              # Email system integration
│   ├── PhysicalMail/       # Physical mail integration
│   └── Webhooks/           # Webhook handling
├── Browser/                 # Browser/E2E tests
│   ├── Auth/
│   ├── Dashboard/
│   └── CriticalFlows/
├── Performance/            # Load/stress tests
├── Support/                # Test support files
│   ├── Factories/
│   ├── Fixtures/
│   └── Helpers/
├── TestCase.php           # Base test case
├── CreatesApplication.php
└── DomainModelTestCase.php # Domain test base classes
```

### Base Test Classes

#### DomainModelTestCase
```php
use Tests\Unit\Models\DomainModelTestCase;

class ClientTest extends DomainModelTestCase
{
    // Automatically provides:
    // - $this->testCompany
    // - $this->testUser
    // - assertBelongsToCompany()
    // - assertSoftDeletable()
    // - assertHasFillableAttributes()
    // - assertHidesAttributes()
}
```

#### DomainServiceTestCase
```php
use Tests\Unit\Services\DomainServiceTestCase;

class ClientServiceTest extends DomainServiceTestCase
{
    // Automatically provides:
    // - $this->testCompany
    // - $this->testUser
    // - actAsUser()
    // - assertServiceReturnsModel()
    // - assertServiceReturnsCollection()
}
```

#### DomainFeatureTestCase
```php
use Tests\Feature\DomainFeatureTestCase;

class ClientControllerTest extends DomainFeatureTestCase
{
    // Automatically provides:
    // - $this->testCompany
    // - $this->testUser
    // - $this->adminUser
    // - actAsUser()
    // - actAsAdmin()
    // - assertCrossTenantProtection()
}
```

#### IntegrationTestCase
```php
use Tests\Integration\IntegrationTestCase;

class RMMIntegrationTest extends IntegrationTestCase
{
    // Automatically provides:
    // - fakeHttpRequests()
    // - assertHttpRequestSent()
    // - mockExternalService()
}
```

---

## Running Tests

### Quick Start

```bash
# Run all tests (using custom runner - memory efficient)
php run-tests.php

# Run all tests with coverage
php run-tests.php --coverage

# Traditional commands (may run out of memory on full suite)
composer test                # Run all tests
composer test:unit          # Unit tests only
composer test:feature       # Feature tests only
composer test:integration   # Integration tests only
composer test:financial     # Financial accuracy tests
composer test:quick         # Fast unit tests (models + services)

# Generate coverage report
composer test:coverage      # Opens coverage/html/index.html
```

### Custom Test Runner (Recommended)

**Why?** PHPUnit doesn't free memory between test files. With 1200+ tests, this causes memory exhaustion.

**Solution:** Our custom runner (`run-tests.php`) runs each test file in isolation.

```bash
# Run without coverage (faster)
php run-tests.php

# Run with coverage (generates coverage.xml)
php run-tests.php --coverage

# With custom memory limit
php -d memory_limit=1G run-tests.php
```

**Features:**
- ✅ Runs each test file in isolation
- ✅ Frees memory between test files  
- ✅ Generates merged coverage reports
- ✅ Works in CI/CD (GitHub Actions, CircleCI)
- ✅ Provides detailed progress tracking
- ✅ Uses PCOV (faster than Xdebug)

### PHPUnit Commands

```bash
# Run all tests (WARNING: may run out of memory)
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Feature
./vendor/bin/phpunit --testsuite=Financial

# Run specific test file
./vendor/bin/phpunit tests/Unit/Models/Client/ClientTest.php

# Run specific test method
./vendor/bin/phpunit --filter testClientBelongsToCompany

# Run with coverage (single file only recommended)
./vendor/bin/phpunit --coverage-html coverage/html tests/Unit/Models/AccountTest.php
```

### Financial Accuracy Test Runner

```bash
# Run all financial accuracy tests
php tests/Integration/Financial/run-financial-accuracy-tests.php

# Run specific suite
php tests/Integration/Financial/run-financial-accuracy-tests.php --suite=invoice

# With coverage
php tests/Integration/Financial/run-financial-accuracy-tests.php --coverage

# Verbose output
php tests/Integration/Financial/run-financial-accuracy-tests.php --verbose
```

---

## Test Types

### 1. Unit Tests

**Purpose**: Test individual classes/methods in isolation

**Location**: `tests/Unit/`

**Example**:
```php
namespace Tests\Unit\Models\Client;

use Tests\Unit\Models\DomainModelTestCase;
use App\Domains\Client\Models\Client;

class ClientTest extends DomainModelTestCase
{
    public function it_belongs_to_company()
    {
        $client = Client::factory()->create([
            'company_id' => $this->testCompany->id
        ]);

        $this->assertBelongsToCompany($client);
    }

    public function it_validates_email_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Client::create([
            'company_id' => $this->testCompany->id,
            'email' => 'invalid-email'
        ]);
    }
}
```

### 2. Feature Tests

**Purpose**: Test HTTP endpoints and user interactions

**Location**: `tests/Feature/`

**Example**:
```php
namespace Tests\Feature\Controllers;

use Tests\Feature\DomainFeatureTestCase;
use App\Domains\Client\Models\Client;

class ClientControllerTest extends DomainFeatureTestCase
{
    public function authenticated_user_can_view_clients()
    {
        $this->actAsUser();
        
        $response = $this->get('/clients');
        
        $response->assertOk();
        $response->assertViewIs('clients.index');
    }

    public function user_cannot_view_other_company_clients()
    {
        $otherClient = Client::factory()->create([
            'company_id' => $this->createSecondaryCompany()->id
        ]);

        $this->actAsUser();
        
        $response = $this->get("/clients/{$otherClient->id}");
        
        $response->assertForbidden();
    }
}
```

### 3. Integration Tests

**Purpose**: Test external service integrations

**Location**: `tests/Integration/`

**Example**:
```php
namespace Tests\Integration\RMM;

use Tests\Integration\IntegrationTestCase;

class RMMWebhookTest extends IntegrationTestCase
{
    public function it_processes_rmm_webhook_successfully()
    {
        $payload = [
            'event' => 'device.created',
            'data' => ['device_id' => 123]
        ];

        $response = $this->postJson('/api/webhooks/rmm', $payload);

        $response->assertOk();
        $this->assertDatabaseHas('assets', ['rmm_id' => 123]);
    }
}
```

### 4. Financial Accuracy Tests

**Purpose**: Ensure critical financial calculations are accurate

**Location**: `tests/Integration/Financial/CalculationAccuracy/`

**Critical**: These tests MUST pass at 100% - they protect revenue accuracy

**Example**:
```php
namespace Tests\Integration\Financial\CalculationAccuracy;

use Tests\Integration\Financial\FinancialTestCase;

class InvoiceCalculationTest extends FinancialTestCase
{
    public function it_calculates_invoice_total_with_tax_correctly()
    {
        $invoice = $this->createInvoice([
            'subtotal' => 1000.00,
            'tax_rate' => 0.0825
        ]);

        $this->assertEquals(1082.50, $invoice->total);
        $this->assertMoneyEquals(1082.50, $invoice->total);
    }
}
```

---

## Writing Tests

### Test Naming Conventions

```php
// Model tests - describe the behavior
public function it_belongs_to_company()
public function it_calculates_total_correctly()
public function it_soft_deletes_related_records()

// Feature tests - describe user actions
public function authenticated_user_can_create_invoice()
public function admin_can_delete_client()
public function guest_cannot_access_dashboard()
```

### Test Structure (AAA Pattern)

```php
public function it_calculates_tax_correctly()
{
    // Arrange - Set up test data
    $invoice = Invoice::factory()->create([
        'subtotal' => 1000.00,
        'tax_rate' => 0.0825
    ]);
    
    // Act - Execute the behavior
    $tax = $invoice->calculateTax();
    
    // Assert - Verify the result
    $this->assertEquals(82.50, $tax);
}
```

### Assertion Best Practices

```php
// Be specific
$this->assertEquals(1000.00, $invoice->total);  // Good
$this->assertTrue($invoice->total > 0);         // Too vague

// Test behavior, not implementation
$this->assertTrue($user->can('create', Invoice::class));  // Good
$this->assertTrue($user->role === 'admin');               // Implementation detail

// Use appropriate assertions
$this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
$this->assertDatabaseMissing('invoices', ['id' => $deleted->id]);
$this->assertSoftDeleted('invoices', ['id' => $softDeleted->id]);
```

---

## Database Testing

### Using Factories

```php
// Create single model
$client = Client::factory()->create();

// Create with specific attributes
$client = Client::factory()->create([
    'company_id' => $this->testCompany->id,
    'status' => 'active'
]);

// Create multiple
$clients = Client::factory()->count(5)->create();

// Use states
$activeClient = Client::factory()->active()->create();
$suspendedClient = Client::factory()->suspended()->create();
```

### Database Assertions

```php
// Assert record exists
$this->assertDatabaseHas('clients', [
    'id' => $client->id,
    'company_id' => $this->testCompany->id
]);

// Assert record doesn't exist
$this->assertDatabaseMissing('clients', [
    'id' => $deletedClient->id
]);

// Assert soft deleted
$this->assertSoftDeleted('clients', [
    'id' => $client->id
]);

// Count records
$this->assertDatabaseCount('clients', 5);
```

---

## Multi-Tenancy Testing

### Always Test Company Scoping

Every test MUST verify multi-tenant isolation:

```php
public function user_cannot_access_other_company_data()
{
    // Create data in different company
    $otherCompany = $this->createSecondaryCompany();
    $otherClient = Client::factory()->create([
        'company_id' => $otherCompany->id
    ]);

    // Try to access as user from testCompany
    $this->actAsUser();
    $response = $this->get("/clients/{$otherClient->id}");

    // Should be forbidden
    $response->assertForbidden();
}
```

### Use Base Test Helper

```php
// Automatically tests cross-tenant protection
$this->assertCrossTenantProtection(
    $client,
    "/clients/{$client->id}",
    'GET'
);
```

---

## Financial Testing

### Critical Financial Tests

Financial accuracy tests are **CRITICAL** and must maintain 99%+ coverage:

```php
// Invoice calculations
public function it_calculates_invoice_total_accurately()
public function it_applies_tax_correctly()
public function it_handles_discounts_properly()

// Payment processing
public function it_allocates_payments_correctly()
public function it_maintains_balance_accuracy()
public function it_prevents_overpayment()

// Recurring billing
public function it_prorates_billing_correctly()
public function it_handles_subscription_changes()
```

### Money Precision

Always use money assertions for financial tests:

```php
// Use assertMoneyEquals for currency comparisons
$this->assertMoneyEquals(1082.50, $invoice->total);

// NOT this (floating point errors)
$this->assertEquals(1082.50, $invoice->total);
```

---

## Test Coverage

### Coverage Goals

| Category | Target | Current |
|----------|--------|---------|
| **Overall** | 85% | Improving |
| **Models** | 95% | 46% |
| **Services** | 80% | 5% |
| **Controllers** | 85% | 15% |
| **Financial** | 99% | 95% |

### Generate Coverage Report

```bash
composer test:coverage
```

Opens `coverage/html/index.html` in your browser.

### Coverage in CI/CD

```yaml
# .github/workflows/ci.yml
- name: Setup PHP with PCOV
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.4'
    coverage: pcov
    extensions: pgsql, pdo_pgsql, redis
    ini-values: memory_limit=1G, pcov.enabled=1

- name: Run tests with coverage
  run: php run-tests.php --coverage

- name: Upload to SonarCloud
  uses: SonarSource/sonarcloud-github-action@master
  env:
    SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
```

**Note:** The custom test runner (`run-tests.php`) is used in CI/CD to prevent memory issues. It generates the same `coverage.xml` format compatible with SonarCloud, Codecov, and other tools.

---

## CI/CD Integration

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: mbstring, pdo, pdo_sqlite
          coverage: xdebug
      
      - name: Install Dependencies
        run: composer install
      
      - name: Run Unit Tests
        run: composer test:unit
      
      - name: Run Feature Tests
        run: composer test:feature
      
      - name: Run Financial Tests (Critical)
        run: composer test:financial
```

---

## Best Practices

### Do's ✅

- ✅ Write tests before implementing features (TDD)
- ✅ Test multi-tenant isolation in EVERY test
- ✅ Use factories for test data
- ✅ Keep tests fast and focused
- ✅ Use descriptive test names
- ✅ Test edge cases and error conditions
- ✅ Maintain 99%+ coverage on financial code

### Don'ts ❌

- ❌ Don't test framework functionality
- ❌ Don't write flaky/random tests
- ❌ Don't skip multi-tenancy verification
- ❌ Don't use production data in tests
- ❌ Don't commit failing tests
- ❌ Don't skip financial accuracy tests

---

## Troubleshooting

### Tests Fail Locally

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear

# Rebuild database
php artisan migrate:fresh

# Run tests
composer test
```

### SQLite Issues

If using SQLite for tests:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### Slow Tests

```bash
# Run in parallel
composer test:parallel

# Run only unit tests (faster)
composer test:quick
```

---

## Resources

### Internal Documentation
- `/docs/TESTING_IMPROVEMENT_PLAN.md` - Comprehensive improvement roadmap
- `/tests/Support/` - Test helpers and fixtures
- `/tests/Unit/Models/DomainModelTestCase.php` - Base model test class
- `/tests/Integration/Financial/` - Financial test examples

### External Resources
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing Docs](https://laravel.com/docs/testing)
- [Test-Driven Development](https://martinfowler.com/bliki/TestDrivenDevelopment.html)

---

## Getting Help

If you have questions about testing:

1. Check this documentation first
2. Review existing tests in `tests/` for examples
3. Check `/docs/TESTING_IMPROVEMENT_PLAN.md` for detailed guidance
4. Ask the development team

---

**Remember**: Tests are not optional - they protect revenue, data integrity, and user security. Write tests for every feature!