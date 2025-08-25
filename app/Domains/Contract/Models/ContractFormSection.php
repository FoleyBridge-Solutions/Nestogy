<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use App\Domains\Contract\Traits\HasConditionalLogic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * ContractFormSection Model
 * 
 * Defines form sections that group related fields together.
 * Supports conditional logic and flexible layouts.
 */
class ContractFormSection extends Model
{
    use HasFactory, BelongsToCompany, HasConditionalLogic;

    protected $table = 'contract_form_sections';

    protected $fillable = [
        'company_id',
        'section_slug',
        'section_name',
        'description',
        'icon',
        'fields_order',
        'conditional_logic',
        'layout_config',
        'is_collapsible',
        'is_collapsed_by_default',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'fields_order' => 'array',
        'conditional_logic' => 'array',
        'layout_config' => 'array',
        'is_collapsible' => 'boolean',
        'is_collapsed_by_default' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get fields for this section in order
     */
    public function getOrderedFields()
    {
        if (empty($this->fields_order)) {
            return collect();
        }

        $fieldSlugs = $this->fields_order;
        
        return ContractFieldDefinition::where('company_id', $this->company_id)
            ->whereIn('field_slug', $fieldSlugs)
            ->active()
            ->get()
            ->sortBy(function ($field) use ($fieldSlugs) {
                return array_search($field->field_slug, $fieldSlugs);
            });
    }

    /**
     * Scope to get active sections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Conditional logic methods moved to HasConditionalLogic trait

    /**
     * Get layout configuration
     */
    public function getLayoutConfig(): array
    {
        return array_merge([
            'columns' => 1,
            'column_gap' => 'medium',
            'field_spacing' => 'medium',
        ], $this->layout_config ?? []);
    }

    /**
     * Generate slug from name
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->section_slug)) {
                $model->section_slug = Str::slug($model->section_name);
            }
        });
    }
}