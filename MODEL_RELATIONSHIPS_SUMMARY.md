# Model Relationships Summary - Nestogy ERP

## Overview
This document provides a high-level summary of the Laravel model relationships across all domains in the Nestogy ERP system.

## Domain Structure

### 1. **Core Domain**
**Key Models:** User, Role, Permission, Tag, Company
- **User** is the central authentication model with relationships to:
  - Company (belongsTo)
  - UserSetting (hasOne)
  - Clients (belongsToMany via user_clients pivot)
  - Tickets (hasMany: created, assigned, closed)
  - Projects (hasMany as manager, belongsToMany as member)
  
### 2. **Company Domain**
**Key Models:** Company, Account, CompanyHierarchy, CompanySubscription
- **Company** supports multi-tenancy with:
  - Users (hasMany)
  - Clients (hasMany)
  - Settings (hasOne)
  - Parent/Child relationships (self-referencing for subsidiaries)
  - CompanyHierarchy for complex organizational structures
  
- **Account** manages financial accounts:
  - Payments (hasMany)
  - Expenses (hasMany)
  - Plaid integration support

### 3. **Client Domain**
**Key Models:** Client, Contact, Location, Network
- **Client** is central to operations:
  - Contacts (hasMany with primary, billing, technical roles)
  - Locations (hasMany)
  - Assets (hasMany)
  - Tickets (hasMany)
  - Invoices (hasMany)
  - Payments (hasMany)
  - Projects (hasMany)
  - Contracts (hasMany)
  - Tags (belongsToMany)
  - AssignedTechnicians (belongsToMany via User)
  - SLA (belongsTo)
  - RateCards (hasMany)
  - Subscription features for SaaS billing

- **Contact** (extends Authenticatable for portal access):
  - Client (belongsTo)
  - Location (belongsTo)
  - Assets (hasMany)
  - Tickets (hasMany)
  - Hierarchical reporting structure (reportsTo/directReports)
  
- **Location** represents physical sites:
  - Client (belongsTo)
  - Contacts (hasMany)
  - Assets (hasMany)
  - Networks (hasMany)
  - Tickets (hasMany)

### 4. **Financial Domain**
**Key Models:** Invoice, Payment, Quote, CreditNote, Expense
- **Invoice**:
  - Client (belongsTo)
  - InvoiceItems (hasMany)
  - PaymentApplications (morphMany)
  - TaxCalculations (morphMany)
  - Tickets (hasMany)
  - TimeEntries (hasMany)
  - Physical mail integration support
  
- **Payment**:
  - Client (belongsTo)
  - Account (belongsTo)
  - PaymentApplications (hasMany)
  - Applied to multiple invoices via PaymentApplication polymorphic

- **Quote**:
  - Client (belongsTo)
  - QuoteItems (hasMany)
  - QuoteVersions (hasMany)
  - QuoteApprovals (hasMany)
  - Can convert to Invoice

- **CreditNote**:
  - Client (belongsTo)
  - CreditNoteItems (hasMany)
  - ApplicationsToInvoices (via ClientCreditApplication)

### 5. **Contract Domain**
**Key Models:** Contract, ContractAmendment, ContractSchedule
- **Contract** (highly configurable):
  - Client (belongsTo)
  - ContractItems/Components (hasMany)
  - Amendments (hasMany)
  - Schedules (hasMany)
  - Signatures (hasMany)
  - Milestones (hasMany)
  - ServiceAssignments
  - AssetAssignments
  - ContactAssignments

### 6. **Ticket Domain**
**Key Models:** Ticket, TicketComment, TicketTimeEntry, SLA
- **Ticket**:
  - Client (belongsTo)
  - Location (belongsTo)
  - Contact (belongsTo)
  - CreatedBy User (belongsTo)
  - AssignedTo User (belongsTo)
  - ClosedBy User (belongsTo)
  - Comments (hasMany)
  - TimeEntries (hasMany)
  - Attachments (hasMany)
  - SLA (belongsTo)
  - Invoice (belongsTo when billed)
  - CalendarEvents (hasMany)

- **TicketTimeEntry**:
  - Ticket (belongsTo)
  - User (belongsTo)
  - Invoice (belongsTo when billed)
  - Billable flag with rate tracking

### 7. **Asset Domain**
**Key Models:** Asset, AssetMaintenance, AssetWarranty, AssetDepreciation
- **Asset**:
  - Client (belongsTo)
  - Location (belongsTo)
  - Contact (belongsTo - assigned to)
  - Maintenance records (hasMany)
  - Warranties (hasMany)
  - Depreciation records (hasMany)
  - RMM integration support

### 8. **Tax Domain**
**Key Models:** Tax, TaxCalculation, TaxExemption, TaxJurisdiction
- **TaxCalculation** (polymorphic):
  - Calculable (morphTo: Invoice, Quote, etc.)
  - TaxJurisdictions breakdown
  - VoIP-specific tax support
  
- **TaxExemption**:
  - Client (belongsTo)
  - TaxExemptionUsage (hasMany)
  - Certificate tracking

### 9. **Collections Domain**
**Key Models:** DunningCampaign, DunningSequence, DunningAction, CollectionNote
- **DunningCampaign**:
  - Sequences (hasMany)
  - Actions taken (hasMany)
  
- **DunningAction**:
  - Client (belongsTo)
  - Invoice (belongsTo)
  - Campaign (belongsTo)
  - Sequence (belongsTo)
  - CollectionNotes (hasMany)

### 10. **HR Domain**
**Key Models:** PayPeriod, Shift, EmployeeSchedule, EmployeeTimeEntry
- **PayPeriod**:
  - Company (belongsTo)
  - TimeEntries (hasMany)
  
- **EmployeeTimeEntry**:
  - User/Employee (belongsTo)
  - PayPeriod (belongsTo)
  - Shift (belongsTo)
  - Overtime calculations

- **EmployeeSchedule**:
  - User (belongsTo)
  - Shifts (hasMany)

### 11. **Project Domain**
**Key Models:** Project, ProjectTask, ProjectMember, ProjectTimeEntry
- **Project**:
  - Client (belongsTo)
  - Manager (belongsTo User)
  - Members (belongsToMany User via ProjectMember)
  - Tasks (hasMany)
  - TimeEntries (hasMany)
  - Expenses (hasMany)
  - Milestones (hasMany)
  - Files (hasMany)

- **ProjectTask**:
  - Project (belongsTo)
  - AssignedTo User (belongsTo)
  - ParentTask (belongsTo self)
  - SubTasks (hasMany self)

## Key Relationship Patterns

### 1. **Multi-Tenancy**
- Almost all models have `company_id` foreign key
- Company scoping via BelongsToCompany trait
- Support for company hierarchies and cross-company access

### 2. **Soft Deletes**
- Most models use soft deletes (archived_at)
- Maintains data integrity for historical records

### 3. **Polymorphic Relationships**
- **Document/File storage**: `documentable_type/id`, `fileable_type/id`
- **Payment Applications**: `applicable_type/id` (can apply to Invoice, CreditNote, etc.)
- **Tax Calculations**: `calculable_type/id`
- **Notifications**: `notifiable_type/id`

### 4. **Pivot Tables with Metadata**
- `user_clients`: User-Client assignments with access_level, is_primary
- `project_members`: Project membership with role, hourly_rate, permissions
- `client_tags`: Tag assignments with company_id
- `payment_applications`: Payment-to-invoice mappings with amounts

### 5. **Hierarchical Structures**
- **Company**: parent_company_id (organizational hierarchy)
- **Contact**: reports_to_id (organizational chart)
- **Task**: parent_task_id (task breakdown)
- **Location**: Primary location per client
- **CompanyHierarchy**: Closure table for complex hierarchies

### 6. **Audit Trail**
- Many models track created_by, updated_by, deleted_by
- AuditLog model for comprehensive activity tracking
- Timestamps on all models

### 7. **Integration Points**
- **RMM Integration**: RmmClientMapping, RmmIntegration
- **Stripe**: stripe_customer_id, stripe_subscription_id on Client
- **Plaid**: plaid_id on Account, plaid_transaction_id on Payment
- **PostGrid**: Physical mail capabilities on Invoice/Letter models

## Relationship Statistics

- **Total Models Analyzed**: ~150+
- **Total Relationships**: ~800+
- **Most Connected Models**: 
  1. Client (30+ relationships)
  2. User (20+ relationships)
  3. Invoice (15+ relationships)
  4. Company (18+ relationships)
  5. Ticket (15+ relationships)

## Relationship Type Distribution

- **belongsTo**: ~300 (parent references)
- **hasMany**: ~250 (one-to-many)
- **belongsToMany**: ~50 (many-to-many)
- **hasOne**: ~40 (one-to-one)
- **morphTo/morphMany**: ~30 (polymorphic)
- **hasManyThrough**: ~10 (indirect relationships)

## Critical Foreign Keys

### Company Level
- `company_id`: Present in almost all models for multi-tenancy

### Client Management
- `client_id`: Links to Client across Financial, Ticket, Project, Asset domains
- `contact_id`: Contact references for communication
- `location_id`: Physical location references

### Financial
- `invoice_id`: Invoice references for payments, time entries
- `payment_id`: Payment tracking
- `account_id`: Financial account references

### User Management
- `user_id`: User references for assignments, creators, processors
- `assigned_to`: User assignments (tickets, tasks)
- `created_by`, `updated_by`: Audit trail

### Service Management
- `ticket_id`: Ticket references
- `project_id`: Project references
- `contract_id`: Contract references
- `sla_id`: SLA references

## Integration Considerations for Mermaid Diagram

For a readable Mermaid diagram, focus on:

1. **Core Entity Relationships**:
   - Company → User → Client → Invoice/Ticket/Project
   
2. **Financial Flow**:
   - Client → Invoice → InvoiceItem
   - Client → Payment → PaymentApplication → Invoice
   
3. **Service Delivery**:
   - Client → Ticket → TimeEntry
   - Client → Contract → ContractSchedule
   
4. **Asset Management**:
   - Client → Location → Asset
   - Asset → Maintenance/Warranty

5. **Project Management**:
   - Client → Project → Task → TimeEntry
   - Project → ProjectMember (User)

See the full `MODEL_RELATIONSHIPS_ANALYSIS.md` file for complete details and Mermaid diagram code.
