<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\Contract;
use App\Models\Client;
use App\Models\Asset;
use App\Domains\Contract\Models\ContractSchedule;
use App\Domains\Contract\Models\ContractTemplate;
use App\Models\Quote;
use App\Domains\Contract\Services\ContractConfigurationRegistry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * ContractService
 * 
 * Handles core contract operations including CRUD, search, filtering,
 * and business logic following Nestogy's Domain-Driven Design patterns.
 */
class ContractService
{
    /**
     * Get contract configuration registry for the current company
     */
    protected function getConfig(): ContractConfigurationRegistry
    {
        $companyId = auth()->user()->company_id;
        return app(ContractConfigurationRegistry::class, ['companyId' => $companyId]);
    }

    /**
     * Get paginated contracts with filters and search
     */
    public function getContracts(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Contract::with(['client', 'quote', 'template', 'creator'])
            ->where('company_id', auth()->user()->company_id);

        // Apply filters
        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get contracts by status
     */
    public function getContractsByStatus(string $status): Collection
    {
        return Contract::with(['client'])
            ->where('company_id', auth()->user()->company_id)
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new contract with comprehensive error recovery
     */
    public function createContract(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            $createdResources = [
                'contract' => null,
                'schedules' => [],
                'asset_assignments' => []
            ];
            
            try {
                // Validate client exists and belongs to company
                $client = Client::where('company_id', auth()->user()->company_id)
                    ->findOrFail($data['client_id']);

                // Set company_id and created_by
                $data['company_id'] = auth()->user()->company_id;
                $data['created_by'] = auth()->id();

                // Set defaults using dynamic configuration
                $config = $this->getConfig();
                $statuses = $config->getContractStatuses();
                $signatureStatuses = $config->getContractSignatureStatuses();
                
                $data['status'] = $data['status'] ?? (array_search('Draft', $statuses) ?: 'draft');
                $data['signature_status'] = $data['signature_status'] ?? (array_search('Pending', $signatureStatuses) ?: 'pending');
                $data['currency_code'] = $data['currency_code'] ?? 'USD';
                
                $renewalTypes = $config->getRenewalTypes();
                $data['renewal_type'] = $data['renewal_type'] ?? (array_search('Manual Renewal', $renewalTypes) ?: 'manual');

                // Generate contract number if not provided
                if (empty($data['contract_number'])) {
                    $data['contract_number'] = $this->generateContractNumber($data['prefix'] ?? 'CNT');
                }

                // Map schedule data to existing contract fields
                $data = $this->mapScheduleDataToContract($data);

                // Clean up empty values that should be null
                if (isset($data['template_id']) && $data['template_id'] === '') {
                    $data['template_id'] = null;
                }
                if (isset($data['end_date']) && $data['end_date'] === '') {
                    $data['end_date'] = null;
                }
                
                Log::info('Starting contract creation', [
                    'client_id' => $data['client_id'],
                    'contract_type' => $data['contract_type'] ?? 'unknown',
                    'template_id' => $data['template_id'] ?? null,
                    'has_pricing_structure' => !empty($data['pricing_structure']),
                    'has_sla_terms' => !empty($data['sla_terms'])
                ]);

                $contract = Contract::create($data);
                $createdResources['contract'] = $contract;

                Log::info('Contract created successfully', [
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number
                ]);

                // Create contract schedules with error handling
                try {
                    $scheduleIds = $this->createContractSchedules($contract, $data);
                    $createdResources['schedules'] = $scheduleIds;
                    
                    Log::info('Contract schedules created', [
                        'contract_id' => $contract->id,
                        'schedule_count' => count($scheduleIds),
                        'schedule_ids' => $scheduleIds
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to create contract schedules', [
                        'contract_id' => $contract->id,
                        'error' => $e->getMessage(),
                        'template_id' => $data['template_id'] ?? null
                    ]);
                    
                    // Continue without schedules - they can be added later
                    $this->logPartialFailure($contract, 'schedules', $e->getMessage());
                }

                // Process asset assignments if auto-assignment is enabled
                $slaTerms = $data['sla_terms'] ?? [];
                if ($slaTerms && ($slaTerms['auto_assign_new_assets'] ?? false)) {
                    try {
                        $assignmentResults = $this->processAssetAssignments($contract, $data);
                        $createdResources['asset_assignments'] = $assignmentResults['assigned_assets'] ?? [];
                        
                        // Store assignment results in contract metadata
                        $metadata = $contract->metadata ?? [];
                        $metadata['asset_assignment_results'] = $assignmentResults;
                        $contract->update(['metadata' => $metadata]);
                        
                        // Recalculate contract value based on actual assigned assets
                        $this->updateContractValueWithAssets($contract);
                        
                        Log::info('Contract asset assignment completed', [
                            'contract_id' => $contract->id,
                            'total_assigned' => $assignmentResults['total_assigned'],
                            'assignment_breakdown' => $assignmentResults['by_type'],
                            'skipped_reasons' => $assignmentResults['skipped']
                        ]);
                        
                    } catch (\Exception $e) {
                        Log::error('Failed to assign assets to contract', [
                            'contract_id' => $contract->id,
                            'error' => $e->getMessage()
                        ]);
                        
                        // Continue without asset assignments
                        $this->logPartialFailure($contract, 'asset_assignments', $e->getMessage());
                    }
                }

                // Generate contract content with populated variables if template exists
                if ($contract->template_id) {
                    try {
                        $this->generateContractContent($contract);
                        
                        Log::info('Contract content generated successfully', [
                            'contract_id' => $contract->id
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to generate contract content', [
                            'contract_id' => $contract->id,
                            'error' => $e->getMessage()
                        ]);
                        
                        // Continue without content generation
                        $this->logPartialFailure($contract, 'content_generation', $e->getMessage());
                    }
                }

                // Log successful creation activity
                activity()
                    ->performedOn($contract)
                    ->causedBy(auth()->user())
                    ->withProperties(['action' => 'created'])
                    ->log('Contract created');

                return $contract;
                
            } catch (\Exception $e) {
                // Critical failure - cleanup any created resources
                Log::error('Critical failure during contract creation', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'created_resources' => array_map(function($resource) {
                        return is_object($resource) ? get_class($resource) . ':' . ($resource->id ?? 'unknown') : 
                               (is_array($resource) ? count($resource) . ' items' : $resource);
                    }, $createdResources)
                ]);
                
                $this->performCleanup($createdResources);
                
                throw $e;
            }
        });
    }

    /**
     * Update an existing contract
     */
    public function updateContract(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data) {
            // Only allow editing of certain statuses
            $config = $this->getConfig();
            $statuses = $config->getContractStatuses();
            $draftStatusKey = array_search('Draft', $statuses) ?: 'draft';
            $pendingReviewKey = array_search('Pending Review', $statuses) ?: 'pending_review';
            
            if (!in_array($contract->status, [$draftStatusKey, $pendingReviewKey])) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft and pending review contracts can be edited'
                ]);
            }

            $oldData = $contract->toArray();
            $contract->update($data);

            // Log activity
            activity()
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'updated',
                    'old_data' => $oldData,
                    'new_data' => $data
                ])
                ->log('Contract updated');

            return $contract->fresh();
        });
    }

    /**
     * Create contract from quote
     */
    public function createFromQuote(Quote $quote, array $contractData, ?ContractTemplate $template = null): Contract
    {
        return DB::transaction(function () use ($quote, $contractData, $template) {
            // Validate quote belongs to company
            if ($quote->company_id !== auth()->user()->company_id) {
                throw ValidationException::withMessages([
                    'quote' => 'Quote not found or access denied'
                ]);
            }

            // Merge quote data with contract data
            $data = array_merge([
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'title' => $contractData['title'] ?? $quote->title,
                'description' => $contractData['description'] ?? $quote->description,
                'contract_value' => $quote->total,
                'currency_code' => $quote->currency ?? 'USD',
                'start_date' => $contractData['start_date'],
                'end_date' => $contractData['end_date'] ?? null,
                'term_months' => $contractData['term_months'] ?? null,
                'contract_type' => $contractData['contract_type'],
                'template_id' => $template?->id,
            ], $contractData);

            // If using template, apply template data
            if ($template) {
                $data = $this->applyTemplateData($data, $template);
            }

            $contract = $this->createContract($data);

            // Update quote status
            $quote->update(['status' => 'converted_to_contract']);

            return $contract;
        });
    }

    /**
     * Activate a contract
     */
    public function activateContract(Contract $contract, ?Carbon $activationDate = null): Contract
    {
        return DB::transaction(function () use ($contract, $activationDate) {
            $config = $this->getConfig();
            $statuses = $config->getContractStatuses();
            $signedStatusKey = array_search('Signed', $statuses) ?: 'signed';
            
            if ($contract->status !== $signedStatusKey) {
                throw ValidationException::withMessages([
                    'status' => 'Contract must be signed before activation'
                ]);
            }

            $contract->markAsActive($activationDate);

            // Log activity
            activity()
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->withProperties(['activation_date' => $activationDate ?? now()])
                ->log('Contract activated');

            return $contract;
        });
    }

    /**
     * Terminate a contract
     */
    public function terminateContract(Contract $contract, string $reason, ?Carbon $terminationDate = null): Contract
    {
        return DB::transaction(function () use ($contract, $reason, $terminationDate) {
            $config = $this->getConfig();
            $statuses = $config->getContractStatuses();
            $activeStatusKey = array_search('Active', $statuses) ?: 'active';
            $suspendedStatusKey = array_search('Suspended', $statuses) ?: 'suspended';
            
            if (!in_array($contract->status, [$activeStatusKey, $suspendedStatusKey])) {
                throw ValidationException::withMessages([
                    'status' => 'Only active or suspended contracts can be terminated'
                ]);
            }

            $contract->terminate($reason, $terminationDate);

            // Log activity
            activity()
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->withProperties([
                    'reason' => $reason,
                    'termination_date' => $terminationDate ?? now()
                ])
                ->log('Contract terminated');

            return $contract;
        });
    }

    /**
     * Suspend a contract
     */
    public function suspendContract(Contract $contract, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $reason) {
            $config = $this->getConfig();
            $statuses = $config->getContractStatuses();
            $activeStatusKey = array_search('Active', $statuses) ?: 'active';
            
            if ($contract->status !== $activeStatusKey) {
                throw ValidationException::withMessages([
                    'status' => 'Only active contracts can be suspended'
                ]);
            }

            $contract->suspend($reason);

            // Log activity
            activity()
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->withProperties(['reason' => $reason])
                ->log('Contract suspended');

            return $contract;
        });
    }

    /**
     * Reactivate a suspended contract
     */
    public function reactivateContract(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract) {
            $config = $this->getConfig();
            $statuses = $config->getContractStatuses();
            $suspendedStatusKey = array_search('Suspended', $statuses) ?: 'suspended';
            
            if ($contract->status !== $suspendedStatusKey) {
                throw ValidationException::withMessages([
                    'status' => 'Only suspended contracts can be reactivated'
                ]);
            }

            $contract->reactivate();

            // Log activity
            activity()
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->log('Contract reactivated');

            return $contract;
        });
    }

    /**
     * Get contract dashboard statistics
     */
    public function getDashboardStatistics(): array
    {
        $companyId = auth()->user()->company_id;
        $config = $this->getConfig();
        
        // Get dynamic status keys
        $statuses = $config->getContractStatuses();
        $signatureStatuses = $config->getContractSignatureStatuses();
        
        $activeStatusKey = array_search('Active', $statuses) ?: 'active';
        $draftStatusKey = array_search('Draft', $statuses) ?: 'draft';
        $pendingSignatureKey = array_search('Pending', $signatureStatuses) ?: 'pending';

        return [
            'total_contracts' => Contract::where('company_id', $companyId)->count(),
            'active_contracts' => Contract::where('company_id', $companyId)
                ->where('status', $activeStatusKey)->count(),
            'draft_contracts' => Contract::where('company_id', $companyId)
                ->where('status', $draftStatusKey)->count(),
            'pending_signature' => Contract::where('company_id', $companyId)
                ->where('signature_status', $pendingSignatureKey)->count(),
            'expiring_soon' => Contract::where('company_id', $companyId)
                ->expiringSoon(30)->count(),
            'total_value' => Contract::where('company_id', $companyId)->sum('contract_value'),
            'monthly_recurring_revenue' => $this->calculateMonthlyRecurringRevenue(),
            'annual_contract_value' => $this->calculateAnnualContractValue(),
        ];
    }

    /**
     * Get contracts expiring soon
     */
    public function getExpiringContracts(int $days = 30): Collection
    {
        return Contract::with(['client'])
            ->where('company_id', auth()->user()->company_id)
            ->expiringSoon($days)
            ->orderBy('end_date', 'asc')
            ->get();
    }

    /**
     * Get contracts due for renewal
     */
    public function getContractsDueForRenewal(int $daysBefore = 30): Collection
    {
        return Contract::with(['client'])
            ->where('company_id', auth()->user()->company_id)
            ->dueForRenewal($daysBefore)
            ->orderBy('end_date', 'asc')
            ->get();
    }

    /**
     * Search contracts
     */
    public function searchContracts(string $query, int $limit = 25): Collection
    {
        return Contract::with(['client'])
            ->where('company_id', auth()->user()->company_id)
            ->search($query)
            ->limit($limit)
            ->get();
    }

    /**
     * Delete a contract (soft delete)
     */
    public function deleteContract(Contract $contract): bool
    {
        return DB::transaction(function () use ($contract) {
            // Only allow deletion of draft contracts
            $config = $this->getConfig();
            $statuses = $config->getContractStatuses();
            $draftStatusKey = array_search('Draft', $statuses) ?: 'draft';
            
            if ($contract->status !== $draftStatusKey) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft contracts can be deleted'
                ]);
            }

            $result = $contract->delete();

            // Log activity
            activity()
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->log('Contract deleted');

            return $result;
        });
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['contract_type'])) {
            $query->where('contract_type', $filters['contract_type']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['signature_status'])) {
            $query->where('signature_status', $filters['signature_status']);
        }

        if (!empty($filters['start_date_from'])) {
            $query->where('start_date', '>=', $filters['start_date_from']);
        }

        if (!empty($filters['start_date_to'])) {
            $query->where('start_date', '<=', $filters['start_date_to']);
        }

        if (!empty($filters['end_date_from'])) {
            $query->where('end_date', '>=', $filters['end_date_from']);
        }

        if (!empty($filters['end_date_to'])) {
            $query->where('end_date', '<=', $filters['end_date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
    }

    /**
     * Generate contract number
     */
    protected function generateContractNumber(string $prefix = 'CNT'): string
    {
        $companyId = auth()->user()->company_id;
        
        // Get all contract numbers with this prefix to find the highest number
        // Use withoutGlobalScope and withTrashed to ensure we see all contracts for this company
        $contractNumbers = Contract::withoutGlobalScope('company')
            ->withTrashed()
            ->where('company_id', $companyId)
            ->where('contract_number', 'like', $prefix . '-%')
            ->pluck('contract_number')
            ->toArray();

        $maxNumber = 0;
        foreach ($contractNumbers as $contractNumber) {
            if (preg_match('/' . preg_quote($prefix) . '-(\d+)/', $contractNumber, $matches)) {
                $currentNumber = (int)$matches[1];
                if ($currentNumber > $maxNumber) {
                    $maxNumber = $currentNumber;
                }
            }
        }

        $nextNumber = $maxNumber + 1;
        
        // Keep trying until we find a unique number (in case of race conditions)
        do {
            $paddedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $contractNumber = $prefix . '-' . $paddedNumber;
            
            $exists = Contract::withoutGlobalScope('company')
                ->withTrashed()
                ->where('company_id', $companyId)
                ->where('contract_number', $contractNumber)
                ->exists();
                
            if (!$exists) {
                return $contractNumber;
            }
            
            $nextNumber++;
        } while ($nextNumber < 10000); // Failsafe to prevent infinite loop

        // If we somehow can't find a unique number, use timestamp
        return $prefix . '-' . now()->format('YmdHis');
    }

    /**
     * Map schedule data to existing contract fields
     */
    protected function mapScheduleDataToContract(array $data): array
    {
        // Keep pricing_schedule data for ContractSchedule creation
        // Note: Data will be stored in Schedule B's schedule_data field instead of Contract.pricing_structure

        // Keep infrastructure_schedule data for ContractSchedule creation
        // Note: Data will be stored in Schedule A's schedule_data field instead of Contract.sla_terms

        // Keep additional_terms data for ContractSchedule creation
        // Note: Data will be stored in Schedule C's schedule_data field instead of Contract.custom_clauses
        
        // Extract specific terms to dedicated contract columns
        if (!empty($data['additional_terms'])) {
            $additionalTerms = $data['additional_terms'];
            
            if (isset($additionalTerms['disputeResolution']['method'])) {
                $data['dispute_resolution'] = $additionalTerms['disputeResolution']['method'];
            }
            if (isset($additionalTerms['disputeResolution']['governingLaw'])) {
                $data['governing_law'] = $additionalTerms['disputeResolution']['governingLaw'];
            }
        }

        // Store template-specific schedule data in metadata
        if (!empty($data['billing_config']) || !empty($data['variable_values']) || !empty($data['template_id'])) {
            // Ensure we have default variables for contract wizard contracts
            $variableValues = $data['variable_values'] ?? [];
            $billingConfig = $data['billing_config'] ?? [];
            
            // Only use defaults if we have absolutely no wizard form data
            // Check for actual wizard-submitted data in multiple places
            $hasPricingData = !empty($data['pricing_schedule']) || !empty($data['sla_terms']);
            $hasInfrastructureData = !empty($data['infrastructure_schedule']);
            $hasActualWizardData = $hasPricingData || $hasInfrastructureData || 
                                  (!empty($variableValues) && count($variableValues) > 0) ||
                                  (!empty($billingConfig) && !empty($billingConfig['model']));
            
            // If we have no actual wizard data and we have a template, populate with defaults
            if (!$hasActualWizardData && !empty($data['template_id'])) {
                \Log::info('No wizard form data detected, using defaults', [
                    'template_id' => $data['template_id'],
                    'has_pricing_data' => $hasPricingData,
                    'has_infrastructure_data' => $hasInfrastructureData
                ]);
                $variableValues = $this->generateDefaultVariables($data);
                $billingConfig = $this->generateDefaultBillingConfig($data);
            } else {
                \Log::info('Wizard form data detected, preserving user selections', [
                    'variable_values_count' => count($variableValues),
                    'billing_config_fields' => array_keys($billingConfig),
                    'has_pricing_data' => $hasPricingData
                ]);
            }
            
            $data['metadata'] = [
                'billing_config' => $billingConfig,
                'variable_values' => $variableValues,
                'template_type' => $data['template_type'] ?? null,
                'schedule_type' => $this->determineScheduleType($data),
                'created_via' => 'contract_wizard',
            ];
        }

        // Clean up only the processed fields, keep schedule data for ContractSchedule creation
        unset($data['billing_config'], $data['variable_values']);

        return $data;
    }

    /**
     * Determine schedule type from template data
     */
    protected function determineScheduleType(array $data): string
    {
        // Check for specialized schedule types first
        if (!empty($data['telecom_schedule'])) {
            return 'telecom';
        }
        if (!empty($data['hardware_schedule'])) {
            return 'hardware';
        }
        if (!empty($data['compliance_schedule'])) {
            return 'compliance';
        }
        
        // Check template type as fallback
        if (!empty($data['template_id'])) {
            $template = \App\Domains\Contract\Models\ContractTemplate::find($data['template_id']);
            if ($template) {
                $templateType = $template->template_type ?? $template->type;
                if (in_array($templateType, ['hosted_pbx', 'sip_trunking', 'unified_communications', 'voip'])) {
                    return 'telecom';
                }
                if (in_array($templateType, ['hardware_procurement', 'software_licensing', 'var'])) {
                    return 'hardware';
                }
                if (in_array($templateType, ['compliance', 'security_audit', 'risk_assessment'])) {
                    return 'compliance';
                }
            }
        }
        
        return 'infrastructure';
    }

    /**
     * Process asset assignments for the contract
     */
    protected function processAssetAssignments(Contract $contract, array $data): array
    {
        $supportedAssetTypes = $data['sla_terms']['supported_asset_types'] ?? [];
        $clientId = $contract->client_id;
        $assignmentResults = [
            'total_assigned' => 0,
            'by_type' => [],
            'skipped' => [],
            'errors' => []
        ];
        
        if (empty($supportedAssetTypes)) {
            $assignmentResults['errors'][] = 'No supported asset types specified for assignment';
            return $assignmentResults;
        }

        Log::info('Starting asset assignment process', [
            'contract_id' => $contract->id,
            'client_id' => $clientId,
            'supported_types' => $supportedAssetTypes
        ]);

        foreach ($supportedAssetTypes as $assetType) {
            // Find assets for this specific type
            $assetsQuery = Asset::where('company_id', $contract->company_id)
                ->where('client_id', $clientId)
                ->where('type', $assetType)
                ->whereNull('supporting_contract_id'); // Only assign assets not already under contract

            $availableAssets = $assetsQuery->get();
            $typeAssignedCount = 0;
            
            if ($availableAssets->count() > 0) {
                // Assign assets of this type to the contract
                $typeAssignedCount = $assetsQuery->update([
                    'supporting_contract_id' => $contract->id,
                    'auto_assigned_support' => true,
                    'support_assigned_at' => now(),
                    'support_assigned_by' => auth()->id(),
                    'support_status' => 'covered',
                    'support_level' => $this->determineSupportLevel($data),
                    'support_evaluation_rules' => json_encode([
                        'asset_type' => $assetType,
                        'service_tier' => $data['sla_terms']['serviceTier'] ?? 'Standard',
                        'auto_assigned' => true,
                        'assigned_via' => 'contract_wizard',
                        'assignment_date' => now()->toISOString()
                    ]),
                    'support_last_evaluated_at' => now(),
                ]);
                
                $assignmentResults['by_type'][$assetType] = [
                    'assigned' => $typeAssignedCount,
                    'available' => $availableAssets->count()
                ];
                
                Log::info('Assets assigned for type', [
                    'asset_type' => $assetType,
                    'count' => $typeAssignedCount,
                    'contract_id' => $contract->id
                ]);
            } else {
                // Check if assets exist but are already assigned
                $existingAssets = Asset::where('company_id', $contract->company_id)
                    ->where('client_id', $clientId)
                    ->where('type', $assetType)
                    ->count();
                    
                if ($existingAssets > 0) {
                    $assignmentResults['skipped'][$assetType] = 'Assets already under contract';
                } else {
                    $assignmentResults['skipped'][$assetType] = 'No assets of this type found';
                }
            }
            
            $assignmentResults['total_assigned'] += $typeAssignedCount;
        }

        // Log comprehensive assignment activity
        activity()
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => 'assets_assigned',
                'assignment_results' => $assignmentResults,
                'supported_asset_types' => $supportedAssetTypes,
                'client_id' => $clientId,
                'support_level' => $this->determineSupportLevel($data)
            ])
            ->log("Asset assignment completed: {$assignmentResults['total_assigned']} assets assigned");

        return $assignmentResults;
    }

    /**
     * Determine support level from contract data
     */
    protected function determineSupportLevel(array $data): string
    {
        $serviceTier = $data['sla_terms']['service_tier'] ?? '';
        
        $tierMapping = [
            'bronze' => 'basic',
            'silver' => 'standard', 
            'gold' => 'premium',
            'platinum' => 'enterprise'
        ];

        return $tierMapping[$serviceTier] ?? 'standard';
    }

    /**
     * Update contract value based on assigned assets
     */
    protected function updateContractValueWithAssets(Contract $contract): void
    {
        if (!$contract->pricing_structure || !isset($contract->pricing_structure['asset_pricing'])) {
            return;
        }

        $pricing = $contract->pricing_structure;
        $totalValue = 0;

        // Add base pricing (handle empty strings)
        $recurringMonthly = $pricing['recurring_monthly'] ?? '';
        $oneTime = $pricing['one_time'] ?? '';
        
        $totalValue += $recurringMonthly !== '' ? (float) $recurringMonthly : 0;
        $totalValue += $oneTime !== '' ? (float) $oneTime : 0;

        // Calculate asset-based pricing with actual counts
        if (isset($pricing['asset_pricing'])) {
            foreach ($pricing['asset_pricing'] as $assetType => $config) {
                if (!empty($config['enabled']) && !empty($config['price']) && $config['price'] !== '') {
                    $assetCount = $contract->supportedAssets()->where('type', $assetType)->count();
                    $totalValue += (float) $config['price'] * $assetCount;
                }
            }
        }

        // Add template-specific pricing (handle empty strings)
        if (isset($pricing['telecom_pricing'])) {
            foreach ($pricing['telecom_pricing'] as $key => $price) {
                if ($price !== '') {
                    $totalValue += (float) $price;
                }
            }
        }

        if (isset($pricing['compliance_pricing']['frameworkSetup'])) {
            foreach ($pricing['compliance_pricing']['frameworkSetup'] as $framework => $setupFee) {
                if ($setupFee !== '') {
                    $totalValue += (float) $setupFee;
                }
            }
        }

        // Update the contract value
        $contract->update(['contract_value' => $totalValue]);

        // Log the value update
        activity()
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => 'contract_value_updated',
                'old_value' => $contract->getOriginal('contract_value'),
                'new_value' => $totalValue,
            ])
            ->log('Contract value updated based on asset assignments');
    }

    /**
     * Create contract schedules from wizard data
     */
    protected function createContractSchedules(Contract $contract, array $data): array
    {
        $createdScheduleIds = [];
        $scheduleType = $this->determineScheduleType($data);
        
        try {
            // Create Schedule A (Infrastructure/Service Configuration)
            if (!empty($data['infrastructure_schedule'])) {
                $scheduleA = $this->createScheduleA($contract, $data, $scheduleType);
                if ($scheduleA) {
                    $createdScheduleIds[] = $scheduleA->id;
                }
            }
            
            // Create Schedule B (Pricing & Fees)
            if (!empty($data['pricing_schedule'])) {
                $scheduleB = $this->createScheduleB($contract, $data, $scheduleType);
                if ($scheduleB) {
                    $createdScheduleIds[] = $scheduleB->id;
                }
            }
            
            // Create Schedule C (Additional Terms)
            if (!empty($data['additional_terms'])) {
                $scheduleC = $this->createScheduleC($contract, $data);
                if ($scheduleC) {
                    $createdScheduleIds[] = $scheduleC->id;
                }
            }
            
            // Create specialized schedules based on template type
            if (!empty($data['telecom_schedule'])) {
                Log::info('Creating telecom schedule', [
                    'contract_id' => $contract->id,
                    'telecom_data_keys' => array_keys($data['telecom_schedule'])
                ]);
                $telecomSchedule = $this->createTelecomSchedule($contract, $data);
                if ($telecomSchedule) {
                    $createdScheduleIds[] = $telecomSchedule->id;
                    Log::info('Telecom schedule created successfully', [
                        'schedule_id' => $telecomSchedule->id,
                        'title' => $telecomSchedule->title
                    ]);
                }
            }
            
            if (!empty($data['hardware_schedule'])) {
                Log::info('Creating hardware schedule', [
                    'contract_id' => $contract->id,
                    'hardware_data_keys' => array_keys($data['hardware_schedule'])
                ]);
                $hardwareSchedule = $this->createHardwareSchedule($contract, $data);
                if ($hardwareSchedule) {
                    $createdScheduleIds[] = $hardwareSchedule->id;
                    Log::info('Hardware schedule created successfully', [
                        'schedule_id' => $hardwareSchedule->id,
                        'title' => $hardwareSchedule->title
                    ]);
                }
            }
            
            if (!empty($data['compliance_schedule'])) {
                Log::info('Creating compliance schedule', [
                    'contract_id' => $contract->id,
                    'compliance_data_keys' => array_keys($data['compliance_schedule'])
                ]);
                $complianceSchedule = $this->createComplianceSchedule($contract, $data);
                if ($complianceSchedule) {
                    $createdScheduleIds[] = $complianceSchedule->id;
                    Log::info('Compliance schedule created successfully', [
                        'schedule_id' => $complianceSchedule->id,
                        'title' => $complianceSchedule->title
                    ]);
                }
            }
            
            Log::info('Contract schedules created successfully', [
                'contract_id' => $contract->id,
                'schedule_ids' => $createdScheduleIds,
                'schedule_count' => count($createdScheduleIds)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creating contract schedules', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'partial_schedule_ids' => $createdScheduleIds
            ]);
            
            // Clean up any partially created schedules
            if (!empty($createdScheduleIds)) {
                ContractSchedule::whereIn('id', $createdScheduleIds)->delete();
                Log::info('Cleaned up partially created schedules', [
                    'deleted_schedule_ids' => $createdScheduleIds
                ]);
            }
            
            throw $e;
        }
        
        return $createdScheduleIds;
    }

    /**
     * Create Schedule A - Service Configuration
     */
    protected function createScheduleA(Contract $contract, array $data, string $scheduleType): ?ContractSchedule
    {
        $title = match($scheduleType) {
            'telecom' => 'Schedule A - Telecommunications & Service Levels',
            'hardware' => 'Schedule A - Hardware Products & Services',
            'compliance' => 'Schedule A - Compliance Framework & Requirements',
            default => 'Schedule A - Infrastructure & SLA'
        };

        $description = match($scheduleType) {
            'telecom' => 'Telecommunications services, QoS metrics, and compliance requirements',
            'hardware' => 'Hardware procurement, installation services, and warranty terms',
            'compliance' => 'Regulatory compliance requirements and audit schedules',
            default => 'Infrastructure coverage, pricing, and additional terms'
        };

        // Build schedule_data from infrastructure_schedule wizard data
        $infraSchedule = $data['infrastructure_schedule'] ?? [];
        $scheduleData = [];
        
        if (!empty($infraSchedule)) {
            $scheduleData = [
                'supportedAssetTypes' => $infraSchedule['supportedAssetTypes'] ?? [],
                'sla' => [
                    'serviceTier' => $infraSchedule['sla']['serviceTier'] ?? 'bronze',
                    'responseTimeHours' => $infraSchedule['sla']['responseTimeHours'] ?? 0,
                    'resolutionTimeHours' => $infraSchedule['sla']['resolutionTimeHours'] ?? 0,
                    'uptimePercentage' => $infraSchedule['sla']['uptimePercentage'] ?? 0,
                ],
                'coverageRules' => [
                    'businessHours' => $infraSchedule['coverageRules']['businessHours'] ?? '8x5',
                    'emergencySupport' => $infraSchedule['coverageRules']['emergencySupport'] ?? 'included',
                    'includeRemoteSupport' => $infraSchedule['coverageRules']['includeRemoteSupport'] ?? true,
                    'includeOnsiteSupport' => $infraSchedule['coverageRules']['includeOnsiteSupport'] ?? false,
                    'autoAssignNewAssets' => $infraSchedule['coverageRules']['autoAssignNewAssets'] ?? false,
                ],
                'exclusions' => $infraSchedule['exclusions'] ?? [
                    'assetTypes' => '',
                    'services' => '',
                ],
            ];
        }

        return ContractSchedule::create([
            'company_id' => $contract->company_id,
            'contract_id' => $contract->id,
            'schedule_type' => ContractSchedule::TYPE_INFRASTRUCTURE,
            'schedule_letter' => 'A',
            'title' => $title,
            'description' => $description,
            'content' => $this->generateScheduleAContent($data, $scheduleType),
            'variables' => $this->extractScheduleVariables($scheduleData),
            'variable_values' => $scheduleData,
            'supported_asset_types' => $scheduleData['supportedAssetTypes'] ?? [],
            'sla_terms' => $scheduleData['sla'] ?? [],
            'coverage_rules' => $scheduleData['coverageRules'] ?? [],
            'status' => 'active',
            'effective_date' => $contract->start_date,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create Schedule B - Pricing & Fees
     */
    protected function createScheduleB(Contract $contract, array $data, string $scheduleType): ?ContractSchedule
    {
        // Build schedule_data from pricing_schedule wizard data
        $pricingSchedule = $data['pricing_schedule'] ?? [];
        $scheduleData = [];
        
        if (!empty($pricingSchedule)) {
            $scheduleData = [
                'billingModel' => $pricingSchedule['billingModel'] ?? 'per_asset',
                'basePricing' => [
                    'monthlyBase' => $pricingSchedule['basePricing']['monthlyBase'] ?? '',
                    'setupFee' => $pricingSchedule['basePricing']['setupFee'] ?? '',
                    'hourlyRate' => $pricingSchedule['basePricing']['hourlyRate'] ?? '',
                ],
                'perUnitPricing' => $pricingSchedule['perUnitPricing'] ?? [],
                'assetTypePricing' => $pricingSchedule['assetTypePricing'] ?? [],
                'telecomPricing' => $pricingSchedule['telecomPricing'] ?? [],
                'hardwarePricing' => $pricingSchedule['hardwarePricing'] ?? [],
                'compliancePricing' => $pricingSchedule['compliancePricing'] ?? [],
                'tiers' => $pricingSchedule['tiers'] ?? [],
                'additionalFees' => $pricingSchedule['additionalFees'] ?? [],
                'paymentTerms' => $pricingSchedule['paymentTerms'] ?? [
                    'billingFrequency' => 'monthly',
                    'terms' => 'net_30',
                ],
            ];
        }

        return ContractSchedule::create([
            'company_id' => $contract->company_id,
            'contract_id' => $contract->id,
            'schedule_type' => ContractSchedule::TYPE_PRICING,
            'schedule_letter' => 'B',
            'title' => 'Schedule B - Pricing & Fees',
            'description' => 'Pricing structure, billing model, and fee schedules',
            'content' => $this->generateScheduleBContent($data),
            'variables' => $this->extractPricingVariables($scheduleData),
            'variable_values' => $scheduleData,
            'pricing_structure' => $scheduleData,
            'status' => 'active',
            'effective_date' => $contract->start_date,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create Schedule C - Additional Terms
     */
    protected function createScheduleC(Contract $contract, array $data): ?ContractSchedule
    {
        // Build schedule_data from additional_terms wizard data
        $additionalTerms = $data['additional_terms'] ?? [];
        $scheduleData = [];
        
        if (!empty($additionalTerms)) {
            $scheduleData = [
                'termination' => $additionalTerms['termination'] ?? [],
                'liability' => $additionalTerms['liability'] ?? [],
                'dataProtection' => $additionalTerms['dataProtection'] ?? [],
                'disputeResolution' => $additionalTerms['disputeResolution'] ?? [],
                'customClauses' => $additionalTerms['customClauses'] ?? [],
                'amendments' => $additionalTerms['amendments'] ?? [],
            ];
        }

        return ContractSchedule::create([
            'company_id' => $contract->company_id,
            'contract_id' => $contract->id,
            'schedule_type' => ContractSchedule::TYPE_ADDITIONAL,
            'schedule_letter' => 'C',
            'title' => 'Schedule C - Additional Terms & Conditions',
            'description' => 'Termination clauses, liability terms, and dispute resolution',
            'content' => $this->generateScheduleCContent($data),
            'variables' => $this->extractTermsVariables($scheduleData),
            'variable_values' => $scheduleData,
            'status' => 'active',
            'effective_date' => $contract->start_date,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Generate Schedule A content based on type
     */
    protected function generateScheduleAContent(array $data, string $scheduleType): string
    {
        $slaTerms = $data['sla_terms'] ?? [];
        
        // Start with template content
        $template = $this->getScheduleATemplate($scheduleType);
        
        // Process variables for substitution
        $variables = array_merge($slaTerms, [
            'schedule_title' => $this->getScheduleATitle($scheduleType),
            'supported_asset_types' => $this->formatAssetTypes($slaTerms['supported_asset_types'] ?? []),
            'response_times' => $this->formatResponseTimes($slaTerms['response_times'] ?? []),
            'coverage_hours' => $this->formatCoverageHours($slaTerms['coverage_hours'] ?? []),
            'service_tier' => $slaTerms['serviceTier'] ?? 'Standard',
            'uptime_target' => $slaTerms['uptimePercentage'] ?? '99.5'
        ]);
        
        return $this->processTemplate($template, $variables);
    }

    /**
     * Generate Schedule B pricing content
     */
    protected function generateScheduleBContent(array $data): string
    {
        $pricing = $data['pricing_structure'] ?? [];
        
        // Get pricing template
        $template = $this->getScheduleBTemplate();
        
        // Process variables for substitution
        $variables = [
            'billing_model' => ucfirst(str_replace('_', ' ', $pricing['billing_model'] ?? 'fixed')),
            'monthly_fee' => number_format((float)($pricing['recurring_monthly'] ?? 0), 2),
            'setup_fee' => number_format((float)($pricing['one_time'] ?? 0), 2),
            'asset_pricing_table' => $this->formatAssetPricingTable($pricing['asset_pricing'] ?? []),
            'telecom_pricing' => $this->formatTelecomPricing($pricing['telecom_pricing'] ?? []),
            'compliance_pricing' => $this->formatCompliancePricing($pricing['compliance_pricing'] ?? []),
            'per_user_pricing' => number_format((float)($pricing['per_user'] ?? 0), 2)
        ];
        
        return $this->processTemplate($template, $variables);
    }

    /**
     * Generate Schedule C terms content
     */
    protected function generateScheduleCContent(array $data): string
    {
        // Get terms template
        $template = $this->getScheduleCTemplate();
        
        // Process variables for substitution
        $customClauses = $data['custom_clauses'] ?? [];
        $variables = [
            'termination_notice' => $customClauses['termination']['noticePeriod'] ?? '30 days',
            'early_termination_fee' => number_format((float)($customClauses['termination']['earlyTerminationFee'] ?? 0), 2),
            'dispute_method' => $data['dispute_resolution'] ?? 'Binding arbitration',
            'governing_law' => $data['governing_law'] ?? 'State of Client Location',
            'data_retention' => $customClauses['data_retention'] ?? 'Standard retention policy applies',
            'backup_policy' => $customClauses['backup_policy'] ?? 'Industry standard backup procedures',
            'security_requirements' => $customClauses['security_requirements'] ?? 'Standard security protocols'
        ];
        
        return $this->processTemplate($template, $variables);
    }

    /**
     * Generate infrastructure schedule content
     */
    protected function generateInfrastructureScheduleContent(array $slaTerms): string
    {
        $content = "## Supported Asset Types\n";
        if (!empty($slaTerms['supported_asset_types'])) {
            foreach ($slaTerms['supported_asset_types'] as $assetType) {
                $content .= "- " . ucfirst(str_replace('_', ' ', $assetType)) . "\n";
            }
        }
        
        $content .= "\n## Service Level Agreement\n";
        if (!empty($slaTerms['service_tier'])) {
            $content .= "**Service Tier**: " . ucfirst($slaTerms['service_tier']) . "\n";
        }
        if (!empty($slaTerms['response_time_hours'])) {
            $content .= "**Response Time**: " . $slaTerms['response_time_hours'] . " hours\n";
        }
        if (!empty($slaTerms['resolution_time_hours'])) {
            $content .= "**Resolution Time**: " . $slaTerms['resolution_time_hours'] . " hours\n";
        }
        if (!empty($slaTerms['uptime_percentage'])) {
            $content .= "**Uptime Guarantee**: " . $slaTerms['uptime_percentage'] . "%\n";
        }
        
        return $content;
    }

    /**
     * Generate telecom schedule content
     */
    protected function generateTelecomScheduleContent(array $slaTerms): string
    {
        // Implementation for telecom-specific content
        return $this->generateInfrastructureScheduleContent($slaTerms);
    }

    /**
     * Generate hardware schedule content  
     */
    protected function generateHardwareScheduleContent(array $slaTerms): string
    {
        // Implementation for hardware-specific content
        return $this->generateInfrastructureScheduleContent($slaTerms);
    }

    /**
     * Generate compliance schedule content
     */
    protected function generateComplianceScheduleContent(array $slaTerms): string
    {
        // Implementation for compliance-specific content
        return $this->generateInfrastructureScheduleContent($slaTerms);
    }

    /**
     * Extract variables from schedule data
     */
    protected function extractScheduleVariables(array $data): array
    {
        $variables = [];
        foreach ($data as $key => $value) {
            if (is_string($value) && !empty($value)) {
                $variables[] = [
                    'name' => $key,
                    'type' => 'string',
                    'required' => true
                ];
            }
        }
        return $variables;
    }

    /**
     * Extract pricing variables
     */
    protected function extractPricingVariables(array $data): array
    {
        return $this->extractScheduleVariables($data);
    }

    /**
     * Extract terms variables
     */
    protected function extractTermsVariables(array $data): array
    {
        return $this->extractScheduleVariables($data);
    }

    /**
     * Get Schedule A title based on type
     */
    protected function getScheduleATitle(string $scheduleType): string
    {
        return match($scheduleType) {
            'telecom' => 'Telecommunications & Service Levels',
            'hardware' => 'Hardware Products & Services', 
            'compliance' => 'Compliance Framework & Requirements',
            default => 'Infrastructure & SLA'
        };
    }

    /**
     * Apply template data to contract
     */
    protected function applyTemplateData(array $data, ContractTemplate $template): array
    {
        // Apply template defaults
        $data['terms_and_conditions'] = $data['terms_and_conditions'] ?? $template->default_terms;
        $data['payment_terms'] = $data['payment_terms'] ?? $template->default_payment_terms;
        $data['sla_terms'] = $data['sla_terms'] ?? $template->default_sla_terms;
        $data['termination_clause'] = $data['termination_clause'] ?? $template->termination_clause;
        $data['liability_clause'] = $data['liability_clause'] ?? $template->liability_clause;
        $data['confidentiality_clause'] = $data['confidentiality_clause'] ?? $template->confidentiality_clause;
        
        return $data;
    }

    /**
     * Calculate monthly recurring revenue
     */
    protected function calculateMonthlyRecurringRevenue(): float
    {
        $companyId = auth()->user()->company_id;
        
        return Contract::where('company_id', $companyId)
            ->active()
            ->get()
            ->sum(function ($contract) {
                return $contract->getMonthlyRecurringRevenue();
            });
    }

    /**
     * Calculate annual contract value
     */
    protected function calculateAnnualContractValue(): float
    {
        $companyId = auth()->user()->company_id;
        
        return Contract::where('company_id', $companyId)
            ->active()
            ->get()
            ->sum(function ($contract) {
                return $contract->getAnnualValue();
            });
    }

    /**
     * Create contract from dynamic builder
     */
    public function createFromBuilder(array $data, \App\Models\User $user): Contract
    {
        return DB::transaction(function () use ($data, $user) {
            // Extract contract data and component assignments
            $contractData = $data['contract'] ?? $data;
            $componentAssignments = $data['components'] ?? [];

            // Validate client access
            $client = Client::where('company_id', $user->company_id)
                ->findOrFail($contractData['client_id']);

            // Prepare contract data
            $contractData['company_id'] = $user->company_id;
            $contractData['created_by'] = $user->id;
            
            // Set dynamic status defaults
            $config = app(ContractConfigurationRegistry::class, ['companyId' => $user->company_id]);
            $statuses = $config->getContractStatuses();
            $signatureStatuses = $config->getContractSignatureStatuses();
            
            $contractData['status'] = array_search('Draft', $statuses) ?: 'draft';
            $contractData['signature_status'] = array_search('Pending', $signatureStatuses) ?: 'pending';
            $contractData['currency_code'] = $contractData['currency_code'] ?? 'USD';
            $contractData['is_programmable'] = true;

            // Generate contract number
            if (empty($contractData['contract_number'])) {
                $contractData['contract_number'] = $this->generateContractNumber('PRG');
            }

            // Clean up empty values that should be null
            if (isset($contractData['template_id']) && $contractData['template_id'] === '') {
                $contractData['template_id'] = null;
            }
            if (isset($contractData['end_date']) && $contractData['end_date'] === '') {
                $contractData['end_date'] = null;
            }

            // Create the contract
            $contract = Contract::create($contractData);

            // Create component assignments
            foreach ($componentAssignments as $index => $assignmentData) {
                $component = \App\Domains\Contract\Models\ContractComponent::where('company_id', $user->company_id)
                    ->findOrFail($assignmentData['component']['id']);

                \App\Domains\Contract\Models\ContractComponentAssignment::create([
                    'contract_id' => $contract->id,
                    'component_id' => $component->id,
                    'configuration' => [],
                    'variable_values' => $assignmentData['variable_values'] ?? [],
                    'pricing_override' => $assignmentData['has_pricing_override'] 
                        ? $assignmentData['pricing_override'] 
                        : null,
                    'status' => 'active',
                    'sort_order' => $index + 1,
                    'assigned_by' => $user->id,
                    'assigned_at' => now(),
                ]);
            }

            // Calculate and update total value
            $totalValue = $contract->componentAssignments()
                ->with('component')
                ->get()
                ->sum(function ($assignment) {
                    return $assignment->calculatePrice();
                });

            $contract->update(['contract_value' => $totalValue]);

            // Log activity
            activity()
                ->performedOn($contract)
                ->causedBy($user)
                ->withProperties([
                    'action' => 'created_from_builder',
                    'component_count' => count($componentAssignments),
                    'total_value' => $totalValue
                ])
                ->log('Contract created using dynamic builder');

            return $contract;
        });
    }

    /**
     * Process template with variable substitution
     */
    protected function processTemplate(string $template, array $variables): string
    {
        $content = $template;
        
        foreach ($variables as $key => $value) {
            // Convert value to string if it's not already
            $stringValue = is_array($value) ? json_encode($value) : (string)$value;
            
            // Replace {{key}} with value
            $content = str_replace('{{' . $key . '}}', $stringValue, $content);
        }
        
        // Clean up any remaining unreplaced variables
        $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);
        
        return $content;
    }

    /**
     * Get Schedule A template based on type
     */
    protected function getScheduleATemplate(string $scheduleType): string
    {
        switch ($scheduleType) {
            case 'telecom':
                return $this->getTelecomScheduleTemplate();
            case 'hardware':
                return $this->getHardwareScheduleTemplate();
            case 'compliance':
                return $this->getComplianceScheduleTemplate();
            default:
                return $this->getInfrastructureScheduleTemplate();
        }
    }

    /**
     * Infrastructure schedule template
     */
    protected function getInfrastructureScheduleTemplate(): string
    {
        return '# SCHEDULE A - INFRASTRUCTURE SERVICES & SERVICE LEVEL AGREEMENT

**Document Version:** 2.0  
**Effective Date:** ' . now()->format('F d, Y') . '  
**Review Date:** ' . now()->addYear()->format('F d, Y') . '

---

## 1. SERVICE OVERVIEW

This Schedule A defines the infrastructure services, service level agreements, and operational parameters for managed IT services provided under this Agreement.

## 2. SERVICE LEVEL AGREEMENT (SLA)

### 2.1 Service Classifications
- **{{service_tier}} Tier Service**: Premium managed infrastructure support
- **Target Availability**: {{uptime_target}}% monthly uptime commitment
- **Service Hours**: {{coverage_hours}}
- **Emergency Support**: 24/7/365 critical issue response

### 2.2 Response Time Commitments
| **Priority Level** | **Definition** | **Response Time** | **Resolution Target** |
|-------------------|----------------|-------------------|---------------------|
| **Critical (P1)** | System down, business stopped | {{response_time_hours}} hours | {{resolution_time_hours}} hours |
| **High (P2)** | Major functionality impaired | 4 business hours | 24 business hours |
| **Medium (P3)** | Minor issues, workarounds available | 8 business hours | 72 business hours |
| **Low (P4)** | General questions, enhancements | 24 business hours | 5 business days |

## 3. SUPPORTED INFRASTRUCTURE

### 3.1 Covered Asset Types
The following infrastructure components are included in this managed service agreement:

{{supported_asset_types}}

### 3.2 Service Scope Per Asset Type
- **Servers & Workstations**: OS management, security patching, performance monitoring, backup verification
- **Network Infrastructure**: Configuration management, performance monitoring, security assessment, firmware updates
- **Storage Systems**: Capacity monitoring, performance optimization, backup integrity validation
- **Virtualization Platforms**: Hypervisor management, resource optimization, disaster recovery planning

## 4. OPERATIONAL SERVICES

### 4.1 Proactive Management
- **24/7 Monitoring**: Continuous monitoring of all managed infrastructure
- **Automated Alerting**: Real-time notification of issues and performance degradation
- **Preventive Maintenance**: Scheduled maintenance windows with advance notice
- **Security Monitoring**: Continuous security event monitoring and threat detection
- **Performance Optimization**: Regular performance analysis and tuning recommendations

### 4.2 Reactive Support
- **Incident Response**: Immediate response to alerts and user-reported issues
- **Problem Resolution**: Root cause analysis and permanent problem resolution
- **Emergency Support**: After-hours critical issue response via emergency hotline
- **Escalation Management**: Structured escalation process for complex issues

### 4.3 Reporting & Communication
- **Monthly Service Reports**: Detailed performance metrics and incident summaries
- **Quarterly Business Reviews**: Strategic planning and service optimization meetings
- **Real-time Dashboard**: 24/7 access to infrastructure health and performance data
- **Change Notifications**: Advance notice of all planned maintenance and changes

## 5. SERVICE EXCLUSIONS

The following services are **NOT** included unless specifically contracted separately:
- Physical hardware replacement and warranty services
- Third-party software support outside the managed environment scope
- Custom application development or integration services
- On-site technical support visits (available as professional services)
- Data recovery services beyond standard backup restoration
- End-user training and help desk services for business applications

## 6. PERFORMANCE METRICS & CREDITS

### 6.1 Service Level Credits
If monthly availability falls below the committed {{uptime_target}}%, Client shall receive service credits:
- **99.0% - {{uptime_target}}%**: 5% monthly service credit
- **98.0% - 98.9%**: 10% monthly service credit  
- **Below 98.0%**: 25% monthly service credit

### 6.2 Measurement Methodology
- Availability measured from Provider monitoring systems
- Planned maintenance windows excluded from calculations
- Credits applied automatically to following month invoice';
    }

    /**
     * Telecom schedule template
     */
    protected function getTelecomScheduleTemplate(): string
    {
        return "# {{schedule_title}}

## Service Quality Metrics
**Uptime Target**: {{uptime_target}}%
**Call Quality**: 95% MOS score of 4.0 or higher
**Latency**: <150ms average
**Jitter**: <30ms
**Packet Loss**: <1%

## Response Times
{{response_times}}

## Coverage Hours
{{coverage_hours}}

## Included Services
- Voice service provisioning
- Number porting assistance
- E911 service configuration
- Call routing optimization
- Quality monitoring
- Technical support

## Service Level Commitments
- Network availability: {{uptime_target}}%
- Call completion rate: 99.5%
- Voice quality monitoring
- 24/7 network operations center";
    }

    /**
     * Hardware schedule template
     */
    protected function getHardwareScheduleTemplate(): string
    {
        return "# {{schedule_title}}

## Hardware Coverage
{{supported_asset_types}}

## Service Levels
**Response Time**: {{response_times}}
**Coverage Hours**: {{coverage_hours}}
**Uptime Target**: {{uptime_target}}%

## Included Services
- Hardware procurement
- Installation and configuration
- Warranty management
- Replacement coordination
- Maintenance scheduling
- End-of-life planning

## Support Scope
- Hardware monitoring
- Preventive maintenance
- Failure diagnosis
- Replacement logistics
- Documentation updates";
    }

    /**
     * Compliance schedule template
     */
    protected function getComplianceScheduleTemplate(): string
    {
        return "# {{schedule_title}}

## Compliance Framework
**Service Tier**: {{service_tier}}
**Monitoring**: Continuous
**Reporting**: Monthly/Quarterly

## Response Times
{{response_times}}

## Coverage Scope
- Compliance monitoring
- Policy enforcement
- Audit preparation
- Risk assessment
- Remediation support
- Documentation maintenance

## Reporting Requirements
- Monthly compliance dashboard
- Quarterly risk assessment
- Annual audit preparation
- Incident response documentation

## Compliance Standards
- Industry-specific regulations
- Data protection requirements
- Security frameworks
- Audit trail maintenance";
    }

    /**
     * Get Schedule B template
     */
    protected function getScheduleBTemplate(): string
    {
        return '# SCHEDULE B - PRICING & FEES

**Document Version:** 2.0  
**Effective Date:** ' . now()->format('F d, Y') . '  
**Review Date:** ' . now()->addYear()->format('F d, Y') . '

---

## 1. BILLING OVERVIEW

### 1.1 Billing Model
**Primary Billing Method**: {{billing_model}}  
**Billing Frequency**: Monthly in advance  
**Currency**: USD (United States Dollars)  
**Invoicing**: Electronic delivery with detailed line items

## 2. MONTHLY RECURRING FEES

### 2.1 Base Service Fees
| **Service Component** | **Monthly Rate** | **Description** |
|----------------------|------------------|-----------------|
| **Base Service Fee** | ${{monthly_fee}} | Core managed services platform |
| **Per User Fee** | ${{per_user_pricing}} | Additional fee per managed user account |
| **Management Overhead** | Included | Account management and reporting |

### 2.2 Asset-Based Monthly Fees
The following monthly fees apply to each managed asset:

{{asset_pricing_table}}

## 3. ONE-TIME & SETUP FEES

### 3.1 Initial Setup & Onboarding
| **Service** | **Fee** | **Description** |
|-------------|---------|-----------------|
| **Initial Setup** | ${{setup_fee}} | Network assessment, initial configuration |
| **Asset Discovery** | Included | Automated discovery and inventory |
| **Documentation** | Included | Network and system documentation |
| **Training** | Included | Administrative and user training (up to 8 hours) |

### 3.2 Professional Services (As Needed)
- **On-site Support**: $175/hour (minimum 4-hour engagement)
- **Project Management**: $150/hour (for projects >$5,000)
- **Custom Integration**: $200/hour (API development, custom scripts)
- **Emergency Response**: $200/hour (outside business hours)

## 4. SPECIALIZED SERVICES

### 4.1 Telecommunications Services
{{telecom_pricing}}

### 4.2 Compliance & Security Services
{{compliance_pricing}}

## 5. PAYMENT TERMS & CONDITIONS

### 5.1 Standard Payment Terms
- **Payment Terms**: Net 30 days from invoice date
- **Late Payment**: 1.5% monthly service charge on overdue amounts
- **Payment Methods**: ACH transfer, wire transfer, or check
- **Auto-Pay Discount**: 2% discount for automated payments

### 5.2 Annual Payment Options
- **Annual Prepayment Discount**: 5% discount on annual service contracts
- **Quarterly Payment Option**: Available with 2% discount
- **Multi-Year Agreements**: Custom pricing available for 3+ year commitments

## 6. PRICE PROTECTION & ADJUSTMENTS

### 6.1 Rate Guarantees
- **Initial Term Protection**: All rates guaranteed for initial contract term
- **Annual Increase Cap**: Maximum 3% annual increase on renewal
- **Advanced Notice**: Minimum 60 days written notice for any rate changes
- **Market Rate Protection**: Competitive rate reviews available upon request

### 6.2 Volume Discounts
- **25-49 Assets**: 5% discount on asset-based fees
- **50-99 Assets**: 10% discount on asset-based fees
- **100+ Assets**: 15% discount on asset-based fees (custom pricing available)

## 7. BILLING DISPUTES & CREDITS

### 7.1 Dispute Resolution
- **Dispute Period**: 30 days from invoice date
- **Resolution Timeline**: 15 business days for dispute resolution
- **Good Faith**: All disputes handled in good faith with detailed investigation

### 7.2 Service Level Credits
Automatic credits applied as outlined in Schedule A for SLA violations:
- Credits applied to subsequent monthly invoice
- No cash refunds - credits only applied to future services
- Credits do not extend contract terms';
    }

    /**
     * Get Schedule C template
     */
    protected function getScheduleCTemplate(): string
    {
        return '# SCHEDULE C - ADDITIONAL TERMS & CONDITIONS

**Document Version:** 2.0  
**Effective Date:** ' . now()->format('F d, Y') . '  
**Review Date:** ' . now()->addYear()->format('F d, Y') . '

---

## 1. CONTRACT TERMINATION

### 1.1 Termination for Convenience
- **Notice Period**: {{termination_notice}}
- **Early Termination Fee**: ${{early_termination_fee}}
- **Final Invoice**: Pro-rated charges through termination date
- **Data Transition**: 30-day transition period for data and system handoff

### 1.2 Termination for Cause
- **Immediate Termination**: Available for material breach after 30-day cure period
- **Non-Payment**: Termination after 60 days past due without cure
- **Security Breach**: Immediate termination for willful security violations
- **No Early Termination Fee**: Waived for justified cause terminations

### 1.3 Post-Termination Obligations
- **Data Return**: Complete data export provided within 30 days
- **Equipment Return**: Client-owned equipment returned within 15 days  
- **Final Settlement**: All outstanding fees settled within 30 days
- **Confidentiality**: All confidentiality obligations survive termination

## 2. DATA PROTECTION & SECURITY

### 2.1 Data Handling & Retention
- **Data Classification**: {{data_retention}}
- **Retention Period**: Data retained for minimum contract term plus 90 days
- **Secure Deletion**: Cryptographic erasure within 30 days of authorized deletion
- **Data Location**: Primary data storage within United States

### 2.2 Backup & Recovery Procedures
- **Backup Policy**: {{backup_policy}}
- **Recovery Testing**: Quarterly backup integrity verification
- **Disaster Recovery**: RTO 4 hours, RPO 1 hour for critical systems
- **Geographic Redundancy**: Backups maintained in geographically separate facilities

### 2.3 Security Framework
- **Security Standards**: {{security_requirements}}
- **Access Controls**: Multi-factor authentication required for all administrative access
- **Monitoring**: 24/7 security event monitoring and incident response
- **Compliance**: Annual SOC 2 Type II audit with results available to Client

## 3. LIABILITY & INDEMNIFICATION

### 3.1 Limitation of Liability
- **Service Provider Liability**: Limited to 12 months of fees paid under this Agreement
- **Consequential Damages**: Neither party liable for indirect, incidental, or consequential damages
- **Data Loss**: Service Provider liability for data loss limited to data restoration costs
- **Business Interruption**: Service Provider not liable for business interruption beyond SLA credits

### 3.2 Mutual Indemnification
- **IP Infringement**: Each party indemnifies against claims of IP infringement by their technology
- **Data Breach**: Service Provider indemnifies for breaches caused by Provider negligence
- **Third-Party Claims**: Client indemnifies for claims arising from Client data or business operations
- **Defense Cooperation**: Parties agree to cooperate in defense of covered claims

### 3.3 Insurance Requirements
- **Service Provider**: Minimum $5M professional liability and $2M cyber liability coverage
- **Client Responsibility**: Maintain appropriate business insurance for operations
- **Certificate of Insurance**: Annual exchange of insurance certificates
- **Notice of Changes**: 30-day advance notice of material insurance changes

## 4. INTELLECTUAL PROPERTY & CONFIDENTIALITY

### 4.1 Intellectual Property Rights
- **Client Data**: Client retains all rights, title, and interest in Client data and systems
- **Service Provider Tools**: Provider retains ownership of proprietary methodologies and tools
- **Developed IP**: Custom developments become Client property upon full payment
- **License Grants**: Provider grants Client perpetual license to use custom-developed solutions

### 4.2 Confidentiality Obligations
- **Mutual Confidentiality**: Both parties protect confidential information received
- **Employee Training**: All personnel trained on confidentiality requirements
- **Non-Disclosure**: Confidential information not disclosed without written consent
- **Return of Information**: All confidential information returned upon contract termination

## 5. DISPUTE RESOLUTION & GOVERNING LAW

### 5.1 Dispute Resolution Process
- **Primary Method**: {{dispute_method}}
- **Escalation Path**: Direct negotiation → Mediation → Binding arbitration
- **Mediation**: Non-binding mediation through recognized ADR organization
- **Arbitration**: Final binding arbitration under American Arbitration Association rules

### 5.2 Governing Law & Jurisdiction
- **Governing Law**: {{governing_law}}
- **Exclusive Jurisdiction**: Courts of competent jurisdiction in Service Provider location
- **Venue**: All legal proceedings in Service Provider principal place of business
- **Waiver of Jury Trial**: Both parties waive right to jury trial for contract disputes

## 6. FORCE MAJEURE & BUSINESS CONTINUITY

### 6.1 Force Majeure Events
Events beyond reasonable control including but not limited to:
- Natural disasters, pandemics, and acts of God
- Government actions, regulations, and legal restrictions  
- Labor strikes, supplier failures, and infrastructure outages
- Cyber attacks, terrorism, and military actions

### 6.2 Force Majeure Procedures
- **Immediate Notice**: Notice of force majeure event within 48 hours
- **Mitigation Efforts**: Reasonable efforts to minimize impact and restore services
- **Alternative Solutions**: Implementation of business continuity plans where possible
- **Contract Suspension**: Performance obligations suspended during qualified events

### 6.3 Business Continuity Planning
- **Disaster Recovery**: Comprehensive disaster recovery plans tested annually
- **Communication Plan**: Multiple communication channels for emergency situations
- **Service Restoration**: Prioritized restoration plan based on business criticality
- **Regular Updates**: Quarterly updates on business continuity preparedness';
    }

    /**
     * Format asset types for display
     */
    protected function formatAssetTypes(array $assetTypes): string
    {
        if (empty($assetTypes)) {
            return "- All standard IT assets";
        }

        $formatted = [];
        foreach ($assetTypes as $type) {
            $formatted[] = "- " . ucfirst(str_replace('_', ' ', $type));
        }

        return implode("\n", $formatted);
    }

    /**
     * Format response times for display
     */
    protected function formatResponseTimes(array $responseTimes): string
    {
        if (empty($responseTimes)) {
            return "**Standard**: 4 hours business days";
        }

        $formatted = [];
        foreach ($responseTimes as $priority => $time) {
            $formatted[] = "**" . ucfirst($priority) . "**: " . $time;
        }

        return implode("\n", $formatted);
    }

    /**
     * Format coverage hours for display
     */
    protected function formatCoverageHours(array $coverageHours): string
    {
        if (empty($coverageHours)) {
            return "**Business Hours**: 8 AM - 6 PM (Local Time)";
        }

        $formatted = [];
        foreach ($coverageHours as $type => $hours) {
            $formatted[] = "**" . ucfirst(str_replace('_', ' ', $type)) . "**: " . $hours;
        }

        return implode("\n", $formatted);
    }

    /**
     * Format asset pricing table
     */
    protected function formatAssetPricingTable(array $assetPricing): string
    {
        if (empty($assetPricing)) {
            return "Asset pricing included in base monthly fee";
        }

        $formatted = [];
        foreach ($assetPricing as $assetType => $config) {
            if (!empty($config['enabled']) && !empty($config['price'])) {
                $price = number_format((float)$config['price'], 2);
                $formatted[] = "- **" . ucfirst(str_replace('_', ' ', $assetType)) . "**: $" . $price . " per month";
            }
        }

        return empty($formatted) ? "No additional asset fees" : implode("\n", $formatted);
    }

    /**
     * Format telecom pricing
     */
    protected function formatTelecomPricing(array $telecomPricing): string
    {
        if (empty($telecomPricing)) {
            return "No telecom services included";
        }

        $formatted = [];
        
        if (!empty($telecomPricing['perChannel'])) {
            $formatted[] = "- **Per Channel**: $" . number_format((float)$telecomPricing['perChannel'], 2);
        }
        
        if (!empty($telecomPricing['callingPlan'])) {
            $formatted[] = "- **Calling Plan**: $" . number_format((float)$telecomPricing['callingPlan'], 2);
        }
        
        if (!empty($telecomPricing['e911'])) {
            $formatted[] = "- **E911 Service**: $" . number_format((float)$telecomPricing['e911'], 2);
        }

        return empty($formatted) ? "Telecom pricing included in base fee" : implode("\n", $formatted);
    }

    /**
     * Format compliance pricing
     */
    protected function formatCompliancePricing(array $compliancePricing): string
    {
        if (empty($compliancePricing)) {
            return "No compliance services included";
        }

        $formatted = [];
        
        if (!empty($compliancePricing['frameworkMonthly'])) {
            foreach ($compliancePricing['frameworkMonthly'] as $framework => $fee) {
                if (!empty($fee)) {
                    $formatted[] = "- **" . strtoupper($framework) . "**: $" . number_format((float)$fee, 2) . " per month";
                }
            }
        }

        return empty($formatted) ? "Compliance services included in base fee" : implode("\n", $formatted);
    }

    /**
     * Log partial failure for non-critical contract creation components
     */
    protected function logPartialFailure(Contract $contract, string $component, string $error): void
    {
        // Update contract metadata to track partial failures
        $metadata = $contract->metadata ?? [];
        $metadata['partial_failures'] = $metadata['partial_failures'] ?? [];
        $metadata['partial_failures'][] = [
            'component' => $component,
            'error' => $error,
            'timestamp' => now()->toISOString(),
            'can_retry' => true
        ];
        
        $contract->update(['metadata' => $metadata]);

        // Log activity for audit trail
        activity()
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => 'partial_failure',
                'component' => $component,
                'error' => $error
            ])
            ->log("Contract creation completed with partial failure in {$component}");
    }

    /**
     * Perform cleanup of resources created during failed contract creation
     */
    protected function performCleanup(array $createdResources): void
    {
        try {
            // Clean up contract schedules if any were created
            if (!empty($createdResources['schedules'])) {
                \App\Domains\Contract\Models\ContractSchedule::whereIn('id', $createdResources['schedules'])
                    ->delete();
                
                Log::info('Cleaned up contract schedules', [
                    'schedule_ids' => $createdResources['schedules']
                ]);
            }

            // Clean up asset assignments if any were created
            if (!empty($createdResources['asset_assignments'])) {
                // Assuming asset assignments are stored in a pivot table or similar
                foreach ($createdResources['asset_assignments'] as $assetId) {
                    // This would need to be adjusted based on actual asset assignment storage
                    Log::info('Would clean up asset assignment', ['asset_id' => $assetId]);
                }
            }

            // Clean up the main contract if it was created
            if (!empty($createdResources['contract'])) {
                $contract = $createdResources['contract'];
                
                // Force delete to bypass soft deletes in cleanup
                $contract->forceDelete();
                
                Log::info('Cleaned up contract during error recovery', [
                    'contract_id' => $contract->id
                ]);
            }

        } catch (\Exception $cleanupError) {
            // Log cleanup errors but don't throw - we're already in error recovery
            Log::error('Error during contract creation cleanup', [
                'cleanup_error' => $cleanupError->getMessage(),
                'original_resources' => $createdResources
            ]);
        }
    }

    /**
     * Validate contract data before creation to prevent common errors
     */
    protected function validateContractData(array $data): array
    {
        $errors = [];

        // Validate required fields
        if (empty($data['client_id'])) {
            $errors[] = 'Client ID is required';
        }

        if (empty($data['title'])) {
            $errors[] = 'Contract title is required';
        }

        if (empty($data['start_date'])) {
            $errors[] = 'Start date is required';
        }

        // Validate date logic
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $startDate = \Carbon\Carbon::parse($data['start_date']);
            $endDate = \Carbon\Carbon::parse($data['end_date']);
            
            if ($endDate->lte($startDate)) {
                $errors[] = 'End date must be after start date';
            }
        }

        // Validate pricing structure if present
        if (!empty($data['pricing_structure'])) {
            $pricing = is_string($data['pricing_structure']) 
                ? json_decode($data['pricing_structure'], true) 
                : $data['pricing_structure'];
                
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid pricing structure format';
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Contract validation failed: ' . implode(', ', $errors));
        }

        return $data;
    }

    /**
     * Retry failed contract components
     */
    public function retryContractComponent(Contract $contract, string $component): bool
    {
        try {
            $metadata = $contract->metadata ?? [];
            $partialFailures = $metadata['partial_failures'] ?? [];
            
            // Find the specific failure to retry
            $failureToRetry = collect($partialFailures)->first(function ($failure) use ($component) {
                return $failure['component'] === $component && ($failure['can_retry'] ?? false);
            });
            
            if (!$failureToRetry) {
                Log::warning('No retryable failure found for component', [
                    'contract_id' => $contract->id,
                    'component' => $component
                ]);
                return false;
            }

            // Attempt to retry the specific component
            switch ($component) {
                case 'schedules':
                    $data = $contract->toArray(); // Get contract data
                    $scheduleIds = $this->createContractSchedules($contract, $data);
                    
                    Log::info('Successfully retried schedule creation', [
                        'contract_id' => $contract->id,
                        'schedule_ids' => $scheduleIds
                    ]);
                    break;

                case 'asset_assignments':
                    // Retry asset assignments
                    $slaTerms = $contract->sla_terms ?? [];
                    if ($slaTerms) {
                        $assignmentResults = $this->processAssetAssignments($contract, ['sla_terms' => $slaTerms]);
                        
                        Log::info('Successfully retried asset assignments', [
                            'contract_id' => $contract->id,
                            'assignments' => $assignmentResults
                        ]);
                    }
                    break;

                default:
                    Log::warning('Unknown component for retry', ['component' => $component]);
                    return false;
            }

            // Remove the failure from metadata
            $metadata['partial_failures'] = collect($partialFailures)
                ->reject(function ($failure) use ($component) {
                    return $failure['component'] === $component;
                })
                ->values()
                ->toArray();
            
            $contract->update(['metadata' => $metadata]);

            // Log successful retry
            activity()
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'component_retry_success',
                    'component' => $component
                ])
                ->log("Successfully retried {$component} for contract");

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to retry contract component', [
                'contract_id' => $contract->id,
                'component' => $component,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Generate contract content with populated variables
     */
    protected function generateContractContent(Contract $contract): void
    {
        Log::info('🚀 Starting contract content generation', [
            'contract_id' => $contract->id,
            'contract_title' => $contract->title,
            'template_id' => $contract->template_id,
            'has_metadata' => !empty($contract->metadata),
            'metadata_keys' => $contract->metadata ? array_keys($contract->metadata) : [],
        ]);
        
        // Use ContractClauseService to generate content from clauses
        $clauseService = app(\App\Domains\Contract\Services\ContractClauseService::class);
        
        // Get contract's template and clauses
        $template = $contract->template;
        if (!$template) {
            Log::error('❌ Contract has no template assigned', [
                'contract_id' => $contract->id,
                'template_id' => $contract->template_id
            ]);
            throw new \InvalidArgumentException('Contract has no template');
        }
        
        Log::info('📋 Template loaded', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_type' => $template->type
        ]);
        
        // Get clauses for this template
        $clauses = $template->clauses()->orderBy('sort_order')->get();
        
        Log::info('📄 Clauses loaded for template', [
            'template_id' => $template->id,
            'clause_count' => $clauses->count(),
            'clause_ids' => $clauses->pluck('id')->toArray()
        ]);
        
        if ($clauses->isEmpty()) {
            Log::warning('⚠️ No active clauses found for template', [
                'template_id' => $template->id,
                'contract_id' => $contract->id
            ]);
            return;
        }
        
        // Generate variables first
        Log::info('🔧 Starting variable generation...');
        $variableMapper = app(\App\Services\TemplateVariableMapper::class);
        $variables = $variableMapper->generateVariables($contract);
        
        Log::info('✅ Variables generated', [
            'total_variables' => count($variables),
            'wizard_vars' => array_intersect_key($variables, array_flip([
                'billing_model', 'service_tier', 'payment_terms', 'response_time_hours',
                'voip_enabled', 'hardware_support', 'price_per_user', 'setup_fee'
            ])),
            'key_variables' => array_slice($variables, 0, 10, true) // First 10 for brevity
        ]);
        
        // Generate content using the template and variables
        Log::info('🏗️ Generating contract content from clauses...');
        $content = $clauseService->generateContractFromClauses($template, $variables);
        
        // Check for unprocessed template variables
        $unprocessedVars = [];
        if (preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches)) {
            $unprocessedVars = array_unique($matches[1]);
        }
        
        if (!empty($unprocessedVars)) {
            Log::warning('⚠️ Unprocessed template variables found in content', [
                'contract_id' => $contract->id,
                'unprocessed_variables' => $unprocessedVars,
                'sample_content' => substr($content, 0, 500) . '...'
            ]);
        }
        
        // Check for conditional processing issues
        $conditionalIssues = [];
        if (strpos($content, '{{#if') !== false) {
            $conditionalIssues[] = 'Unprocessed {{#if}} blocks found';
        }
        if (strpos($content, '{{else}}') !== false) {
            $conditionalIssues[] = 'Unprocessed {{else}} blocks found';
        }
        if (strpos($content, '{{/if}}') !== false) {
            $conditionalIssues[] = 'Unprocessed {{/if}} blocks found';
        }
        
        if (!empty($conditionalIssues)) {
            Log::error('❌ Conditional processing issues detected', [
                'contract_id' => $contract->id,
                'issues' => $conditionalIssues,
                'sample_problematic_content' => $this->extractProblematicContent($content)
            ]);
        }
        
        // Update contract with generated content and variables
        $contract->update([
            'content' => $content,
            'variables' => $variables
        ]);
        
        Log::info('✅ Contract content generated and saved', [
            'contract_id' => $contract->id,
            'content_length' => strlen($content),
            'variable_count' => count($variables),
            'has_unprocessed_vars' => !empty($unprocessedVars),
            'has_conditional_issues' => !empty($conditionalIssues),
            'success' => empty($unprocessedVars) && empty($conditionalIssues)
        ]);
    }
    
    /**
     * Extract problematic content sections for debugging
     */
    private function extractProblematicContent(string $content): array
    {
        $issues = [];
        
        // Find unprocessed conditionals with context
        if (preg_match_all('/(.{0,50}\{\{[#\/]?if[^}]*\}\}.{0,50})/s', $content, $matches)) {
            $issues['conditional_blocks'] = array_slice($matches[0], 0, 3); // First 3 matches
        }
        
        // Find unprocessed variables with context
        if (preg_match_all('/(.{0,30}\{\{[^#\/][^}]*\}\}.{0,30})/s', $content, $matches)) {
            $issues['unprocessed_variables'] = array_slice($matches[0], 0, 5); // First 5 matches
        }
        
        return $issues;
    }

    /**
     * Generate default variables for contracts without form data
     */
    protected function generateDefaultVariables(array $data): array
    {
        // Get template to determine default values
        $template = null;
        if (!empty($data['template_id'])) {
            $template = \App\Domains\Contract\Models\ContractTemplate::find($data['template_id']);
        }
        
        return [
            // Billing & Pricing
            'billing_model' => 'monthly_fixed',
            'billing_frequency' => 'monthly',
            'payment_terms' => 'net_30',
            'monthly_base_rate' => '$2,500.00',
            'setup_fee' => '$500.00',
            'hourly_rate' => '$150.00',
            
            // Service Levels
            'service_tier' => 'silver',
            'response_time_hours' => '4',
            'resolution_time_hours' => '24',
            'uptime_percentage' => '99.5',
            'business_hours' => '8 AM - 6 PM (Monday-Friday)',
            
            // Performance Metrics
            'tier_benefits' => implode("\n", [
                '- 24/7 monitoring and alerting',
                '- Remote support and troubleshooting',  
                '- Monthly performance reports',
                '- Quarterly business reviews',
                '- Emergency after-hours support'
            ]),
            
            // Coverage Details
            'supported_asset_types' => implode(', ', [
                'servers', 'workstations', 'network_equipment', 'storage_systems'
            ]),
            
            // Additional defaults based on template
            'auto_assign_new_assets' => true,
            'includes_remote_support' => true,
            'includes_onsite_support' => false,
            'emergency_support_included' => true,
        ];
    }

    /**
     * Generate default billing configuration
     */
    protected function generateDefaultBillingConfig(array $data): array
    {
        return [
            'model' => 'monthly_fixed',
            'base_rate' => '2500.00',
            'auto_assign_assets' => true,
            'auto_assign_new_assets' => false,
            'auto_assign_contacts' => false,
            'auto_assign_new_contacts' => false,
        ];
    }

    /**
     * Create Telecom Schedule
     */
    protected function createTelecomSchedule(Contract $contract, array $data): ?ContractSchedule
    {
        $telecomData = $data['telecom_schedule'] ?? [];
        
        return ContractSchedule::create([
            'contract_id' => $contract->id,
            'company_id' => $contract->company_id,
            'schedule_type' => 'telecom',
            'title' => 'Schedule D - Telecommunications Services',
            'description' => 'VoIP services, quality of service metrics, and telecommunications compliance',
            'schedule_data' => [
                'channelCount' => $telecomData['channelCount'] ?? 10,
                'callingPlan' => $telecomData['callingPlan'] ?? 'local_long_distance',
                'internationalCalling' => $telecomData['internationalCalling'] ?? 'additional',
                'emergencyServices' => $telecomData['emergencyServices'] ?? 'enabled',
                'qos' => $telecomData['qos'] ?? [
                    'meanOpinionScore' => '4.2',
                    'jitterMs' => 30,
                    'packetLossPercent' => 0.1,
                    'uptimePercent' => '99.9'
                ],
                'carrier' => $telecomData['carrier'] ?? ['primary' => '', 'backup' => ''],
                'protocol' => $telecomData['protocol'] ?? 'sip',
                'codecs' => $telecomData['codecs'] ?? ['G.711', 'G.722'],
                'compliance' => $telecomData['compliance'] ?? [
                    'fccCompliant' => true,
                    'karisLaw' => true,
                    'rayBaums' => true
                ],
                'security' => $telecomData['security'] ?? [
                    'encryption' => true,
                    'fraudProtection' => true,
                    'callRecording' => false
                ]
            ],
            'status' => 'active',
            'created_by' => auth()->id()
        ]);
    }

    /**
     * Create Hardware Schedule
     */
    protected function createHardwareSchedule(Contract $contract, array $data): ?ContractSchedule
    {
        $hardwareData = $data['hardware_schedule'] ?? [];
        
        return ContractSchedule::create([
            'contract_id' => $contract->id,
            'company_id' => $contract->company_id,
            'schedule_type' => 'hardware',
            'title' => 'Schedule E - Hardware Products & Services',
            'description' => 'Hardware procurement, installation services, warranty terms, and pricing',
            'schedule_data' => [
                'selectedCategories' => $hardwareData['selectedCategories'] ?? [],
                'procurementModel' => $hardwareData['procurementModel'] ?? 'direct_resale',
                'leadTimeDays' => $hardwareData['leadTimeDays'] ?? 5,
                'leadTimeType' => $hardwareData['leadTimeType'] ?? 'business_days',
                'services' => $hardwareData['services'] ?? [
                    'basicInstallation' => false,
                    'rackAndStack' => false,
                    'cabling' => false,
                    'powerConfiguration' => false,
                    'basicConfiguration' => false
                ],
                'sla' => $hardwareData['sla'] ?? [
                    'installationTimeline' => 'Within 5 business days',
                    'configurationTimeline' => 'Within 2 business days',
                    'supportResponse' => '4_hours'
                ],
                'warranty' => $hardwareData['warranty'] ?? [
                    'hardwarePeriod' => '1_year',
                    'supportPeriod' => '1_year',
                    'onSiteSupport' => false,
                    'advancedReplacement' => false,
                    'extendedOptions' => []
                ],
                'pricing' => $hardwareData['pricing'] ?? [
                    'markupModel' => 'fixed_percentage',
                    'categoryMarkup' => [],
                    'volumeTiers' => [],
                    'hardwarePaymentTerms' => 'net_30',
                    'servicePaymentTerms' => 'net_30',
                    'taxExempt' => false
                ]
            ],
            'status' => 'active',
            'created_by' => auth()->id()
        ]);
    }

    /**
     * Create Compliance Schedule
     */
    protected function createComplianceSchedule(Contract $contract, array $data): ?ContractSchedule
    {
        $complianceData = $data['compliance_schedule'] ?? [];
        
        return ContractSchedule::create([
            'contract_id' => $contract->id,
            'company_id' => $contract->company_id,
            'schedule_type' => 'compliance',
            'title' => 'Schedule F - Compliance Framework & Requirements',
            'description' => 'Regulatory compliance requirements, audit schedules, and training programs',
            'schedule_data' => [
                'selectedFrameworks' => $complianceData['selectedFrameworks'] ?? [],
                'scope' => $complianceData['scope'] ?? '',
                'riskLevel' => $complianceData['riskLevel'] ?? 'medium',
                'industrySector' => $complianceData['industrySector'] ?? '',
                'audits' => $complianceData['audits'] ?? [
                    'internal' => false,
                    'external' => false,
                    'penetrationTesting' => false,
                    'vulnerabilityScanning' => false,
                    'riskAssessment' => false
                ],
                'frequency' => $complianceData['frequency'] ?? [
                    'comprehensive' => 'annually',
                    'interim' => 'quarterly',
                    'vulnerability' => 'monthly'
                ],
                'deliverables' => $complianceData['deliverables'] ?? [
                    'executiveSummary' => false,
                    'detailedFindings' => false,
                    'remediationPlan' => false,
                    'complianceMatrix' => false,
                    'dashboardReporting' => false
                ],
                'training' => $complianceData['training'] ?? [
                    'selectedPrograms' => [],
                    'deliveryMethod' => 'online',
                    'frequency' => 'annually',
                    'tracking' => [
                        'attendance' => false,
                        'assessments' => false,
                        'certifications' => false
                    ],
                    'minimumScore' => 80
                ],
                'monitoring' => $complianceData['monitoring'] ?? [
                    'siem' => false,
                    'logManagement' => false,
                    'fileIntegrity' => false,
                    'accessMonitoring' => false,
                    'changeManagement' => false
                ]
            ],
            'status' => 'active',
            'created_by' => auth()->id()
        ]);
    }
}