<?php

namespace App\Livewire\Contracts;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Services\ContractService;
use App\Models\Client;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EditContract extends Component
{
    use AuthorizesRequests;

    private const NULLABLE_STRING_RULE = 'nullable|string';

    public Contract $contract;
    
    // Form fields
    public $title;
    public $contract_type;
    public $client_id;
    public $description;
    public $start_date;
    public $end_date;
    public $contract_value;
    public $status;
    public $billing_model;
    public $currency_code;
    public $payment_terms;
    public $content;
    public $variables;
    
    // UI state
    public $activeTab = 'basic';
    public $canEdit = true;
    public $canSave = true;
    
    // Tab visibility
    public $availableTabs = [];
    
    protected function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string',
            'client_id' => 'required|exists:clients,id',
            'description' => self::NULLABLE_STRING_RULE,
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'contract_value' => 'nullable|numeric|min:0', // Changed to nullable since it's auto-calculated
            'status' => 'required|string',
            'billing_model' => self::NULLABLE_STRING_RULE,
            'currency_code' => 'required|string|size:3',
            'payment_terms' => self::NULLABLE_STRING_RULE,
            'content' => self::NULLABLE_STRING_RULE,
            'variables' => 'nullable|array',
        ];
    }

    public function mount(Contract $contract)
    {
        $this->authorize('update', $contract);
        
        $this->contract = $contract;
        
        // Populate form fields
        $this->title = $contract->title;
        $this->contract_type = $contract->contract_type;
        $this->client_id = $contract->client_id;
        $this->description = $contract->description;
        $this->start_date = $contract->start_date?->format('Y-m-d');
        $this->end_date = $contract->end_date?->format('Y-m-d');
        $this->contract_value = $contract->contract_value;
        $this->status = $contract->status;
        $this->billing_model = $contract->billing_model ?? 'fixed';
        $this->currency_code = $contract->currency_code ?? 'USD';
        $this->payment_terms = $contract->payment_terms;
        $this->content = $contract->content;
        $this->variables = $contract->variables ?? [];
        
        // Check if contract can be edited
        $this->canEdit = $contract->canBeEdited();
        $this->canSave = $this->canEdit;
        
        // Define available tabs based on contract type and billing model
        $this->availableTabs = $this->getAvailableTabs();
    }
    
    protected function getAvailableTabs()
    {
        $tabs = [
            'basic' => [
                'label' => 'Basic Information',
                'icon' => 'information-circle',
                'always' => true,
            ],
            'billing' => [
                'label' => 'Billing Model',
                'icon' => 'currency-dollar',
                'always' => true,
            ],
            'schedules' => [
                'label' => 'Schedules',
                'icon' => 'document-text',
                'show' => true,
                'count' => $this->contract->schedules()->count(),
            ],
            'assignments' => [
                'label' => 'Assignments',
                'icon' => 'users',
                'show' => in_array($this->billing_model, ['per_asset', 'per_contact', 'hybrid']),
                'count' => ($this->contract->assetAssignments()->count() + $this->contract->contactAssignments()->count()),
            ],
            'content' => [
                'label' => 'Contract Language',
                'icon' => 'document-text',
                'always' => true,
            ],
        ];
        
        return $tabs;
    }

    public function selectBillingModel($model)
    {
        if (!$this->canEdit) {
            return;
        }
        
        $this->billing_model = $model;
        $this->calculateContractValue();
    }
    
    public function calculateContractValue()
    {
        $this->contract->refresh();
        
        switch ($this->billing_model) {
            case 'fixed':
                // For fixed pricing, keep the manually entered value or use contract value
                // Don't auto-calculate, let user set it
                break;
                
            case 'per_asset':
                // Calculate based on asset assignments
                $monthlyTotal = 0;
                foreach ($this->contract->assetAssignments as $assignment) {
                    $monthlyTotal += $assignment->calculateMonthlyCharges();
                }
                $this->contract_value = $monthlyTotal * 12; // Annual value
                break;
                
            case 'per_contact':
                // Calculate based on contact assignments
                $monthlyTotal = 0;
                foreach ($this->contract->contactAssignments as $assignment) {
                    $monthlyTotal += $assignment->monthly_rate ?? 0;
                }
                $this->contract_value = $monthlyTotal * 12; // Annual value
                break;
                
            case 'tiered':
            case 'hybrid':
                // For tiered/hybrid, use the contract's built-in calculation
                $monthlyRevenue = $this->contract->getMonthlyRecurringRevenue();
                $this->contract_value = $this->contract->getAnnualValue();
                break;
                
            default:
                $this->contract_value = $this->contract->contract_value;
        }
    }
    
    public function updatedBillingModel()
    {
        $this->calculateContractValue();
    }

    public function regenerateContent()
    {
        if (!$this->canEdit || !$this->contract->template_id) {
            session()->flash('error', 'Cannot regenerate content for this contract.');
            return;
        }

        try {
            $contractService = app(ContractService::class);
            
            // Regenerate contract content from template
            $contractService->generateContractContent($this->contract);
            
            // Reload the content
            $this->contract->refresh();
            $this->content = $this->contract->content;
            $this->variables = $this->contract->variables ?? [];
            
            session()->flash('success', 'Contract content regenerated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Failed to regenerate contract content', [
                'contract_id' => $this->contract->id,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to regenerate content: ' . $e->getMessage());
        }
    }

    public function save()
    {
        if (!$this->canEdit) {
            session()->flash('error', 'This contract cannot be edited in its current state.');
            return;
        }

        $this->validate();

        try {
            $contractService = app(ContractService::class);
            
            $data = [
                'title' => $this->title,
                'contract_type' => $this->contract_type,
                'client_id' => $this->client_id,
                'description' => $this->description,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'contract_value' => $this->contract_value,
                'status' => $this->status,
                'billing_model' => $this->billing_model,
                'currency_code' => $this->currency_code,
                'payment_terms' => $this->payment_terms,
                'content' => $this->content,
                'variables' => $this->variables,
            ];

            $contractService->updateContract($this->contract, $data);

            session()->flash('success', 'Contract updated successfully!');
            
            return redirect()->route('financial.contracts.show', $this->contract);
            
        } catch (\Exception $e) {
            \Log::error('Contract update failed', [
                'contract_id' => $this->contract->id,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to update contract: ' . $e->getMessage());
        }
    }

    public function getClientsProperty()
    {
        return Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();
    }
    
    // Listen for assignment updates
    protected $listeners = ['assignmentsUpdated' => 'handleAssignmentsUpdated'];
    
    public function handleAssignmentsUpdated()
    {
        // Refresh the contract to get updated relationships
        $this->contract->refresh();
        
        // Recalculate contract value
        $this->calculateContractValue();
        
        // Refresh available tabs
        $this->availableTabs = $this->getAvailableTabs();
    }

    public function render()
    {
        return view('livewire.contracts.edit-contract')
            ->layout('layouts.app');
    }
}
