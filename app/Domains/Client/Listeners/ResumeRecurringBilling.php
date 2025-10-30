<?php

namespace App\Domains\Client\Listeners;

use App\Domains\Client\Events\ServiceResumed;
use App\Domains\Client\Services\ServiceBillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Resumes recurring billing when service is resumed
 */
class ResumeRecurringBilling implements ShouldQueue
{
    public function __construct(
        private ServiceBillingService $billing
    ) {}

    public function handle(ServiceResumed $event): void
    {
        $service = $event->service;

        if (!$service->hasRecurringBilling()) {
            return;
        }

        try {
            $this->billing->resumeBilling($service);
            
            Log::info('Recurring billing resumed via event', [
                'service_id' => $service->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to resume billing from event', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
