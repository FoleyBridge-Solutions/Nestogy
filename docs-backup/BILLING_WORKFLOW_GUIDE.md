# Billing Workflow Quick Start Guide

## Overview
This guide explains how to use the new billing features implemented in Tasks 1.1-1.4.

---

## 1. Setting Up Rate Cards

### Via Tinker
```bash
php artisan tinker
```

```php
use App\Domains\Financial\Models\RateCard;
use App\Models\Client;

$client = Client::find(1);

// Create standard rate
RateCard::create([
    'company_id' => $client->company_id,
    'client_id' => $client->id,
    'name' => 'Standard Hourly Rate',
    'service_type' => 'standard',
    'hourly_rate' => 125.00,
    'effective_from' => now(),
    'is_default' => true,
    'is_active' => true,
    'rounding_increment' => 15,  // 15 minutes
    'rounding_method' => 'up',   // Always round up
]);

// Create after-hours rate
RateCard::create([
    'company_id' => $client->company_id,
    'client_id' => $client->id,
    'name' => 'After Hours Premium',
    'service_type' => 'after_hours',
    'hourly_rate' => 187.50,  // 1.5x premium
    'effective_from' => now(),
    'is_active' => true,
    'rounding_increment' => 15,
    'rounding_method' => 'up',
]);
```

### Rate Card Options

**Service Types:**
- `standard` - Regular business hours
- `after_hours` - Evenings/nights
- `emergency` - Urgent calls
- `weekend` - Saturday/Sunday
- `holiday` - Holiday work
- `project` - Project-based work
- `consulting` - Advisory services

**Rounding Methods:**
- `up` - Always round up (e.g., 1.1 hours → 1.25 hours)
- `down` - Always round down (e.g., 1.9 hours → 1.75 hours)
- `nearest` - Round to nearest (e.g., 1.1 hours → 1.0 hours)
- `none` - No rounding

**Rounding Increments:**
- `6` - 6 minutes (0.1 hour)
- `15` - 15 minutes (0.25 hour) - Most common
- `30` - 30 minutes (0.5 hour)
- `60` - 60 minutes (1 hour)

---

## 2. Time Entry to Invoice Workflow

### Step 1: Log Time
Technicians log time on tickets as usual. Time entries are tracked in `ticket_time_entries` table.

### Step 2: Navigate to Billing Approval
Go to: **`/billing/time-entries`**

### Step 3: Filter Entries
- Select client from dropdown
- Choose date range
- Select technician (optional)
- Toggle "Billable only" checkbox

### Step 4: Review & Select
- Review unbilled time entries
- Check individual entries or "Select All"
- Verify hours and descriptions

### Step 5: Preview Invoice
- Click "Preview Invoice" button
- Review line items and totals
- Check grouping (by ticket/date/tech)

### Step 6: Generate Invoice
- Click "Generate Invoice" in preview modal
- Invoice created automatically
- Time entries marked as invoiced
- Redirected to invoice view

---

## 3. Export to Accounting Software

### From Time Entry Approval Page

1. Set filters (client, date range, etc.)
2. Click "Export" dropdown
3. Select format:
   - **CSV** - Universal format
   - **QuickBooks IIF** - Direct QuickBooks import
   - **Xero CSV** - Xero time tracking import

### Export Contents

**CSV Columns:**
- Date
- Client
- Ticket #
- Technician
- Description
- Hours
- Rate
- Amount
- Billable (Yes/No)
- Invoiced (Yes/No)
- Invoice #

**QuickBooks IIF Format:**
```
!TIMACT DATE CUST SERV DURATION NOTE BILLSTATUS
TIMACT 10/02/2025 Acme Corp Managed Services 2:30 Working on ticket #1234 Billable
```

**Xero CSV Columns:**
- ContactName (client)
- Description
- Date
- DurationInHours
- Rate
- Project (ticket #)
- Task (work type)

---

## 4. Bulk Operations

### Bulk Approve
1. Select multiple time entries
2. Click "Approve Selected"
3. All entries marked as approved

### Bulk Reject
1. Select multiple time entries
2. Click "Reject Selected"
3. All entries marked as rejected

### Bulk Invoice Generation
Use the service directly:

```php
use App\Domains\Financial\Services\TimeEntryInvoiceService;

$service = new TimeEntryInvoiceService();

$results = $service->bulkGenerateInvoices(
    clientIds: [1, 2, 3],
    startDate: now()->startOfMonth(),
    endDate: now()->endOfMonth(),
    options: ['groupBy' => 'ticket']
);

// Returns:
// ['success' => [...], 'failed' => [...], 'skipped' => [...]]
```

---

## 5. Rate Card Business Logic

### How Rates are Applied

1. **Lookup Order:**
   ```
   1. Client-specific rate card for service type
   2. Client default rate card (applies_to_all_services = true)
   3. Time entry hourly_rate field
   4. Client hourly_rate field
   5. Default rate ($100)
   ```

2. **Calculation:**
   ```php
   // Example: 1.7 hours at $125/hr with 15-min rounding (up)
   
   Step 1: Apply minimum hours
   - minimum_hours = 2.0
   - actual_hours = 1.7
   - Result: 2.0 hours (minimum enforced)
   
   Step 2: Apply rounding
   - 2.0 hours = 8 increments of 15 minutes
   - Already on increment boundary
   - Result: 2.0 hours
   
   Step 3: Calculate amount
   - 2.0 hours × $125/hr = $250.00
   ```

3. **Effective Dates:**
   - Rate must be active (`is_active = true`)
   - Current date must be >= `effective_from`
   - Current date must be <= `effective_to` (or null)

---

## 6. Grouping Options

### By Ticket (Default)
```
Invoice Item 1: Ticket #1234: Email server down
  - 3.5 hours @ $125/hr = $437.50

Invoice Item 2: Ticket #1235: Password reset
  - 0.5 hours @ $125/hr = $62.50
```

### By Date
```
Invoice Item 1: Services provided on Oct 1, 2025
  - 4.0 hours @ $125/hr = $500.00

Invoice Item 2: Services provided on Oct 2, 2025
  - 6.5 hours @ $125/hr = $812.50
```

### By Technician
```
Invoice Item 1: Services by John Smith
  - 5.0 hours @ $125/hr = $625.00

Invoice Item 2: Services by Jane Doe
  - 3.5 hours @ $125/hr = $437.50
```

### Combined (No Grouping)
```
Invoice Item 1: Professional Services
  - 15.5 hours across 8 tickets @ $125/hr = $1,937.50
```

---

## 7. Common Scenarios

### Scenario: New Client Setup
```php
// 1. Create client
$client = Client::create([...]);

// 2. Create rate card
RateCard::create([
    'client_id' => $client->id,
    'service_type' => 'standard',
    'hourly_rate' => 150.00,
    'rounding_increment' => 15,
    'rounding_method' => 'up',
    'is_default' => true,
]);

// 3. Technicians log time
// 4. Manager approves and generates invoice
```

### Scenario: Rate Increase
```php
// Old rate
$oldRate = RateCard::find(1);
$oldRate->update(['effective_to' => today()]);

// New rate starting tomorrow
RateCard::create([
    'client_id' => $client->id,
    'service_type' => 'standard',
    'hourly_rate' => 175.00,  // Increased from $150
    'effective_from' => tomorrow(),
    'rounding_increment' => 15,
    'rounding_method' => 'up',
    'is_default' => true,
]);
```

### Scenario: Special Project Rate
```php
RateCard::create([
    'client_id' => $client->id,
    'service_type' => 'project',
    'hourly_rate' => 200.00,  // Premium rate
    'effective_from' => now(),
    'effective_to' => now()->addMonths(3),  // Limited time
    'minimum_hours' => 4.0,  // 4-hour minimum
]);
```

---

## 8. Troubleshooting

### Time entries not showing up
- ✅ Check `invoice_id` is NULL (uninvoiced filter)
- ✅ Check date range includes entry dates
- ✅ Check client filter matches entry client
- ✅ Check `billable` flag if "Billable only" is checked

### Wrong rate being applied
- ✅ Check rate card `is_active = true`
- ✅ Check effective dates include work date
- ✅ Check service type matches (or use `applies_to_all_services`)
- ✅ Check client has rate card assigned

### Rounding not working as expected
- ✅ Verify `rounding_increment` value (6, 15, 30, or 60)
- ✅ Verify `rounding_method` (up, down, nearest, none)
- ✅ Check minimum hours setting

### Export file empty
- ✅ Ensure date range includes entries
- ✅ Check filters don't exclude all entries
- ✅ Verify browser download settings

---

## 9. API Usage (Programmatic)

### Generate Invoice Programmatically
```php
use App\Domains\Financial\Services\TimeEntryInvoiceService;

$service = new TimeEntryInvoiceService();

// Get uninvoiced entries
$entries = $service->getUninvoicedTimeEntries(
    clientId: 1,
    startDate: now()->startOfMonth(),
    endDate: now()->endOfMonth()
);

// Preview invoice
$preview = $service->previewInvoice(
    $entries->pluck('id')->toArray(),
    clientId: 1,
    options: ['groupBy' => 'ticket']
);

// Generate if looks good
if ($preview['total'] > 0) {
    $invoice = $service->generateInvoiceFromTimeEntries(
        $entries->pluck('id')->toArray(),
        clientId: 1,
        options: [
            'groupBy' => 'ticket',
            'prefix' => 'INV',
            'due_date' => now()->addDays(30),
        ]
    );
}
```

### Export Programmatically
```php
use App\Domains\Financial\Services\AccountingExportService;

$service = new AccountingExportService();

$export = $service->downloadExport(
    startDate: now()->startOfMonth(),
    endDate: now()->endOfMonth(),
    format: 'quickbooks_iif',
    filters: [
        'company_id' => 1,
        'client_id' => 5,
        'billable_only' => true,
    ]
);

// Returns: ['content' => '...', 'filename' => '...', 'mime_type' => '...']

// Save to file
file_put_contents($export['filename'], $export['content']);
```

---

## 10. Best Practices

### For Managers
1. ✅ Review time entries weekly
2. ✅ Set up rate cards before first invoice
3. ✅ Use preview before generating invoices
4. ✅ Export to accounting system monthly
5. ✅ Keep rate cards up to date

### For Billing Staff
1. ✅ Filter by client before bulk operations
2. ✅ Verify rates before approval
3. ✅ Use grouping that makes sense for client
4. ✅ Check for missing descriptions
5. ✅ Export regularly for reconciliation

### For Admins
1. ✅ Document rate changes
2. ✅ Use effective dates for rate transitions
3. ✅ Test rounding rules with sample data
4. ✅ Set appropriate minimums (1-4 hours typical)
5. ✅ Review export formats with accountant

---

**Need Help?** Check `/opt/nestogy/IMPLEMENTATION_COMPLETE_TASKS_1-6.md` for complete technical documentation.
