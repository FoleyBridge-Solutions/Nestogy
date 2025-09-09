<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use App\Domains\Contract\Traits\HasConditionalLogic;
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
    use HasFactory, BelongsToCompany, HasConditionalLogic;

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

    // Conditional logic methods moved to HasConditionalLogic trait

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