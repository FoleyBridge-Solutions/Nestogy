# Service Management System - Implementation Guide

## Overview

This document describes the new service management system implemented for Nestogy, which provides comprehensive lifecycle management for client services including provisioning, billing, renewal, and monitoring.

## Architecture

### Core Services

The system consists of 5 main service classes that work together to manage the complete service lifecycle:

```
┌─────────────────────────────────────────────────────────────────┐
│                 ClientServiceManagementService                  │
│                    (Core Orchestrator)                          │
│  - provisionService()      - suspendService()                   │
│  - activateService()       - cancelService()                    │
│  - renewService()          - calculateMRR()                     │
│  - getServiceHealth()      - transferToClient()                 │
└─────┬──────────────┬──────────────┬──────────────┬─────────────┘
      │              │              │              │
      ▼              ▼              ▼              ▼
┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│Provision │  │ Billing  │  │ Renewal  │  │Monitoring│
│ Service  │  │ Service  │  │ Service  │  │ Service  │
└──────────┘  └──────────┘  └──────────┘  └──────────┘
```

### 1. ClientServiceManagementService

**Purpose:** Core orchestrator for all service lifecycle operations

**Key Methods:**
- `provisionService(Client, Product, array)` - Create new service from template
- `activateService(ClientService)` - Activate pending service
- `suspendService(ClientService, string)` - Suspend service with reason
- `cancelService(ClientService, Carbon)` - Cancel with fee calculation
- `renewService(ClientService, int)` - Renew for specified months
- `getDueForRenewal(int)` - Get services needing renewal
- `calculateMRR(Client)` - Calculate Monthly Recurring Revenue
- `getServiceHealth(ClientService)` - Get health score and metrics
- `resumeService(ClientService)` - Resume suspended service
- `transferToClient(ClientService, Client)` - Transfer to different client

**Usage Example:**
```php
use App\Domains\Client\Services\ClientServiceManagementService;

$serviceManager = app(ClientServiceManagementService::class);

// Provision a new service
$service = $serviceManager->provisionService(
    $client,
    $productTemplate,
    [
        'name' => 'Managed IT Services',
        'monthly_cost' => 2500,
        'billing_cycle' => 'monthly',
        'auto_renewal' => true,
        'assigned_technician' => $tech->id,
    ]
);

// Activate the service
$serviceManager->activateService($service);

// Calculate company MRR
$mrr = $serviceManager->calculateMRR();
```

### 2. ServiceProvisioningService

**Purpose:** Handles multi-step provisioning workflows

**Key Methods:**
- `startProvisioning(ClientService)` - Initialize workflow
- `assignTechnicians(ClientService, User, ?User)` - Assign primary & backup
- `setupMonitoring(ClientService)` - Enable monitoring
- `configureServiceParameters(ClientService, array)` - Set SLA terms
- `completeProvisioning(ClientService)` - Finalize setup
- `failProvisioning(ClientService, string)` - Mark as failed
- `getProvisioningStatus(ClientService)` - Get workflow progress

**Usage Example:**
```php
$provisioning = app(ServiceProvisioningService::class);

// Assign technicians
$provisioning->assignTechnicians($service, $primaryTech, $backupTech);

// Configure SLA parameters
$provisioning->configureServiceParameters($service, [
    'sla_terms' => '24/7 support with 1-hour response time',
    'response_time' => '1 hour',
    'resolution_time' => '4 hours',
]);

// Enable monitoring
$provisioning->setupMonitoring($service);

// Complete provisioning
$provisioning->completeProvisioning($service);
```

### 3. ServiceBillingService

**Purpose:** Financial integration and billing automation

**Key Methods:**
- `createRecurringBilling(ClientService)` - Auto-create recurring billing
- `generateServiceInvoice(ClientService, Carbon, Carbon)` - Generate invoice
- `calculateProration(ClientService, Carbon, Carbon)` - Prorate partial periods
- `applySetupFees(ClientService, Invoice)` - Add setup fees
- `calculateCancellationFee(ClientService, Carbon)` - Calculate penalty
- `suspendBilling(ClientService)` - Pause billing
- `resumeBilling(ClientService)` - Resume billing
- `calculateTotalContractValue(ClientService)` - Get total value
- `getRevenueProjection(ClientService, int)` - Project future revenue

**Usage Example:**
```php
$billing = app(ServiceBillingService::class);

// Create recurring billing when service is activated
$recurring = $billing->createRecurringBilling($service);

// Generate invoice for a period
$invoice = $billing->generateServiceInvoice(
    $service,
    Carbon::parse('2025-10-01'),
    Carbon::parse('2025-10-31')
);

// Calculate cancellation fee
$fee = $billing->calculateCancellationFee($service, now());

// Get revenue projection
$projection = $billing->getRevenueProjection($service, 12);
```

### 4. ServiceRenewalService

**Purpose:** Automated renewal management

**Key Methods:**
- `processAutoRenewals()` - Batch process auto-renewals
- `checkRenewalEligibility(ClientService)` - Validate renewal eligibility
- `calculateRenewalPrice(ClientService)` - Get renewal pricing
- `sendRenewalNotification(ClientService, int)` - Send reminders
- `createRenewalQuote(ClientService)` - Generate renewal quote
- `approveRenewal(ClientService, ?float)` - Process renewal
- `denyRenewal(ClientService, string)` - Reject renewal
- `getServicesInGracePeriod()` - Get expired services in grace
- `extendGracePeriod(ClientService, int)` - Extend expiration
- `sendRenewalReminders()` - Batch send reminders

**Usage Example:**
```php
$renewal = app(ServiceRenewalService::class);

// Process all auto-renewals (scheduled job)
$results = $renewal->processAutoRenewals();

// Create renewal quote for manual approval
$quote = $renewal->createRenewalQuote($service);

// Approve renewal with new pricing
$renewed = $renewal->approveRenewal($service, 2750.00);

// Send renewal reminders (scheduled job)
$sent = $renewal->sendRenewalReminders();
```

### 5. ServiceMonitoringService

**Purpose:** Health tracking and SLA compliance

**Key Methods:**
- `checkSLACompliance(ClientService)` - Check SLA metrics
- `getUptimeMetrics(ClientService, int)` - Get uptime data
- `getPerformanceMetrics(ClientService)` - Get performance data
- `calculateHealthScore(ClientService)` - Calculate 0-100 score
- `isServiceHealthy(ClientService)` - Boolean health check
- `getServiceAlerts(ClientService)` - Get active alerts
- `recordIncident(ClientService, array)` - Log incident
- `resolveIncident(int)` - Close incident
- `generateSLAReport(ClientService, Carbon, Carbon)` - Generate report
- `runHealthChecks()` - Batch check all services

**Usage Example:**
```php
$monitoring = app(ServiceMonitoringService::class);

// Calculate health score (scheduled job)
$score = $monitoring->calculateHealthScore($service);

// Check SLA compliance
$compliance = $monitoring->checkSLACompliance($service);

// Record an incident
$monitoring->recordIncident($service, [
    'type' => 'outage',
    'is_sla_breach' => true,
    'description' => 'Server downtime for 2 hours',
]);

// Get service alerts
$alerts = $monitoring->getServiceAlerts($service);

// Generate SLA report
$report = $monitoring->generateSLAReport(
    $service,
    Carbon::parse('2025-10-01'),
    Carbon::parse('2025-10-31')
);
```

## Database Schema Changes

### New Fields in `client_services` Table

```sql
-- Contract & Product relationships
contract_id                 BIGINT NULLABLE FK(contracts)
product_id                  BIGINT NULLABLE FK(products)

-- Provisioning tracking
provisioning_status         VARCHAR NULLABLE (pending, in_progress, completed, failed)
provisioned_at              TIMESTAMP NULLABLE
activated_at                TIMESTAMP NULLABLE
suspended_at                TIMESTAMP NULLABLE
cancelled_at                TIMESTAMP NULLABLE

-- Cancellation management
cancellation_reason         TEXT NULLABLE
cancellation_fee            DECIMAL(10,2) DEFAULT 0

-- Renewal tracking
renewal_count               INT DEFAULT 0
last_renewed_at             TIMESTAMP NULLABLE

-- Health & SLA tracking
health_score                INT NULLABLE (0-100)
last_health_check_at        TIMESTAMP NULLABLE
sla_breaches_count          INT DEFAULT 0
last_sla_breach_at          TIMESTAMP NULLABLE

-- Billing integration
recurring_billing_id        BIGINT NULLABLE FK(recurrings)
actual_monthly_revenue      DECIMAL(10,2) DEFAULT 0
```

### Indexes Added

```sql
INDEX idx_provisioning_status (provisioning_status)
INDEX idx_activated_at (activated_at)
INDEX idx_renewal_date (renewal_date)
INDEX idx_health_score (health_score)
```

## Service Lifecycle Flow

```
┌─────────────┐
│   PENDING   │ ← provisionService()
│  (Created)  │
└──────┬──────┘
       │ startProvisioning()
       ▼
┌─────────────┐
│PROVISIONING │
│(In Progress)│
└──────┬──────┘
       │ completeProvisioning()
       ▼
┌─────────────┐
│PROVISIONED  │
│  (Ready)    │
└──────┬──────┘
       │ activateService()
       ▼
┌─────────────┐     suspendService()    ┌─────────────┐
│   ACTIVE    │◄───────────────────────►│ SUSPENDED   │
│ (Running)   │     resumeService()     │  (Paused)   │
└──────┬──────┘                         └─────────────┘
       │ cancelService()
       ▼
┌─────────────┐
│  CANCELLED  │
│   (Ended)   │
└─────────────┘
```

## Integration Points

### 1. Financial Domain Integration

**Automatic Recurring Billing:**
When a service is activated, recurring billing is automatically created:

```php
// In ClientServiceManagementService::activateService()
if ($this->billing && $service->monthly_cost > 0) {
    $this->billing->createRecurringBilling($service);
}
```

This creates a `Recurring` record that will generate invoices automatically.

### 2. Contract Domain Integration

Services can now be linked to contracts via `contract_id`:

```php
// Create service from contract
$service = $serviceManager->provisionService($client, $product, [
    'contract_id' => $contract->id,
    'name' => 'Services from Contract #' . $contract->id,
]);
```

### 3. Ticket Domain Integration (Future)

SLA breaches will automatically create tickets:

```php
// In ServiceMonitoringService::recordIncident()
if ($incidentData['is_sla_breach']) {
    // TODO: Dispatch event to create ticket
    ServiceSLABreached::dispatch($service, $incidentData);
}
```

## Scheduled Jobs

### Recommended Cron Schedule

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Process auto-renewals daily at 2 AM
    $schedule->call(function () {
        app(ServiceRenewalService::class)->processAutoRenewals();
    })->dailyAt('02:00');

    // Send renewal reminders daily at 9 AM
    $schedule->call(function () {
        app(ServiceRenewalService::class)->sendRenewalReminders();
    })->dailyAt('09:00');

    // Run health checks on all services every 6 hours
    $schedule->call(function () {
        app(ServiceMonitoringService::class)->runHealthChecks();
    })->everySixHours();

    // Generate and send SLA reports monthly
    $schedule->call(function () {
        // TODO: Generate monthly SLA reports
    })->monthlyOn(1, '08:00');
}
```

## Usage Scenarios

### Scenario 1: Onboard New Client with Service

```php
$serviceManager = app(ClientServiceManagementService::class);
$provisioning = app(ServiceProvisioningService::class);

// 1. Provision service
$service = $serviceManager->provisionService($client, $managedServicesProduct, [
    'name' => 'Managed IT Services - Premium',
    'monthly_cost' => 3500,
    'setup_cost' => 1000,
    'billing_cycle' => 'monthly',
    'auto_renewal' => true,
    'start_date' => now(),
    'end_date' => now()->addYear(),
]);

// 2. Assign technicians
$provisioning->assignTechnicians($service, $seniorTech, $juniorTech);

// 3. Configure service parameters
$provisioning->configureServiceParameters($service, [
    'sla_terms' => '24/7 support, 1-hour response, 4-hour resolution',
    'response_time' => '1 hour',
    'resolution_time' => '4 hours',
    'service_hours' => ['Monday-Sunday' => '00:00-23:59'],
]);

// 4. Enable monitoring
$provisioning->setupMonitoring($service);

// 5. Complete provisioning
$provisioning->completeProvisioning($service);

// 6. Activate service (creates recurring billing)
$serviceManager->activateService($service);
```

### Scenario 2: Handle Service Suspension

```php
$serviceManager = app(ClientServiceManagementService::class);

// Suspend for non-payment
$serviceManager->suspendService($service, 'Non-payment - invoice overdue by 45 days');

// Later, when payment received...
$serviceManager->resumeService($service);
```

### Scenario 3: Process Service Cancellation

```php
$serviceManager = app(ClientServiceManagementService::class);

// Cancel service
$cancellationFee = $serviceManager->cancelService($service, now()->addDays(30));

// Fee is automatically calculated based on remaining contract
echo "Cancellation fee: $" . $cancellationFee;
```

### Scenario 4: Monthly MRR Reporting

```php
$serviceManager = app(ClientServiceManagementService::class);

// Company-wide MRR
$totalMRR = $serviceManager->calculateMRR();

// Per-client MRR
foreach ($clients as $client) {
    $clientMRR = $serviceManager->calculateMRR($client);
    echo "{$client->name}: $" . number_format($clientMRR, 2) . "/month\n";
}
```

## Testing

### Unit Tests

```php
// tests/Unit/Services/ClientServiceManagementServiceTest.php

public function test_provision_service_creates_service_record()
{
    $client = Client::factory()->create();
    $product = Product::factory()->create(['type' => 'service']);
    
    $service = $this->serviceManager->provisionService($client, $product, [
        'name' => 'Test Service',
        'monthly_cost' => 500,
    ]);
    
    $this->assertInstanceOf(ClientService::class, $service);
    $this->assertEquals('pending', $service->status);
    $this->assertEquals($client->id, $service->client_id);
}

public function test_activate_service_creates_recurring_billing()
{
    $service = ClientService::factory()->create([
        'status' => 'pending',
        'monthly_cost' => 1000,
    ]);
    
    $this->serviceManager->activateService($service);
    
    $service->refresh();
    $this->assertEquals('active', $service->status);
    $this->assertNotNull($service->activated_at);
    $this->assertNotNull($service->recurring_billing_id);
}
```

## Migration Guide

### Step 1: Run Migration

```bash
php artisan migrate
```

### Step 2: Update Existing Services

```php
// Script to backfill existing services
$services = ClientService::all();

foreach ($services as $service) {
    // Set activated_at for active services
    if ($service->status === 'active' && !$service->activated_at) {
        $service->update(['activated_at' => $service->start_date ?? $service->created_at]);
    }
    
    // Calculate initial health score
    app(ServiceMonitoringService::class)->calculateHealthScore($service);
}
```

### Step 3: Setup Scheduled Jobs

Add the recommended cron jobs to `app/Console/Kernel.php` (see above).

### Step 4: Update Controllers

Update controllers to use the new services instead of direct model manipulation.

## Benefits

### 1. Automated Billing
- Services automatically create recurring billing when activated
- No manual invoice creation needed
- Proration handled automatically for partial periods

### 2. Proactive Management
- Health scores identify problems before clients complain
- Renewal reminders prevent lapses
- SLA breach tracking ensures accountability

### 3. Revenue Protection
- Auto-renewals capture renewals automatically
- Cancellation fees calculated correctly
- Grace periods prevent immediate service termination

### 4. Better Visibility
- MRR calculations show recurring revenue
- Health dashboards show service status
- SLA reports demonstrate value delivery

### 5. Cleaner Code
- Business logic extracted from controllers
- Reusable services across application
- Testable components

## Future Enhancements

### Phase 2 (Future Implementation)

1. **Event System**
   - ServiceActivated event
   - ServiceSuspended event
   - ServiceCancelled event
   - ServiceRenewed event
   - ServiceSLABreached event

2. **Notification Integration**
   - Email notifications for renewals
   - SMS alerts for SLA breaches
   - Client portal notifications

3. **Reporting Dashboard**
   - Service health overview
   - MRR trends
   - Renewal pipeline
   - Churn analysis

4. **Asset Integration**
   - Link services to assets
   - Track which assets support which services
   - Asset lifecycle tied to service lifecycle

5. **Advanced Monitoring**
   - Integration with RMM tools
   - Real-time uptime tracking
   - Performance metrics collection
   - Automated incident creation

## Support

For questions or issues with the service management system, contact the development team or refer to the inline documentation in the service classes.

---

**Last Updated:** October 29, 2025
**Version:** 1.0.0
