# Testing Improvement Plan

## Executive Summary

Current state: 47 test files covering 101 models (~46% model coverage)
Target state: Comprehensive test coverage across all domains with organized test structure

---

## Current Testing Gaps Analysis

### 1. Model Coverage Gap
**Current**: 47 test files for 101 models (46.5% coverage)
**Issue**: Critical business domains lack model tests

#### Models with No Tests
| Domain | Model Count | Tested | Coverage |
|--------|-------------|--------|----------|
| Knowledge | 5 | 0 | 0% |
| Lead | 4 | 0 | 0% |
| Marketing | 4 | 0 | 0% |
| Security | 8 | 1 | 12.5% |
| Asset | 6 | 0 | 0% |
| Client | 5 | 1 | 20% |
| Financial | 12 | 6 | 50% |
| Product | 8 | 0 | 0% |
| Project | 6 | 0 | 0% |
| Ticket | 10 | 0 | 0% |
| Integration | 7 | 1 | 14% |
| Contract | 5 | 2 | 40% |

### 2. Service Layer Coverage Gap
**Issue**: Only 5 service tests exist despite 100+ services across domains

#### Services with No Tests
- Core Domain: 30+ services (0 tests)
- Email Domain: 10 services (0 tests)
- PhysicalMail Domain: 8 services (0 tests)
- Knowledge Domain: 4 services (0 tests)
- Lead Domain: 3 services (0 tests)
- Marketing Domain: 3 services (0 tests)
- Security Domain: 5 services (0 tests)

### 3. Test Organization Issues

#### Problems Identified
1. **Mixed test types**: Unit and feature tests not clearly separated by purpose
2. **Orphaned test scripts**: `run-financial-accuracy-tests.php` outside PHPUnit structure
3. **Missing test suites**: No dedicated suites for domains
4. **No integration tests**: Limited API/integration testing
5. **No browser tests**: No Dusk/E2E tests for critical user flows

---

## Recommended Testing Structure

### Phase 1: Reorganize Existing Tests (Week 1)

```
tests/
├── Unit/
│   ├── Models/              # Pure model tests
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
│   │   ├── PhysicalMail/
│   │   └── Integration/
│   ├── Policies/            # Authorization policy tests
│   ├── Observers/           # Model observer tests
│   ├── Rules/               # Validation rule tests
│   ├── Helpers/             # Helper function tests
│   └── Traits/              # Trait tests (existing)
├── Feature/
│   ├── Api/                 # API endpoint tests
│   ├── Controllers/         # HTTP controller tests
│   ├── Livewire/            # Livewire component tests
│   ├── Auth/                # Authentication flows
│   ├── Domains/             # Domain feature tests
│   └── Integration/         # External integrations
├── Integration/
│   ├── Financial/           # Financial accuracy suite
│   │   ├── CalculationAccuracy/
│   │   ├── TaxCompliance/
│   │   └── AuditTrail/
│   ├── RMM/                 # RMM integrations
│   ├── Email/               # Email system integration
│   ├── PhysicalMail/        # Physical mail integration
│   └── Webhooks/            # Webhook handling
├── Browser/                 # Dusk tests (new)
│   ├── Auth/
│   ├── Dashboard/
│   └── CriticalFlows/
├── Performance/             # Load/stress tests
└── Support/                 # Test helpers/fixtures
    ├── Factories/
    ├── Fixtures/
    └── Helpers/
```

### Phase 2: Priority Test Creation (Weeks 2-4)

#### Critical Path Tests (Week 2)
**Priority**: P0 - Must have before production

1. **Financial Domain Tests**
   - [ ] Invoice calculation tests (COMPLETE)
   - [ ] Tax calculation tests (COMPLETE)
   - [ ] Payment processing tests (COMPLETE)
   - [ ] Recurring billing tests (COMPLETE)
   - [ ] Revenue recognition tests (NEW)
   - [ ] Refund processing tests (NEW)

2. **Security Domain Tests**
   - [ ] Authentication tests
   - [ ] Authorization tests (PARTIAL)
   - [ ] Input sanitization tests (COMPLETE)
   - [ ] CSRF protection tests (NEW)
   - [ ] Rate limiting tests (NEW)
   - [ ] Encryption tests (NEW)

3. **Multi-Tenancy Tests**
   - [ ] Company isolation tests
   - [ ] Data scoping tests
   - [ ] Cross-tenant prevention tests
   - [ ] Subscription management tests

#### High-Value Domain Tests (Week 3)
**Priority**: P1 - High business value

4. **Client Domain Tests**
   ```php
   # Models to test:
   - Client
   - ClientContact
   - ClientAddress
   - ClientNote
   - ClientDocument
   
   # Key behaviors:
   - Relationship integrity
   - Soft deletion cascade
   - Search functionality
   - Status transitions
   ```

5. **Ticket Domain Tests**
   ```php
   # Models to test:
   - Ticket
   - TicketComment
   - TicketAttachment
   - TicketPriority
   - TicketStatus
   
   # Key behaviors:
   - SLA calculations
   - Status workflows
   - Assignment rules
   - Email integration
   ```

6. **Asset Domain Tests**
   ```php
   # Models to test:
   - Asset
   - AssetType
   - AssetNetwork
   - AssetMaintenance
   
   # Key behaviors:
   - Depreciation calculations
   - Maintenance scheduling
   - Network discovery
   - RMM integration
   ```

#### Essential Domain Tests (Week 4)
**Priority**: P2 - Essential for quality

7. **Contract Domain Tests**
   - Contract model tests (PARTIAL)
   - Contract service tests (COMPLETE)
   - Contract generation tests (COMPLETE)
   - Billing cycle tests
   - Term renewal tests

8. **Product Domain Tests**
   - Product model tests
   - Bundle tests
   - Pricing rule tests
   - Inventory tests
   - Tax category tests

9. **Project Domain Tests**
   - Project model tests
   - Task tracking tests
   - Time tracking tests
   - Billing integration tests
   - Status workflow tests

### Phase 3: Service Layer Tests (Weeks 5-6)

#### Core Services (Week 5)
```php
tests/Unit/Services/Core/
├── DashboardDataServiceTest.php
├── DashboardCacheServiceTest.php
├── NavigationServiceTest.php
├── CommandPaletteServiceTest.php
├── NotificationServiceTest.php
├── EmailServiceTest.php
└── SettingsServiceTest.php
```

#### Domain Services (Week 6)
```php
tests/Unit/Services/Domains/
├── Client/
│   └── ClientServiceTest.php (EXISTS)
├── Financial/
│   ├── InvoiceServiceTest.php
│   ├── PaymentServiceTest.php
│   └── RecurringBillingServiceTest.php
├── Email/
│   ├── UnifiedEmailSyncServiceTest.php
│   └── ImapServiceTest.php
└── PhysicalMail/
    ├── PhysicalMailServiceTest.php
    └── PhysicalMailTemplateServiceTest.php
```

### Phase 4: Integration & E2E Tests (Weeks 7-8)

#### API Integration Tests (Week 7)
```php
tests/Integration/Api/
├── ClientApiTest.php
├── TicketApiTest.php
├── InvoiceApiTest.php
├── AssetApiTest.php
└── WebhookApiTest.php
```

#### Browser Tests (Week 8)
```php
tests/Browser/
├── Auth/
│   ├── LoginTest.php
│   └── RegistrationTest.php
├── Dashboard/
│   ├── DashboardLoadTest.php
│   └── LazyLoadingTest.php
└── CriticalFlows/
    ├── CreateInvoiceTest.php
    ├── ProcessPaymentTest.php
    └── CreateTicketTest.php
```

---

## Test Suite Configuration

### Updated phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    
    <testsuites>
        <!-- Unit Tests: Fast, isolated, no external dependencies -->
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        
        <!-- Feature Tests: Application-level tests with database -->
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        
        <!-- Integration Tests: External service integrations -->
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
        
        <!-- Financial Accuracy: Critical financial tests -->
        <testsuite name="Financial">
            <directory suffix="Test.php">./tests/Integration/Financial</directory>
        </testsuite>
        
        <!-- Performance Tests: Load and stress tests -->
        <testsuite name="Performance">
            <directory suffix="Test.php">./tests/Performance</directory>
        </testsuite>
        
        <!-- Quick Suite: Fast tests for CI/CD -->
        <testsuite name="Quick">
            <directory suffix="Test.php">./tests/Unit/Models</directory>
            <directory suffix="Test.php">./tests/Unit/Services</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <report>
            <clover outputFile="coverage/clover.xml"/>
            <html outputDirectory="coverage/html" lowUpperBound="50" highLowerBound="80"/>
            <text outputFile="php://stdout" showUncoveredFiles="false"/>
        </report>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory suffix=".php">./app/Console</directory>
            <directory suffix=".php">./app/Exceptions</directory>
            <directory suffix=".php">./app/Http/Middleware</directory>
        </exclude>
    </coverage>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

### Composer Scripts

Add to `composer.json`:

```json
"scripts": {
    "test": "phpunit",
    "test:unit": "phpunit --testsuite=Unit",
    "test:feature": "phpunit --testsuite=Feature",
    "test:integration": "phpunit --testsuite=Integration",
    "test:financial": "phpunit --testsuite=Financial",
    "test:quick": "phpunit --testsuite=Quick",
    "test:coverage": "phpunit --coverage-html coverage/html",
    "test:parallel": "php artisan test --parallel"
}
```

---

## Testing Standards & Best Practices

### 1. Test Naming Convention

```php
// Model tests - use descriptive method names
public function it_calculates_invoice_total_correctly()
public function it_validates_email_format()
public function it_belongs_to_company()

// Feature tests - use user story format
public function user_can_create_invoice()
public function admin_can_delete_client()
public function authenticated_user_can_view_dashboard()
```

### 2. Test Structure (AAA Pattern)

```php
public function it_calculates_tax_correctly()
{
    // Arrange
    $invoice = Invoice::factory()->create([
        'subtotal' => 1000.00,
        'tax_rate' => 0.0825
    ]);
    
    // Act
    $tax = $invoice->calculateTax();
    
    // Assert
    $this->assertEquals(82.50, $tax);
}
```

### 3. Test Data Management

```php
// Use factories for test data
$client = Client::factory()->create();

// Use states for variations
$activeClient = Client::factory()->active()->create();
$suspendedClient = Client::factory()->suspended()->create();

// Use sequences for multiple records
$clients = Client::factory()->count(5)->create();
```

### 4. Assertion Guidelines

```php
// Be specific
$this->assertEquals(1000.00, $invoice->total); // Good
$this->assertTrue($invoice->total > 0);        // Too vague

// Test behavior, not implementation
$this->assertTrue($user->can('create', Invoice::class));  // Good
$this->assertTrue($user->role === 'admin');               // Implementation detail

// Use appropriate assertions
$this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
$this->assertDatabaseMissing('invoices', ['id' => $deletedInvoice->id]);
$this->assertSoftDeleted('invoices', ['id' => $softDeletedInvoice->id]);
```

### 5. Test Independence

```php
// Each test should be independent
public function setUp(): void
{
    parent::setUp();
    
    // Fresh database state for each test
    $this->artisan('migrate:fresh');
    
    // Common test data setup
    $this->testCompany = Company::factory()->create();
    $this->testUser = User::factory()->create([
        'company_id' => $this->testCompany->id
    ]);
}

// Clean up after each test if needed
public function tearDown(): void
{
    // Reset any static state
    Cache::flush();
    
    parent::tearDown();
}
```

---

## Coverage Goals

### Minimum Coverage Targets

| Category | Current | Phase 1 | Phase 2 | Phase 3 | Final Goal |
|----------|---------|---------|---------|---------|------------|
| **Overall** | ~20% | 40% | 60% | 75% | 85% |
| **Models** | 46% | 70% | 85% | 90% | 95% |
| **Services** | 5% | 30% | 50% | 70% | 80% |
| **Controllers** | 15% | 40% | 60% | 75% | 85% |
| **Critical Path** | 60% | 90% | 95% | 98% | 99% |

### Critical Path Definition
Must maintain 99% coverage:
- Financial calculations
- Tax calculations
- Payment processing
- Authentication/Authorization
- Multi-tenancy isolation
- Data encryption

---

## Tools & Infrastructure

### Required Testing Tools

1. **PHPUnit** (installed) - Core testing framework
2. **Laravel Dusk** (needed) - Browser testing
3. **Pest** (optional) - Modern test syntax
4. **PHPStan** (recommended) - Static analysis
5. **Mockery** (installed) - Mocking framework
6. **Faker** (installed) - Test data generation

### CI/CD Integration

```yaml
# .github/workflows/tests.yml
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
        run: composer install --prefer-dist --no-interaction
      
      - name: Run Unit Tests
        run: composer test:unit
      
      - name: Run Feature Tests
        run: composer test:feature
      
      - name: Run Financial Accuracy Tests
        run: composer test:financial
      
      - name: Generate Coverage Report
        run: composer test:coverage
      
      - name: Upload Coverage
        uses: codecov/codecov-action@v2
        with:
          files: ./coverage/clover.xml
```

---

## Migration Strategy for Existing Tests

### Step 1: Move Financial Tests

```bash
# Move financial accuracy tests to Integration suite
mkdir -p tests/Integration/Financial
mv tests/Unit/Financial/CalculationAccuracy tests/Integration/Financial/
mv tests/run-financial-accuracy-tests.php tests/Integration/Financial/
```

### Step 2: Refactor Test Runner

Convert standalone script to Artisan command:

```php
// app/Console/Commands/TestFinancialAccuracy.php
php artisan make:command TestFinancialAccuracy
```

### Step 3: Update Documentation

```bash
# Update TESTING.md
# Update README.md with new test commands
# Update CI/CD configuration
```

---

## Immediate Action Items

### Week 1 Tasks
- [ ] Reorganize test directory structure
- [ ] Move financial tests to Integration suite
- [ ] Update phpunit.xml with new test suites
- [ ] Add composer test scripts
- [ ] Create base test classes for each domain

### Week 2 Tasks
- [ ] Create model tests for Knowledge domain (5 tests)
- [ ] Create model tests for Lead domain (4 tests)
- [ ] Create model tests for Marketing domain (4 tests)
- [ ] Create model tests for Security domain (7 tests)
- [ ] Create service tests for Core domain (10 tests)

### Week 3 Tasks
- [ ] Create model tests for Client domain (4 tests)
- [ ] Create model tests for Ticket domain (10 tests)
- [ ] Create model tests for Asset domain (6 tests)
- [ ] Create service tests for Email domain (5 tests)

### Week 4 Tasks
- [ ] Create model tests for Product domain (8 tests)
- [ ] Create model tests for Project domain (6 tests)
- [ ] Create integration tests for RMM (3 tests)
- [ ] Create integration tests for Email (3 tests)

---

## Test Template Examples

### Model Test Template

```php
<?php

namespace Tests\Unit\Models\Knowledge;

use App\Domains\Knowledge\Models\Article;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $testCompany;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testCompany = Company::factory()->create();
        $this->testUser = User::factory()->create([
            'company_id' => $this->testCompany->id
        ]);
    }

    public function it_belongs_to_company()
    {
        $article = Article::factory()->create([
            'company_id' => $this->testCompany->id
        ]);

        $this->assertInstanceOf(Company::class, $article->company);
        $this->assertEquals($this->testCompany->id, $article->company_id);
    }

    public function it_belongs_to_author()
    {
        $article = Article::factory()->create([
            'author_id' => $this->testUser->id
        ]);

        $this->assertInstanceOf(User::class, $article->author);
        $this->assertEquals($this->testUser->id, $article->author_id);
    }

    public function it_has_required_fillable_attributes()
    {
        $fillable = (new Article())->getFillable();
        
        $expectedAttributes = [
            'company_id',
            'author_id',
            'title',
            'content',
            'status',
            'published_at'
        ];

        foreach ($expectedAttributes as $attribute) {
            $this->assertContains(
                $attribute,
                $fillable,
                "Attribute {$attribute} should be fillable"
            );
        }
    }

    public function it_can_be_published()
    {
        $article = Article::factory()->draft()->create();

        $article->publish();

        $this->assertEquals('published', $article->status);
        $this->assertNotNull($article->published_at);
    }

    public function it_scopes_to_published_articles()
    {
        Article::factory()->published()->count(3)->create([
            'company_id' => $this->testCompany->id
        ]);
        
        Article::factory()->draft()->count(2)->create([
            'company_id' => $this->testCompany->id
        ]);

        $publishedCount = Article::published()->count();

        $this->assertEquals(3, $publishedCount);
    }
}
```

### Service Test Template

```php
<?php

namespace Tests\Unit\Services\Knowledge;

use App\Domains\Knowledge\Models\Article;
use App\Domains\Knowledge\Services\ArticleService;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $testCompany;
    protected User $testUser;
    protected ArticleService $articleService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testCompany = Company::factory()->create();
        $this->testUser = User::factory()->create([
            'company_id' => $this->testCompany->id
        ]);
        
        $this->articleService = app(ArticleService::class);
    }

    public function it_creates_article_with_valid_data()
    {
        $data = [
            'title' => 'Test Article',
            'content' => 'Test content',
            'author_id' => $this->testUser->id,
        ];

        $article = $this->articleService->create($data, $this->testCompany);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('Test Article', $article->title);
        $this->assertEquals($this->testCompany->id, $article->company_id);
    }

    public function it_publishes_article_successfully()
    {
        $article = Article::factory()->draft()->create([
            'company_id' => $this->testCompany->id
        ]);

        $result = $this->articleService->publish($article);

        $this->assertTrue($result);
        $this->assertEquals('published', $article->fresh()->status);
    }

    public function it_searches_articles_by_keyword()
    {
        Article::factory()->create([
            'company_id' => $this->testCompany->id,
            'title' => 'Laravel Testing'
        ]);
        
        Article::factory()->create([
            'company_id' => $this->testCompany->id,
            'title' => 'PHP Development'
        ]);

        $results = $this->articleService->search('Laravel', $this->testCompany);

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Testing', $results->first()->title);
    }
}
```

### Feature Test Template

```php
<?php

namespace Tests\Feature\Knowledge;

use App\Domains\Knowledge\Models\Article;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $testCompany;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testCompany = Company::factory()->create();
        $this->testUser = User::factory()->create([
            'company_id' => $this->testCompany->id
        ]);
    }

    public function authenticated_user_can_view_articles_index()
    {
        $this->actingAs($this->testUser);

        $response = $this->get('/knowledge/articles');

        $response->assertStatus(200);
        $response->assertViewIs('knowledge.articles.index');
    }

    public function authenticated_user_can_create_article()
    {
        $this->actingAs($this->testUser);

        $articleData = [
            'title' => 'New Article',
            'content' => 'Article content',
        ];

        $response = $this->post('/knowledge/articles', $articleData);

        $response->assertRedirect();
        $this->assertDatabaseHas('articles', [
            'title' => 'New Article',
            'company_id' => $this->testCompany->id
        ]);
    }

    public function user_cannot_edit_other_company_article()
    {
        $otherCompany = Company::factory()->create();
        $otherArticle = Article::factory()->create([
            'company_id' => $otherCompany->id
        ]);

        $this->actingAs($this->testUser);

        $response = $this->put("/knowledge/articles/{$otherArticle->id}", [
            'title' => 'Updated Title'
        ]);

        $response->assertStatus(403);
    }
}
```

---

## Success Metrics

### Quantitative Metrics
- **Test Count**: Increase from 47 to 300+ tests
- **Model Coverage**: Increase from 46% to 95%
- **Service Coverage**: Increase from 5% to 80%
- **Overall Coverage**: Increase from 20% to 85%
- **Test Execution Time**: Keep under 2 minutes for unit tests
- **CI/CD Success Rate**: Maintain >98%

### Qualitative Metrics
- All critical paths have >99% coverage
- All financial calculations have verification tests
- Zero production bugs in tested code paths
- Developers can run tests locally in <5 minutes
- New features require tests before merge
- Test documentation is clear and complete

---

## Resources & References

### Documentation
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing Docs](https://laravel.com/docs/testing)
- [Laravel Dusk Docs](https://laravel.com/docs/dusk)
- [Pest PHP Docs](https://pestphp.com/)

### Internal Resources
- `/docs/TESTING.md` - Current testing guide
- `/tests/TestCase.php` - Base test class
- `/tests/TestHelpers.php` - Test helper functions
- `/tests/Integration/Financial/` - Financial test examples

### Team Training
- Week 1: Testing fundamentals workshop
- Week 2: Model testing patterns
- Week 3: Service testing patterns
- Week 4: Integration testing strategies
- Week 5: TDD best practices

---

## Risk Assessment

### High Risk Areas (Require Immediate Testing)
1. Financial calculations - **CRITICAL**
2. Multi-tenancy isolation - **CRITICAL**
3. Authentication/Authorization - **HIGH**
4. Data encryption - **HIGH**
5. Payment processing - **CRITICAL**

### Medium Risk Areas
1. Email sending/receiving
2. RMM integrations
3. Report generation
4. Dashboard data loading
5. File uploads

### Low Risk Areas (Can defer)
1. UI components
2. Static pages
3. Email templates
4. PDF layouts
5. Notification formatting

---

## Conclusion

This comprehensive testing improvement plan addresses all identified gaps:

1. **Low test coverage**: Structured plan to increase from 46% to 95% model coverage
2. **Missing domain tests**: Specific tests planned for Knowledge, Lead, Marketing, Security
3. **Test organization**: Clear directory structure with proper test suite separation
4. **Missing integration tests**: Dedicated integration test suite with financial accuracy focus
5. **Orphaned scripts**: Migration plan to integrate financial test runner into standard framework

**Timeline**: 8 weeks to achieve 85% overall coverage
**Resources**: 2-3 developers working part-time on testing
**Investment**: ~320 developer hours
**ROI**: Reduced production bugs, faster feature development, increased confidence