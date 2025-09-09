<?php

namespace App\Jobs;

use App\Domains\Integration\Models\RMMAlert;
use App\Domains\Integration\Services\RMMIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process RMM Alert Job
 * 
 * Asynchronously processes RMM alerts, creating tickets and handling notifications.
 * Implements retry logic and error handling for reliable alert processing.
 */
class ProcessRMMAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RMMAlert $alert;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job should run.
     */
    public int $timeout = 120;

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
    public function __construct(RMMAlert $alert)
    {
        $this->alert = $alert;
        $this->queue = 'rmm-alerts';
    }

    /**
     * Execute the job.
     */
    public function handle(RMMIntegrationService $rmmService): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Processing RMM alert', [
                'alert_id' => $this->alert->id,
                'integration_id' => $this->alert->integration_id,
                'external_alert_id' => $this->alert->external_alert_id,
                'severity' => $this->alert->severity,
                'attempt' => $this->attempts(),
            ]);

            // Skip if already processed
            if ($this->alert->isProcessed()) {
                Log::info('RMM alert already processed, skipping', [
                    'alert_id' => $this->alert->id,
                ]);
                return;
            }

            // Skip duplicates
            if ($this->alert->isDuplicate()) {
                $this->alert->markProcessed();
                Log::info('RMM alert marked as duplicate, skipping ticket creation', [
                    'alert_id' => $this->alert->id,
                ]);
                return;
            }

            // Convert alert to ticket
            $ticket = $rmmService->convertAlertToTicket($this->alert);

            if ($ticket) {
                Log::info('Successfully created ticket from RMM alert', [
                    'alert_id' => $this->alert->id,
                    'ticket_id' => $ticket->id,
                    'client_id' => $ticket->client_id,
                ]);

                // Dispatch additional processing jobs
                $this->dispatchFollowUpJobs($ticket);
            } else {
                Log::warning('Failed to create ticket from RMM alert', [
                    'alert_id' => $this->alert->id,
                ]);
            }

            // Mark alert as processed
            $this->alert->markProcessed();

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('RMM alert processing completed', [
                'alert_id' => $this->alert->id,
                'processing_time_ms' => $processingTime,
                'ticket_created' => !is_null($ticket),
            ]);

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('RMM alert processing failed', [
                'alert_id' => $this->alert->id,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'processing_time_ms' => $processingTime,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // If this is the final attempt, mark as processed to prevent endless retries
            if ($this->attempts() >= $this->tries) {
                $this->alert->markProcessed();
                Log::error('RMM alert processing failed permanently after max attempts', [
                    'alert_id' => $this->alert->id,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RMM alert processing job failed permanently', [
            'alert_id' => $this->alert->id,
            'error' => $exception->getMessage(),
        ]);

        // Mark as processed to prevent further attempts
        $this->alert->markProcessed();
    }

    /**
     * Dispatch follow-up jobs for ticket processing.
     */
    protected function dispatchFollowUpJobs($ticket): void
    {
        // Dispatch notification job if enabled
        if ($this->shouldNotifyClient()) {
            NotifyClientOfRMMAlert::dispatch($ticket, $this->alert)
                ->delay(now()->addMinutes(1));
        }

        // Dispatch auto-assignment job if enabled
        if ($this->shouldAutoAssign()) {
            AutoAssignTicket::dispatch($ticket)
                ->delay(now()->addMinutes(2));
        }

        // Dispatch escalation check job for urgent alerts
        if ($this->alert->severity === RMMAlert::SEVERITY_URGENT) {
            CheckTicketEscalation::dispatch($ticket)
                ->delay(now()->addMinutes(15));
        }

        // Update device mapping if needed
        if ($this->alert->device_id) {
            SyncDeviceInventory::dispatch(
                $this->alert->integration,
                $this->alert->device_id
            )->delay(now()->addMinutes(5));
        }
    }

    /**
     * Check if client should be notified.
     */
    protected function shouldNotifyClient(): bool
    {
        $alertRules = $this->alert->integration->alert_rules ?? [];
        return data_get($alertRules, 'notify_client', false);
    }

    /**
     * Check if ticket should be auto-assigned.
     */
    protected function shouldAutoAssign(): bool
    {
        $alertRules = $this->alert->integration->alert_rules ?? [];
        return data_get($alertRules, 'auto_assign_technician', false);
    }

    /**
     * Determine the queue this job should be sent to.
     */
    public function queue(): string
    {
        // Route urgent alerts to high priority queue
        if ($this->alert->severity === RMMAlert::SEVERITY_URGENT) {
            return 'urgent-alerts';
        }

        return 'rmm-alerts';
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'rmm-alert',
            'integration:' . $this->alert->integration_id,
            'severity:' . $this->alert->severity,
            'external_id:' . $this->alert->external_alert_id,
        ];
    }
}