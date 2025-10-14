# PostgreSQL Test Database Fixes

## Problem

CI tests were failing with PostgreSQL-specific errors:

1. **Type Constraint Violations**: `SQLSTATE[23505]: Unique violation: duplicate key value violates unique constraint "pg_type_typname_nsp_index"`
2. **Missing Tables**: Migrations failing because database state was inconsistent  
3. **Exit Code 2 Errors**: Tests exiting with errors before running

## Root Cause

Laravel's `RefreshDatabase` trait, when using PostgreSQL, can leave behind custom types (enums, composite types) after dropping tables. When tests run in separate processes (as our `run-tests.php` does), each process tries to recreate these types, causing constraint violations.

Additionally, stale configuration/route caches can cause tests to hang or behave unexpectedly.

## Solution

### 1. Database Reset in run-tests.php

Modified `/opt/nestogy/run-tests.php` to reset the PostgreSQL test database before running tests:

```php
private function resetTestDatabase(): void
{
    // Terminates active connections
    // Drops database IF EXISTS
    // Creates fresh database
}
```

This ensures:
- No leftover custom types from previous runs
- Clean database state for all tests
- Proper isolation between CI runs

### 2. CI Workflow Update

Updated `.github/workflows/ci.yml` to explicitly reset database before migrations:

```yaml
- name: Reset test database
  run: |
    PGPASSWORD=nestogy_dev_pass psql -h 127.0.0.1 -U nestogy -d postgres -c "DROP DATABASE IF EXISTS nestogy_test;"
    PGPASSWORD=nestogy_dev_pass psql -h 127.0.0.1 -U nestogy -d postgres -c "CREATE DATABASE nestogy_test OWNER nestogy;"
```

### 3. Connection Termination

Added logic to terminate existing connections before dropping database to prevent "database is being accessed by other users" errors.

## Testing

To verify the fix locally:

```bash
# Reset database and run tests
php run-tests.php

# Run specific test file
php artisan test tests/Feature/ClientControllerTest.php

# Run with coverage (slower)
php run-tests.php --coverage
```

## Notes

- The fix is PostgreSQL-specific and won't affect MySQL/SQLite environments
- Database reset happens once at the start of the test suite, not between individual test files
- Each test file still uses `RefreshDatabase` trait for isolation within the file
- Duplicate test files exist but are in different namespaces (Feature vs Unit)

##Important: Cache Clearing

If tests hang or behave unexpectedly after making changes, clear Laravel caches:

```bash
php artisan cache:clear
php artisan config:clear  
php artisan route:clear
```

## Files Modified

1. `/opt/nestogy/run-tests.php` - Added `resetTestDatabase()` method
2. `/opt/nestogy/.github/workflows/ci.yml` - Added database reset step

## Success Criteria

After applying these fixes:
- ✅ No more "duplicate key value violates unique constraint pg_type_typname_nsp_index" errors
- ✅ Tests run successfully with clean database state
- ✅ CI pipeline completes without database-related failures
- ⚠️  Some individual test assertions may still fail (these are application logic issues, not database issues)
