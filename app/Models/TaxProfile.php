<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tax Profile Model
 * 
 * Defines tax requirements and calculation rules for different product/service categories.
 * Maps categories to appropriate tax engines and required data fields.
 * 
 * @property int $id
 * @property int $company_id
 * @property int|null $category_id
 * @property int|null $tax_category_id
 * @property string $profile_type
 * @property string $name
 * @property string|null $description
 * @property array $required_fields
 * @property array $tax_types
 * @property string $calculation_engine
 * @property array|null $field_definitions
 * @property array|null $validation_rules
 * @property array|null $default_values
 * @property bool $is_active
 * @property int $priority
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class TaxProfile extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'tax_profiles';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'category_id',
        'tax_category_id',
        'profile_type',
        'name',
        'description',
        'required_fields',
        'tax_types',
        'calculation_engine',
        'field_definitions',
        'validation_rules',
        'default_values',
        'is_active',
        'priority',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'category_id' => 'integer',
        'tax_category_id' => 'integer',
        'required_fields' => 'array',
        'tax_types' => 'array',
        'field_definitions' => 'array',
        'validation_rules' => 'array',
        'default_values' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Profile type constants
     */
    const TYPE_VOIP = 'voip';
    const TYPE_DIGITAL_SERVICES = 'digital_services';
    const TYPE_EQUIPMENT = 'equipment';
    const TYPE_PROFESSIONAL = 'professional';
    const TYPE_GENERAL = 'general';
    const TYPE_CUSTOM = 'custom';

    /**
     * Calculation engine constants
     */
    const ENGINE_VOIP = 'VoIPTaxService';
    const ENGINE_SERVICE_TAX = 'ServiceTaxCalculator';
    const ENGINE_CUSTOM = 'custom';

    /**
     * Field type definitions for dynamic forms
     */
    const FIELD_TYPES = [
        'line_count' => [
            'type' => 'number',
            'label' => 'Number of Lines',
            'min' => 1,
            'default' => 1,
            'help' => 'Number of phone lines or extensions',
        ],
        'minutes' => [
            'type' => 'number',
            'label' => 'Estimated Minutes/Month',
            'min' => 0,
            'default' => 0,
            'help' => 'Estimated monthly usage in minutes',
        ],
        'extensions' => [
            'type' => 'number',
            'label' => 'Number of Extensions',
            'min' => 0,
            'default' => 0,
            'help' => 'Number of PBX extensions',
        ],
        'data_usage' => [
            'type' => 'number',
            'label' => 'Data Usage (GB)',
            'min' => 0,
            'default' => 0,
            'help' => 'Monthly data usage in gigabytes',
        ],
        'storage_amount' => [
            'type' => 'number',
            'label' => 'Storage Amount (GB)',
            'min' => 0,
            'default' => 0,
            'help' => 'Storage capacity in gigabytes',
        ],
        'user_count' => [
            'type' => 'number',
            'label' => 'Number of Users',
            'min' => 1,
            'default' => 1,
            'help' => 'Number of user licenses',
        ],
        'weight' => [
            'type' => 'number',
            'label' => 'Weight (lbs)',
            'min' => 0,
            'default' => 0,
            'help' => 'Product weight in pounds',
        ],
        'dimensions' => [
            'type' => 'dimensions',
            'label' => 'Dimensions',
            'fields' => ['length', 'width', 'height'],
            'help' => 'Product dimensions (L x W x H)',
        ],
        'hours' => [
            'type' => 'number',
            'label' => 'Service Hours',
            'min' => 0,
            'default' => 0,
            'help' => 'Number of service hours',
        ],
        'service_location' => [
            'type' => 'address',
            'label' => 'Service Location',
            'help' => 'Where the service will be performed',
        ],
    ];

    /**
     * Get the category this profile belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the tax category this profile uses.
     */
    public function taxCategory(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class);
    }

    /**
     * Get products using this tax profile.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'tax_profile_id');
    }

    /**
     * Check if a specific field is required.
     */
    public function requiresField(string $field): bool
    {
        return in_array($field, $this->required_fields ?? []);
    }

    /**
     * Get field definition.
     */
    public function getFieldDefinition(string $field): ?array
    {
        // Check custom definitions first
        if (isset($this->field_definitions[$field])) {
            return $this->field_definitions[$field];
        }
        
        // Fall back to default definitions
        return self::FIELD_TYPES[$field] ?? null;
    }

    /**
     * Get validation rules for required fields.
     */
    public function getValidationRules(): array
    {
        $rules = [];
        
        foreach ($this->required_fields ?? [] as $field) {
            $fieldDef = $this->getFieldDefinition($field);
            
            if ($fieldDef) {
                switch ($fieldDef['type']) {
                    case 'number':
                        $rules[$field] = 'required|numeric|min:' . ($fieldDef['min'] ?? 0);
                        break;
                    case 'address':
                        $rules[$field . '.state'] = 'required|string|max:2';
                        $rules[$field . '.city'] = 'nullable|string|max:255';
                        $rules[$field . '.zip'] = 'nullable|string|max:10';
                        break;
                    case 'dimensions':
                        $rules[$field . '.length'] = 'nullable|numeric|min:0';
                        $rules[$field . '.width'] = 'nullable|numeric|min:0';
                        $rules[$field . '.height'] = 'nullable|numeric|min:0';
                        break;
                    default:
                        $rules[$field] = 'required';
                }
            }
        }
        
        // Add custom validation rules if defined
        if ($this->validation_rules) {
            $rules = array_merge($rules, $this->validation_rules);
        }
        
        return $rules;
    }

    /**
     * Get default values for fields.
     */
    public function getDefaultValues(): array
    {
        $defaults = [];
        
        foreach ($this->required_fields ?? [] as $field) {
            $fieldDef = $this->getFieldDefinition($field);
            
            if ($fieldDef && isset($fieldDef['default'])) {
                $defaults[$field] = $fieldDef['default'];
            }
        }
        
        // Merge with custom defaults
        if ($this->default_values) {
            $defaults = array_merge($defaults, $this->default_values);
        }
        
        return $defaults;
    }

    /**
     * Check if this profile applies to VoIP services.
     */
    public function isVoIPProfile(): bool
    {
        return $this->profile_type === self::TYPE_VOIP ||
               in_array('voip', $this->tax_types ?? []) ||
               $this->calculation_engine === self::ENGINE_VOIP;
    }

    /**
     * Scope to get active profiles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get profiles by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('profile_type', $type);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Create default tax profiles for a company.
     */
    public static function createDefaultProfiles(int $companyId): void
    {
        $profiles = [
            [
                'company_id' => $companyId,
                'profile_type' => self::TYPE_VOIP,
                'name' => 'VoIP Services',
                'description' => 'Tax profile for VoIP and telecommunications services',
                'required_fields' => ['line_count', 'minutes', 'service_location'],
                'tax_types' => ['federal_excise', 'usf', 'e911', 'state_telecom', 'local_telecom'],
                'calculation_engine' => self::ENGINE_VOIP,
                'priority' => 10,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'profile_type' => self::TYPE_DIGITAL_SERVICES,
                'name' => 'Digital Services',
                'description' => 'Tax profile for cloud, SaaS, and digital services',
                'required_fields' => ['user_count'],
                'tax_types' => ['sales_tax', 'digital_services_tax'],
                'calculation_engine' => self::ENGINE_SERVICE_TAX,
                'priority' => 20,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'profile_type' => self::TYPE_EQUIPMENT,
                'name' => 'Equipment Sales',
                'description' => 'Tax profile for equipment and hardware sales',
                'required_fields' => ['weight'],
                'tax_types' => ['sales_tax', 'use_tax', 'recycling_fee'],
                'calculation_engine' => self::ENGINE_SERVICE_TAX,
                'priority' => 30,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'profile_type' => self::TYPE_PROFESSIONAL,
                'name' => 'Professional Services',
                'description' => 'Tax profile for consulting and professional services',
                'required_fields' => ['hours', 'service_location'],
                'tax_types' => ['service_tax'],
                'calculation_engine' => self::ENGINE_SERVICE_TAX,
                'priority' => 40,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'profile_type' => self::TYPE_GENERAL,
                'name' => 'General Products',
                'description' => 'Default tax profile for general products and services',
                'required_fields' => [],
                'tax_types' => ['sales_tax'],
                'calculation_engine' => self::ENGINE_SERVICE_TAX,
                'priority' => 100,
                'is_active' => true,
            ],
        ];
        
        foreach ($profiles as $profile) {
            self::create($profile);
        }
    }
}