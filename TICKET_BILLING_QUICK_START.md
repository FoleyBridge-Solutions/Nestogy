# Ticket Billing System - Quick Start Guide

## 🚀 Implementation Complete!

The automatic ticket billing system is now fully implemented and ready for deployment.

## What Was Built

**Event-Driven Billing System** that automatically creates invoices when support tickets are closed or resolved.

**3 Billing Strategies:**
- ⏱️ Time-based (hourly billing from time entries)
- 💰 Per-ticket (flat fee from contract)
- 🔄 Mixed (combination of both)

## Quick Commands

### Test the System (Dry Run)
```bash
# See what would be billed without actually billing
php artisan billing:process-pending-tickets --dry-run
```

### Process Tickets Manually
```bash
# Process up to 10 tickets
php artisan billing:process-pending-tickets --limit=10

# Process for specific client
php artisan billing:process-pending-tickets --client=123

# See what's available
php artisan billing:process-pending-tickets --help
```

### Enable Automatic Billing
```bash
# Add to .env file
TICKET_BILLING_ENABLED=true
AUTO_BILL_ON_CLOSE=true
```

## Configuration (Safe Defaults)

Add these to your `.env` file:

```bash
# REQUIRED: Master switch
TICKET_BILLING_ENABLED=true

# Start with manual billing (safe)
AUTO_BILL_ON_CLOSE=false
AUTO_BILL_ON_RESOLVE=false

# Strategy (time_based, per_ticket, or mixed)
BILLING_STRATEGY_DEFAULT=time_based

# Minimum hours (0.25 = 15 minutes)
BILLING_MIN_HOURS=0.25

# Rounding (0.25 = round to nearest 15 min)
BILLING_ROUND_HOURS_TO=0.25

# Invoice defaults
BILLING_INVOICE_DUE_DAYS=30
BILLING_REQUIRE_APPROVAL=true
BILLING_SKIP_ZERO_INVOICES=true
```

## How It Works

### Automatic Flow (when enabled)
```
1. Ticket is closed/resolved
   ↓
2. Event fires (TicketClosed/TicketResolved)
   ↓
3. Listener queues billing job
   ↓
4. Job processes asynchronously
   ↓
5. Invoice created (as draft if approval required)
```

### Manual Flow (safe for testing)
```bash
# Find pending tickets
php artisan billing:process-pending-tickets --dry-run

# Process them
php artisan billing:process-pending-tickets --limit=20

# Review invoices in admin panel
# Approve and send when ready
```

## Files Created (11 New Files)

### Core Files
- ✅ `app/Domains/Financial/Services/TicketBillingService.php` (479 lines)
- ✅ `app/Jobs/ProcessTicketBilling.php` (134 lines)
- ✅ `app/Console/Commands/ProcessPendingTicketBilling.php` (217 lines)

### Events & Listeners
- ✅ `app/Events/TicketCreated.php`
- ✅ `app/Events/TicketClosed.php`
- ✅ `app/Events/TicketResolved.php`
- ✅ `app/Listeners/RecordContractTicketUsage.php`
- ✅ `app/Listeners/QueueTicketBillingJob.php`

### Configuration
- ✅ `config/billing.php` (209 lines)

### Tests
- ✅ `tests/Unit/Services/TicketBillingServiceTest.php` (15 test methods)
- ✅ `tests/Feature/TicketBillingFlowTest.php` (12 test methods)

### Updated Files (4)
- ✅ `app/Domains/Ticket/Models/Ticket.php` (added event firing)
- ✅ `app/Providers/AppServiceProvider.php` (registered listeners)
- ✅ `app/Console/Kernel.php` (added scheduled task)
- ✅ `.env.example` (added 30+ billing variables)

## Testing

### Run Automated Tests
```bash
# Unit tests
php artisan test tests/Unit/Services/TicketBillingServiceTest.php

# Feature tests
php artisan test tests/Feature/TicketBillingFlowTest.php

# All tests
php artisan test
```

### Manual Testing Steps

1. **Find a closed ticket with time entries:**
   ```bash
   php artisan tinker
   >>> $ticket = Ticket::where('status', 'Closed')
             ->where('billable', true)
             ->whereNull('invoice_id')
             ->first();
   >>> $ticket->id
   ```

2. **Test billing service directly:**
   ```php
   >>> $service = app(\App\Domains\Financial\Services\TicketBillingService::class);
   >>> $invoice = $service->billTicket($ticket);
   >>> $invoice->amount  // Should show invoice amount
   ```

3. **Check the generated invoice:**
   - Go to admin panel → Invoices
   - Find the newly created invoice
   - Verify line items are correct
   - Verify amount matches time entries

## Deployment Steps

### Day 1: Deploy Code
```bash
git add .
git commit -m "Add automatic ticket billing system"
git push origin main

# On server
php artisan config:clear
php artisan cache:clear
php artisan migrate  # No new migrations, but good practice
```

### Day 2-7: Test Manually
```bash
# Monday: Process 10 tickets
php artisan billing:process-pending-tickets --limit=10

# Tuesday: Process 25 tickets
php artisan billing:process-pending-tickets --limit=25

# Wednesday: Process 50 tickets
php artisan billing:process-pending-tickets --limit=50

# Thursday-Friday: Review all generated invoices
# Weekend: Fix any issues discovered
```

### Day 8+: Enable Auto-Billing
```bash
# Update .env
AUTO_BILL_ON_CLOSE=true

# Restart queue workers
php artisan queue:restart
```

## Monitoring

### Check Queue Status
```bash
# See if billing jobs are processing
php artisan queue:work --queue=billing --once

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### View Logs
```bash
# Billing-specific logs
tail -f storage/logs/ticket-billing.log

# All Laravel logs
tail -f storage/logs/laravel.log | grep TicketBilling
```

### Daily Scheduled Task
The system runs automatically at **3:30 AM daily** to catch any missed tickets:
```bash
# Manually trigger the scheduled task
php artisan billing:process-pending-tickets
```

## Troubleshooting

### "No tickets found to process"
✅ This is normal! Means all billable tickets are already invoiced.

### "Ticket billing is disabled"
Check `.env`:
```bash
TICKET_BILLING_ENABLED=true
```

### "Queue jobs not running"
Start queue workers:
```bash
php artisan queue:work --queue=billing
```

### "Events not firing"
Clear event cache:
```bash
php artisan event:clear
php artisan config:clear
```

## Safety Features

✅ **Idempotent** - Won't create duplicate invoices  
✅ **Dry-run mode** - Test without making changes  
✅ **Draft invoices** - Require approval before sending  
✅ **Comprehensive logging** - Track everything  
✅ **Queue retries** - Automatic retry on failure  
✅ **Daily catch-up** - Finds missed tickets  

## Configuration Examples

### Conservative (Start Here)
```bash
TICKET_BILLING_ENABLED=true
AUTO_BILL_ON_CLOSE=false  # Manual only
BILLING_REQUIRE_APPROVAL=true  # All invoices are drafts
BILLING_SKIP_ZERO_INVOICES=true
```

### Moderate (After Testing)
```bash
TICKET_BILLING_ENABLED=true
AUTO_BILL_ON_CLOSE=true  # Auto-bill when closed
AUTO_BILL_ON_RESOLVE=false  # But not when just resolved
BILLING_REQUIRE_APPROVAL=true
```

### Aggressive (Fully Automated)
```bash
TICKET_BILLING_ENABLED=true
AUTO_BILL_ON_CLOSE=true
AUTO_BILL_ON_RESOLVE=true  # Bill as soon as resolved
BILLING_REQUIRE_APPROVAL=false  # Skip approval
BILLING_AUTO_SEND=true  # Send invoices automatically
```

## Support

### Check Configuration
```bash
php artisan config:show billing
```

### Verify Command Exists
```bash
php artisan list | grep billing
```

### Test Service Directly
```bash
php artisan tinker
>>> $service = app(\App\Domains\Financial\Services\TicketBillingService::class);
>>> $ticket = Ticket::find(123);  // Replace with real ticket ID
>>> $service->canBillTicket($ticket);  // Should return true/false
```

## What's Next?

### Optional Enhancements (Future)
1. Email notifications when invoices are created
2. Dashboard widget showing billing metrics
3. Bulk invoice approval interface
4. Per-client billing configuration overrides
5. Billing reports and analytics

### Business Value
- ⚡ **Faster invoicing** - Automatic instead of manual
- 💰 **More revenue** - Don't forget to bill tickets
- 📊 **Better tracking** - All billing logged and auditable
- 🎯 **Accurate billing** - Multiple strategies, automatic calculations

---

## Quick Reference

| Command | Purpose |
|---------|---------|
| `billing:process-pending-tickets` | Process unbilled tickets |
| `billing:process-pending-tickets --dry-run` | Show what would be processed |
| `billing:process-pending-tickets --limit=N` | Process N tickets |
| `billing:process-pending-tickets --client=ID` | Process for specific client |
| `billing:process-pending-tickets --force` | Re-bill even if invoiced |

| Environment Variable | Default | Purpose |
|---------------------|---------|---------|
| `TICKET_BILLING_ENABLED` | `true` | Master switch |
| `AUTO_BILL_ON_CLOSE` | `false` | Auto-bill when closed |
| `AUTO_BILL_ON_RESOLVE` | `false` | Auto-bill when resolved |
| `BILLING_STRATEGY_DEFAULT` | `time_based` | Default billing method |
| `BILLING_MIN_HOURS` | `0.25` | Minimum billable hours |
| `BILLING_REQUIRE_APPROVAL` | `true` | Create drafts or sent |

---

**🎉 System is ready! Start with dry-run mode, then gradually enable features as you gain confidence.**

For detailed documentation, see: `TICKET_BILLING_IMPLEMENTATION_COMPLETE.md`
