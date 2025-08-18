<?php

namespace App\Domains\Financial\Services;

use App\Models\Product;
use Carbon\Carbon;

/**
 * Billing Schedule Service
 * 
 * Focused service responsible only for generating billing schedules.
 * Demonstrates Single Responsibility Principle from composition pattern.
 */
class BillingScheduleService
{
    /**
     * Generate billing schedule for a product.
     */
    public function generateSchedule(Product $product, Carbon $startDate, int $periods = 12): array
    {
        $schedule = [];
        $currentDate = $startDate->copy();

        for ($i = 0; $i < $periods; $i++) {
            $billingDate = $this->getNextBillingDate($currentDate, $product->billing_cycle);
            
            $schedule[] = [
                'period' => $i + 1,
                'billing_date' => $billingDate->format('Y-m-d'),
                'due_date' => $billingDate->copy()->addDays($product->payment_terms ?? 30)->format('Y-m-d'),
                'amount' => $product->base_price,
                'billing_cycle' => $product->billing_cycle,
                'description' => $this->generatePeriodDescription($product, $billingDate)
            ];

            $currentDate = $billingDate;
        }

        return $schedule;
    }

    /**
     * Generate schedule with custom billing amounts.
     */
    public function generateCustomSchedule(Product $product, Carbon $startDate, array $customAmounts): array
    {
        $schedule = [];
        $currentDate = $startDate->copy();

        foreach ($customAmounts as $period => $amount) {
            $billingDate = $this->getNextBillingDate($currentDate, $product->billing_cycle);
            
            $schedule[] = [
                'period' => $period,
                'billing_date' => $billingDate->format('Y-m-d'),
                'due_date' => $billingDate->copy()->addDays($product->payment_terms ?? 30)->format('Y-m-d'),
                'amount' => $amount,
                'billing_cycle' => $product->billing_cycle,
                'description' => $this->generatePeriodDescription($product, $billingDate)
            ];

            $currentDate = $billingDate;
        }

        return $schedule;
    }

    /**
     * Get the next billing date based on cycle.
     */
    public function getNextBillingDate(Carbon $currentDate, string $billingCycle): Carbon
    {
        return match($billingCycle) {
            'weekly' => $currentDate->copy()->addWeek(),
            'monthly' => $currentDate->copy()->addMonth(),
            'quarterly' => $currentDate->copy()->addMonths(3),
            'semi-annually' => $currentDate->copy()->addMonths(6),
            'annually' => $currentDate->copy()->addYear(),
            default => $currentDate->copy()->addMonth()
        };
    }

    /**
     * Calculate billing cycle days for proration.
     */
    public function getBillingCycleDays(string $billingCycle): int
    {
        return match($billingCycle) {
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'semi-annually' => 180,
            'annually' => 365,
            default => 30
        };
    }

    /**
     * Generate period description for billing.
     */
    public function generatePeriodDescription(Product $product, Carbon $billingDate): string
    {
        $period = match($product->billing_cycle) {
            'weekly' => 'Week of ' . $billingDate->format('M d, Y'),
            'monthly' => $billingDate->format('F Y'),
            'quarterly' => 'Q' . $billingDate->quarter . ' ' . $billingDate->year,
            'semi-annually' => ($billingDate->month <= 6 ? 'First' : 'Second') . ' Half ' . $billingDate->year,
            'annually' => 'Year ' . $billingDate->year,
            default => $billingDate->format('M d, Y')
        };

        return $product->name . ' - ' . $period;
    }

    /**
     * Validate billing cycle.
     */
    public function isValidBillingCycle(string $cycle): bool
    {
        return in_array($cycle, ['weekly', 'monthly', 'quarterly', 'semi-annually', 'annually']);
    }

    /**
     * Get available billing cycles.
     */
    public function getAvailableBillingCycles(): array
    {
        return [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly', 
            'quarterly' => 'Quarterly',
            'semi-annually' => 'Semi-Annually',
            'annually' => 'Annually'
        ];
    }
}