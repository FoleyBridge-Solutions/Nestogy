# Implementation Plan for High Priority Anti-Patterns

## **Priority 1: Refactor God Classes**

### **1.1 TicketController Decomposition (2,086 lines, 76 methods)**

**Current Issues:**
- 45 public methods handling everything from CRUD to time tracking, billing, exports
- Violates Single Responsibility Principle
- Difficult to test and maintain

**Target Architecture:**
```
app/Domains/Ticket/
  Controllers/
    TicketController.php          (Index, Create, Store, Show, Edit, Update, Destroy)
    TicketStatusController.php    (updateStatus, updatePriority, assign)
    TicketResolutionController.php (resolve, reopen)
    TicketCommentController.php   (addReply, storeReply)
    TicketTimeTrackingController.php (startSmartTimer, pauseTimer, stopTimer, getBillingDashboard)
    TicketExportController.php    (export, generatePdf)
    TicketSchedulingController.php (schedule)
    TicketMergeController.php     (merge)
    TicketSearchController.php    (search, getViewers)
  Requests/
    StoreTicketRequest.php
    UpdateTicketRequest.php
    AssignTicketRequest.php
    ScheduleTicketRequest.php
    MergeTicketRequest.php
```

**Implementation Steps:**
1. Create domain-specific Request classes to extract validation logic (currently in controller)
2. Extract time tracking endpoints (7 methods) → `TicketTimeTrackingController`
3. Extract status management (3 methods) → `TicketStatusController`
4. Extract resolution/reopening (2 methods) → `TicketResolutionController`
5. Extract export/PDF (2 methods) → `TicketExportController`
6. Extract scheduling (1 method) → `TicketSchedulingController`
7. Extract merge (1 method) → `TicketMergeController`
8. Extract search (2 methods) → `TicketSearchController`
9. Keep core CRUD (7 methods) in main `TicketController`
10. Update routes in `app/Domains/Ticket/routes.php`
11. Inject services via constructor (already partially done)
12. Move filter methods (11 protected methods) to dedicated `TicketQueryBuilder` or service

**Services Already Available:**
- ✅ `CommentService`
- ✅ `ResolutionService`
- ✅ `TimeTrackingService`
- ✅ `WorkTypeClassificationService`

### **1.2 ContractService Decomposition (4,043 lines, 15+ public methods)**

**Current Issues:**
- Massive service handling CRUD, lifecycle, analytics, automation
- Already has 16 specialized services but this one still too large

**Existing Specialized Services:**
```
ContractAnalyticsService
ContractApprovalService
ContractAutomationService
ContractClauseService
ContractGenerationService
ContractLifecycleService
ContractTemplateService
```

**Refactoring Strategy:**
1. **Extract to `ContractQueryService`:**
   - `getContracts()` 
   - `getContractsByStatus()`
   - `searchContracts()`
   - `getExpiringContracts()`
   - `getContractsDueForRenewal()`

2. **Extract to `ContractStatisticsService`:**
   - `getDashboardStatistics()`
   - All analytics/metrics methods

3. **Keep in `ContractService`:**
   - `createContract()`
   - `updateContract()`
   - `deleteContract()`
   - `createFromBuilder()`

4. **Move to `ContractLifecycleService` (already exists):**
   - `activateContract()`
   - `terminateContract()`
   - `suspendContract()`
   - `reactivateContract()`

5. **Extract to `ContractRetryService`:**
   - `retryContractComponent()`
   - Error handling and retry logic

### **1.3 NavigationService Decomposition (3,715 lines)**

**Current Structure Analysis Needed:**
- Extract sidebar configuration → `SidebarConfigProvider` (already exists at 1,492 lines!)
- Extract command palette → `CommandPaletteService` (already exists at 1,575 lines!)
- Extract breadcrumb generation → `BreadcrumbService`
- Extract menu rendering → `MenuRenderingService`
- Keep core navigation state in `NavigationService` (client selection, context)

**Implementation:**
1. Analyze method distribution in NavigationService
2. Create `NavigationMenuBuilder` for menu construction
3. Create `NavigationStateManager` for session-based client selection
4. Create `NavigationPermissionResolver` for permission-based menu filtering
5. Slim down to < 500 lines focusing on coordination

### **1.4 DashboardController (1,818 lines)**

**Refactoring Strategy:**
1. Extract to `DashboardWidgetController` - individual widget endpoints
2. Extract to `DashboardDataService` (already exists at 1,211 lines - also needs refactoring!)
3. Create widget-specific services:
   - `TicketMetricsService`
   - `FinancialMetricsService`
   - `ClientMetricsService`
   - `AssetMetricsService`

---

## **Priority 2: Replace Direct Facade Calls with Dependency Injection**

### **2.1 Database (DB) Facade Usage**

**Current State:**
- 100+ instances of `DB::` facade calls
- Mixed transaction patterns
- Hard to mock in tests

**Implementation:**
1. **Create Repository Layer:** Use existing `ProjectRepository` as template
   ```php
   app/Domains/Ticket/Repositories/
     TicketRepository.php
     TicketCommentRepository.php
     TicketTimeEntryRepository.php
   
   app/Domains/Financial/Repositories/
     InvoiceRepository.php
     QuoteRepository.php
     PaymentRepository.php
   
   app/Domains/Client/Repositories/
     ClientRepository.php
     ContactRepository.php
   ```

2. **Repository Pattern Structure:**
   ```php
   class TicketRepository
   {
       public function findWithRelations(int $id, array $relations = []): ?Ticket
       public function getFilteredQuery(array $filters): Builder
       public function getPaginated(array $filters, int $perPage): LengthAwarePaginator
       public function getByClient(int $clientId): Collection
       public function getOverdueBySla(): Collection
       // Raw queries moved here
   }
   ```

3. **Standardize Transaction Management:**
   - Create `DatabaseTransactionManager` wrapper
   - Replace all `DB::beginTransaction()` patterns with single approach
   - Use `DB::transaction(callable)` as standard (already used in some places)

4. **Inject Repositories into Services:**
   ```php
   class TicketService
   {
       public function __construct(
           private TicketRepository $repository,
           private NotificationService $notificationService,
           private SLAService $slaService
       ) {}
   }
   ```

### **2.2 Authentication (Auth) Facade Usage**

**Current State:**
- 50+ instances of `Auth::user()` in Livewire components
- 100+ instances of `Auth::id()` and `Auth::check()`
- Hard to test, tight coupling

**Implementation:**

1. **Create Base Livewire Component with User Injection:**
   ```php
   app/Livewire/Concerns/WithAuthenticatedUser.php
   ```
   ```php
   trait WithAuthenticatedUser
   {
       public User $user;
       public int $companyId;
       
       public function bootWithAuthenticatedUser(): void
       {
           $this->user = auth()->user();
           $this->companyId = $this->user->company_id;
       }
   }
   ```

2. **Update All Livewire Components:**
   - Replace `Auth::user()` with `$this->user`
   - Replace `Auth::user()->company_id` with `$this->companyId`
   - Replace `Auth::id()` with `$this->user->id`

3. **Controller User Injection:**
   ```php
   // Before
   public function index(Request $request)
   {
       $tickets = Ticket::where('company_id', auth()->user()->company_id)->get();
   }
   
   // After
   public function index(Request $request)
   {
       $user = $request->user();
       $tickets = $this->ticketRepository->getByCompany($user->company_id);
   }
   ```

4. **Create Company Scope Helper:**
   ```php
   app/Domains/Core/Services/CompanyContextService.php
   ```
   - Inject into controllers/services
   - Provides `getCurrentCompanyId()`, `getCurrentUser()`, `scopeToCompany(Builder $query)`

---

## **Priority 3: Fix N+1 Query Problems**

### **3.1 Identification Strategy**

**Tools:**
1. Enable Laravel Debugbar in development
2. Use `DB::enableQueryLog()` in critical paths
3. Install `barryvdh/laravel-debugbar` (already installed)

**Common Patterns Found:**
```php
// ProductController.php:124 - Needs eager loading
$usageCount = DB::table('invoice_items')
    ->where('product_id', $product->id)
    ->count();

// TicketController - Good example already present
$tickets = $query->with([
    'client', 'contact', 'assignee', 'asset', 'template', 'workflow', 'watchers'
])->paginate();
```

### **3.2 Implementation Steps**

1. **Audit Top Controllers/Services:**
   - TicketController
   - InvoiceController
   - ClientController
   - DashboardController
   - All Livewire components

2. **Create Query Scopes for Common Patterns:**
   ```php
   // In Ticket model
   public function scopeWithAllRelations($query)
   {
       return $query->with([
           'client:id,name,company_id',
           'assignee:id,name,email',
           'comments.author:id,name',
           'timeEntries.user:id,name',
           'watchers.user:id,name'
       ]);
   }
   ```

3. **Use Lazy Eager Loading Strategically:**
   ```php
   // When relations are conditionally needed
   if ($includeComments) {
       $tickets->load('comments.author');
   }
   ```

4. **Create Dashboard-Specific DTOs:**
   - Use `select()` to load only needed columns
   - Use `selectRaw()` for aggregates
   - Leverage existing `DashboardQueryOptimizer` trait (found in codebase)

5. **Document Eager Loading Requirements:**
   - Add PHPDoc to repository methods specifying what's eager loaded
   - Create `docs/architecture/query-optimization.md`

---

## **Priority 4: Implement Specific Exception Handling**

### **4.1 Current Broad Catches**

**Files with Most Generic Catches:**
- `CDRProcessingService.php` - 4 catches
- `TexasComptrollerApiClient.php` - 4 catches
- `TaxCloudV3ApiClient.php` - 4 catches
- `PortalPaymentService.php` - 9 catches
- `ClientPortalService.php` - 9 catches
- `PortalAuthService.php` - 11 catches

### **4.2 Existing Exception Infrastructure**

**Already Available:**
```
✅ BaseException (abstract)
✅ TicketException + 10 specialized exceptions
✅ QuoteException hierarchy (7 exceptions)
✅ Financial exceptions (Tax, API-specific)
✅ ClientException
✅ AssetException
✅ ProjectException
```

### **4.3 Implementation Strategy**

1. **Create Missing Domain Exceptions:**
   ```php
   app/Domains/Integration/Exceptions/
     IntegrationException (exists)
     ApiConnectionException
     ApiRateLimitException
     ApiAuthenticationException
     DataSyncException
   
   app/Domains/Financial/Exceptions/
     PaymentProcessingException
     StripeException
     PaymentGatewayException
     InvoiceGenerationException
   ```

2. **Replace Generic Catches Systematically:**
   ```php
   // Before
   try {
       $result = $api->call();
   } catch (Exception $e) {
       Log::error('API failed', ['error' => $e->getMessage()]);
       return false;
   }
   
   // After
   try {
       $result = $api->call();
   } catch (GuzzleException $e) {
       throw new ApiConnectionException('Failed to connect to API', 503, $e);
   } catch (ClientException $e) {
       if ($e->getCode() === 429) {
           throw new ApiRateLimitException('Rate limit exceeded', 429, $e);
       }
       throw new ApiAuthenticationException('API authentication failed', 401, $e);
   } catch (Throwable $e) {
       // Only catch truly unexpected errors
       throw new IntegrationException('Unexpected API error', 500, $e);
   }
   ```

3. **Create Exception Hierarchy Documentation:**
   ```
   docs/architecture/exception-handling.md
   ```
   - When to use which exception
   - How to add context
   - Logging conventions
   - User message guidelines

4. **Update Exception Handler:**
   - Enhance `app/Exceptions/Handler.php` to properly route domain exceptions
   - Add specific rendering for API exceptions (JSON responses)
   - Add monitoring hooks for critical exceptions

5. **Add Retry Logic for Specific Exceptions:**
   ```php
   use Illuminate\Support\Facades\Retry;
   
   Retry::times(3)
       ->catch(ApiRateLimitException::class)
       ->sleep(1000)
       ->run(fn() => $api->call());
   ```

---

## **Priority 5: Move Business Logic from Models to Services**

### **5.1 Current Model Anti-Patterns**

**Models with Heavy Logic:**
- `Invoice.php` (1,546 lines) - static validation rules, currency formatting, status management
- `Product.php` (564 lines) - static validation, business rules
- `Setting.php` (1,546 lines) - configuration logic
- `Category.php` - tree building, color generation
- `Client.php` - custom rate calculations

**100+ Static Methods Found:**
- Validation rules (should be in Form Requests)
- Business calculations (should be in Services)
- Formatting helpers (should be in Value Objects)

### **5.2 Implementation Strategy**

#### **5.2.1 Extract Validation to Form Requests**

```php
// Before (in Model)
public static function getValidationRules(): array
{
    return ['field' => 'required|string'];
}

// After (in Request)
app/Domains/Financial/Requests/
  StoreInvoiceRequest.php
  UpdateInvoiceRequest.php
  
class StoreInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prefix' => 'nullable|string|max:10',
            'number' => 'required|integer|min:1',
            // ... validation rules
        ];
    }
}
```

#### **5.2.2 Create Value Objects for Formatting**

```php
app/Domains/Financial/ValueObjects/
  Money.php
  Currency.php
  InvoiceNumber.php

class Money
{
    public function __construct(
        public readonly float $amount,
        public readonly Currency $currency
    ) {}
    
    public function format(): string
    {
        return $this->currency->symbol() . number_format($this->amount, 2);
    }
    
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->code,
            'formatted' => $this->format()
        ];
    }
}
```

#### **5.2.3 Extract Business Logic to Services**

```php
// Before (in Invoice Model)
public function getBalance(): float
{
    return $this->amount - $this->paid_amount;
}

public function markAsPaid(float $amount): void
{
    $this->paid_amount += $amount;
    if ($this->getBalance() <= 0) {
        $this->status = 'Paid';
    }
    $this->save();
}

// After
app/Domains/Financial/Services/InvoicePaymentService.php

class InvoicePaymentService
{
    public function applyPayment(Invoice $invoice, Payment $payment): Invoice
    {
        DB::transaction(function() use ($invoice, $payment) {
            $invoice->payments()->attach($payment);
            
            $totalPaid = $invoice->payments()->sum('amount');
            $balance = $invoice->amount - $totalPaid;
            
            $invoice->update([
                'paid_amount' => $totalPaid,
                'status' => $balance <= 0 ? InvoiceStatus::PAID : $invoice->status,
                'paid_at' => $balance <= 0 ? now() : null
            ]);
            
            event(new InvoicePaymentApplied($invoice, $payment));
            
            return $invoice;
        });
    }
}
```

#### **5.2.4 Create Enums for Static Lists**

```php
// Before (in Model)
public static function getAvailableStatuses(): array
{
    return ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled'];
}

// After
app/Domains/Financial/Enums/InvoiceStatus.php

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::PAID => 'green',
            self::OVERDUE => 'red',
            self::CANCELLED => 'yellow',
        };
    }
}
```

#### **5.2.5 Slim Down Models**

**Target Model Structure:**
```php
class Invoice extends Model
{
    // Properties
    protected $fillable = [...];
    protected $casts = [
        'status' => InvoiceStatus::class,
        'date' => 'datetime',
        'amount' => Money::class, // Custom cast
    ];
    
    // Relationships
    public function client(): BelongsTo
    public function items(): HasMany
    public function payments(): HasMany
    
    // Scopes (query helpers only)
    public function scopeOverdue($query)
    public function scopePaid($query)
    
    // Accessors (presentation only)
    public function getIsOverdueAttribute(): bool
    
    // NO business logic
    // NO static validation
    // NO formatting beyond simple accessors
}
```

### **5.3 Migration Path**

1. **Phase 1: Create Services** (no breaking changes)
   - Create new service classes
   - Keep model methods as wrappers initially
   - Update new code to use services

2. **Phase 2: Add Deprecation Warnings**
   - Add `@deprecated` tags to model business methods
   - Add trigger_error() for deprecation notices in dev

3. **Phase 3: Update Existing Code**
   - Replace model method calls with service calls
   - Run tests after each domain update

4. **Phase 4: Remove Model Methods**
   - Remove deprecated methods
   - Final test run

---

## **Cross-Cutting Implementation Concerns**

### **Testing Strategy**

For each refactoring:

1. **Write Tests First (If Missing):**
   ```
   tests/Unit/Domains/Ticket/Services/TicketServiceTest.php
   tests/Feature/Domains/Ticket/Controllers/TicketControllerTest.php
   ```

2. **Maintain Existing Test Coverage:**
   - 117 test files currently exist
   - Ensure no regression after refactoring
   - Target 75% coverage (documented requirement)

3. **Use Test Doubles:**
   ```php
   public function test_creates_ticket_with_repository()
   {
       $repo = Mockery::mock(TicketRepository::class);
       $repo->shouldReceive('create')->once()->andReturn($ticket);
       
       $service = new TicketService($repo, ...);
       $result = $service->createTicket($data);
       
       $this->assertInstanceOf(Ticket::class, $result);
   }
   ```

### **Documentation Requirements**

Create/update:
1. `docs/architecture/controller-decomposition.md`
2. `docs/architecture/service-layer-patterns.md`
3. `docs/architecture/repository-pattern.md`
4. `docs/architecture/exception-handling.md`
5. `docs/architecture/dependency-injection.md`
6. Update `docs/CLAUDE.md` with new architectural decisions

### **Backward Compatibility**

1. **Maintain Routes:**
   - Keep existing route names/paths
   - Update route → controller mappings
   - Document any route changes

2. **API Contracts:**
   - Keep response formats identical
   - Maintain request validation
   - Add versioning if breaking changes needed

3. **Events:**
   - Keep existing event dispatching
   - Document new events added

### **Performance Monitoring**

1. **Add Query Monitoring:**
   ```php
   // In tests
   DB::enableQueryLog();
   $service->getTickets();
   $queries = DB::getQueryLog();
   $this->assertLessThan(5, count($queries));
   ```

2. **Benchmark Critical Paths:**
   - Dashboard loading
   - Invoice generation
   - Ticket list queries

3. **Monitor After Deployment:**
   - Use Laravel Telescope/Debugbar
   - Track N+1 query resolution success

---

## **Implementation Order Recommendation**

1. **Start with TicketController** (clear boundaries, services exist)
2. **Add Repository Layer** (enables better testing for everything)
3. **Fix Auth Facade in Livewire** (simple find/replace mostly)
4. **Add Specific Exceptions** (improve error handling throughout)
5. **Extract Model Business Logic** (requires services from step 2)
6. **Fix N+1 Queries** (easier with repositories)
7. **Decompose Large Services** (final step, builds on everything)

Each step provides immediate value and sets foundation for next step.
