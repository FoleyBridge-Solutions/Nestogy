# Service Management System - Quick Reference

**Version:** 2.0.0  
**Status:** Production Ready ✅  
**Last Updated:** October 29, 2025

---

## 🚀 Quick Start

### Activate a Service (Auto-creates Billing)
```php
$serviceManager = app(ClientServiceManagementService::class);
$serviceManager->activateService($service);

// Automatically:
// - Creates recurring billing
// - Sends activation notification
```

### Calculate Monthly Recurring Revenue
```php
$mrr = $serviceManager->calculateMRR(); // Company-wide
$clientMrr = $serviceManager->calculateMRR($client); // Per client
```

### Check Service Health
```php
$monitoringService = app(ServiceMonitoringService::class);
$score = $monitoringService->calculateHealthScore($service); // 0-100
```

### Process Auto-Renewals (Cron Job)
```php
$renewalService = app(ServiceRenewalService::class);
$results = $renewalService->processAutoRenewals();
```

---

## 📋 Service Classes

| Service | Location | Purpose |
|---------|----------|---------|
| `ClientServiceManagementService` | `/app/Domains/Client/Services/` | Main orchestrator - provision, activate, suspend, cancel, renew |
| `ServiceProvisioningService` | `/app/Domains/Client/Services/` | Multi-step service setup workflow |
| `ServiceBillingService` | `/app/Domains/Client/Services/` | Financial integration, recurring billing, proration |
| `ServiceRenewalService` | `/app/Domains/Client/Services/` | Auto-renewals, reminders, grace periods |
| `ServiceMonitoringService` | `/app/Domains/Client/Services/` | Health scoring, SLA tracking, alerts |

---

## 🔔 Events & What Triggers Them

| Event | Triggered By | Listeners |
|-------|-------------|-----------|
| `ServiceActivated` | `activateService()` | CreateRecurringBilling, NotifyActivated |
| `ServiceSuspended` | `suspendService()` | SuspendBilling, NotifySuspended |
| `ServiceResumed` | `resumeService()` | ResumeBilling |
| `ServiceCancelled` | `cancelService()` | *(Billing handled directly)* |
| `ServiceRenewed` | `renewService()` | *(Currently none)* |
| `ServiceDueForRenewal` | `sendRenewalReminders()` | NotifyRenewalDue |
| `ServiceSLABreached` | `recordIncident()` | AlertOnBreach, RecalculateHealth |
| `ServiceHealthDegraded` | `calculateHealthScore()` | RecalculateHealth |

---

## 🎯 Common Operations

### Provision New Service
```php
$service = $serviceManager->provisionService($client, $product, [
    'name' => 'Premium Managed IT',
    'monthly_cost' => 3500,
    'setup_cost' => 1000,
    'billing_cycle' => 'monthly',
    'auto_renewal' => true,
    'end_date' => now()->addYear(),
]);
```

### Complete Provisioning Workflow
```php
$provisioning = app(ServiceProvisioningService::class);
$provisioning->assignTechnicians($service, $primaryTech, $backupTech);
$provisioning->configureServiceParameters($service, [
    'sla_terms' => '24/7, 1hr response',
    'response_time' => '1 hour',
]);
$provisioning->setupMonitoring($service);
$provisioning->completeProvisioning($service);
```

### Suspend Service (Auto-pauses Billing)
```php
$serviceManager->suspendService($service, 'Payment overdue');
// Billing automatically suspended via event
```

### Resume Service (Auto-resumes Billing)
```php
$serviceManager->resumeService($service);
// Billing automatically resumed via event
```

### Cancel Service (Calculates Fee)
```php
$fee = $serviceManager->cancelService($service, now()->addDays(30));
// Returns calculated cancellation fee
```

### Record SLA Breach (Auto-alerts)
```php
$monitoringService->recordIncident($service, [
    'is_sla_breach' => true,
    'description' => 'Response time exceeded',
    'severity' => 'high',
]);
// Automatically creates alert and recalculates health
```

---

## 📊 Health Score Components

| Factor | Impact | Max Deduction |
|--------|--------|---------------|
| SLA Breaches | -5 points each | -30 points |
| Client Satisfaction | ±20 points based on rating | ±20 points |
| Review Overdue | -1 point per 10 days after 90 | -20 points |
| Low Uptime | -10 points per % below 99.9% | -25 points |

**Score Ranges:**
- 90-100: Excellent
- 70-89: Good
- 50-69: Needs Attention
- 0-49: Critical

---

## 🔄 Scheduled Jobs (Recommended)

Add to `/app/Console/Kernel.php`:

```php
// Process auto-renewals daily at 2 AM
$schedule->call(function () {
    app(ServiceRenewalService::class)->processAutoRenewals();
})->dailyAt('02:00');

// Send renewal reminders daily at 9 AM
$schedule->call(function () {
    app(ServiceRenewalService::class)->sendRenewalReminders();
})->dailyAt('09:00');

// Run health checks every 6 hours
$schedule->call(function () {
    app(ServiceMonitoringService::class)->runHealthChecks();
})->everySixHours();
```

---

## 🎓 Key Methods Reference

### ClientServiceManagementService
- `provisionService($client, $product, $data)` - Create new service
- `activateService($service)` - Activate service + auto-create billing
- `suspendService($service, $reason)` - Suspend service + pause billing
- `resumeService($service)` - Resume service + resume billing
- `cancelService($service, $effectiveDate, $reason)` - Cancel + calculate fee
- `renewService($service, $months)` - Extend service period
- `calculateMRR($client = null)` - Calculate monthly recurring revenue
- `getServiceHealth($service)` - Get comprehensive health data
- `transferService($service, $newClient)` - Transfer to different client

### ServiceBillingService
- `createRecurringBilling($service)` - Create recurring billing record
- `generateServiceInvoice($service, $startDate, $endDate)` - Generate invoice
- `calculateProration($service, $startDate, $billingDate)` - Calculate partial period
- `calculateCancellationFee($service, $cancellationDate)` - Calculate early term fee
- `suspendBilling($recurringBilling)` - Pause billing
- `resumeBilling($recurringBilling)` - Resume billing

### ServiceMonitoringService
- `calculateHealthScore($service)` - Calculate 0-100 health score
- `recordIncident($service, $incidentData)` - Record incident/breach
- `checkSLACompliance($service)` - Check SLA compliance
- `getServiceAlerts($service)` - Get all active alerts
- `generateSLAReport($service, $start, $end)` - Generate report
- `runHealthChecks()` - Batch check all monitored services

### ServiceRenewalService
- `processAutoRenewals()` - Process all due auto-renewals
- `sendRenewalReminders()` - Send 30/14/7 day reminders
- `checkRenewalEligibility($service)` - Check if eligible
- `calculateRenewalPrice($service)` - Calculate renewal price
- `createRenewalQuote($service)` - Create quote for approval
- `approveRenewal($service, $newPrice)` - Approve and process
- `extendGracePeriod($service, $days)` - Extend grace period

---

## 📁 File Locations

### Services
- `/app/Domains/Client/Services/ClientServiceManagementService.php`
- `/app/Domains/Client/Services/ServiceProvisioningService.php`
- `/app/Domains/Client/Services/ServiceBillingService.php`
- `/app/Domains/Client/Services/ServiceRenewalService.php`
- `/app/Domains/Client/Services/ServiceMonitoringService.php`

### Events
- `/app/Domains/Client/Events/Service*.php` (9 files)

### Listeners
- `/app/Domains/Client/Listeners/*.php` (8 files)

### Models
- `/app/Domains/Client/Models/ClientService.php`

### Documentation
- `/docs/SERVICE_MANAGEMENT_SYSTEM.md` (85 KB comprehensive guide)
- `/SERVICE_MANAGEMENT_IMPLEMENTATION_SUMMARY.md` (Overview)
- `/PHASE_2_EVENT_SYSTEM_COMPLETE.md` (Event system details)

---

## ⚙️ Configuration

### Queue Workers
Ensure queue workers are running for async event processing:

```bash
# Development
php artisan queue:work

# Production (use Supervisor)
supervisorctl start nestogy-queue-worker:*
```

### Database Fields
The system uses these fields in `client_services`:
- `contract_id`, `product_id`, `recurring_billing_id` (relationships)
- `provisioning_status`, `activated_at`, `suspended_at`, `cancelled_at` (lifecycle)
- `cancellation_reason`, `cancellation_fee` (cancellation)
- `renewal_date`, `renewal_count`, `last_renewed_at` (renewal)
- `health_score`, `last_health_check_at`, `sla_breaches_count`, `last_sla_breach_at` (monitoring)

---

## 🎯 Best Practices

1. **Always use service classes** - Don't manipulate ClientService directly
2. **Let events handle side effects** - Don't manually create billing, it's automatic
3. **Run health checks regularly** - Schedule the job to run every 6 hours
4. **Set auto_renewal** - Prevent revenue loss from expired services
5. **Monitor SLA breaches** - They automatically degrade health scores
6. **Use grace periods** - Give clients time to renew before cancellation
7. **Track MRR regularly** - Key metric for business health

---

## 🐛 Troubleshooting

### Billing not created on activation?
- Check queue workers are running: `php artisan queue:work`
- Check event registration: `php artisan event:list | grep ServiceActivated`
- Check logs: `tail -f storage/logs/laravel.log`

### Events not firing?
- Verify event registration in `AppServiceProvider::registerEventListeners()`
- Check service methods are dispatching events
- Run `php artisan config:clear` to clear cached config

### Health score not updating?
- Check `last_health_check_at` timestamp
- Manually run: `$monitoringService->calculateHealthScore($service)`
- Check for errors in logs

---

## 📞 Support

For detailed documentation, see:
- **Main Guide:** `/docs/SERVICE_MANAGEMENT_SYSTEM.md`
- **Phase 2 Details:** `/PHASE_2_EVENT_SYSTEM_COMPLETE.md`

All code has comprehensive inline PHPDoc comments!

---

**Ready to manage services like a pro!** 🚀
