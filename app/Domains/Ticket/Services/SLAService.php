<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Ticket\Models\SLA;
use App\Models\Client;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * SLA Service for Domain-Driven Design
 *
 * Handles all SLA-related business logic including creation, assignment,
 * breach detection, and deadline calculations.
 */
class SLAService
{
    /**
     * Create a new SLA for a company
     */
    public function create(int $companyId, array $data): SLA
    {
        $data['company_id'] = $companyId;

        // If this is set as default, ensure no other default exists
        if ($data['is_default'] ?? false) {
            $this->clearDefaultSLA($companyId);
        }

        return SLA::create($data);
    }

    /**
     * Update an existing SLA
     */
    public function update(SLA $sla, array $data): SLA
    {
        // If this is being set as default, clear other defaults
        if (($data['is_default'] ?? false) && ! $sla->is_default) {
            $this->clearDefaultSLA($sla->company_id);
        }

        $sla->update($data);

        return $sla->fresh();
    }

    /**
     * Delete an SLA and handle client reassignment
     */
    public function delete(SLA $sla): bool
    {
        // Get the default SLA for reassignment
        $defaultSLA = $this->getDefaultSLA($sla->company_id);

        // Reassign all clients using this SLA to the default SLA
        if ($defaultSLA && $defaultSLA->id !== $sla->id) {
            Client::where('sla_id', $sla->id)
                ->update(['sla_id' => $defaultSLA->id]);
        } else {
            // If deleting the default SLA, set clients to null
            Client::where('sla_id', $sla->id)
                ->update(['sla_id' => null]);
        }

        return $sla->delete();
    }

    /**
     * Get the default SLA for a company
     */
    public function getDefaultSLA(int $companyId): ?SLA
    {
        return SLA::where('company_id', $companyId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->effectiveOn()
            ->first();
    }

    /**
     * Get all active SLAs for a company
     */
    public function getActiveSLAs(int $companyId): Collection
    {
        return SLA::where('company_id', $companyId)
            ->active()
            ->effectiveOn()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the effective SLA for a client
     */
    public function getClientSLA(Client $client): ?SLA
    {
        // First try to get the client's specific SLA
        if ($client->sla_id) {
            $sla = SLA::where('id', $client->sla_id)
                ->active()
                ->effectiveOn()
                ->first();

            if ($sla) {
                return $sla;
            }
        }

        // Fallback to company default SLA
        return $this->getDefaultSLA($client->company_id);
    }

    /**
     * Assign SLA to a client
     */
    public function assignSLAToClient(Client $client, ?int $slaId): Client
    {
        // Validate SLA belongs to same company
        if ($slaId) {
            $sla = SLA::where('id', $slaId)
                ->where('company_id', $client->company_id)
                ->active()
                ->effectiveOn()
                ->first();

            if (! $sla) {
                throw new \InvalidArgumentException('Invalid SLA for this client\'s company');
            }
        }

        $client->update(['sla_id' => $slaId]);

        return $client->fresh();
    }

    /**
     * Calculate response deadline for a ticket
     */
    public function calculateResponseDeadline(Client $client, string $priority, Carbon $createdAt): ?Carbon
    {
        $sla = $this->getClientSLA($client);

        if (! $sla) {
            return null;
        }

        return $sla->calculateResponseDeadline($createdAt, $priority);
    }

    /**
     * Calculate resolution deadline for a ticket
     */
    public function calculateResolutionDeadline(Client $client, string $priority, Carbon $createdAt): ?Carbon
    {
        $sla = $this->getClientSLA($client);

        if (! $sla) {
            return null;
        }

        return $sla->calculateResolutionDeadline($createdAt, $priority);
    }

    /**
     * Check if response SLA is breached
     */
    public function isResponseBreached(Client $client, string $priority, Carbon $createdAt): bool
    {
        $sla = $this->getClientSLA($client);

        if (! $sla) {
            return false;
        }

        return $sla->isBreached($createdAt, $priority, 'response');
    }

    /**
     * Check if resolution SLA is breached
     */
    public function isResolutionBreached(Client $client, string $priority, Carbon $createdAt, ?Carbon $resolvedAt = null): bool
    {
        $sla = $this->getClientSLA($client);

        if (! $sla) {
            return false;
        }

        return $sla->isBreached($createdAt, $priority, 'resolution', $resolvedAt);
    }

    /**
     * Check if SLA is approaching breach (warning threshold)
     */
    public function isApproachingBreach(Client $client, string $priority, Carbon $createdAt, string $type = 'response'): bool
    {
        $sla = $this->getClientSLA($client);

        if (! $sla) {
            return false;
        }

        return $sla->isApproachingBreach($createdAt, $priority, $type);
    }

    /**
     * Get SLA performance metrics for a company
     */
    public function getSLAMetrics(int $companyId, Carbon $from, Carbon $to): array
    {
        // This would typically query tickets table to calculate metrics
        // For now, return a basic structure
        return [
            'total_tickets' => 0,
            'response_sla_met' => 0,
            'resolution_sla_met' => 0,
            'response_sla_percentage' => 0,
            'resolution_sla_percentage' => 0,
            'average_response_time' => 0,
            'average_resolution_time' => 0,
            'breached_tickets' => [],
        ];
    }

    /**
     * Create default SLA for a new company
     */
    public function createDefaultSLA(int $companyId, string $companyName): SLA
    {
        return $this->create($companyId, [
            'name' => $companyName.' - Default SLA',
            'description' => 'Default Service Level Agreement for '.$companyName,
            'is_default' => true,
            'is_active' => true,
            'critical_response_minutes' => 60,
            'high_response_minutes' => 240,
            'medium_response_minutes' => 480,
            'low_response_minutes' => 1440,
            'critical_resolution_minutes' => 240,
            'high_resolution_minutes' => 1440,
            'medium_resolution_minutes' => 4320,
            'low_resolution_minutes' => 10080,
            'business_hours_start' => '09:00',
            'business_hours_end' => '17:00',
            'business_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => 'UTC',
            'coverage_type' => SLA::COVERAGE_BUSINESS_HOURS,
            'holiday_coverage' => false,
            'exclude_weekends' => true,
            'escalation_enabled' => true,
            'breach_warning_percentage' => 80,
            'uptime_percentage' => 99.50,
            'first_call_resolution_target' => 75.00,
            'customer_satisfaction_target' => 90.00,
            'notify_on_breach' => true,
            'notify_on_warning' => true,
            'effective_from' => now()->toDateString(),
        ]);
    }

    /**
     * Migrate SLA data from settings to new SLA structure
     */
    public function migrateSLAFromSettings(Company $company): ?SLA
    {
        $settings = $company->settings;

        if (! $settings || ! $settings->sla_definitions) {
            return $this->createDefaultSLA($company->id, $company->name);
        }

        $slaData = $settings->sla_definitions;

        // Extract response times from settings
        $responseTimes = $slaData['response_times'] ?? [];
        $resolutionTimes = $slaData['resolution_times'] ?? [];

        return $this->create($company->id, [
            'name' => $company->name.' - Migrated SLA',
            'description' => 'SLA migrated from company settings',
            'is_default' => true,
            'is_active' => true,
            'critical_response_minutes' => $responseTimes['critical'] ?? 60,
            'high_response_minutes' => $responseTimes['high'] ?? 240,
            'medium_response_minutes' => $responseTimes['medium'] ?? 480,
            'low_response_minutes' => $responseTimes['low'] ?? 1440,
            'critical_resolution_minutes' => $resolutionTimes['critical'] ?? 240,
            'high_resolution_minutes' => $resolutionTimes['high'] ?? 1440,
            'medium_resolution_minutes' => $resolutionTimes['medium'] ?? 4320,
            'low_resolution_minutes' => $resolutionTimes['low'] ?? 10080,
            'business_hours_start' => $settings->business_hours_start ?? '09:00',
            'business_hours_end' => $settings->business_hours_end ?? '17:00',
            'business_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => $settings->timezone ?? 'UTC',
            'coverage_type' => SLA::COVERAGE_BUSINESS_HOURS,
            'holiday_coverage' => false,
            'exclude_weekends' => $slaData['exclude_weekends'] ?? true,
            'escalation_enabled' => $slaData['enabled'] ?? true,
            'breach_warning_percentage' => 80,
            'uptime_percentage' => 99.50,
            'first_call_resolution_target' => 75.00,
            'customer_satisfaction_target' => 90.00,
            'notify_on_breach' => true,
            'notify_on_warning' => true,
            'effective_from' => now()->toDateString(),
        ]);
    }

    /**
     * Clear default SLA flag for all other SLAs in company
     */
    protected function clearDefaultSLA(int $companyId): void
    {
        SLA::where('company_id', $companyId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * Validate SLA data
     */
    public function validateSLAData(array $data): array
    {
        $errors = [];

        // Validate response times are less than resolution times
        $priorities = ['critical', 'high', 'medium', 'low'];

        foreach ($priorities as $priority) {
            $responseField = $priority.'_response_minutes';
            $resolutionField = $priority.'_resolution_minutes';

            if (isset($data[$responseField], $data[$resolutionField])) {
                if ($data[$responseField] >= $data[$resolutionField]) {
                    $errors[] = "Response time must be less than resolution time for {$priority} priority";
                }
            }
        }

        // Validate business hours
        if (isset($data['business_hours_start'], $data['business_hours_end'])) {
            $start = Carbon::createFromTimeString($data['business_hours_start']);
            $end = Carbon::createFromTimeString($data['business_hours_end']);

            if ($start->gte($end)) {
                $errors[] = 'Business hours start time must be before end time';
            }
        }

        // Validate effective dates
        if (isset($data['effective_from'], $data['effective_to'])) {
            $from = Carbon::parse($data['effective_from']);
            $to = Carbon::parse($data['effective_to']);

            if ($from->gte($to)) {
                $errors[] = 'Effective from date must be before effective to date';
            }
        }

        return $errors;
    }
}
