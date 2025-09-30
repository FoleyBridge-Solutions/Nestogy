<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\ContractFieldDefinition;
use App\Domains\Contract\Models\ContractTypeDefinition;
use App\Domains\Contract\Models\ContractTypeFormMapping;
use Illuminate\Support\Facades\Auth;

/**
 * DynamicContractFormBuilder
 *
 * Builds dynamic forms for contract creation and editing based on
 * company-specific configuration and contract type definitions.
 */
class DynamicContractFormBuilder
{
    /**
     * Build create form for given contract type
     */
    public function buildCreateForm(string $contractType): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $typeDefinition = ContractTypeDefinition::where('company_id', $user->company_id)
            ->where('slug', $contractType)
            ->first();

        if (! $typeDefinition) {
            throw new \Exception("Contract type '{$contractType}' not found");
        }

        return $this->buildFormDefinition($typeDefinition, 'create');
    }

    /**
     * Build edit form for given contract
     */
    public function buildEditForm($contract): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $typeDefinition = ContractTypeDefinition::where('company_id', $user->company_id)
            ->where('slug', $contract->contract_type)
            ->first();

        if (! $typeDefinition) {
            throw new \Exception("Contract type '{$contract->contract_type}' not found");
        }

        $formDefinition = $this->buildFormDefinition($typeDefinition, 'edit', $contract);

        // Pre-populate with existing contract data
        $formDefinition['values'] = $this->extractContractValues($contract, $formDefinition['sections']);

        return $formDefinition;
    }

    /**
     * Build filter form for contract list
     */
    public function buildFilterForm(string $contractType): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $typeDefinition = ContractTypeDefinition::where('company_id', $user->company_id)
            ->where('slug', $contractType)
            ->first();

        if (! $typeDefinition) {
            return [];
        }

        // Get filterable fields
        $filterableFields = ContractFieldDefinition::where('company_id', $user->company_id)
            ->active()
            ->filterable()
            ->orderBy('sort_order')
            ->get();

        $filters = [];
        foreach ($filterableFields as $field) {
            $filters[] = [
                'field_slug' => $field->field_slug,
                'field_type' => $field->field_type,
                'label' => $field->label,
                'options' => $field->getOptions(),
                'ui_config' => $field->getUiConfig(),
            ];
        }

        return [
            'contract_type' => $contractType,
            'filters' => $filters,
        ];
    }

    /**
     * Build form definition for contract type
     */
    protected function buildFormDefinition(ContractTypeDefinition $typeDefinition, string $mode, $contract = null): array
    {
        // Get form mappings for this contract type
        $formMappings = ContractTypeFormMapping::where('company_id', $typeDefinition->company_id)
            ->where('contract_type_slug', $typeDefinition->slug)
            ->with('section')
            ->orderBy('sort_order')
            ->get();

        $sections = [];
        $validationRules = [];
        $defaultValues = $typeDefinition->getDefaultValues();

        foreach ($formMappings as $mapping) {
            $section = $mapping->section;
            if (! $section || ! $section->is_active) {
                continue;
            }

            // Check if section should be visible
            if (! $mapping->shouldBeVisible()) {
                continue;
            }

            $sectionData = [
                'section_slug' => $section->section_slug,
                'section_name' => $section->section_name,
                'description' => $section->description,
                'icon' => $section->icon,
                'is_required' => $mapping->is_required,
                'is_collapsible' => $section->is_collapsible,
                'is_collapsed_by_default' => $section->is_collapsed_by_default,
                'layout_config' => $section->getLayoutConfig(),
                'fields' => [],
            ];

            // Get fields for this section
            $fields = $section->getOrderedFields();

            foreach ($fields as $field) {
                // Apply field overrides from mapping
                $fieldData = $mapping->applyFieldOverrides($field);

                // Add to section
                $sectionData['fields'][] = $this->buildFieldDefinition($fieldData, $mode);

                // Add validation rules
                $validationRules[$field->field_slug] = $field->getValidationRules();

                // Add default value if set
                if ($field->default_value !== null) {
                    $defaultValues[$field->field_slug] = $field->default_value;
                }
            }

            $sections[] = $sectionData;
        }

        return [
            'contract_type' => $typeDefinition->slug,
            'contract_type_name' => $typeDefinition->name,
            'mode' => $mode,
            'sections' => $sections,
            'validation_rules' => $validationRules,
            'default_values' => $defaultValues,
            'business_rules' => $typeDefinition->getBusinessRules(),
        ];
    }

    /**
     * Build field definition for form rendering
     */
    protected function buildFieldDefinition(array $fieldData, string $mode): array
    {
        $field = [
            'field_slug' => $fieldData['field_slug'],
            'field_type' => $fieldData['field_type'],
            'label' => $fieldData['label'],
            'placeholder' => $fieldData['placeholder'],
            'help_text' => $fieldData['help_text'],
            'is_required' => $fieldData['is_required'],
            'validation_rules' => $fieldData['validation_rules'] ?? [],
            'ui_config' => $fieldData['ui_config'] ?? [],
            'default_value' => $fieldData['default_value'],
        ];

        // Add options for select/choice fields
        if (isset($fieldData['options'])) {
            $field['options'] = $fieldData['options'];
        }

        // Add field-type specific configurations
        switch ($fieldData['field_type']) {
            case ContractFieldDefinition::TYPE_CLIENT_SELECTOR:
                $field['ajax_url'] = route('api.clients.search');
                break;

            case ContractFieldDefinition::TYPE_ASSET_SELECTOR:
                $field['ajax_url'] = route('api.assets.search');
                break;

            case ContractFieldDefinition::TYPE_USER_SELECTOR:
                $field['ajax_url'] = route('api.users.search');
                break;
        }

        return $field;
    }

    /**
     * Extract values from contract for form pre-population
     */
    protected function extractContractValues($contract, array $sections): array
    {
        $values = [];

        // Get all field slugs from sections
        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                $fieldSlug = $field['field_slug'];

                // Try to get value from contract
                if (isset($contract->{$fieldSlug})) {
                    $values[$fieldSlug] = $contract->{$fieldSlug};
                }

                // Handle special mappings
                switch ($fieldSlug) {
                    case 'client_id':
                        $values[$fieldSlug] = $contract->client_id;
                        break;
                    case 'start_date':
                        $values[$fieldSlug] = $contract->start_date?->format('Y-m-d');
                        break;
                    case 'end_date':
                        $values[$fieldSlug] = $contract->end_date?->format('Y-m-d');
                        break;
                        // Add more field mappings as needed
                }
            }
        }

        return $values;
    }

    /**
     * Validate form data against field definitions
     */
    public function validateFormData(string $contractType, array $data): array
    {
        $formDefinition = $this->buildCreateForm($contractType);
        $validationRules = $formDefinition['validation_rules'] ?? [];

        $validator = validator($data, $validationRules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }

    /**
     * Render field as HTML
     */
    public function renderField(array $fieldDefinition, $value = null): string
    {
        $componentName = $this->getFieldComponent($fieldDefinition['field_type']);

        return view("components.contract-fields.{$componentName}", [
            'field' => $fieldDefinition,
            'value' => $value,
        ])->render();
    }

    /**
     * Get component name for field type
     */
    protected function getFieldComponent(string $fieldType): string
    {
        $componentMap = [
            ContractFieldDefinition::TYPE_TEXT => 'text',
            ContractFieldDefinition::TYPE_TEXTAREA => 'textarea',
            ContractFieldDefinition::TYPE_NUMBER => 'number',
            ContractFieldDefinition::TYPE_EMAIL => 'email',
            ContractFieldDefinition::TYPE_DATE => 'date',
            ContractFieldDefinition::TYPE_DATETIME => 'datetime',
            ContractFieldDefinition::TYPE_SELECT => 'select',
            ContractFieldDefinition::TYPE_MULTISELECT => 'multiselect',
            ContractFieldDefinition::TYPE_CHECKBOX => 'checkbox',
            ContractFieldDefinition::TYPE_RADIO => 'radio',
            ContractFieldDefinition::TYPE_FILE => 'file',
            ContractFieldDefinition::TYPE_CLIENT_SELECTOR => 'client-selector',
            ContractFieldDefinition::TYPE_ASSET_SELECTOR => 'asset-selector',
            ContractFieldDefinition::TYPE_USER_SELECTOR => 'user-selector',
            ContractFieldDefinition::TYPE_CURRENCY => 'currency',
            ContractFieldDefinition::TYPE_PERCENTAGE => 'percentage',
            ContractFieldDefinition::TYPE_JSON => 'json',
        ];

        return $componentMap[$fieldType] ?? 'text';
    }
}
