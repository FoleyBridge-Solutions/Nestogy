# PLAN: Complete Test Suite Fixes

## Current Status

**✅ Completed:**
- Custom test runner with coverage merging (`run-tests.php`)
- CI/CD configuration updated (`.github/workflows/ci.yml`)
- All client web routes added
- Authorization fixes (Bouncer permissions in tests)
- Controller unit test reflection fixes
- CSV content-type assertions fixed
- Route naming conflicts resolved

**❌ Blocking Issues:**
1. **Migration ordering** - Tests fail during `RefreshDatabase` because foreign keys reference tables that don't exist yet
2. **Invalid controller references** - `TaxEngineController` doesn't exist in routes

---

## Priority 1: Fix Migration Ordering (CRITICAL - Blocks All Tests)

### Problem
Migration `2024_01_01_000002_create_permissions_system.php` line 50 creates `user_roles` table with foreign key to `users` table, but this runs BEFORE migration `2024_01_01_000001_create_all_tables_v1.php` finishes creating the users table, causing:
```
ERROR: relation "users" does not exist
```

### Root Cause
When `RefreshDatabase` runs `migrate:fresh`, it drops ALL tables then runs migrations in filename order. The permission system migration (000002) tries to reference users table during the same transaction as the main migration (000001).

### Solution Options

**Option A: Merge Migrations (RECOMMENDED)**
- Move the `user_roles` table creation from `000002` INTO `000001` 
- Place it AFTER the users table creation (after line 60)
- Keep role/permission tables in 000002 but remove the user_roles table

**Option B: Rename Migration**
- Rename `000002_create_permissions_system.php` to `000001_b_create_permissions_system.php`
- Ensures it runs after 000001 completes

**Option C: Remove Foreign Key Constraint**
- Change `$table->foreign('user_id')` to just `$table->unsignedBigInteger('user_id')->index()`
- Add foreign key in a later migration after all base tables exist

### Implementation Steps (Option A - Recommended)

1. **Edit `2024_01_01_000001_create_all_tables_v1.php`:**
   - After line 60 (users table creation), add user_roles table creation
   - Copy the user_roles schema from 000002 migration

2. **Edit `2024_01_01_000002_create_permissions_system.php`:**
   - Remove the user_roles table creation (lines 47-58)
   - Keep permissions, roles, role_permissions, permission_groups tables

3. **Test:**
   ```bash
   php artisan migrate:fresh --env=testing
   php -d memory_limit=1G vendor/bin/phpunit tests/Unit/Models/AccountTest.php
   ```

---

## Priority 2: Fix Invalid Controller References

### Problem
Routes reference non-existent controllers:
- `App\Http\Controllers\Api\TaxEngineController` (14 routes)
- Possibly others discovered via `php artisan route:list`

### Solution

**Find all invalid controllers:**
```bash
grep -r "Http\\\\Controllers" routes/ | grep -v "Domains" | sort -u
```

**For each invalid controller:**

1. **TaxEngineController** - Check if it should be:
   - `App\Domains\Financial\Controllers\Api\TaxEngineController`
   - `App\Domains\Financial\Controllers\TaxCalculationController`
   - Or if routes should be removed entirely

2. **ServiceTaxController** - Already fixed to `App\Domains\Financial\Controllers\Api\ServiceTaxController`

3. **Verify all route controller namespaces:**
   ```bash
   php artisan route:list --columns=uri,action 2>&1 | grep "does not exist"
   ```

---

## Priority 3: Fix Remaining Test Failures

### Based on Test Output Analysis

From `/tmp/test_output.txt`, there were **73 total failures** across:

**A. ClientController Tests (Feature) - 23 failures**
- ✅ Fixed: Authorization (403 errors) - Now using `Bouncer::allow()->everything()`
- ✅ Fixed: Missing routes - Added complete client resource routes
- ⚠️ Remaining: CSV export content validation
- ⚠️ Remaining: Session-based client selection edge cases

**B. DashboardController Tests (Feature) - 16 failures**
- ✅ Fixed: Added `dashboard.stats` route
- ⚠️ Remaining: DashboardController needs `getData()` method implementation

**C. ClientController Tests (Unit) - 5 failures**
- ✅ Fixed: All reflection-based initialization tests

**D. Other Controllers** - 29 failures
- Need individual analysis after migration issues resolved

### Steps to Fix Remaining Failures

1. **Add `getData()` method to DashboardController:**
   ```php
   public function getData(Request $request)
   {
       $type = $request->input('type');
       // Return appropriate data based on type
   }
   ```

2. **Fix CSV Export Issues:**
   - Check `ClientController::exportCsv()` actually generates CSV content
   - Verify file headers are set correctly

3. **Fix Client Selection Logic:**
   - Review session handling in `selectClient()` and `clearSelection()` methods
   - Ensure proper validation of client IDs

---

## Priority 4: Test Database Performance Optimization

### Current Issue
Tests using `RefreshDatabase` are slow because they drop and recreate ALL tables for every test class.

### Solution: Use Database Transactions

**Create a custom RefreshDatabase trait:**

```php
// tests/RefreshesDatabase.php
trait RefreshesDatabase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;
    
    protected function refreshTestDatabase()
    {
        if (!static::$dbInitialized) {
            $this->artisan('migrate:fresh');
            static::$dbInitialized = true;
        }
        
        $this->beginDatabaseTransaction();
    }
}
```

This runs migrations once per test run instead of once per test class, speeding up tests by 10-20x.

---

## Priority 5: Fix PHPUnit Configuration Warning

### Issue
```
Cannot bootstrap extension because class Illuminate\Testing\ParallelTesting\ParallelTestingExtension does not exist
```

### Solution
**Remove the extension from `phpunit.xml`:**
```xml
<!-- DELETE these lines: -->
<extensions>
    <bootstrap class="Illuminate\Testing\ParallelTesting\ParallelTestingExtension"/>
</extensions>
```

This extension doesn't exist in Laravel 11/PHPUnit 11 - it's causing warnings.

---

## Priority 6: Verify Custom Test Runner with Coverage

### Test Coverage Generation

**Run with coverage:**
```bash
php run-tests.php --coverage
```

**Expected output:**
- Creates individual `.cov` files in `storage/coverage/`
- Merges them into `coverage.xml`
- Clean up temporary files
- Report should be ~7-8MB

**Verify coverage:**
```bash
ls -lh coverage.xml
grep -c "<line" coverage.xml  # Should show thousands of lines covered
```

---

## Priority 7: CI/CD Integration Verification

### GitHub Actions Testing

**After fixing migrations, test in CI:**

1. Push changes to branch
2. Check GitHub Actions run
3. Verify:
   - Tests complete without memory errors
   - Coverage report generated
   - SonarCloud upload succeeds

**If CI fails:**
- Check memory limits (currently 1G, may need 2G)
- Verify PostgreSQL service is healthy
- Check test database migrations succeed

---

## Execution Order

### Phase 1: Critical Blockers (Do First)
1. ✅ Fix migration ordering (merge user_roles into 000001)
2. ✅ Fix TaxEngineController namespace issues
3. ✅ Remove PHPUnit extension warning
4. ✅ Test that basic unit tests pass

### Phase 2: Feature Completeness
5. ✅ Add DashboardController::getData() method
6. ✅ Fix CSV export implementation
7. ✅ Fix client selection edge cases
8. ✅ Run full test suite and verify pass rate

### Phase 3: Optimization
9. ✅ Implement transaction-based RefreshDatabase
10. ✅ Benchmark test performance
11. ✅ Verify coverage generation works end-to-end

### Phase 4: CI/CD Validation
12. ✅ Push to GitHub and verify CI passes
13. ✅ Check SonarCloud integration
14. ✅ Document any remaining known issues

---

## Success Criteria

**Minimum Viable:**
- ✅ 95%+ tests passing (1150+ of 1216 tests)
- ✅ No migration errors
- ✅ Coverage report generates successfully
- ✅ CI/CD pipeline completes

**Ideal:**
- 💯 100% tests passing (1216 of 1216)
- ⚡ Test suite completes in <5 minutes
- 📊 Coverage uploaded to SonarCloud
- 📝 All issues documented in TEST_STATUS_REPORT.md

---

## Estimated Effort

| Phase | Tasks | Time | Priority |
|-------|-------|------|----------|
| Phase 1 | 4 tasks | 30-45 min | 🔥 CRITICAL |
| Phase 2 | 4 tasks | 1-2 hours | ⚠️ HIGH |
| Phase 3 | 3 tasks | 1 hour | 📈 MEDIUM |
| Phase 4 | 3 tasks | 30 min | ✅ LOW |

**Total: 3-4.5 hours** to get from current state to fully passing test suite.

---

## Files to Modify (Summary)

1. `database/migrations/2024_01_01_000001_create_all_tables_v1.php` - Add user_roles table
2. `database/migrations/2024_01_01_000002_create_permissions_system.php` - Remove user_roles
3. `routes/web.php` - Fix TaxEngineController namespace
4. `phpunit.xml` - Remove ParallelTestingExtension
5. `app/Domains/Core/Controllers/DashboardController.php` - Add getData() method
6. `app/Domains/Client/Controllers/ClientController.php` - Fix exportCsv(), clearSelection()
7. `tests/RefreshesDatabase.php` - Create optimized trait (new file)
8. `TEST_STATUS_REPORT.md` - Update with final status

---

## Risk Assessment

**Low Risk:**
- Route fixes (already tested patterns)
- PHPUnit config changes (cosmetic)
- Documentation updates

**Medium Risk:**
- Migration reordering (could break production if not careful)
- Mitigation: Test on clean database first

**High Risk:**
- RefreshDatabase optimization (could cause test pollution)
- Mitigation: Run full suite multiple times to verify isolation

---

## Quick Start Commands

```bash
# 1. Fix PHPUnit config warning (safest first step)
sed -i '/<extensions>/,/<\/extensions>/d' phpunit.xml

# 2. Fix TaxEngineController namespace
sed -i 's|App\\\\Http\\\\Controllers\\\\Api\\\\TaxEngineController|App\\\\Domains\\\\Financial\\\\Controllers\\\\Api\\\\TaxEngineController|g' routes/web.php

# 3. Test migration fix (manual - see Priority 1)
php artisan migrate:fresh --env=testing

# 4. Run test suite
php run-tests.php

# 5. Run with coverage
php run-tests.php --coverage
```

---

**Ready to execute? Start with Phase 1, Task 1: Fix migration ordering.**
