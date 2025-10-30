<?php

namespace App\Domains\Client\Services;

use App\Domains\Client\Models\ClientService;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Recurring;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service Billing Service
 * 
 * Integrates client services with the financial/billing system:
 * - Creates recurring billing schedules
 * - Generates service invoices
 * - Handles proration for partial periods
 * - Calculates cancellation fees
 * - Manages billing suspension/resumption
 */
class ServiceBillingService
{
    /**
     * Create recurring billing for an active service
     */
    public function createRecurringBilling(ClientService $service): ?Recurring
    {
        if ($service->hasRecurringBilling()) {
            return $service->recurringBilling;
        }

        if ($service->monthly_cost <= 0) {
            return null;
        }

        return DB::transaction(function () use ($service) {
            // Map billing cycle to frequency
            $frequency = $this->mapBillingCycleToFrequency($service->billing_cycle);

            $recurring = Recurring::create([
                'company_id' => $service->company_id,
                'client_id' => $service->client_id,
                'description' => "Service: {$service->name}",
                'amount' => $service->monthly_cost,
                'frequency' => $frequency,
                'start_date' => $service->start_date ?? now(),
                'end_date' => $service->end_date,
                'next_bill_date' => $this->calculateNextBillDate($service),
                'status' => 'active',
                'auto_charge' => false, // Requires manual review
            ]);

            $service->update([
                'recurring_billing_id' => $recurring->id,
            ]);

            Log::info('Recurring billing created for service', [
                'service_id' => $service->id,
                'recurring_id' => $recurring->id,
                'amount' => $service->monthly_cost,
                'frequency' => $frequency,
            ]);

            return $recurring;
        });
    }

    /**
     * Generate an invoice for a service for a specific period
     */
    public function generateServiceInvoice(
        ClientService $service,
        Carbon $periodStart,
        Carbon $periodEnd
    ): Invoice {
        return DB::transaction(function () use ($service, $periodStart, $periodEnd) {
            // Calculate prorated amount if needed
            $amount = $this->calculateProration($service, $periodStart, $periodEnd);

            $invoice = Invoice::create([
                'company_id' => $service->company_id,
                'client_id' => $service->client_id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'draft',
                'subtotal' => $amount,
                'total' => $amount,
                'currency' => $service->currency ?? 'USD',
                'notes' => "Service: {$service->name}\nPeriod: {$periodStart->toDateString()} to {$periodEnd->toDateString()}",
            ]);

            // Add invoice items
            $invoice->items()->create([
                'description' => $service->name,
                'quantity' => 1,
                'unit_price' => $amount,
                'total' => $amount,
            ]);

            // Update service revenue tracking
            $service->update([
                'actual_monthly_revenue' => $service->actual_monthly_revenue + $amount,
            ]);

            Log::info('Service invoice generated', [
                'service_id' => $service->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'period' => "{$periodStart->toDateString()} to {$periodEnd->toDateString()}",
            ]);

            return $invoice;
        });
    }

    /**
     * Calculate prorated amount for partial billing period
     */
    public function calculateProration(
        ClientService $service,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        $fullMonthCost = $service->monthly_cost;

        // Calculate based on billing cycle
        switch ($service->billing_cycle) {
            case 'weekly':
                $daysInPeriod = 7;
                break;
            case 'monthly':
                $daysInPeriod = 30;
                break;
            case 'quarterly':
                $daysInPeriod = 90;
                break;
            case 'semi-annually':
                $daysInPeriod = 180;
                break;
            case 'annually':
                $daysInPeriod = 365;
                break;
            default:
                $daysInPeriod = 30;
        }

        $actualDays = $startDate->diffInDays($endDate) + 1;

        if ($actualDays >= $daysInPeriod) {
            return $fullMonthCost;
        }

        $proratedAmount = ($fullMonthCost / $daysInPeriod) * $actualDays;

        Log::debug('Proration calculated', [
            'service_id' => $service->id,
            'full_amount' => $fullMonthCost,
            'days_in_period' => $daysInPeriod,
            'actual_days' => $actualDays,
            'prorated_amount' => $proratedAmount,
        ]);

        return round($proratedAmount, 2);
    }

    /**
     * Apply setup fees to an invoice
     */
    public function applySetupFees(ClientService $service, Invoice $invoice): void
    {
        if ($service->setup_cost > 0) {
            DB::transaction(function () use ($service, $invoice) {
                $invoice->items()->create([
                    'description' => "Setup Fee: {$service->name}",
                    'quantity' => 1,
                    'unit_price' => $service->setup_cost,
                    'total' => $service->setup_cost,
                ]);

                $invoice->update([
                    'subtotal' => $invoice->subtotal + $service->setup_cost,
                    'total' => $invoice->total + $service->setup_cost,
                ]);

                Log::info('Setup fees applied to invoice', [
                    'service_id' => $service->id,
                    'invoice_id' => $invoice->id,
                    'setup_cost' => $service->setup_cost,
                ]);
            });
        }
    }

    /**
     * Calculate cancellation fee based on service terms
     */
    public function calculateCancellationFee(
        ClientService $service,
        Carbon $cancellationDate
    ): float {
        // Check if service has minimum commitment
        $startDate = $service->start_date ?? $service->created_at;
        $monthsActive = $startDate->diffInMonths($cancellationDate);

        // If cancelled before end date, calculate penalty
        if ($service->end_date && $cancellationDate->lt($service->end_date)) {
            $remainingMonths = $cancellationDate->diffInMonths($service->end_date);
            
            // Charge 50% of remaining contract value as penalty
            $fee = ($service->monthly_cost * $remainingMonths) * 0.5;

            Log::info('Cancellation fee calculated', [
                'service_id' => $service->id,
                'months_active' => $monthsActive,
                'remaining_months' => $remainingMonths,
                'cancellation_fee' => $fee,
            ]);

            return round($fee, 2);
        }

        return 0;
    }

    /**
     * Suspend billing for a service
     */
    public function suspendBilling(ClientService $service): void
    {
        if ($service->hasRecurringBilling()) {
            DB::transaction(function () use ($service) {
                $service->recurringBilling->update([
                    'status' => 'paused',
                ]);

                Log::info('Billing suspended for service', [
                    'service_id' => $service->id,
                    'recurring_id' => $service->recurring_billing_id,
                ]);
            });
        }
    }

    /**
     * Resume billing for a service
     */
    public function resumeBilling(ClientService $service): void
    {
        if ($service->hasRecurringBilling()) {
            DB::transaction(function () use ($service) {
                $service->recurringBilling->update([
                    'status' => 'active',
                    'next_bill_date' => $this->calculateNextBillDate($service),
                ]);

                Log::info('Billing resumed for service', [
                    'service_id' => $service->id,
                    'recurring_id' => $service->recurring_billing_id,
                ]);
            });
        }
    }

    /**
     * Calculate total contract value
     */
    public function calculateTotalContractValue(ClientService $service): float
    {
        if (!$service->start_date || !$service->end_date) {
            return 0;
        }

        $months = $service->start_date->diffInMonths($service->end_date);
        $totalValue = ($service->monthly_cost * $months) + ($service->setup_cost ?? 0);

        return round($totalValue, 2);
    }

    /**
     * Get revenue projection for a service
     */
    public function getRevenueProjection(ClientService $service, int $months = 12): array
    {
        $projection = [
            'months' => $months,
            'monthly_cost' => $service->monthly_cost,
            'total_projected' => $service->monthly_cost * $months,
            'breakdown' => [],
        ];

        $currentDate = now();
        for ($i = 0; $i < $months; $i++) {
            $month = $currentDate->copy()->addMonths($i);
            $projection['breakdown'][] = [
                'month' => $month->format('Y-m'),
                'amount' => $service->monthly_cost,
            ];
        }

        return $projection;
    }

    /**
     * Map billing cycle to recurring frequency
     */
    private function mapBillingCycleToFrequency(string $billingCycle): string
    {
        return match($billingCycle) {
            'weekly' => 'weekly',
            'monthly' => 'monthly',
            'quarterly' => 'quarterly',
            'semi-annually' => 'semiannually',
            'annually' => 'yearly',
            'one-time' => 'one-time',
            default => 'monthly',
        };
    }

    /**
     * Calculate next bill date based on service configuration
     */
    private function calculateNextBillDate(ClientService $service): Carbon
    {
        $startDate = $service->start_date ?? now();

        return match($service->billing_cycle) {
            'weekly' => $startDate->copy()->addWeek(),
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'semi-annually' => $startDate->copy()->addMonths(6),
            'annually' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };
    }
}
