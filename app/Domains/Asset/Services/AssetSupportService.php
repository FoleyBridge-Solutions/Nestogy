<?php

namespace App\Domains\Asset\Services;

use App\Models\Asset;
use App\Models\Contract;
use App\Models\ContractSchedule;
use App\Models\Client;
use App\Events\AssetCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Asset Support Service
 * 
 * Handles automatic evaluation and assignment of asset support status
 * based on active contract schedules and support coverage rules.
 */
class AssetSupportService
{
    /**
     * Support status constants
     */
    const STATUS_SUPPORTED = 'supported';
    const STATUS_UNSUPPORTED = 'unsupported';
    const STATUS_PENDING_ASSIGNMENT = 'pending_assignment';
    const STATUS_EXCLUDED = 'excluded';

    /**
     * Evaluate support status for a single asset.
     */
    public function evaluateAssetSupport(Asset $asset, bool $autoAssign = true): array
    {
        $evaluation = [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'asset_type' => $asset->type,
            'client_id' => $asset->client_id,
            'previous_status' => $asset->support_status,
            'evaluated_at' => now()->toISOString(),
            'recommendations' => [],
            'coverage_options' => [],
            'evaluation_rules' => [],
        ];

        try {
            // Get all effective infrastructure schedules for this client
            $infrastructureSchedules = $this->getEffectiveInfrastructureSchedules($asset->client_id, $asset->company_id);
            
            if ($infrastructureSchedules->isEmpty()) {
                // No infrastructure schedules available
                $evaluation['new_status'] = self::STATUS_UNSUPPORTED;
                $evaluation['reason'] = 'No active infrastructure schedules found for client';
                $evaluation['recommendations'][] = 'Create a support contract with Schedule A (Infrastructure) for this client';
            } else {
                // Evaluate against each schedule to find coverage
                $coverageResult = $this->findBestCoverage($asset, $infrastructureSchedules);
                
                if ($coverageResult['covered']) {
                    $evaluation['new_status'] = self::STATUS_SUPPORTED;
                    $evaluation['reason'] = $coverageResult['reason'];
                    $evaluation['supporting_schedule'] = $coverageResult['schedule'];
                    $evaluation['supporting_contract'] = $coverageResult['schedule']->contract;
                    $evaluation['support_level'] = $coverageResult['support_level'];
                    $evaluation['auto_assigned'] = $autoAssign && $coverageResult['schedule']->auto_assign_assets;
                } else {
                    $evaluation['new_status'] = self::STATUS_UNSUPPORTED;
                    $evaluation['reason'] = $coverageResult['reason'];
                    $evaluation['coverage_options'] = $coverageResult['potential_schedules'];
                    $evaluation['recommendations'] = $this->generateSupportRecommendations($asset, $infrastructureSchedules);
                }
            }

            // Store evaluation rules used
            $evaluation['evaluation_rules'] = $this->getEvaluationRules($infrastructureSchedules);

            // Apply the evaluation if auto-assign is enabled
            if ($autoAssign && isset($evaluation['supporting_schedule'])) {
                $this->applySupportAssignment($asset, $evaluation);
            }

        } catch (\Exception $e) {
            Log::error('Asset support evaluation failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $evaluation['new_status'] = $asset->support_status ?: self::STATUS_UNSUPPORTED;
            $evaluation['reason'] = 'Evaluation failed: ' . $e->getMessage();
            $evaluation['error'] = true;
        }

        return $evaluation;
    }

    /**
     * Evaluate support status for multiple assets.
     */
    public function evaluateBulkAssetSupport(array $assetIds, bool $autoAssign = true): array
    {
        $evaluations = [];
        $summary = [
            'total_assets' => count($assetIds),
            'evaluated' => 0,
            'errors' => 0,
            'status_changes' => 0,
            'newly_supported' => 0,
            'newly_unsupported' => 0,
            'processing_time' => 0,
        ];

        $startTime = microtime(true);

        foreach ($assetIds as $assetId) {
            try {
                $asset = Asset::findOrFail($assetId);
                $evaluation = $this->evaluateAssetSupport($asset, $autoAssign);
                
                $evaluations[] = $evaluation;
                $summary['evaluated']++;

                // Track status changes
                if ($evaluation['previous_status'] !== $evaluation['new_status']) {
                    $summary['status_changes']++;
                    
                    if ($evaluation['new_status'] === self::STATUS_SUPPORTED) {
                        $summary['newly_supported']++;
                    } elseif ($evaluation['new_status'] === self::STATUS_UNSUPPORTED) {
                        $summary['newly_unsupported']++;
                    }
                }

            } catch (\Exception $e) {
                $summary['errors']++;
                Log::error('Bulk asset support evaluation failed', [
                    'asset_id' => $assetId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $summary['processing_time'] = round((microtime(true) - $startTime) * 1000, 2); // ms

        return [
            'evaluations' => $evaluations,
            'summary' => $summary,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Evaluate support for all assets of a specific client.
     */
    public function evaluateClientAssetSupport(int $clientId, bool $autoAssign = true): array
    {
        $assets = Asset::where('client_id', $clientId)
            ->where('company_id', auth()->user()->company_id)
            ->get();

        $assetIds = $assets->pluck('id')->toArray();
        
        return $this->evaluateBulkAssetSupport($assetIds, $autoAssign);
    }

    /**
     * Handle new asset discovery and support evaluation.
     */
    public function handleAssetDiscovery(Asset $asset): array
    {
        Log::info('Handling asset discovery for support evaluation', [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'client_id' => $asset->client_id,
        ]);

        // Always start with unsupported status for new assets
        $asset->update([
            'support_status' => self::STATUS_UNSUPPORTED,
            'support_last_evaluated_at' => now(),
        ]);

        // Evaluate support coverage
        $evaluation = $this->evaluateAssetSupport($asset, true);

        // Log the result
        Log::info('Asset discovery support evaluation completed', [
            'asset_id' => $asset->id,
            'previous_status' => $evaluation['previous_status'],
            'new_status' => $evaluation['new_status'],
            'reason' => $evaluation['reason'],
        ]);

        return $evaluation;
    }

    /**
     * Re-evaluate all assets when a contract schedule changes.
     */
    public function handleScheduleChange(ContractSchedule $schedule): array
    {
        if (!$schedule->isInfrastructureSchedule()) {
            return ['message' => 'Schedule is not an infrastructure schedule, no asset re-evaluation needed'];
        }

        Log::info('Re-evaluating assets due to schedule change', [
            'schedule_id' => $schedule->id,
            'contract_id' => $schedule->contract_id,
            'schedule_type' => $schedule->schedule_type,
        ]);

        // Get all assets for the client
        $clientId = $schedule->contract->client_id;
        
        return $this->evaluateClientAssetSupport($clientId, true);
    }

    /**
     * Get all effective infrastructure schedules for a client.
     */
    protected function getEffectiveInfrastructureSchedules(int $clientId, ?int $companyId = null): Collection
    {
        // Use provided company ID or get from auth
        $companyId = $companyId ?: (auth()->user() ? auth()->user()->company_id : null);
        
        if (!$companyId) {
            // If no company context, get it from the client
            $client = Client::find($clientId);
            $companyId = $client ? $client->company_id : null;
        }
        
        if (!$companyId) {
            return collect();
        }

        return ContractSchedule::where('company_id', $companyId)
            ->infrastructure()
            ->effective()
            ->whereHas('contract', function ($query) use ($clientId) {
                $query->where('client_id', $clientId)
                    ->where('status', Contract::STATUS_ACTIVE);
            })
            ->with(['contract'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find the best coverage option for an asset.
     */
    protected function findBestCoverage(Asset $asset, Collection $schedules): array
    {
        $result = [
            'covered' => false,
            'reason' => '',
            'schedule' => null,
            'support_level' => null,
            'potential_schedules' => [],
        ];

        foreach ($schedules as $schedule) {
            if ($schedule->shouldCoverAsset($asset)) {
                // Found coverage!
                $result['covered'] = true;
                $result['reason'] = "Asset matches coverage rules in {$schedule->title}";
                $result['schedule'] = $schedule;
                $result['support_level'] = $this->determineSupportLevel($asset, $schedule);
                break;
            } else {
                // Track as potential option
                $result['potential_schedules'][] = [
                    'schedule_id' => $schedule->id,
                    'title' => $schedule->title,
                    'contract_number' => $schedule->contract->contract_number,
                    'why_not_covered' => $this->explainWhyNotCovered($asset, $schedule),
                ];
            }
        }

        if (!$result['covered']) {
            if (empty($result['potential_schedules'])) {
                $result['reason'] = 'No infrastructure schedules available for this client';
            } else {
                $result['reason'] = 'Asset does not match coverage rules in any available schedules';
            }
        }

        return $result;
    }

    /**
     * Determine the support level for an asset based on schedule.
     */
    protected function determineSupportLevel(Asset $asset, ContractSchedule $schedule): string
    {
        $serviceLevel = $schedule->getServiceLevel($asset->type);
        
        if ($serviceLevel && isset($serviceLevel['level'])) {
            return $serviceLevel['level'];
        }

        // Default support levels based on asset type
        return match ($asset->type) {
            'Server' => 'premium',
            'Firewall', 'Router', 'Switch' => 'standard',
            'Desktop', 'Laptop' => 'basic',
            default => 'basic',
        };
    }

    /**
     * Explain why an asset is not covered by a schedule.
     */
    protected function explainWhyNotCovered(Asset $asset, ContractSchedule $schedule): string
    {
        if (!$schedule->isEffective()) {
            return 'Schedule is not currently effective';
        }

        if (!$schedule->supportsAssetType($asset->type)) {
            $supportedTypes = implode(', ', $schedule->getSupportedAssetTypes());
            return "Asset type '{$asset->type}' not in supported types: {$supportedTypes}";
        }

        // Check more specific rules...
        // This could be expanded to give detailed explanations
        return 'Asset does not meet schedule coverage criteria';
    }

    /**
     * Apply support assignment to an asset.
     */
    protected function applySupportAssignment(Asset $asset, array $evaluation): void
    {
        if (!isset($evaluation['supporting_schedule'])) {
            return;
        }

        $schedule = $evaluation['supporting_schedule'];
        $contract = $evaluation['supporting_contract'];

        $asset->update([
            'support_status' => $evaluation['new_status'],
            'support_level' => $evaluation['support_level'],
            'supporting_contract_id' => $contract->id,
            'supporting_schedule_id' => $schedule->id,
            'auto_assigned_support' => $evaluation['auto_assigned'] ?? false,
            'support_assigned_at' => now(),
            'support_assigned_by' => auth()->id(),
            'support_last_evaluated_at' => now(),
            'support_evaluation_rules' => $evaluation['evaluation_rules'],
        ]);

        // Update schedule asset count
        $schedule->updateAssetCount();

        Log::info('Asset support assignment applied', [
            'asset_id' => $asset->id,
            'contract_id' => $contract->id,
            'schedule_id' => $schedule->id,
            'support_level' => $evaluation['support_level'],
            'auto_assigned' => $evaluation['auto_assigned'] ?? false,
        ]);
    }

    /**
     * Generate support recommendations for an unsupported asset.
     */
    protected function generateSupportRecommendations(Asset $asset, Collection $schedules): array
    {
        $recommendations = [];

        if ($schedules->isEmpty()) {
            $recommendations[] = [
                'type' => 'create_contract',
                'priority' => 'high',
                'action' => 'Create a support contract with Schedule A (Infrastructure) for this client',
                'details' => "Client '{$asset->client->name}' has no infrastructure support schedules",
            ];
        } else {
            $recommendations[] = [
                'type' => 'modify_schedule',
                'priority' => 'medium',
                'action' => 'Update existing schedule to include this asset type',
                'details' => "Add '{$asset->type}' to supported asset types in an existing infrastructure schedule",
            ];

            $recommendations[] = [
                'type' => 'manual_assignment',
                'priority' => 'low',
                'action' => 'Manually assign asset to a support contract',
                'details' => 'Override automatic rules and assign support manually if this asset requires coverage',
            ];
        }

        return $recommendations;
    }

    /**
     * Get evaluation rules summary.
     */
    protected function getEvaluationRules(Collection $schedules): array
    {
        return [
            'total_schedules_evaluated' => $schedules->count(),
            'schedule_ids' => $schedules->pluck('id')->toArray(),
            'evaluation_criteria' => [
                'asset_type_support',
                'inclusion_rules',
                'exclusion_rules',
                'location_coverage',
                'schedule_effectiveness',
            ],
            'evaluated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get support status statistics for a client.
     */
    public function getClientSupportStatistics(int $clientId): array
    {
        // Get company_id from the client
        $client = Client::find($clientId);
        $companyId = $client ? $client->company_id : (auth()->user() ? auth()->user()->company_id : null);
        
        if (!$companyId) {
            return ['total_assets' => 0, 'by_status' => [], 'by_level' => [], 'auto_assigned_percentage' => 0];
        }

        $stats = Asset::where('client_id', $clientId)
            ->where('company_id', $companyId)
            ->selectRaw('
                support_status,
                support_level,
                COUNT(*) as count,
                COUNT(CASE WHEN auto_assigned_support = true THEN 1 END) as auto_assigned_count
            ')
            ->groupBy(['support_status', 'support_level'])
            ->get();

        $summary = [
            'total_assets' => $stats->sum('count'),
            'by_status' => [],
            'by_level' => [],
            'auto_assigned_percentage' => 0,
        ];

        foreach ($stats as $stat) {
            $summary['by_status'][$stat->support_status] = ($summary['by_status'][$stat->support_status] ?? 0) + $stat->count;
            
            if ($stat->support_level) {
                $summary['by_level'][$stat->support_level] = ($summary['by_level'][$stat->support_level] ?? 0) + $stat->count;
            }
        }

        $totalAutoAssigned = $stats->sum('auto_assigned_count');
        if ($summary['total_assets'] > 0) {
            $summary['auto_assigned_percentage'] = round(($totalAutoAssigned / $summary['total_assets']) * 100, 2);
        }

        return $summary;
    }

    /**
     * Get unsupported assets for a company.
     */
    public function getUnsupportedAssets(int $companyId, int $limit = 50): Collection
    {
        return Asset::where('company_id', $companyId)
            ->where('support_status', self::STATUS_UNSUPPORTED)
            ->with(['client', 'location'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get assets needing support re-evaluation.
     */
    public function getAssetsNeedingReevaluation(int $companyId, int $daysOld = 30): Collection
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return Asset::where('company_id', $companyId)
            ->where(function ($query) use ($cutoffDate) {
                $query->whereNull('support_last_evaluated_at')
                    ->orWhere('support_last_evaluated_at', '<', $cutoffDate);
            })
            ->with(['client', 'supportingContract', 'supportingSchedule'])
            ->orderBy('support_last_evaluated_at', 'asc')
            ->get();
    }

    /**
     * Mark an asset as excluded from support.
     */
    public function excludeAssetFromSupport(Asset $asset, string $reason, ?int $excludedBy = null): void
    {
        $asset->update([
            'support_status' => self::STATUS_EXCLUDED,
            'support_level' => null,
            'supporting_contract_id' => null,
            'supporting_schedule_id' => null,
            'auto_assigned_support' => false,
            'support_assigned_at' => null,
            'support_assigned_by' => $excludedBy ?? auth()->id(),
            'support_last_evaluated_at' => now(),
            'support_notes' => $reason,
        ]);

        Log::info('Asset excluded from support', [
            'asset_id' => $asset->id,
            'reason' => $reason,
            'excluded_by' => $excludedBy ?? auth()->id(),
        ]);
    }

    /**
     * Manually assign support to an asset.
     */
    public function manuallyAssignSupport(
        Asset $asset, 
        Contract $contract, 
        ?ContractSchedule $schedule = null, 
        ?string $supportLevel = null,
        ?string $notes = null
    ): void {
        $asset->update([
            'support_status' => self::STATUS_SUPPORTED,
            'support_level' => $supportLevel ?: 'standard',
            'supporting_contract_id' => $contract->id,
            'supporting_schedule_id' => $schedule?->id,
            'auto_assigned_support' => false,
            'support_assigned_at' => now(),
            'support_assigned_by' => auth()->id(),
            'support_last_evaluated_at' => now(),
            'support_notes' => $notes,
        ]);

        if ($schedule) {
            $schedule->updateAssetCount();
        }

        Log::info('Asset manually assigned support', [
            'asset_id' => $asset->id,
            'contract_id' => $contract->id,
            'schedule_id' => $schedule?->id,
            'support_level' => $supportLevel,
            'assigned_by' => auth()->id(),
        ]);
    }
}