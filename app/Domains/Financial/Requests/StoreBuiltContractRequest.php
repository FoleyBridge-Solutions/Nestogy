<?php

namespace App\Domains\Financial\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuiltContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Contract basic information
            'title' => 'required|string|max:255',
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query) {
                    return $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'type' => 'required|string|in:service,maintenance,support,custom',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'billing_frequency' => 'required|string|in:monthly,quarterly,annually,one-time',
            'description' => 'nullable|string|max:2000',

            // Component assignments
            'components' => 'required|array|min:1',
            'components.*.component.id' => [
                'required',
                'integer',
                Rule::exists('contract_components', 'id')->where(function ($query) {
                    return $query->where('company_id', auth()->user()->company_id)
                                 ->where('status', 'active');
                }),
            ],
            'components.*.variable_values' => 'nullable|array',
            'components.*.has_pricing_override' => 'boolean',
            'components.*.pricing_override' => 'nullable|array',
            'components.*.pricing_override.type' => 'nullable|string|in:fixed,percentage',
            'components.*.pricing_override.amount' => 'nullable|numeric|min:0',

            // Calculated totals (for validation)
            'total_value' => 'required|numeric|min:0',
            'is_programmable' => 'boolean',

            // Optional fields
            'auto_renewal' => 'boolean',
            'renewal_terms' => 'nullable|string|max:1000',
            'payment_terms' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Contract title is required.',
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'Selected client is not valid.',
            'start_date.required' => 'Contract start date is required.',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',
            'end_date.after' => 'End date must be after the start date.',
            'components.required' => 'At least one component must be added.',
            'components.min' => 'At least one component must be added.',
            'components.*.component.id.required' => 'Component ID is required.',
            'components.*.component.id.exists' => 'One or more selected components are not valid.',
            'total_value.required' => 'Contract total value is required.',
            'total_value.min' => 'Contract value cannot be negative.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure is_programmable is set
        if (!$this->has('is_programmable')) {
            $this->merge(['is_programmable' => true]);
        }

        // Validate component data structure
        if ($this->has('components')) {
            $components = $this->input('components');
            
            // Normalize component data
            $normalized = collect($components)->map(function ($componentData) {
                return [
                    'component' => $componentData['component'] ?? [],
                    'variable_values' => $componentData['variable_values'] ?? [],
                    'has_pricing_override' => $componentData['has_pricing_override'] ?? false,
                    'pricing_override' => $componentData['pricing_override'] ?? null,
                ];
            })->toArray();
            
            $this->merge(['components' => $normalized]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that components belong to the user's company
            if ($this->has('components')) {
                $this->validateComponentAccess($validator);
            }

            // Validate pricing calculations
            if ($this->has('total_value') && $this->has('components')) {
                $this->validatePricingCalculations($validator);
            }

            // Validate component configurations
            if ($this->has('components')) {
                $this->validateComponentConfigurations($validator);
            }
        });
    }

    /**
     * Validate that all components belong to the user's company
     */
    protected function validateComponentAccess($validator): void
    {
        $componentIds = collect($this->input('components'))
            ->pluck('component.id')
            ->filter()
            ->unique();

        $validComponents = \App\Models\Financial\ContractComponent::whereIn('id', $componentIds)
            ->where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->pluck('id');

        $invalidComponents = $componentIds->diff($validComponents);

        if ($invalidComponents->isNotEmpty()) {
            $validator->errors()->add(
                'components',
                'Some selected components are not available or do not belong to your organization.'
            );
        }
    }

    /**
     * Validate pricing calculations
     */
    protected function validatePricingCalculations($validator): void
    {
        $submittedTotal = (float) $this->input('total_value');
        $calculatedTotal = 0;

        foreach ($this->input('components') as $index => $componentData) {
            $componentId = $componentData['component']['id'] ?? null;
            
            if (!$componentId) {
                continue;
            }

            $component = \App\Models\Financial\ContractComponent::find($componentId);
            if (!$component) {
                continue;
            }

            $variables = $componentData['variable_values'] ?? [];
            $pricingOverride = $componentData['pricing_override'] ?? null;

            // Calculate component price
            $basePrice = $component->calculatePrice($variables);
            $finalPrice = $basePrice;

            if ($componentData['has_pricing_override'] && $pricingOverride) {
                switch ($pricingOverride['type'] ?? '') {
                    case 'fixed':
                        $finalPrice = (float) ($pricingOverride['amount'] ?? 0);
                        break;
                    case 'percentage':
                        $percentage = (float) ($pricingOverride['amount'] ?? 100);
                        $finalPrice = $basePrice * ($percentage / 100);
                        break;
                }
            }

            $calculatedTotal += $finalPrice;
        }

        // Allow small rounding differences (up to $0.01)
        $difference = abs($submittedTotal - $calculatedTotal);
        if ($difference > 0.01) {
            $validator->errors()->add(
                'total_value',
                "Submitted total value ($" . number_format($submittedTotal, 2) . 
                ") does not match calculated total ($" . number_format($calculatedTotal, 2) . ")."
            );
        }
    }

    /**
     * Validate component configurations
     */
    protected function validateComponentConfigurations($validator): void
    {
        foreach ($this->input('components') as $index => $componentData) {
            $componentId = $componentData['component']['id'] ?? null;
            
            if (!$componentId) {
                continue;
            }

            $component = \App\Models\Financial\ContractComponent::find($componentId);
            if (!$component) {
                continue;
            }

            // Validate required variables
            $variables = $componentData['variable_values'] ?? [];
            $componentVariables = $component->variables ?? [];

            foreach ($componentVariables as $variableConfig) {
                if (($variableConfig['required'] ?? false)) {
                    $variableName = $variableConfig['name'];
                    
                    if (empty($variables[$variableName])) {
                        $validator->errors()->add(
                            "components.{$index}.variable_values.{$variableName}",
                            "The {$variableName} field is required for {$component->name}."
                        );
                    }
                }
            }

            // Validate pricing override values
            if ($componentData['has_pricing_override']) {
                $override = $componentData['pricing_override'] ?? [];
                
                if (empty($override['type'])) {
                    $validator->errors()->add(
                        "components.{$index}.pricing_override.type",
                        "Pricing override type is required when custom pricing is enabled."
                    );
                }

                if (!isset($override['amount']) || $override['amount'] < 0) {
                    $validator->errors()->add(
                        "components.{$index}.pricing_override.amount",
                        "Pricing override amount must be a positive number."
                    );
                }
            }
        }
    }

    /**
     * Get the contract data formatted for creation
     */
    public function getContractData(): array
    {
        return [
            'title' => $this->input('title'),
            'client_id' => $this->input('client_id'),
            'type' => $this->input('type'),
            'start_date' => $this->input('start_date'),
            'end_date' => $this->input('end_date'),
            'billing_frequency' => $this->input('billing_frequency'),
            'description' => $this->input('description'),
            'total_value' => $this->input('total_value'),
            'is_programmable' => $this->input('is_programmable', true),
            'auto_renewal' => $this->input('auto_renewal', false),
            'renewal_terms' => $this->input('renewal_terms'),
            'payment_terms' => $this->input('payment_terms'),
            'notes' => $this->input('notes'),
            'status' => 'draft', // Always start as draft
            'company_id' => auth()->user()->company_id,
            'created_by' => auth()->id(),
        ];
    }

    /**
     * Get the component assignments data
     */
    public function getComponentAssignments(): array
    {
        $components = $this->input('components', []);
        $assignments = [];

        foreach ($components as $index => $componentData) {
            $assignments[] = [
                'component_id' => $componentData['component']['id'],
                'configuration' => [], // Could be extended later
                'variable_values' => $componentData['variable_values'] ?? [],
                'pricing_override' => $componentData['has_pricing_override'] 
                    ? $componentData['pricing_override'] 
                    : null,
                'status' => 'active',
                'sort_order' => $index + 1,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ];
        }

        return $assignments;
    }
}