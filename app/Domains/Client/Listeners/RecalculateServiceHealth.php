<?php

namespace App\Domains\Client\Listeners;

use App\Domains\Client\Events\ServiceSLABreached;
use App\Domains\Client\Services\ServiceMonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Recalculates service health when SLA is breached
 */
class RecalculateServiceHealth implements ShouldQueue
{
    public function __construct(
        private ServiceMonitoringService $monitoring
    ) {}

    public function handle(ServiceSLABreached $event): void
    {
        $service = $event->service;

        try {
            $previousScore = $service->health_score;
            $newScore = $this->monitoring->calculateHealthScore($service);

            Log::info('Service health recalculated after SLA breach', [
                'service_id' => $service->id,
                'previous_score' => $previousScore,
                'new_score' => $newScore,
            ]);

            // If health has degraded significantly, fire another event
            if ($previousScore && $newScore < $previousScore - 10) {
                // TODO: Dispatch ServiceHealthDegraded event
            }
        } catch (\Exception $e) {
            Log::error('Failed to recalculate service health', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
