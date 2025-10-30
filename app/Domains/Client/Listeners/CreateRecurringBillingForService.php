<?php

namespace App\Domains\Client\Listeners;

use App\Domains\Client\Events\ServiceActivated;
use App\Domains\Client\Services\ServiceBillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Creates recurring billing when a service is activated
 */
class CreateRecurringBillingForService implements ShouldQueue
{
    public function __construct(
        private ServiceBillingService $billing
    ) {}

    public function handle(ServiceActivated $event): void
    {
        $service = $event->service;

        // Only create billing if service has a monthly cost
        if ($service->monthly_cost <= 0) {
            Log::info('Skipping recurring billing creation - no monthly cost', [
                'service_id' => $service->id,
            ]);
            return;
        }

        // Skip if billing already exists
        if ($service->hasRecurringBilling()) {
            Log::info('Skipping recurring billing creation - already exists', [
                'service_id' => $service->id,
                'recurring_billing_id' => $service->recurring_billing_id,
            ]);
            return;
        }

        try {
            $recurring = $this->billing->createRecurringBilling($service);
            
            Log::info('Recurring billing created via event', [
                'service_id' => $service->id,
                'recurring_id' => $recurring?->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create recurring billing from event', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
            
            // Don't throw - we don't want to fail the entire activation
        }
    }
}
