# Ticket Billing UI - User Guide

## Where to Control Ticket Billing in the UI

The ticket billing system can now be controlled from **3 locations** in the application:

---

## 1. Settings Page - Global Configuration

### Location
**Settings → Billing & Financial → Ticket Billing**

### URL
`/settings/billing-financial` (then select "Ticket Billing" tab)

### What You Can Do

**View Statistics:**
- See how many tickets are pending billing
- Monitor billing queue jobs
- Check system status (enabled/disabled)

**Quick Actions:**
- **Process Pending Tickets** - Manually trigger billing for all unbilled tickets
- **Dry Run (Preview)** - See what would be billed without actually billing
- **Refresh Stats** - Update the statistics dashboard

**Configure Settings:**

1. **Master Control**
   - Enable/Disable entire billing system

2. **Auto-Billing Triggers** (Recommended: Keep OFF initially)
   - Auto-bill when ticket is closed
   - Auto-bill when ticket is resolved

3. **Billing Strategy**
   - Time-Based (hourly billing)
   - Per-Ticket (flat fee)
   - Mixed (combination)

4. **Time & Rounding**
   - Minimum billable hours (e.g., 0.25 = 15 min)
   - Round hours to (15 min, 30 min, or 1 hour)

5. **Invoice Settings**
   - Invoice due days
   - Require manual approval (recommended: ON)
   - Skip zero amount invoices
   - Auto-send invoices (only if approval is OFF)

6. **Processing Settings**
   - Batch size for scheduled tasks

### Screenshots Location
![Settings Dashboard](docs/screenshots/billing-settings.png)

---

## 2. Ticket Detail Page - Individual Ticket Billing

### Location
**Tickets → View Ticket #XXX**

### URL
`/tickets/{id}`

### What You Can Do

**Visible When:**
- Ticket is billable (`billable = true`)
- Ticket is closed or resolved
- Ticket is NOT already invoiced

**Action Buttons:**

1. **"Generate Invoice" Button** (Green, in header)
   - Click to immediately generate an invoice for this ticket
   - Shows "Generating..." while processing
   - Redirects to invoice when complete

2. **Dropdown Menu → "Generate Invoice"**
   - Same action available in the more actions menu
   - Only shows if ticket is billable and not invoiced

3. **"View Invoice" Button** (if already invoiced)
   - Takes you directly to the generated invoice
   - Shows invoice number and amount

### What Happens When You Click "Generate Invoice"

1. System checks if ticket can be billed
2. Determines billing strategy (time/per-ticket/mixed)
3. Calculates total amount based on:
   - Time entries (if time-based)
   - Contract rate (if per-ticket)
   - Both (if mixed)
4. Creates invoice (as draft if approval required)
5. Links invoice to ticket
6. Shows success message with invoice details
7. Redirects to invoice page

### Validation

The button won't work if:
- ❌ Ticket is not billable
- ❌ Ticket is already invoiced
- ❌ Ticket has no time entries AND no contract rate
- ❌ Ticket is not closed or resolved

---

## 3. Command Line Interface (CLI)

### For Developers/Admins

**Process Pending Tickets:**
```bash
# See what would be processed
php artisan billing:process-pending-tickets --dry-run

# Process up to 100 tickets
php artisan billing:process-pending-tickets --limit=100

# Process for specific client
php artisan billing:process-pending-tickets --client=123

# Force re-billing (even if invoiced)
php artisan billing:process-pending-tickets --force
```

**Available Flags:**
- `--limit=N` - Process N tickets max
- `--company=ID` - Filter by company
- `--client=ID` - Filter by client
- `--dry-run` - Preview only, don't actually bill
- `--force` - Force billing even if already invoiced

---

## Typical Workflows

### Workflow 1: Testing the System (Recommended First Steps)

1. **Go to Settings → Billing & Financial**
2. **Enable the system** (but keep auto-billing OFF)
   - `TICKET_BILLING_ENABLED = true`
   - `AUTO_BILL_ON_CLOSE = false`
3. **Click "Dry Run (Preview)"** to see pending tickets
4. **Click "Process Pending Tickets"**
   - Start with 10-20 tickets
   - Review the generated invoices
5. **Check the invoices** (Invoices page)
   - Verify amounts are correct
   - Check line items
   - Approve if everything looks good
6. **Repeat** until confident
7. **Enable auto-billing** when ready

### Workflow 2: Manual Billing for a Single Ticket

1. **Open ticket** you want to bill
2. **Verify ticket is billable**
   - Check for "billable" badge
   - Check time entries exist OR client has contract rate
3. **Close or resolve the ticket** (if not already)
4. **Click "Generate Invoice"** button
5. **Review the invoice**
6. **Approve and send** when ready

### Workflow 3: Bulk Processing (Monthly Billing)

1. **Go to Settings → Billing & Financial**
2. **Check statistics**
   - Note how many pending tickets
3. **Click "Process Pending Tickets"**
   - System queues all unbilled tickets
4. **Wait for queue to process** (or run queue worker)
5. **Go to Invoices page**
6. **Filter by status = "Draft"**
7. **Review all generated invoices**
8. **Bulk approve** when ready
9. **Send to clients**

### Workflow 4: Fully Automated (For Production)

1. **Enable auto-billing** in settings
   - `AUTO_BILL_ON_CLOSE = true`
2. **Set invoices to require approval**
   - `BILLING_REQUIRE_APPROVAL = true`
3. **Close tickets normally**
   - Invoices auto-generate in background
4. **Daily review**
   - Check Invoices page for new drafts
   - Approve and send
5. **Weekly check**
   - Run "Process Pending Tickets" to catch any missed

---

## UI Features Explained

### Settings Page Features

**Statistics Cards:**
- **Pending Tickets** - Count of unbilled closed/resolved tickets
- **Queue Jobs** - Active billing jobs being processed
- **System Status** - Enabled/Disabled indicator

**Color Coding:**
- 🟢 Green = Enabled/Active
- 🔴 Red = Disabled/Error
- 🔵 Blue = Info/Pending
- 🟡 Yellow = Warning

**Form Validation:**
- All fields are validated in real-time
- Invalid values show error messages
- Can't save until all fields are valid

**Banner Messages:**
- 🔵 Info banner shows when pending tickets exist
- 🟡 Warning banner shows when auto-billing is enabled
- 🔴 Error banner shows when system is disabled

### Ticket Page Features

**Button States:**
- **Primary (Green)** - Action available, click to proceed
- **Disabled (Gray)** - Action not available (reason shown in tooltip)
- **Processing (Spinning)** - Action in progress, please wait
- **Success (Checkmark)** - Action completed successfully

**Visual Indicators:**
- Green "Generate Invoice" button = Ready to bill
- Blue "View Invoice" button = Already invoiced
- No button = Not billable or conditions not met

---

## Permissions & Access Control

### Who Can Access Billing Settings
- **Admins** - Full access to all settings
- **Managers** - Can view and modify settings
- **Users** - No access to settings page

### Who Can Generate Invoices
- **Admins** - Can generate from any ticket
- **Managers** - Can generate from assigned tickets
- **Technicians** - Can generate from own tickets (if permitted)
- **Clients** - Cannot generate invoices

---

## Troubleshooting

### "Generate Invoice" Button Not Showing

**Check:**
1. Is ticket billable? (should show "billable" badge)
2. Is ticket closed or resolved?
3. Is ticket already invoiced? (check for "View Invoice" button)
4. Does ticket have time entries OR contract rate?

### Button Grayed Out / Disabled

**Reasons:**
- System is disabled in settings
- You don't have permission
- Ticket doesn't meet requirements
- Billing is already in progress

### Invoice Generated But Amount is $0

**This happens when:**
- No billable time entries recorded
- No contract rate set for client/contact
- All time entries marked as non-billable

**Fix:**
- Add time entries and mark as billable
- Set up contract with per-ticket rate
- Check billing strategy in settings

### "This ticket cannot be billed" Error

**Check:**
1. Settings → Billing enabled?
2. Ticket has time entries (for time-based)?
3. Contact has active contract (for per-ticket)?
4. Time entries marked as billable?
5. Ticket marked as billable?

---

## Best Practices

### For Testing
1. ✅ Start with auto-billing OFF
2. ✅ Use dry-run mode first
3. ✅ Process small batches (10-20 tickets)
4. ✅ Review every invoice manually
5. ✅ Keep approval required ON

### For Production
1. ✅ Enable auto-billing only after testing
2. ✅ Keep approval required ON initially
3. ✅ Daily review of generated invoices
4. ✅ Weekly run of "Process Pending Tickets"
5. ✅ Monthly audit of billing accuracy

### For Accuracy
1. ✅ Ensure time entries are complete
2. ✅ Mark billable vs non-billable correctly
3. ✅ Set up contracts with correct rates
4. ✅ Close tickets promptly
5. ✅ Review before approving invoices

---

## Support

### Need Help?
1. Check the settings page for statistics
2. Use dry-run mode to preview
3. Review logs at `storage/logs/ticket-billing.log`
4. Contact your system administrator

### Reporting Issues
When reporting issues, include:
- Ticket ID
- Error message (if any)
- What you expected to happen
- What actually happened
- Screenshots if applicable

---

## Quick Reference

| Action | Location | Permission Required |
|--------|----------|-------------------|
| View billing settings | Settings → Billing & Financial | Admin, Manager |
| Change billing settings | Settings → Billing & Financial | Admin |
| Process pending tickets | Settings → Billing & Financial | Admin, Manager |
| Generate invoice from ticket | Ticket detail page | Admin, Manager, Assigned User |
| View generated invoice | Ticket detail page | Anyone who can view ticket |
| Approve invoice | Invoices page | Admin, Manager |

| Status | What It Means |
|--------|---------------|
| Pending | Ticket is ready to be billed |
| Processing | Billing job is running |
| Invoiced | Invoice has been generated |
| Draft | Invoice created but needs approval |
| Sent | Invoice sent to client |

---

**Last Updated:** November 6, 2025  
**Version:** 1.0  
**Status:** Production Ready
