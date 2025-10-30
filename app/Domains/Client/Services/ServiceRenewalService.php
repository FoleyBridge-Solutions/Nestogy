<?php

namespace App\Domains\Client\Services;

use App\Domains\Client\Events\ServiceDueForRenewal;
use App\Domains\Client\Models\ClientService;
use App\Domains\Financial\Models\Quote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service Renewal Service
 * 
 * Manages the service renewal process:
 * - Auto-renewal processing
 * - Renewal eligibility checking
 * - Renewal pricing calculations
 * - Renewal notifications
 * - Grace period management
 */
class ServiceRenewalService
{
    /**
     * Process auto-renewals for eligible services
     */
    public function processAutoRenewals(): array
    {
        $results = [
            'processed' => [],
            'failed' => [],
            'skipped' => [],
        ];

        // Get services due for renewal with auto_renewal enabled
        $services = ClientService::where('status', 'active')
            ->where('auto_renewal', true)
            ->whereNotNull('renewal_date')
            ->where('renewal_date', '<=', now())
            ->with(['client'])
            ->get();

        Log::info('Processing auto-renewals', [
            'count' => $services->count(),
        ]);

        foreach ($services as $service) {
            try {
                if ($this->checkRenewalEligibility($service)) {
                    $renewed = $this->processRenewal($service);
                    $results['processed'][] = [
                        'service_id' => $service->id,
                        'client_name' => $service->client->name,
                        'new_renewal_date' => $renewed->renewal_date,
                    ];
                } else {
                    $results['skipped'][] = [
                        'service_id' => $service->id,
                        'reason' => 'Not eligible for renewal',
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Auto-renewal failed', [
                    'service_id' => $service->id,
                    'error' => $e->getMessage(),
                ]);
                $results['failed'][] = [
                    'service_id' => $service->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Auto-renewal processing complete', [
            'processed' => count($results['processed']),
            'failed' => count($results['failed']),
            'skipped' => count($results['skipped']),
        ]);

        return $results;
    }

    /**
     * Check if a service is eligible for renewal
     */
    public function checkRenewalEligibility(ClientService $service): bool
    {
        // Service must be active
        if ($service->status !== 'active') {
            return false;
        }

        // Must have a renewal date
        if (!$service->renewal_date) {
            return false;
        }

        // Client must not have outstanding balance above threshold
        // TODO: Check client credit status

        // Service must not have recent SLA breaches
        if ($service->sla_breaches_count > 5) {
            Log::warning('Service renewal blocked due to SLA breaches', [
                'service_id' => $service->id,
                'sla_breaches_count' => $service->sla_breaches_count,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Calculate renewal pricing (may include price increases)
     */
    public function calculateRenewalPrice(ClientService $service): float
    {
        $currentPrice = $service->monthly_cost;

        // Check if product template has updated pricing
        if ($service->product) {
            $templatePrice = $service->product->base_price;
            
            // If template price increased, apply increase
            if ($templatePrice > $currentPrice) {
                Log::info('Service renewal price increase', [
                    'service_id' => $service->id,
                    'old_price' => $currentPrice,
                    'new_price' => $templatePrice,
                ]);
                return $templatePrice;
            }
        }

        // Otherwise keep current price
        return $currentPrice;
    }

    /**
     * Send renewal notification to client
     */
    public function sendRenewalNotification(ClientService $service, int $daysBeforeExpiry): void
    {
        Log::info('Sending renewal notification', [
            'service_id' => $service->id,
            'client_id' => $service->client_id,
            'days_before_expiry' => $daysBeforeExpiry,
            'renewal_date' => $service->renewal_date?->toDateString(),
        ]);

        // TODO: Integrate with notification system
        // - Email client about upcoming renewal
        // - Include renewal pricing
        // - Provide option to cancel or modify
        // - Include account manager contact info
    }

    /**
     * Create a renewal quote for client approval
     */
    public function createRenewalQuote(ClientService $service): Quote
    {
        return DB::transaction(function () use ($service) {
            $renewalPrice = $this->calculateRenewalPrice($service);
            $renewalMonths = 12; // Default to 12-month renewal

            $quote = Quote::create([
                'company_id' => $service->company_id,
                'client_id' => $service->client_id,
                'title' => "Service Renewal: {$service->name}",
                'description' => "Renewal quote for {$renewalMonths} months",
                'quote_date' => now(),
                'expiry_date' => now()->addDays(30),
                'status' => 'draft',
                'subtotal' => $renewalPrice * $renewalMonths,
                'total' => $renewalPrice * $renewalMonths,
                'currency' => $service->currency ?? 'USD',
            ]);

            // Add quote items
            $quote->items()->create([
                'description' => "{$service->name} - {$renewalMonths} month renewal",
                'quantity' => $renewalMonths,
                'unit_price' => $renewalPrice,
                'total' => $renewalPrice * $renewalMonths,
            ]);

            Log::info('Renewal quote created', [
                'service_id' => $service->id,
                'quote_id' => $quote->id,
                'amount' => $renewalPrice * $renewalMonths,
            ]);

            return $quote;
        });
    }

    /**
     * Approve and process a service renewal
     */
    public function approveRenewal(ClientService $service, ?float $newPrice = null): ClientService
    {
        return DB::transaction(function () use ($service, $newPrice) {
            $renewalMonths = 12; // Default renewal period
            $currentRenewalDate = $service->renewal_date ?? $service->end_date ?? now();
            $newRenewalDate = $currentRenewalDate->copy()->addMonths($renewalMonths);

            $updateData = [
                'renewal_date' => $newRenewalDate,
                'end_date' => $newRenewalDate,
                'renewal_count' => ($service->renewal_count ?? 0) + 1,
                'last_renewed_at' => now(),
            ];

            // Apply new pricing if provided
            if ($newPrice !== null) {
                $updateData['monthly_cost'] = $newPrice;
            }

            $service->update($updateData);

            Log::info('Service renewal approved', [
                'service_id' => $service->id,
                'new_renewal_date' => $newRenewalDate->toDateString(),
                'new_price' => $newPrice,
                'renewal_count' => $service->renewal_count,
            ]);

            // TODO: Dispatch ServiceRenewed event

            return $service->fresh();
        });
    }

    /**
     * Deny/cancel a service renewal
     */
    public function denyRenewal(ClientService $service, string $reason): void
    {
        DB::transaction(function () use ($service, $reason) {
            $service->update([
                'auto_renewal' => false,
                'notes' => ($service->notes ?? '') . "\n\nRenewal denied: {$reason}",
            ]);

            Log::warning('Service renewal denied', [
                'service_id' => $service->id,
                'client_id' => $service->client_id,
                'reason' => $reason,
            ]);

            // TODO: Send notification to account manager
        });
    }

    /**
     * Get services currently in grace period
     */
    public function getServicesInGracePeriod(): Collection
    {
        return ClientService::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->where('end_date', '>', now()->subDays(30)) // 30-day grace period
            ->with(['client', 'technician'])
            ->get();
    }

    /**
     * Extend grace period for a service
     */
    public function extendGracePeriod(ClientService $service, int $days): void
    {
        DB::transaction(function () use ($service, $days) {
            $newEndDate = ($service->end_date ?? now())->copy()->addDays($days);

            $service->update([
                'end_date' => $newEndDate,
                'renewal_date' => $newEndDate,
                'notes' => ($service->notes ?? '') . "\n\nGrace period extended by {$days} days",
            ]);

            Log::info('Grace period extended', [
                'service_id' => $service->id,
                'days' => $days,
                'new_end_date' => $newEndDate->toDateString(),
            ]);
        });
    }

    /**
     * Send renewal reminders for services expiring soon
     */
    public function sendRenewalReminders(): array
    {
        $results = [
            '30_days' => 0,
            '14_days' => 0,
            '7_days' => 0,
        ];

        // 30 days before expiry
        $services30 = ClientService::where('status', 'active')
            ->whereNotNull('renewal_date')
            ->whereBetween('renewal_date', [now()->addDays(29), now()->addDays(31)])
            ->get();

        foreach ($services30 as $service) {
            $this->sendRenewalNotification($service, 30);
            event(new ServiceDueForRenewal($service, 30));
            $results['30_days']++;
        }

        // 14 days before expiry
        $services14 = ClientService::where('status', 'active')
            ->whereNotNull('renewal_date')
            ->whereBetween('renewal_date', [now()->addDays(13), now()->addDays(15)])
            ->get();

        foreach ($services14 as $service) {
            $this->sendRenewalNotification($service, 14);
            event(new ServiceDueForRenewal($service, 14));
            $results['14_days']++;
        }

        // 7 days before expiry
        $services7 = ClientService::where('status', 'active')
            ->whereNotNull('renewal_date')
            ->whereBetween('renewal_date', [now()->addDays(6), now()->addDays(8)])
            ->get();

        foreach ($services7 as $service) {
            $this->sendRenewalNotification($service, 7);
            event(new ServiceDueForRenewal($service, 7));
            $results['7_days']++;
        }

        Log::info('Renewal reminders sent', $results);

        return $results;
    }

    /**
     * Process a renewal internally (used by auto-renewal)
     */
    private function processRenewal(ClientService $service): ClientService
    {
        $newPrice = $this->calculateRenewalPrice($service);
        return $this->approveRenewal($service, $newPrice);
    }
}
