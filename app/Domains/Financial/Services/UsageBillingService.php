<?php

namespace App\Domains\Financial\Services;

use App\Models\Product;
use Carbon\Carbon;

/**
 * Usage Billing Service
 * 
 * Focused service responsible only for usage-based billing calculations.
 * Demonstrates Single Responsibility Principle from composition pattern.
 */
class UsageBillingService
{
    /**
     * Calculate usage-based billing for a product.
     */
    public function calculateUsageBilling(Product $product, float $usage, Carbon $periodStart, Carbon $periodEnd): array
    {
        if ($product->billing_model !== 'usage_based') {
            throw new \InvalidArgumentException('Product does not support usage-based billing');
        }

        $includedUnits = $product->usage_included ?? 0;
        $unitPrice = $product->usage_rate ?? $product->base_price;
        
        $calculation = $this->performUsageCalculation($product->base_price, $usage, $includedUnits, $unitPrice);

        return array_merge($calculation, [
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'usage' => $usage,
            'included_units' => $includedUnits,
            'unit_price' => $unitPrice,
            'unit_type' => $product->unit_type ?? 'units',
            'billing_model' => 'usage_based'
        ]);
    }

    /**
     * Calculate tiered usage billing.
     */
    public function calculateTieredUsage(Product $product, float $usage, array $tiers): array
    {
        $totalAmount = 0;
        $remainingUsage = $usage;
        $tierBreakdown = [];

        foreach ($tiers as $tier) {
            if ($remainingUsage <= 0) break;

            $tierUsage = min($remainingUsage, $tier['limit'] - $tier['start']);
            $tierAmount = $tierUsage * $tier['rate'];
            
            $tierBreakdown[] = [
                'tier' => $tier['name'],
                'usage' => $tierUsage,
                'rate' => $tier['rate'],
                'amount' => $tierAmount,
                'start' => $tier['start'],
                'limit' => $tier['limit']
            ];

            $totalAmount += $tierAmount;
            $remainingUsage -= $tierUsage;
        }

        return [
            'total_amount' => round($totalAmount, 2),
            'total_usage' => $usage,
            'tier_breakdown' => $tierBreakdown,
            'billing_model' => 'tiered_usage'
        ];
    }

    /**
     * Calculate overage charges for exceeded limits.
     */
    public function calculateOverageCharges(Product $product, float $usage, float $includedLimit): array
    {
        $overage = max(0, $usage - $includedLimit);
        $overageRate = $product->overage_rate ?? $product->usage_rate ?? 0;
        $overageAmount = $overage * $overageRate;

        return [
            'overage_units' => $overage,
            'overage_rate' => $overageRate,
            'overage_amount' => round($overageAmount, 2),
            'included_limit' => $includedLimit,
            'total_usage' => $usage,
            'within_limit' => $usage <= $includedLimit
        ];
    }

    /**
     * Calculate usage billing with minimum commitment.
     */
    public function calculateWithMinimumCommitment(Product $product, float $usage, float $minimumCommitment): array
    {
        $usageCalculation = $this->calculateUsageBilling(
            $product, 
            $usage, 
            now()->startOfMonth(), 
            now()->endOfMonth()
        );

        $minimumAmount = $minimumCommitment;
        $usageAmount = $usageCalculation['total_amount'];
        $finalAmount = max($minimumAmount, $usageAmount);
        $minimumFeeApplied = $finalAmount === $minimumAmount;

        return array_merge($usageCalculation, [
            'minimum_commitment' => $minimumCommitment,
            'usage_amount' => $usageAmount,
            'final_amount' => $finalAmount,
            'minimum_fee_applied' => $minimumFeeApplied,
            'commitment_shortfall' => $minimumFeeApplied ? ($minimumAmount - $usageAmount) : 0
        ]);
    }

    /**
     * Get usage summary for multiple products.
     */
    public function getUsageSummary(array $products, array $usageData, Carbon $periodStart, Carbon $periodEnd): array
    {
        $summary = [
            'total_usage_amount' => 0,
            'total_base_amount' => 0,
            'total_overage_amount' => 0,
            'products' => []
        ];

        foreach ($products as $product) {
            $usage = $usageData[$product->id] ?? 0;
            
            if ($product->billing_model === 'usage_based') {
                $calculation = $this->calculateUsageBilling($product, $usage, $periodStart, $periodEnd);
                
                $summary['products'][] = array_merge($calculation, [
                    'product_id' => $product->id,
                    'product_name' => $product->name
                ]);
                
                $summary['total_usage_amount'] += $calculation['total_amount'];
                $summary['total_base_amount'] += $calculation['base_amount'];
                $summary['total_overage_amount'] += $calculation['overage_amount'];
            }
        }

        return $summary;
    }

    /**
     * Perform the core usage calculation logic.
     */
    protected function performUsageCalculation(float $basePrice, float $usage, float $includedUnits, float $unitPrice): array
    {
        $baseAmount = $basePrice;
        $overageAmount = 0;
        $overageUnits = max(0, $usage - $includedUnits);

        if ($overageUnits > 0) {
            $overageAmount = $overageUnits * $unitPrice;
        }

        $totalAmount = $baseAmount + $overageAmount;

        return [
            'base_amount' => $baseAmount,
            'overage_amount' => $overageAmount,
            'overage_units' => $overageUnits,
            'total_amount' => round($totalAmount, 2)
        ];
    }

    /**
     * Validate usage data for billing.
     */
    public function validateUsageData(Product $product, float $usage): array
    {
        $errors = [];

        if ($usage < 0) {
            $errors[] = 'Usage cannot be negative';
        }

        if ($product->billing_model !== 'usage_based') {
            $errors[] = 'Product does not support usage-based billing';
        }

        if (empty($product->usage_rate) && empty($product->base_price)) {
            $errors[] = 'Product must have either usage rate or base price defined';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}