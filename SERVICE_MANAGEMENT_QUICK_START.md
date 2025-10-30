# Service Management System - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Basic Service Provisioning

```php
use App\Domains\Client\Services\ClientServiceManagementService;

$serviceManager = app(ClientServiceManagementService::class);

// Create a new service for a client
$service = $serviceManager->provisionService(
    $client,                    // Your Client model
    $productTemplate,           // Product model (service template)
    [
        'name' => 'Managed IT Services',
        'monthly_cost' => 2500,
        'setup_cost' => 500,
        'billing_cycle' => 'monthly',
        'auto_renewal' => true,
        'start_date' => now(),
        'end_date' => now()->addYear(),
    ]
);

// Activate it (this creates recurring billing automatically!)
$serviceManager->activateService($service);
```

That's it! The service is now active and will generate invoices automatically.

### Step 2: Calculate Your MRR

```php
// Total company MRR
$mrr = $serviceManager->calculateMRR();
echo "Monthly Recurring Revenue: $" . number_format($mrr, 2);

// Per-client MRR
$clientMRR = $serviceManager->calculateMRR($client);
```

### Step 3: Check Service Health

```php
use App\Domains\Client\Services\ServiceMonitoringService;

$monitoring = app(ServiceMonitoringService::class);

// Get health score (0-100)
$health = $monitoring->calculateHealthScore($service);
echo "Health Score: {$health}/100";

// Get detailed health report
$report = $serviceManager->getServiceHealth($service);
print_r($report);
/*
Array (
    [score] => 85
    [status] => 'healthy'
    [factors] => [
        ['name' => 'SLA Breaches', 'impact' => 0, 'value' => 0],
        ['name' => 'Client Satisfaction', 'impact' => 10, 'value' => 9],
    ]
)
*/
```

### Step 4: Handle Service Operations

```php
// Suspend a service (pauses billing)
$serviceManager->suspendService($service, 'Non-payment - overdue invoice');

// Resume it later
$serviceManager->resumeService($service);

// Cancel a service (calculates cancellation fee)
$fee = $serviceManager->cancelService($service, now()->addDays(30));
echo "Cancellation fee: $" . number_format($fee, 2);

// Renew a service
$renewed = $serviceManager->renewService($service, 12); // 12 months
```

### Step 5: Setup Automated Jobs

Add to `/app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Auto-process renewals daily
    $schedule->call(function () {
        $renewal = app(\App\Domains\Client\Services\ServiceRenewalService::class);
        $results = $renewal->processAutoRenewals();
        \Log::info('Auto-renewals processed', $results);
    })->dailyAt('02:00');
    
    // Send renewal reminders
    $schedule->call(function () {
        $renewal = app(\App\Domains\Client\Services\ServiceRenewalService::class);
        $sent = $renewal->sendRenewalReminders();
        \Log::info('Renewal reminders sent', $sent);
    })->dailyAt('09:00');
    
    // Health checks
    $schedule->call(function () {
        $monitoring = app(\App\Domains\Client\Services\ServiceMonitoringService::class);
        $results = $monitoring->runHealthChecks();
        \Log::info('Health checks complete', $results);
    })->everySixHours();
}
```

## 📊 Common Queries

### Get Services Due for Renewal
```php
$dueForRenewal = $serviceManager->getDueForRenewal(30); // Next 30 days
foreach ($dueForRenewal as $service) {
    echo "{$service->client->name} - {$service->name} - Due: {$service->renewal_date}\n";
}
```

### Get Services Ending Soon
```php
$endingSoon = $serviceManager->getEndingSoon(14); // Next 14 days
```

### Get Unhealthy Services
```php
$monitoring = app(ServiceMonitoringService::class);
$allServices = ClientService::where('status', 'active')->get();

$unhealthy = $allServices->filter(function ($service) use ($monitoring) {
    return !$monitoring->isServiceHealthy($service);
});
```

### Generate SLA Report
```php
$report = $monitoring->generateSLAReport(
    $service,
    Carbon::parse('2025-10-01'),
    Carbon::parse('2025-10-31')
);
```

## 🎯 Real-World Scenarios

### Scenario: New Client Onboarding
```php
// 1. Create service
$service = $serviceManager->provisionService($client, $product, [...]);

// 2. Assign team
$provisioning = app(ServiceProvisioningService::class);
$provisioning->assignTechnicians($service, $primaryTech, $backupTech);

// 3. Configure SLA
$provisioning->configureServiceParameters($service, [
    'sla_terms' => '24/7 support, 1-hour response time',
    'response_time' => '1 hour',
    'resolution_time' => '4 hours',
]);

// 4. Enable monitoring
$provisioning->setupMonitoring($service);

// 5. Complete and activate
$provisioning->completeProvisioning($service);
$serviceManager->activateService($service);
```

### Scenario: Monthly Billing Run
```php
$billing = app(ServiceBillingService::class);
$activeServices = ClientService::where('status', 'active')->get();

foreach ($activeServices as $service) {
    $invoice = $billing->generateServiceInvoice(
        $service,
        now()->startOfMonth(),
        now()->endOfMonth()
    );
    
    // Apply setup fees if it's the first bill
    if (!$service->activated_at->lt(now()->subMonth())) {
        $billing->applySetupFees($service, $invoice);
    }
}
```

### Scenario: Handle Client Non-Payment
```php
// Suspend all client services
$clientServices = $client->services()->where('status', 'active')->get();

foreach ($clientServices as $service) {
    $serviceManager->suspendService(
        $service,
        'Account suspended - invoice #{$overdueInvoice->id} overdue by 30 days'
    );
}

// When payment received, resume
foreach ($clientServices as $service) {
    $serviceManager->resumeService($service);
}
```

## 🔍 Debugging & Monitoring

### Check Service Status
```php
$service = ClientService::find($id);

echo "Status: " . $service->status . "\n";
echo "Lifecycle Stage: " . $service->getLifecycleStage() . "\n";
echo "Is Active: " . ($service->isActive() ? 'Yes' : 'No') . "\n";
echo "Has Billing: " . ($service->hasRecurringBilling() ? 'Yes' : 'No') . "\n";
echo "Health Score: " . ($service->health_score ?? 'Not calculated') . "\n";
```

### View Service Alerts
```php
$alerts = $monitoring->getServiceAlerts($service);
foreach ($alerts as $alert) {
    echo "[{$alert['severity']}] {$alert['title']}: {$alert['message']}\n";
}
```

### Check SLA Compliance
```php
$compliance = $monitoring->checkSLACompliance($service);
echo "Compliant: " . ($compliance['is_compliant'] ? 'Yes' : 'No') . "\n";
print_r($compliance['metrics']);
```

## ⚠️ Important Notes

1. **Activate Services to Start Billing**: Services must be activated to create recurring billing
2. **Auto-Renewal Requires Cron**: Set up scheduled jobs for auto-renewals to work
3. **Health Scores Update on Check**: Run health checks regularly via cron
4. **Cancellation Fees Are Automatic**: Cancel fee is calculated based on remaining contract value

## 🆘 Troubleshooting

**Service not creating invoices?**
- Check if service is activated: `$service->isActivated()`
- Check if recurring billing exists: `$service->hasRecurringBilling()`
- Verify recurring billing is active: `$service->recurringBilling->status`

**Health score always null?**
- Run: `$monitoring->calculateHealthScore($service)`
- Or setup the health check cron job

**Auto-renewals not working?**
- Verify cron jobs are setup in Kernel.php
- Check: `php artisan schedule:list`
- Run manually: `php artisan schedule:run`

## 📞 Need Help?

- Full docs: `/docs/SERVICE_MANAGEMENT_SYSTEM.md`
- Implementation summary: `/SERVICE_MANAGEMENT_IMPLEMENTATION_SUMMARY.md`
- Code: `/app/Domains/Client/Services/`

---

**You're ready to go! Start managing services like a pro.** 🎉
