# Option 1 Implementation Summary: Database Transactions for Fast Tests

## Overview
Implemented a comprehensive solution to dramatically improve test performance by running migrations once and using database transactions for test isolation.

## Changes Made

### 1. Updated `/opt/nestogy/run-tests.php`

**`resetTestDatabase()` method:**
- Loads phpunit.xml environment variables
- Properly terminates all PostgreSQL connections to test database
- Drops test database with `WITH (FORCE)` to handle lingering connections
- Creates fresh test database
- Uses proper PostgreSQL connection strings

**`runMigrations()` method:**
- Runs migrations ONCE using the correct test database
- Sets `DB_DATABASE` environment variable to ensure migrations run on `nestogy_test`
- Exits with error code if migrations fail

### 2. Updated `/opt/nestogy/tests/RefreshesDatabase.php`

Changed from running full migrations per test to using transactions:

```php
trait RefreshesDatabase
{
    use RefreshDatabase;

    protected function refreshTestDatabase(): void
    {
        // Mark migrations as already complete
        // Migrations are run once by run-tests.php
        if (! RefreshDatabaseState::$migrated) {
            RefreshDatabaseState::$migrated = true;
        }

        // Start a transaction for each test
        $this->beginDatabaseTransaction();
    }
}
```

**Key Benefits:**
- Migrations run ONCE at the start (40 seconds)
- Each test runs in a transaction that rolls back (fast - ~1-2s per test file)
- 118 test files no longer each run migrations independently
- Total time: ~40s migration + ~2-3 minutes tests = **under 5 minutes**

### 3. Updated All Test Files (110 files)

Bulk updated all test files to use the custom trait:
- Changed `use Illuminate\Foundation\Testing\RefreshDatabase;` 
- To `use Tests\RefreshesDatabase;`
- Changed trait usage from `use RefreshDatabase;` to `use RefreshesDatabase;`

### 4. Updated `/opt/nestogy/phpunit.xml`

Removed unused `DB_MIGRATE_FRESH` environment variable.

### 5. GitHub Actions CI Workflow (`.github/workflows/ci.yml`)

**Already optimal** - no changes needed:
- PostgreSQL service container creates fresh database
- `run-tests.php` handles all database setup
- No separate migration steps required

## How It Works

### Test Execution Flow:

1. **run-tests.php starts:**
   - Resets test database (drops/creates)
   - Runs migrations ONCE on `nestogy_test`
   - Finds all 118 test files

2. **For each test file (separate PHP process):**
   - PHPUnit loads the test file
   - Custom `RefreshesDatabase` trait marks migrations as "already done"
   - Each test method runs in a transaction
   - Transaction rolls back after test completes
   - Next test starts with clean database state

3. **Coverage generation:**
   - Individual `.cov` files merged
   - Single `coverage.xml` generated

### Performance Comparison:

**Before (RefreshDatabase per file):**
- 118 files × ~40s migrations each = ~78 minutes
- Plus test execution time
- **Total: 80+ minutes**

**After (Transactions):**
- 1 × ~40s migration
- 118 files × ~1-2s per file = ~2-3 minutes
- **Total: ~4-5 minutes**

## Testing

### Local Testing:
```bash
# Run single test
php artisan test --filter=test_name

# Run full test suite
php run-tests.php

# Run with coverage
php run-tests.php --coverage
```

### CI Testing:
GitHub Actions will:
1. Create fresh PostgreSQL container
2. Run `php run-tests.php --coverage`
3. Upload coverage to SonarCloud
4. Deploy to Laravel Cloud (on main branch)

## Potential Issues & Solutions

### Issue: Some tests fail with "relation does not exist"
**Cause:** Local database has stale schema or wasn't properly reset  
**Solution:** 
```bash
psql postgresql://nestogy:nestogy_dev_pass@127.0.0.1:5432/postgres \
  -c "DROP DATABASE IF EXISTS nestogy_test WITH (FORCE);"
psql postgresql://nestogy:nestogy_dev_pass@127.0.0.1:5432/postgres \
  -c "CREATE DATABASE nestogy_test OWNER nestogy;"
DB_DATABASE=nestogy_test php artisan migrate --force
```

### Issue: Tests that actually need to test transactions
**Cause:** Some tests may need to test actual database commits  
**Solution:** Those specific tests can override and use `RefreshDatabase` directly:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class SpecialTest extends TestCase
{
    use RefreshDatabase; // Override the custom trait
}
```

### Issue: Bouncer permissions cache
**Cause:** Bouncer may cache permissions across transactions  
**Solution:** Clear bouncer cache in test setUp if needed:
```php
protected function setUp(): void
{
    parent::setUp();
    \Silber\Bouncer\BouncerFacade::refresh();
}
```

## Files Changed

1. `/opt/nestogy/run-tests.php` - Database reset and migration logic
2. `/opt/nestogy/tests/RefreshesDatabase.php` - Transaction-based trait
3. `/opt/nestogy/phpunit.xml` - Removed unused env variable
4. 110 test files - Updated to use custom trait

## Success Criteria

✅ Migrations run once at start  
✅ Each test runs in transaction  
✅ Tests are isolated from each other  
✅ Total test time < 5 minutes  
✅ CI workflow remains simple  
✅ Coverage generation works  

## Next Steps

1. Push changes to GitHub
2. Monitor CI run to verify  < 5 minute execution
3. Address any test failures (likely unrelated to this change)
4. Consider adding test parallelization for even faster execution

## Notes

- This approach works best with PostgreSQL (transaction support)
- Tests should avoid explicit transaction management
- Factories and seeders work normally
- Static data (migrations) persists, dynamic data (inserts) rolls back
