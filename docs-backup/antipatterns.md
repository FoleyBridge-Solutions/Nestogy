# Antipattern Tracking Document

**Project:** Nestogy ERP
**Generated:** 2025-10-15
**Last Updated:** 2025-10-15

---

## Executive Summary

This document tracks all identified antipatterns in the Nestogy codebase, organized by category with severity ratings and resolution status.

### Overview by Severity

| Severity | Count | Status |
|----------|-------|--------|
| **P0 - CRITICAL** | 17 | 🔴 Needs Immediate Attention |
| **P1 - HIGH** | 43 | 🟠 High Priority |
| **P2 - MEDIUM** | 68 | 🟡 Medium Priority |
| **P3 - LOW** | 25+ | 🟢 Low Priority / Tech Debt |
| **TOTAL** | 153+ | In Progress |

### Quick Stats

- **Files Analyzed:** 1,036 PHP files, 1,110 Blade files
- **Test Coverage:** 121 tests for 1,036 files (11.7%)
- **Controllers >1000 lines:** 7
- **Services >1000 lines:** 12
- **Models >500 lines:** 8
- **Blade files >500 lines:** 19

---

## Table of Contents

1. [N+1 Query Antipatterns](#1-n1-query-antipatterns)
2. [Code Duplication](#2-code-duplication)
3. [Fat Controllers](#3-fat-controllers)
4. [Missing Validation](#4-missing-validation)
5. [God Objects](#5-god-objects)
6. [Security Issues](#6-security-issues)
7. [Dependency Antipatterns](#7-dependency-antipatterns)
8. [Performance Issues](#8-performance-issues)
9. [Error Handling Antipatterns](#9-error-handling-antipatterns)
10. [Testing Gaps](#10-testing-gaps)
11. [Blade/View Antipatterns](#11-bladeview-antipatterns)
12. [Code Smells](#12-code-smells)

---

## 1. N+1 Query Antipatterns

**Severity:** 🔴 P0-CRITICAL to 🟡 P2-MEDIUM
**Total Issues:** 11
**Status:** Not Started

### Critical Issues (P0)

#### 1.1 InvoiceStatus Widget - Balance Calculation Loop
- [ ] **CRITICAL** - Fix immediately
- **File:** [app/Livewire/Dashboard/Widgets/InvoiceStatus.php:91-93](app/Livewire/Dashboard/Widgets/InvoiceStatus.php#L91-L93)
- **Issue:** `getBalance()` method called on each invoice in loop, likely triggers N+1
- **Impact:** Dashboard loads could make hundreds of queries
- **Fix:** Pre-load payment relationships:
  ```php
  return $query->with('paymentApplications.payment')->get()->sum(function ($invoice) {
      return $invoice->getBalance();
  });
  ```

#### 1.2 VoIPTaxReportingService - Nested Loop N+1
- [ ] **CRITICAL** - Fix immediately
- **File:** [app/Domains/Financial/Services/VoIPTaxReportingService.php:426-470](app/Domains/Financial/Services/VoIPTaxReportingService.php#L426)
- **Issue:** `$invoice->voipItems` triggers separate query for each invoice
- **Impact:** Could make thousands of queries for large reports
- **Fix:** Eager load: `$invoices = Invoice::with('voipItems')->where(...)->get();`

#### 1.3 CustomerSatisfaction Widget - Multiple Iterations
- [ ] **CRITICAL** - Fix immediately
- **File:** [app/Livewire/Dashboard/Widgets/CustomerSatisfaction.php:112-131](app/Livewire/Dashboard/Widgets/CustomerSatisfaction.php#L112)
- **Issue:** Iterates through tickets multiple times, recalculating scores
- **Impact:** Inefficient dashboard rendering
- **Fix:** Calculate once and cache results

#### 1.4 KpiGrid Widget - Resolved Tickets Loop
- [ ] **CRITICAL** - Fix immediately
- **File:** [app/Livewire/Dashboard/Widgets/KpiGrid.php:374-390](app/Livewire/Dashboard/Widgets/KpiGrid.php#L374)
- **Issue:** Loading all resolved tickets into memory for calculation
- **Fix:** Use database-level aggregation

### High Priority Issues (P1)

#### 1.5 RecurringInvoiceService - Query in Loop
- [ ] **HIGH** - Fix soon
- **File:** [app/Domains/Financial/Services/RecurringInvoiceService.php:544](app/Domains/Financial/Services/RecurringInvoiceService.php#L544)
- **Issue:** Loading recurring invoices inside loop
- **Fix:** Eager load with constraints

#### 1.6 DigitalSignatureService - Multiple Signature Queries
- [ ] **HIGH** - Fix soon
- **File:** [app/Domains/Security/Services/DigitalSignatureService.php:193,258,331](app/Domains/Security/Services/DigitalSignatureService.php#L193)
- **Issue:** `$contract->signatures()` called multiple times
- **Fix:** Eager load once at start of method

#### 1.7 PaymentCreate Livewire - Balance Check Loop
- [ ] **HIGH** - Fix soon
- **File:** [app/Livewire/Financial/PaymentCreate.php:214-228](app/Livewire/Financial/PaymentCreate.php#L214)
- **Issue:** `getBalance()` on each invoice in loop
- **Fix:** Eager load payment relationships

#### 1.8 ExecutiveReportService - Missing Relationships
- [ ] **HIGH** - Fix soon
- **File:** [app/Domains/Report/Services/ExecutiveReportService.php:94-101](app/Domains/Report/Services/ExecutiveReportService.php#L94)
- **Issue:** Health score calculation likely needs more eager loading
- **Fix:** Identify and load all required relationships

#### 1.9 CollectionManagementService - batchAssessRisk N+1
- [ ] **HIGH** - Fix soon
- **File:** [app/Domains/Financial/Services/CollectionManagementService.php:697-699](app/Domains/Financial/Services/CollectionManagementService.php#L697)
- **Issue:** `assessClientRisk()` queries relationships per client
- **Fix:** `$clients = $clients->load(['invoices', 'payments', 'tickets']);`

### Medium Priority Issues (P2)

#### 1.10 Mobile Time Tracker - Query in Blade
- [ ] **MEDIUM** - Tech debt
- **File:** [resources/views/livewire/mobile-time-tracker.blade.php:126](resources/views/livewire/mobile-time-tracker.blade.php#L126)
- **Issue:** Direct query in Blade template
- **Fix:** Move to Livewire component computed property

#### 1.11 ProjectShow - Team Members Access
- [ ] **RESOLVED** ✅ - Already has eager loading
- **File:** [resources/views/livewire/projects/project-show.blade.php:160-170](resources/views/livewire/projects/project-show.blade.php#L160)
- **Status:** Component already eager loads on lines 60-74

---

## 2. Code Duplication

**Severity:** 🟠 P1-HIGH to 🟡 P2-MEDIUM
**Total Issues:** 6 major patterns
**Status:** Not Started

### High Priority Duplication (P1)

#### 2.1 JSON Response Handling - 64 Instances
- [ ] **HIGH** - Extract to trait/helper
- **Pattern:** `if ($request->wantsJson())` blocks with identical structure
- **Files:** InvoiceController, QuoteController, PaymentController, 61+ others
- **Fix:** Create `JsonResponseTrait` or `ApiResponseHelper`

#### 2.2 Authorization Checks - 154+ Instances
- [ ] **HIGH** - Standardize pattern
- **Pattern:** `$this->authorize('view', ...)` repeated across 67 controllers
- **Fix:** Use middleware where possible, create base controller method

#### 2.3 Logging Patterns - 296+ Instances
- [ ] **HIGH** - Extract to service
- **Pattern:** `Log::error(...failed` with similar structure
- **Fix:** Create centralized `LoggingService` with standard methods

### Medium Priority Duplication (P2)

#### 2.4 Item Management Logic - Invoice/Quote Duplication
- [ ] **MEDIUM** - Extract to shared service
- **Files:**
  - [app/Domains/Financial/Controllers/InvoiceController.php:331-464](app/Domains/Financial/Controllers/InvoiceController.php#L331) (addItem, updateItem, deleteItem)
  - [app/Domains/Financial/Controllers/QuoteController.php:406-539](app/Domains/Financial/Controllers/QuoteController.php#L406) (addItem, updateItem, deleteItem)
- **Fix:** Create `LineItemService` for shared logic

#### 2.5 Validation Rules - 417+ Instances
- [ ] **MEDIUM** - Extract constants
- **Pattern:** `'required|numeric|min:0'` repeated across files
- **Fix:** Create validation rule constants or custom rule classes

#### 2.6 Export Logic Duplication
- [ ] **MEDIUM** - Create export service
- **Files:**
  - [app/Domains/Financial/Controllers/InvoiceController.php:757-812](app/Domains/Financial/Controllers/InvoiceController.php#L757)
  - [app/Domains/Financial/Controllers/QuoteController.php:1128-1189](app/Domains/Financial/Controllers/QuoteController.php#L1128)
- **Fix:** Create `CsvExportService` with generic export methods

---

## 3. Fat Controllers

**Severity:** 🔴 P0-CRITICAL
**Total Issues:** 20+ controllers
**Status:** Not Started

### Critical Fat Controllers (P0)

#### 3.1 DashboardController - 1,818 Lines
- [ ] **CRITICAL** - Needs major refactoring
- **File:** [app/Domains/Core/Controllers/DashboardController.php](app/Domains/Core/Controllers/DashboardController.php)
- **Size:** 1,818 lines, 67 methods
- **Issues:** 239 DB queries, complex workflow logic, statistics calculation
- **Extract to:**
  - [ ] `WorkflowDataService` (lines 570-1095)
  - [ ] `DashboardStatisticsService` (lines 145-288)
  - [ ] `WorkflowKPIProvider` (lines 1098-1292)
  - [ ] `DashboardChartService` (lines 1400-1576)
  - [ ] `AlertGenerationService` (lines 486-534)

#### 3.2 ClientController - 1,720 Lines
- [ ] **CRITICAL** - Needs major refactoring
- **File:** [app/Domains/Client/Controllers/ClientController.php](app/Domains/Client/Controllers/ClientController.php)
- **Size:** 1,720 lines, 68 methods
- **Issues:** 59 DB queries, CSV import/export, URL validation
- **Extract to:**
  - [ ] `ClientRepository` - Database queries
  - [ ] `ClientExportService` (lines 654-718)
  - [ ] `ClientImportService` (lines 849-1719)
  - [ ] `ClientValidationService` (lines 1162-1317)

#### 3.3 QuoteController - 1,651 Lines
- [ ] **CRITICAL** - Needs major refactoring
- **File:** [app/Domains/Financial/Controllers/QuoteController.php](app/Domains/Financial/Controllers/QuoteController.php)
- **Size:** 1,651 lines, 43 methods
- **Issues:** 58 DB queries, conversion logic, PDF generation
- **Extract to:**
  - [ ] `QuoteRepository`
  - [ ] `QuoteConversionAction` (lines 707-823)
  - [ ] `QuoteApprovalService` (lines 544-625)
  - [ ] `QuotePdfGenerator` (lines 671-702)

#### 3.4 ContractController - 1,505 Lines
- [ ] **CRITICAL** - Needs major refactoring
- **File:** [app/Domains/Contract/Controllers/ContractController.php](app/Domains/Contract/Controllers/ContractController.php)
- **Size:** 1,505 lines, 47 methods
- **Issues:** 58 DB queries, billing calculations, raw SQL
- **Extract to:**
  - [ ] `ContractRepository`
  - [ ] `ContractBillingCalculator` (lines 1343-1504)
  - [ ] `ContractActivationService` (lines 752-799)
  - [ ] `ContractSignatureService` (lines 324-403)

#### 3.5 ClientPortalController - 1,337 Lines
- [ ] **CRITICAL** - Needs major refactoring
- **File:** [app/Domains/Client/Controllers/ClientPortalController.php](app/Domains/Client/Controllers/ClientPortalController.php)
- **Size:** 1,337 lines, 68 methods

#### 3.6 InvoiceController - 1,252 Lines
- [ ] **CRITICAL** - Needs major refactoring
- **File:** [app/Domains/Financial/Controllers/InvoiceController.php](app/Domains/Financial/Controllers/InvoiceController.php)
- **Size:** 1,252 lines, 38 methods

#### 3.7 UserController - 1,038 Lines
- [ ] **CRITICAL** - Needs major refactoring
- **File:** [app/Domains/Security/Controllers/UserController.php](app/Domains/Security/Controllers/UserController.php)
- **Size:** 1,038 lines

### High Priority (P1) - 13 Controllers (500-1000 lines)

- [ ] RecurringController - 865 lines
- [ ] NavigationController - 851 lines
- [ ] TaskController - 844 lines
- [ ] AssignmentController - 780 lines
- [ ] ContactController - 765 lines
- [ ] TimeTrackingController - 749 lines
- [ ] (7 more controllers over 500 lines)

---

## 4. Missing Validation

**Severity:** 🔴 P0-CRITICAL to 🟡 P2-MEDIUM
**Total Issues:** 13
**Status:** Not Started

### Critical Validation Issues (P0)

#### 4.1 Contact Model - Overly Permissive Fillable
- [ ] **CRITICAL** - Security vulnerability
- **File:** [app/Models/Contact.php:60-133](app/Models/Contact.php#L60)
- **Issue:** 85+ fields in `$fillable`, including sensitive security fields
- **Risk:** Mass assignment of `password_hash`, `pin`, `invitation_token`, `failed_login_count`, `locked_until`
- **Fix:** Remove security fields from fillable, use explicit setters

#### 4.2 ContactController::store() - Mass Assignment Vulnerability
- [ ] **CRITICAL** - Security vulnerability
- **File:** [app/Domains/Client/Controllers/ContactController.php:139](app/Domains/Client/Controllers/ContactController.php#L139)
- **Issue:** `new Contact($request->all())` passes ALL request data
- **Risk:** User could inject arbitrary fields
- **Fix:** Use `$request->validated()` or `$request->only([...])`

#### 4.3 Client Model - Payment Fields in Fillable
- [ ] **CRITICAL** - Business logic risk
- **File:** [app/Models/Client.php:20-75](app/Models/Client.php#L20)
- **Issue:** `stripe_customer_id`, `stripe_subscription_id`, `subscription_status` are mass-assignable
- **Fix:** Protect payment fields, control via service layer only

### High Priority Issues (P1)

#### 4.4 PaymentController::store() - Fallback Value After Validation
- [ ] **HIGH** - Logic error
- **File:** [app/Domains/Financial/Controllers/PaymentController.php:123](app/Domains/Financial/Controllers/PaymentController.php#L123)
- **Issue:** `$validated['currency'] = $validated['currency'] ?? 'USD';` after validation
- **Fix:** Add default in validation rules, not after

#### 4.5 ContactController::updatePortalAccess() - Direct Request Access
- [ ] **HIGH** - Inconsistent validation
- **File:** [app/Domains/Client/Controllers/ContactController.php:398-419](app/Domains/Client/Controllers/ContactController.php#L398)
- **Issue:** Uses `$request->input()` and `$request->boolean()` after validation
- **Fix:** Use validated data consistently

#### 4.6 ContactController::update() - Uses fill($request->all())
- [ ] **HIGH** - Security risk
- **File:** [app/Domains/Client/Controllers/ContactController.php:289](app/Domains/Client/Controllers/ContactController.php#L289)
- **Issue:** `$contact->fill($request->all())` instead of validated data
- **Fix:** Use `$contact->fill($request->validated())`

### Medium Priority Issues (P2)

#### 4.7 ClientController::index() - Raw Search Parameters
- [ ] **MEDIUM** - SQL injection risk
- **File:** [app/Domains/Client/Controllers/ClientController.php:124-139](app/Domains/Client/Controllers/ClientController.php#L124)
- **Issue:** Uses `$request->get()` directly in LIKE queries
- **Fix:** Validate search parameters

#### 4.8-4.10 Missing FormRequest Classes
- [ ] **MEDIUM** - Create dedicated FormRequest classes
- **Missing:** `StoreContactRequest`, `UpdateContactRequest`
- **Missing:** `StoreLocationRequest`, `UpdateLocationRequest`
- **Missing:** Dedicated classes for several other entities

#### 4.11 Payment Model - Financial Fields Fillable
- [ ] **MEDIUM** - Business logic risk
- **File:** [app/Models/Payment.php:24-32](app/Models/Payment.php#L24)
- **Issue:** `status`, `gateway_transaction_id`, `refund_amount` are mass-assignable
- **Fix:** Control via service layer

#### 4.12-4.13 Password/PIN Handling
- [ ] **LOW** - Could be improved
- **Files:** ContactController lines 403-410
- **Issue:** Inline password hashing, no strength validation beyond min:8
- **Fix:** Move to service layer, strengthen validation

---

## 5. God Objects

**Severity:** 🔴 P0-CRITICAL
**Total Issues:** 15
**Status:** Not Started

### Critical God Objects (P0)

#### 5.1 Setting Model - THE ULTIMATE GOD OBJECT
- [ ] **CRITICAL** - Most severe antipattern in codebase
- **File:** [app/Models/Setting.php](app/Models/Setting.php)
- **Size:** 1,546 lines, 51 methods, 400+ fillable attributes
- **Responsibilities:** Company settings, security, email, financial, RMM, ticketing, projects, assets, portal, automation, compliance, backup, performance, reporting, notifications, API, mobile, training, system
- **Issues:** Knows about EVERY subsystem, massive coupling
- **Fix Strategy:**
  - [ ] Split into domain-specific configuration classes
  - [ ] `CompanySettings`, `SecuritySettings`, `EmailSettings`
  - [ ] `FinancialSettings`, `IntegrationSettings`, `PortalSettings`
  - [ ] Use proper configuration management pattern

#### 5.2 ContractService - 4,043 Lines, 114 Methods
- [ ] **CRITICAL** - Massive service class
- **File:** [app/Domains/Contract/Services/ContractService.php](app/Domains/Contract/Services/ContractService.php)
- **Responsibilities:** CRUD, search, filtering, validation, schedules, assets, pricing, lifecycle management, statistics
- **Fix:** Split into 8-10 focused services

#### 5.3 NavigationService - 3,753 Lines, 127 Methods
- [ ] **CRITICAL** - Extreme method count
- **File:** [app/Domains/Core/Services/NavigationService.php](app/Domains/Core/Services/NavigationService.php)
- **Responsibilities:** Navigation generation, routes, permissions, menu building
- **Fix:** Apply strategy pattern, split by navigation type

#### 5.4 ContractGenerationService - 1,898 Lines, 79 Methods
- [ ] **CRITICAL** - Complex document generation
- **File:** [app/Domains/Contract/Services/ContractGenerationService.php](app/Domains/Contract/Services/ContractGenerationService.php)
- **Fix:** Split by generation strategy

#### 5.5 TemplateVariableMapper - 1,872 Lines, 56 Methods
- [ ] **CRITICAL** - Too many mapping responsibilities
- **File:** [app/Domains/Core/Services/TemplateVariableMapper.php](app/Domains/Core/Services/TemplateVariableMapper.php)
- **Fix:** Split by domain/entity type

### High Priority God Objects (P1)

#### 5.6 CommandPaletteService - 1,575 Lines, 57 Methods
- [ ] **HIGH**
- **File:** [app/Domains/Core/Services/CommandPaletteService.php](app/Domains/Core/Services/CommandPaletteService.php)

#### 5.7 ContractClauseService - 1,417 Lines, 50 Methods
- [ ] **HIGH**
- **File:** [app/Domains/Contract/Services/ContractClauseService.php](app/Domains/Contract/Services/ContractClauseService.php)

#### 5.8 TaxEngineRouter - 1,396 Lines, 43 Methods
- [ ] **HIGH**
- **File:** [app/Domains/Financial/Services/TaxEngine/TaxEngineRouter.php](app/Domains/Financial/Services/TaxEngine/TaxEngineRouter.php)

#### 5.9 DashboardDataService - 1,211 Lines, 86 Methods
- [ ] **HIGH**
- **File:** [app/Domains/Core/Services/DashboardDataService.php](app/Domains/Core/Services/DashboardDataService.php)
- **Issues:** Tight coupling, direct service instantiation

#### 5.10 SettingsService - 1,167 Lines, 34 Methods
- [ ] **HIGH**
- **File:** [app/Domains/Core/Services/SettingsService.php](app/Domains/Core/Services/SettingsService.php)

#### 5.11-5.12 Financial Services
- [ ] **HIGH** - QuoteService - 1,159 lines, 49 methods
- [ ] **HIGH** - ClientPortalService - 1,156 lines, 51 methods

### Models with Business Logic (P1)

#### 5.13 Quote Model - 1,025 Lines, 62 Methods
- [ ] **HIGH** - Move business logic to services
- **File:** [app/Models/Quote.php](app/Models/Quote.php)
- **Issue:** Tax calculations, pricing models in model

#### 5.14 Recurring Model - 962 Lines, 46 Methods
- [ ] **HIGH** - Extract calculations
- **File:** [app/Models/Recurring.php](app/Models/Recurring.php)
- **Methods to extract:** `calculateNextDate()`, `calculateProration()`, `calculateUsageCharges()`, `generateInvoice()`

#### 5.15 Invoice Model - 929 Lines, 62 Methods
- [ ] **HIGH** - Extract tax/generation logic
- **File:** [app/Models/Invoice.php](app/Models/Invoice.php)
- **Methods to extract:** `calculateVoIPTaxes()`, `calculateTotals()`, `generateUrlKey()`

---

## 6. Security Issues

**Severity:** 🔴 P0-CRITICAL to 🟡 P2-MEDIUM
**Total Issues:** 8
**Status:** Not Started

### Critical Security Issues (P0)

#### 6.1 WidgetService - SQL Injection via String Concatenation
- [ ] **CRITICAL** - SQL injection vulnerability
- **File:** [app/Domains/Report/Services/WidgetService.php:97-100](app/Domains/Report/Services/WidgetService.php#L97)
- **Issue:** Direct string concatenation in pagination: `$query .= ' LIMIT '.($config['paginate']['offset'] ?? 0)`
- **Risk:** User-controlled parameters could inject SQL
- **Fix:** Use parameterized queries for LIMIT/OFFSET

#### 6.2 WidgetService - User-Editable SQL Queries
- [ ] **CRITICAL** - Potential arbitrary SQL execution
- **File:** [app/Domains/Report/Services/WidgetService.php](app/Domains/Report/Services/WidgetService.php) (multiple lines)
- **Issue:** `DB::select($query, $params)` where `$query` comes from configuration
- **Risk:** If widget configs are user-editable, arbitrary SQL possible
- **Fix:** Validate/sanitize all queries, use Eloquent query builder

#### 6.3 Contract Content XSS Vulnerability
- [ ] **CRITICAL** - XSS vulnerability
- **File:** [resources/views/financial/contracts/show.blade.php.bootstrap-backup:408](resources/views/financial/contracts/show.blade.php.bootstrap-backup#L408)
- **Issue:** `{!! $contract->content !!}` - unescaped output
- **Risk:** Malicious scripts in contract content
- **Fix:** Sanitize HTML content or use `{{ }}` if plain text

### Medium Priority Security Issues (P2)

#### 6.4 CRUD Table Callback Output Not Escaped
- [ ] **MEDIUM** - XSS risk
- **File:** [resources/views/components/crud-table.blade.php:54](resources/views/components/crud-table.blade.php#L54)
- **Issue:** `{!! call_user_func($column['callback'], $item) !!}`
- **Fix:** Ensure callbacks return escaped content

#### 6.5 ContractController - Raw SQL for JSON
- [ ] **LOW** - Use Laravel's JSON operators
- **File:** [app/Domains/Contract/Controllers/ContractController.php:869](app/Domains/Contract/Controllers/ContractController.php#L869)
- **Issue:** `DB::raw("JSON_UNQUOTE(JSON_EXTRACT(...)`
- **Fix:** Use `->sum('pricing_structure->recurring_monthly')`

#### 6.6-6.8 Review whereRaw() Usages
- [ ] **LOW** - Monitor for safety
- **Files:** Multiple files use `whereRaw()` with parameterized bindings (currently safe)
- **Action:** Monitor for changes, ensure bindings always used

### Security Strengths ✅

- ✅ Excellent CSRF protection across all forms (207 `@csrf` found)
- ✅ Strong password policies (`Password::min(8)->letters()->mixedCase()->numbers()`)
- ✅ No hardcoded credentials found
- ✅ Comprehensive authorization checks (178 files use `Gate::`, `authorize()`, `can()`)
- ✅ Rate limiting on authentication (5 attempts/minute)
- ✅ Proper encryption for sensitive data (`Crypt::encryptString()`)
- ✅ Sanitization in error handlers

---

## 7. Dependency Antipatterns

**Severity:** 🟠 P1-HIGH to 🟡 P2-MEDIUM
**Total Issues:** 6 major patterns
**Status:** Not Started

### High Priority Issues (P1)

#### 7.1 Service Locator Pattern - 93+ Files
- [ ] **HIGH** - Use dependency injection instead
- **Pattern:** Using `app()` to resolve dependencies
- **Examples:**
  - QuoteController:787,1477,1529 - `app(\App\Domains\Financial\Services\RecurringBillingService::class)`
  - PaymentController:125 - `app(\App\Domains\Financial\Services\PaymentService::class)`
  - CampaignController:29 - `$this->service = app(\App\Domains\Marketing\Services\CampaignService::class)`
- **Fix:** Inject via constructor

#### 7.2 Direct Instantiation with 'new' - 124+ Files
- [ ] **HIGH** - Tight coupling
- **Examples:**
  - QuoteService:34 - `$this->taxEngine = new TaxEngineRouter($companyId);`
  - RmmServiceFactory:26,44 - `return new TacticalRmmService($integration);`
- **Fix:** Use dependency injection or factory pattern

#### 7.3 Missing Service Interfaces - 50+ Services
- [ ] **HIGH** - Violates dependency inversion
- **Issue:** Only 12 interfaces for 166+ services
- **Missing interfaces for:**
  - InvoiceService, QuoteService, PaymentService
  - NotificationService, ResolutionEstimateService
  - All Tax Engine services
- **Fix:** Create interfaces for all major services

#### 7.4 Hard Dependencies on Concrete Classes
- [ ] **HIGH** - Tight coupling
- **Examples:**
  - PaymentProcessingService - Type-hints concrete `CommunicationService`, `VoipCollectionService`
  - QuoteController - Type-hints concrete QuoteService, EmailService, PdfService
  - QuoteService - `protected ?TaxEngineRouter $taxEngine` (concrete class)
- **Fix:** Type-hint interfaces, not concrete classes

### Medium Priority Issues (P2)

#### 7.5 Excessive Facade Usage - 601+ Occurrences
- [ ] **MEDIUM** - Framework coupling
- **Issue:** Overuse of `Auth::`, `DB::`, `Log::`, `Cache::` static facades
- **Impact:** Makes testing harder, tight framework coupling
- **Fix:** Inject dependencies where possible

#### 7.6 Missing Repository Pattern - 95% of Models
- [ ] **MEDIUM** - Data access coupling
- **Issue:** Only 2 repositories (TicketRepository, InvoiceRepository)
- **Impact:** Services directly call Eloquent static methods
- **Fix:** Create repositories for major entities

---

## 8. Performance Issues

**Severity:** 🔴 P0-CRITICAL to 🟡 P2-MEDIUM
**Total Issues:** 12
**Status:** Not Started

### Critical Performance Issues (P0)

#### 8.1 Client Dashboard - 12+ Queries in Trend Loops
- [ ] **CRITICAL** - Major performance impact
- **File:** [app/Livewire/Client/Dashboard.php:378-435](app/Livewire/Client/Dashboard.php#L378)
- **Issue:** `getTicketTrends()` executes 12 queries (6 months × 2 queries)
- **Impact:** Slow dashboard rendering
- **Fix:** Use single aggregate query with date grouping

#### 8.2 Email Bulk Operations - No Chunking, N+1
- [ ] **CRITICAL** - Memory and performance
- **File:** [app/Livewire/Email/Inbox.php:258-298](app/Livewire/Email/Inbox.php#L258)
- **Issue:** Loads all selected messages into memory, then N+1 on operations
- **Impact:** Could crash on large selections
- **Fix:** Use `chunk(100)` for bulk operations

#### 8.3 NotificationService - Preferences in Loop
- [ ] **CRITICAL** - N+1 queries
- **File:** [app/Services/NotificationService.php](app/Services/NotificationService.php) (multiple locations)
- **Issue:** `NotificationPreference::getOrCreateForUser()` called per user in loop
- **Impact:** 10 watchers = 10+ extra queries
- **Fix:** Pre-load all user preferences before loop

### High Priority Performance Issues (P1)

#### 8.4 Invoice/Payment Totals - 4 Separate Queries
- [ ] **HIGH** - Inefficient aggregation
- **File:** [app/Livewire/Financial/InvoiceIndex.php:136-144](app/Livewire/Financial/InvoiceIndex.php#L136)
- **Issue:** Clones base query 4 times for different status sums
- **Fix:** Use single query with conditional aggregation (CASE WHEN)

#### 8.5 Active Projects - Queries in Map Function
- [ ] **HIGH** - N+1 in collection mapping
- **File:** [app/Livewire/Client/Dashboard.php:516-529](app/Livewire/Client/Dashboard.php#L516)
- **Issue:** 2 count queries per project in map function
- **Fix:** Eager load task counts

#### 8.6 Spending Trends - 6 Queries in Loop
- [ ] **HIGH** - Inefficient trend calculation
- **File:** [app/Livewire/Client/Dashboard.php:412-435](app/Livewire/Client/Dashboard.php#L412)
- **Issue:** One sum query per month
- **Fix:** Single aggregate query grouped by month

### Medium Priority Performance Issues (P2)

#### 8.7-8.9 Missing Caching
- [ ] **MEDIUM** - Implement caching strategy
- **Issue:** Most Livewire components don't cache expensive operations
- **Good example:** TeamPerformance widget uses 5-minute cache
- **Fix:** Add caching to dashboard widgets, client lists, notification counts

#### 8.10 Computed Properties Not Consistently Used
- [ ] **MEDIUM** - Re-calculation overhead
- **Issue:** Some expensive operations not marked with `#[Computed]` attribute
- **Fix:** Use computed properties for expensive calculations

#### 8.11-8.12 Missing Query Limits
- [ ] **LOW** - Potential large data sets
- **Issue:** Some queries lack limits (e.g., contract list, invoice list)
- **Fix:** Add consistent limits or pagination

### Performance Strengths ✅

- ✅ **Excellent database indexing** - 20+ indexes on tickets, 13 on invoices, comprehensive coverage
- ✅ Good use of eager loading with `with()` in many places
- ✅ TeamPerformance widget shows proper caching pattern

---

## 9. Error Handling Antipatterns

**Severity:** 🔴 P0-CRITICAL to 🟡 P2-MEDIUM
**Total Issues:** 130+
**Status:** Not Started

### Critical Error Handling Issues (P0)

#### 9.1 Empty Catch Block - AlertPanel
- [ ] **CRITICAL** - Silent failure
- **File:** [app/Livewire/Dashboard/Widgets/AlertPanel.php:178-179](app/Livewire/Dashboard/Widgets/AlertPanel.php#L178)
- **Issue:** Completely empty catch block, NO logging
- **Impact:** Backup/disk space alerts fail invisibly
- **Fix:** Add logging at minimum

### High Priority Issues (P1)

#### 9.2 Payment Processing - Generic Exception Catch
- [ ] **HIGH** - Poor user experience
- **File:** [app/Domains/Financial/Services/PortalPaymentService.php:176-185](app/Domains/Financial/Services/PortalPaymentService.php#L176)
- **Issue:** All exceptions show same generic message
- **Impact:** Can't distinguish card decline from network error
- **Fix:** Catch specific exceptions separately

#### 9.3 Authentication Service - Generic Exception Catch
- [ ] **HIGH** - Security and UX issue
- **File:** [app/Domains/Security/Services/PortalAuthService.php:146-154](app/Domains/Security/Services/PortalAuthService.php#L146)
- **Issue:** Same message for all auth failures
- **Impact:** Can't distinguish invalid credentials from system errors
- **Fix:** Handle specific exception types

#### 9.4 Portal API Controllers - 34+ Generic Catches
- [ ] **HIGH** - Widespread pattern
- **Files:** Multiple controllers in `app/Domains/Client/Controllers/Api/Portal/`
- **Pattern:** `catch (Exception $e) { return $this->handleException($e, 'context'); }`
- **Issue:** Loses error specificity
- **Fix:** Catch specific exceptions, provide actionable error messages

### Medium Priority Issues (P2)

#### 9.5-9.10 Silent Failures with Comments
- [ ] **MEDIUM** - Review each case
- **Examples:**
  - CommandPaletteService:1112 - "Silent fail" with no logging
  - SidebarConfigProvider:377,852,1153 - "Silently handle database issues" with no logging
  - UpdateTaxData:495 - Uses `Log::debug` instead of `Log::warning` (invisible in production)
  - CompanyObserver:18-21 - No logging at all
- **Fix:** Add appropriate logging level for production visibility

#### 9.11 Using Exceptions for Control Flow
- [ ] **MEDIUM** - Antipattern
- **Examples:**
  - CompanyMailSettings:92-98 - Uses exception to check if data is encrypted
  - StripeSubscriptionService:577-609 - Parses exception message for "No such price"
  - DynamicMailConfigService:49-57 - Exception as fallback mechanism
- **Fix:** Use explicit checks instead of exception-based flow

### Statistics

- **Generic Exception catches:** 130+ instances
- **Empty catch blocks:** 1 (critical)
- **Silent failures with comments:** 15+
- **Exception-based control flow:** 5+

---

## 10. Testing Gaps

**Severity:** 🔴 P0-CRITICAL to 🟠 P1-HIGH
**Total Issues:** Multiple categories
**Status:** Not Started

### Critical Testing Gaps (P0)

#### 10.1 Financial Services - NO TESTS
- [ ] **CRITICAL** - Revenue/payment risk
- **Missing tests for:**
  - PaymentProcessingService - Transaction handling
  - PaymentService - Core payment logic
  - PaymentApplicationService - Payment-to-invoice mapping
  - InvoiceService - Invoice generation
  - RecurringBillingService - Subscription billing
  - DunningAutomationService - Collections
  - QuoteService - Quote generation
  - CreditNoteProcessingService - Credit notes
- **Impact:** No safety net for critical financial operations
- **Priority:** Start with payment processing

#### 10.2 Tax Calculation - NO TESTS
- [ ] **CRITICAL** - Compliance risk
- **Missing tests for:**
  - All TaxEngine services (15+ files)
  - All VoIPTax services (9 files)
  - Tax rate calculations
  - Multi-jurisdiction handling
- **Impact:** Tax miscalculations could cause compliance issues
- **Priority:** High - tax logic must be tested

#### 10.3 Security Services - NO TESTS
- [ ] **CRITICAL** - Security risk
- **Missing tests for:**
  - Authentication services (11 services)
  - Permission handling
  - Access control
  - Compliance checks
- **Impact:** Security vulnerabilities could go undetected
- **Priority:** High - security must be tested

### High Priority Testing Gaps (P1)

#### 10.4 Service Test Coverage - 3%
- [ ] **HIGH** - Inadequate coverage
- **Stats:** 7 tests for 216 services
- **Missing:** 209 services have NO tests
- **Fix:** Add service tests for core business logic

#### 10.5 Controller Test Coverage - 4%
- [ ] **HIGH** - No HTTP testing
- **Stats:** 7 tests for 163 controllers
- **Missing:** 156 controllers have NO tests
- **Fix:** Add HTTP tests for critical endpoints

#### 10.6 Livewire Test Coverage - 4%
- [ ] **HIGH** - UI components untested
- **Stats:** 5 tests for 120 components
- **Missing:** 115 components have NO tests
- **Fix:** Test component rendering and interactions

#### 10.7 Model Test Coverage - 91%
- [ ] **MEDIUM** - Good coverage but wrong type
- **Stats:** 93 of 102 models tested
- **Issue:** All tests use database (integration tests, not unit tests)
- **Missing:** 9 models without any tests
- **Fix:** Keep coverage, but true unit tests needed for business logic

### Testing Antipatterns

#### 10.8 Unit Tests Using Database - 100%
- [ ] **HIGH** - Not true unit tests
- **Issue:** ALL "unit tests" use `RefreshDatabase` trait
- **Impact:** Slow execution, brittle tests
- **Fix:** Use mocks/stubs for true unit testing

#### 10.9 Weak Assertions
- [ ] **MEDIUM** - Low test value
- **Examples:**
  - Tests only check `assertIsArray()` without verifying content
  - Tests only check `assertInstanceOf()` without behavior
  - Tests only check fillable attributes exist
- **Fix:** Test behavior, not implementation

#### 10.10 Missing Edge Case Tests
- [ ] **HIGH** - Incomplete coverage
- **Missing tests for:**
  - Negative amounts, null values
  - Invalid status transitions
  - Concurrent operations
  - Division by zero
  - Date boundaries
  - Very large values
  - Duplicate submissions
  - Race conditions
- **Fix:** Add edge case test suites

### Summary Statistics

- **Test/Code Ratio:** 11.7% (121 tests for 1,036 files)
- **Service Coverage:** 3% (7/216)
- **Controller Coverage:** 4% (7/163)
- **Livewire Coverage:** 4% (5/120)
- **Model Coverage:** 91% (93/102)
- **True Unit Tests:** <1% (most use database)

---

## 11. Blade/View Antipatterns

**Severity:** 🔴 P0-CRITICAL to 🟡 P2-MEDIUM
**Total Issues:** 28+ database queries, 19 large files
**Status:** Not Started

### Critical View Issues (P0)

#### 11.1 Database Queries in Views - 28+ Files
- [ ] **CRITICAL** - Performance and architecture violation
- **Examples:**
  - [marketing/campaigns/index.blade.php:83](resources/views/marketing/campaigns/index.blade.php#L83) - `\App\Models\User::where()->get()`
  - [physical-mail/contacts.blade.php:30-37,102](resources/views/physical-mail/contacts.blade.php#L30) - Multiple queries in view
  - [physical-mail/index.blade.php:42](resources/views/physical-mail/index.blade.php#L42) - Count query in view
  - [leads/create.blade.php:114,147](resources/views/leads/create.blade.php#L114) - LeadSource and User queries
  - [layouts/app.blade.php:35](resources/views/layouts/app.blade.php#L35) - Session-based client query
  - [tickets/edit.blade.php:48,112,181](resources/views/tickets/edit.blade.php#L48) - Multiple queries
  - Plus 22 more files
- **Impact:** N+1 queries, slow page loads, testing difficulties
- **Fix:** Move ALL queries to controllers/view composers

#### 11.2 Massive Contract Creation View - 1,919 Lines
- [ ] **CRITICAL** - Unmaintainable
- **File:** [resources/views/financial/contracts/create.blade.php](resources/views/financial/contracts/create.blade.php)
- **Size:** 1,919 lines (1,463 lines of JavaScript!)
- **Issues:** 76% of file is embedded JavaScript
- **Fix:**
  - [ ] Extract JavaScript to separate file
  - [ ] Break into Blade components
  - [ ] Use Livewire component for dynamic sections

### High Priority View Issues (P1)

#### 11.3-11.7 Large View Files (>1,000 lines)
- [ ] **HIGH** - Break into components
- **Files:**
  - [admin/contract-views/configurator.blade.php](resources/views/admin/contract-views/configurator.blade.php) - 1,784 lines
  - [admin/contract-forms/designer.blade.php](resources/views/admin/contract-forms/designer.blade.php) - 1,728 lines
  - [admin/navigation/builder.blade.php](resources/views/admin/navigation/builder.blade.php) - 1,604 lines
  - [clients/it-documentation/create-original.blade.php](resources/views/clients/it-documentation/create-original.blade.php) - 1,309 lines
  - [admin/contract-workflows/designer.blade.php](resources/views/admin/contract-workflows/designer.blade.php) - 1,307 lines

#### 11.8 Inline JavaScript - 694 Files with @php Blocks
- [ ] **HIGH** - Extract to separate files
- **Issue:** Business logic in views, massive JavaScript blocks
- **Examples:**
  - Contract creation: 1,463 lines of Alpine.js
  - Physical mail contacts: Inline function definitions
  - Reports: Chart/export functions
- **Fix:** Move to separate `.js` files, use proper asset compilation

### Medium Priority View Issues (P2)

#### 11.9 Inline CSS - 20 Files with <style> Blocks
- [ ] **MEDIUM** - Extract to CSS files
- **Examples:**
  - Contract creation view with custom classes
  - Report views with chart styling
  - PDF views with layout styles
- **Fix:** Use Tailwind utilities or extract to CSS files

#### 11.10 Business Logic in @php Blocks - 694 Files
- [ ] **MEDIUM** - Move to controllers
- **Examples:**
  - Query building in views
  - Data filtering/transformation
  - Status calculations
- **Fix:** Prepare data in controllers/view composers

#### 11.11 Duplicate UI Patterns
- [ ] **MEDIUM** - Create reusable components
- **Patterns:**
  - Button styles: 18+ identical instances
  - Card containers: 29+ identical instances
  - User dropdowns: 5+ identical instances
- **Fix:** Create Blade components (already have 111 components, use them!)

#### 11.12 Complex Conditionals - 7 Files with 4+ @elseif Chains
- [ ] **MEDIUM** - Simplify or use presenter classes
- **Files:** recurring/show, quotes/approve, invoices/edit, tickets/*
- **Fix:** Use view composers or presenter pattern

### View Statistics

- **Total Blade files:** 1,110
- **Files with DB queries:** 28+
- **Large files (>500 lines):** 19
- **Files with `@php` blocks:** 694
- **Files with `<script>` tags:** 30+
- **Files with `<style>` tags:** 20
- **JavaScript functions:** 983 instances

---

## 12. Code Smells

**Severity:** 🟡 P2-MEDIUM to 🟢 P3-LOW
**Total Issues:** 150+ TODOs, 50+ magic numbers
**Status:** Not Started

### High Priority Code Smells (P1)

#### 12.1 Stub Controllers - 10+ Controllers with NO Implementation
- [ ] **HIGH** - Complete or remove
- **Files:**
  - [ExportController](app/Domains/Financial/Controllers/ExportController.php) - All methods are TODOs
  - [RecurringInvoiceController](app/Domains/Financial/Controllers/RecurringInvoiceController.php) - All CRUD is TODOs
  - [BudgetController](app/Domains/Financial/Controllers/BudgetController.php) - All functionality TODOs
  - [ForecastController](app/Domains/Financial/Controllers/ForecastController.php) - All forecasting TODOs
  - [PricingController](app/Domains/Financial/Controllers/PricingController.php) - Stub implementation
  - [VendorController](app/Domains/Financial/Controllers/VendorController.php) - All vendor management TODOs
  - Plus 4+ more
- **Fix:** Either implement or remove from routing

#### 12.2 TODO Comments - 150+ Instances
- [ ] **HIGH** - Track and complete
- **Major concentrations:**
  - Financial controllers: 60+ TODOs for core features
  - Portal features: 10+ TODOs for file attachments, exports
  - Email services: 5+ TODOs for attachment handling
  - Notification channels: 5+ TODOs for SMS sending
  - Workflow services: 5+ TODOs for escalation
- **Fix:** Create issues for each TODO, prioritize, implement or remove

### Medium Priority Code Smells (P2)

#### 12.3 Long Parameter Lists - 8+ Critical Cases
- [ ] **MEDIUM** - Refactor to parameter objects
- **Examples:**
  - [VoIPTaxCalculationService:84](app/Domains/Financial/Services/VoIPTaxCalculationService.php#L84) - `calculateStateTaxes()` has 7 parameters
  - [VoIPTaxCalculationService:127](app/Domains/Financial/Services/VoIPTaxCalculationService.php#L127) - `calculateLocalTaxes()` has 7 parameters
  - [ProductPricingService:110](app/Domains/Financial/Services/ProductPricingService.php#L110) - `applyPricingRule()` has 6 parameters
  - [PortalPaymentService:97](app/Domains/Financial/Services/PortalPaymentService.php#L97) - Anonymous function captures 8 variables
  - [KpiGrid:108](app/Livewire/Dashboard/Widgets/KpiGrid.php#L108) - `calculateRevenue()` has 8 parameters
  - [FinancialKpis:164,180](app/Livewire/Dashboard/Widgets/FinancialKpis.php#L164) - `buildKpiItem()` has 7 parameters
- **Fix:** Create parameter objects or DTOs

#### 12.4 Magic Numbers - 50+ Instances
- [ ] **MEDIUM** - Extract to constants
- **Examples:**
  - [PortalPaymentService:49-61](app/Domains/Financial/Services/PortalPaymentService.php#L49) - Fee percentages, limits hardcoded
  - [ContractApprovalService:249,261,273](app/Domains/Contract/Services/ContractApprovalService.php#L249) - Approval thresholds (5000, 25000, 100000)
  - [PhysicalMailContentAnalyzer:65](app/Domains/PhysicalMail/Services/PhysicalMailContentAnalyzer.php#L65) - Page estimation `/ 3000`
  - [ContractAnalyticsService:229](app/Domains/Contract/Services/ContractAnalyticsService.php#L229) - Weeks calculation `* 4.33`
  - [DashboardCacheService:150](app/Domains/Core/Services/DashboardCacheService.php#L150) - Cache TTL `300`
  - [UpdateTaxData:386-436](app/Console/Commands/UpdateTaxData.php#L386) - 50+ lines of hardcoded county codes
  - [ForecastController:105+](app/Domains/Financial/Controllers/ForecastController.php#L105) - Random ranges, percentages
- **Fix:** Extract to configuration or class constants

#### 12.5 Deeply Nested Conditionals - 15+ Instances
- [ ] **MEDIUM** - Simplify with guard clauses
- **Examples:**
  - [PhysicalMailLetter:138-145](app/Domains/PhysicalMail/Models/PhysicalMailLetter.php#L138) - 4 levels of nesting
  - [ExceptionHandlingMiddleware:136-140](app/Middleware/ExceptionHandlingMiddleware.php#L136) - Nested with recursion
- **Fix:** Use early returns, extract methods

#### 12.6 Dead/Commented Code
- [ ] **MEDIUM** - Remove and rely on version control
- **Examples:**
  - [TestPhysicalMail:88-219](app/Console/Commands/TestPhysicalMail.php#L88) - Large CSS blocks commented
  - [ContractService:1320](app/Domains/Contract/Services/ContractService.php#L1320) - Disabled auto-generation code
  - 10,278 comment instances across 753 files (some legitimate, some dead code)
- **Fix:** Clean up, use git for history

### Low Priority Code Smells (P3)

#### 12.7 Inconsistent Naming
- [ ] **LOW** - Standardize conventions
- **Issue:** Mix of snake_case and camelCase in some contexts
- **Fix:** Follow PSR standards consistently

#### 12.8 Feature Envy
- [ ] **LOW** - Refactor coupling
- **Examples:**
  - KpiGrid widget heavily uses DashboardCacheService
  - PortalPaymentService orchestrates many services
- **Fix:** Consider facade or mediator pattern

#### 12.9 Empty Collections with TODOs
- [ ] **LOW** - Implement or remove
- **Pattern:** `collect(); // TODO: Load from database` in 20+ locations
- **Fix:** Complete implementation

---

## Appendix A: Statistics Summary

### Codebase Overview
- **PHP Files:** 1,036
- **Blade Files:** 1,110
- **Test Files:** 121
- **Model Files:** 102
- **Service Files:** 216
- **Controller Files:** 163
- **Livewire Components:** 120

### Antipattern Distribution
```
P0-CRITICAL:     17 issues  (█████████░░░░░░░░░░░)
P1-HIGH:         43 issues  (█████████████████████)
P2-MEDIUM:       68 issues  (████████████████████████████████)
P3-LOW:          25+ issues (████████████)
```

### Files by Size
- **>2000 lines:** 3 files (ContractService, NavigationService, ContractGenerationService)
- **>1500 lines:** 7 files
- **>1000 lines:** 27 files
- **>500 lines:** 50+ files

### Test Coverage
- **Overall:** 11.7%
- **Services:** 3%
- **Controllers:** 4%
- **Livewire:** 4%
- **Models:** 91%

---

## Appendix B: Priority Matrix

### Immediate Action Required (P0-CRITICAL)

**Financial Security:**
1. WidgetService SQL injection
2. Contact mass assignment vulnerability
3. Payment processing untested

**Performance:**
4. Dashboard trend calculation loops
5. Email bulk operations
6. NotificationService N+1

**Data Integrity:**
7. InvoiceStatus N+1 query
8. VoIPTaxReporting N+1
9. Client fillable security fields

**Architecture:**
10. Setting model (God Object)
11. Fat controllers (7 files >1000 lines)

### Next 30 Days (P1-HIGH)

**Code Quality:**
- Extract service locator pattern (93 files)
- Create missing service interfaces (50+ services)
- Add tests for critical services

**Performance:**
- Fix remaining N+1 queries (9 issues)
- Implement caching strategy
- Add chunking for bulk operations

**Architecture:**
- Break up fat controllers
- Split God Object services
- Extract code duplication

### Next 60 Days (P2-MEDIUM)

**Testing:**
- Increase service test coverage
- Add controller/Livewire tests
- Implement edge case testing

**Views:**
- Extract database queries from Blade
- Break up large view files
- Create reusable components

**Code Quality:**
- Complete TODO implementations
- Extract magic numbers
- Simplify complex conditionals

### Ongoing (P3-LOW)

- Standardize naming conventions
- Remove dead code
- Monitor facade usage
- Improve error messages

---

## Appendix C: Tracking Progress

### How to Use This Document

1. **Initial Assessment:** Review each section, understand the issues
2. **Prioritization:** Work through P0 → P1 → P2 → P3
3. **Track Progress:** Check off ✅ boxes as issues are resolved
4. **Update Regularly:** Add notes, dates, and assignees as needed
5. **Review:** Weekly review of P0/P1, monthly review of all issues

### Checkbox Legend
- [ ] Not Started
- [x] In Progress
- [✅] Completed
- [⚠️] Blocked
- [🔄] Recurring Issue

### Adding Notes
Use markdown blockquotes to add notes:
> **Note (2025-10-20):** Started work on InvoiceStatus fix
> **Blocked:** Waiting for database migration

---

## Appendix D: Recommended Tools

### Static Analysis
- **PHPStan** - Detect type errors, N+1 queries
- **Psalm** - Find bugs, security issues
- **PHP CS Fixer** - Enforce coding standards
- **Laravel Pint** - Already in use ✅

### Performance
- **Laravel Telescope** - Monitor queries, performance
- **Laravel Debugbar** - Already installed ✅
- **Blackfire** - Profile performance bottlenecks

### Testing
- **PHPUnit** - Already in use ✅
- **Pest** - Consider migration for better DX
- **Laravel Dusk** - Browser testing

### Code Quality
- **SonarQube** - Already configured ✅
- **Code Climate** - Maintainability analysis
- **PHP Insights** - Code quality metrics

---

## Appendix E: Useful Commands

### Find Antipatterns
```bash
# Find N+1 queries (missing eager loading)
grep -r "foreach.*->get()" app/

# Find service locator pattern
grep -r "app(" app/ | grep -v "// " | wc -l

# Find long files
find app/ -name "*.php" -exec wc -l {} \; | sort -rn | head -20

# Find TODO comments
grep -r "TODO\|FIXME" app/ | wc -l

# Find magic numbers
grep -r "[0-9]\{3,\}" app/ --include="*.php"
```

### Run Tests
```bash
# All tests
composer test

# With coverage
composer test:coverage

# Specific suite
composer test:unit
composer test:feature
composer test:financial
```

### Static Analysis
```bash
# PHPStan (if installed)
vendor/bin/phpstan analyse

# Laravel Pint
./vendor/bin/pint

# Check for security issues
composer audit
```

---

## Document Change Log

| Date | Changed By | Changes |
|------|-----------|---------|
| 2025-10-15 | Claude Code | Initial document creation |
|  |  |  |
|  |  |  |

---

**End of Document**

*This is a living document. Update regularly as antipatterns are discovered and resolved.*
