# Nestogy ERP Development Seeder Implementation Plan

## Overview
This document provides precise instructions for two AI agents to simultaneously implement comprehensive database seeders for the Nestogy ERP system. Each agent has specific responsibilities and must complete ALL assigned tasks without stopping.

## Critical Requirements
- **BOTH AGENTS MUST COMPLETE ALL ASSIGNED TASKS**
- **DO NOT STOP UNTIL YOUR SECTION IS FULLY IMPLEMENTED**
- **ALL FOREIGN KEY RELATIONSHIPS MUST BE MAINTAINED**
- **USE LARAVEL FACTORIES AND SEEDERS PROPERLY**
- **TEST EACH SEEDER AFTER CREATION**

## Agent 1: Foundation & Infrastructure Seeders

### Your Responsibilities
You are responsible for creating ALL foundation seeders that other data depends on. You must work independently and create everything needed for Agent 2's seeders to function.

### Task List - COMPLETE ALL OF THESE

#### 1. Create DevDatabaseSeeder
Location: `database/seeders/Dev/DevDatabaseSeeder.php`
```php
- Create main orchestrator seeder
- Check for development/local environment
- Call all other seeders in correct order
- Use database transactions for safety
- Add progress output messages
- DO NOT STOP until this is complete
```

#### 2. Create CompanySeeder
Location: `database/seeders/Dev/CompanySeeder.php`
```php
Companies to create:
- Company ID 1: "Nestogy Platform" (platform operator)
- Company ID 2: "TechGuard MSP" (EST timezone)
- Company ID 3: "CloudFirst Solutions" (CST timezone)  
- Company ID 4: "Digital Shield IT" (PST timezone)
- Company ID 5: "Mountain Peak Tech" (MST timezone)

Each company needs:
- Full address information
- Phone, email, website
- Currency (USD for all)
- Locale settings
- Default hourly rates ($150-$250)
- Billing configuration
- DO NOT STOP until all companies are created
```

#### 3. Create SettingsSeeder
Location: `database/seeders/Dev/SettingsSeeder.php`
```php
For each company create:
- Default net terms (30 days)
- Invoice/quote/ticket numbering start points
- Timezone settings
- Theme preferences
- Start pages
- DO NOT STOP until settings are complete
```

#### 4. Create UserSeeder
Location: `database/seeders/Dev/UserSeeder.php`
```php
Per company create these users:

Company 1 (Nestogy Platform):
- super@nestogy.com (Super Admin via Bouncer)
- admin@nestogy.com (Admin)

Companies 2-5 (Each MSP):
- admin@[company].com (Admin role)
- admin2@[company].com (Admin role)
- tech1@[company].com through tech5@[company].com (Tech role)
- accounting@[company].com (Accountant role)
- accounting2@[company].com (Accountant role)
- sales@[company].com (Sales role)
- marketing@[company].com (Marketing role)

All passwords: 'password123'
Create UserSettings for each user
Assign Bouncer roles properly
DO NOT STOP until all users are created
```

#### 5. Create RolesAndPermissionsSeeder
Location: `database/seeders/Dev/RolesAndPermissionsSeeder.php`
```php
Using Silber/Bouncer create:
- Super Admin role (Company 1 only)
- Admin role
- Tech role
- Accountant role
- Sales role
- Marketing role

Assign permissions for each domain:
- clients.*, assets.*, tickets.*, etc.
- DO NOT STOP until all roles have proper permissions
```

#### 6. Create CategorySeeder
Location: `database/seeders/Dev/CategorySeeder.php`
```php
Create categories for:
- Invoice categories (Service, Product, Support, Consulting)
- Expense categories (Hardware, Software, Travel, Office, Utilities)
- Ticket categories (Support, Maintenance, Emergency, Project)
- DO NOT STOP until all categories exist
```

#### 7. Create VendorSeeder
Location: `database/seeders/Dev/VendorSeeder.php`
```php
Per company create 10-15 vendors:
- Microsoft, Dell, HP, Cisco, Amazon
- Local ISPs and service providers
- Software vendors
- Hardware suppliers
- Include contact information
- DO NOT STOP until vendors are complete
```

#### 8. Create ClientSeeder
Location: `database/seeders/Dev/ClientSeeder.php`
```php
Per company create EXACTLY:

5 Enterprise Clients (>100 employees):
- Names: "[City] Medical Group", "[City] Financial Services", etc.
- 100-150 employee count
- Complete address information
- Hourly rate: $200-300
- Status: active
- Custom rate configurations

10 Mid-Market Clients (20-100 employees):
- Names: "[City] Law Firm", "[City] Manufacturing Co", etc.
- 20-100 employee count
- Complete address information
- Hourly rate: $150-200
- Status: active

15 Small Business Clients (<20 employees):
- Names: "[City] Dental Office", "[City] Auto Repair", etc.
- 5-20 employee count
- Complete address information
- Hourly rate: $125-150
- Mix of active/inactive status

10-20 Leads (prospects):
- Basic company information
- No contracts
- Lead flag = true

DO NOT STOP until all 40-50 clients per company exist
```

#### 9. Create LocationSeeder
Location: `database/seeders/Dev/LocationSeeder.php`
```php
Enterprise clients: 5-10 locations each
Mid-market clients: 2-3 locations each
Small business: 1 location each

Each location needs:
- Full address
- Phone number
- Primary contact assignment
- Access instructions
- Parking information
- DO NOT STOP until all locations are created
```

#### 10. Create ContactSeeder
Location: `database/seeders/Dev/ContactSeeder.php`
```php
Per client create:
Enterprise: 10-20 contacts
- CEO, CTO, IT Manager, etc.
- Mix of primary, billing, technical flags
- Some with portal access

Mid-Market: 5-10 contacts
- Owner, IT Manager, Office Manager
- At least one of each type

Small Business: 2-5 contacts
- Owner, Office Manager
- At least primary and billing

Include:
- Full names, titles, emails, phones
- Department information
- Some with portal credentials
- DO NOT STOP until all contacts exist
```

#### 11. Create NetworkSeeder
Location: `database/seeders/Dev/NetworkSeeder.php`
```php
Per company create:
- Default networks (LAN, WAN, DMZ, Guest)
- IP ranges for each
- VLAN configurations
- DO NOT STOP until networks are configured
```

#### 12. Create TaxSeeder
Location: `database/seeders/Dev/TaxSeeder.php`
```php
Create:
- Tax jurisdictions for each state
- Tax categories (Product, Service, VoIP)
- Tax rates per jurisdiction
- VoIP-specific tax rates
- E911 fees
- DO NOT STOP until tax system is complete
```

### IMPORTANT REMINDERS FOR AGENT 1:
- You MUST create ALL of the above seeders
- Each seeder must be fully functional
- Use Laravel factories where appropriate
- Add database transactions for safety
- Include progress output in each seeder
- Test each seeder individually
- DO NOT STOP until everything above is complete
- Your work enables Agent 2 to function - they depend on you

---

## Agent 2: Operations & Business Data Seeders

### Your Responsibilities
You are responsible for creating ALL operational seeders that depend on Agent 1's foundation data. You must work independently but assume Agent 1's data exists.

### Task List - COMPLETE ALL OF THESE

#### 1. Create AssetSeeder
Location: `database/seeders/Dev/AssetSeeder.php`
```php
Per client create based on size:

Enterprise (100-150 assets each):
- 20-30 Servers (Windows, Linux mix)
- 50-80 Desktops
- 10-20 Laptops
- 5-10 Routers/Switches
- 10-15 Printers
- 5-10 Firewalls
- Other devices

Mid-Market (20-50 assets each):
- 2-5 Servers
- 15-30 Desktops
- 5-10 Laptops
- 3-5 Network devices
- 3-5 Printers

Small Business (5-20 assets each):
- 0-1 Server
- 5-15 Desktops
- 2-5 Laptops
- 1-2 Network devices
- 1-2 Printers

Each asset needs:
- Type, name, make, model, serial
- Purchase date (varied over past 5 years)
- Warranty expiration dates
- IP addresses for servers/network devices
- Support status (70% supported, 20% unsupported, 10% pending)
- Location and contact assignments
- DO NOT STOP until all assets are created
```

#### 2. Create AssetWarrantySeeder
Location: `database/seeders/Dev/AssetWarrantySeeder.php`
```php
For 60% of assets create warranties:
- Warranty provider
- Start and end dates
- Coverage type
- 30% expired, 50% active, 20% expiring soon
- DO NOT STOP until warranties are complete
```

#### 3. Create ContractTemplateSeeder
Location: `database/seeders/Dev/ContractTemplateSeeder.php`
```php
Create templates:
- Managed Services Agreement
- Project Services Agreement
- Break-Fix Agreement
- Software License Agreement
- Hardware Lease Agreement
- Include full terms and conditions
- DO NOT STOP until templates exist
```

#### 4. Create SLASeeder
Location: `database/seeders/Dev/SLASeeder.php`
```php
Per company create 4 SLA levels:
- Platinum: 1hr response, 4hr resolution
- Gold: 2hr response, 8hr resolution
- Silver: 4hr response, 24hr resolution
- Bronze: 8hr response, 48hr resolution

Include:
- Business hours vs 24/7 coverage
- Escalation procedures
- Penalty clauses
- DO NOT STOP until all SLAs exist
```

#### 5. Create ContractSeeder
Location: `database/seeders/Dev/ContractSeeder.php`
```php
Create contracts:

All Enterprise clients:
- Comprehensive managed services
- 3-year terms
- $5,000-10,000/month value
- Include all assets
- Platinum/Gold SLA

70% of Mid-Market clients:
- Standard managed services
- 1-2 year terms
- $2,000-5,000/month value
- Include critical assets
- Gold/Silver SLA

30% of Small Business:
- Basic support contracts
- Monthly/Annual terms
- $500-2,000/month value
- Bronze SLA

Each contract needs:
- Start date (varied over past 2 years)
- End dates (some expired, some expiring soon)
- Pricing structures
- Payment terms
- Auto-renewal settings
- DO NOT STOP until all contracts exist
```

#### 6. Create ContractScheduleSeeder
Location: `database/seeders/Dev/ContractScheduleSeeder.php`
```php
Per contract create:
- Schedule A (Infrastructure/Assets covered)
- Schedule B (Pricing details)
- Service level schedules
- Maintenance windows
- DO NOT STOP until schedules are complete
```

#### 7. Create TicketSeeder
Location: `database/seeders/Dev/TicketSeeder.php`
```php
Generate 6 months of historical tickets:

Per client per month:
- Enterprise: 50-100 tickets
- Mid-Market: 20-50 tickets
- Small Business: 5-20 tickets

Status distribution:
- 40% Closed
- 20% Open
- 20% In Progress
- 10% On Hold
- 10% Resolved

Priority distribution:
- 5% Critical
- 15% High
- 50% Medium
- 30% Low

Each ticket needs:
- Realistic subject and details
- Client, contact, asset assignments
- Created/assigned/closed by users
- Billable flag (70% billable)
- Some linked to projects
- DO NOT STOP until all tickets exist
```

#### 8. Create TicketReplySeeder
Location: `database/seeders/Dev/TicketReplySeeder.php`
```php
Per ticket create 2-10 replies:
- Mix of tech updates and client responses
- Time worked entries (15min to 4hrs)
- Internal notes
- Status updates
- Resolution descriptions for closed tickets
- DO NOT STOP until all replies exist
```

#### 9. Create ProjectSeeder
Location: `database/seeders/Dev/ProjectSeeder.php`
```php
Per company create 30-40 projects:
Types:
- Infrastructure upgrades
- Cloud migrations
- Security implementations
- Software deployments
- Office relocations

Status distribution:
- 30% Completed
- 40% In Progress
- 20% Planning
- 10% On Hold

Include:
- Project manager assignments
- Due dates
- Descriptions
- Some overdue
- DO NOT STOP until all projects exist
```

#### 10. Create ProjectTaskSeeder
Location: `database/seeders/Dev/ProjectTaskSeeder.php`
```php
Per project create 5-20 tasks:
- Task dependencies
- Assigned users
- Due dates
- Status tracking
- Time estimates
- DO NOT STOP until all tasks exist
```

#### 11. Create InvoiceSeeder
Location: `database/seeders/Dev/InvoiceSeeder.php`
```php
Generate 6 months of invoices:

Monthly recurring for all clients with contracts
Project invoices for completed projects
Ad-hoc invoices for break-fix work

Status:
- 70% Paid
- 20% Sent/Outstanding
- 10% Overdue

Each invoice needs:
- Proper numbering sequence
- Due dates (net 30)
- Line items from tickets/projects
- Tax calculations
- DO NOT STOP until all invoices exist
```

#### 12. Create InvoiceItemSeeder
Location: `database/seeders/Dev/InvoiceItemSeeder.php`
```php
Per invoice create line items:
- Managed services fees
- Project work
- Time and materials
- Hardware/software sales
- Quantities, rates, descriptions
- Tax amounts where applicable
- DO NOT STOP until all items exist
```

#### 13. Create PaymentSeeder
Location: `database/seeders/Dev/PaymentSeeder.php`
```php
For 70% of invoices create payments:
- Payment date (within terms)
- Payment method (40% credit card, 30% ACH, 20% check, 10% wire)
- Reference numbers
- Full or partial payments
- DO NOT STOP until payments are recorded
```

#### 14. Create RecurringInvoiceSeeder
Location: `database/seeders/Dev/RecurringInvoiceSeeder.php`
```php
For all managed services contracts:
- Monthly recurring setup
- Next run dates
- Template line items
- Active status
- DO NOT STOP until recurring invoices exist
```

#### 15. Create LeadSeeder
Location: `database/seeders/Dev/LeadSeeder.php`
```php
Per company create 30-50 leads:
- Company information
- Contact details
- Source (Website, Referral, Cold Call, etc.)
- Status (New, Qualified, Proposal, Won/Lost)
- Estimated value
- Notes and follow-ups
- DO NOT STOP until all leads exist
```

#### 16. Create QuoteSeeder
Location: `database/seeders/Dev/QuoteSeeder.php`
```php
Create quotes for:
- 50% of leads
- Some existing clients (upgrades)
Status:
- Draft, Sent, Accepted, Rejected
- Expiration dates
- Line items with pricing
- Terms and conditions
- DO NOT STOP until quotes are complete
```

#### 17. Create ExpenseSeeder
Location: `database/seeders/Dev/ExpenseSeeder.php`
```php
Generate 6 months of expenses:
- Hardware purchases
- Software licenses
- Travel expenses
- Office supplies
- Vendor assignments
- Receipt attachments references
- DO NOT STOP until expenses are recorded
```

#### 18. Create KnowledgeBaseSeeder
Location: `database/seeders/Dev/KnowledgeBaseSeeder.php`
```php
Create 50-100 KB articles:
Categories:
- How-To Guides
- Troubleshooting
- Best Practices
- FAQs
- Policies

Each article needs:
- Title, content (use Lorem Ipsum)
- Category assignment
- Tags
- Author
- View counts
- Helpful/not helpful votes
- DO NOT STOP until KB is populated
```

#### 19. Create IntegrationSeeder
Location: `database/seeders/Dev/IntegrationSeeder.php`
```php
Create mock integrations:
- RMM platforms (ConnectWise, NinjaOne)
- Documentation (IT Glue, Hudu)
- PSA systems
- Backup solutions
- Include API credentials (fake)
- Mapping configurations
- DO NOT STOP until integrations exist
```

#### 20. Create ReportTemplateSeeder
Location: `database/seeders/Dev/ReportTemplateSeeder.php`
```php
Create report templates:
- Monthly client reports
- SLA compliance reports
- Financial summaries
- Ticket analytics
- Asset inventory reports
- Project status reports
- DO NOT STOP until templates exist
```

### Data Relationships You Must Maintain:

1. **Every ticket must have**:
   - Valid client_id
   - Valid created_by user_id
   - Optional but valid: asset_id, contact_id, location_id, project_id

2. **Every invoice must have**:
   - Valid client_id
   - Valid category_id
   - At least one invoice item
   - Optional but valid: contract_id

3. **Every asset must have**:
   - Valid client_id
   - Valid company_id
   - Optional but valid: location_id, contact_id, vendor_id, network_id

4. **Every contract must have**:
   - Valid client_id
   - Valid company_id
   - At least one schedule
   - Optional but valid: quote_id, template_id

5. **Every payment must have**:
   - Valid invoice_id
   - Valid client_id
   - Amount <= invoice balance

### IMPORTANT REMINDERS FOR AGENT 2:
- You MUST create ALL of the above seeders
- Assume Agent 1's data exists (companies, users, clients, contacts)
- Maintain ALL foreign key relationships
- Use realistic data distributions
- Add variety to avoid repetitive data
- Include edge cases (overdue items, expired contracts, etc.)
- DO NOT STOP until everything above is complete
- Test your seeders with: `php artisan db:seed --class=Dev\\DevDatabaseSeeder`

---

## Testing Instructions (For Both Agents)

After completing your assigned seeders:

1. Run migration fresh: `php artisan migrate:fresh`
2. Run your seeders: `php artisan db:seed --class=Dev\\DevDatabaseSeeder`
3. Verify no errors occur
4. Check database for:
   - Correct record counts
   - Valid foreign keys
   - Realistic data distribution
   - No orphaned records

## Environment Configuration

Both agents should ensure these environment variables are considered:
```env
APP_ENV=local
DB_CONNECTION=mysql
SEED_COMPANIES=5
SEED_MONTHS_HISTORY=6
```

## Final Checklist

### Agent 1 Must Complete:
- [ ] DevDatabaseSeeder (orchestrator)
- [ ] CompanySeeder (5 companies)
- [ ] SettingsSeeder (per company)
- [ ] UserSeeder (all users with roles)
- [ ] RolesAndPermissionsSeeder (Bouncer setup)
- [ ] CategorySeeder (all types)
- [ ] VendorSeeder (10-15 per company)
- [ ] ClientSeeder (40-50 per company)
- [ ] LocationSeeder (all client locations)
- [ ] ContactSeeder (all client contacts)
- [ ] NetworkSeeder (network configurations)
- [ ] TaxSeeder (jurisdictions and rates)

### Agent 2 Must Complete:
- [ ] AssetSeeder (all client assets)
- [ ] AssetWarrantySeeder (60% coverage)
- [ ] ContractTemplateSeeder (5 templates)
- [ ] SLASeeder (4 levels per company)
- [ ] ContractSeeder (coverage per client size)
- [ ] ContractScheduleSeeder (A & B schedules)
- [ ] TicketSeeder (6 months history)
- [ ] TicketReplySeeder (2-10 per ticket)
- [ ] ProjectSeeder (30-40 per company)
- [ ] ProjectTaskSeeder (5-20 per project)
- [ ] InvoiceSeeder (6 months history)
- [ ] InvoiceItemSeeder (line items)
- [ ] PaymentSeeder (70% payment rate)
- [ ] RecurringInvoiceSeeder (for contracts)
- [ ] LeadSeeder (30-50 per company)
- [ ] QuoteSeeder (for leads and upgrades)
- [ ] ExpenseSeeder (6 months history)
- [ ] KnowledgeBaseSeeder (50-100 articles)
- [ ] IntegrationSeeder (mock integrations)
- [ ] ReportTemplateSeeder (standard reports)

## CRITICAL: DO NOT STOP
Both agents MUST complete ALL assigned tasks. The database will not function properly with partial data. Each seeder depends on others, so you must implement everything in your section completely.

**Agent 1**: Your foundation data is critical for Agent 2
**Agent 2**: Your operational data completes the system

BOTH AGENTS: Do not stop working until every checkbox above in your section is complete!