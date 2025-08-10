<?php

namespace App\Services;

use App\Models\Recurring;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * VoIPTieredPricingService
 * 
 * Handles complex tiered pricing calculations for VoIP services including
 * usage-based billing, progressive tiers, volume discounts, and hybrid pricing models.
 */
class VoIPTieredPricingService
{
    /**
     * Calculate pricing using tiered structure
     */
    public function calculateTieredPricing(Recurring $recurring, array $usageData, ?Carbon $billingDate = null): array
    {
        $billingDate = $billingDate ?? now();
        $pricingModel = $recurring->pricing_model ?? 'flat';
        
        $calculation = [
            'recurring_id' => $recurring->id,
            'billing_date' => $billingDate->toDateString(),
            'pricing_model' => $pricingModel,
            'base_charges' => 0,
            'usage_charges' => 0,
            'total_charges' => 0,
            'tier_breakdown' => [],
            'service_breakdown' => [],
            'discounts_applied' => [],
            'taxes_applicable' => false
        ];

        // Calculate base recurring charges
        $calculation['base_charges'] = $this->calculateBaseCharges($recurring, $billingDate);

        // Calculate usage-based charges based on pricing model
        switch ($pricingModel) {
            case 'usage_based':
                $calculation['usage_charges'] = $this->calculateUsageBasedCharges($recurring, $usageData);
                break;
            case 'tiered':
                $calculation['usage_charges'] = $this->calculateTieredCharges($recurring, $usageData);
                break;
            case 'hybrid':
                $calculation['usage_charges'] = $this->calculateHybridCharges($recurring, $usageData);
                break;
            case 'volume_discount':
                $calculation['usage_charges'] = $this->calculateVolumeDiscountCharges($recurring, $usageData);
                break;
            default: // flat
                $calculation['usage_charges'] = 0;
        }

        // Apply any volume discounts
        $calculation = $this->applyVolumeDiscounts($recurring, $calculation, $usageData);

        // Apply contract escalations if applicable
        $calculation = $this->applyContractEscalations($recurring, $calculation, $billingDate);

        // Calculate total
        $calculation['total_charges'] = $calculation['base_charges'] + $calculation['usage_charges'];

        // Apply any final adjustments
        $calculation = $this->applyFinalAdjustments($recurring, $calculation);

        Log::info('Tiered pricing calculated', [
            'recurring_id' => $recurring->id,
            'total_charges' => $calculation['total_charges'],
            'pricing_model' => $pricingModel
        ]);

        return $calculation;
    }

    /**
     * Calculate progressive tier pricing
     */
    public function calculateProgressiveTierPricing(array $tiers, float $usage): array
    {
        $calculation = [
            'total_usage' => $usage,
            'total_cost' => 0,
            'tiers_used' => [],
            'remaining_usage' => $usage
        ];

        foreach ($tiers as $tierIndex => $tier) {
            if ($calculation['remaining_usage'] <= 0) {
                break;
            }

            $tierMin = $tier['min_usage'] ?? 0;
            $tierMax = $tier['max_usage'] ?? null;
            $tierRate = $tier['rate'] ?? 0;
            $tierName = $tier['name'] ?? "Tier " . ($tierIndex + 1);

            // Calculate usage that falls within this tier
            $usageInTier = 0;
            if ($tierMax === null) {
                // Unlimited tier - all remaining usage
                $usageInTier = $calculation['remaining_usage'];
            } else {
                // Limited tier
                $tierCapacity = $tierMax - $tierMin;
                $usageInTier = min($calculation['remaining_usage'], $tierCapacity);
            }

            $tierCost = $usageInTier * $tierRate;
            $calculation['total_cost'] += $tierCost;

            $calculation['tiers_used'][] = [
                'tier_index' => $tierIndex,
                'tier_name' => $tierName,
                'tier_range' => [
                    'min' => $tierMin,
                    'max' => $tierMax
                ],
                'rate' => $tierRate,
                'usage_in_tier' => $usageInTier,
                'tier_cost' => $tierCost,
                'cumulative_cost' => $calculation['total_cost']
            ];

            $calculation['remaining_usage'] -= $usageInTier;
        }

        return $calculation;
    }

    /**
     * Calculate volume-based discounts
     */
    public function calculateVolumeDiscounts(Recurring $recurring, float $totalUsage, float $totalCharges): array
    {
        $discounts = ['total_discount' => 0, 'applied_discounts' => []];
        $volumeDiscounts = $recurring->volume_discounts ?? [];

        foreach ($volumeDiscounts as $discount) {
            $thresholdType = $discount['threshold_type'] ?? 'usage'; // 'usage' or 'revenue'
            $threshold = $discount['threshold'] ?? 0;
            $discountType = $discount['discount_type'] ?? 'percentage'; // 'percentage' or 'fixed'
            $discountValue = $discount['discount_value'] ?? 0;

            $qualified = false;
            $appliedTo = 0;

            if ($thresholdType === 'usage' && $totalUsage >= $threshold) {
                $qualified = true;
                $appliedTo = $totalUsage;
            } elseif ($thresholdType === 'revenue' && $totalCharges >= $threshold) {
                $qualified = true;
                $appliedTo = $totalCharges;
            }

            if ($qualified) {
                $discountAmount = 0;
                if ($discountType === 'percentage') {
                    $discountAmount = $totalCharges * ($discountValue / 100);
                } else {
                    $discountAmount = $discountValue;
                }

                $discounts['total_discount'] += $discountAmount;
                $discounts['applied_discounts'][] = [
                    'name' => $discount['name'] ?? 'Volume Discount',
                    'threshold_type' => $thresholdType,
                    'threshold' => $threshold,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'applied_to' => $appliedTo,
                    'discount_amount' => $discountAmount
                ];
            }
        }

        return $discounts;
    }

    /**
     * Calculate overage pricing for services with allowances
     */
    public function calculateOveragePricing(Recurring $recurring, array $usageByService): array
    {
        $overageCharges = ['total' => 0, 'by_service' => []];
        $serviceTiers = $recurring->service_tiers ?? [];

        foreach ($serviceTiers as $tier) {
            $serviceType = $tier['service_type'];
            $monthlyAllowance = $tier['monthly_allowance'] ?? 0;
            $overageRate = $tier['overage_rate'] ?? 0;
            $actualUsage = $usageByService[$serviceType] ?? 0;

            if ($actualUsage > $monthlyAllowance) {
                $overage = $actualUsage - $monthlyAllowance;
                $overageCharge = $overage * $overageRate;

                // Apply overage caps and minimums
                $overageCharge = $this->applyOverageLimits($tier, $overageCharge, $overage);

                $overageCharges['total'] += $overageCharge;
                $overageCharges['by_service'][$serviceType] = [
                    'allowance' => $monthlyAllowance,
                    'usage' => $actualUsage,
                    'overage' => $overage,
                    'rate' => $overageRate,
                    'charge' => $overageCharge
                ];
            }
        }

        return $overageCharges;
    }

    /**
     * Calculate bundle pricing
     */
    public function calculateBundlePricing(Recurring $recurring, array $selectedServices): array
    {
        $bundleCalculation = [
            'individual_total' => 0,
            'bundle_total' => 0,
            'bundle_savings' => 0,
            'services_included' => [],
            'bundle_applied' => false
        ];

        $bundles = $recurring->service_bundles ?? [];
        $bestBundle = null;
        $maxSavings = 0;

        foreach ($bundles as $bundle) {
            $bundleServices = $bundle['included_services'] ?? [];
            $bundlePrice = $bundle['bundle_price'] ?? 0;
            
            // Check if all required services are selected
            $allIncluded = true;
            $individualTotal = 0;
            
            foreach ($bundleServices as $serviceType) {
                if (!isset($selectedServices[$serviceType])) {
                    $allIncluded = false;
                    break;
                }
                $individualTotal += $selectedServices[$serviceType]['price'] ?? 0;
            }

            if ($allIncluded) {
                $savings = $individualTotal - $bundlePrice;
                if ($savings > $maxSavings) {
                    $maxSavings = $savings;
                    $bestBundle = $bundle;
                    $bundleCalculation['individual_total'] = $individualTotal;
                    $bundleCalculation['bundle_total'] = $bundlePrice;
                    $bundleCalculation['bundle_savings'] = $savings;
                    $bundleCalculation['services_included'] = $bundleServices;
                }
            }
        }

        if ($bestBundle) {
            $bundleCalculation['bundle_applied'] = true;
        }

        return $bundleCalculation;
    }

    /**
     * Calculate international calling charges with complex routing
     */
    public function calculateInternationalCharges(array $callData, array $internationalRates): array
    {
        $charges = [
            'total_charges' => 0,
            'total_minutes' => 0,
            'calls_processed' => 0,
            'by_destination' => [],
            'by_rate_class' => []
        ];

        foreach ($callData as $call) {
            if ($call['call_type'] !== 'international') {
                continue;
            }

            $destination = $this->getDestinationFromNumber($call['to_number']);
            $rateClass = $this->getRateClass($destination, $internationalRates);
            $rate = $internationalRates[$rateClass]['rate'] ?? 0;
            $duration = $call['duration_seconds'] / 60; // Convert to minutes
            
            // Apply minimum duration if specified
            $minDuration = $internationalRates[$rateClass]['min_duration'] ?? 0;
            $billableDuration = max($duration, $minDuration);
            
            // Apply rounding rules
            $roundingRule = $internationalRates[$rateClass]['rounding'] ?? 'none';
            $billableDuration = $this->applyRoundingRule($billableDuration, $roundingRule);
            
            $callCost = $billableDuration * $rate;

            // Update totals
            $charges['total_charges'] += $callCost;
            $charges['total_minutes'] += $billableDuration;
            $charges['calls_processed']++;

            // Update by destination
            if (!isset($charges['by_destination'][$destination])) {
                $charges['by_destination'][$destination] = [
                    'calls' => 0, 'minutes' => 0, 'charges' => 0
                ];
            }
            $charges['by_destination'][$destination]['calls']++;
            $charges['by_destination'][$destination]['minutes'] += $billableDuration;
            $charges['by_destination'][$destination]['charges'] += $callCost;

            // Update by rate class
            if (!isset($charges['by_rate_class'][$rateClass])) {
                $charges['by_rate_class'][$rateClass] = [
                    'calls' => 0, 'minutes' => 0, 'charges' => 0, 'rate' => $rate
                ];
            }
            $charges['by_rate_class'][$rateClass]['calls']++;
            $charges['by_rate_class'][$rateClass]['minutes'] += $billableDuration;
            $charges['by_rate_class'][$rateClass]['charges'] += $callCost;
        }

        return $charges;
    }

    /**
     * Calculate time-based pricing (peak/off-peak rates)
     */
    public function calculateTimeBasedPricing(array $callData, array $timeRates): array
    {
        $charges = [
            'total_charges' => 0,
            'peak_charges' => 0,
            'off_peak_charges' => 0,
            'weekend_charges' => 0,
            'by_time_period' => []
        ];

        foreach ($callData as $call) {
            $callTime = Carbon::parse($call['call_time']);
            $duration = $call['duration_seconds'] / 60;
            
            $timePeriod = $this->getTimePeriod($callTime, $timeRates);
            $rate = $timeRates[$timePeriod]['rate'] ?? 0;
            $callCost = $duration * $rate;

            $charges['total_charges'] += $callCost;
            $charges[$timePeriod . '_charges'] += $callCost;

            if (!isset($charges['by_time_period'][$timePeriod])) {
                $charges['by_time_period'][$timePeriod] = [
                    'calls' => 0, 'minutes' => 0, 'charges' => 0, 'rate' => $rate
                ];
            }
            $charges['by_time_period'][$timePeriod]['calls']++;
            $charges['by_time_period'][$timePeriod]['minutes'] += $duration;
            $charges['by_time_period'][$timePeriod]['charges'] += $callCost;
        }

        return $charges;
    }

    /**
     * Calculate base recurring charges
     */
    protected function calculateBaseCharges(Recurring $recurring, Carbon $billingDate): float
    {
        $baseCharges = $recurring->amount ?? 0;
        
        // Apply proration if mid-cycle changes
        if ($this->requiresProration($recurring, $billingDate)) {
            $baseCharges = $this->calculateProratedAmount($recurring, $baseCharges, $billingDate);
        }

        return $baseCharges;
    }

    /**
     * Calculate usage-based charges
     */
    protected function calculateUsageBasedCharges(Recurring $recurring, array $usageData): float
    {
        $totalCharges = 0;
        $serviceTiers = $recurring->service_tiers ?? [];

        foreach ($usageData as $serviceType => $usage) {
            $rate = $this->getServiceRate($serviceTiers, $serviceType);
            $totalCharges += $usage * $rate;
        }

        return $totalCharges;
    }

    /**
     * Calculate tiered charges
     */
    protected function calculateTieredCharges(Recurring $recurring, array $usageData): float
    {
        $totalCharges = 0;
        $serviceTiers = $recurring->service_tiers ?? [];

        foreach ($serviceTiers as $tier) {
            $serviceType = $tier['service_type'];
            $usage = $usageData[$serviceType] ?? 0;
            
            if ($usage > 0 && isset($tier['tier_structure'])) {
                $tierCalculation = $this->calculateProgressiveTierPricing($tier['tier_structure'], $usage);
                $totalCharges += $tierCalculation['total_cost'];
            }
        }

        return $totalCharges;
    }

    /**
     * Calculate hybrid charges (base + usage)
     */
    protected function calculateHybridCharges(Recurring $recurring, array $usageData): float
    {
        $totalCharges = 0;
        $serviceTiers = $recurring->service_tiers ?? [];

        foreach ($serviceTiers as $tier) {
            $serviceType = $tier['service_type'];
            $usage = $usageData[$serviceType] ?? 0;
            $monthlyAllowance = $tier['monthly_allowance'] ?? 0;

            // Base tier cost is included in base charges
            // Only calculate overage
            if ($usage > $monthlyAllowance) {
                $overage = $usage - $monthlyAllowance;
                $overageRate = $tier['overage_rate'] ?? 0;
                $totalCharges += $overage * $overageRate;
            }
        }

        return $totalCharges;
    }

    /**
     * Calculate volume discount charges
     */
    protected function calculateVolumeDiscountCharges(Recurring $recurring, array $usageData): float
    {
        // First calculate normal usage charges
        $baseUsageCharges = $this->calculateUsageBasedCharges($recurring, $usageData);
        
        // Then apply volume discounts
        $totalUsage = array_sum($usageData);
        $discounts = $this->calculateVolumeDiscounts($recurring, $totalUsage, $baseUsageCharges);
        
        return max(0, $baseUsageCharges - $discounts['total_discount']);
    }

    /**
     * Apply volume discounts to calculation
     */
    protected function applyVolumeDiscounts(Recurring $recurring, array $calculation, array $usageData): array
    {
        $totalUsage = array_sum($usageData);
        $discounts = $this->calculateVolumeDiscounts($recurring, $totalUsage, $calculation['total_charges']);
        
        if ($discounts['total_discount'] > 0) {
            $calculation['usage_charges'] -= $discounts['total_discount'];
            $calculation['discounts_applied'] = array_merge(
                $calculation['discounts_applied'], 
                $discounts['applied_discounts']
            );
        }

        return $calculation;
    }

    /**
     * Apply contract escalations
     */
    protected function applyContractEscalations(Recurring $recurring, array $calculation, Carbon $billingDate): array
    {
        $escalations = $recurring->metadata['contract_escalations'] ?? [];
        
        foreach ($escalations as $escalation) {
            $effectiveDate = Carbon::parse($escalation['effective_date']);
            if ($billingDate->gte($effectiveDate)) {
                $escalationPercent = $escalation['percentage'] ?? 0;
                $escalationAmount = $calculation['base_charges'] * ($escalationPercent / 100);
                
                $calculation['base_charges'] += $escalationAmount;
                $calculation['discounts_applied'][] = [
                    'name' => 'Contract Escalation',
                    'type' => 'escalation',
                    'percentage' => $escalationPercent,
                    'amount' => $escalationAmount,
                    'effective_date' => $effectiveDate->toDateString()
                ];
            }
        }

        return $calculation;
    }

    /**
     * Apply final adjustments
     */
    protected function applyFinalAdjustments(Recurring $recurring, array $calculation): array
    {
        // Apply minimum charges
        $minCharge = $recurring->metadata['minimum_charge'] ?? 0;
        if ($calculation['total_charges'] < $minCharge) {
            $adjustment = $minCharge - $calculation['total_charges'];
            $calculation['total_charges'] = $minCharge;
            $calculation['discounts_applied'][] = [
                'name' => 'Minimum Charge Adjustment',
                'type' => 'minimum_adjustment',
                'amount' => $adjustment
            ];
        }

        return $calculation;
    }

    /**
     * Apply overage limits
     */
    protected function applyOverageLimits(array $tier, float $overageCharge, float $overage): float
    {
        // Apply maximum cap
        if (isset($tier['overage_maximum']) && $overageCharge > $tier['overage_maximum']) {
            $overageCharge = $tier['overage_maximum'];
        }

        // Apply minimum charge
        if (isset($tier['overage_minimum']) && $overageCharge > 0 && $overageCharge < $tier['overage_minimum']) {
            $overageCharge = $tier['overage_minimum'];
        }

        return $overageCharge;
    }

    /**
     * Get service rate from tiers
     */
    protected function getServiceRate(array $serviceTiers, string $serviceType): float
    {
        foreach ($serviceTiers as $tier) {
            if ($tier['service_type'] === $serviceType) {
                return $tier['base_rate'] ?? 0;
            }
        }
        return 0;
    }

    /**
     * Check if proration is required
     */
    protected function requiresProration(Recurring $recurring, Carbon $billingDate): bool
    {
        // Simple check - in production this would be more sophisticated
        return $recurring->proration_enabled ?? false;
    }

    /**
     * Calculate prorated amount
     */
    protected function calculateProratedAmount(Recurring $recurring, float $baseAmount, Carbon $billingDate): float
    {
        // Simplified proration - in production this would handle various scenarios
        return $baseAmount; // Placeholder for now
    }

    /**
     * Get destination from phone number
     */
    protected function getDestinationFromNumber(string $phoneNumber): string
    {
        // Simplified destination detection
        if (preg_match('/^011(\d{1,3})/', $phoneNumber, $matches)) {
            $countryCode = $matches[1];
            
            $countries = [
                '44' => 'United Kingdom',
                '33' => 'France',
                '49' => 'Germany',
                '86' => 'China',
                '81' => 'Japan'
            ];
            
            return $countries[$countryCode] ?? 'Other International';
        }
        
        return 'Unknown';
    }

    /**
     * Get rate class for destination
     */
    protected function getRateClass(string $destination, array $internationalRates): string
    {
        foreach ($internationalRates as $class => $config) {
            if (in_array($destination, $config['destinations'] ?? [])) {
                return $class;
            }
        }
        return 'standard';
    }

    /**
     * Apply rounding rule to duration
     */
    protected function applyRoundingRule(float $duration, string $rule): float
    {
        switch ($rule) {
            case 'up':
                return ceil($duration);
            case 'down':
                return floor($duration);
            case 'nearest':
                return round($duration);
            case '6_second':
                return ceil($duration * 10) / 10; // Round up to nearest 6-second increment
            default:
                return $duration;
        }
    }

    /**
     * Get time period for call
     */
    protected function getTimePeriod(Carbon $callTime, array $timeRates): string
    {
        $hour = $callTime->hour;
        $dayOfWeek = $callTime->dayOfWeek;

        // Weekend
        if ($dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY) {
            return 'weekend';
        }

        // Peak hours (typically 8 AM - 6 PM on weekdays)
        if ($hour >= 8 && $hour < 18) {
            return 'peak';
        }

        // Off-peak
        return 'off_peak';
    }
}