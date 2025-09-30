<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * ContractFieldDefinition Model
 *
 * Defines custom fields for contracts, replacing hardcoded form fields.
 * Each company can define their own field types and configurations.
 */
class ContractFieldDefinition extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'contract_field_definitions';

    protected $fillable = [
        'company_id',
        'field_slug',
        'field_type',
        'label',
        'placeholder',
        'help_text',
        'validation_rules',
        'ui_config',
        'options',
        'is_required',
        'is_searchable',
        'is_sortable',
        'is_filterable',
        'default_value',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'ui_config' => 'array',
        'options' => 'array',
        'is_required' => 'boolean',
        'is_searchable' => 'boolean',
        'is_sortable' => 'boolean',
        'is_filterable' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Field type constants
     */
    const TYPE_TEXT = 'text';

    const TYPE_TEXTAREA = 'textarea';

    const TYPE_NUMBER = 'number';

    const TYPE_EMAIL = 'email';

    const TYPE_DATE = 'date';

    const TYPE_DATETIME = 'datetime';

    const TYPE_SELECT = 'select';

    const TYPE_MULTISELECT = 'multiselect';

    const TYPE_CHECKBOX = 'checkbox';

    const TYPE_RADIO = 'radio';

    const TYPE_FILE = 'file';

    const TYPE_CLIENT_SELECTOR = 'client_selector';

    const TYPE_ASSET_SELECTOR = 'asset_selector';

    const TYPE_USER_SELECTOR = 'user_selector';

    const TYPE_CURRENCY = 'currency';

    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_JSON = 'json';

    /**
     * Scope to get active fields
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get searchable fields
     */
    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    /**
     * Scope to get filterable fields
     */
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    /**
     * Get validation rules for this field
     */
    public function getValidationRules(): array
    {
        $rules = $this->validation_rules ?? [];

        if ($this->is_required) {
            $rules[] = 'required';
        }

        return $rules;
    }

    /**
     * Get field options for select/radio fields
     */
    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    /**
     * Get UI configuration
     */
    public function getUiConfig(): array
    {
        return $this->ui_config ?? [];
    }

    /**
     * Check if field is of given type
     */
    public function isType(string $type): bool
    {
        return $this->field_type === $type;
    }

    /**
     * Check if field supports multiple values
     */
    public function isMultiple(): bool
    {
        return in_array($this->field_type, [
            self::TYPE_MULTISELECT,
            self::TYPE_CHECKBOX,
        ]);
    }

    /**
     * Get available field types
     */
    public static function getAvailableFieldTypes(): array
    {
        return [
            self::TYPE_TEXT => 'Text Input',
            self::TYPE_TEXTAREA => 'Text Area',
            self::TYPE_NUMBER => 'Number Input',
            self::TYPE_EMAIL => 'Email Input',
            self::TYPE_DATE => 'Date Picker',
            self::TYPE_DATETIME => 'Date/Time Picker',
            self::TYPE_SELECT => 'Select Dropdown',
            self::TYPE_MULTISELECT => 'Multi-Select Dropdown',
            self::TYPE_CHECKBOX => 'Checkbox Group',
            self::TYPE_RADIO => 'Radio Buttons',
            self::TYPE_FILE => 'File Upload',
            self::TYPE_CLIENT_SELECTOR => 'Client Selector',
            self::TYPE_ASSET_SELECTOR => 'Asset Selector',
            self::TYPE_USER_SELECTOR => 'User Selector',
            self::TYPE_CURRENCY => 'Currency Input',
            self::TYPE_PERCENTAGE => 'Percentage Input',
            self::TYPE_JSON => 'JSON Editor',
        ];
    }

    /**
     * Generate slug from label
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->field_slug)) {
                $model->field_slug = Str::slug($model->label);
            }
        });
    }
}
