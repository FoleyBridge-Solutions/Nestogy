# Architecture Refactoring Plan - Domain-Driven Design Alignment

## Current State Analysis

### Controller Distribution Issues
- **Total Controllers**: 151 (143 in domains + 8 in Http/Controllers)
- **Problem**: Mixed patterns with controllers in both traditional Laravel location and domains
- **Webhook Controllers**: Located in app/Http/Controllers/Api/Webhooks instead of Integration domain

### Domain Size Disparities
| Domain | Files | Services | Controllers | Health Status |
|--------|-------|----------|-------------|---------------|
| Financial | 102 | 41 | 36 | Over-developed |
| Client | 73 | 18 | 31 | Well-developed |
| Contract | 62 | 12 | 9 | Balanced |
| Core | 56 | 24 | 11 | Service-heavy |
| Ticket | 35 | 9 | 10 | Balanced |
| Email | 29 | 9 | 9 | Balanced |
| Security | 20 | 10 | 7 | Under-developed |
| PhysicalMail | 20 | 4 | 2 | Controller-light |
| Product | 20 | 2 | 10 | Service-light |
| Asset | 18 | 5 | 5 | Under-developed |
| Integration | 18 | 6 | 2 | Controller-light |
| Report | 13 | 6 | 4 | Under-developed |
| Knowledge | 11 | 3 | 3 | Under-developed |
| Lead | 8 | 3 | 1 | Under-developed |
| Marketing | 8 | 2 | 1 | Under-developed |

### Service Layer Organization
- **Total Services**: 156 (all properly in domains)
- **Issue**: No app/Services directory exists (good!), but service distribution is uneven
- **Core Domain**: 24 services (may be doing too much)
- **Financial Domain**: 41 services (needs sub-domain organization)

## Refactoring Strategy

### Phase 1: Controller Migration (Week 1)

#### 1.1 Webhook Controller Migration
```bash
# Move webhook controllers to Integration domain
app/Http/Controllers/Api/Webhooks/ → app/Domains/Integration/Http/Controllers/Webhooks/
```

**Files to migrate:**
- ConnectWiseWebhookController.php
- DattoWebhookController.php
- GenericRMMWebhookController.php
- NinjaOneWebhookController.php
- StripeWebhookController.php → app/Domains/Financial/Http/Controllers/Webhooks/

#### 1.2 API Controller Migration
```bash
# Move remaining API controllers to appropriate domains
app/Http/Controllers/Api/DocumentTemplateController.php → app/Domains/Knowledge/Http/Controllers/Api/
```

#### 1.3 Update Routes
- Update `routes/api.php` to reference new controller locations
- Ensure proper namespacing

### Phase 2: Domain Boundary Refinement (Week 2)

#### 2.1 Split Large Domains

**Financial Domain** (102 files → ~30 files each)
Split into sub-domains:
- `app/Domains/Billing/` - Invoicing, quotes, recurring billing
- `app/Domains/Payment/` - Payment processing, methods, gateways
- `app/Domains/Accounting/` - Expenses, tax, financial reports
- `app/Domains/Pricing/` - Products, pricing, discounts

**Core Domain** (56 files → focused domains)
Extract into specific domains:
- `app/Domains/Company/` - Company management
- `app/Domains/User/` - User management (if not in Security)
- `app/Domains/Settings/` - System settings
- Keep Core for truly cross-cutting concerns only

#### 2.2 Consolidate Small Domains

**Merge Related Domains:**
- Lead + Marketing → `app/Domains/CRM/`
- Knowledge + Report → `app/Domains/Intelligence/`
- Asset + Product → `app/Domains/Inventory/` (or keep separate if distinct)

### Phase 3: Service Layer Organization (Week 3)

#### 3.1 Service Categorization Pattern

Each domain should have:
```
app/Domains/{Domain}/
├── Services/
│   ├── Commands/     # Write operations
│   │   ├── Create{Entity}Service.php
│   │   ├── Update{Entity}Service.php
│   │   └── Delete{Entity}Service.php
│   ├── Queries/      # Read operations
│   │   ├── Get{Entity}Service.php
│   │   └── List{Entity}Service.php
│   ├── Processors/   # Business logic
│   │   └── {Entity}ProcessorService.php
│   └── Integrations/ # External integrations
│       └── {External}IntegrationService.php
```

#### 3.2 Financial Domain Service Reorganization

Current 41 services to be organized as:
```
app/Domains/Financial/Services/
├── Billing/
│   ├── Commands/
│   │   ├── CreateInvoiceService.php
│   │   ├── UpdateInvoiceService.php
│   │   └── SendInvoiceService.php
│   ├── Queries/
│   │   ├── GetInvoiceService.php
│   │   └── ListInvoicesService.php
│   └── Processors/
│       ├── InvoiceCalculatorService.php
│       └── RecurringBillingService.php
├── Payment/
│   ├── Commands/
│   │   └── ProcessPaymentService.php
│   ├── Queries/
│   │   └── GetPaymentHistoryService.php
│   └── Integrations/
│       ├── StripePaymentService.php
│       └── PayPalPaymentService.php
└── Tax/
    ├── Processors/
    │   ├── TaxCalculatorService.php
    │   └── TexasTaxService.php
    └── Queries/
        └── GetTaxRatesService.php
```

### Phase 4: Implementation Standards (Ongoing)

#### 4.1 Controller Standards

```php
namespace App\Domains\{Domain}\Http\Controllers;

use App\Domains\{Domain}\Http\Controllers\Concerns\{Domain}Authorization;
use App\Domains\{Domain}\Http\Requests\{Specific}Request;
use App\Domains\{Domain}\Services\Commands\{Action}Service;

class {Entity}Controller extends BaseDomainController
{
    use {Domain}Authorization;
    
    public function __construct(
        private readonly {Action}Service $service
    ) {}
    
    public function {action}({Specific}Request $request)
    {
        $this->authorize('{action}', {Entity}::class);
        
        return $this->service->execute($request->validated());
    }
}
```

#### 4.2 Service Standards

```php
namespace App\Domains\{Domain}\Services\{Category};

use App\Domains\{Domain}\Contracts\{Action}ServiceInterface;
use App\Domains\{Domain}\Models\{Entity};
use App\Domains\{Domain}\Events\{Entity}{Action}ed;

class {Action}{Entity}Service implements {Action}ServiceInterface
{
    public function execute(array $data): {Entity}
    {
        // Single responsibility
        // Clear input/output
        // Event dispatching
        // Error handling
    }
}
```

#### 4.3 Directory Structure Standard

```
app/Domains/{Domain}/
├── Config/              # Domain-specific configuration
├── Contracts/           # Interfaces and contracts
├── Events/             # Domain events
├── Exceptions/         # Domain exceptions
├── Http/
│   ├── Controllers/    # HTTP controllers
│   ├── Middleware/     # Domain middleware
│   ├── Requests/       # Form requests
│   └── Resources/      # API resources
├── Jobs/               # Async jobs
├── Models/             # Eloquent models
├── Notifications/      # Domain notifications
├── Policies/           # Authorization policies
├── Repositories/       # Data repositories
├── Rules/              # Validation rules
├── Services/           # Business logic
└── Tests/              # Domain tests
```

### Phase 5: Migration Execution Plan

#### Week 1: Controller Migration
- [ ] Create migration script for controllers
- [ ] Update route files
- [ ] Test all endpoints
- [ ] Update documentation

#### Week 2: Domain Restructuring
- [ ] Create new domain directories
- [ ] Move files to new domains
- [ ] Update namespaces
- [ ] Update composer autoload
- [ ] Run tests

#### Week 3: Service Reorganization
- [ ] Categorize existing services
- [ ] Create service subdirectories
- [ ] Refactor large services
- [ ] Update dependency injection

#### Week 4: Testing & Documentation
- [ ] Run full test suite
- [ ] Update API documentation
- [ ] Update developer guides
- [ ] Create domain interaction diagrams

## Success Metrics

1. **No controllers in app/Http/Controllers** (except base Controller.php)
2. **Domain size variance < 50%** (excluding Core)
3. **All services categorized** by responsibility
4. **100% test coverage** for refactored code
5. **Clear domain boundaries** with documented interfaces

## Risk Mitigation

1. **Gradual Migration**: Move one domain at a time
2. **Backward Compatibility**: Maintain old routes with deprecation notices
3. **Feature Flags**: Use flags to switch between old/new implementations
4. **Automated Testing**: Ensure comprehensive tests before refactoring
5. **Documentation First**: Update docs before code changes

## Rollback Strategy

1. Git tags before each phase
2. Database backups before migrations
3. Feature flags for quick switching
4. Monitoring for error rates
5. Rollback scripts prepared

## Next Steps

1. Review and approve this plan
2. Create detailed tickets for each phase
3. Assign team members to domains
4. Set up monitoring dashboards
5. Begin Phase 1 implementation