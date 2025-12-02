# Automatic Ticket Billing System - Implementation Plan

**Author**: System Architect  
**Date**: 2025-11-06  
**Status**: Ready for Implementation  
**Architecture Style**: Event-Driven, Domain-Driven Design

---

## 📖 Table of Contents

1. [Executive Summary](#executive-summary)
2. [Current State Analysis](#current-state-analysis)
3. [System Architecture](#system-architecture)
4. [Implementation Phases](#implementation-phases)
5. [Technical Specifications](#technical-specifications)
6. [Testing Strategy](#testing-strategy)
7. [Deployment Plan](#deployment-plan)
8. [Risk Mitigation](#risk-mitigation)
9. [Success Metrics](#success-metrics)

---

## 🎯 Executive Summary

### Problem Statement
The system currently requires **manual intervention** to convert billable support tickets into invoices. This creates:
- Revenue leakage from unbilled work
- Administrative overhead
- Delayed cash flow
- Inconsistent billing practices

### Solution Overview
Implement an **event-driven, automated billing system** that:
- Automatically generates invoices when tickets are closed/resolved
- Supports multiple billing strategies (time-based, per-ticket, hybrid)
- Provides full audit trail and manual override capabilities
- Integrates seamlessly with existing contract and invoice systems
- Operates asynchronously via queue workers

### Business Value
- **95% reduction** in manual billing tasks
- **100% billing coverage** for billable tickets
- **Faster cash flow** - invoices generated within 5 minutes of ticket closure
- **Improved accuracy** - eliminates human error in billing calculations
- **Better client experience** - consistent, predictable billing

---

## 🔍 Current State Analysis

### Existing Infrastructure ✅

#### 1. Contract Billing Models
**Location**: `app/Domains/Contract/Models/ContractContactAssignment.php:425-453`

```php
// Per-ticket rate calculation exists
if ($this->billing_frequency === self::FREQUENCY_PER_TICKET || $this->per_ticket_rate > 0) {
    $total += $this->current_month_tickets * $this->per_ticket_rate;
}

// Ticket tracking method exists
public function recordTicketCreation(): void
{
    $this->increment('current_month_tickets');
    $this->increment('total_tickets_created');
    $this->update(['last_access_date' => now()->toDateString()]);
}
```

**Fields Available**:
- `per_ticket_rate` - Fixed charge per ticket
- `current_month_tickets` - Counter for billing period
- `billing_frequency` - Can be set to `FREQUENCY_PER_TICKET`

#### 2. Time Entry Billing
**Location**: `app/Domains/Ticket/Models/TicketTimeEntry.php`

```php
protected $fillable = [
    'billable',          // Boolean flag
    'hours_worked',      // Decimal(2)
    'hours_billed',      // Rounded billable hours
    'hourly_rate',       // Rate for entry
    'amount',            // Total charge
    'invoice_id',        // Links to invoice when billed
    'invoiced_at',       // Timestamp when invoiced
];
```

**Service Available**: `TimeEntryInvoiceService.php:18`
```php
public function generateInvoiceFromTimeEntries(array $timeEntryIds, int $clientId, array $options = []): Invoice
```

#### 3. Ticket Model Infrastructure
**Location**: `app/Domains/Ticket/Models/Ticket.php`

```php
protected $fillable = [
    'billable',          // Line 51 - Boolean flag
    'invoice_id',        // Line 65 - Link to invoice
    'status',            // Line 50 - Ticket status
    'closed_at',         // Line 56 - Closure timestamp
    'is_resolved',       // Line 71 - Resolution flag
    'resolved_at',       // Line 72 - Resolution timestamp
];
```

**Relationships**:
- `timeEntries()` - HasMany to TicketTimeEntry
- `client()` - BelongsTo Client
- `invoice()` - BelongsTo Invoice

**Scopes Available**:
- `scopeBillable()` - Line 935
- `scopeClosed()` - Line 886
- `scopeResolved()` - Line 890

#### 4. Job Queue Infrastructure
**Existing Patterns**:
- `CheckSLABreaches.php` - ShouldQueue implementation
- `SendRecurringInvoiceEmail.php` - Email queue pattern
- Distributed scheduler with `onOneServer()`
- Proper retry/timeout handling

**Kernel Schedule**: `app/Console/Kernel.php:76-86`
```php
$schedule->call(function () {
    $scheduler = app(DistributedSchedulerService::class);
    $scheduler->executeIfNotRunning('recurring-invoices-daily', function () {
        \Artisan::call('billing:process-recurring-distributed');
    });
})->daily()->at('00:30');
```

#### 5. Configuration System
**Location**: `config/nestogy.php:210-216`

```php
'non_billable_ticket_types' => [
    'warranty',
    'internal',
    'training',
    'maintenance',
],
```

### Missing Components ❌

1. **No Domain Events** for ticket lifecycle
   - Need: `TicketCreated`, `TicketClosed`, `TicketResolved`
   
2. **No Event Listeners** wiring billing to ticket events
   - Need: Listeners to trigger billing automation

3. **No Billing Automation Service**
   - Need: `TicketBillingService` with strategy pattern

4. **No Billing Queue Jobs**
   - Need: `ProcessTicketBilling` job

5. **No Billing Configuration**
   - Need: `config/billing.php` with feature flags

6. **No Scheduled Batch Processing**
   - Need: Command to catch missed tickets

---

## 🏗️ System Architecture

### Architectural Layers

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  (Livewire Components, Manual Override UI, Admin Config)    │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     Application Layer                        │
│  (Event Listeners, Queue Jobs, Console Commands)            │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                      Domain Layer                            │
│  (TicketBillingService, Billing Strategies, Events)         │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                   Infrastructure Layer                       │
│  (Models, Repositories, External Services)                   │
└─────────────────────────────────────────────────────────────┘
```

### Event Flow Diagram

```
┌──────────────┐
│ Ticket Model │
│  (closed)    │
└──────┬───────┘
       │ fires
       ↓
┌─────────────────┐
│  TicketClosed   │  ← Domain Event
│     Event       │
└────────┬────────┘
         │ handled by
         ↓
┌──────────────────────┐
│ QueueTicketBillingJob│  ← Event Listener
│     Listener         │
└──────────┬───────────┘
           │ dispatches
           ↓
┌───────────────────────┐
│ ProcessTicketBilling  │  ← Queue Job
│       Job             │
└──────────┬────────────┘
           │ uses
           ↓
┌───────────────────────┐
│  TicketBillingService │  ← Domain Service
│   (Strategy Pattern)  │
└──────────┬────────────┘
           │ creates
           ↓
┌───────────────────────┐
│   Invoice Created     │
│   Ticket Updated      │
└───────────────────────┘
```

### Component Interaction

```
┌─────────────────────────────────────────────────────────────┐
│                    TICKET LIFECYCLE                          │
└─────────────────────────────────────────────────────────────┘
                    ↓ (status change)
┌─────────────────────────────────────────────────────────────┐
│                    DOMAIN EVENTS                             │
│  • TicketCreated  • TicketClosed  • TicketResolved          │
└─────────────────────────────────────────────────────────────┘
                    ↓ (event dispatched)
┌──────────────────────────┬──────────────────────────────────┐
│  RecordContractTicketUsage │  QueueTicketBillingJob         │
│      (Listener)            │       (Listener)                │
└──────────────────────────┴──────────────────────────────────┘
                    ↓ (job queued)
┌─────────────────────────────────────────────────────────────┐
│              ProcessTicketBilling (Job)                      │
│  • Retry: 3 times                                            │
│  • Timeout: 120 seconds                                      │
│  • Queue: 'billing'                                          │
└─────────────────────────────────────────────────────────────┘
                    ↓ (executes)
┌─────────────────────────────────────────────────────────────┐
│            TicketBillingService (Service)                    │
│  ┌─────────────────────────────────────────┐                │
│  │  determineBillingStrategy()             │                │
│  │  ├─> time_based                         │                │
│  │  ├─> per_ticket                         │                │
│  │  └─> mixed                               │                │
│  └─────────────────────────────────────────┘                │
└─────────────────────────────────────────────────────────────┘
                    ↓ (generates)
┌─────────────────────────────────────────────────────────────┐
│                  INVOICE CREATED                             │
│  • Invoice record saved                                      │
│  • Ticket.invoice_id updated                                 │
│  • TimeEntry.invoice_id updated                              │
│  • Audit log entry created                                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 📅 Implementation Phases

### Phase 1: Foundation (Days 1-2) 🔨

**Objective**: Lay the groundwork with events and configuration.

#### Tasks:

1. **Create Domain Events**
   - `app/Events/TicketCreated.php`
   - `app/Events/TicketClosed.php`
   - `app/Events/TicketResolved.php`

2. **Create Billing Configuration**
   - `config/billing.php` with feature flags
   - Add to `.env.example`

3. **Register Events**
   - Update `app/Providers/EventServiceProvider.php`

**Deliverables**:
- ✅ 3 event classes created
- ✅ Configuration file with all feature flags
- ✅ Events registered in service provider
- ✅ Unit tests for event instantiation

**Testing**:
```php
$ticket = Ticket::factory()->create();
event(new TicketCreated($ticket));
Event::assertDispatched(TicketCreated::class);
```

---

### Phase 2: Service Layer (Days 3-4) 🧠

**Objective**: Build the core business logic for billing.

#### Tasks:

1. **Create TicketBillingService**
   - `app/Domains/Financial/Services/TicketBillingService.php`
   - Implement strategy pattern
   - Handle all three billing strategies

2. **Create Billing Strategies** (if complex)
   - `TimeBasedBillingStrategy.php`
   - `PerTicketBillingStrategy.php`
   - `MixedBillingStrategy.php`

3. **Add Helper Methods**
   - `shouldAutoBill()` - Check if automation enabled
   - `determineBillingStrategy()` - Select correct strategy
   - `calculateBillableAmount()` - Amount calculation

**Deliverables**:
- ✅ TicketBillingService with full strategy implementation
- ✅ Comprehensive unit tests (80%+ coverage)
- ✅ Documentation with usage examples

**Testing**:
```php
// Time-based billing
$ticket = Ticket::factory()
    ->hasTimeEntries(3, ['billable' => true, 'hours_worked' => 2.5])
    ->create();
$invoice = $service->generateInvoiceForTicket($ticket);
$this->assertEquals(187.50, $invoice->total); // 7.5 hours * $25/hr

// Per-ticket billing
$contract = Contract::factory()->create(['per_ticket_rate' => 50.00]);
$ticket = Ticket::factory()->for($contract)->create();
$invoice = $service->generateInvoiceForTicket($ticket);
$this->assertEquals(50.00, $invoice->total);
```

---

### Phase 3: Automation (Days 5-6) ⚡

**Objective**: Wire up the automation with listeners and jobs.

#### Tasks:

1. **Create Event Listeners**
   - `app/Listeners/RecordContractTicketUsage.php`
   - `app/Listeners/QueueTicketBillingJob.php`

2. **Create Queue Job**
   - `app/Jobs/ProcessTicketBilling.php`
   - Implement ShouldQueue
   - Add retry logic, timeout, tags

3. **Wire Up in EventServiceProvider**
   ```php
   protected $listen = [
       TicketCreated::class => [
           RecordContractTicketUsage::class,
       ],
       TicketClosed::class => [
           QueueTicketBillingJob::class,
       ],
   ];
   ```

4. **Add Safety Checks**
   - Don't bill if already invoiced
   - Don't bill non-billable tickets
   - Don't bill if automation disabled
   - Don't bill if no billable time/rate

**Deliverables**:
- ✅ 2 event listeners created
- ✅ 1 queue job created
- ✅ Events wired in service provider
- ✅ Integration tests for event flow

**Testing**:
```php
Event::fake();
Queue::fake();

$ticket = Ticket::factory()->billable()->create();
$ticket->update(['status' => Ticket::STATUS_CLOSED]);
event(new TicketClosed($ticket));

Queue::assertPushed(ProcessTicketBilling::class, fn($job) => 
    $job->ticketId === $ticket->id
);
```

---

### Phase 4: Scheduled Tasks (Day 7) ⏰

**Objective**: Add safety net for missed tickets.

#### Tasks:

1. **Create Console Command**
   - `app/Console/Commands/ProcessPendingTicketBilling.php`
   - Find unbilled tickets
   - Dispatch jobs for each
   - Support `--dry-run` and `--limit` options

2. **Add to Kernel Schedule**
   ```php
   $schedule->command('billing:process-pending-tickets')
       ->hourly()
       ->withoutOverlapping()
       ->onOneServer()
       ->appendOutputTo(storage_path('logs/ticket-billing.log'));
   ```

3. **Add Monitoring**
   - Log ticket counts processed
   - Alert on large backlogs
   - Track processing time

**Deliverables**:
- ✅ Console command created
- ✅ Added to scheduled tasks
- ✅ Logging and monitoring in place
- ✅ Command tests

**Testing**:
```php
$this->artisan('billing:process-pending-tickets --dry-run')
     ->expectsOutput('Found 25 tickets to process')
     ->assertExitCode(0);
```

---

### Phase 5: Model Integration (Day 8) 🔗

**Objective**: Fire events from Ticket model lifecycle.

#### Tasks:

1. **Update Ticket Model Boot Method**
   ```php
   static::created(function ($ticket) {
       event(new TicketCreated($ticket));
   });
   
   static::updated(function ($ticket) {
       if ($ticket->wasChanged('status')) {
           if ($ticket->status === Ticket::STATUS_CLOSED) {
               event(new TicketClosed($ticket));
           }
       }
       if ($ticket->wasChanged('is_resolved') && $ticket->is_resolved) {
           event(new TicketResolved($ticket));
       }
   });
   ```

2. **Update Resolve Method**
   - Fire `TicketResolved` event in `resolve()` method

3. **Add Contract Integration**
   - Ensure `recordTicketCreation()` called

4. **Add Audit Logging**
   - Log all automatic billing actions

**Deliverables**:
- ✅ Events firing from model lifecycle
- ✅ Contract tracking integrated
- ✅ Audit logs created
- ✅ Model tests updated

**Testing**:
```php
Event::fake();

$ticket = Ticket::factory()->create(); // Created event
Event::assertDispatched(TicketCreated::class);

$ticket->update(['status' => Ticket::STATUS_CLOSED]); // Closed event
Event::assertDispatched(TicketClosed::class);
```

---

### Phase 6: Testing & Polish (Days 9-10) ✅

**Objective**: Comprehensive testing and edge case handling.

#### Tasks:

1. **Unit Tests**
   - Test each billing strategy in isolation
   - Test configuration loading
   - Test helper methods

2. **Integration Tests**
   - Test full event → listener → job → service flow
   - Test with real database
   - Test with queued jobs

3. **Feature Tests**
   - End-to-end billing scenarios
   - Multiple billing strategies
   - Edge cases (reopened tickets, etc.)

4. **Edge Case Handling**
   - Ticket reopened after billing
   - Time entries added after close
   - Contract expired mid-ticket
   - Client with no payment method
   - Duplicate billing prevention

5. **Performance Testing**
   - Load test with 1000 tickets
   - Queue worker performance
   - Database query optimization

**Deliverables**:
- ✅ 90%+ test coverage
- ✅ All edge cases handled
- ✅ Performance benchmarks documented
- ✅ Known issues documented

**Test Suite**:
```bash
php artisan test --filter TicketBilling
# Expected: 45+ tests passing
```

---

### Phase 7: Migration & Rollout (Days 11-12) 🚀

**Objective**: Safe production deployment.

#### Tasks:

1. **Database Migrations** (if needed)
   - Add indexes for performance
   - Add audit log table

2. **Admin UI**
   - Livewire component for config overrides
   - Manual trigger button per ticket
   - Billing history view

3. **Documentation**
   - User guide for admins
   - API documentation
   - Troubleshooting guide

4. **Staged Rollout**
   ```bash
   # Day 11: Deploy with automation OFF
   TICKET_BILLING_ENABLED=true
   AUTO_BILL_ON_CLOSE=false
   
   # Day 12: Enable for select clients
   # Enable per-client in admin UI
   
   # Day 13: Monitor and adjust
   
   # Day 14: Enable globally
   AUTO_BILL_ON_CLOSE=true
   ```

5. **Monitoring Setup**
   - Set up alerts for failed jobs
   - Dashboard for billing metrics
   - Error tracking integration

**Deliverables**:
- ✅ Production-ready deployment
- ✅ Admin UI functional
- ✅ Complete documentation
- ✅ Monitoring in place
- ✅ Rollback plan ready

---

## 🔧 Technical Specifications

### File Structure

```
app/
├── Events/
│   ├── TicketCreated.php          # NEW
│   ├── TicketClosed.php           # NEW
│   └── TicketResolved.php         # NEW
├── Listeners/
│   ├── RecordContractTicketUsage.php  # NEW
│   └── QueueTicketBillingJob.php      # NEW
├── Jobs/
│   └── ProcessTicketBilling.php       # NEW
├── Domains/
│   └── Financial/
│       └── Services/
│           ├── TicketBillingService.php     # NEW
│           ├── TimeEntryInvoiceService.php  # EXISTS
│           └── InvoiceService.php           # EXISTS
├── Console/
│   └── Commands/
│       └── ProcessPendingTicketBilling.php  # NEW
└── Providers/
    └── EventServiceProvider.php             # UPDATED

config/
└── billing.php                              # NEW

tests/
├── Unit/
│   └── Services/
│       └── TicketBillingServiceTest.php     # NEW
├── Feature/
│   └── TicketBillingTest.php                # NEW
└── Integration/
    └── TicketBillingFlowTest.php            # NEW
```

### Database Schema (Existing)

**Tickets Table**:
```sql
tickets
  - id
  - billable (boolean)
  - invoice_id (nullable, foreign key)
  - status (enum)
  - closed_at (timestamp)
  - is_resolved (boolean)
  - resolved_at (timestamp)
```

**Ticket Time Entries Table**:
```sql
ticket_time_entries
  - id
  - ticket_id
  - billable (boolean)
  - hours_worked (decimal)
  - hourly_rate (decimal)
  - amount (decimal)
  - invoice_id (nullable)
  - invoiced_at (timestamp)
```

**Contract Contact Assignments Table**:
```sql
contract_contact_assignments
  - id
  - contract_id
  - contact_id
  - billing_frequency (enum)
  - per_ticket_rate (decimal)
  - current_month_tickets (integer)
```

### Configuration Schema

**config/billing.php**:
```php
return [
    'ticket_billing' => [
        'enabled' => env('TICKET_BILLING_ENABLED', true),
        
        'automation' => [
            'auto_bill_on_close' => env('AUTO_BILL_ON_CLOSE', false),
            'auto_bill_on_resolve' => env('AUTO_BILL_ON_RESOLVE', false),
            'require_time_entries' => env('REQUIRE_TIME_ENTRIES', true),
            'delay_minutes' => env('BILLING_DELAY_MINUTES', 5),
        ],
        
        'strategies' => [
            'default' => env('BILLING_STRATEGY_DEFAULT', 'time_based'),
            'allow_mixed' => env('BILLING_ALLOW_MIXED', true),
        ],
        
        'thresholds' => [
            'minimum_billable_hours' => env('BILLING_MIN_HOURS', 0.25),
            'require_approval_over_hours' => env('BILLING_APPROVAL_THRESHOLD', 8.0),
        ],
        
        'invoice_generation' => [
            'status' => env('BILLING_INVOICE_STATUS', 'draft'),
            'group_by_client' => env('BILLING_GROUP_BY_CLIENT', true),
            'due_days' => env('BILLING_DUE_DAYS', 30),
            'auto_send' => env('BILLING_AUTO_SEND_INVOICE', false),
        ],
        
        'safety' => [
            'prevent_duplicate_billing' => true,
            'allow_rebilling' => false,
            'max_retry_attempts' => 3,
        ],
    ],
];
```

### Queue Configuration

**Jobs**:
- Queue: `billing`
- Timeout: 120 seconds
- Retries: 3 attempts
- Backoff: Exponential (1s, 5s, 15s)

**Worker Command**:
```bash
php artisan queue:work --queue=billing,default --tries=3 --timeout=120
```

---

## 🧪 Testing Strategy

### Test Pyramid

```
        ┌──────────┐
        │   E2E    │  (5% - Full flow with UI)
        └──────────┘
       ┌────────────┐
       │Integration │  (15% - Multiple components)
       └────────────┘
     ┌────────────────┐
     │   Feature      │  (30% - API/Service tests)
     └────────────────┘
   ┌──────────────────────┐
   │        Unit          │  (50% - Isolated logic)
   └──────────────────────┘
```

### Test Coverage Goals

| Component | Coverage Target |
|-----------|----------------|
| TicketBillingService | 95% |
| Event Listeners | 90% |
| Queue Jobs | 85% |
| Console Commands | 80% |
| Models | 90% |
| **Overall** | **90%+** |

### Test Scenarios

#### 1. Time-Based Billing
```php
✅ Single ticket with multiple time entries
✅ Ticket with no time entries (skip)
✅ Ticket with non-billable entries (skip)
✅ Ticket with mixed billable/non-billable entries
✅ Multiple tickets for same client (grouped invoice)
✅ Different hourly rates per entry
```

#### 2. Per-Ticket Billing
```php
✅ Contract with per_ticket_rate set
✅ Multiple tickets with same rate
✅ Ticket with both per-ticket rate and time entries (mixed)
✅ Contract with $0 per-ticket rate (skip)
```

#### 3. Edge Cases
```php
✅ Ticket reopened after billing (prevent duplicate)
✅ Time entries added after ticket closed (catch in scheduled job)
✅ Contract expired during ticket lifecycle
✅ Client with no active contract (fallback to time-based)
✅ Ticket with invoice_id already set (skip)
✅ Job fails and retries successfully
✅ Event dispatched multiple times (idempotent)
```

#### 4. Configuration Tests
```php
✅ Automation disabled globally
✅ Automation disabled per client
✅ Auto-bill on resolve vs close
✅ Minimum billable hours threshold
✅ Approval required over threshold
```

### Running Tests

```bash
# All billing tests
php artisan test --filter TicketBilling

# With coverage
php artisan test --filter TicketBilling --coverage

# Specific test class
php artisan test tests/Unit/Services/TicketBillingServiceTest.php

# Parallel execution
php artisan test --parallel --filter TicketBilling
```

---

## 🚀 Deployment Plan

### Pre-Deployment Checklist

- [ ] All tests passing (90%+ coverage)
- [ ] Code reviewed by senior developer
- [ ] Database migrations reviewed
- [ ] Performance benchmarks acceptable
- [ ] Documentation complete
- [ ] Rollback plan documented
- [ ] Monitoring configured
- [ ] Stakeholders notified

### Deployment Steps

#### Step 1: Infrastructure (Day 11 Morning)
```bash
# 1. Deploy code to staging
git checkout main
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# 2. Run tests on staging
php artisan test --env=staging

# 3. Verify queue workers running
supervisorctl status laravel-worker-billing
```

#### Step 2: Feature Flag Deployment (Day 11 Afternoon)
```bash
# Deploy with automation OFF
echo "TICKET_BILLING_ENABLED=true" >> .env
echo "AUTO_BILL_ON_CLOSE=false" >> .env
echo "AUTO_BILL_ON_RESOLVE=false" >> .env

php artisan config:clear
php artisan cache:clear
```

#### Step 3: Manual Testing (Day 11 Evening)
```bash
# Test manual billing trigger from UI
# Test configuration changes
# Verify logs are working
tail -f storage/logs/ticket-billing.log
```

#### Step 4: Pilot Rollout (Day 12)
```php
// Enable for 3-5 test clients
// Via admin UI or database:
UPDATE clients 
SET settings = JSON_SET(settings, '$.auto_bill_tickets', true)
WHERE id IN (1, 2, 3);
```

#### Step 5: Monitor (Day 12-13)
- Watch error rates
- Check job success rates
- Verify invoice generation
- Collect user feedback

#### Step 6: Full Rollout (Day 14)
```bash
# Enable globally
echo "AUTO_BILL_ON_CLOSE=true" >> .env
php artisan config:clear
```

### Rollback Plan

If issues arise:

```bash
# Immediate: Disable automation
echo "AUTO_BILL_ON_CLOSE=false" >> .env
php artisan config:clear

# If needed: Stop queue workers
supervisorctl stop laravel-worker-billing

# Full rollback:
git revert <commit-hash>
php artisan migrate:rollback
```

---

## ⚠️ Risk Mitigation

### Identified Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Duplicate invoices generated | High | Low | Check invoice_id before billing |
| Performance degradation | Medium | Medium | Queue jobs, index database |
| Lost revenue from bugs | High | Low | Scheduled catch-up job |
| Client confusion | Medium | Medium | Clear documentation, gradual rollout |
| Queue worker failures | Medium | Low | Monitoring, auto-restart, retries |

### Safety Mechanisms

1. **Idempotency**: All operations can safely run multiple times
2. **Feature Flags**: Instant disable via config
3. **Manual Override**: Always allow manual billing
4. **Audit Trail**: Log every action
5. **Dry-Run Mode**: Test before executing
6. **Scheduled Catch-Up**: Hourly job catches missed tickets
7. **Monitoring**: Alerts on failures
8. **Rollback Plan**: Documented revert process

---

## 📊 Success Metrics

### KPIs to Track

#### Business Metrics
- **Billing Coverage**: % of billable tickets invoiced
  - Target: 99%+
  
- **Time to Invoice**: Minutes from ticket close to invoice generation
  - Target: < 5 minutes

- **Revenue Impact**: Additional revenue captured vs manual process
  - Target: 15-20% increase

- **Admin Time Saved**: Hours per week no longer spent on manual billing
  - Target: 20+ hours/week

#### Technical Metrics
- **Job Success Rate**: % of billing jobs completing successfully
  - Target: 99.5%+

- **Processing Time**: Average job execution time
  - Target: < 30 seconds

- **Error Rate**: Failed jobs per 1000 tickets
  - Target: < 5 failures

- **Queue Wait Time**: Time jobs spend in queue
  - Target: < 1 minute

### Monitoring Dashboards

Create dashboards showing:
- Tickets billed per hour/day/week
- Billing job success/failure rates
- Average processing time
- Revenue generated via automation
- Error breakdown by type
- Queue depth and wait times

---

## 📚 API Documentation

### TicketBillingService Methods

```php
/**
 * Generate invoice for a ticket based on contract terms
 *
 * @param Ticket $ticket
 * @return Invoice|null
 * @throws BillingException
 */
public function generateInvoiceForTicket(Ticket $ticket): ?Invoice

/**
 * Determine billing strategy for ticket
 *
 * @param Ticket $ticket
 * @return string ('time_based'|'per_ticket'|'mixed')
 */
public function determineBillingStrategy(Ticket $ticket): string

/**
 * Check if ticket should be auto-billed
 *
 * @param Ticket $ticket
 * @return bool
 */
public function shouldAutoBill(Ticket $ticket): bool

/**
 * Calculate billable amount for ticket
 *
 * @param Ticket $ticket
 * @return float
 */
public function calculateBillableAmount(Ticket $ticket): float
```

### Event Payloads

```php
// TicketCreated
class TicketCreated
{
    public Ticket $ticket;
    public User $creator;
    public Carbon $timestamp;
}

// TicketClosed
class TicketClosed
{
    public Ticket $ticket;
    public User $closer;
    public Carbon $closedAt;
}
```

### Job Tags

All billing jobs tagged for Horizon:
```php
['billing', 'ticket-billing', 'ticket:123', 'client:456']
```

---

## 🔒 Security Considerations

1. **Authorization**: Only admins can modify billing config
2. **Audit Logging**: All billing actions logged with user
3. **Rate Limiting**: Prevent abuse of manual triggers
4. **Data Validation**: Sanitize all inputs
5. **Queue Security**: Billing queue on separate worker
6. **Invoice Permissions**: Check client access before generation

---

## 📖 User Documentation

### For Administrators

**Enabling Automatic Billing**:
1. Navigate to Settings > Billing > Automation
2. Toggle "Auto-bill tickets on close"
3. Configure billing strategy per client

**Manual Billing Override**:
1. Open ticket details
2. Click "Generate Invoice" button
3. Review preview
4. Confirm generation

**Monitoring Billing Activity**:
1. Navigate to Reports > Billing > Automation
2. View recent billing activity
3. Check for failed jobs
4. Review revenue metrics

### For Developers

**Adding a New Billing Strategy**:
```php
// In TicketBillingService
protected function determineBillingStrategy(Ticket $ticket): string
{
    // Add your logic
    if ($customCondition) {
        return 'custom_strategy';
    }
}

// Add strategy method
protected function billCustomStrategy(Ticket $ticket): Invoice
{
    // Implementation
}
```

---

## 🎓 Lessons from the Field

### Design Principles Applied

1. **Single Responsibility**: Each class does one thing well
2. **Open/Closed**: Easy to extend with new billing strategies
3. **Liskov Substitution**: All strategies interchangeable
4. **Interface Segregation**: Focused interfaces
5. **Dependency Inversion**: Depend on abstractions

### Best Practices

✅ **DO**:
- Use events for decoupling
- Queue heavy operations
- Log everything important
- Test edge cases thoroughly
- Provide manual overrides
- Make operations idempotent

❌ **DON'T**:
- Block user actions for billing
- Assume events always fire
- Skip error handling
- Hard-code business rules
- Trust external data
- Deploy without monitoring

---

## 📞 Support & Troubleshooting

### Common Issues

**Issue**: Jobs are queuing but not processing
```bash
# Check queue workers
supervisorctl status laravel-worker-billing

# Check queue depth
php artisan queue:work --once

# Clear failed jobs
php artisan queue:flush
```

**Issue**: Duplicate invoices generated
```bash
# Check for multiple events firing
tail -f storage/logs/laravel.log | grep TicketClosed

# Fix: Update idempotency check in listener
```

**Issue**: Billing not triggering
```bash
# Check feature flag
php artisan config:show billing.ticket_billing.automation.auto_bill_on_close

# Check event is firing
Event::fake();
// Test code
Event::assertDispatched(TicketClosed::class);
```

### Getting Help

- **Documentation**: `/docs/billing/automatic-ticket-billing.md`
- **Logs**: `storage/logs/ticket-billing.log`
- **Horizon**: `/horizon` (queue monitoring)
- **Slack**: #billing-automation channel

---

## 🎯 Next Steps

After successful implementation:

1. **Analytics Dashboard** - Build real-time billing metrics
2. **Email Notifications** - Alert clients of new invoices
3. **Payment Integration** - Auto-charge saved payment methods
4. **Predictive Billing** - ML to predict ticket costs
5. **Client Portal** - Show billing breakdown to clients
6. **API Endpoints** - Expose billing data via API
7. **Mobile App** - Mobile billing management

---

## ✅ Sign-Off

**Ready for Implementation**: YES ✓

**Architecture Reviewed By**: System Architect  
**Date**: 2025-11-06  
**Estimated Completion**: 12 working days  
**Risk Level**: Low-Medium  
**ROI Confidence**: High  

---

**Let's build something great.** 🚀
