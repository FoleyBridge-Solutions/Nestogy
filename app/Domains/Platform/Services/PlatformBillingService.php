<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySubscription;
use App\Domains\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PlatformBillingService
 *
 * Manages platform-level billing operations for MSP tenants.
 * Only callable by super-admin users (company_id = 1).
 */
class PlatformBillingService
{
    /**
     * Calculate total Monthly Recurring Revenue (MRR)
     * Only counts subscriptions with status = 'active' (not trialing)
     */
    public function calculateMRR(): float
    {
        return CompanySubscription::where('status', CompanySubscription::STATUS_ACTIVE)
            ->sum('monthly_amount');
    }

    /**
     * Calculate MRR for a specific period
     */
    public function calculateMRRForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $subscriptions = CompanySubscription::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', CompanySubscription::STATUS_ACTIVE)
            ->get();

        return [
            'total_mrr' => $subscriptions->sum('monthly_amount'),
            'count' => $subscriptions->count(),
            'average_mrr' => $subscriptions->avg('monthly_amount') ?? 0,
        ];
    }

    /**
     * Calculate churn rate for last 30 days
     * Formula: (canceled_last_30d / active_at_start_of_month) * 100
     */
    public function calculateChurnRate(): float
    {
        $startOfMonth = now()->subDays(30);
        
        // Count active subscriptions at start of period
        $activeAtStart = CompanySubscription::where('created_at', '<', $startOfMonth)
            ->whereIn('status', [
                CompanySubscription::STATUS_ACTIVE,
                CompanySubscription::STATUS_TRIALING,
            ])
            ->count();

        if ($activeAtStart === 0) {
            return 0;
        }

        // Count cancellations in last 30 days
        $canceledLast30Days = CompanySubscription::where('canceled_at', '>=', $startOfMonth)
            ->count();

        return round(($canceledLast30Days / $activeAtStart) * 100, 2);
    }

    /**
     * Get revenue trends over time
     */
    public function getRevenueTrends(int $months = 12): array
    {
        $trends = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $monthKey = $date->format('Y-m');
            
            $mrr = CompanySubscription::where('status', CompanySubscription::STATUS_ACTIVE)
                ->where('created_at', '<=', $date->endOfMonth())
                ->where(function ($query) use ($date) {
                    $query->whereNull('canceled_at')
                          ->orWhere('canceled_at', '>', $date->endOfMonth());
                })
                ->sum('monthly_amount');
            
            $trends[] = [
                'month' => $monthKey,
                'label' => $date->format('M Y'),
                'mrr' => $mrr,
            ];
        }
        
        return $trends;
    }

    /**
     * Get signup and cancellation trends
     */
    public function getSignupCancellationTrends(int $months = 12): array
    {
        $trends = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            
            $signups = Company::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->where('id', '>', 1) // Exclude platform company
                ->count();
            
            $cancellations = CompanySubscription::whereYear('canceled_at', $date->year)
                ->whereMonth('canceled_at', $date->month)
                ->count();
            
            $trends[] = [
                'month' => $monthKey,
                'label' => $date->format('M Y'),
                'signups' => $signups,
                'cancellations' => $cancellations,
            ];
        }
        
        return $trends;
    }

    /**
     * Suspend a tenant company
     * - Mark company as inactive
     * - Update subscription status to suspended
     * - Invalidate all user sessions immediately
     * - Queue notification email to company admin
     */
    public function suspendTenant(Company $company, ?string $reason = null): bool
    {
        if ($company->id === 1) {
            throw new \Exception('Cannot suspend platform company');
        }

        DB::beginTransaction();
        
        try {
            // Mark company as inactive
            $company->update(['is_active' => false]);
            
            // Update subscription status
            $subscription = $company->subscription;
            if ($subscription) {
                $subscription->update([
                    'status' => CompanySubscription::STATUS_SUSPENDED,
                    'suspended_at' => now(),
                    'metadata' => array_merge($subscription->metadata ?? [], [
                        'suspension_reason' => $reason,
                        'suspended_by' => Auth::id(),
                    ]),
                ]);
            }
            
            // Invalidate all sessions for company users
            $this->invalidateCompanySessions($company);
            
            // Clear remember tokens to force re-login
            User::where('company_id', $company->id)
                ->update(['remember_token' => null]);
            
            // Queue notification email
            // TODO: Uncomment when NotifyTenantSuspended job is created
            // $adminUsers = User::where('company_id', $company->id)
            //     ->whereHas('roles', function ($query) {
            //         $query->where('name', 'admin');
            //     })
            //     ->get();
            // 
            // foreach ($adminUsers as $admin) {
            //     NotifyTenantSuspended::dispatch($admin, $company, $reason);
            // }
            
            DB::commit();
            
            Log::info('Tenant suspended', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'reason' => $reason,
                'suspended_by' => Auth::id(),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to suspend tenant', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resume a suspended tenant company
     */
    public function resumeTenant(Company $company): bool
    {
        if ($company->id === 1) {
            throw new \Exception('Cannot resume platform company');
        }

        DB::beginTransaction();
        
        try {
            // Mark company as active
            $company->update(['is_active' => true]);
            
            // Update subscription status
            $subscription = $company->subscription;
            if ($subscription) {
                $subscription->update([
                    'status' => CompanySubscription::STATUS_ACTIVE,
                    'suspended_at' => null,
                    'metadata' => array_merge($subscription->metadata ?? [], [
                        'resumed_at' => now()->toDateTimeString(),
                        'resumed_by' => Auth::id(),
                    ]),
                ]);
            }
            
            // Queue notification email
            // TODO: Uncomment when NotifyTenantResumed job is created
            // $adminUsers = User::where('company_id', $company->id)
            //     ->whereHas('roles', function ($query) {
            //         $query->where('name', 'admin');
            //     })
            //     ->get();
            // 
            // foreach ($adminUsers as $admin) {
            //     NotifyTenantResumed::dispatch($admin, $company);
            // }
            
            DB::commit();
            
            Log::info('Tenant resumed', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'resumed_by' => Auth::id(),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to resume tenant', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Invalidate all sessions for company users
     */
    private function invalidateCompanySessions(Company $company): void
    {
        $userIds = User::where('company_id', $company->id)->pluck('id');
        
        // Delete sessions from database
        DB::table('sessions')
            ->whereIn('user_id', $userIds)
            ->delete();
    }

    /**
     * Get cohort analysis data
     * Tracks retention by signup month
     */
    public function getCohortAnalysis(int $months = 12): array
    {
        $cohorts = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $cohortDate = now()->subMonths($i)->startOfMonth();
            
            // Companies signed up in this month
            $signedUp = Company::whereYear('created_at', $cohortDate->year)
                ->whereMonth('created_at', $cohortDate->month)
                ->where('id', '>', 1)
                ->count();
            
            if ($signedUp === 0) {
                continue;
            }
            
            // Still active now
            $stillActive = Company::whereYear('created_at', $cohortDate->year)
                ->whereMonth('created_at', $cohortDate->month)
                ->where('id', '>', 1)
                ->where('is_active', true)
                ->whereHas('subscription', function ($query) {
                    $query->whereIn('status', [
                        CompanySubscription::STATUS_ACTIVE,
                        CompanySubscription::STATUS_TRIALING,
                    ]);
                })
                ->count();
            
            $retentionRate = round(($stillActive / $signedUp) * 100, 2);
            
            $cohorts[] = [
                'cohort_month' => $cohortDate->format('M Y'),
                'signups' => $signedUp,
                'still_active' => $stillActive,
                'retention_rate' => $retentionRate,
            ];
        }
        
        return $cohorts;
    }

    /**
     * Calculate Average Revenue Per User (ARPU)
     */
    public function calculateARPU(): float
    {
        $totalMRR = $this->calculateMRR();
        $activeCompanies = Company::where('id', '>', 1)
            ->where('is_active', true)
            ->whereHas('subscription', function ($query) {
                $query->where('status', CompanySubscription::STATUS_ACTIVE);
            })
            ->count();
        
        return $activeCompanies > 0 ? round($totalMRR / $activeCompanies, 2) : 0;
    }

    /**
     * Calculate Customer Lifetime Value (LTV)
     * Simple formula: ARPU / Churn Rate
     */
    public function calculateLTV(): float
    {
        $arpu = $this->calculateARPU();
        $churnRate = $this->calculateChurnRate();
        
        // Avoid division by zero - check if churn rate is effectively zero
        if ($churnRate <= 0.0) {
            return 0;
        }
        
        return round($arpu / ($churnRate / 100), 2);
    }

    /**
     * Get trial conversion rate
     */
    public function getTrialConversionRate(): float
    {
        $totalTrials = CompanySubscription::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->count();
        
        if ($totalTrials === 0) {
            return 0;
        }
        
        $converted = CompanySubscription::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->where('status', CompanySubscription::STATUS_ACTIVE)
            ->count();
        
        return round(($converted / $totalTrials) * 100, 2);
    }

    /**
     * Get top revenue-generating tenants
     */
    public function getTopRevenueCompanies(int $limit = 10): array
    {
        return Company::where('id', '>', 1)
            ->whereHas('subscription', function ($query) {
                $query->where('status', CompanySubscription::STATUS_ACTIVE);
            })
            ->with(['subscription'])
            ->get()
            ->sortByDesc(function ($company) {
                return $company->subscription->monthly_amount ?? 0;
            })
            ->take($limit)
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'mrr' => $company->subscription->monthly_amount ?? 0,
                    'users' => $company->subscription->current_user_count ?? 0,
                    'status' => $company->subscription->status ?? 'unknown',
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get subscriptions with failed payments
     */
    public function getFailedPaymentSubscriptions(): array
    {
        return CompanySubscription::where('status', CompanySubscription::STATUS_PAST_DUE)
            ->with(['company'])
            ->get()
            ->map(function ($subscription) {
                return [
                    'company_id' => $subscription->company_id,
                    'company_name' => $subscription->company->name ?? 'Unknown',
                    'amount' => $subscription->monthly_amount,
                    'stripe_customer_id' => $subscription->stripe_customer_id,
                    'stripe_subscription_id' => $subscription->stripe_subscription_id,
                    'current_period_end' => $subscription->current_period_end,
                ];
            })
            ->toArray();
    }

    /**
     * Get platform statistics summary
     */
    public function getPlatformStats(): array
    {
        $totalCompanies = Company::where('id', '>', 1)->count();
        $activeCompanies = Company::where('id', '>', 1)
            ->where('is_active', true)
            ->count();
        
        $activeSubscriptions = CompanySubscription::where('status', CompanySubscription::STATUS_ACTIVE)->count();
        $trialingSubscriptions = CompanySubscription::where('status', CompanySubscription::STATUS_TRIALING)->count();
        $suspendedCompanies = Company::where('id', '>', 1)
            ->where('is_active', false)
            ->count();
        
        return [
            'total_companies' => $totalCompanies,
            'active_companies' => $activeCompanies,
            'suspended_companies' => $suspendedCompanies,
            'active_subscriptions' => $activeSubscriptions,
            'trialing_subscriptions' => $trialingSubscriptions,
            'mrr' => $this->calculateMRR(),
            'churn_rate' => $this->calculateChurnRate(),
            'arpu' => $this->calculateARPU(),
            'ltv' => $this->calculateLTV(),
            'trial_conversion_rate' => $this->getTrialConversionRate(),
        ];
    }
}
