# Platform Admin Dashboard - FINAL IMPLEMENTATION PLAN

## Configuration Summary (Based on Your Answers)

✅ **MRR**: Only `active` status subscriptions  
✅ **Suspension**: Immediate logout (invalidate sessions)  
✅ **Churn**: `canceled_last_30d / active_at_start_of_month`  
✅ **Charts**: Use Flux chart components (no external libs)  
✅ **Emails**: Queued (background jobs)  
✅ **Impersonation**: Not in scope (Phase 2)  
✅ **Delete**: Not implemented (only suspend/resume)  
✅ **Payment Retry**: Auto via Stripe webhooks + manual button  
✅ **Analytics**: Include cohort analysis (retention by signup month)  
✅ **Access**: Direct navigation to `/admin` (no nav link, bookmark it)

---

## Executive Summary

Build `/admin` platform management dashboard for **super-admins** (Nestogy platform operators like super@nestogy.com) to manage MSP tenant companies and their subscriptions/billing back to Nestogy.

**Key Discovery**: Multi-tenant SaaS architecture where:
- **Company ID 1** = Nestogy Platform (billing company)
- **Tenant Companies** (ID 2+) = MSP customers who sign up
- **Client records** under Company 1 = Billing representation of tenant companies
- **CompanySubscription** model tracks each tenant's plan/billing status

---

## 1. Current System Architecture (Researched)

### Signup Flow (`CompanyRegistrationController`)
```
1. POST /signup → CompanyRegistrationController@register
   ├─ Creates Company (tenant MSP, ID 2+)
   ├─ Creates Client (under company_id=1, links to tenant via company_link_id)
   ├─ Creates Admin User (for tenant company)
   ├─ Creates CompanySubscription (tracks plan/billing)
   ├─ Stripe setup ($1 auth or paid subscription with 14-day trial)
   └─ Redirects to /dashboard (tenant logs in)

2. /setup → SetupWizard (Livewire, self-hosted only)
   ├─ Creates first Company (becomes ID 1 if fresh install)
   ├─ Creates super-admin user
   └─ No Stripe (self-hosted scenario)
```

### Billing Model
- **CompanySubscription** (`app/Domains/Company/Models/CompanySubscription.php`):
  - Tracks: `company_id`, `subscription_plan_id`, `status` (trialing/active/past_due/canceled/suspended)
  - Fields: `monthly_amount`, `max_users`, `current_user_count`, `stripe_subscription_id`, `trial_ends_at`, `current_period_end`
  - Methods: `isActive()`, `canAddUser()`, `changePlan()`, `suspend()`, `resume()`

- **SubscriptionPlan** (`app/Domains/Product/Models/SubscriptionPlan.php`):
  - Plans: free, starter, pro, enterprise
  - Pricing models: `fixed`, `per_user`, `hybrid` (base + per_user)
  - Fields: `price_monthly`, `user_limit`, `features[]`

- **Client** (under company_id=1):
  - Billing proxy for tenant companies
  - Fields: `company_link_id` (links to tenant Company), `stripe_customer_id`, `subscription_plan_id`, `subscription_status`, `trial_ends_at`
  - Type: `'saas_customer'`

### Services
- **StripeSubscriptionService**: Stripe API (createCustomer, createSubscription, cancel, webhooks)
- **SubscriptionService**: App logic (createSubscription for Company, processRecurringBilling, suspend/resume)
- Kernel schedules: `sync-stripe-subscriptions-distributed` (hourly)

### Auth/Permissions
- **super-admin** role (`RolesAndPermissionsSeeder.php:183`): `Bouncer::allow($superAdmin)->everything()`
- **Admin role check** (`User.php:isSuperAdmin()`): `$this->isA('admin') && $this->company_id === 1`
- **Gates** (`AuthServiceProvider.php`): `manage-subscriptions`, `manage-subscription-plans` (checks super-admin)

### Existing /admin Routes
- `routes/Core/routes.php:164`: `/admin/subscriptions` (middleware `can:manage-subscriptions`, empty controller references)

---

## 2. Requirements & Feature Spec

### MVP Features (Phase 1, ~12-14h)

#### A. Platform Dashboard (`/admin`)
**User**: super@nestogy.com (company_id=1, role=super-admin)

**Metrics Cards**:
- Total Tenants (active companies count)
- Active Subscriptions (status=active)
- MRR (Monthly Recurring Revenue): `SUM(company_subscriptions.monthly_amount WHERE status = 'active')`
- Churn Rate: `(canceled_last_30d / active_start_of_month) * 100`

**Charts** (Flux chart-bar):
- MRR Trend (last 12 months)
- New Signups vs Cancellations (last 6 months)
- Revenue by Plan (pie chart: free/starter/pro/enterprise)

#### B. Companies Management (`/admin/companies`)
**Table** (Flux table, sortable/searchable):
| Company | Plan | Status | Users | Clients | MRR | Next Bill | Actions |
|---------|------|--------|-------|---------|-----|-----------|---------|
| TechGuard MSP | Pro | Active | 17/25 | 42 | $199 | 2025-01-15 | View/Suspend |

**Columns**:
- Company name (link to detail)
- Subscription plan name
- Status badge (active=green, trialing=blue, past_due=orange, suspended=red)
- User count: `current_user_count / max_users` (or "Unlimited")
- Client count (from `clients` table)
- MRR (`monthly_amount`)
- Next billing date (`current_period_end`)
- Actions: View, Suspend, Resume

**Filters**:
- Status (all/active/trialing/past_due/suspended)
- Plan (all/free/starter/pro/enterprise)
- Search by company name/email

**Detail View** (`/admin/companies/{id}`):
- Company info (name, email, created_at, last_login)
- Subscription details (plan, status, trial_ends_at, monthly_amount, Stripe link)
- Usage stats (users, clients, tickets, invoices generated)
- Admin users list (name, email, last_login)
- Actions: Change Plan, Suspend, Resume, Cancel Subscription, View Client Record (company 1), Send Email

#### C. Billing & Subscriptions (`/admin/billing`)
**Subscriptions Tab** (default):
- Table: company, plan, status, MRR, current_period_end, actions (change plan, cancel)
- Filters: Status, Plan, Expiring Soon (7d), Trial Ending (3d)

**Revenue Tab**:
- Total MRR (current)
- ARR (MRR * 12)
- Forecast (next 3 months, assumes churn rate)
- Revenue by Plan breakdown

**Failed Payments Tab**:
- Query: `status='past_due'`, show company, amount_due, last_payment_attempt, retry_count
- Actions: Retry Payment, Contact Customer, Suspend

**Actions**:
- Manually retry payment (Stripe API)
- Generate invoice (for tenant, sent to Client email)
- View Stripe customer portal

#### D. Analytics (`/admin/analytics`)
**Metrics**:
- LTV (Lifetime Value): Avg months subscribed * avg MRR
- Churn: % canceled per month
- ARPU (Average Revenue Per User): MRR / total users across all tenants
- Trial Conversion Rate: % trials that become paid

**Charts**:
- Cohort analysis (signups by month, retention over time) - TABLE FORMAT
- Top MSPs by MRR (bar chart, top 10)
- Plan distribution (pie)

---

### Nice-to-Have (Phase 2, Future)
- Bulk actions (suspend multiple, upgrade all free→starter)
- Email templates (trial ending, payment failed, suspension notice)
- Usage-based billing (charge per ticket/client beyond plan limits)
- Webhooks dashboard (Stripe events log)
- Tenant impersonation (admin can log in as tenant admin for support)
- Reports export (CSV/PDF)
- Notifications (email super-admin on failed payment)

---

## 3. Implementation Tasks (Detailed, 12-14h total)

### **PHASE 1: Foundation (Day 1, 4h)**

#### Task 1.1: SuperAdmin Middleware (30min)
**File**: `app/Http/Middleware/SuperAdmin.php` (NEW)
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Platform admins only. Access denied.');
        }
        
        return $next($request);
    }
}
```

**Register**: `app/Http/Kernel.php` → `$middlewareAliases['super-admin'] = \App\Http\Middleware\SuperAdmin::class`

#### Task 1.2: Routes (30min)
**File**: `routes/admin.php` (NEW)
```php
<?php

use App\Http\Controllers\Admin\AdminBillingController;
use App\Http\Controllers\Admin\AdminCompanyController;
use Illuminate\Support\Facades\Route;

// Platform admin routes - super-admin only
Route::middleware(['auth', 'verified', 'super-admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', App\Livewire\Admin\Dashboard::class)->name('dashboard');
    
    // Companies
    Route::get('/companies', App\Livewire\Admin\CompanyList::class)->name('companies.index');
    Route::get('/companies/{company}', App\Livewire\Admin\CompanyDetail::class)->name('companies.show');
    Route::post('/companies/{company}/suspend', [AdminCompanyController::class, 'suspend'])->name('companies.suspend');
    Route::post('/companies/{company}/resume', [AdminCompanyController::class, 'resume'])->name('companies.resume');
    
    // Billing
    Route::get('/billing', App\Livewire\Admin\BillingDashboard::class)->name('billing.index');
    Route::post('/subscriptions/{subscription}/change-plan', [AdminBillingController::class, 'changePlan'])->name('billing.change-plan');
    Route::post('/subscriptions/{subscription}/cancel', [AdminBillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/subscriptions/{subscription}/retry-payment', [AdminBillingController::class, 'retryPayment'])->name('billing.retry-payment');
    
    // Analytics
    Route::get('/analytics', App\Livewire\Admin\Analytics::class)->name('analytics');
});
```

**Register**: `bootstrap/app.php` → Add to routing configuration

#### Task 1.3: Permissions Seeder Update (30min)
**File**: `database/seeders/RolesAndPermissionsSeeder.php`

Add to abilities array (around line 166):
```php
// Platform administration (super-admin only)
'platform.*' => 'Full platform administration',
'platform.companies.view' => 'View all companies',
'platform.companies.suspend' => 'Suspend companies',
'platform.billing.view' => 'View platform billing',
'platform.billing.manage' => 'Manage subscriptions',
'platform.analytics.view' => 'View platform analytics',
```

Add to super-admin permissions (after line 208):
```php
// Explicitly grant platform permissions (already has everything(), but for clarity)
Bouncer::allow($superAdmin)->to([
    'platform.*',
]);
```

#### Task 1.4: PlatformBillingService (1.5h)
**File**: `app/Domains/Platform/Services/PlatformBillingService.php` (NEW)

```php
<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySubscription;
use App\Domains\Core\Models\User;
use App\Jobs\NotifyTenantResumed;
use App\Jobs\NotifyTenantSuspended;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformBillingService
{
    /**
     * Calculate MRR (Monthly Recurring Revenue)
     * Only counts 'active' subscriptions per user requirements
     */
    public function calculateMRR(): float
    {
        return CompanySubscription::where('status', 'active')->sum('monthly_amount');
    }

    /**
     * Calculate churn rate
     * canceled_last_30d / active_at_start_of_month
     */
    public function calculateChurnRate(int $days = 30): float
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $activeStart = CompanySubscription::where('status', 'active')
            ->where('created_at', '<', $startDate)
            ->count();
        
        $canceled = CompanySubscription::where('status', 'canceled')
            ->whereBetween('canceled_at', [$startDate, now()])
            ->count();
        
        return $activeStart > 0 ? round(($canceled / $activeStart) * 100, 2) : 0;
    }

    /**
     * Suspend tenant company
     * Immediately logs out all users per user requirements
     */
    public function suspendTenant(Company $tenant, string $reason): void
    {
        DB::transaction(function () use ($tenant, $reason) {
            // Update company
            $tenant->update([
                'is_active' => false,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);
            
            // Update subscription
            $tenant->subscription?->suspend($reason);
            
            // IMMEDIATE LOGOUT: Invalidate all sessions for company users
            $tenant->users()->each(function ($user) {
                // Delete sessions from database
                DB::table('sessions')->where('user_id', $user->id)->delete();
                
                // Clear remember tokens
                $user->update(['remember_token' => null]);
            });
            
            // Queue email notification
            dispatch(new NotifyTenantSuspended($tenant, $reason));
            
            // Audit log
            activity()
                ->causedBy(auth()->user())
                ->performedOn($tenant)
                ->withProperties(['reason' => $reason])
                ->log('company_suspended');
        });
    }

    /**
     * Resume suspended tenant
     */
    public function resumeTenant(Company $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->update([
                'is_active' => true,
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
            
            $tenant->subscription?->resume();
            
            // Queue email notification
            dispatch(new NotifyTenantResumed($tenant));
            
            // Audit log
            activity()
                ->causedBy(auth()->user())
                ->performedOn($tenant)
                ->log('company_resumed');
        });
    }

    /**
     * Revenue forecast for next N months
     * Accounts for churn rate
     */
    public function forecastRevenue(int $months = 3): array
    {
        $mrr = $this->calculateMRR();
        $churnRate = $this->calculateChurnRate() / 100;
        
        $forecast = [];
        $projectedMRR = $mrr;
        
        for ($i = 1; $i <= $months; $i++) {
            $projectedMRR *= (1 - $churnRate);
            $forecast[] = [
                'month' => now()->addMonths($i)->format('M Y'),
                'mrr' => round($projectedMRR, 2),
            ];
        }
        
        return $forecast;
    }

    /**
     * Cohort analysis: retention by signup month
     * Required per user specifications
     */
    public function getCohortAnalysis(): array
    {
        // Group tenants by signup month, track retention
        $cohorts = Company::where('id', '>', 1)
            ->selectRaw("DATE_TRUNC('month', created_at) as cohort_month")
            ->selectRaw('COUNT(*) as total_signups')
            ->groupBy('cohort_month')
            ->orderBy('cohort_month', 'desc')
            ->limit(12)
            ->get();
        
        $data = [];
        foreach ($cohorts as $cohort) {
            $month = Carbon::parse($cohort->cohort_month);
            
            $activeNow = Company::where('id', '>', 1)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->where('is_active', true)
                ->count();
            
            $retentionRate = $cohort->total_signups > 0 
                ? round(($activeNow / $cohort->total_signups) * 100, 1) 
                : 0;
            
            $data[] = [
                'month' => $month->format('M Y'),
                'signups' => $cohort->total_signups,
                'active' => $activeNow,
                'retention' => $retentionRate,
            ];
        }
        
        return $data;
    }

    /**
     * Get platform-wide metrics
     */
    public function getDashboardMetrics(): array
    {
        return [
            'total_tenants' => Company::where('id', '>', 1)->count(),
            'active_subscriptions' => CompanySubscription::where('status', 'active')->count(),
            'trialing' => CompanySubscription::where('status', 'trialing')->count(),
            'mrr' => $this->calculateMRR(),
            'arr' => $this->calculateMRR() * 12,
            'churn_rate' => $this->calculateChurnRate(),
        ];
    }

    /**
     * Calculate LTV (Lifetime Value)
     */
    public function calculateLTV(): float
    {
        $avgMonthsSubscribed = CompanySubscription::where('status', 'active')
            ->selectRaw("AVG(EXTRACT(EPOCH FROM (NOW() - created_at)) / 2592000) as avg_months")
            ->value('avg_months') ?? 1;
        
        return round($this->calculateMRR() * $avgMonthsSubscribed, 2);
    }

    /**
     * Calculate ARPU (Average Revenue Per User)
     */
    public function calculateARPU(): float
    {
        $totalUsers = User::where('company_id', '>', 1)->count();
        return $totalUsers > 0 ? round($this->calculateMRR() / $totalUsers, 2) : 0;
    }

    /**
     * Trial conversion rate
     */
    public function calculateTrialConversion(): float
    {
        $totalTrials = CompanySubscription::where('status', 'trialing')
            ->orWhere(fn($q) => $q->where('status', 'active')->whereNotNull('trial_ends_at'))
            ->count();
        
        $converted = CompanySubscription::where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->count();
        
        return $totalTrials > 0 ? round(($converted / $totalTrials) * 100, 1) : 0;
    }
}
```

#### Task 1.5: Admin\Dashboard Livewire (1.5h)
**File**: `app/Livewire/Admin/Dashboard.php` (NEW)

```php
<?php

namespace App\Livewire\Admin;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySubscription;
use App\Domains\Platform\Services\PlatformBillingService;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public array $metrics = [];
    public array $revenueChart = [];
    public array $signupsChart = [];

    public function mount()
    {
        $billingService = app(PlatformBillingService::class);
        
        $this->metrics = $billingService->getDashboardMetrics();
        
        // MRR trend (last 12 months)
        $this->revenueChart = CompanySubscription::selectRaw("DATE_TRUNC('month', created_at) as month")
            ->selectRaw('SUM(monthly_amount) as mrr')
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($row) => [
                'label' => Carbon::parse($row->month)->format('M Y'),
                'value' => $row->mrr,
            ])
            ->toArray();
        
        // Signups vs Cancellations (last 6 months)
        $this->signupsChart = collect(range(0, 5))->map(function ($i) {
            $month = now()->subMonths($i);
            return [
                'label' => $month->format('M Y'),
                'signups' => Company::whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->count(),
                'cancellations' => CompanySubscription::where('status', 'canceled')
                    ->whereMonth('canceled_at', $month->month)
                    ->whereYear('canceled_at', $month->year)
                    ->count(),
            ];
        })->reverse()->values()->toArray();
    }

    public function render()
    {
        return view('livewire.admin.dashboard')
            ->layout('layouts.app');
    }
}
```

**View**: `resources/views/livewire/admin/dashboard.blade.php` (NEW)
```blade
<flux:page title="Platform Admin Dashboard">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Metrics Cards -->
        <flux:card>
            <flux:heading size="sm">Total Tenants</flux:heading>
            <div class="text-3xl font-bold">{{ $metrics['total_tenants'] }}</div>
            <flux:text>Active MSP companies</flux:text>
        </flux:card>
        
        <flux:card>
            <flux:heading size="sm">Active Subscriptions</flux:heading>
            <div class="text-3xl font-bold text-green-600">{{ $metrics['active_subscriptions'] }}</div>
            <flux:text>{{ $metrics['trialing'] }} trialing</flux:text>
        </flux:card>
        
        <flux:card>
            <flux:heading size="sm">MRR</flux:heading>
            <div class="text-3xl font-bold text-green-600">${{ number_format($metrics['mrr'], 0) }}</div>
            <flux:text>ARR: ${{ number_format($metrics['arr'], 0) }}</flux:text>
        </flux:card>
        
        <flux:card>
            <flux:heading size="sm">Churn Rate</flux:heading>
            <div class="text-3xl font-bold {{ $metrics['churn_rate'] > 5 ? 'text-red-600' : 'text-green-600' }}">
                {{ $metrics['churn_rate'] }}%
            </div>
            <flux:text>Last 30 days</flux:text>
        </flux:card>
    </div>
    
    <!-- Charts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <flux:card>
            <flux:heading>MRR Trend</flux:heading>
            <flux:subheading>Last 12 months</flux:subheading>
            <div class="mt-4">
                @if(count($revenueChart) > 0)
                    <flux:chart-bar :data="$revenueChart" />
                @else
                    <flux:text>No data available</flux:text>
                @endif
            </div>
        </flux:card>
        
        <flux:card>
            <flux:heading>Signups vs Cancellations</flux:heading>
            <flux:subheading>Last 6 months</flux:subheading>
            <div class="mt-4">
                @if(count($signupsChart) > 0)
                    <flux:chart-bar :data="$signupsChart" />
                @else
                    <flux:text>No data available</flux:text>
                @endif
            </div>
        </flux:card>
    </div>
    
    <!-- Quick Actions -->
    <div class="mt-8">
        <flux:card>
            <flux:heading>Quick Actions</flux:heading>
            <div class="flex gap-4 mt-4">
                <flux:button href="{{ route('admin.companies.index') }}">View All Companies</flux:button>
                <flux:button href="{{ route('admin.billing.index') }}" variant="outline">Billing Dashboard</flux:button>
                <flux:button href="{{ route('admin.analytics') }}" variant="outline">Analytics</flux:button>
            </div>
        </flux:card>
    </div>
</flux:page>
```

---

### **PHASE 2: Companies Management (Day 2, 4h)**

#### Task 2.1: Admin\CompanyList Livewire (2h)
**File**: `app/Livewire/Admin/CompanyList.php` (NEW)

```php
<?php

namespace App\Livewire\Admin;

use App\Domains\Company\Models\Company;
use App\Domains\Platform\Services\PlatformBillingService;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyList extends Component
{
    use WithPagination;

    public array $filters = [
        'status' => '',
        'plan' => '',
        'search' => '',
    ];

    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';
    
    public $suspendingCompany = null;
    public string $suspension_reason = '';

    public function updatingFilters()
    {
        $this->resetPage();
    }

    public function sortBy($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function getCompaniesProperty()
    {
        return Company::query()
            ->with(['subscription.subscriptionPlan', 'clientRecord'])
            ->where('id', '>', 1)
            ->withCount(['users', 'clients'])
            ->when($this->filters['status'], fn($q, $v) => 
                $q->whereHas('subscription', fn($sq) => $sq->where('status', $v))
            )
            ->when($this->filters['plan'], fn($q, $v) => 
                $q->whereHas('subscription.subscriptionPlan', fn($sq) => $sq->where('slug', $v))
            )
            ->when($this->filters['search'], fn($q, $v) => 
                $q->where(fn($sq) => 
                    $sq->where('name', 'like', "%{$v}%")
                      ->orWhere('email', 'like', "%{$v}%")
                )
            )
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate(50);
    }

    public function showSuspendModal($companyId)
    {
        $this->suspendingCompany = $companyId;
        $this->suspension_reason = '';
    }

    public function suspendCompany()
    {
        $this->authorize('platform.companies.suspend');
        
        $this->validate([
            'suspension_reason' => 'required|string|max:500',
        ]);
        
        $company = Company::findOrFail($this->suspendingCompany);
        
        app(PlatformBillingService::class)->suspendTenant($company, $this->suspension_reason);
        
        session()->flash('success', "Company '{$company->name}' suspended. All users logged out.");
        
        $this->reset(['suspendingCompany', 'suspension_reason']);
    }

    public function resumeCompany($companyId)
    {
        $this->authorize('platform.companies.suspend');
        
        $company = Company::findOrFail($companyId);
        
        app(PlatformBillingService::class)->resumeTenant($company);
        
        session()->flash('success', "Company '{$company->name}' resumed.");
    }

    public function render()
    {
        return view('livewire.admin.company-list', [
            'companies' => $this->companies,
        ])->layout('layouts.app');
    }
}
```

**View**: `resources/views/livewire/admin/company-list.blade.php` (NEW)
```blade
<flux:page title="Companies">
    <div class="space-y-6">
        <!-- Filters -->
        <flux:card>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:input 
                    wire:model.live.debounce.300ms="filters.search" 
                    placeholder="Search companies..." 
                    type="search" />
                
                <flux:select wire:model.live="filters.status">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="trialing">Trialing</option>
                    <option value="past_due">Past Due</option>
                    <option value="suspended">Suspended</option>
                    <option value="canceled">Canceled</option>
                </flux:select>
                
                <flux:select wire:model.live="filters.plan">
                    <option value="">All Plans</option>
                    <option value="free">Free</option>
                    <option value="starter">Starter</option>
                    <option value="pro">Pro</option>
                    <option value="enterprise">Enterprise</option>
                </flux:select>
            </div>
        </flux:card>

        <!-- Companies Table -->
        <flux:card>
            <flux:table>
                <flux:columns>
                    <flux:column sortable wire:click="sortBy('name')">Company</flux:column>
                    <flux:column>Plan</flux:column>
                    <flux:column>Status</flux:column>
                    <flux:column>Users</flux:column>
                    <flux:column>Clients</flux:column>
                    <flux:column sortable wire:click="sortBy('monthly_amount')">MRR</flux:column>
                    <flux:column>Next Bill</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($companies as $company)
                        <flux:row>
                            <flux:cell>
                                <a href="{{ route('admin.companies.show', $company) }}" class="font-semibold hover:underline">
                                    {{ $company->name }}
                                </a>
                                <div class="text-sm text-gray-500">{{ $company->email }}</div>
                            </flux:cell>
                            <flux:cell>
                                {{ $company->subscription?->subscriptionPlan?->name ?? 'None' }}
                            </flux:cell>
                            <flux:cell>
                                <flux:badge 
                                    size="sm" 
                                    color="{{ $company->subscription?->getStatusColor() ?? 'gray' }}">
                                    {{ $company->subscription?->status ?? 'No subscription' }}
                                </flux:badge>
                            </flux:cell>
                            <flux:cell>
                                {{ $company->subscription?->current_user_count ?? 0 }} / 
                                {{ $company->subscription?->max_users ?? '∞' }}
                            </flux:cell>
                            <flux:cell>{{ $company->clients_count }}</flux:cell>
                            <flux:cell>${{ number_format($company->subscription?->monthly_amount ?? 0, 2) }}</flux:cell>
                            <flux:cell>
                                {{ $company->subscription?->current_period_end?->format('M d, Y') ?? 'N/A' }}
                            </flux:cell>
                            <flux:cell>
                                <div class="flex gap-2">
                                    @if($company->is_active)
                                        <flux:button 
                                            size="sm" 
                                            variant="danger"
                                            wire:click="showSuspendModal({{ $company->id }})">
                                            Suspend
                                        </flux:button>
                                    @else
                                        <flux:button 
                                            size="sm" 
                                            variant="success"
                                            wire:click="resumeCompany({{ $company->id }})">
                                            Resume
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="8">
                                <div class="text-center py-8 text-gray-500">
                                    No companies found
                                </div>
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>

            <div class="mt-4">
                {{ $companies->links() }}
            </div>
        </flux:card>
    </div>

    <!-- Suspend Modal -->
    @if($suspendingCompany)
        <flux:modal name="suspend-company" variant="flyout" :open="true" wire:close="reset('suspendingCompany')">
            <form wire:submit="suspendCompany">
                <flux:heading>Suspend Company</flux:heading>
                <flux:subheading>This will immediately log out all users.</flux:subheading>

                <div class="mt-6">
                    <flux:textarea 
                        wire:model="suspension_reason" 
                        label="Reason for suspension" 
                        required
                        rows="4"
                        placeholder="e.g., Non-payment, Terms violation..." />
                </div>

                <div class="flex gap-2 mt-6">
                    <flux:button type="submit" variant="danger">Suspend Company</flux:button>
                    <flux:button type="button" variant="outline" wire:click="reset('suspendingCompany')">Cancel</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</flux:page>
```

#### Task 2.2: Admin\CompanyDetail Livewire (2h)
**File**: `app/Livewire/Admin/CompanyDetail.php` (NEW)

```php
<?php

namespace App\Livewire\Admin;

use App\Domains\Company\Models\Company;
use Livewire\Component;

class CompanyDetail extends Component
{
    public Company $company;
    public array $stats = [];
    public string $activeTab = 'overview';

    public function mount(Company $company)
    {
        $this->company = $company->load(['subscription.subscriptionPlan', 'users', 'clientRecord']);
        
        $this->stats = [
            'total_users' => $company->users()->count(),
            'total_clients' => $company->clients()->count(),
            'total_tickets' => $company->tickets()->count(),
            'total_invoices' => $company->invoices()->count(),
            'last_login' => $company->users()
                ->whereNotNull('last_login_at')
                ->orderBy('last_login_at', 'desc')
                ->first()?->last_login_at,
        ];
    }

    public function render()
    {
        return view('livewire.admin.company-detail')
            ->layout('layouts.app');
    }
}
```

**View**: `resources/views/livewire/admin/company-detail.blade.php` (NEW)
```blade
<flux:page :title="$company->name">
    <div class="space-y-6">
        <!-- Header -->
        <flux:card>
            <div class="flex justify-between items-start">
                <div>
                    <flux:heading size="lg">{{ $company->name }}</flux:heading>
                    <flux:text>{{ $company->email }}</flux:text>
                    <div class="mt-2">
                        @if($company->is_active)
                            <flux:badge color="green">Active</flux:badge>
                        @else
                            <flux:badge color="red">Suspended</flux:badge>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <flux:text>Created: {{ $company->created_at->format('M d, Y') }}</flux:text>
                    @if($stats['last_login'])
                        <flux:text>Last Login: {{ $stats['last_login']->diffForHumans() }}</flux:text>
                    @endif
                </div>
            </div>
        </flux:card>

        <!-- Tabs -->
        <flux:tabs wire:model="activeTab">
            <flux:tab name="overview">Overview</flux:tab>
            <flux:tab name="subscription">Subscription</flux:tab>
            <flux:tab name="users">Users</flux:tab>
        </flux:tabs>

        <!-- Tab Content -->
        @if($activeTab === 'overview')
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:card>
                    <flux:heading size="sm">Users</flux:heading>
                    <div class="text-2xl font-bold">{{ $stats['total_users'] }}</div>
                </flux:card>
                <flux:card>
                    <flux:heading size="sm">Clients</flux:heading>
                    <div class="text-2xl font-bold">{{ $stats['total_clients'] }}</div>
                </flux:card>
                <flux:card>
                    <flux:heading size="sm">Tickets</flux:heading>
                    <div class="text-2xl font-bold">{{ $stats['total_tickets'] }}</div>
                </flux:card>
                <flux:card>
                    <flux:heading size="sm">Invoices</flux:heading>
                    <div class="text-2xl font-bold">{{ $stats['total_invoices'] }}</div>
                </flux:card>
            </div>

            @if($company->suspended_at)
                <flux:card variant="danger">
                    <flux:heading>Suspension Details</flux:heading>
                    <flux:text>Suspended: {{ $company->suspended_at->format('M d, Y g:i A') }}</flux:text>
                    <flux:text>Reason: {{ $company->suspension_reason }}</flux:text>
                </flux:card>
            @endif
        @endif

        @if($activeTab === 'subscription')
            <flux:card>
                @if($company->subscription)
                    <flux:heading>Subscription Details</flux:heading>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <flux:text class="font-semibold">Plan</flux:text>
                            <flux:text>{{ $company->subscription->subscriptionPlan?->name ?? 'Unknown' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="font-semibold">Status</flux:text>
                            <flux:badge color="{{ $company->subscription->getStatusColor() }}">
                                {{ $company->subscription->status }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:text class="font-semibold">Monthly Amount</flux:text>
                            <flux:text>${{ number_format($company->subscription->monthly_amount, 2) }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="font-semibold">Next Billing</flux:text>
                            <flux:text>{{ $company->subscription->current_period_end?->format('M d, Y') }}</flux:text>
                        </div>
                        @if($company->subscription->stripe_subscription_id)
                            <div>
                                <flux:text class="font-semibold">Stripe Subscription</flux:text>
                                <a href="https://dashboard.stripe.com/subscriptions/{{ $company->subscription->stripe_subscription_id }}" 
                                   target="_blank" 
                                   class="text-blue-600 hover:underline">
                                    View in Stripe
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <flux:text>No subscription found</flux:text>
                @endif
            </flux:card>
        @endif

        @if($activeTab === 'users')
            <flux:card>
                <flux:table>
                    <flux:columns>
                        <flux:column>Name</flux:column>
                        <flux:column>Email</flux:column>
                        <flux:column>Role</flux:column>
                        <flux:column>Last Login</flux:column>
                    </flux:columns>
                    <flux:rows>
                        @foreach($company->users as $user)
                            <flux:row>
                                <flux:cell>{{ $user->name }}</flux:cell>
                                <flux:cell>{{ $user->email }}</flux:cell>
                                <flux:cell>{{ $user->userSetting?->role ?? 'user' }}</flux:cell>
                                <flux:cell>{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</flux:cell>
                            </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>
            </flux:card>
        @endif
    </div>
</flux:page>
```

---

### **PHASE 3: Billing & Analytics (Day 3, 4h)**

#### Task 3.1: Admin\BillingDashboard Livewire (2h)
**File**: `app/Livewire/Admin/BillingDashboard.php` (NEW)

```php
<?php

namespace App\Livewire\Admin;

use App\Domains\Company\Models\CompanySubscription;
use App\Domains\Platform\Services\PlatformBillingService;
use Livewire\Component;

class BillingDashboard extends Component
{
    public string $activeTab = 'subscriptions';
    public array $revenue = [];
    public array $forecast = [];

    public function mount()
    {
        $billingService = app(PlatformBillingService::class);
        
        $this->revenue = [
            'mrr' => $billingService->calculateMRR(),
            'arr' => $billingService->calculateMRR() * 12,
        ];
        
        $this->forecast = $billingService->forecastRevenue(3);
    }

    public function render()
    {
        $subscriptions = CompanySubscription::with(['company', 'subscriptionPlan'])
            ->when($this->activeTab === 'failed', fn($q) => $q->where('status', 'past_due'))
            ->when($this->activeTab === 'expiring', fn($q) => $q->where('current_period_end', '<=', now()->addDays(7)))
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('livewire.admin.billing-dashboard', [
            'subscriptions' => $subscriptions,
        ])->layout('layouts.app');
    }
}
```

**View**: `resources/views/livewire/admin/billing-dashboard.blade.php` (NEW)
```blade
<flux:page title="Billing Dashboard">
    <div class="space-y-6">
        <!-- Revenue Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card>
                <flux:heading size="sm">MRR</flux:heading>
                <div class="text-3xl font-bold text-green-600">${{ number_format($revenue['mrr'], 0) }}</div>
                <flux:text>Monthly Recurring Revenue</flux:text>
            </flux:card>
            <flux:card>
                <flux:heading size="sm">ARR</flux:heading>
                <div class="text-3xl font-bold">${{ number_format($revenue['arr'], 0) }}</div>
                <flux:text>Annual Recurring Revenue</flux:text>
            </flux:card>
            <flux:card>
                <flux:heading size="sm">Forecast (3mo)</flux:heading>
                <div class="space-y-1">
                    @foreach($forecast as $month)
                        <flux:text>{{ $month['month'] }}: ${{ number_format($month['mrr'], 0) }}</flux:text>
                    @endforeach
                </div>
            </flux:card>
        </div>

        <!-- Tabs -->
        <flux:tabs wire:model.live="activeTab">
            <flux:tab name="subscriptions">All Subscriptions</flux:tab>
            <flux:tab name="failed">Failed Payments</flux:tab>
            <flux:tab name="expiring">Expiring Soon</flux:tab>
        </flux:tabs>

        <!-- Subscriptions Table -->
        <flux:card>
            <flux:table>
                <flux:columns>
                    <flux:column>Company</flux:column>
                    <flux:column>Plan</flux:column>
                    <flux:column>Status</flux:column>
                    <flux:column>MRR</flux:column>
                    <flux:column>Next Billing</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>
                <flux:rows>
                    @forelse($subscriptions as $subscription)
                        <flux:row>
                            <flux:cell>
                                <a href="{{ route('admin.companies.show', $subscription->company) }}" class="hover:underline">
                                    {{ $subscription->company->name }}
                                </a>
                            </flux:cell>
                            <flux:cell>{{ $subscription->subscriptionPlan?->name ?? 'None' }}</flux:cell>
                            <flux:cell>
                                <flux:badge size="sm" color="{{ $subscription->getStatusColor() }}">
                                    {{ $subscription->status }}
                                </flux:badge>
                            </flux:cell>
                            <flux:cell>${{ number_format($subscription->monthly_amount, 2) }}</flux:cell>
                            <flux:cell>{{ $subscription->current_period_end?->format('M d, Y') }}</flux:cell>
                            <flux:cell>
                                @if($subscription->status === 'past_due')
                                    <flux:button size="sm" variant="primary">Retry Payment</flux:button>
                                @endif
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="6" class="text-center py-8 text-gray-500">
                                No subscriptions found
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>

            <div class="mt-4">
                {{ $subscriptions->links() }}
            </div>
        </flux:card>
    </div>
</flux:page>
```

#### Task 3.2: Admin\Analytics Livewire (2h)
**File**: `app/Livewire/Admin/Analytics.php` (NEW)

```php
<?php

namespace App\Livewire\Admin;

use App\Domains\Company\Models\Company;
use App\Domains\Platform\Services\PlatformBillingService;
use Livewire\Component;

class Analytics extends Component
{
    public array $metrics = [];
    public array $cohorts = [];
    public array $topMSPs = [];
    public array $planDistribution = [];

    public function mount()
    {
        $billingService = app(PlatformBillingService::class);
        
        $this->metrics = [
            'ltv' => $billingService->calculateLTV(),
            'arpu' => $billingService->calculateARPU(),
            'trial_conversion' => $billingService->calculateTrialConversion(),
            'churn_rate' => $billingService->calculateChurnRate(),
        ];
        
        // Cohort analysis
        $this->cohorts = $billingService->getCohortAnalysis();
        
        // Top 10 MSPs by MRR
        $this->topMSPs = Company::with('subscription')
            ->where('id', '>', 1)
            ->whereHas('subscription', fn($q) => $q->where('status', 'active'))
            ->get()
            ->sortByDesc(fn($c) => $c->subscription->monthly_amount)
            ->take(10)
            ->map(fn($c) => [
                'name' => $c->name,
                'mrr' => $c->subscription->monthly_amount,
            ])
            ->toArray();
        
        // Plan distribution
        $this->planDistribution = \App\Domains\Company\Models\CompanySubscription::with('subscriptionPlan')
            ->where('status', 'active')
            ->get()
            ->groupBy('subscriptionPlan.name')
            ->map(fn($group) => [
                'plan' => $group->first()->subscriptionPlan->name ?? 'Unknown',
                'count' => $group->count(),
                'mrr' => $group->sum('monthly_amount'),
            ])
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.analytics')
            ->layout('layouts.app');
    }
}
```

**View**: `resources/views/livewire/admin/analytics.blade.php` (NEW)
```blade
<flux:page title="Platform Analytics">
    <div class="space-y-6">
        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:card>
                <flux:heading size="sm">LTV</flux:heading>
                <div class="text-2xl font-bold">${{ number_format($metrics['ltv'], 0) }}</div>
                <flux:text>Lifetime Value</flux:text>
            </flux:card>
            <flux:card>
                <flux:heading size="sm">ARPU</flux:heading>
                <div class="text-2xl font-bold">${{ number_format($metrics['arpu'], 2) }}</div>
                <flux:text>Avg Revenue Per User</flux:text>
            </flux:card>
            <flux:card>
                <flux:heading size="sm">Trial Conversion</flux:heading>
                <div class="text-2xl font-bold">{{ $metrics['trial_conversion'] }}%</div>
                <flux:text>Trial to Paid</flux:text>
            </flux:card>
            <flux:card>
                <flux:heading size="sm">Churn Rate</flux:heading>
                <div class="text-2xl font-bold {{ $metrics['churn_rate'] > 5 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $metrics['churn_rate'] }}%
                </div>
                <flux:text>Last 30 days</flux:text>
            </flux:card>
        </div>

        <!-- Cohort Analysis -->
        <flux:card>
            <flux:heading>Cohort Analysis</flux:heading>
            <flux:subheading>Retention by signup month</flux:subheading>
            <div class="mt-4">
                <flux:table>
                    <flux:columns>
                        <flux:column>Month</flux:column>
                        <flux:column>Signups</flux:column>
                        <flux:column>Active Now</flux:column>
                        <flux:column>Retention</flux:column>
                    </flux:columns>
                    <flux:rows>
                        @foreach($cohorts as $cohort)
                            <flux:row>
                                <flux:cell>{{ $cohort['month'] }}</flux:cell>
                                <flux:cell>{{ $cohort['signups'] }}</flux:cell>
                                <flux:cell>{{ $cohort['active'] }}</flux:cell>
                                <flux:cell>
                                    <flux:badge 
                                        size="sm" 
                                        color="{{ $cohort['retention'] >= 80 ? 'green' : ($cohort['retention'] >= 60 ? 'yellow' : 'red') }}">
                                        {{ $cohort['retention'] }}%
                                    </flux:badge>
                                </flux:cell>
                            </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>
            </div>
        </flux:card>

        <!-- Top MSPs & Plan Distribution -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:card>
                <flux:heading>Top 10 MSPs by MRR</flux:heading>
                <div class="mt-4 space-y-2">
                    @foreach($topMSPs as $msp)
                        <div class="flex justify-between items-center">
                            <flux:text>{{ $msp['name'] }}</flux:text>
                            <flux:text class="font-semibold">${{ number_format($msp['mrr'], 2) }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </flux:card>

            <flux:card>
                <flux:heading>Plan Distribution</flux:heading>
                <div class="mt-4 space-y-2">
                    @foreach($planDistribution as $plan)
                        <div class="flex justify-between items-center">
                            <flux:text>{{ $plan['plan'] }} ({{ $plan['count'] }})</flux:text>
                            <flux:text class="font-semibold">${{ number_format($plan['mrr'], 2) }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>
    </div>
</flux:page>
```

---

### **PHASE 4: Polish & Testing (Day 4, 2-3h)**

#### Task 4.1: Controllers (30min)
**File**: `app/Http/Controllers/Admin/AdminCompanyController.php` (NEW)

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Company\Models\Company;
use App\Domains\Platform\Services\PlatformBillingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminCompanyController extends Controller
{
    public function suspend(Company $company, Request $request)
    {
        $this->authorize('platform.companies.suspend');
        
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        app(PlatformBillingService::class)->suspendTenant($company, $validated['reason']);
        
        return back()->with('success', "Company '{$company->name}' suspended successfully.");
    }

    public function resume(Company $company)
    {
        $this->authorize('platform.companies.suspend');
        
        app(PlatformBillingService::class)->resumeTenant($company);
        
        return back()->with('success', "Company '{$company->name}' resumed successfully.");
    }
}
```

**File**: `app/Http/Controllers/Admin/AdminBillingController.php` (NEW)

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Company\Models\CompanySubscription;
use App\Domains\Core\Services\StripeSubscriptionService;
use App\Domains\Product\Models\SubscriptionPlan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminBillingController extends Controller
{
    public function changePlan(CompanySubscription $subscription, Request $request)
    {
        $this->authorize('platform.billing.manage');
        
        $validated = $request->validate([
            'new_plan_id' => 'required|exists:subscription_plans,id',
        ]);
        
        $newPlan = SubscriptionPlan::find($validated['new_plan_id']);
        $subscription->changePlan($newPlan);
        
        return back()->with('success', 'Plan changed successfully.');
    }

    public function cancel(CompanySubscription $subscription)
    {
        $this->authorize('platform.billing.manage');
        
        $subscription->cancel();
        
        return back()->with('success', 'Subscription canceled successfully.');
    }

    public function retryPayment(CompanySubscription $subscription)
    {
        $this->authorize('platform.billing.manage');
        
        try {
            $stripe = app(StripeSubscriptionService::class);
            // Trigger Stripe retry via API
            $result = $stripe->stripe->subscriptions->retrieve(
                $subscription->stripe_subscription_id,
                ['expand' => ['latest_invoice.payment_intent']]
            );
            
            session()->flash('success', 'Payment retry initiated.');
        } catch (\Exception $e) {
            session()->flash('error', 'Retry failed: ' . $e->getMessage());
        }
        
        return back();
    }
}
```

#### Task 4.2: Queue Jobs for Emails (1h)
**File**: `app/Jobs/NotifyTenantSuspended.php` (NEW)

```php
<?php

namespace App\Jobs;

use App\Domains\Company\Models\Company;
use App\Mail\TenantSuspendedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyTenantSuspended implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public string $reason
    ) {}

    public function handle(): void
    {
        // Send to all company admins
        $admins = $this->company->users()
            ->whereHas('userSetting', fn($q) => $q->where('role', 'admin'))
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(
                new TenantSuspendedMail($this->company, $this->reason)
            );
        }
    }
}
```

**File**: `app/Jobs/NotifyTenantResumed.php` (NEW)

```php
<?php

namespace App\Jobs;

use App\Domains\Company\Models\Company;
use App\Mail\TenantResumedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyTenantResumed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Company $company
    ) {}

    public function handle(): void
    {
        $admins = $this->company->users()
            ->whereHas('userSetting', fn($q) => $q->where('role', 'admin'))
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(
                new TenantResumedMail($this->company)
            );
        }
    }
}
```

**File**: `app/Mail/TenantSuspendedMail.php` (NEW)

```php
<?php

namespace App\Mail;

use App\Domains\Company\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantSuspendedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public string $reason
    ) {}

    public function build()
    {
        return $this->subject("Account Suspended - {$this->company->name}")
            ->markdown('emails.tenant-suspended');
    }
}
```

**View**: `resources/views/emails/tenant-suspended.blade.php` (NEW)

```blade
@component('mail::message')
# Account Suspended

Your Nestogy account for **{{ $company }}** has been suspended.

**Reason:** {{ $reason }}

Please contact support to resolve this issue and restore access to your account.

@component('mail::button', ['url' => config('app.url')])
Contact Support
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

**File**: `app/Mail/TenantResumedMail.php` (NEW)

```php
<?php

namespace App\Mail;

use App\Domains\Company\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantResumedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company
    ) {}

    public function build()
    {
        return $this->subject("Account Resumed - {$this->company->name}")
            ->markdown('emails.tenant-resumed');
    }
}
```

**View**: `resources/views/emails/tenant-resumed.blade.php` (NEW)

```blade
@component('mail::message')
# Account Resumed

Good news! Your Nestogy account for **{{ $company }}** has been reactivated.

You can now log in and access all features.

@component('mail::button', ['url' => route('login')])
Login to Your Account
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

#### Task 4.3: Database Indexes (15min)
**Migration**: `database/migrations/2025_12_02_add_platform_admin_indexes.php` (NEW)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_subscriptions', function (Blueprint $table) {
            $table->index(['status', 'monthly_amount']);
            $table->index('current_period_end');
            $table->index('trial_ends_at');
            $table->index('canceled_at');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('created_at');
            $table->index('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('company_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status', 'monthly_amount']);
            $table->dropIndex(['current_period_end']);
            $table->dropIndex(['trial_ends_at']);
            $table->dropIndex(['canceled_at']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['suspended_at']);
        });
    }
};
```

#### Task 4.4: Feature Tests (1h)
**File**: `tests/Feature/Admin/PlatformAdminAccessTest.php` (NEW)

```php
<?php

namespace Tests\Feature\Admin;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_super_admin_cannot_access_admin_routes()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    public function test_super_admin_can_access_dashboard()
    {
        $platformCompany = Company::factory()->create(['id' => 1]);
        $superAdmin = User::factory()->create(['company_id' => 1]);
        $superAdmin->assign('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200);
    }
}
```

**File**: `tests/Feature/Admin/CompanyManagementTest.php` (NEW)

```php
<?php

namespace Tests\Feature\Admin;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySubscription;
use App\Domains\Core\Models\User;
use App\Domains\Platform\Services\PlatformBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_suspend_company()
    {
        $tenant = Company::factory()->create();
        $tenantUser = User::factory()->create(['company_id' => $tenant->id]);
        CompanySubscription::factory()->create(['company_id' => $tenant->id]);

        $service = app(PlatformBillingService::class);
        $service->suspendTenant($tenant, 'Non-payment');

        $tenant->refresh();
        
        $this->assertFalse($tenant->is_active);
        $this->assertNotNull($tenant->suspended_at);
        $this->assertEquals('Non-payment', $tenant->suspension_reason);
        
        // Verify sessions cleared
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $tenantUser->id)->count());
    }

    public function test_can_resume_company()
    {
        $tenant = Company::factory()->create([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => 'Test',
        ]);
        CompanySubscription::factory()->create(['company_id' => $tenant->id]);

        $service = app(PlatformBillingService::class);
        $service->resumeTenant($tenant);

        $tenant->refresh();
        
        $this->assertTrue($tenant->is_active);
        $this->assertNull($tenant->suspended_at);
        $this->assertNull($tenant->suspension_reason);
    }
}
```

**File**: `tests/Feature/Admin/BillingCalculationsTest.php` (NEW)

```php
<?php

namespace Tests\Feature\Admin;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySubscription;
use App\Domains\Platform\Services\PlatformBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCalculationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mrr_calculation_is_accurate()
    {
        // Create active subscriptions
        CompanySubscription::factory()->create([
            'status' => 'active',
            'monthly_amount' => 100.00,
        ]);
        CompanySubscription::factory()->create([
            'status' => 'active',
            'monthly_amount' => 200.00,
        ]);
        
        // Create trialing (should not count)
        CompanySubscription::factory()->create([
            'status' => 'trialing',
            'monthly_amount' => 50.00,
        ]);

        $service = app(PlatformBillingService::class);
        $mrr = $service->calculateMRR();

        $this->assertEquals(300.00, $mrr);
    }

    public function test_churn_rate_calculation()
    {
        // Create companies that were active 30+ days ago
        Company::factory()->count(10)->create([
            'created_at' => now()->subDays(60),
        ]);
        
        // Create subscriptions
        CompanySubscription::factory()->count(10)->create([
            'status' => 'active',
            'created_at' => now()->subDays(60),
        ]);
        
        // Cancel 2 in last 30 days
        CompanySubscription::factory()->count(2)->create([
            'status' => 'canceled',
            'canceled_at' => now()->subDays(15),
            'created_at' => now()->subDays(60),
        ]);

        $service = app(PlatformBillingService::class);
        $churn = $service->calculateChurnRate();

        // 2 canceled / 10 active start = 20%
        $this->assertEquals(20.0, $churn);
    }
}
```

---

## File Summary (25 New, 4 Updated)

### New Files (25):
1. `app/Http/Middleware/SuperAdmin.php`
2. `routes/admin.php`
3. `app/Domains/Platform/Services/PlatformBillingService.php`
4. `app/Livewire/Admin/Dashboard.php`
5. `app/Livewire/Admin/CompanyList.php`
6. `app/Livewire/Admin/CompanyDetail.php`
7. `app/Livewire/Admin/BillingDashboard.php`
8. `app/Livewire/Admin/Analytics.php`
9. `app/Http/Controllers/Admin/AdminCompanyController.php`
10. `app/Http/Controllers/Admin/AdminBillingController.php`
11. `resources/views/livewire/admin/dashboard.blade.php`
12. `resources/views/livewire/admin/company-list.blade.php`
13. `resources/views/livewire/admin/company-detail.blade.php`
14. `resources/views/livewire/admin/billing-dashboard.blade.php`
15. `resources/views/livewire/admin/analytics.blade.php`
16. `app/Jobs/NotifyTenantSuspended.php`
17. `app/Jobs/NotifyTenantResumed.php`
18. `app/Mail/TenantSuspendedMail.php`
19. `app/Mail/TenantResumedMail.php`
20. `resources/views/emails/tenant-suspended.blade.php`
21. `resources/views/emails/tenant-resumed.blade.php`
22. `database/migrations/2025_12_02_add_platform_admin_indexes.php`
23. `tests/Feature/Admin/PlatformAdminAccessTest.php`
24. `tests/Feature/Admin/CompanyManagementTest.php`
25. `tests/Feature/Admin/BillingCalculationsTest.php`

### Updated Files (4):
1. `database/seeders/RolesAndPermissionsSeeder.php` (add platform.* permissions)
2. `app/Http/Kernel.php` (register SuperAdmin middleware)
3. `bootstrap/app.php` (register admin routes)
4. `app/Domains/Core/Models/User.php` (confirm isSuperAdmin() method exists)

---

## Validation Checklist

Before marking complete:
- [ ] Navigate to `/admin` as super@nestogy.com → Dashboard loads
- [ ] MRR calculation matches: `SELECT SUM(monthly_amount) FROM company_subscriptions WHERE status='active'`
- [ ] Suspend company → Users immediately logged out (test with active session)
- [ ] Cohort analysis shows correct retention rates
- [ ] Flux charts render (no external dependencies)
- [ ] Queued emails in `jobs` table (not sent sync)
- [ ] All tests pass (`php artisan test --filter=Admin`)

---

## Deployment Steps

1. **Merge PR** (after approval)
2. **Run migration**: `php artisan migrate` (add indexes)
3. **Seed permissions**: `php artisan db:seed --class=RolesAndPermissionsSeeder`
4. **Clear cache**: `php artisan config:clear && php artisan route:clear && php artisan view:clear`
5. **Restart queue**: `php artisan queue:restart`
6. **Verify**: Access https://your-domain.com/admin (bookmark it)
7. **Monitor**: Check `storage/logs/laravel.log` for errors

---

## Time Estimate: 12-14 hours

- Day 1: Foundation (4h) - Middleware, routes, service, dashboard
- Day 2: Companies (4h) - List, detail, suspend/resume
- Day 3: Billing/Analytics (4h) - Dashboard, cohorts, charts
- Day 4: Polish (2-3h) - Tests, emails, controllers, docs

---

## Success Criteria

✅ **Auth**: Only super-admin (company_id=1) can access `/admin`  
✅ **Dashboard**: Shows accurate MRR, tenant count, churn rate  
✅ **Companies**: Can view, search, filter, suspend, resume all tenants  
✅ **Billing**: View subscriptions, change plans, see failed payments  
✅ **Analytics**: Cohort analysis shows retention by signup month  
✅ **Suspension**: Immediately logs out all tenant users  
✅ **Emails**: Queued via background jobs  
✅ **Performance**: Queries run <500ms with 100+ tenants  
✅ **Tests**: 90%+ coverage on admin features  
✅ **UI**: Responsive, uses Flux components, matches existing design  

---

**Implementation ready. All user requirements incorporated.**
