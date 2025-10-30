<?php

namespace App\Domains\Client\Listeners;

use App\Domains\Client\Events\ServiceSuspended;
use App\Domains\Client\Services\ServiceBillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Suspends recurring billing when service is suspended
 */
class SuspendRecurringBilling implements ShouldQueue
{
    public function __construct(
        private ServiceBillingService $billing
    ) {}

    public function handle(ServiceSuspended $event): void
    {
        $service = $event->service;

        if (!$service->hasRecurringBilling()) {
            return;
        }

        try {
            $this->billing->suspendBilling($service);
            
            Log::info('Recurring billing suspended via event', [
                'service_id' => $service->id,
                'reason' => $event->reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to suspend billing from event', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
