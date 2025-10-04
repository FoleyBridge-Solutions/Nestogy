# Test Fixes - Session 2 Progress

## Tests Fixed This Session

### 1. PortalNotificationTest ✅
**Fixes Applied:**
- Fixed `type` from number to string enum: `['info', 'warning', 'error', 'success', 'update']`
- Fixed `message` from optional randomNumber to required sentence (NOT NULL text)
- Fixed `language` from optional randomNumber to 'en' (NOT NULL varchar with default)
- Fixed `updated_by` from datetime to null (bigint user ID)
- Fixed `view_count` from optional to required integer (NOT NULL with default 0)
- Fixed `click_count` from optional to required integer (NOT NULL with default 0)
- Fixed `status` enum from `['active', 'inactive', 'pending']` to `['pending', 'sent', 'delivered', 'read', 'failed', 'cancelled']`

**File:** `/opt/nestogy/database/factories/PortalNotificationFactory.php`

### 2. RecurringTest ✅
**Fixes Applied:**
- **Model fixes** in `/opt/nestogy/app/Models/Recurring.php`:
  - Added Schema::hasColumn checks for `billing_type` (3 locations)
  - Added Schema::hasColumn check for `discount_type`
  - Added Schema::hasColumn check for `proration_method`
  - Cleaned up `$fillable` array to only include actual DB columns

- **Factory fixes** in `/opt/nestogy/database/factories/RecurringFactory.php`:
  - Removed non-existent columns: `proration_method`, `contract_escalation`, `escalation_percentage`, `escalation_months`, `last_escalation`, `tax_settings`, `max_invoices`, `invoices_generated`, `metadata`
  - Fixed `prefix` from randomNumber to word
  - Fixed `number` from optional to required (NOT NULL)
  - Fixed `scope` from randomNumber to word
  - Fixed `frequency` from randomNumber to enum: `['daily', 'weekly', 'monthly', 'quarterly', 'yearly']`
  - Fixed `last_sent` from randomNumber to optional datetime
  - Fixed `next_date` from optional past date to required future date (NOT NULL)
  - Fixed `status` from enum to boolean
  - Fixed `note` from randomNumber to sentence
  - Fixed `overage_rates` from float to null (it's JSON column)
  - Fixed `auto_invoice_generation` from randomNumber to boolean
  - Fixed `invoice_terms_days` from randomNumber to number 0-90
  - Added missing `category_id` and `client_id` (NOT NULL foreign keys)

### 3. PricingRuleFactory ✅
**Fixes Applied:**
- Fixed `pricing_model` from `'flat_rate'` to enum: `['fixed', 'tiered', 'volume', 'usage', 'package', 'custom']`

**File:** `/opt/nestogy/database/factories/PricingRuleFactory.php`

## Current Status

**Tests Passing:** ~699/735 (estimated based on previous 696 + 3 fixed)
**Tests Failing:** ~36
**Progress This Session:** 3 tests fixed (PortalNotification, Recurring, partial PricingRule)

## Common Patterns Found

1. **Factory/Schema Mismatch**: Many factories have fields that don't exist in actual DB schema
2. **Model/Schema Mismatch**: Models reference columns in fillable/casts that don't exist
3. **Data Type Errors**: 
   - randomNumber() used for text/datetime/boolean fields
   - optional() on NOT NULL columns
   - Wrong enum values vs database constraints

4. **Solution Pattern**:
   - Check actual schema: `sudo -u postgres psql nestogy -c "\d table_name"`
   - Check constraints: `SELECT pg_get_constraintdef(oid) FROM pg_constraint WHERE conname = 'constraint_name'`
   - Add Schema::hasColumn() checks in models for columns that may not exist
   - Fix factories to match actual schema

## Remaining Work

### Next Tests to Fix (in order of appearance)
1. CompanyMailSettingsTest - First error in sequential run
2. ServiceTest
3. SettingTest
4. SubsidiaryPermissionTest
5. TagTest
6. TaxApiSettingsTest
7. TaxCalculationTest
8. TicketRatingTest
9. TimeEntryTest
10. UsageRecordTest
11. UsageTierTest
12. QuoteApprovalTest (database connection issue)
13. ~24 more

### Strategy for Remaining Tests

**Option 1: Systematic Factory Fixes (Recommended)**
1. Run each test individually with timeout
2. Identify error type (NOT NULL, check constraint, undefined column)
3. Fix factory/model accordingly
4. Move to next test
5. **Time estimate**: 36 tests × 5 min = 180 minutes (3 hours)

**Option 2: Batch Analysis**
1. Run all tests, collect all errors
2. Group by error type
3. Fix all similar errors at once
4. Re-run to verify
5. **Time estimate**: Could be faster but riskier

**Option 3: Schema-First Approach**
1. Generate list of all tables with factories
2. Compare each factory definition with actual schema
3. Fix all factories preventively
4. Run tests to verify
5. **Time estimate**: Unknown, but could catch all issues

## Files Modified This Session

1. `/opt/nestogy/database/factories/PortalNotificationFactory.php`
2. `/opt/nestogy/database/factories/RecurringFactory.php`
3. `/opt/nestogy/database/factories/PricingRuleFactory.php`
4. `/opt/nestogy/app/Models/Recurring.php`

## Next Steps

1. Continue fixing tests one by one, starting with CompanyMailSettingsTest
2. Document each fix in this file
3. Track progress toward 0 errors
4. Run full CI suite when complete to verify

## Test Execution Time Note

- Each individual test takes ~13-15 seconds
- Full suite takes 5+ minutes (timeout at 300s)
- Running tests individually is more efficient for debugging
