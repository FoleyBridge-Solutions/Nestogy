<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ContractWizard extends Component
{
    public $currentStep = 1;
    public $totalSteps = 5;
    public $data = [];
    public $autoAdvanceOnSelect = false;
    
    // Step names for display
    public $stepNames = [
        1 => 'Template Selection',
        2 => 'Contract Details', 
        3 => 'Asset Assignment',
        4 => 'Infrastructure Schedule',
        5 => 'Review & Generate'
    ];
    
    protected $listeners = [
        'stepChanged' => 'handleStepChange',
        'dataUpdated' => 'handleDataUpdate',
        'templateSelected' => 'handleTemplateSelection'
    ];

    public $selectedTemplate = null;

    public function mount()
    {
        // Initialize wizard with empty data structure
        $this->data = [
            'template' => null,
            'contract_details' => [],
            'assets' => [],
            'infrastructure' => [],
            'review' => []
        ];
    }

    public function nextStep()
    {
        if ($this->canGoToStep($this->currentStep + 1)) {
            $this->currentStep++;
            $this->dispatch('stepChanged', ['step' => $this->currentStep]);
        }
    }
    
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->dispatch('stepChanged', ['step' => $this->currentStep]);
        }
    }
    
    public function goToStep($step)
    {
        if ($this->canGoToStep($step)) {
            $this->currentStep = $step;
            $this->dispatch('stepChanged', ['step' => $this->currentStep]);
        }
    }

    public function canGoToStep($step)
    {
        // Basic validation - can always go backwards or to current step
        if ($step <= $this->currentStep) {
            return true;
        }
        
        // Step-specific validation for forward navigation
        if ($step > 1 && !$this->selectedTemplate) {
            // Cannot proceed past step 1 without selecting a template
            return false;
        }
        
        // For now, allow progression to next step only
        return $step <= $this->currentStep + 1 && $step <= $this->totalSteps;
    }

    public function saveDraft()
    {
        // Implement draft saving logic
        session(['contract_wizard_draft' => $this->data]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Draft saved successfully!'
        ]);
    }
    
    public function loadDraft()
    {
        $draft = session('contract_wizard_draft');
        if ($draft) {
            $this->data = $draft;
        }
    }
    
    public function updateData($section, $newData)
    {
        $this->data[$section] = array_merge($this->data[$section] ?? [], $newData);
    }
    
    public function handleStepChange($payload)
    {
        // Handle step change events from child components
        if (isset($payload['step'])) {
            $this->currentStep = $payload['step'];
        }
    }
    
    public function handleDataUpdate($payload)
    {
        // Handle data updates from child components
        if (isset($payload['section']) && isset($payload['data'])) {
            $this->updateData($payload['section'], $payload['data']);
        }
    }
    
    public function handleTemplateSelection($template)
    {
        // Validate and coerce template payload
        $validatedTemplate = $this->validateTemplatePayload($template);
        
        // Handle template selection from TemplateSelection component
        $this->selectedTemplate = $validatedTemplate;
        $this->data['template'] = $validatedTemplate;
        
        // Only auto-advance if enabled and we're on step 1 to prevent repeated advancement
        if ($this->autoAdvanceOnSelect && $this->currentStep === 1) {
            $this->nextStep();
        }
    }

    private function validateTemplatePayload($template): array
    {
        // Ensure template is an array
        if (!is_array($template)) {
            throw new \InvalidArgumentException('Template payload must be an array');
        }

        // Validate and coerce required fields with defaults
        return [
            'id' => isset($template['id']) ? (is_numeric($template['id']) ? (int)$template['id'] : null) : null,
            'name' => isset($template['name']) && is_string($template['name']) ? $template['name'] : 'Unknown Template',
            'description' => isset($template['description']) && is_string($template['description']) ? $template['description'] : null,
            'category' => isset($template['category']) && is_string($template['category']) ? $template['category'] : null,
            'billing_model' => isset($template['billing_model']) && is_string($template['billing_model']) ? $template['billing_model'] : null,
            'usage_count' => isset($template['usage_count']) && is_numeric($template['usage_count']) ? (int)$template['usage_count'] : 0,
            'variable_fields' => isset($template['variable_fields']) && is_array($template['variable_fields']) ? $template['variable_fields'] : [],
        ];
    }
    
    public function getStepProgress()
    {
        return round(($this->currentStep / $this->totalSteps) * 100);
    }
    
    public function isStepCompleted($step)
    {
        // Implement step completion validation logic
        // For now, mark previous steps as completed
        return $step < $this->currentStep;
    }
    
    public function isStepActive($step)
    {
        return $step === $this->currentStep;
    }

    public function render()
    {
        return view('livewire.contract-wizard')
            ->extends('layouts.app')
            ->section('content');
    }
}