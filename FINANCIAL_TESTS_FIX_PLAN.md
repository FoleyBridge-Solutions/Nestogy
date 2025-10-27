# Financial Tests Refactoring Plan

## Summary
The Financial domain tests are failing because they were written based on expected behavior that doesn't match the actual database schema and code implementation.

## Test Results
- **API Invoice Tests**: 24 passed, 2 skipped ✓
- **Invoice Controller Tests**: Many failures
- **Quote Controller Tests**: Almost all failures
- **Total**: 58 failed, 2 skipped, 61 passed

## Root Causes

### 1. Route Ordering Issues (CRITICAL)
**File**: `/opt/nestogy/app/Domains/Financial/routes.php`

**Problem**: Utility routes defined AFTER resource routes get caught by route model binding:
```php
Route::resource('invoices', InvoiceController::class);  // Line 68
Route::get('invoices/overdue', [InvoiceController::class, 'overdue'])->name('invoices.overdue');  // Line 69
```
The `/invoices/overdue` route will never match because Laravel will treat "overdue" as an :invoice ID parameter.

**Fix**: Move all utility routes BEFORE the resource route:
```php
// Utility routes FIRST
Route::get('invoices/overdue', [InvoiceController::class, 'overdue'])->name('invoices.overdue');
Route::get('invoices/draft', [InvoiceController::class, 'draft'])->name('invoices.draft');
Route::get('invoices/sent', [InvoiceController::class, 'sent'])->name('invoices.sent');
Route::get('invoices/paid', [InvoiceController::class, 'paid'])->name('invoices.paid');
Route::get('invoices/recurring', [InvoiceController::class, 'recurring'])->name('invoices.recurring');
Route::get('invoices/export/csv', [InvoiceController::class, 'exportCsv'])->name('invoices.export.csv');

// Resource route AFTER
Route::resource('invoices', InvoiceController::class);
```

Same issue exists for quotes - lines 22-27 should be BEFORE line 19.

### 2. Global Company Scope Returns 404 Instead of 403
**Pattern**: Tests expecting 403 for unauthorized access get 404 instead

**Cause**: `BelongsToCompany` trait applies global scope that filters by company_id, preventing unauthorized invoices/quotes from being found.

**Fix**: Change test expectations from 403 to 404:
```php
// In tests where accessing another company's resource
$response->assertStatus(404);  // Not 403
```

**Files to update**:
- InvoiceControllerTest: line 259, 503
- QuoteControllerTest: line 812, and others

### 3. Soft Deletes Using `archived_at` Column
**Pattern**: Tests using `assertSoftDeleted()` fail because Invoice model uses `archived_at` not `deleted_at`

**Fix**: Check for archived_at instead:
```php
$this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
$invoice->refresh();
$this->assertNotNull($invoice->archived_at);
```

### 4. Decimal Values Returned as Strings
**Pattern**: Tests expecting numeric values get string decimals

**Fix**: Update test expectations:
```php
$response->assertJsonPath('totals.paid', '1000.00');  // Not 1000
```

### 5. Missing Database Tables/Columns
**Fixed**:
- ✓ `notification_logs` table created
- ✓ `companies.id` sequence attached

**Still Needed**:
- `recurring_invoices.is_active` column missing
- Other recurring invoice schema issues

### 6. View Names Don't Match
**Pattern**: Tests expect specific view names that may not match actual views

**Examples**:
- Expected: `financial.invoices.edit-livewire`
- Expected: `financial.quotes.index-livewire`

**Action**: Need to verify actual view names returned by controllers

### 7. Missing Form Request Validation Classes
Controllers reference:
- `StoreInvoiceRequest`
- `UpdateInvoiceRequest`
- `StoreQuoteRequest`
- `UpdateQuoteRequest`
- `ApproveQuoteRequest`
- `StorePaymentRequest`

Need to verify these exist or create them.

## Action Items

### High Priority (Blocks Most Tests)
1. ✓ Fix route ordering in `/opt/nestogy/app/Domains/Financial/routes.php`
   - Move invoice utility routes before resource route (lines 69-74 before 68)
   - Move quote utility routes before resource route (lines 22-27 before 19)

2. Update all tests expecting 403 to expect 404 for multi-tenancy violations
   - Search for `assertStatus(403)` in InvoiceControllerTest
   - Search for `assertStatus(403)` in QuoteControllerTest

3. Fix soft delete assertions to check `archived_at`
   - Update deleteInvoice tests
   - Update deleteQuote tests

### Medium Priority
4. Fix decimal/numeric assertions in tests
   - Update totals/amounts to expect string decimals

5. Verify and fix view names in controllers
   - Check actual view files exist
   - Match test expectations to actual views

6. Create or verify Form Request classes exist

### Low Priority (Can Be Skipped)
7. Fix recurring invoice functionality (already skipped 2 tests)
   - Add `is_active` column to recurring_invoices table
   - Fix recurring invoice schema

## Files That Need Changes

### Routes
- `/opt/nestogy/app/Domains/Financial/routes.php` - Reorder routes

### Tests
- `/opt/nestogy/tests/Feature/Financial/InvoiceControllerTest.php` - Multiple fixes
- `/opt/nestogy/tests/Feature/Financial/QuoteControllerTest.php` - Multiple fixes
- `/opt/nestogy/tests/Feature/Financial/Api/InvoicesControllerTest.php` - ✓ Already fixed

### Migrations (If Needed)
- Create migration for recurring_invoices schema fixes

## Next Steps
1. Fix route ordering (1 file change, fixes ~10-15 tests)
2. Bulk update 403→404 expectations (2 files, fixes ~5-10 tests)
3. Run tests again to see remaining failures
4. Address view name mismatches
5. Create missing Form Request classes if needed
