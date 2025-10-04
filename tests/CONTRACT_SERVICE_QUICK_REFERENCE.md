# ContractService Tests - Quick Reference

## 📋 Test Files at a Glance

| File | Purpose | Test Count | Key Areas |
|------|---------|------------|-----------|
| `ContractServiceTest.php` | Core functionality | ~30 | CRUD, State, Templates, Filters |
| `ContractServiceScheduleTest.php` | Schedule management | ~15 | Schedules A/B/C, Telecom, Hardware |
| `ContractServiceAssetTest.php` | Asset operations | ~15 | Assignment, Pricing, Isolation |
| `ContractServiceWorkflowTest.php` | End-to-end workflows | ~15 | Lifecycle, Renewals, Specialized |
| `ContractServiceIntegrationTest.php` | System integration | ~20 | Services, DB, Concurrency |

## 🚀 Quick Commands

```bash
# Run all tests
php artisan test --filter=ContractService

# Run specific file
php artisan test tests/Unit/Services/ContractServiceTest.php

# Run one test
php artisan test --filter=test_creates_contract_with_complete_data

# With coverage
php artisan test --filter=ContractService --coverage

# Parallel execution
php artisan test --filter=ContractService --parallel
```

## 🎯 Common Test Patterns

### Create Contract
```php
$data = [
    'client_id' => $this->client->id,
    'title' => 'Test Contract',
    'start_date' => now()->format('Y-m-d'),
];
$contract = $this->service->createContract($data);
```

### With Schedules
```php
$data = [
    // ... basic data
    'infrastructure_schedule' => [...],
    'pricing_schedule' => [...],
    'additional_terms' => [...],
];
```

### With Asset Assignment
```php
$data = [
    // ... basic data
    'sla_terms' => [
        'auto_assign_new_assets' => true,
        'supported_asset_types' => ['server', 'workstation'],
    ],
];
```

## 📊 Test Coverage Map

### CRUD Operations
- ✅ `test_creates_contract_with_minimum_required_data()`
- ✅ `test_creates_contract_with_complete_data()`
- ✅ `test_updates_contract_in_draft_status()`
- ✅ `test_deletes_draft_contract()`

### State Transitions
- ✅ `test_activates_signed_contract()`
- ✅ `test_suspends_active_contract()`
- ✅ `test_terminates_active_contract()`
- ✅ `test_reactivates_suspended_contract()`

### Schedules
- ✅ `test_creates_schedule_a_infrastructure()`
- ✅ `test_creates_schedule_b_pricing()`
- ✅ `test_creates_schedule_c_additional_terms()`
- ✅ `test_creates_telecom_schedule()`
- ✅ `test_creates_hardware_schedule()`
- ✅ `test_creates_compliance_schedule()`

### Asset Management
- ✅ `test_assigns_assets_by_type()`
- ✅ `test_updates_contract_value_with_asset_pricing()`
- ✅ `test_sets_asset_support_metadata()`

### Integration
- ✅ `test_integrates_with_template_variable_mapper()`
- ✅ `test_handles_database_transactions_correctly()`
- ✅ `test_concurrent_contract_creation_handles_race_conditions()`

## 🔧 Setup in Each Test

```php
protected function setUp(): void
{
    parent::setUp();
    
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    
    $this->actingAs($this->user);
    $this->service = app(ContractService::class);
}
```

## 🛡️ Mocking Config Registry

```php
protected function mockConfigRegistry(): void
{
    $mock = \Mockery::mock(ContractConfigurationRegistry::class);
    $mock->shouldReceive('getContractStatuses')->andReturn([
        'draft' => 'Draft',
        'active' => 'Active',
        'signed' => 'Signed',
        'suspended' => 'Suspended',
        'terminated' => 'Terminated',
    ]);
    // ... more mocking
    $this->app->instance(ContractConfigurationRegistry::class, $mock);
}
```

## 📈 Test Statistics

| Category | Count |
|----------|-------|
| **Total Tests** | ~95 |
| **Unit Tests** | ~60 |
| **Feature Tests** | ~15 |
| **Integration Tests** | ~20 |
| **Test Files** | 5 |
| **Lines of Code** | ~2,500+ |

## 🔍 Finding Tests

### By Functionality
```bash
# Contract creation
grep -r "test_creates" tests/

# State transitions
grep -r "test_.*contract" tests/ | grep -E "(activate|suspend|terminate)"

# Asset assignment
grep -r "test_.*asset" tests/

# Schedule creation
grep -r "test_.*schedule" tests/
```

### By Test Type
```bash
# Unit tests
ls tests/Unit/Services/ContractService*.php

# Feature tests
ls tests/Feature/Services/ContractService*.php
```

## 🐛 Debugging Failed Tests

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Run with Verbose Output
```bash
php artisan test --filter=test_name -v
```

### Database State
```bash
# Check test database
php artisan db:show --database=testing
```

## 📚 Related Documentation

- **Full Documentation**: `/tests/CONTRACT_SERVICE_TEST_DOCUMENTATION.md`
- **Summary**: `/tests/CONTRACT_SERVICE_TEST_SUMMARY.md`
- **This File**: `/tests/CONTRACT_SERVICE_QUICK_REFERENCE.md`

## 🎓 Key Assertions Reference

```php
// Instance checks
$this->assertInstanceOf(Contract::class, $contract);

// Value checks
$this->assertEquals('expected', $actual);
$this->assertCount(5, $collection);

// String checks
$this->assertStringContainsString('text', $content);

// Database checks
$this->assertDatabaseHas('contracts', ['id' => $id]);
$this->assertSoftDeleted('contracts', ['id' => $id]);

// Array checks
$this->assertArrayHasKey('key', $array);
$this->assertNotEmpty($array);

// Boolean checks
$this->assertTrue($condition);
$this->assertNull($value);
```

## ⚡ Performance Tips

1. **Run in parallel**: Use `--parallel` flag
2. **Filter tests**: Use specific filters to run subsets
3. **Database**: Ensure test DB is optimized
4. **Mocking**: Mock external services for speed

## 🔄 CI/CD Integration

```yaml
# Example GitHub Actions
- name: Run ContractService Tests
  run: php artisan test --filter=ContractService --coverage
  
- name: Upload Coverage
  uses: codecov/codecov-action@v3
```

## 📝 Quick Test Template

```php
public function test_your_scenario(): void
{
    // Arrange
    $data = [/* test data */];
    
    // Act
    $result = $this->service->method($data);
    
    // Assert
    $this->assertEquals('expected', $result->field);
}
```
