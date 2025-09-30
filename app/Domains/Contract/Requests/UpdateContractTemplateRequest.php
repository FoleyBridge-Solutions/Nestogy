<?php

namespace App\Domains\Contract\Requests;

use App\Domains\Contract\Models\ContractTemplate;
use App\Http\Requests\BaseUpdateRequest;
use Illuminate\Validation\Rule;

class UpdateContractTemplateRequest extends BaseUpdateRequest
{
    protected function getValidationRules(): array
    {
        $template = $this->route('template');
        $availableTypes = ContractTemplate::getAvailableTypes($this->user()->company_id);
        $availableStatuses = ContractTemplate::getAvailableStatuses($this->user()->company_id);
        $availableBillingModels = ContractTemplate::getAvailableBillingModels($this->user()->company_id);

        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('contract_templates', 'slug')->ignore($template->id),
            ],
            'description' => 'nullable|string',
            'template_type' => 'required|in:'.implode(',', array_keys($availableTypes)),
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'required|in:'.implode(',', array_keys($availableStatuses)),
            'version' => 'nullable|string|max:20',
            'is_default' => 'boolean',
            'variable_fields' => 'nullable|array',
            'default_values' => 'nullable|array',
            'required_fields' => 'nullable|array',
            'voip_service_types' => 'nullable|array',
            'default_sla_terms' => 'nullable|array',
            'default_pricing_structure' => 'nullable|array',
            'compliance_templates' => 'nullable|array',
            'jurisdictions' => 'nullable|array',
            'regulatory_requirements' => 'nullable|array',
            'legal_disclaimers' => 'nullable|string',
            'customization_options' => 'nullable|array',
            'conditional_clauses' => 'nullable|array',
            'pricing_models' => 'nullable|array',
            'billing_model' => 'nullable|in:'.implode(',', array_keys($availableBillingModels)),
            'asset_billing_rules' => 'nullable|array',
            'supported_asset_types' => 'nullable|array',
            'asset_service_matrix' => 'nullable|array',
            'default_per_asset_rate' => 'nullable|numeric|min:0',
            'contact_billing_rules' => 'nullable|array',
            'contact_access_tiers' => 'nullable|array',
            'default_per_contact_rate' => 'nullable|numeric|min:0',
            'calculation_formulas' => 'nullable|array',
            'auto_assignment_rules' => 'nullable|array',
            'billing_triggers' => 'nullable|array',
            'workflow_automation' => 'nullable|array',
            'notification_triggers' => 'nullable|array',
            'integration_hooks' => 'nullable|array',
            'requires_approval' => 'boolean',
            'approval_workflow' => 'nullable|array',
            'next_review_date' => 'nullable|date|after:today',
            'metadata' => 'nullable|array',
            'rendering_options' => 'nullable|array',
            'signature_settings' => 'nullable|array',
        ];
    }

    protected function getBooleanFields(): array
    {
        return ['is_default', 'requires_approval'];
    }

    protected function getRouteParameterName(): string
    {
        return 'template';
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Template name is required.',
            'template_type.required' => 'Template type is required.',
            'template_type.in' => 'Selected template type is not valid.',
            'status.required' => 'Template status is required.',
            'status.in' => 'Selected status is not valid.',
            'slug.unique' => 'This slug is already taken.',
            'default_per_asset_rate.numeric' => 'Asset rate must be a valid number.',
            'default_per_asset_rate.min' => 'Asset rate must be 0 or greater.',
            'default_per_contact_rate.numeric' => 'Contact rate must be a valid number.',
            'default_per_contact_rate.min' => 'Contact rate must be 0 or greater.',
            'next_review_date.after' => 'Review date must be in the future.',
        ];
    }
}
