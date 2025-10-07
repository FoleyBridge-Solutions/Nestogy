<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\ContractFieldDefinition;
use App\Domains\Contract\Models\ContractTypeDefinition;
use App\Domains\Contract\Models\ContractTypeFormMapping;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * DynamicContractValidationService
 *
 * Handles dynamic validation for contracts based on company-specific
 * field definitions and business rules.
 */
class DynamicContractValidationService
{
    /**
     * Validate contract data for given contract type
     */
    public function validateContractData(string $contractType, array $data, string $mode = 'create'): array
    {
        $user = Auth::user();
        if (! $user) {
            return [
                'valid' => false,
                'errors' => ['authentication' => ['User not authenticated']],
            ];
        }

        // Get contract type definition
        $typeDefinition = ContractTypeDefinition::where('company_id', $user->company_id)
            ->where('slug', $contractType)
            ->first();

        if (! $typeDefinition) {
            return [
                'valid' => false,
                'errors' => ['contract_type' => ['Invalid contract type']],
            ];
        }

        // Build validation rules
        $validationRules = $this->buildValidationRules($typeDefinition, $mode);
        $validationMessages = $this->buildValidationMessages($typeDefinition);

        // Create validator
        $validator = Validator::make($data, $validationRules, $validationMessages);

        // Add custom validation rules
        $this->addCustomValidationRules($validator, $typeDefinition, $data, $mode);

        // Run validation
        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        // Run business rule validation
        $businessRuleErrors = $this->validateBusinessRules($typeDefinition, $data, $mode);
        if (! empty($businessRuleErrors)) {
            return [
                'valid' => false,
                'errors' => $businessRuleErrors,
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }

    /**
     * Build validation rules from field definitions
     */
    protected function buildValidationRules(ContractTypeDefinition $typeDefinition, string $mode): array
    {
        $rules = [];

        // Get form mappings for this contract type
        $formMappings = ContractTypeFormMapping::where('company_id', $typeDefinition->company_id)
            ->where('contract_type_slug', $typeDefinition->slug)
            ->with('section')
            ->get();

        foreach ($formMappings as $mapping) {
            $section = $mapping->section;
            if (! $section || ! $section->is_active) {
                continue;
            }

            // Get fields for this section
            $fields = $section->getOrderedFields();

            foreach ($fields as $field) {
                // Apply field overrides from mapping
                $fieldData = $mapping->applyFieldOverrides($field);

                // Build validation rules for this field
                $fieldRules = $this->buildFieldValidationRules($fieldData, $mode);
                if (! empty($fieldRules)) {
                    $rules[$field->field_slug] = $fieldRules;
                }
            }
        }

        // Add core contract validation rules
        $rules = array_merge($rules, $this->getCoreContractValidationRules($mode));

        return $rules;
    }

    /**
     * Build validation rules for a specific field
     */
    protected function buildFieldValidationRules(array $fieldData, string $mode): array
    {
        $rules = [];

        // Required validation
        if ($fieldData['is_required']) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Type-specific validation
        switch ($fieldData['field_type']) {
            case ContractFieldDefinition::TYPE_EMAIL:
                $rules[] = 'email';
                break;

            case ContractFieldDefinition::TYPE_NUMBER:
                $rules[] = 'numeric';
                break;

            case ContractFieldDefinition::TYPE_DATE:
                $rules[] = 'date';
                break;

            case ContractFieldDefinition::TYPE_DATETIME:
                $rules[] = 'date';
                break;

            case ContractFieldDefinition::TYPE_CURRENCY:
                $rules[] = 'numeric';
                $rules[] = 'min:0';
                break;

            case ContractFieldDefinition::TYPE_PERCENTAGE:
                $rules[] = 'numeric';
                $rules[] = 'min:0';
                $rules[] = 'max:100';
                break;

            case ContractFieldDefinition::TYPE_CLIENT_SELECTOR:
                $rules[] = 'exists:clients,id';
                break;

            case ContractFieldDefinition::TYPE_ASSET_SELECTOR:
                $rules[] = 'exists:assets,id';
                break;

            case ContractFieldDefinition::TYPE_USER_SELECTOR:
                $rules[] = 'exists:users,id';
                break;

            case ContractFieldDefinition::TYPE_SELECT:
                if (! empty($fieldData['options'])) {
                    $allowedValues = array_column($fieldData['options'], 'value');
                    $rules[] = 'in:'.implode(',', $allowedValues);
                }
                break;

            case ContractFieldDefinition::TYPE_MULTISELECT:
                $rules[] = 'array';
                if (! empty($fieldData['options'])) {
                    $allowedValues = array_column($fieldData['options'], 'value');
                    $rules[] = 'in:'.implode(',', $allowedValues);
                }
                break;

            case ContractFieldDefinition::TYPE_FILE:
                $rules[] = 'file';
                // Add file type and size restrictions if configured
                if (! empty($fieldData['ui_config']['allowed_types'])) {
                    $rules[] = 'mimes:'.implode(',', $fieldData['ui_config']['allowed_types']);
                }
                if (! empty($fieldData['ui_config']['max_size'])) {
                    $rules[] = 'max:'.$fieldData['ui_config']['max_size'];
                }
                break;

            case ContractFieldDefinition::TYPE_JSON:
                $rules[] = 'json';
                break;
        }

        // Add custom validation rules from field definition
        if (! empty($fieldData['validation_rules'])) {
            $rules = array_merge($rules, $fieldData['validation_rules']);
        }

        return $rules;
    }

    /**
     * Build validation messages
     */
    protected function buildValidationMessages(ContractTypeDefinition $typeDefinition): array
    {
        $messages = [];

        // Get form mappings for this contract type
        $formMappings = ContractTypeFormMapping::where('company_id', $typeDefinition->company_id)
            ->where('contract_type_slug', $typeDefinition->slug)
            ->with('section')
            ->get();

        foreach ($formMappings as $mapping) {
            $section = $mapping->section;
            if (! $section || ! $section->is_active) {
                continue;
            }

            $fields = $section->getOrderedFields();

            foreach ($fields as $field) {
                $fieldSlug = $field->field_slug;
                $label = $field->label;

                // Add common messages
                $messages["{$fieldSlug}.required"] = "The {$label} field is required.";
                $messages["{$fieldSlug}.email"] = "The {$label} must be a valid email address.";
                $messages["{$fieldSlug}.numeric"] = "The {$label} must be a number.";
                $messages["{$fieldSlug}.date"] = "The {$label} must be a valid date.";
                $messages["{$fieldSlug}.exists"] = "The selected {$label} is invalid.";
                $messages["{$fieldSlug}.in"] = "The selected {$label} is invalid.";
                $messages["{$fieldSlug}.file"] = "The {$label} must be a file.";
                $messages["{$fieldSlug}.mimes"] = "The {$label} must be a file of valid type.";
                $messages["{$fieldSlug}.max"] = "The {$label} may not be greater than the allowed limit.";
                $messages["{$fieldSlug}.min"] = "The {$label} must be at least the minimum value.";
                $messages["{$fieldSlug}.json"] = "The {$label} must be valid JSON.";
            }
        }

        return $messages;
    }

    /**
     * Add custom validation rules
     */
    protected function addCustomValidationRules($validator, ContractTypeDefinition $typeDefinition, array $data, string $mode): void
    {
        // Add custom rule for contract number uniqueness
        $validator->addRules([
            'contract_number' => [
                function ($attribute, $value, $fail) use ($typeDefinition, $data, $mode) {
                    if (empty($value)) {
                        return; // Let required validation handle this
                    }

                    $query = \App\Domains\Contract\Models\Contract::where('company_id', $typeDefinition->company_id)
                        ->where('contract_number', $value);

                    // For updates, exclude current contract
                    if ($mode === 'update' && ! empty($data['id'])) {
                        $query->where('id', '!=', $data['id']);
                    }

                    if ($query->exists()) {
                        $fail('The contract number has already been taken.');
                    }
                },
            ],
        ]);

        // Add date range validation
        $validator->addRules([
            'end_date' => [
                function ($attribute, $value, $fail) use ($data) {
                    if (empty($value) || empty($data['start_date'])) {
                        return;
                    }

                    $startDate = \Carbon\Carbon::parse($data['start_date']);
                    $endDate = \Carbon\Carbon::parse($value);

                    if ($endDate->lte($startDate)) {
                        $fail('The end date must be after the start date.');
                    }
                },
            ],
        ]);

        // Add client relationship validation
        $validator->addRules([
            'client_id' => [
                function ($attribute, $value, $fail) use ($typeDefinition) {
                    if (empty($value)) {
                        return;
                    }

                    $client = \App\Models\Client::where('company_id', $typeDefinition->company_id)
                        ->where('id', $value)
                        ->first();

                    if (! $client) {
                        $fail('The selected client does not belong to your company.');
                    }
                },
            ],
        ]);
    }

    /**
     * Validate business rules
     */
    protected function validateBusinessRules(ContractTypeDefinition $typeDefinition, array $data, string $mode): array
    {
        $errors = [];
        $businessRules = $typeDefinition->getBusinessRules();

        if (empty($businessRules)) {
            return $errors;
        }

        foreach ($businessRules as $rule) {
            $ruleType = $rule['type'] ?? null;
            $ruleErrors = $this->validateBusinessRule($ruleType, $rule, $data);
            $errors = array_merge_recursive($errors, $ruleErrors);
        }

        return $errors;
    }

    protected function validateBusinessRule(?string $ruleType, array $rule, array $data): array
    {
        $errors = [];

        switch ($ruleType) {
            case 'max_contract_value':
                $errors = $this->validateMaxContractValue($rule, $data);
                break;

            case 'min_contract_value':
                $errors = $this->validateMinContractValue($rule, $data);
                break;

            case 'max_term_months':
                $errors = $this->validateMaxTermMonths($rule, $data);
                break;

            case 'required_approval':
                break;

            case 'blocked_clients':
                $errors = $this->validateBlockedClients($rule, $data);
                break;

            case 'required_fields_conditional':
                $errors = $this->validateConditionalRequiredFields($rule, $data);
                break;
        }

        return $errors;
    }

    protected function validateMaxContractValue(array $rule, array $data): array
    {
        $maxValue = $rule['value'] ?? null;
        if ($maxValue && ! empty($data['contract_value']) && $data['contract_value'] > $maxValue) {
            return ['contract_value' => ['Contract value cannot exceed '.number_format($maxValue, 2)]];
        }
        return [];
    }

    protected function validateMinContractValue(array $rule, array $data): array
    {
        $minValue = $rule['value'] ?? null;
        if ($minValue && ! empty($data['contract_value']) && $data['contract_value'] < $minValue) {
            return ['contract_value' => ['Contract value must be at least '.number_format($minValue, 2)]];
        }
        return [];
    }

    protected function validateMaxTermMonths(array $rule, array $data): array
    {
        $maxTerm = $rule['value'] ?? null;
        if ($maxTerm && ! empty($data['term_months']) && $data['term_months'] > $maxTerm) {
            return ['term_months' => ["Contract term cannot exceed {$maxTerm} months"]];
        }
        return [];
    }

    protected function validateBlockedClients(array $rule, array $data): array
    {
        $blockedClients = $rule['value'] ?? [];
        if (! empty($data['client_id']) && in_array($data['client_id'], $blockedClients)) {
            return ['client_id' => ['Contracts cannot be created for this client']];
        }
        return [];
    }

    protected function validateConditionalRequiredFields(array $rule, array $data): array
    {
        $errors = [];
        $condition = $rule['condition'] ?? [];
        $requiredFields = $rule['fields'] ?? [];

        if ($this->evaluateCondition($condition, $data)) {
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $errors[$field][] = 'This field is required based on your selections';
                }
            }
        }

        return $errors;
    }

    /**
     * Evaluate conditional logic
     */
    protected function evaluateCondition(array $condition, array $data): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (! $field || ! isset($data[$field])) {
            return false;
        }

        $fieldValue = $data[$field];

        switch ($operator) {
            case '=':
            case '==':
                return $fieldValue == $value;
            case '!=':
                return $fieldValue != $value;
            case '>':
                return $fieldValue > $value;
            case '>=':
                return $fieldValue >= $value;
            case '<':
                return $fieldValue < $value;
            case '<=':
                return $fieldValue <= $value;
            case 'in':
                return in_array($fieldValue, (array) $value);
            case 'not_in':
                return ! in_array($fieldValue, (array) $value);
            case 'empty':
                return empty($fieldValue);
            case 'not_empty':
                return ! empty($fieldValue);
            default:
                return false;
        }
    }

    /**
     * Get core contract validation rules
     */
    protected function getCoreContractValidationRules(string $mode): array
    {
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'client_id' => 'required|exists:clients,id',
            'contract_type' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'contract_value' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
        ];

        if ($mode === 'update') {
            // Make some fields optional for updates
            unset($rules['company_id']);
            $rules['client_id'] = 'sometimes|required|exists:clients,id';
            $rules['contract_type'] = 'sometimes|required|string';
        }

        return $rules;
    }
}
