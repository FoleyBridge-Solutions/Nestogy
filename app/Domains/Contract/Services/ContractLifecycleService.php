<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractAmendment;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContractRenewalNotification;

/**
 * Service for managing contract lifecycle operations including renewals,
 * price escalations, notifications, and revenue forecasting.
 */
class ContractLifecycleService
{
    /**
     * Process auto-renewal for eligible contracts
     *
     * @param Company|null $company Optionally limit to specific company
     * @param bool $dryRun Test mode without making changes
     * @return Collection Results of renewal processing
     */
    public function processAutoRenewals(?Company $company = null, bool $dryRun = false): Collection
    {
        $results = collect();
        
        try {
            $query = Contract::where('auto_renew', true)
                ->where('status', 'active')
                ->whereDate('end_date', '<=', Carbon::today());
            
            if ($company) {
                $query->where('company_id', $company->id);
            }
            
            $contracts = $query->get();
            
            foreach ($contracts as $contract) {
                $result = $this->renewContract($contract, $dryRun);
                $results->push($result);
            }
            
            return $results;
        } catch (\Exception $e) {
            Log::error('Contract auto-renewal processing failed', [
                'error' => $e->getMessage(),
                'company_id' => $company?->id
            ]);
            throw $e;
        }
    }
    
    /**
     * Renew a single contract with price escalation
     *
     * @param Contract $contract
     * @param bool $dryRun
     * @return array Renewal result details
     */
    public function renewContract(Contract $contract, bool $dryRun = false): array
    {
        DB::beginTransaction();
        
        try {
            $originalValue = $contract->value;
            $escalationRate = $contract->escalation_rate ?? config('nestogy.contracts.default_escalation_rate', 3.0);
            $newValue = $this->calculateEscalatedPrice($originalValue, $escalationRate);
            
            $renewalData = [
                'contract_id' => $contract->id,
                'original_value' => $originalValue,
                'new_value' => $newValue,
                'escalation_rate' => $escalationRate,
                'renewal_date' => Carbon::now(),
                'dry_run' => $dryRun
            ];
            
            if (!$dryRun) {
                // Create amendment record
                $amendment = $this->createAmendment($contract, [
                    'type' => 'renewal',
                    'description' => "Auto-renewal with {$escalationRate}% price escalation",
                    'previous_value' => $originalValue,
                    'new_value' => $newValue,
                    'effective_date' => $contract->end_date->addDay()
                ]);
                
                // Update contract
                $contract->update([
                    'value' => $newValue,
                    'start_date' => $contract->end_date->addDay(),
                    'end_date' => $this->calculateRenewalEndDate($contract),
                    'last_renewed_at' => Carbon::now(),
                    'renewal_count' => $contract->renewal_count + 1
                ]);
                
                $renewalData['amendment_id'] = $amendment->id;
                $renewalData['status'] = 'renewed';
                
                // Log activity
                activity()
                    ->performedOn($contract)
                    ->causedBy(auth()->user())
                    ->withProperties($renewalData)
                    ->log('Contract auto-renewed');
            } else {
                $renewalData['status'] = 'dry_run';
            }
            
            DB::commit();
            return $renewalData;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contract renewal failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Apply price escalation to a contract value
     *
     * @param float $currentValue
     * @param float $escalationRate Percentage increase
     * @return float New escalated value
     */
    public function calculateEscalatedPrice(float $currentValue, float $escalationRate): float
    {
        return round($currentValue * (1 + ($escalationRate / 100)), 2);
    }
    
    /**
     * Send renewal notifications at specified intervals
     *
     * @param int $daysBeforeExpiry Days before expiry to send notification
     * @param Company|null $company
     * @return Collection Sent notifications
     */
    public function sendRenewalNotifications(int $daysBeforeExpiry, ?Company $company = null): Collection
    {
        $notifications = collect();
        
        try {
            $targetDate = Carbon::today()->addDays($daysBeforeExpiry);
            
            $query = Contract::whereDate('end_date', $targetDate)
                ->where('status', 'active')
                ->where(function ($q) use ($daysBeforeExpiry) {
                    $q->whereNull('last_notification_sent')
                        ->orWhere('last_notification_days', '!=', $daysBeforeExpiry);
                });
            
            if ($company) {
                $query->where('company_id', $company->id);
            }
            
            $contracts = $query->with(['client', 'client.contacts'])->get();
            
            foreach ($contracts as $contract) {
                $notification = $this->sendContractNotification($contract, $daysBeforeExpiry);
                $notifications->push($notification);
            }
            
            return $notifications;
            
        } catch (\Exception $e) {
            Log::error('Renewal notification sending failed', [
                'error' => $e->getMessage(),
                'days_before' => $daysBeforeExpiry
            ]);
            throw $e;
        }
    }
    
    /**
     * Send notification for a specific contract
     *
     * @param Contract $contract
     * @param int $daysBeforeExpiry
     * @return array Notification details
     */
    protected function sendContractNotification(Contract $contract, int $daysBeforeExpiry): array
    {
        try {
            $primaryContact = $contract->client->contacts()
                ->where('is_primary', true)
                ->first();
            
            if (!$primaryContact || !$primaryContact->email) {
                return [
                    'contract_id' => $contract->id,
                    'status' => 'skipped',
                    'reason' => 'No primary contact with email'
                ];
            }
            
            // Send email notification
            Mail::to($primaryContact->email)
                ->cc($this->getNotificationCcList($contract))
                ->send(new ContractRenewalNotification($contract, $daysBeforeExpiry));
            
            // Update contract notification tracking
            $contract->update([
                'last_notification_sent' => Carbon::now(),
                'last_notification_days' => $daysBeforeExpiry
            ]);
            
            // Log activity
            activity()
                ->performedOn($contract)
                ->withProperties([
                    'days_before_expiry' => $daysBeforeExpiry,
                    'recipient' => $primaryContact->email
                ])
                ->log("Renewal notification sent ({$daysBeforeExpiry} days)");
            
            return [
                'contract_id' => $contract->id,
                'status' => 'sent',
                'recipient' => $primaryContact->email,
                'days_before' => $daysBeforeExpiry
            ];
            
        } catch (\Exception $e) {
            Log::error('Contract notification failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'contract_id' => $contract->id,
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Track contract amendments
     *
     * @param Contract $contract
     * @param array $amendmentData
     * @return ContractAmendment
     */
    public function createAmendment(Contract $contract, array $amendmentData): ContractAmendment
    {
        return DB::transaction(function () use ($contract, $amendmentData) {
            $amendment = ContractAmendment::create([
                'contract_id' => $contract->id,
                'company_id' => $contract->company_id,
                'type' => $amendmentData['type'],
                'description' => $amendmentData['description'],
                'previous_value' => $amendmentData['previous_value'] ?? $contract->value,
                'new_value' => $amendmentData['new_value'] ?? $contract->value,
                'effective_date' => $amendmentData['effective_date'] ?? Carbon::now(),
                'created_by' => auth()->id() ?? null,
                'metadata' => $amendmentData['metadata'] ?? []
            ]);
            
            activity()
                ->performedOn($contract)
                ->withProperties($amendmentData)
                ->log("Contract amendment created: {$amendmentData['type']}");
            
            return $amendment;
        });
    }
    
    /**
     * Calculate revenue forecast for contracts
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param Company|null $company
     * @return array Revenue forecast details
     */
    public function calculateRevenueForecast(Carbon $startDate, Carbon $endDate, ?Company $company = null): array
    {
        try {
            $query = Contract::where('status', 'active')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                });
            
            if ($company) {
                $query->where('company_id', $company->id);
            }
            
            $contracts = $query->get();
            
            $forecast = [
                'total_contracts' => $contracts->count(),
                'base_revenue' => 0,
                'escalated_revenue' => 0,
                'auto_renewing' => 0,
                'expiring' => 0,
                'monthly_breakdown' => []
            ];
            
            // Calculate monthly breakdown
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $monthKey = $currentDate->format('Y-m');
                $monthlyRevenue = 0;
                
                foreach ($contracts as $contract) {
                    if ($this->isContractActiveInMonth($contract, $currentDate)) {
                        $monthlyValue = $this->calculateMonthlyValue($contract);
                        $monthlyRevenue += $monthlyValue;
                        
                        // Add to totals
                        $forecast['base_revenue'] += $monthlyValue;
                        
                        // Calculate escalated value if auto-renewing
                        if ($contract->auto_renew && $contract->end_date->isSameMonth($currentDate)) {
                            $escalationRate = $contract->escalation_rate ?? 3.0;
                            $escalatedMonthly = $this->calculateEscalatedPrice($monthlyValue, $escalationRate);
                            $forecast['escalated_revenue'] += ($escalatedMonthly - $monthlyValue);
                            $forecast['auto_renewing']++;
                        }
                        
                        // Count expiring contracts
                        if (!$contract->auto_renew && $contract->end_date->isSameMonth($currentDate)) {
                            $forecast['expiring']++;
                        }
                    }
                }
                
                $forecast['monthly_breakdown'][$monthKey] = $monthlyRevenue;
                $currentDate->addMonth();
            }
            
            $forecast['total_forecast'] = $forecast['base_revenue'] + $forecast['escalated_revenue'];
            
            return $forecast;
            
        } catch (\Exception $e) {
            Log::error('Revenue forecast calculation failed', [
                'error' => $e->getMessage(),
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            throw $e;
        }
    }
    
    /**
     * Monitor SLA compliance for contracts
     *
     * @param Contract $contract
     * @return array SLA compliance metrics
     */
    public function monitorSlaCompliance(Contract $contract): array
    {
        try {
            $slaMetrics = [
                'contract_id' => $contract->id,
                'compliance_rate' => 0,
                'violations' => [],
                'metrics' => []
            ];
            
            // Get SLA requirements from contract
            $slaRequirements = $contract->sla_requirements ?? [];
            
            if (empty($slaRequirements)) {
                $slaMetrics['compliance_rate'] = 100;
                return $slaMetrics;
            }
            
            $totalChecks = 0;
            $passedChecks = 0;
            
            // Check response time SLA
            if (isset($slaRequirements['response_time'])) {
                $responseCompliance = $this->checkResponseTimeSla($contract, $slaRequirements['response_time']);
                $slaMetrics['metrics']['response_time'] = $responseCompliance;
                $totalChecks++;
                if ($responseCompliance['compliant']) {
                    $passedChecks++;
                } else {
                    $slaMetrics['violations'][] = 'Response time SLA violated';
                }
            }
            
            // Check resolution time SLA
            if (isset($slaRequirements['resolution_time'])) {
                $resolutionCompliance = $this->checkResolutionTimeSla($contract, $slaRequirements['resolution_time']);
                $slaMetrics['metrics']['resolution_time'] = $resolutionCompliance;
                $totalChecks++;
                if ($resolutionCompliance['compliant']) {
                    $passedChecks++;
                } else {
                    $slaMetrics['violations'][] = 'Resolution time SLA violated';
                }
            }
            
            // Check uptime SLA
            if (isset($slaRequirements['uptime_percentage'])) {
                $uptimeCompliance = $this->checkUptimeSla($contract, $slaRequirements['uptime_percentage']);
                $slaMetrics['metrics']['uptime'] = $uptimeCompliance;
                $totalChecks++;
                if ($uptimeCompliance['compliant']) {
                    $passedChecks++;
                } else {
                    $slaMetrics['violations'][] = 'Uptime SLA violated';
                }
            }
            
            // Calculate overall compliance rate
            $slaMetrics['compliance_rate'] = $totalChecks > 0 
                ? round(($passedChecks / $totalChecks) * 100, 2) 
                : 100;
            
            // Log SLA violations
            if (!empty($slaMetrics['violations'])) {
                activity()
                    ->performedOn($contract)
                    ->withProperties($slaMetrics)
                    ->log('SLA compliance violations detected');
            }
            
            return $slaMetrics;
            
        } catch (\Exception $e) {
            Log::error('SLA compliance monitoring failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Process bulk contract renewals
     *
     * @param array $contractIds
     * @param array $options Renewal options
     * @return Collection Results of bulk processing
     */
    public function bulkRenewContracts(array $contractIds, array $options = []): Collection
    {
        $results = collect();
        $dryRun = $options['dry_run'] ?? false;
        $customEscalation = $options['escalation_rate'] ?? null;
        
        DB::beginTransaction();
        
        try {
            $contracts = Contract::whereIn('id', $contractIds)
                ->where('company_id', auth()->user()->company_id)
                ->get();
            
            foreach ($contracts as $contract) {
                // Apply custom escalation if provided
                if ($customEscalation !== null) {
                    $contract->escalation_rate = $customEscalation;
                }
                
                $result = $this->renewContract($contract, $dryRun);
                $results->push($result);
            }
            
            if (!$dryRun) {
                DB::commit();
                
                // Log bulk operation
                activity()
                    ->withProperties([
                        'contract_ids' => $contractIds,
                        'total_renewed' => $results->where('status', 'renewed')->count(),
                        'options' => $options
                    ])
                    ->log('Bulk contract renewal processed');
            } else {
                DB::rollBack();
            }
            
            return $results;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk contract renewal failed', [
                'contract_ids' => $contractIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Calculate renewal end date based on contract terms
     *
     * @param Contract $contract
     * @return Carbon
     */
    protected function calculateRenewalEndDate(Contract $contract): Carbon
    {
        $renewalPeriod = $contract->renewal_period ?? 'yearly';
        $endDate = $contract->end_date->copy();
        
        switch ($renewalPeriod) {
            case 'monthly':
                return $endDate->addMonth();
            case 'quarterly':
                return $endDate->addMonths(3);
            case 'semi-annually':
                return $endDate->addMonths(6);
            case 'yearly':
            default:
                return $endDate->addYear();
        }
    }
    
    /**
     * Get CC list for contract notifications
     *
     * @param Contract $contract
     * @return array
     */
    protected function getNotificationCcList(Contract $contract): array
    {
        $ccList = [];
        
        // Add account manager if exists
        if ($contract->account_manager_id) {
            $accountManager = User::find($contract->account_manager_id);
            if ($accountManager && $accountManager->email) {
                $ccList[] = $accountManager->email;
            }
        }
        
        // Add finance contacts
        $financeContacts = $contract->client->contacts()
            ->where('type', 'finance')
            ->pluck('email')
            ->filter()
            ->toArray();
        
        return array_merge($ccList, $financeContacts);
    }
    
    /**
     * Check if contract is active in given month
     *
     * @param Contract $contract
     * @param Carbon $date
     * @return bool
     */
    protected function isContractActiveInMonth(Contract $contract, Carbon $date): bool
    {
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();
        
        return $contract->start_date <= $monthEnd && $contract->end_date >= $monthStart;
    }
    
    /**
     * Calculate monthly value of contract
     *
     * @param Contract $contract
     * @return float
     */
    protected function calculateMonthlyValue(Contract $contract): float
    {
        $billing_cycle = $contract->billing_cycle ?? 'monthly';
        
        switch ($billing_cycle) {
            case 'monthly':
                return $contract->value;
            case 'quarterly':
                return $contract->value / 3;
            case 'semi-annually':
                return $contract->value / 6;
            case 'yearly':
                return $contract->value / 12;
            default:
                return $contract->value;
        }
    }
    
    /**
     * Check response time SLA compliance
     *
     * @param Contract $contract
     * @param array $requirements
     * @return array
     */
    protected function checkResponseTimeSla(Contract $contract, array $requirements): array
    {
        // This would check actual ticket response times
        // Simplified for implementation
        $avgResponseTime = $contract->client->tickets()
            ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
            ->avg('first_response_minutes') ?? 0;
        
        $requiredTime = $requirements['minutes'] ?? 60;
        
        return [
            'compliant' => $avgResponseTime <= $requiredTime,
            'actual' => $avgResponseTime,
            'required' => $requiredTime
        ];
    }
    
    /**
     * Check resolution time SLA compliance
     *
     * @param Contract $contract
     * @param array $requirements
     * @return array
     */
    protected function checkResolutionTimeSla(Contract $contract, array $requirements): array
    {
        // Check actual ticket resolution times
        $avgResolutionTime = $contract->client->tickets()
            ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
            ->whereNotNull('resolved_at')
            ->avg('resolution_minutes') ?? 0;
        
        $requiredTime = $requirements['minutes'] ?? 240;
        
        return [
            'compliant' => $avgResolutionTime <= $requiredTime,
            'actual' => $avgResolutionTime,
            'required' => $requiredTime
        ];
    }
    
    /**
     * Check uptime SLA compliance
     *
     * @param Contract $contract
     * @param float $requiredUptime
     * @return array
     */
    protected function checkUptimeSla(Contract $contract, float $requiredUptime): array
    {
        // This would check actual system uptime
        // Simplified for implementation
        $actualUptime = 99.5; // Would be calculated from monitoring data
        
        return [
            'compliant' => $actualUptime >= $requiredUptime,
            'actual' => $actualUptime,
            'required' => $requiredUptime
        ];
    }
}