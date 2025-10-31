# Core Model Relationships - Mermaid Diagram

## Simplified Entity Relationship Diagram

This diagram shows the core relationships between the most important entities in the Nestogy ERP system.

```mermaid
erDiagram
    Company ||--o{ User : "employs"
    Company ||--o{ Client : "manages"
    Company ||--o{ Account : "owns"
    Company ||--|| Setting : "has"
    Company ||--o{ Company : "parent_of"
    
    User ||--|| UserSetting : "has"
    User }o--o{ Client : "assigned_to"
    User ||--o{ Ticket : "creates"
    User ||--o{ Ticket : "assigned"
    User ||--o{ Project : "manages"
    User }o--o{ Project : "member_of"
    
    Client ||--o{ Contact : "has"
    Client ||--o{ Location : "has"
    Client ||--o{ Asset : "owns"
    Client ||--o{ Invoice : "receives"
    Client ||--o{ Payment : "makes"
    Client ||--o{ Ticket : "submits"
    Client ||--o{ Project : "commissions"
    Client ||--o{ Contract : "signs"
    Client }o--o{ Tag : "tagged_with"
    Client ||--o| SLA : "has"
    
    Contact }o--|| Client : "belongs_to"
    Contact }o--o| Location : "at"
    Contact ||--o{ Asset : "uses"
    Contact ||--o{ Ticket : "requests"
    
    Location }o--|| Client : "belongs_to"
    Location ||--o{ Asset : "houses"
    Location ||--o{ Network : "contains"
    Location ||--o{ Contact : "hosts"
    
    Invoice }o--|| Client : "billed_to"
    Invoice ||--o{ InvoiceItem : "contains"
    Invoice ||--o{ PaymentApplication : "receives"
    Invoice ||--o{ TaxCalculation : "calculates"
    Invoice ||--o{ Ticket : "bills"
    Invoice }o--o| Project : "for"
    
    Payment }o--|| Client : "from"
    Payment }o--|| Account : "deposited_to"
    Payment ||--o{ PaymentApplication : "applied_as"
    
    PaymentApplication }o--|| Payment : "applies"
    PaymentApplication }o--|| Invoice : "to"
    
    Ticket }o--|| Client : "for"
    Ticket }o--o| Location : "at"
    Ticket }o--o| Contact : "requested_by"
    Ticket }o--|| User : "created_by"
    Ticket }o--o| User : "assigned_to"
    Ticket ||--o{ TicketComment : "has"
    Ticket ||--o{ TicketTimeEntry : "tracked_in"
    Ticket }o--o| Invoice : "billed_on"
    Ticket }o--o| SLA : "governed_by"
    
    TicketTimeEntry }o--|| Ticket : "for"
    TicketTimeEntry }o--|| User : "by"
    TicketTimeEntry }o--o| Invoice : "billed_on"
    
    Project }o--|| Client : "for"
    Project }o--|| User : "managed_by"
    Project ||--o{ ProjectTask : "contains"
    Project ||--o{ ProjectTimeEntry : "tracks"
    Project }o--o{ User : "has_members"
    
    ProjectTask }o--|| Project : "in"
    ProjectTask }o--o| User : "assigned_to"
    ProjectTask }o--o| ProjectTask : "parent_of"
    
    Contract }o--|| Client : "with"
    Contract ||--o{ ContractSchedule : "defines"
    Contract ||--o{ ContractAmendment : "modified_by"
    Contract ||--o{ ContractSignature : "signed_with"
    
    Asset }o--|| Client : "owned_by"
    Asset }o--o| Location : "located_at"
    Asset }o--o| Contact : "assigned_to"
    Asset ||--o{ AssetMaintenance : "maintained_by"
    Asset ||--o{ AssetWarranty : "covered_by"
    
    Quote }o--|| Client : "for"
    Quote ||--o{ QuoteItem : "contains"
    Quote ||--o{ QuoteVersion : "versioned_as"
    Quote }o--o| Invoice : "converts_to"
    
    CreditNote }o--|| Client : "issued_to"
    CreditNote ||--o{ CreditNoteItem : "contains"
    CreditNote ||--o{ ClientCreditApplication : "applied_via"
```

## Domain-Specific Diagrams

### Financial Domain Focus

```mermaid
erDiagram
    Client ||--o{ Invoice : "receives"
    Client ||--o{ Payment : "makes"
    Client ||--o{ Quote : "requests"
    Client ||--o{ CreditNote : "gets"
    
    Invoice ||--o{ InvoiceItem : "line_items"
    Invoice }o--o{ Payment : "paid_by"
    Invoice ||--o{ TaxCalculation : "taxes"
    
    Payment ||--o{ PaymentApplication : "applied"
    PaymentApplication }o--|| Invoice : "to_invoice"
    
    Quote ||--o{ QuoteItem : "line_items"
    Quote }o--o| Invoice : "becomes"
    
    Account ||--o{ Payment : "receives"
    Account ||--o{ Expense : "pays"
```

### Ticket Management Focus

```mermaid
erDiagram
    Client ||--o{ Ticket : "submits"
    User ||--o{ Ticket : "handles"
    
    Ticket ||--o{ TicketComment : "discussed_in"
    Ticket ||--o{ TicketTimeEntry : "time_tracked"
    Ticket ||--o{ TicketAttachment : "files"
    Ticket }o--|| SLA : "governed_by"
    Ticket }o--o| Location : "at_site"
    Ticket }o--o| Contact : "reported_by"
    Ticket }o--o| Invoice : "billed_on"
    
    TicketTimeEntry }o--|| User : "worked_by"
    TicketTimeEntry }o--o| Invoice : "invoiced"
```

### Asset Management Focus

```mermaid
erDiagram
    Client ||--o{ Asset : "owns"
    Client ||--o{ Location : "has"
    
    Location ||--o{ Asset : "houses"
    Location ||--o{ Network : "contains"
    Location ||--o{ Contact : "staffed_by"
    
    Asset }o--o| Contact : "assigned_to"
    Asset ||--o{ AssetMaintenance : "serviced"
    Asset ||--o{ AssetWarranty : "warranted"
    Asset ||--o{ AssetDepreciation : "depreciated"
```

### Project Management Focus

```mermaid
erDiagram
    Client ||--o{ Project : "commissions"
    User ||--o{ Project : "manages"
    User }o--o{ Project : "works_on"
    
    Project ||--o{ ProjectTask : "tasks"
    Project ||--o{ ProjectTimeEntry : "time"
    Project ||--o{ ProjectExpense : "costs"
    Project ||--o{ ProjectMilestone : "milestones"
    
    ProjectTask }o--o| User : "assigned_to"
    ProjectTask }o--o| ProjectTask : "subtask_of"
    
    ProjectTimeEntry }o--|| User : "by"
    ProjectTimeEntry }o--|| Project : "for"
```

### HR & Time Tracking Focus

```mermaid
erDiagram
    Company ||--o{ PayPeriod : "defines"
    User ||--o{ EmployeeTimeEntry : "logs"
    User ||--o{ EmployeeSchedule : "scheduled"
    
    PayPeriod ||--o{ EmployeeTimeEntry : "contains"
    EmployeeSchedule ||--o{ Shift : "includes"
    EmployeeTimeEntry }o--o| Shift : "during"
    EmployeeTimeEntry }o--|| PayPeriod : "in_period"
```

### Tax & Compliance Focus

```mermaid
erDiagram
    Invoice ||--o{ TaxCalculation : "calculates"
    Client ||--o{ TaxExemption : "has"
    
    TaxCalculation }o--o{ TaxJurisdiction : "jurisdictions"
    TaxExemption ||--o{ TaxExemptionUsage : "used_in"
    TaxExemptionUsage }o--|| Invoice : "applied_to"
```

### Collections & Dunning Focus

```mermaid
erDiagram
    Company ||--o{ DunningCampaign : "runs"
    DunningCampaign ||--o{ DunningSequence : "executes"
    DunningSequence ||--o{ DunningAction : "triggers"
    
    Client ||--o{ DunningAction : "receives"
    Invoice ||--o{ DunningAction : "prompts"
    
    DunningAction ||--o{ CollectionNote : "documented"
    Client ||--o{ AccountHold : "placed_on"
```

## Notes

- `||--o{` = One to Many
- `||--||` = One to One
- `}o--o{` = Many to Many
- `}o--||` = Many to One
- `||..o{` = One to Many (through/indirect)

## Usage

These diagrams can be rendered in any Mermaid-compatible viewer:
- GitHub Markdown
- GitLab
- Mermaid Live Editor (https://mermaid.live)
- VS Code with Mermaid extension
- Documentation sites (MkDocs, Docusaurus, etc.)

