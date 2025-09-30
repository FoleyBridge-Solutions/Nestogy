<?php

namespace App\Domains\Integration\Services;

use App\Domains\Integration\Models\RmmIntegration;

/**
 * RMM Service Interface
 *
 * Defines the contract for RMM service implementations.
 * Ensures consistent API across different RMM providers.
 */
interface RmmServiceInterface
{
    /**
     * Initialize the service with integration configuration.
     */
    public function __construct(RmmIntegration $integration);

    /**
     * Test connection to the RMM system.
     *
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function testConnection(): array;

    /**
     * Get all agents/devices from the RMM system.
     *
     * @param  array  $filters  Optional filters
     * @return array ['success' => bool, 'data' => array, 'total' => int]
     */
    public function getAgents(array $filters = []): array;

    /**
     * Get a specific agent by ID.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getAgent(string $agentId): array;

    /**
     * Get all clients/organizations from the RMM system.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getClients(): array;

    /**
     * Create a new client/organization in the RMM system.
     *
     * @param  array  $clientData  Client data (name, description, etc.)
     * @return array ['success' => bool, 'data' => array, 'id' => string]
     */
    public function createClient(array $clientData): array;

    /**
     * Get all sites for a specific client.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getSites(string $clientId): array;

    /**
     * Get alerts from the RMM system.
     *
     * @param  array  $filters  Optional filters (date range, severity, etc.)
     * @return array ['success' => bool, 'data' => array, 'total' => int]
     */
    public function getAlerts(array $filters = []): array;

    /**
     * Get a specific alert by ID.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getAlert(string $alertId): array;

    /**
     * Acknowledge/resolve an alert.
     *
     * @param  string  $action  'acknowledge' or 'resolve'
     * @param  string|null  $note  Optional note
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateAlert(string $alertId, string $action, ?string $note = null): array;

    /**
     * Get checks/monitors for an agent.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getAgentChecks(string $agentId): array;

    /**
     * Run a command on an agent.
     *
     * @return array ['success' => bool, 'data' => array, 'task_id' => string]
     */
    public function runCommand(string $agentId, string $command, array $options = []): array;

    /**
     * Get system information for an agent.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getAgentInfo(string $agentId): array;

    /**
     * Get installed software for an agent.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getAgentSoftware(string $agentId): array;

    /**
     * Get services for an agent.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getAgentServices(string $agentId): array;

    /**
     * Get event logs for an agent.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getAgentEventLogs(string $agentId, string $logType, int $days = 7): array;

    /**
     * Create a script and run it on an agent.
     *
     * @return array ['success' => bool, 'data' => array, 'task_id' => string]
     */
    public function runScript(string $agentId, array $scriptData): array;

    /**
     * Get pending actions for an agent.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getPendingActions(string $agentId): array;

    /**
     * Reboot an agent.
     *
     * @return array ['success' => bool, 'task_id' => string]
     */
    public function rebootAgent(string $agentId, array $options = []): array;

    /**
     * Get task/action status.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function getTaskStatus(string $taskId): array;

    /**
     * Sync agents data and return standardized format.
     *
     * @return array ['success' => bool, 'agents' => array, 'total' => int]
     */
    public function syncAgents(): array;

    /**
     * Sync alerts data and return standardized format.
     *
     * @return array ['success' => bool, 'alerts' => array, 'total' => int]
     */
    public function syncAlerts(array $filters = []): array;

    /**
     * Get webhook endpoint URL for this integration.
     */
    public function getWebhookUrl(): string;

    /**
     * Process webhook payload from the RMM system.
     *
     * @return array ['success' => bool, 'data' => array]
     */
    public function processWebhook(array $payload): array;

    /**
     * Get API rate limit information.
     *
     * @return array ['success' => bool, 'limits' => array]
     */
    public function getRateLimits(): array;

    /**
     * Get service health/status information.
     *
     * @return array ['success' => bool, 'status' => string, 'version' => string]
     */
    public function getServiceHealth(): array;
}
