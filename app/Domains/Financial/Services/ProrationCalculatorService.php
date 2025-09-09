<?php

namespace App\Domains\Financial\Services;

use App\Models\Product;
use Carbon\Carbon;

/**
 * Proration Calculator Service
 * 
 * Focused service responsible only for proration calculations.
 * Demonstrates Single Responsibility Principle from composition pattern.
 */
class ProrationCalculatorService
{
    protected BillingScheduleService $scheduleService;

    public function __construct(BillingScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Calculate prorated amount for partial period.
     */
    public function calculateProratedAmount(Product $product, Carbon $startDate, Carbon $endDate = null): array
    {
        if ($product->billing_model !== 'subscription') {
            return [
                'amount' => $product->base_price,
                'days' => 0,
                'is_prorated' => false,
                'calculation_method' => 'full_amount'
            ];
        }

        $endDate = $endDate ?? $this->scheduleService->getNextBillingDate($startDate, $product->billing_cycle);
        $totalDays = $this->scheduleService->getBillingCycleDays($product->billing_cycle);
        $usedDays = $startDate->diffInDays($endDate);
        
        $proratedAmount = ($product->base_price / $totalDays) * $usedDays;

        return [
            'amount' => round($proratedAmount, 2),
            'days' => $usedDays,
            'total_days' => $totalDays,
            'is_prorated' => true,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'calculation_method' => 'daily_proration',
            'daily_rate' => round($product->base_price / $totalDays, 4)
        ];
    }

    /**
     * Calculate proration for service changes.
     */
    public function calculateServiceChangeProration(Product $oldProduct, Product $newProduct, Carbon $changeDate, Carbon $cycleEndDate): array
    {
        $remainingDays = $changeDate->diffInDays($cycleEndDate);
        $totalDays = $this->scheduleService->getBillingCycleDays($newProduct->billing_cycle);
        
        // Credit for unused portion of old product
        $creditAmount = ($oldProduct->base_price / $totalDays) * $remainingDays;
        
        // Charge for new product from change date
        $chargeAmount = ($newProduct->base_price / $totalDays) * $remainingDays;
        
        $netAmount = $chargeAmount - $creditAmount;

        return [
            'credit_amount' => round($creditAmount, 2),
            'charge_amount' => round($chargeAmount, 2),
            'net_amount' => round($netAmount, 2),
            'remaining_days' => $remainingDays,
            'total_days' => $totalDays,
            'change_date' => $changeDate->format('Y-m-d'),
            'cycle_end_date' => $cycleEndDate->format('Y-m-d'),
            'old_product' => $oldProduct->name,
            'new_product' => $newProduct->name
        ];
    }

    /**
     * Calculate proration for mid-cycle cancellation.
     */
    public function calculateCancellationProration(Product $product, Carbon $lastBillingDate, Carbon $cancellationDate): array
    {
        $cycleEndDate = $this->scheduleService->getNextBillingDate($lastBillingDate, $product->billing_cycle);
        $totalDays = $this->scheduleService->getBillingCycleDays($product->billing_cycle);
        $usedDays = $lastBillingDate->diffInDays($cancellationDate);
        $unusedDays = $cancellationDate->diffInDays($cycleEndDate);
        
        // Calculate refund for unused portion
        $refundAmount = ($product->base_price / $totalDays) * $unusedDays;

        return [
            'refund_amount' => round($refundAmount, 2),
            'used_days' => $usedDays,
            'unused_days' => $unusedDays,
            'total_days' => $totalDays,
            'last_billing_date' => $lastBillingDate->format('Y-m-d'),
            'cancellation_date' => $cancellationDate->format('Y-m-d'),
            'cycle_end_date' => $cycleEndDate->format('Y-m-d'),
            'daily_rate' => round($product->base_price / $totalDays, 4)
        ];
    }

    /**
     * Calculate upgrade/downgrade proration with different billing cycles.
     */
    public function calculateCycleChangeProration(Product $oldProduct, Product $newProduct, Carbon $changeDate): array
    {
        // Handle different billing cycles during product changes
        $oldCycleDays = $this->scheduleService->getBillingCycleDays($oldProduct->billing_cycle);
        $newCycleDays = $this->scheduleService->getBillingCycleDays($newProduct->billing_cycle);
        
        // Normalize to daily rates
        $oldDailyRate = $oldProduct->base_price / $oldCycleDays;
        $newDailyRate = $newProduct->base_price / $newCycleDays;
        
        // Calculate based on the new product's billing cycle
        $nextBillingDate = $this->scheduleService->getNextBillingDate($changeDate, $newProduct->billing_cycle);
        $daysUntilNextBilling = $changeDate->diffInDays($nextBillingDate);
        
        $oldProductChargeForPeriod = $oldDailyRate * $daysUntilNextBilling;
        $newProductChargeForPeriod = $newDailyRate * $daysUntilNextBilling;
        
        $adjustmentAmount = $newProductChargeForPeriod - $oldProductChargeForPeriod;

        return [
            'old_daily_rate' => round($oldDailyRate, 4),
            'new_daily_rate' => round($newDailyRate, 4),
            'days_until_next_billing' => $daysUntilNextBilling,
            'old_product_charge' => round($oldProductChargeForPeriod, 2),
            'new_product_charge' => round($newProductChargeForPeriod, 2),
            'adjustment_amount' => round($adjustmentAmount, 2),
            'change_date' => $changeDate->format('Y-m-d'),
            'next_billing_date' => $nextBillingDate->format('Y-m-d'),
            'billing_cycle_changed' => $oldProduct->billing_cycle !== $newProduct->billing_cycle
        ];
    }

    /**
     * Get proration summary for multiple products.
     */
    public function getProrationSummary(array $products, Carbon $startDate, Carbon $endDate): array
    {
        $summary = [
            'total_prorated_amount' => 0,
            'total_full_amount' => 0,
            'total_savings' => 0,
            'products' => []
        ];

        foreach ($products as $product) {
            $proration = $this->calculateProratedAmount($product, $startDate, $endDate);
            
            $summary['products'][] = array_merge($proration, [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'full_amount' => $product->base_price
            ]);
            
            $summary['total_prorated_amount'] += $proration['amount'];
            $summary['total_full_amount'] += $product->base_price;
        }

        $summary['total_savings'] = $summary['total_full_amount'] - $summary['total_prorated_amount'];
        $summary['savings_percentage'] = $summary['total_full_amount'] > 0 
            ? round(($summary['total_savings'] / $summary['total_full_amount']) * 100, 2) 
            : 0;

        return $summary;
    }
}