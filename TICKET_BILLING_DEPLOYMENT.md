# Ticket Billing System - Deployment Checklist

## ✅ Pre-Deployment Verification

### 1. Files Created (18 Total)

**Backend (11 files):**
- [x] `app/Events/TicketCreated.php` (457 bytes)
- [x] `app/Events/TicketClosed.php` (457 bytes)
- [x] `app/Events/TicketResolved.php` (459 bytes)
- [x] `app/Domains/Financial/Services/TicketBillingService.php` (16.8 KB)
- [x] `app/Listeners/RecordContractTicketUsage.php` (2.0 KB)
- [x] `app/Listeners/QueueTicketBillingJob.php` (2.5 KB)
- [x] `app/Jobs/ProcessTicketBilling.php` (4.0 KB)
- [x] `app/Console/Commands/ProcessPendingTicketBilling.php` (7.1 KB)
- [x] `config/billing.php` (7.2 KB)
- [x] `.env.example` (updated with 30+ variables)
- [x] `app/Domains/Ticket/Models/Ticket.php` (updated boot method)

**Frontend (4 files):**
- [x] `app/Livewire/Settings/TicketBillingSettings.php` (7.7 KB)
- [x] `resources/views/livewire/settings/ticket-billing-settings.blade.php` (11 KB)
- [x] `resources/views/settings/ticket-billing.blade.php` (153 bytes)
- [x] `app/Livewire/Tickets/TicketShow.php` (updated with generateInvoice())

**Tests (2 files):**
- [x] `tests/Unit/Services/TicketBillingServiceTest.php` (15 tests)
- [x] `tests/Feature/TicketBillingFlowTest.php` (12 tests)

**Documentation (1 file):**
- [x] `TICKET_BILLING_UI_GUIDE.md` (comprehensive user guide)

### 2. Routes Registered

```bash
# Verify route exists
php artisan route:list --name=ticket-billing
# Expected: settings/ticket-billing → TicketBillingSettings component

# Verify command exists  
php artisan list | grep billing
# Expected: billing:process-pending-tickets
```

### 3. Configuration Files

```bash
# Verify config file loads
php artisan config:show billing.ticket.enabled
# Expected: true or config value
```

### 4. Syntax Validation

```bash
# All files pass PHP lint
php -l app/Events/Ticket*.php
php -l app/Domains/Financial/Services/TicketBillingService.php
php -l app/Livewire/Settings/TicketBillingSettings.php
# All should return: No syntax errors detected
```

---

## 🚀 Deployment Steps

### Step 1: Deploy Code to Production

```bash
# On your local machine
git add .
git commit -m "Add automatic ticket billing system with full UI"
git push origin main

# On production server
cd /path/to/nestogy
git pull origin main
```

### Step 2: Update Environment Variables

```bash
# Edit .env file on production
nano .env

# Add these variables (safe defaults):
TICKET_BILLING_ENABLED=true
AUTO_BILL_ON_CLOSE=false
AUTO_BILL_ON_RESOLVE=false
BILLING_STRATEGY_DEFAULT=time_based
BILLING_MIN_HOURS=0.25
BILLING_ROUND_HOURS_TO=0.25
BILLING_INVOICE_DUE_DAYS=30
BILLING_QUEUE=billing
BILLING_JOB_RETRIES=3
BILLING_JOB_TIMEOUT=120
BILLING_REQUIRE_APPROVAL=true
BILLING_SKIP_ZERO_INVOICES=true
BILLING_AUTO_SEND=false
BILLING_BATCH_SIZE=100
BILLING_LOGGING_ENABLED=true
BILLING_LOG_CHANNEL=stack
BILLING_LOG_LEVEL=info
```

### Step 3: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
```

### Step 4: Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Step 5: Setup Queue Worker

**Option A: Supervisor (Recommended)**

Create `/etc/supervisor/conf.d/nestogy-billing.conf`:

```ini
[program:nestogy-billing-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/nestogy/artisan queue:work --queue=billing --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/nestogy/storage/logs/billing-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start nestogy-billing-worker:*
```

**Option B: Systemd Service**

Create `/etc/systemd/system/nestogy-billing.service`:

```ini
[Unit]
Description=Nestogy Billing Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/nestogy
ExecStart=/usr/bin/php artisan queue:work --queue=billing --sleep=3 --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable nestogy-billing
sudo systemctl start nestogy-billing
```

### Step 6: Verify Installation

```bash
# Test the command
php artisan billing:process-pending-tickets --dry-run

# Check logs
tail -f storage/logs/laravel.log

# Verify queue worker is running
ps aux | grep "queue:work"
```

### Step 7: Access UI

1. Open browser
2. Navigate to: `https://yourdomain.com/settings/ticket-billing`
3. Should see the Ticket Billing Settings page
4. Verify statistics show correctly

---

## 🧪 Testing in Production

### Test 1: Dry Run (No Changes Made)

```bash
php artisan billing:process-pending-tickets --dry-run --limit=10
```

**Expected:** Shows 10 pending tickets without creating invoices

### Test 2: Process Single Ticket

1. Find a closed ticket with time entries
2. Go to ticket detail page
3. Click "Generate Invoice" button
4. Should redirect to invoice page
5. Verify invoice amount is correct

### Test 3: Bulk Process (Small Batch)

```bash
php artisan billing:process-pending-tickets --limit=5
```

**Expected:** Creates 5 invoices, check Invoices page

### Test 4: Verify Queue Processing

1. Close a billable ticket
2. Check if job was queued: `php artisan queue:failed`
3. Monitor logs: `tail -f storage/logs/ticket-billing.log`

---

## ⚡ Gradual Rollout Plan

### Week 1: Testing Phase
- ✅ Deploy code
- ✅ Configure with AUTO_BILL_ON_CLOSE=false
- ✅ Manually process 10-20 tickets
- ✅ Review all generated invoices
- ✅ Train staff on UI

### Week 2: Limited Rollout
- ✅ Enable for 2-3 test clients
- ✅ Monitor closely
- ✅ Process daily batches manually
- ✅ Gather feedback

### Week 3: Expanded Rollout
- ✅ Enable for 10-15 clients
- ✅ Start using auto-billing for test clients
- ✅ Monitor queue performance
- ✅ Optimize as needed

### Week 4: Full Production
- ✅ Enable AUTO_BILL_ON_CLOSE=true globally
- ✅ Monitor daily
- ✅ Review weekly reports
- ✅ Document any issues

---

## 📊 Monitoring & Maintenance

### Daily Checks

```bash
# Check failed jobs
php artisan queue:failed

# Check pending tickets count
php artisan billing:process-pending-tickets --dry-run | grep "Found"

# Check logs for errors
tail -100 storage/logs/ticket-billing.log | grep ERROR
```

### Weekly Tasks

1. Review draft invoices requiring approval
2. Check billing accuracy (spot checks)
3. Monitor queue worker uptime
4. Review any failed billing jobs
5. Process any stuck tickets manually

### Monthly Reviews

1. Analyze billing patterns
2. Review automation rate (% auto-billed)
3. Check for edge cases
4. Optimize configuration if needed
5. Update documentation based on learnings

---

## 🔧 Troubleshooting

### Issue: Settings page shows 404

**Fix:**
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: "Generate Invoice" button not showing

**Check:**
1. Is ticket billable?
2. Is ticket closed/resolved?
3. Does ticket have time entries?
4. Check user permissions

**Debug:**
```php
// In tinker
$ticket = Ticket::find(123);
$service = app(\App\Domains\Financial\Services\TicketBillingService::class);
$service->canBillTicket($ticket);
```

### Issue: Queue jobs not processing

**Fix:**
```bash
# Restart queue worker
sudo supervisorctl restart nestogy-billing-worker:*

# Or systemd
sudo systemctl restart nestogy-billing
```

### Issue: Invoices have wrong amounts

**Check:**
1. Time entry hourly rates
2. Contract per-ticket rates
3. Minimum hours configuration
4. Rounding settings

**Review:**
```bash
# Enable debug logging
BILLING_LOG_LEVEL=debug

# Check detailed logs
tail -f storage/logs/ticket-billing.log
```

---

## 🎯 Success Metrics

Track these after deployment:

| Metric | Target | How to Measure |
|--------|--------|---------------|
| Automation Rate | >80% | % tickets auto-billed vs manual |
| Invoice Accuracy | >95% | % invoices requiring no adjustment |
| Processing Time | <5 min | Ticket close to invoice creation |
| Failed Jobs | <1% | Failed / Total jobs |
| Revenue Impact | +15-30% | Invoiced ticket revenue increase |

---

## 📞 Support

### Getting Help

1. Check logs first: `storage/logs/ticket-billing.log`
2. Review this deployment guide
3. Check UI guide: `TICKET_BILLING_UI_GUIDE.md`
4. Check implementation docs: `TICKET_BILLING_IMPLEMENTATION_COMPLETE.md`

### Reporting Issues

Include in bug reports:
- Laravel version
- PHP version
- Error messages (full stack trace)
- Steps to reproduce
- Expected vs actual behavior
- Relevant ticket/invoice IDs

---

## ✅ Final Checklist

Before going live:

- [ ] All files deployed
- [ ] Environment variables set
- [ ] Caches cleared
- [ ] Queue worker running
- [ ] Dry run tested successfully
- [ ] Single ticket billing tested
- [ ] Bulk processing tested (5-10 tickets)
- [ ] Settings UI accessible
- [ ] Staff trained
- [ ] Documentation reviewed
- [ ] Monitoring set up
- [ ] Backup plan ready

---

## 🎉 Deployment Complete!

Once all checklist items are complete, your automatic ticket billing system is **LIVE**!

**Next Steps:**
1. Monitor closely for first week
2. Process pending tickets daily
3. Review generated invoices
4. Gradually increase automation
5. Celebrate improved efficiency! 🎊

---

**Deployed:** [Date]  
**Deployed By:** [Name]  
**Version:** 1.0  
**Status:** ✅ Production Ready
