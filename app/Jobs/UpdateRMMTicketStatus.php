<?php

namespace App\Jobs;

use App\Domains\Integration\Models\Integration;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

/**
 * Update RMM Ticket Status Job
 * 
 * Handles bidirectional sync of ticket status updates between
 * Nestogy and RMM systems. Updates RMM when tickets are resolved.
 */
class UpdateRMMTicketStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Ticket $ticket;
    protected string $newStatus;
    protected ?string $resolution;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job should run.
     */
    public int $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // 30s, 1m, 2m
    }

    /**
     * Create a new job instance.
     */
    public function __construct(Ticket $ticket, string $newStatus, ?string $resolution = null)
    {
        $this->ticket = $ticket;
        $this->newStatus = $newStatus;
        $this->resolution = $resolution;
        $this->queue = 'rmm-updates';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            Log::info('Updating RMM ticket status', [
                'ticket_id' => $this->ticket->id,
                'new_status' => $this->newStatus,
                'has_resolution' => !is_null($this->resolution),
                'attempt' => $this->attempts(),
            ]);

            // Find related RMM alert
            $rmmAlert = $this->ticket->rmmAlerts()->first();
            if (!$rmmAlert) {
                Log::info('No RMM alert found for ticket, skipping RMM update', [
                    'ticket_id' => $this->ticket->id,
                ]);
                return;
            }

            $integration = $rmmAlert->integration;
            if (!$integration->isActive()) {
                Log::warning('Integration inactive, skipping RMM update', [
                    'ticket_id' => $this->ticket->id,
                    'integration_id' => $integration->id,
                ]);
                return;
            }

            // Update RMM system based on provider
            $updated = $this->updateRMMSystem($integration, $rmmAlert, $this->newStatus, $this->resolution);

            if ($updated) {
                Log::info('RMM ticket status updated successfully', [
                    'ticket_id' => $this->ticket->id,
                    'integration_id' => $integration->id,
                    'external_alert_id' => $rmmAlert->external_alert_id,
                ]);
            }

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('RMM ticket status update completed', [
                'ticket_id' => $this->ticket->id,
                'processing_time_ms' => $processingTime,
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('RMM ticket status update failed', [
                'ticket_id' => $this->ticket->id,
                'new_status' => $this->newStatus,
                'attempt' => $this->attempts(),
                'processing_time_ms' => $processingTime,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update RMM system based on provider.
     */
    protected function updateRMMSystem(Integration $integration, $rmmAlert, string $status, ?string $resolution = null): bool
    {
        try {
            switch ($integration->provider) {
                case Integration::PROVIDER_CONNECTWISE:
                    return $this->updateConnectWise($integration, $rmmAlert, $status, $resolution);
                case Integration::PROVIDER_DATTO:
                    return $this->updateDatto($integration, $rmmAlert, $status, $resolution);
                case Integration::PROVIDER_NINJA:
                    return $this->updateNinja($integration, $rmmAlert, $status, $resolution);
                default:
                    Log::info('RMM status updates not supported for provider', [
                        'provider' => $integration->provider,
                    ]);
                    return false;
            }
        } catch (RequestException $e) {
            Log::error('API request failed during RMM status update', [
                'integration_id' => $integration->id,
                'external_alert_id' => $rmmAlert->external_alert_id,
                'status_code' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update ConnectWise Automate alert status.
     */
    protected function updateConnectWise(Integration $integration, $rmmAlert, string $status, ?string $resolution = null): bool
    {
        $credentials = $integration->getCredentials();
        $endpoint = $integration->api_endpoint;
        
        if (!$endpoint || !isset($credentials['api_key'])) {
            Log::warning('ConnectWise credentials not configured for status update');
            return false;
        }

        $statusMapping = [
            'Closed' => 'Resolved',
            'Resolved' => 'Resolved',
            'Cancelled' => 'Dismissed',
        ];

        $rmmStatus = $statusMapping[$status] ?? 'Active';
        
        $payload = [
            'Status' => $rmmStatus,
            'ResolvedBy' => auth()->user()?->name ?? 'Nestogy System',
            'ResolvedDate' => now()->toISOString(),
        ];

        if ($resolution) {
            $payload['Resolution'] = $resolution;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $credentials['api_key'],
            'Content-Type' => 'application/json',
        ])->timeout(30)->put(
            "{$endpoint}/alerts/{$rmmAlert->external_alert_id}",
            $payload
        );

        return $response->successful();
    }

    /**
     * Update Datto RMM alert status.
     */
    protected function updateDatto(Integration $integration, $rmmAlert, string $status, ?string $resolution = null): bool
    {
        $credentials = $integration->getCredentials();
        $endpoint = $integration->api_endpoint;
        
        if (!$endpoint || !isset($credentials['api_key'])) {
            Log::warning('Datto credentials not configured for status update');
            return false;
        }

        $statusMapping = [
            'Closed' => 'resolved',
            'Resolved' => 'resolved',
            'Cancelled' => 'dismissed',
        ];

        $rmmStatus = $statusMapping[$status] ?? 'open';
        
        $payload = [
            'status' => $rmmStatus,
            'resolved_by' => auth()->user()?->name ?? 'Nestogy System',
            'resolved_at' => now()->toISOString(),
        ];

        if ($resolution) {
            $payload['resolution_notes'] = $resolution;
        }

        $response = Http::withHeaders([
            'X-API-Key' => $credentials['api_key'],
            'Content-Type' => 'application/json',
        ])->timeout(30)->patch(
            "{$endpoint}/alert/{$rmmAlert->external_alert_id}",
            $payload
        );

        return $response->successful();
    }

    /**
     * Update NinjaOne alert status.
     */
    protected function updateNinja(Integration $integration, $rmmAlert, string $status, ?string $resolution = null): bool
    {
        $credentials = $integration->getCredentials();
        $endpoint = $integration->api_endpoint;
        
        if (!$endpoint || !isset($credentials['bearer_token'])) {
            Log::warning('NinjaOne credentials not configured for status update');
            return false;
        }

        $statusMapping = [
            'Closed' => 'RESOLVED',
            'Resolved' => 'RESOLVED',
            'Cancelled' => 'DISMISSED',
        ];

        $rmmStatus = $statusMapping[$status] ?? 'ACTIVE';
        
        $payload = [
            'status' => $rmmStatus,
            'resolvedBy' => auth()->user()?->name ?? 'Nestogy System',
            'resolvedAt' => now()->toISOString(),
        ];

        if ($resolution) {
            $payload['resolutionNotes'] = $resolution;
        }

        $response = Http::withToken($credentials['bearer_token'])
            ->timeout(30)
            ->patch(
                "{$endpoint}/v2/alerts/{$rmmAlert->external_alert_id}",
                $payload
            );

        return $response->successful();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RMM ticket status update job failed permanently', [
            'ticket_id' => $this->ticket->id,
            'new_status' => $this->newStatus,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'rmm-update',
            'ticket:' . $this->ticket->id,
            'status:' . $this->newStatus,
        ];
    }
}