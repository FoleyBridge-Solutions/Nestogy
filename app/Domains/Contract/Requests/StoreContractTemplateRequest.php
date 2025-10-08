<?php

namespace App\Domains\Contract\Requests;

use App\Domains\Contract\Models\ContractTemplate;
use App\Http\Requests\BaseStoreRequest;

class StoreContractTemplateRequest extends BaseStoreRequest
{
    private const NULLABLE_ARRAY = 'nullable|array';

    protected function getModelClass(): string
    {
        return ContractTemplate::class;
    }

    protected function getValidationRules(): array
    {
        $availableTypes = ContractTemplate::getAvailableTypes($this->user()->company_id);
        $availableStatuses = ContractTemplate::getAvailableStatuses($this->user()->company_id);
        $availableBillingModels = ContractTemplate::getAvailableBillingModels($this->user()->company_id);

        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:contract_templates,slug',
            'description' => 'nullable|string',
            'template_type' => 'required|in:'.implode(',', array_keys($availableTypes)),
            'category' => 'nullable|string|max:100',
            'tags' => self::NULLABLE_ARRAY,
            'tags.*' => 'string|max:50',
            'status' => 'required|in:'.implode(',', array_keys($availableStatuses)),
            'version' => 'nullable|string|max:20',
            'is_default' => 'boolean',
            'variable_fields' => self::NULLABLE_ARRAY,
            'default_values' => self::NULLABLE_ARRAY,
            'required_fields' => self::NULLABLE_ARRAY,
            'voip_service_types' => self::NULLABLE_ARRAY,
            'default_sla_terms' => self::NULLABLE_ARRAY,
            'default_pricing_structure' => self::NULLABLE_ARRAY,
            'compliance_templates' => self::NULLABLE_ARRAY,
            'jurisdictions' => self::NULLABLE_ARRAY,
            'regulatory_requirements' => self::NULLABLE_ARRAY,
            'legal_disclaimers' => 'nullable|string',
            'customization_options' => self::NULLABLE_ARRAY,
            'conditional_clauses' => self::NULLABLE_ARRAY,
            'pricing_models' => self::NULLABLE_ARRAY,
            'billing_model' => 'nullable|in:'.implode(',', array_keys($availableBillingModels)),
            'asset_billing_rules' => self::NULLABLE_ARRAY,
            'supported_asset_types' => self::NULLABLE_ARRAY,
            'asset_service_matrix' => self::NULLABLE_ARRAY,
            'default_per_asset_rate' => 'nullable|numeric|min:0',
            'contact_billing_rules' => self::NULLABLE_ARRAY,
            'contact_access_tiers' => self::NULLABLE_ARRAY,
            'default_per_contact_rate' => 'nullable|numeric|min:0',
            'calculation_formulas' => self::NULLABLE_ARRAY,
            'auto_assignment_rules' => self::NULLABLE_ARRAY,
            'billing_triggers' => self::NULLABLE_ARRAY,
            'workflow_automation' => self::NULLABLE_ARRAY,
            'notification_triggers' => self::NULLABLE_ARRAY,
            'integration_hooks' => self::NULLABLE_ARRAY,
            'requires_approval' => 'boolean',
            'approval_workflow' => self::NULLABLE_ARRAY,
            'next_review_date' => 'nullable|date|after:today',
            'metadata' => self::NULLABLE_ARRAY,
            'rendering_options' => self::NULLABLE_ARRAY,
            'signature_settings' => self::NULLABLE_ARRAY,
        ];
    }

    protected function getBooleanFields(): array
    {
        return ['is_default', 'requires_approval'];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Template name is required.',
            'template_type.required' => 'Template type is required.',
            'template_type.in' => 'Selected template type is not valid.',
            'status.required' => 'Template status is required.',
            'status.in' => 'Selected status is not valid.',
            'default_per_asset_rate.numeric' => 'Asset rate must be a valid number.',
            'default_per_asset_rate.min' => 'Asset rate must be 0 or greater.',
            'default_per_contact_rate.numeric' => 'Contact rate must be a valid number.',
            'default_per_contact_rate.min' => 'Contact rate must be 0 or greater.',
            'next_review_date.after' => 'Review date must be in the future.',
        ];
    }
}
