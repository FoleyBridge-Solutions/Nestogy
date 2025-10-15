# Model Test Coverage Plan

## Goal
Achieve 50%+ test coverage for all models with focused testing on:
- Critical business logic
- Relationships
- Scopes and queries
- Key attributes and validations

## Test Categories

### Priority 1: Core Models (Must have 80%+ coverage)
These models are critical to business operations and require comprehensive testing.

1. **User** (`app/Models/User.php`)
   - Authentication/authorization methods
   - Company relationship
   - Roles and permissions
   - UserSetting relationship
   - Status management
   - Soft deletes

2. **Company** (`app/Models/Company.php`)
   - Company creation
   - Relationships: users, clients, invoices, settings
   - Locale and currency settings
   - Multi-tenancy isolation

3. **Client** (`app/Models/Client.php`)
   - BelongsToCompany trait
   - Status management
   - Relationships: tickets, invoices, contracts
   - Custom rates
   - Soft deletes

4. **Invoice** (`app/Models/Invoice.php`)
   - Amount calculations
   - Status transitions
   - Payment tracking
   - Tax calculations
   - Relationships: items, payments, client

5. **Payment** (`app/Models/Payment.php`)
   - Amount validation
   - Invoice relationship
   - Payment method handling
   - Status tracking

### Priority 2: Financial Models (Must have 70%+ coverage)
Critical for financial accuracy and compliance.

6. **InvoiceItem** (`app/Models/InvoiceItem.php`)
   - Price calculations
   - Tax calculations
   - Invoice relationship

7. **Product** (`app/Models/Product.php`)
   - Pricing rules
   - Tax categories
   - Relationships

8. **CreditNote** (`app/Models/CreditNote.php`)
   - Amount calculations
   - Approval workflow
   - Invoice relationship

9. **Quote** (`app/Models/Quote.php`)
   - Status transitions
   - Conversion to invoice
   - Approval workflow
   - Version management

10. **RecurringInvoice** (`app/Models/RecurringInvoice.php`)
    - Frequency calculations
    - Auto-generation logic
    - Status management

11. **Expense** (`app/Models/Expense.php`)
    - Category relationships
    - Amount validation
    - Company scoping

12. **PaymentMethod** (`app/Models/PaymentMethod.php`)
    - Type validation
    - Client relationship
    - Active status

### Priority 3: Operational Models (Must have 60%+ coverage)
Important for day-to-day operations.

13. **Ticket** (`app/Models/Ticket.php`)
    - Status workflow
    - Priority management
    - Assignment logic
    - SLA tracking

14. **Contact** (`app/Models/Contact.php`)
    - Client relationship
    - Communication preferences
    - Contact types

15. **Project** (`app/Models/Project.php`)
    - Status tracking
    - Client relationship
    - Budget management

16. **TimeEntry** (`app/Models/TimeEntry.php`)
    - Duration calculations
    - Billable status
    - User/Project relationships

17. **Asset** (`app/Models/Asset.php`)
    - Client relationship
    - Status tracking
    - Depreciation
    - Warranty tracking

18. **Service** (`app/Models/Service.php`)
    - Pricing rules
    - Tax calculations
    - Client relationship

### Priority 4: Configuration Models (Must have 50%+ coverage)
Settings and configuration management.

19. **UserSetting** (`app/Models/UserSetting.php`)
    - Role constants
    - User relationship
    - Default values

20. **CompanyMailSettings** (`app/Models/CompanyMailSettings.php`)
    - SMTP configuration
    - Validation rules
    - Company relationship

21. **Setting** (`app/Models/Setting.php`)
    - Key-value storage
    - Type casting
    - Company scoping

22. **TaxRate** (`app/Models/ServiceTaxRate.php`)
    - Rate calculations
    - Jurisdiction rules
    - Date ranges

23. **TaxProfile** (`app/Models/TaxProfile.php`)
    - Exemption handling
    - Client relationship
    - Tax categories

### Priority 5: Supporting Models (Must have 40%+ coverage)
Less critical but still need basic coverage.

24. **Document** (`app/Models/Document.php`)
    - File handling
    - Relationships
    - Access control

25. **AuditLog** (`app/Models/AuditLog.php`)
    - Event tracking
    - User relationship
    - Data serialization

26. **Tag** (`app/Models/Tag.php`)
    - Many-to-many relationships
    - Scoping

27. **Category** (`app/Models/Category.php`)
    - Hierarchical structure
    - Relationships

28. **Location** (`app/Models/Location.php`)
    - Address validation
    - Client relationship

29. **Network** (`app/Models/Network.php`)
    - IP validation
    - Client relationship

30. **File** (`app/Models/File.php`)
    - Storage handling
    - MIME type validation
    - Relationships

## Test Strategy Per Model

### Standard Test Structure
Each model test should include:

1. **Factory Tests**
   - Can create instance with factory
   - Factory generates valid data

2. **Relationship Tests**
   - BelongsTo relationships work
   - HasMany relationships work
   - Many-to-many relationships work
   - Eager loading works

3. **Scope Tests**
   - Query scopes return correct results
   - Scopes can be chained

4. **Attribute Tests**
   - Fillable attributes
   - Guarded attributes
   - Casts work correctly
   - Accessors/Mutators

5. **Validation Tests** (if applicable)
   - Required fields
   - Format validation
   - Business rule validation

6. **Business Logic Tests**
   - Status transitions
   - Calculations
   - Custom methods

7. **Trait Tests**
   - Trait methods work
   - Trait relationships work

## Testing Guidelines

### What to Test
- ✅ Public methods with business logic
- ✅ Relationships and their integrity
- ✅ Scopes and query builders
- ✅ Calculated attributes
- ✅ Status/state transitions
- ✅ Critical validations

### What NOT to Test
- ❌ Framework functionality (Eloquent basics)
- ❌ Simple getters/setters
- ❌ Trivial accessors without logic
- ❌ Database migrations
- ❌ Private methods (test through public interface)

## Implementation Plan

### Phase 1: Core Models (Week 1)
- User, Company, Client, Invoice, Payment
- Target: 80% coverage each
- Estimated: 5-7 test files, ~500 assertions

### Phase 2: Financial Models (Week 2)
- InvoiceItem, Product, CreditNote, Quote, RecurringInvoice, Expense, PaymentMethod
- Target: 70% coverage each
- Estimated: 7 test files, ~400 assertions

### Phase 3: Operational Models (Week 3)
- Ticket, Contact, Project, TimeEntry, Asset, Service
- Target: 60% coverage each
- Estimated: 6 test files, ~300 assertions

### Phase 4: Configuration Models (Week 4)
- UserSetting, CompanyMailSettings, Setting, TaxRate, TaxProfile
- Target: 50% coverage each
- Estimated: 5 test files, ~200 assertions

### Phase 5: Supporting Models (Week 5)
- Document, AuditLog, Tag, Category, Location, Network, File
- Target: 40% coverage each
- Estimated: 7 test files, ~200 assertions

## Expected Outcomes

### Coverage Targets
- Overall model coverage: **55-60%**
- Core models: **80%+**
- Financial models: **70%+**
- Operational models: **60%+**
- Configuration models: **50%+**
- Supporting models: **40%+**

### Total Test Count
- **30 test files**
- **~1,600 assertions**
- **~2,500 lines of test code**

## Success Metrics

1. ✅ All priority 1-3 models have dedicated test files
2. ✅ All critical business logic is covered
3. ✅ All relationships are tested
4. ✅ CI/CD pipeline includes model tests
5. ✅ Test execution time under 2 minutes
6. ✅ Zero false positives/negatives
7. ✅ Tests are maintainable and readable

## Next Steps

1. Create factory definitions for all models
2. Set up database seeders for test data
3. Create base test classes with common assertions
4. Implement Phase 1 tests (Core Models)
5. Run coverage report and adjust priorities