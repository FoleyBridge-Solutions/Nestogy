<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ContractTypeFormMapping Model
 * 
 * Maps form sections to contract types with specific configurations.
 * Allows different contract types to have different form layouts.
 */
class ContractTypeFormMapping extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'contract_type_form_mappings';

    protected $fillable = [
        'company_id',
        'contract_type_slug',
        'section_slug',
        'is_required',
        'sort_order',
        'conditional_logic',
        'field_overrides',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'conditional_logic' => 'array',
        'field_overrides' => 'array',
    ];

    /**
     * Get the contract type definition
     */
    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractTypeDefinition::class, 'contract_type_slug', 'slug');
    }

    /**
     * Get the form section
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ContractFormSection::class, 'section_slug', 'section_slug');
    }

    /**
     * Check if this mapping should be visible for given contract type and data
     */
    public function shouldBeVisible(array $formData = []): bool
    {
        if (empty($this->conditional_logic)) {
            return true;
        }

        // Same conditional logic as ContractFormSection
        foreach ($this->conditional_logic as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field || !isset($formData[$field])) {
                continue;
            }

            $fieldValue = $formData[$field];

            switch ($operator) {
                case '=':
                case '==':
                    if ($fieldValue != $value) {
                        return false;
                    }
                    break;
                case '!=':
                    if ($fieldValue == $value) {
                        return false;
                    }
                    break;
                case 'in':
                    if (!in_array($fieldValue, (array)$value)) {
                        return false;
                    }
                    break;
                case 'not_in':
                    if (in_array($fieldValue, (array)$value)) {
                        return false;
                    }
                    break;
                case 'empty':
                    if (!empty($fieldValue)) {
                        return false;
                    }
                    break;
                case 'not_empty':
                    if (empty($fieldValue)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Get field overrides for this mapping
     */
    public function getFieldOverrides(): array
    {
        return $this->field_overrides ?? [];
    }

    /**
     * Apply field overrides to a field definition
     */
    public function applyFieldOverrides($field): array
    {
        $overrides = $this->getFieldOverrides();
        $fieldSlug = $field->field_slug;

        if (!isset($overrides[$fieldSlug])) {
            return $field->toArray();
        }

        $fieldData = $field->toArray();
        $fieldOverrides = $overrides[$fieldSlug];

        // Apply overrides
        foreach ($fieldOverrides as $key => $value) {
            if ($key === 'validation_rules' && is_array($value)) {
                $fieldData[$key] = array_merge($fieldData[$key] ?? [], $value);
            } elseif ($key === 'ui_config' && is_array($value)) {
                $fieldData[$key] = array_merge($fieldData[$key] ?? [], $value);
            } else {
                $fieldData[$key] = $value;
            }
        }

        return $fieldData;
    }
}