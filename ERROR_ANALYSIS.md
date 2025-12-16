# Test Error Analysis & Fix Plan

## Error Categories Summary
- **Total Errors**: 87
- **Total Failures**: 59
- **PHPUnit Warnings**: 1

## Detailed Error Analysis

### Category 1: Missing Database Columns (20+ errors)

#### 1.1 Contract Signatures - Missing `signatory_type` column
**Affected Tests**: 3 tests (errors #1-3)
**Error**: `column "signatory_type" of relation "contract_signatures" does not exist`
**Root Cause**: Model expects column but migration doesn't have it
**Fix Plan**: Create migration to add `signatory_type` column to `contract_signatures` table

#### 1.2 Projects - Missing `end_date` column  
**Affected Tests**: 1 test (error #19)
**Error**: `column "end_date" of relation "projects" does not exist`
**Root Cause**: Factory/Model uses `end_date` but table doesn't have it
**Fix Plan**: Create migration to add `end_date` column to `projects` table

#### 1.3 Tickets - Missing `due_date` column
**Affected Tests**: 1 test (error #20)
**Error**: `column "due_date" of relation "tickets" does not exist`
**Root Cause**: Factory uses `due_date` but table doesn't have it
**Fix Plan**: Create migration to add `due_date` column to `tickets` table

#### 1.4 Invoices - Missing `sent_at` and `paid_at` columns
**Affected Tests**: 3 tests (errors #24, #26, #27)
**Error**: `column "sent_at"/"paid_at" of relation "invoices" does not exist`
**Root Cause**: Model/Factory uses these columns but they don't exist
**Fix Plan**: Create migration to add `sent_at` and `paid_at` columns to `invoices` table

#### 1.5 Contracts - Missing `is_active` and `value` columns
**Affected Tests**: 2 tests (errors #31, #32)
**Error**: `column "is_active"/"value" of relation "contracts" does not exist`
**Root Cause**: Factory uses these but table doesn't have them
**Fix Plan**: Create migration to add `is_active` and `value` columns to `contracts` table

#### 1.6 Taxes - Missing `rate`, `is_default`, `is_active` columns
**Affected Tests**: 2 tests (errors #45, #46)
**Error**: `column "rate"/"is_default"/"is_active" of relation "taxes" does not exist`
**Root Cause**: Factory uses these but table doesn't have them
**Fix Plan**: Create migration to add missing columns to `taxes` table

### Category 2: NOT NULL Constraint Violations (15+ errors)

#### 2.1 EmailAccount - `imap_password` cannot be NULL
**Affected Tests**: 15 tests (errors #7-15, #33-37)
**Error**: `null value in column "imap_password" violates not-null constraint`
**Root Cause**: Factory sets it to null but DB requires NOT NULL
**Fix Plan**: ✅ FIXED - Updated EmailAccountFactory to encrypt passwords

#### 2.2 InvoiceItems - `name` cannot be NULL
**Affected Tests**: 4 tests (errors #21-23, #30)
**Error**: `null value in column "name" of relation "invoice_items" violates not-null constraint`
**Root Cause**: InvoiceService creates items without `name` field
**Fix Plan**: Update InvoiceService to include `name` when creating invoice items

### Category 3: Missing Database Tables (2 errors)

#### 3.1 RevenueMetric - Table `revenue_metric_s` doesn't exist
**Affected Tests**: 1 test (error #38)
**Error**: `relation "revenue_metric_s" does not exist`
**Root Cause**: Model pluralization issue or missing migration
**Fix Plan**: Check model table name, create migration if needed

#### 3.2 Asset - Table `asset_s` doesn't exist
**Affected Tests**: 1 test (error #41)
**Error**: `relation "asset_s" does not exist`
**Root Cause**: Model pluralization issue or missing migration
**Fix Plan**: Check model table name, create migration if needed

### Category 4: Missing Factory Classes (10+ errors)

#### 4.1 ProjectTask, ProjectTimeEntry - No factories
**Affected Tests**: 2 tests (errors #17, #18)
**Error**: `Call to undefined method factory()`
**Fix Plan**: Create ProjectTaskFactory and ProjectTimeEntryFactory

#### 4.2 ProjectExpense - No factory
**Affected Tests**: 3 tests (errors #42-44)
**Error**: `Call to undefined method factory()`
**Fix Plan**: Create ProjectExpenseFactory

#### 4.3 KbArticle, KbCategory - No factories
**Affected Tests**: 6 tests (errors #49-54)
**Error**: `Call to undefined method factory()`
**Fix Plan**: Create KbArticleFactory and KbCategoryFactory

### Category 5: Missing Model Methods (2 errors)

#### 5.1 Contact - Missing `canAccessCrossTenant()` method
**Affected Tests**: 2 tests (errors #16, #39)
**Error**: `Call to undefined method canAccessCrossTenant()`
**Root Cause**: View/code calls method that doesn't exist on Contact model
**Fix Plan**: Add `canAccessCrossTenant()` method to Contact model

### Category 6: Unique Constraint Violations (3 errors)

#### 6.1 Settings - Duplicate `company_id`
**Affected Tests**: 3 tests (errors #4-6)
**Error**: `duplicate key value violates unique constraint "settings_company_id_unique"`
**Root Cause**: Tests creating multiple Settings for same company_id
**Fix Plan**: Update SettingFactory to handle unique constraint properly

### Category 7: Configuration Issues (1 error)

#### 7.1 Auth guard 'portal' not defined
**Affected Tests**: 1 test (error #40)
**Error**: `Auth guard [portal] is not defined`
**Fix Plan**: Add 'portal' guard to auth configuration

### Category 8: Factory Reference Errors (2 errors)

#### 8.1 DocumentFactory - Wrong User class reference
**Affected Tests**: 2 tests (errors #47, #48)
**Error**: `Class "Database\Factories\Domains\Core\Models\User" not found`
**Fix Plan**: Fix User class import in DocumentFactory

### Category 9: Check Constraint Violations (3 errors)

#### 9.1 Lead - Invalid priority value
**Affected Tests**: 3 tests (errors #55-57)
**Error**: `new row violates check constraint "leads_priority_check"`
**Fix Plan**: Update LeadFactory to use valid priority values

### Category 10: Mockery/Test Logic Issues (3 errors)

#### 10.1 InvoiceService - Excessive logging calls
**Affected Tests**: 3 tests (errors #25, #28, #29)
**Error**: `Method info() should be called exactly 1 times but called 3 times`
**Fix Plan**: Update test expectations or reduce logging in service

#### 10.2 InvoiceService - Null reference on toDateTimeString()
**Affected Tests**: 1 test (error #27)
**Error**: `Call to member function toDateTimeString() on null`
**Fix Plan**: Ensure test provides proper date values

## Execution Order

1. Database Schema Fixes (Migrations) - Highest Impact
2. Factory Fixes - Medium Impact
3. Model Method Fixes - Low Impact  
4. Configuration Fixes - Low Impact
5. Test Logic Fixes - Lowest Impact

