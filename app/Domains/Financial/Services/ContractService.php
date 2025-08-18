<?php

namespace App\Domains\Financial\Services;

use App\Models\Contract;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\Quote;
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
     * Create a new contract
     */
    public function createContract(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            // Validate client exists and belongs to company
            $client = Client::where('company_id', auth()->user()->company_id)
                ->findOrFail($data['client_id']);

            // Set company_id and created_by
            $data['company_id'] = auth()->user()->company_id;
            $data['created_by'] = auth()->id();

            // Set defaults
            $data['status'] = $data['status'] ?? Contract::STATUS_DRAFT;
            $data['signature_status'] = $data['signature_status'] ?? Contract::SIGNATURE_PENDING;
            $data['currency_code'] = $data['currency_code'] ?? 'USD';
            $data['renewal_type'] = $data['renewal_type'] ?? Contract::RENEWAL_MANUAL;

            // Generate contract number if not provided
            if (empty($data['contract_number'])) {
                $data['contract_number'] = $this->generateContractNumber($data['prefix'] ?? 'CNT');
            }

            $contract = Contract::create($data);

            // Log activity
            activity()
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->withProperties(['action' => 'created'])
                ->log('Contract created');

            return $contract;
        });
    }

    /**
     * Update an existing contract
     */
    public function updateContract(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data) {
            // Only allow editing of certain statuses
            if (!in_array($contract->status, [Contract::STATUS_DRAFT, Contract::STATUS_PENDING_REVIEW])) {
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
            if ($contract->status !== Contract::STATUS_SIGNED) {
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
            if (!in_array($contract->status, [Contract::STATUS_ACTIVE, Contract::STATUS_SUSPENDED])) {
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
            if ($contract->status !== Contract::STATUS_ACTIVE) {
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
            if ($contract->status !== Contract::STATUS_SUSPENDED) {
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

        return [
            'total_contracts' => Contract::where('company_id', $companyId)->count(),
            'active_contracts' => Contract::where('company_id', $companyId)
                ->where('status', Contract::STATUS_ACTIVE)->count(),
            'draft_contracts' => Contract::where('company_id', $companyId)
                ->where('status', Contract::STATUS_DRAFT)->count(),
            'pending_signature' => Contract::where('company_id', $companyId)
                ->where('signature_status', Contract::SIGNATURE_PENDING)->count(),
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
            if ($contract->status !== Contract::STATUS_DRAFT) {
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
        
        $lastContract = Contract::where('company_id', $companyId)
            ->where('prefix', $prefix)
            ->orderBy('number', 'desc')
            ->first();

        $number = $lastContract ? $lastContract->number + 1 : 1;
        $paddedNumber = str_pad($number, 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $paddedNumber;
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
            $contractData['status'] = Contract::STATUS_DRAFT;
            $contractData['signature_status'] = Contract::SIGNATURE_PENDING;
            $contractData['currency_code'] = $contractData['currency_code'] ?? 'USD';
            $contractData['is_programmable'] = true;

            // Generate contract number
            if (empty($contractData['contract_number'])) {
                $contractData['contract_number'] = $this->generateContractNumber('PRG');
            }

            // Create the contract
            $contract = Contract::create($contractData);

            // Create component assignments
            foreach ($componentAssignments as $index => $assignmentData) {
                $component = \App\Models\Financial\ContractComponent::where('company_id', $user->company_id)
                    ->findOrFail($assignmentData['component']['id']);

                \App\Models\Financial\ContractComponentAssignment::create([
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
}