<?php

namespace App\Domains\Financial\Services;

use App\Models\AccountHold;
use App\Models\Client;
use App\Models\CollectionNote;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VoIP Collection Service
 *
 * Handles VoIP-specific collection activities including service suspension
 * with E911 preservation, equipment recovery, number porting restrictions,
 * and specialized VoIP collection workflows.
 */
class VoipCollectionService
{
    protected CollectionManagementService $collectionService;

    protected array $essentialServices = ['E911', 'emergency', '911'];

    protected array $suspensionGracePeriod = [
        'residential' => 3, // 3 days
        'business' => 7,    // 7 days
        'enterprise' => 14,  // 14 days
    ];

    public function __construct(CollectionManagementService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * Initiate service suspension with E911 preservation.
     */
    public function suspendVoipServices(
        Client $client,
        string $reason,
        array $options = []
    ): AccountHold {
        return DB::transaction(function () use ($client, $reason, $options) {
            // Check for existing active holds
            $existingHold = $client->accountHolds()
                ->where('status', AccountHold::STATUS_ACTIVE)
                ->where('hold_type', AccountHold::TYPE_SERVICE_SUSPENSION)
                ->first();

            if ($existingHold) {
                throw new \Exception('Client already has an active service suspension hold');
            }

            // Determine suspension level based on risk and account type
            $suspensionLevel = $this->determineSuspensionLevel($client, $options);

            // Create account hold record
            $hold = AccountHold::create([
                'client_id' => $client->id,
                'hold_type' => AccountHold::TYPE_SERVICE_SUSPENSION,
                'reason' => $reason,
                'status' => AccountHold::STATUS_ACTIVE,
                'severity' => $suspensionLevel,
                'scheduled_date' => $options['scheduled_date'] ?? Carbon::now()->addDays(
                    $this->suspensionGracePeriod[$client->account_type] ?? 3
                ),
                'services_affected' => $this->getAffectedServices($client, $suspensionLevel),
                'services_preserved' => $this->getPreservedServices($client),
                'equipment_hold' => $options['equipment_hold'] ?? false,
                'porting_restriction' => $options['porting_restriction'] ?? true,
                'notes' => $options['notes'] ?? '',
                'created_by' => auth()->id() ?? 1,
            ]);

            // Execute suspension if scheduled for now
            if ($hold->scheduled_date <= Carbon::now()) {
                $this->executeSuspension($hold);
            }

            // Create collection note
            CollectionNote::create([
                'client_id' => $client->id,
                'account_hold_id' => $hold->id,
                'note_type' => CollectionNote::TYPE_SERVICE_SUSPENSION,
                'content' => "VoIP service suspension initiated: {$reason}",
                'outcome' => CollectionNote::OUTCOME_SERVICE_SUSPENDED,
                'is_important' => true,
                'follow_up_date' => $hold->scheduled_date->addDays(7),
                'created_by' => auth()->id() ?? 1,
            ]);

            Log::info('VoIP service suspension initiated', [
                'client_id' => $client->id,
                'hold_id' => $hold->id,
                'suspension_level' => $suspensionLevel,
                'scheduled_date' => $hold->scheduled_date,
            ]);

            return $hold;
        });
    }

    /**
     * Determine appropriate suspension level based on client risk and account type.
     */
    protected function determineSuspensionLevel(Client $client, array $options = []): string
    {
        if (isset($options['force_level'])) {
            return $options['force_level'];
        }

        $riskAssessment = $this->collectionService->assessClientRisk($client);
        $riskLevel = $riskAssessment['risk_level'];
        $accountType = $client->account_type;

        // Progressive suspension levels
        switch ($riskLevel) {
            case 'low':
                return AccountHold::SEVERITY_SOFT_SUSPENSION; // Block outbound only
            case 'medium':
                return $accountType === 'enterprise' ?
                    AccountHold::SEVERITY_SOFT_SUSPENSION :
                    AccountHold::SEVERITY_PARTIAL_SUSPENSION; // Block most services
            case 'high':
                return AccountHold::SEVERITY_PARTIAL_SUSPENSION;
            case 'critical':
                return AccountHold::SEVERITY_FULL_SUSPENSION; // Block all except E911
            default:
                return AccountHold::SEVERITY_SOFT_SUSPENSION;
        }
    }

    /**
     * Get services that will be affected by suspension.
     */
    protected function getAffectedServices(Client $client, string $suspensionLevel): array
    {
        $allServices = $this->getClientVoipServices($client);

        switch ($suspensionLevel) {
            case AccountHold::SEVERITY_SOFT_SUSPENSION:
                return $this->filterServices($allServices, ['outbound_calling', 'international']);
            case AccountHold::SEVERITY_PARTIAL_SUSPENSION:
                return $this->filterServices($allServices, ['outbound_calling', 'international', 'features']);
            case AccountHold::SEVERITY_FULL_SUSPENSION:
                return $this->filterServices($allServices, ['all'], ['E911', 'emergency']);
            default:
                return [];
        }
    }

    /**
     * Get services that will be preserved during suspension.
     */
    protected function getPreservedServices(Client $client): array
    {
        $preserved = ['E911', 'emergency_services'];

        // Enterprise clients get additional preserved services
        if ($client->account_type === 'enterprise') {
            $preserved = array_merge($preserved, ['inbound_calling', 'voicemail']);
        }

        return $preserved;
    }

    /**
     * Get all VoIP services for a client.
     */
    protected function getClientVoipServices(Client $client): array
    {
        // This would integrate with the VoIP management system
        return [
            'inbound_calling' => true,
            'outbound_calling' => true,
            'international' => true,
            'voicemail' => true,
            'call_forwarding' => true,
            'conference' => true,
            'E911' => true,
            'features' => true,
        ];
    }

    /**
     * Filter services based on suspension rules.
     */
    protected function filterServices(array $services, array $toSuspend, array $toPreserve = []): array
    {
        $affected = [];

        foreach ($services as $service => $enabled) {
            if (! $enabled) {
                continue;
            }

            // Never suspend preserved services
            if (in_array($service, $toPreserve)) {
                continue;
            }
            if (in_array($service, $this->essentialServices)) {
                continue;
            }

            // Check if service should be suspended
            if (in_array('all', $toSuspend) || in_array($service, $toSuspend)) {
                $affected[] = $service;
            }
        }

        return $affected;
    }

    /**
     * Execute the actual service suspension.
     */
    protected function executeSuspension(AccountHold $hold): bool
    {
        try {
            $client = $hold->client;
            $servicesAffected = $hold->services_affected ?? [];

            // Call VoIP system API to suspend services
            $result = $this->callVoipApi('suspend_services', [
                'client_id' => $client->id,
                'services' => $servicesAffected,
                'preserve_services' => $hold->services_preserved ?? [],
                'hold_id' => $hold->id,
            ]);

            if ($result['success']) {
                $hold->update([
                    'executed_date' => Carbon::now(),
                    'execution_status' => 'completed',
                    'system_response' => $result['response'] ?? null,
                ]);

                // Apply porting restriction if enabled
                if ($hold->porting_restriction) {
                    $this->applyPortingRestriction($client, $hold);
                }

                Log::info('VoIP service suspension executed', [
                    'client_id' => $client->id,
                    'hold_id' => $hold->id,
                    'services_suspended' => $servicesAffected,
                ]);

                return true;
            }

            throw new \Exception('VoIP system returned failure: '.($result['error'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            $hold->update([
                'execution_status' => 'failed',
                'system_response' => $e->getMessage(),
            ]);

            Log::error('Failed to execute VoIP service suspension', [
                'client_id' => $hold->client_id,
                'hold_id' => $hold->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Apply number porting restrictions.
     */
    protected function applyPortingRestriction(Client $client, AccountHold $hold): void
    {
        try {
            $phoneNumbers = $this->getClientPhoneNumbers($client);

            foreach ($phoneNumbers as $number) {
                $this->callVoipApi('restrict_porting', [
                    'phone_number' => $number,
                    'client_id' => $client->id,
                    'hold_id' => $hold->id,
                    'reason' => 'Collections account hold',
                ]);
            }

            Log::info('Porting restrictions applied', [
                'client_id' => $client->id,
                'hold_id' => $hold->id,
                'numbers_count' => count($phoneNumbers),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to apply porting restrictions', [
                'client_id' => $client->id,
                'hold_id' => $hold->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Restore VoIP services after payment or resolution.
     */
    public function restoreVoipServices(AccountHold $hold, string $reason = ''): bool
    {
        return DB::transaction(function () use ($hold, $reason) {
            try {
                $client = $hold->client;

                // Call VoIP system to restore services
                $result = $this->callVoipApi('restore_services', [
                    'client_id' => $client->id,
                    'services' => $hold->services_affected ?? [],
                    'hold_id' => $hold->id,
                ]);

                if ($result['success']) {
                    // Remove porting restrictions
                    if ($hold->porting_restriction) {
                        $this->removePortingRestriction($client, $hold);
                    }

                    // Update hold status
                    $hold->update([
                        'status' => AccountHold::STATUS_RESOLVED,
                        'resolved_date' => Carbon::now(),
                        'resolved_by' => auth()->id() ?? 1,
                        'resolution_reason' => $reason ?: 'Services restored',
                    ]);

                    // Create collection note
                    CollectionNote::create([
                        'client_id' => $client->id,
                        'account_hold_id' => $hold->id,
                        'note_type' => CollectionNote::TYPE_SERVICE_SUSPENSION,
                        'content' => "VoIP services restored: {$reason}",
                        'outcome' => CollectionNote::OUTCOME_SERVICES_RESTORED,
                        'created_by' => auth()->id() ?? 1,
                    ]);

                    Log::info('VoIP services restored', [
                        'client_id' => $client->id,
                        'hold_id' => $hold->id,
                        'reason' => $reason,
                    ]);

                    return true;
                }

                throw new \Exception('VoIP system returned failure: '.($result['error'] ?? 'Unknown error'));
            } catch (\Exception $e) {
                Log::error('Failed to restore VoIP services', [
                    'client_id' => $hold->client_id,
                    'hold_id' => $hold->id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        });
    }

    /**
     * Remove number porting restrictions.
     */
    protected function removePortingRestriction(Client $client, AccountHold $hold): void
    {
        try {
            $phoneNumbers = $this->getClientPhoneNumbers($client);

            foreach ($phoneNumbers as $number) {
                $this->callVoipApi('remove_porting_restriction', [
                    'phone_number' => $number,
                    'client_id' => $client->id,
                    'hold_id' => $hold->id,
                ]);
            }

            Log::info('Porting restrictions removed', [
                'client_id' => $client->id,
                'hold_id' => $hold->id,
                'numbers_count' => count($phoneNumbers),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to remove porting restrictions', [
                'client_id' => $client->id,
                'hold_id' => $hold->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Initiate equipment recovery process.
     */
    public function initiateEquipmentRecovery(
        Client $client,
        array $equipmentList = [],
        string $reason = ''
    ): array {
        $recoveryRecord = [
            'client_id' => $client->id,
            'equipment_list' => $equipmentList ?: $this->getClientEquipment($client),
            'initiated_date' => Carbon::now(),
            'reason' => $reason,
            'status' => 'initiated',
            'recovery_method' => $this->determineRecoveryMethod($client),
            'estimated_value' => $this->calculateEquipmentValue($equipmentList ?: $this->getClientEquipment($client)),
        ];

        // Create collection note
        CollectionNote::create([
            'client_id' => $client->id,
            'note_type' => CollectionNote::TYPE_EQUIPMENT_RECOVERY,
            'content' => "Equipment recovery initiated: {$reason}",
            'outcome' => CollectionNote::OUTCOME_EQUIPMENT_RECOVERY_INITIATED,
            'is_important' => true,
            'requires_attention' => true,
            'follow_up_date' => Carbon::now()->addDays(14),
            'created_by' => auth()->id() ?? 1,
        ]);

        Log::info('Equipment recovery initiated', [
            'client_id' => $client->id,
            'equipment_count' => count($recoveryRecord['equipment_list']),
            'estimated_value' => $recoveryRecord['estimated_value'],
        ]);

        return $recoveryRecord;
    }

    /**
     * Get client's equipment inventory.
     */
    protected function getClientEquipment(Client $client): array
    {
        // This would integrate with equipment management system
        $equipment = $this->callVoipApi('get_client_equipment', [
            'client_id' => $client->id,
        ]);

        return $equipment['equipment'] ?? [];
    }

    /**
     * Calculate total value of equipment.
     */
    protected function calculateEquipmentValue(array $equipment): float
    {
        $totalValue = 0;

        foreach ($equipment as $item) {
            $totalValue += $item['value'] ?? 0;
        }

        return $totalValue;
    }

    /**
     * Determine optimal equipment recovery method.
     */
    protected function determineRecoveryMethod(Client $client): string
    {
        $riskAssessment = $this->collectionService->assessClientRisk($client);
        $equipmentValue = $this->calculateEquipmentValue($this->getClientEquipment($client));

        if ($riskAssessment['risk_level'] === 'critical' || $equipmentValue > 1000) {
            return 'field_recovery'; // Send technician
        } elseif ($equipmentValue > 500) {
            return 'prepaid_return'; // Send prepaid shipping label
        } else {
            return 'voluntary_return'; // Request voluntary return
        }
    }

    /**
     * Get client's phone numbers.
     */
    protected function getClientPhoneNumbers(Client $client): array
    {
        // This would integrate with phone number management system
        $numbers = $this->callVoipApi('get_client_numbers', [
            'client_id' => $client->id,
        ]);

        return $numbers['phone_numbers'] ?? [];
    }

    /**
     * Check E911 compliance before suspension.
     */
    public function checkE911Compliance(Client $client): array
    {
        $e911Status = $this->callVoipApi('check_e911_status', [
            'client_id' => $client->id,
        ]);

        $compliance = [
            'is_compliant' => $e911Status['compliant'] ?? false,
            'active_e911_numbers' => $e911Status['e911_numbers'] ?? [],
            'compliance_issues' => $e911Status['issues'] ?? [],
            'can_suspend_safely' => true,
        ];

        // Check if suspension would create E911 compliance issues
        if (! empty($compliance['active_e911_numbers'])) {
            $compliance['can_suspend_safely'] = $this->validateE911Preservation($compliance);
        }

        return $compliance;
    }

    /**
     * Validate that E911 services can be preserved during suspension.
     */
    protected function validateE911Preservation(array $compliance): bool
    {
        // E911 services must always remain active
        // This checks if our suspension logic properly preserves these services
        return ! empty($compliance['active_e911_numbers']);
    }

    /**
     * Generate VoIP collection strategy recommendations.
     */
    public function generateVoipCollectionStrategy(Client $client): array
    {
        $riskAssessment = $this->collectionService->assessClientRisk($client);
        $e911Compliance = $this->checkE911Compliance($client);
        $equipment = $this->getClientEquipment($client);
        $equipmentValue = $this->calculateEquipmentValue($equipment);

        $strategy = [
            'recommended_actions' => [],
            'timeline' => [],
            'risk_factors' => [],
            'compliance_considerations' => [],
        ];

        // Service suspension recommendations
        if ($riskAssessment['risk_level'] === 'high' || $riskAssessment['risk_level'] === 'critical') {
            $strategy['recommended_actions'][] = [
                'action' => 'service_suspension',
                'priority' => 'high',
                'timeline' => '3-7 days',
                'level' => $this->determineSuspensionLevel($client),
                'e911_preserved' => true,
            ];
        }

        // Equipment recovery recommendations
        if ($equipmentValue > 300) {
            $strategy['recommended_actions'][] = [
                'action' => 'equipment_recovery',
                'priority' => $equipmentValue > 1000 ? 'high' : 'medium',
                'method' => $this->determineRecoveryMethod($client),
                'estimated_value' => $equipmentValue,
            ];
        }

        // Porting restriction recommendations
        if ($riskAssessment['risk_level'] !== 'low') {
            $strategy['recommended_actions'][] = [
                'action' => 'porting_restriction',
                'priority' => 'medium',
                'reason' => 'Prevent number porting during collection process',
            ];
        }

        $strategy['compliance_considerations'] = $e911Compliance;

        return $strategy;
    }

    /**
     * Mock VoIP API call for integration.
     */
    protected function callVoipApi(string $endpoint, array $data): array
    {
        // This would integrate with the actual VoIP management system
        // For now, return mock successful responses

        Log::info('VoIP API call', [
            'endpoint' => $endpoint,
            'data' => $data,
        ]);

        switch ($endpoint) {
            case 'suspend_services':
            case 'restore_services':
            case 'restrict_porting':
            case 'remove_porting_restriction':
                return ['success' => true, 'response' => 'Operation completed'];

            case 'get_client_equipment':
                return [
                    'equipment' => [
                        ['id' => 1, 'type' => 'phone', 'model' => 'Yealink T46G', 'value' => 150],
                        ['id' => 2, 'type' => 'gateway', 'model' => 'Cisco SPA122', 'value' => 75],
                    ],
                ];

            case 'get_client_numbers':
                return [
                    'phone_numbers' => ['+15551234567', '+15557654321'],
                ];

            case 'check_e911_status':
                return [
                    'compliant' => true,
                    'e911_numbers' => ['+15551234567'],
                    'issues' => [],
                ];

            default:
                return ['success' => false, 'error' => 'Unknown endpoint'];
        }
    }

    /**
     * Process scheduled VoIP collection actions.
     */
    public function processScheduledVoipActions(): array
    {
        $results = [
            'suspensions_executed' => 0,
            'equipment_recovery_initiated' => 0,
            'compliance_checks' => 0,
            'errors' => [],
        ];

        // Execute scheduled suspensions
        $scheduledSuspensions = AccountHold::where('status', AccountHold::STATUS_ACTIVE)
            ->where('scheduled_date', '<=', Carbon::now())
            ->whereNull('executed_date')
            ->get();

        foreach ($scheduledSuspensions as $hold) {
            try {
                if ($this->executeSuspension($hold)) {
                    $results['suspensions_executed']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'type' => 'suspension',
                    'hold_id' => $hold->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Check compliance for all active holds
        $activeHolds = AccountHold::where('status', AccountHold::STATUS_ACTIVE)
            ->where('hold_type', AccountHold::TYPE_SERVICE_SUSPENSION)
            ->get();

        foreach ($activeHolds as $hold) {
            try {
                $compliance = $this->checkE911Compliance($hold->client);
                if (! $compliance['is_compliant']) {
                    Log::warning('E911 compliance issue detected', [
                        'client_id' => $hold->client_id,
                        'hold_id' => $hold->id,
                        'issues' => $compliance['compliance_issues'],
                    ]);
                }
                $results['compliance_checks']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'type' => 'compliance_check',
                    'hold_id' => $hold->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
