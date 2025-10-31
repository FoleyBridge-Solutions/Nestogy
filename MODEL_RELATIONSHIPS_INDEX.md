# Model Relationships Documentation Index

This directory contains comprehensive documentation of all Laravel model relationships in the Nestogy ERP system.

## Documentation Files

### 1. MODEL_RELATIONSHIPS_SUMMARY.md
**Size:** ~9KB  
**Purpose:** Executive summary of all domains and their key models  
**Best For:** Quick overview, understanding the system architecture

**Contents:**
- Domain-by-domain breakdown
- Key relationship patterns (multi-tenancy, polymorphic, hierarchical)
- Relationship statistics
- Critical foreign keys reference

### 2. MODEL_RELATIONSHIPS_ANALYSIS.md  
**Size:** ~90KB  
**Purpose:** Complete detailed analysis of every model  
**Best For:** Deep dive into specific models, finding all relationships

**Contents:**
- Full model listings by domain
- Every relationship method documented
- Foreign key listings
- Namespace and class information
- Mermaid diagram with core models

### 3. CORE_RELATIONSHIPS_DIAGRAM.md
**Size:** ~10KB  
**Purpose:** Visual Mermaid diagrams of relationships  
**Best For:** Visualizing data flow, understanding connections

**Contents:**
- Comprehensive ER diagram with all core entities
- Domain-specific focused diagrams:
  - Financial Domain
  - Ticket Management
  - Asset Management  
  - Project Management
  - HR & Time Tracking
  - Tax & Compliance
  - Collections & Dunning

## Quick Reference

### Most Important Models

1. **Company** (`App\Domains\Company\Models\Company`)
   - Multi-tenant root entity
   - 18+ relationships including hierarchy support
   - See: MODEL_RELATIONSHIPS_SUMMARY.md:48

2. **User** (`App\Domains\Core\Models\User`)
   - Authentication and authorization
   - 20+ relationships
   - See: User.php:3

3. **Client** (`App\Domains\Client\Models\Client`)
   - Central business entity
   - 30+ relationships to all domains
   - See: Client.php:3

4. **Invoice** (`App\Domains\Financial\Models\Invoice`)
   - Financial transactions
   - 15+ relationships including polymorphic
   - See: Invoice.php:3

5. **Ticket** (`App\Domains\Ticket\Models\Ticket`)
   - Service management
   - 15+ relationships
   - See: Ticket.php:3

### Common Patterns

#### Multi-Tenancy
```php
// Almost every model has:
protected $fillable = ['company_id', ...];

// Via BelongsToCompany trait
public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}
```

#### Polymorphic Relationships
```php
// Documents/Files
'documentable_type', 'documentable_id'

// Payment Applications
'applicable_type', 'applicable_id'

// Tax Calculations  
'calculable_type', 'calculable_id'
```

#### Pivot Tables with Metadata
```php
// User-Client assignments
'user_clients' => ['access_level', 'is_primary', 'assigned_at']

// Project members
'project_members' => ['role', 'hourly_rate', 'can_edit']

// Payment applications
'payment_applications' => ['amount', 'applied_date', 'is_active']
```

## Usage Examples

### Finding All Relationships for a Model

1. Open `MODEL_RELATIONSHIPS_ANALYSIS.md`
2. Search for the model name (e.g., "#### Client")
3. View the relationships table

### Understanding Data Flow

1. Open `CORE_RELATIONSHIPS_DIAGRAM.md`
2. Find the relevant domain diagram
3. Follow the relationship arrows

### Building a New Feature

1. Check `MODEL_RELATIONSHIPS_SUMMARY.md` for affected domains
2. Review relationships in `MODEL_RELATIONSHIPS_ANALYSIS.md`
3. Use diagrams in `CORE_RELATIONSHIPS_DIAGRAM.md` to validate design

## Model Counts by Domain

| Domain | Models | Key Focus |
|--------|--------|-----------|
| Core | 18 | Users, Roles, Permissions, Tags |
| Company | 10 | Multi-tenancy, Accounts, Hierarchy |
| Client | 23 | Clients, Contacts, Locations |
| Financial | 26 | Invoices, Payments, Quotes, Credit Notes |
| Contract | 27 | Contracts, Schedules, Amendments |
| Ticket | 16 | Tickets, Time Tracking, SLAs |
| Asset | 4 | Assets, Maintenance, Warranties |
| Tax | 13 | Tax Calculations, Exemptions, Compliance |
| Collections | 4 | Dunning, Collection Actions |
| HR | 6 | Time Entries, Schedules, Pay Periods |
| Project | 11 | Projects, Tasks, Time Tracking |
| Product | 11 | Products, Bundles, Usage Tracking |

**Total: ~150+ models analyzed**

## Key Findings

### Highly Connected Models
These models have the most relationships and are central to the system:

1. **Client** - 30+ relationships (connects all domains)
2. **User** - 20+ relationships (authentication, assignments)
3. **Company** - 18+ relationships (multi-tenancy root)
4. **Invoice** - 15+ relationships (financial hub)
5. **Ticket** - 15+ relationships (service delivery)

### Critical Foreign Keys

#### Must Have in Most Models
- `company_id` - Multi-tenancy
- `created_by`, `updated_by` - Audit trail
- `client_id` - Client relationships

#### Domain-Specific
- Financial: `invoice_id`, `payment_id`, `account_id`
- Service: `ticket_id`, `project_id`, `contract_id`
- Location: `location_id`, `contact_id`
- Assignments: `assigned_to`, `user_id`

## Diagram Legend

### Mermaid Notation
- `||--o{` One to Many
- `||--||` One to One
- `}o--o{` Many to Many
- `}o--||` Many to One
- `||..o{` One to Many (through/indirect)

### Relationship Types in Code
- `belongsTo` - Parent reference (Many to One)
- `hasOne` - One to One
- `hasMany` - One to Many
- `belongsToMany` - Many to Many (pivot table)
- `morphTo/morphMany` - Polymorphic
- `hasManyThrough` - Indirect relationship

## Maintenance

These documents were auto-generated from the Laravel models on: **2025-10-30**

To regenerate:
```bash
php /tmp/generate_relationship_report.php > MODEL_RELATIONSHIPS_ANALYSIS.md
```

## See Also

- `/opt/nestogy/docs/` - General documentation
- `/opt/nestogy/database/schema/` - Database schema
- Individual model files in `/opt/nestogy/app/Domains/*/Models/`

