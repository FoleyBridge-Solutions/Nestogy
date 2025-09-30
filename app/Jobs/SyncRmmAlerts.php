<?php

namespace App\Jobs;

use App\Domains\Integration\Models\RMMAlert;
use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncRmmAlerts implements ShouldQueue
{
    use Queueable;

    protected RmmIntegration $integration;

    protected array $filters;

    /**
     * Create a new job instance.
     */
    public function __construct(RmmIntegration $integration, array $filters = [])
    {
        $this->integration = $integration;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(RmmServiceFactory $factory): void
    {
        try {
            Log::info('Starting RMM alerts sync', [
                'integration_id' => $this->integration->id,
                'company_id' => $this->integration->company_id,
                'filters' => $this->filters,
            ]);

            // Create RMM service instance
            $service = $factory->make($this->integration);

            // Sync alerts
            $result = $service->syncAlerts($this->filters);

            if (! $result['success']) {
                throw new \Exception('Failed to sync alerts: '.($result['error'] ?? 'Unknown error'));
            }

            $alerts = $result['alerts'];
            $processedCount = 0;
            $createdCount = 0;
            $ticketsCreated = 0;

            Log::info('Processing alerts from RMM', [
                'total_alerts' => count($alerts),
                'integration_id' => $this->integration->id,
            ]);

            foreach ($alerts as $alertData) {
                try {
                    $result = $this->processAlert($alertData);
                    $processedCount++;

                    if ($result['created']) {
                        $createdCount++;
                    }

                    if ($result['ticket_created']) {
                        $ticketsCreated++;
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to process alert', [
                        'alert_id' => $alertData['id'] ?? 'unknown',
                        'message' => $alertData['message'] ?? 'unknown',
                        'error' => $e->getMessage(),
                        'integration_id' => $this->integration->id,
                    ]);
                }
            }

            Log::info('RMM alerts sync completed', [
                'integration_id' => $this->integration->id,
                'total_processed' => $processedCount,
                'alerts_created' => $createdCount,
                'tickets_created' => $ticketsCreated,
            ]);

        } catch (\Exception $e) {
            Log::error('RMM alerts sync failed', [
                'integration_id' => $this->integration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single alert and optionally create a ticket.
     */
    protected function processAlert(array $alertData): array
    {
        $created = false;
        $ticketCreated = false;

        // Check if alert already exists
        $existingAlert = RMMAlert::where([
            'integration_id' => $this->integration->id,
            'external_alert_id' => $alertData['id'],
        ])->first();

        if ($existingAlert) {
            // Update existing alert if needed
            if (! $existingAlert->resolved && $alertData['resolved']) {
                $existingAlert->update([
                    'processed_at' => now(),
                    'raw_payload' => array_merge($existingAlert->raw_payload, ['updated_data' => $alertData]),
                ]);
            }

            return ['created' => false, 'ticket_created' => false];
        }

        // Create new RMM alert
        $alert = RMMAlert::create([
            'integration_id' => $this->integration->id,
            'external_alert_id' => $alertData['id'],
            'device_id' => $alertData['agent_id'],
            'alert_type' => $alertData['alert_type'],
            'severity' => $alertData['severity'],
            'message' => $alertData['message'],
            'raw_payload' => $alertData,
            'is_duplicate' => false,
        ]);

        $created = true;

        Log::debug('Created RMM alert', [
            'alert_id' => $alert->id,
            'external_id' => $alertData['id'],
            'severity' => $alertData['severity'],
            'integration_id' => $this->integration->id,
        ]);

        // Check if we should auto-create tickets
        $settings = $this->integration->settings ?? [];
        $autoCreateTickets = $settings['auto_create_tickets'] ?? true;

        if ($autoCreateTickets && ! $alertData['resolved'] && $this->shouldCreateTicket($alertData)) {
            $ticket = $this->createTicketFromAlert($alert, $alertData);
            if ($ticket) {
                $alert->update(['ticket_id' => $ticket->id]);
                $ticketCreated = true;

                Log::info('Created ticket from RMM alert', [
                    'alert_id' => $alert->id,
                    'ticket_id' => $ticket->id,
                    'integration_id' => $this->integration->id,
                ]);
            }
        }

        // Mark alert as processed
        $alert->markProcessed();

        return ['created' => $created, 'ticket_created' => $ticketCreated];
    }

    /**
     * Determine if a ticket should be created for this alert.
     */
    protected function shouldCreateTicket(array $alertData): bool
    {
        $settings = $this->integration->settings ?? [];

        // Check severity threshold
        $minSeverity = $settings['min_ticket_severity'] ?? 'normal';
        $severityLevels = ['low' => 1, 'normal' => 2, 'high' => 3, 'urgent' => 4];

        $alertSeverityLevel = $severityLevels[$alertData['severity']] ?? 2;
        $minSeverityLevel = $severityLevels[$minSeverity] ?? 2;

        if ($alertSeverityLevel < $minSeverityLevel) {
            return false;
        }

        // Check excluded alert types
        $excludedTypes = $settings['excluded_alert_types'] ?? [];
        if (in_array($alertData['alert_type'], $excludedTypes)) {
            return false;
        }

        // Check if agent is in maintenance mode (if available)
        if (isset($alertData['maintenance_mode']) && $alertData['maintenance_mode']) {
            return false;
        }

        return true;
    }

    /**
     * Create a ticket from an RMM alert.
     */
    protected function createTicketFromAlert(RMMAlert $alert, array $alertData): ?Ticket
    {
        try {
            // Resolve client
            $client = $this->resolveClientFromAlert($alertData);
            if (! $client) {
                Log::warning('Could not resolve client for alert ticket creation', [
                    'alert_id' => $alert->id,
                    'client_name' => $alertData['client'] ?? 'unknown',
                ]);

                return null;
            }

            // Generate ticket data
            $ticketData = [
                'company_id' => $this->integration->company_id,
                'client_id' => $client->id,
                'subject' => $this->generateTicketSubject($alertData),
                'description' => $this->generateTicketDescription($alertData),
                'priority' => $this->mapSeverityToPriority($alertData['severity']),
                'status' => 'new',
                'created_by' => 1, // System user
                'source' => 'RMM Integration',
                'category' => 'Infrastructure',
                'tags' => ['rmm', 'automated', $this->integration->rmm_type],
                'custom_fields' => [
                    'rmm_alert_id' => $alert->id,
                    'rmm_external_id' => $alertData['id'],
                    'rmm_agent_hostname' => $alertData['agent_hostname'] ?? 'Unknown',
                    'rmm_alert_type' => $alertData['alert_type'],
                    'rmm_integration_id' => $this->integration->id,
                ],
            ];

            return Ticket::create($ticketData);

        } catch (\Exception $e) {
            Log::error('Failed to create ticket from RMM alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve client from alert data.
     */
    protected function resolveClientFromAlert(array $alertData): ?Client
    {
        $clientName = $alertData['client'] ?? null;
        if (! $clientName) {
            return null;
        }

        return Client::where('company_id', $this->integration->company_id)
            ->where(function ($query) use ($clientName) {
                $query->where('name', $clientName)
                    ->orWhere('company_name', $clientName);
            })
            ->first();
    }

    /**
     * Generate ticket subject from alert data.
     */
    protected function generateTicketSubject(array $alertData): string
    {
        $hostname = $alertData['agent_hostname'] ?? $alertData['hostname'] ?? 'Unknown';
        $alertType = $alertData['alert_type'] ?? 'System Alert';
        $severity = strtoupper($alertData['severity'] ?? 'NORMAL');

        return "[{$severity}] {$alertType} on {$hostname}";
    }

    /**
     * Generate ticket description from alert data.
     */
    protected function generateTicketDescription(array $alertData): string
    {
        $description = "**RMM Alert Details**\n\n";
        $description .= '**Device:** '.($alertData['agent_hostname'] ?? 'Unknown')."\n";
        $description .= '**Client:** '.($alertData['client'] ?? 'Unknown')."\n";
        $description .= '**Site:** '.($alertData['site'] ?? 'Unknown')."\n";
        $description .= '**Alert Type:** '.($alertData['alert_type'] ?? 'System Alert')."\n";
        $description .= '**Severity:** '.strtoupper($alertData['severity'] ?? 'Normal')."\n";
        $description .= '**Message:** '.($alertData['message'] ?? 'No message provided')."\n";
        $description .= '**Created:** '.($alertData['created_time'] ?? 'Unknown')."\n\n";
        $description .= '**Integration:** '.$this->integration->name."\n";
        $description .= '**RMM System:** '.$this->integration->getRmmTypeLabel()."\n\n";
        $description .= '*This ticket was automatically created from an RMM system alert.*';

        return $description;
    }

    /**
     * Map alert severity to ticket priority.
     */
    protected function mapSeverityToPriority(string $severity): string
    {
        $mapping = [
            'urgent' => 'Critical',
            'high' => 'High',
            'normal' => 'Medium',
            'low' => 'Low',
        ];

        return $mapping[$severity] ?? 'Medium';
    }
}
