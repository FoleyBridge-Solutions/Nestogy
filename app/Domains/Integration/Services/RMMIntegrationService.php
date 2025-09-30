<?php

namespace App\Domains\Integration\Services;

use App\Domains\Integration\Models\DeviceMapping;
use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Models\RMMAlert;
use App\Domains\Ticket\Models\Ticket;
use App\Jobs\ProcessRMMAlert;
use Illuminate\Support\Facades\Log;

/**
 * RMM Integration Service
 *
 * Core service for processing RMM webhooks and managing integrations.
 * Handles payload standardization, alert processing, and device mapping.
 */
class RMMIntegrationService
{
    /**
     * Process incoming RMM webhook payload.
     */
    public function processWebhookPayload(Integration $integration, array $payload): array
    {
        try {
            Log::info('Processing RMM webhook payload', [
                'integration_id' => $integration->id,
                'provider' => $integration->provider,
                'payload_size' => count($payload),
            ]);

            // Standardize payload format
            $standardizedPayload = $this->standardizePayload($integration, $payload);

            // Create or update device mapping
            $deviceMapping = $this->handleDeviceMapping($integration, $standardizedPayload);

            // Create RMM alert record
            $alert = $this->createAlert($integration, $standardizedPayload, $deviceMapping);

            // Check for duplicates
            if ($this->isDuplicateAlert($alert)) {
                $alert->markDuplicate();
                Log::info('Duplicate alert detected', ['alert_id' => $alert->id]);

                return ['status' => 'duplicate', 'alert_id' => $alert->id];
            }

            // Dispatch processing job
            ProcessRMMAlert::dispatch($alert);

            // Update integration last sync
            $integration->updateLastSync();

            Log::info('RMM webhook processed successfully', [
                'integration_id' => $integration->id,
                'alert_id' => $alert->id,
                'device_mapping_id' => $deviceMapping?->id,
            ]);

            return [
                'status' => 'processed',
                'alert_id' => $alert->id,
                'device_mapping_id' => $deviceMapping?->id,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process RMM webhook payload', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw $e;
        }
    }

    /**
     * Standardize webhook payload format across different RMM providers.
     */
    public function standardizePayload(Integration $integration, array $payload): array
    {
        $fieldMappings = $integration->field_mappings ?: Integration::getDefaultFieldMappings($integration->provider);

        $standardized = [
            'device_id' => data_get($payload, $fieldMappings['device_id']),
            'device_name' => data_get($payload, $fieldMappings['device_name']),
            'client_id' => data_get($payload, $fieldMappings['client_id']),
            'alert_id' => data_get($payload, $fieldMappings['alert_id']),
            'message' => data_get($payload, $fieldMappings['message']),
            'severity' => data_get($payload, $fieldMappings['severity']),
            'timestamp' => data_get($payload, $fieldMappings['timestamp']),
            'alert_type' => $this->determineAlertType($payload, $integration->provider),
            'raw_payload' => $payload,
        ];

        // Normalize severity
        if ($standardized['severity']) {
            $standardized['severity'] = RMMAlert::normalizeSeverity(
                $standardized['severity'],
                $integration->provider
            );
        } else {
            $standardized['severity'] = RMMAlert::SEVERITY_NORMAL;
        }

        // Ensure we have required fields
        $standardized['device_id'] = $standardized['device_id'] ?: 'unknown';
        $standardized['device_name'] = $standardized['device_name'] ?: 'Unknown Device';
        $standardized['alert_id'] = $standardized['alert_id'] ?: uniqid('alert_');
        $standardized['message'] = $standardized['message'] ?: 'RMM Alert';

        return $standardized;
    }

    /**
     * Handle device mapping creation or update.
     */
    public function handleDeviceMapping(Integration $integration, array $standardizedPayload): ?DeviceMapping
    {
        // Skip if we don't have device information
        if (! $standardizedPayload['device_id'] || ! $standardizedPayload['client_id']) {
            return null;
        }

        // Find or resolve client ID
        $clientId = $this->resolveClientId($integration, $standardizedPayload['client_id']);
        if (! $clientId) {
            Log::warning('Could not resolve client ID for device mapping', [
                'integration_id' => $integration->id,
                'rmm_client_id' => $standardizedPayload['client_id'],
            ]);

            return null;
        }

        // Create or update device mapping
        $deviceMapping = DeviceMapping::updateOrCreateMapping(
            $integration->id,
            $standardizedPayload['device_id'],
            $clientId,
            $standardizedPayload['device_name'],
            [
                'last_alert' => now()->toISOString(),
                'provider_data' => $standardizedPayload['raw_payload'],
            ]
        );

        return $deviceMapping;
    }

    /**
     * Create RMM alert record.
     */
    public function createAlert(Integration $integration, array $standardizedPayload, ?DeviceMapping $deviceMapping = null): RMMAlert
    {
        $alert = RMMAlert::create([
            'integration_id' => $integration->id,
            'external_alert_id' => $standardizedPayload['alert_id'],
            'device_id' => $standardizedPayload['device_id'],
            'asset_id' => $deviceMapping?->asset_id,
            'alert_type' => $standardizedPayload['alert_type'],
            'severity' => $standardizedPayload['severity'],
            'message' => $standardizedPayload['message'],
            'raw_payload' => $standardizedPayload['raw_payload'],
        ]);

        return $alert;
    }

    /**
     * Check if alert is a duplicate.
     */
    public function isDuplicateAlert(RMMAlert $alert): bool
    {
        return $alert->hasSimilarRecentAlert(1); // Check last hour
    }

    /**
     * Convert RMM alert to ticket.
     */
    public function convertAlertToTicket(RMMAlert $alert): ?Ticket
    {
        $integration = $alert->integration;
        $alertRules = $integration->alert_rules ?: Integration::getDefaultAlertRules($integration->provider);

        // Check if auto-create tickets is enabled
        if (! data_get($alertRules, 'auto_create_tickets', true)) {
            return null;
        }

        try {
            // Determine client ID
            $clientId = $this->resolveClientIdFromAlert($alert);
            if (! $clientId) {
                Log::warning('Cannot create ticket: client not found', ['alert_id' => $alert->id]);

                return null;
            }

            // Create ticket
            $ticket = Ticket::create([
                'company_id' => $integration->company_id,
                'client_id' => $clientId,
                'title' => $this->generateTicketTitle($alert),
                'description' => $this->generateTicketDescription($alert),
                'priority' => $this->mapSeverityToPriority($alert->severity),
                'status' => 'Open',
                'source' => 'RMM Integration',
                'category' => 'Infrastructure',
                'subcategory' => 'Monitoring',
                'asset_id' => $alert->asset_id,
            ]);

            // Link alert to ticket
            $alert->update(['ticket_id' => $ticket->id]);

            // Auto-assign if configured
            if (data_get($alertRules, 'auto_assign_technician')) {
                $this->autoAssignTicket($ticket, $integration);
            }

            // Notify client if configured
            if (data_get($alertRules, 'notify_client')) {
                $this->notifyClient($ticket, $alert);
            }

            Log::info('Created ticket from RMM alert', [
                'alert_id' => $alert->id,
                'ticket_id' => $ticket->id,
            ]);

            return $ticket;

        } catch (\Exception $e) {
            Log::error('Failed to create ticket from RMM alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve client ID from RMM client identifier.
     */
    protected function resolveClientId(Integration $integration, string $rmmClientId): ?int
    {
        // This would typically involve mapping RMM client IDs to internal client IDs
        // For now, we'll assume the RMM client ID might be the internal ID or name

        // Try as direct ID first
        if (is_numeric($rmmClientId)) {
            $client = \App\Models\Client::where('company_id', $integration->company_id)
                ->where('id', $rmmClientId)
                ->first();
            if ($client) {
                return $client->id;
            }
        }

        // Try matching by name or RMM ID
        $client = \App\Models\Client::where('company_id', $integration->company_id)
            ->where(function ($query) use ($rmmClientId) {
                $query->where('name', $rmmClientId)
                    ->orWhere('company_name', $rmmClientId)
                    ->orWhere('rmm_id', $rmmClientId);
            })
            ->first();

        return $client?->id;
    }

    /**
     * Resolve client ID from alert.
     */
    protected function resolveClientIdFromAlert(RMMAlert $alert): ?int
    {
        // Try from device mapping first
        $deviceMapping = DeviceMapping::where([
            'integration_id' => $alert->integration_id,
            'rmm_device_id' => $alert->device_id,
        ])->first();

        if ($deviceMapping) {
            return $deviceMapping->client_id;
        }

        // Try to resolve from raw payload
        $clientId = data_get($alert->raw_payload, 'client_id')
                 ?: data_get($alert->raw_payload, 'ClientID')
                 ?: data_get($alert->raw_payload, 'site_name')
                 ?: data_get($alert->raw_payload, 'organizationId');

        if ($clientId) {
            return $this->resolveClientId($alert->integration, $clientId);
        }

        return null;
    }

    /**
     * Determine alert type from payload.
     */
    protected function determineAlertType(array $payload, string $provider): string
    {
        // Extract alert type based on provider-specific fields
        switch ($provider) {
            case Integration::PROVIDER_CONNECTWISE:
                return data_get($payload, 'AlertType', 'System Alert');
            case Integration::PROVIDER_DATTO:
                return data_get($payload, 'alert_type', 'system');
            case Integration::PROVIDER_NINJA:
                return data_get($payload, 'alertType', 'system');
            default:
                return data_get($payload, 'alert_type', 'system');
        }
    }

    /**
     * Generate ticket title from alert.
     */
    protected function generateTicketTitle(RMMAlert $alert): string
    {
        $deviceName = $alert->device_id;
        $alertType = $alert->alert_type;

        return "[{$alert->severity}] {$alertType} - {$deviceName}";
    }

    /**
     * Generate ticket description from alert.
     */
    protected function generateTicketDescription(RMMAlert $alert): string
    {
        $description = "RMM Alert Details:\n\n";
        $description .= "Device: {$alert->device_id}\n";
        $description .= "Alert Type: {$alert->alert_type}\n";
        $description .= "Severity: {$alert->getSeverityLabel()}\n";
        $description .= "Message: {$alert->message}\n";
        $description .= "Integration: {$alert->integration->name}\n";
        $description .= "Received: {$alert->created_at->format('Y-m-d H:i:s')}\n\n";
        $description .= 'This ticket was automatically created from an RMM system alert.';

        return $description;
    }

    /**
     * Map alert severity to ticket priority.
     */
    protected function mapSeverityToPriority(string $severity): string
    {
        $mapping = [
            RMMAlert::SEVERITY_URGENT => 'Urgent',
            RMMAlert::SEVERITY_HIGH => 'High',
            RMMAlert::SEVERITY_NORMAL => 'Normal',
            RMMAlert::SEVERITY_LOW => 'Low',
        ];

        return $mapping[$severity] ?? 'Normal';
    }

    /**
     * Auto-assign ticket to technician.
     */
    protected function autoAssignTicket(Ticket $ticket, Integration $integration): void
    {
        // This would implement logic to assign tickets to technicians
        // based on client, alert type, current workload, etc.
        Log::info('Auto-assignment not yet implemented', ['ticket_id' => $ticket->id]);
    }

    /**
     * Notify client about the alert/ticket.
     */
    protected function notifyClient(Ticket $ticket, RMMAlert $alert): void
    {
        // This would implement client notification logic
        Log::info('Client notification not yet implemented', [
            'ticket_id' => $ticket->id,
            'alert_id' => $alert->id,
        ]);
    }
}
