<?php

namespace App\Livewire\Contracts;

use App\Domains\Client\Models\Client;
use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Services\ContractConfigurationRegistry;
use App\Domains\Contract\Services\ContractService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ContractWizard extends Component
{
    // Core wizard state
    public $currentStep = 1;
    public $totalSteps = 5;
    
    // Contract being edited (null for create)
    public ?Contract $contract = null;
    
    // Template selection
    public $selectedTemplate = null;
    public $templateFilter = [
        'category' => '',
        'billingModel' => '',
    ];
    
    // Form data - Basic Information
    public $title = '';
    public $contract_type = '';
    public $client_id = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $term_months = '';
    public $currency_code = 'USD';
    public $payment_terms = '';
    public $status = 'draft';
    
    // Billing model
    public $billing_model = '';
    public $contract_value = '';
    
    // Schedule configurations
    public $infrastructureSchedule = [];
    public $pricingSchedule = [];
    public $additionalTerms = [];
    public $slaTerms = [];
    public $telecomSchedule = [];
    public $hardwareSchedule = [];
    public $complianceSchedule = [];
    
    // Variable values for template
    public $variableValues = [];
    
    // Available data
    public $clients = [];
    public $templates = [];
    public $contractTypes = [];
    public $billingModels = [];
    
    protected $contractService;
    
    public function boot(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }
    
    public function mount(?Contract $contract = null)
    {
        $this->contract = $contract;
        
        // Load available data
        $this->loadClients();
        $this->loadTemplates();
        $this->loadConfiguration();
        
        // Initialize schedule structures
        $this->initializeSchedules();
        
        if ($contract) {
            // Edit mode - populate from existing contract
            $this->populateFromContract($contract);
        } else {
            // Create mode - check for saved progress
            $this->loadSavedProgress();
        }
    }
    
    protected function loadClients()
    {
        $user = Auth::user();
        $this->clients = Client::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();
    }
    
    protected function loadTemplates()
    {
        $user = Auth::user();
        $this->templates = ContractTemplate::where('company_id', $user->company_id)
            ->whereIn('status', ['active', 'draft'])
            ->with(['clauses'])
            ->withCount(['contracts'])
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                $template->clause_count = $template->clauses->count();
                $template->usage_count = $template->contracts_count;
                $template->variable_count = $template->variable_fields 
                    ? count($template->variable_fields) 
                    : count($template->extractVariables());
                return $template;
            });
    }
    
    protected function loadConfiguration()
    {
        $contractConfigRegistry = new ContractConfigurationRegistry(auth()->user()->company_id);
        $this->contractTypes = $contractConfigRegistry->getContractTypes();
        $this->billingModels = $contractConfigRegistry->getBillingModels();
    }
    
    protected function initializeSchedules()
    {
        $this->infrastructureSchedule = [
            'supportedAssetTypes' => [],
            'sla' => [
                'serviceTier' => '',
                'responseTimeHours' => '',
                'resolutionTimeHours' => '',
                'uptimePercentage' => '',
            ],
            'coverageRules' => [
                'businessHours' => '8x5',
                'emergencySupport' => 'included',
                'includeRemoteSupport' => true,
                'includeOnsiteSupport' => false,
            ],
            'exclusions' => [
                'assetTypes' => '',
                'services' => '',
            ],
        ];
        
        $this->slaTerms = [
            'auto_assign_new_assets' => false,
            'auto_assign_assets' => false,
            'auto_assign_contacts' => false,
            'auto_assign_new_contacts' => false,
            'require_manual_approval' => false,
            'notify_on_assignment' => false,
        ];
        
        $this->pricingSchedule = [
            'billingModel' => '',
            'basePricing' => [
                'monthlyBase' => '',
                'setupFee' => '',
                'hourlyRate' => '',
            ],
        ];
        
        $this->additionalTerms = [
            'termination' => [
                'noticePeriod' => '30_days',
                'earlyTerminationFee' => '',
            ],
        ];
    }
    
    protected function populateFromContract(Contract $contract)
    {
        $this->title = $contract->title;
        $this->contract_type = $contract->contract_type;
        $this->client_id = $contract->client_id;
        $this->description = $contract->description;
        $this->start_date = $contract->start_date?->format('Y-m-d');
        $this->end_date = $contract->end_date?->format('Y-m-d');
        $this->currency_code = $contract->currency_code ?? 'USD';
        $this->payment_terms = $contract->payment_terms;
        $this->status = $contract->status;
        $this->billing_model = $contract->billing_model ?? 'fixed';
        $this->contract_value = $contract->contract_value;
    }
    
    protected function loadSavedProgress()
    {
        // This will be implemented to restore from session/cache
    }
    
    // Template selection
    public function selectTemplate($templateId)
    {
        if ($templateId === null) {
            $this->selectedTemplate = null;
            $this->reset(['title', 'contract_type']);
            return;
        }
        
        $this->selectedTemplate = $this->templates->firstWhere('id', $templateId);
        
        if ($this->selectedTemplate) {
            $this->title = $this->selectedTemplate->name;
            $this->contract_type = $this->mapTemplateTypeToContractType($this->selectedTemplate->template_type);
            $this->billing_model = $this->selectedTemplate->billing_model;
            
            // Initialize variable values
            if ($this->selectedTemplate->variable_fields) {
                foreach ($this->selectedTemplate->variable_fields as $field) {
                    $this->variableValues[$field['name']] = $field['default_value'] ?? '';
                }
            }
            
            // Set supported asset types based on template
            $this->infrastructureSchedule['supportedAssetTypes'] = 
                $this->getSupportedAssetTypesForTemplate($this->selectedTemplate);
        }
    }
    
    protected function mapTemplateTypeToContractType($templateType)
    {
        $mapping = [
            'managed_services' => 'managed_services',
            'cybersecurity_services' => 'recurring_service',
            'backup_dr' => 'recurring_service',
            'cloud_migration' => 'one_time_service',
            'm365_management' => 'managed_services',
            'break_fix' => 'maintenance',
            'hosted_pbx' => 'recurring_service',
            'sip_trunking' => 'recurring_service',
            'hardware_procurement' => 'one_time_service',
            'software_licensing' => 'recurring_service',
        ];
        
        return $mapping[$templateType] ?? 'recurring_service';
    }
    
    protected function getSupportedAssetTypesForTemplate($template)
    {
        $assetTypeMapping = [
            'managed_services' => ['workstation', 'server', 'network_device', 'hypervisor_node', 'storage', 'printer'],
            'cybersecurity_services' => ['workstation', 'server', 'network_device', 'security_device'],
            'backup_dr' => ['workstation', 'server', 'storage', 'hypervisor_node'],
            'hosted_pbx' => ['workstation'],
            'sip_trunking' => ['workstation', 'server', 'network_device'],
            'hardware_procurement' => ['workstation', 'server', 'network_device', 'storage', 'printer'],
        ];
        
        return $assetTypeMapping[$template->template_type] ?? ['workstation', 'server', 'network_device'];
    }
    
    // Billing model selection
    public function selectBillingModel($model)
    {
        $this->billing_model = $model;
    }
    
    // Step navigation
    public function nextStep()
    {
        if ($this->canProceedToNext()) {
            $this->currentStep++;
        }
    }
    
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
    
    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
        }
    }
    
    public function canProceedToNext()
    {
        switch ($this->currentStep) {
            case 1:
                return true; // Template selection is optional
            case 2:
                $requiredFields = $this->title && $this->contract_type && $this->client_id && $this->start_date;
                $dateValidation = $this->end_date || $this->term_months;
                return $requiredFields && $dateValidation;
            case 3:
            case 4:
            case 5:
                return true;
            default:
                return false;
        }
    }
    
    // Form submission
    public function save()
    {
        $this->validate($this->rules());
        
        $data = [
            'title' => $this->title,
            'contract_type' => $this->contract_type,
            'client_id' => $this->client_id,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'currency_code' => $this->currency_code,
            'payment_terms' => $this->payment_terms,
            'status' => $this->status,
            'billing_model' => $this->billing_model,
            'contract_value' => $this->contract_value,
            'template_id' => $this->selectedTemplate?->id,
            'variable_values' => $this->variableValues,
            'infrastructure_schedule' => $this->infrastructureSchedule,
            'pricing_schedule' => $this->pricingSchedule,
            'additional_terms' => $this->additionalTerms,
            'sla_terms' => $this->slaTerms,
        ];
        
        if ($this->contract) {
            // Update existing contract
            $this->contract->update($data);
            session()->flash('success', 'Contract updated successfully!');
            return redirect()->route('financial.contracts.show', $this->contract);
        } else {
            // Create new contract
            $data['company_id'] = auth()->user()->company_id;
            $data['created_by'] = auth()->id();
            $contract = Contract::create($data);
            
            session()->flash('success', 'Contract created successfully!');
            return redirect()->route('financial.contracts.show', $contract);
        }
    }
    
    protected function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'term_months' => 'nullable|integer|min:1|max:120',
            'currency_code' => 'required|string|size:3',
            'billing_model' => 'nullable|string',
        ];
    }
    
    public function render()
    {
        return view('livewire.contracts.contract-wizard')
            ->extends('layouts.app')
            ->section('content')
            ->title($this->contract ? 'Edit Contract' : 'Create Contract');
    }
}
