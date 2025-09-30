<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tax Category Model
 *
 * Represents service category classifications for VoIP taxation.
 * Different service types may have different tax treatments.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $code
 * @property string $category_type
 * @property string|null $description
 * @property array|null $service_types
 * @property array|null $tax_rules
 * @property bool $is_taxable
 * @property bool $is_interstate
 * @property bool $is_international
 * @property bool $requires_jurisdiction_detection
 * @property string|null $default_tax_treatment
 * @property array|null $exemption_rules
 * @property int $priority
 * @property bool $is_active
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class TaxCategory extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'tax_categories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'category_type',
        'description',
        'service_types',
        'tax_rules',
        'is_taxable',
        'is_interstate',
        'is_international',
        'requires_jurisdiction_detection',
        'default_tax_treatment',
        'exemption_rules',
        'priority',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'service_types' => 'array',
        'tax_rules' => 'array',
        'is_taxable' => 'boolean',
        'is_interstate' => 'boolean',
        'is_international' => 'boolean',
        'requires_jurisdiction_detection' => 'boolean',
        'exemption_rules' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Category type enumeration
     */
    const TYPE_TELECOMMUNICATIONS = 'telecommunications';

    const TYPE_INTERNET = 'internet';

    const TYPE_DATA_SERVICES = 'data_services';

    const TYPE_EQUIPMENT = 'equipment';

    const TYPE_INSTALLATION = 'installation';

    const TYPE_MAINTENANCE = 'maintenance';

    const TYPE_HOSTING = 'hosting';

    const TYPE_SOFTWARE = 'software';

    /**
     * Service category constants for VoIP
     */
    const CATEGORY_LOCAL_SERVICE = 'local_service';

    const CATEGORY_LONG_DISTANCE = 'long_distance';

    const CATEGORY_INTERNATIONAL = 'international';

    const CATEGORY_TOLL_FREE = 'toll_free';

    const CATEGORY_DATA_SERVICES = 'data_services';

    const CATEGORY_INTERNET_ACCESS = 'internet_access';

    const CATEGORY_VOIP_FIXED = 'voip_fixed';

    const CATEGORY_VOIP_NOMADIC = 'voip_nomadic';

    const CATEGORY_HOSTED_PBX = 'hosted_pbx';

    const CATEGORY_SIP_TRUNKING = 'sip_trunking';

    const CATEGORY_PRI_CIRCUITS = 'pri_circuits';

    const CATEGORY_FEATURES = 'features';

    const CATEGORY_EQUIPMENT = 'equipment';

    const CATEGORY_INSTALLATION = 'installation';

    const CATEGORY_MAINTENANCE = 'maintenance';

    /**
     * Tax treatment enumeration
     */
    const TREATMENT_STANDARD = 'standard';

    const TREATMENT_EXEMPT = 'exempt';

    const TREATMENT_REDUCED = 'reduced';

    const TREATMENT_SPECIAL = 'special';

    /**
     * Get the tax rates for this category.
     */
    public function taxRates(): HasMany
    {
        return $this->hasMany(VoIPTaxRate::class, 'tax_category_id');
    }

    /**
     * Get active tax rates for this category.
     */
    public function activeTaxRates(): HasMany
    {
        return $this->taxRates()->active();
    }

    /**
     * Get quote items using this tax category.
     */
    public function quoteItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'tax_category_id')
            ->whereHas('quote');
    }

    /**
     * Get invoice items using this tax category.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'tax_category_id')
            ->whereHas('invoice');
    }

    /**
     * Check if the category applies to a specific service type.
     */
    public function appliesTo(string $serviceType): bool
    {
        if (empty($this->service_types)) {
            return true; // Applies to all if not specified
        }

        return in_array($serviceType, $this->service_types);
    }

    /**
     * Check if the category is taxable.
     */
    public function isTaxable(): bool
    {
        return $this->is_taxable && $this->is_active;
    }

    /**
     * Check if the category requires jurisdiction detection.
     */
    public function requiresJurisdictionDetection(): bool
    {
        return $this->requires_jurisdiction_detection;
    }

    /**
     * Get the default tax treatment for this category.
     */
    public function getDefaultTaxTreatment(): string
    {
        return $this->default_tax_treatment ?? self::TREATMENT_STANDARD;
    }

    /**
     * Check if the category is exempt from taxes.
     */
    public function isExempt(): bool
    {
        return $this->getDefaultTaxTreatment() === self::TREATMENT_EXEMPT;
    }

    /**
     * Get applicable tax rates for a specific jurisdiction.
     */
    public function getTaxRatesForJurisdiction(int $jurisdictionId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeTaxRates()
            ->byJurisdiction($jurisdictionId)
            ->orderByPriority()
            ->get();
    }

    /**
     * Get tax rules for specific conditions.
     */
    public function getTaxRules(array $conditions = []): array
    {
        $rules = $this->tax_rules ?? [];

        // Filter rules based on conditions
        if (! empty($conditions)) {
            $filteredRules = [];

            foreach ($rules as $rule) {
                $ruleApplies = true;

                if (isset($rule['conditions'])) {
                    foreach ($rule['conditions'] as $key => $value) {
                        if (isset($conditions[$key]) && $conditions[$key] !== $value) {
                            $ruleApplies = false;
                            break;
                        }
                    }
                }

                if ($ruleApplies) {
                    $filteredRules[] = $rule;
                }
            }

            return $filteredRules;
        }

        return $rules;
    }

    /**
     * Check if the category has special exemption rules.
     */
    public function hasExemptionRules(): bool
    {
        return ! empty($this->exemption_rules);
    }

    /**
     * Get exemption rules for specific conditions.
     */
    public function getExemptionRules(array $conditions = []): array
    {
        if (! $this->hasExemptionRules()) {
            return [];
        }

        $rules = $this->exemption_rules;

        // Filter rules based on conditions
        if (! empty($conditions)) {
            $filteredRules = [];

            foreach ($rules as $rule) {
                $ruleApplies = true;

                if (isset($rule['conditions'])) {
                    foreach ($rule['conditions'] as $key => $value) {
                        if (isset($conditions[$key]) && $conditions[$key] !== $value) {
                            $ruleApplies = false;
                            break;
                        }
                    }
                }

                if ($ruleApplies) {
                    $filteredRules[] = $rule;
                }
            }

            return $filteredRules;
        }

        return $rules;
    }

    /**
     * Get category type label.
     */
    public function getCategoryTypeLabel(): string
    {
        $labels = [
            self::TYPE_TELECOMMUNICATIONS => 'Telecommunications',
            self::TYPE_INTERNET => 'Internet Services',
            self::TYPE_DATA_SERVICES => 'Data Services',
            self::TYPE_EQUIPMENT => 'Equipment',
            self::TYPE_INSTALLATION => 'Installation Services',
            self::TYPE_MAINTENANCE => 'Maintenance Services',
            self::TYPE_HOSTING => 'Hosting Services',
            self::TYPE_SOFTWARE => 'Software Services',
        ];

        return $labels[$this->category_type] ?? ucfirst(str_replace('_', ' ', $this->category_type));
    }

    /**
     * Scope to get active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get taxable categories.
     */
    public function scopeTaxable($query)
    {
        return $query->where('is_taxable', true);
    }

    /**
     * Scope to get categories by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('category_type', $type);
    }

    /**
     * Scope to get telecommunications categories.
     */
    public function scopeTelecommunications($query)
    {
        return $query->where('category_type', self::TYPE_TELECOMMUNICATIONS);
    }

    /**
     * Scope to get interstate categories.
     */
    public function scopeInterstate($query)
    {
        return $query->where('is_interstate', true);
    }

    /**
     * Scope to get international categories.
     */
    public function scopeInternational($query)
    {
        return $query->where('is_international', true);
    }

    /**
     * Scope to search categories.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%');
        });
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrderByPriority($query, string $direction = 'asc')
    {
        return $query->orderBy('priority', $direction);
    }

    /**
     * Find category by service type.
     */
    public static function findByServiceType(string $serviceType): ?self
    {
        return static::active()
            ->where(function ($q) use ($serviceType) {
                $q->whereNull('service_types')
                    ->orWhereJsonContains('service_types', $serviceType);
            })
            ->orderByPriority()
            ->first();
    }

    /**
     * Get validation rules for category creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tax_categories,code',
            'category_type' => 'required|in:telecommunications,internet,data_services,equipment,installation,maintenance,hosting,software',
            'description' => 'nullable|string',
            'service_types' => 'nullable|array',
            'service_types.*' => 'string',
            'tax_rules' => 'nullable|array',
            'is_taxable' => 'boolean',
            'is_interstate' => 'boolean',
            'is_international' => 'boolean',
            'requires_jurisdiction_detection' => 'boolean',
            'default_tax_treatment' => 'nullable|in:standard,exempt,reduced,special',
            'exemption_rules' => 'nullable|array',
            'priority' => 'integer|min:0|max:999',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get available category types.
     */
    public static function getAvailableCategoryTypes(): array
    {
        return [
            self::TYPE_TELECOMMUNICATIONS => 'Telecommunications',
            self::TYPE_INTERNET => 'Internet Services',
            self::TYPE_DATA_SERVICES => 'Data Services',
            self::TYPE_EQUIPMENT => 'Equipment',
            self::TYPE_INSTALLATION => 'Installation Services',
            self::TYPE_MAINTENANCE => 'Maintenance Services',
            self::TYPE_HOSTING => 'Hosting Services',
            self::TYPE_SOFTWARE => 'Software Services',
        ];
    }

    /**
     * Get available tax treatments.
     */
    public static function getAvailableTaxTreatments(): array
    {
        return [
            self::TREATMENT_STANDARD => 'Standard Taxation',
            self::TREATMENT_EXEMPT => 'Tax Exempt',
            self::TREATMENT_REDUCED => 'Reduced Tax Rate',
            self::TREATMENT_SPECIAL => 'Special Treatment',
        ];
    }

    /**
     * Create default VoIP service categories.
     */
    public static function createDefaultCategories(int $companyId): array
    {
        $categories = [];

        $defaultCategories = [
            [
                'name' => 'Local Service',
                'code' => 'LOCAL',
                'category_type' => self::TYPE_TELECOMMUNICATIONS,
                'description' => 'Local telephone service within LATA boundaries',
                'service_types' => [VoIPTaxRate::SERVICE_TYPE_LOCAL],
                'is_taxable' => true,
                'is_interstate' => false,
                'requires_jurisdiction_detection' => true,
                'priority' => 10,
            ],
            [
                'name' => 'Long Distance',
                'code' => 'LONGDIST',
                'category_type' => self::TYPE_TELECOMMUNICATIONS,
                'description' => 'Long distance and interstate calling',
                'service_types' => [VoIPTaxRate::SERVICE_TYPE_LONG_DISTANCE],
                'is_taxable' => true,
                'is_interstate' => true,
                'requires_jurisdiction_detection' => true,
                'priority' => 20,
            ],
            [
                'name' => 'International',
                'code' => 'INTL',
                'category_type' => self::TYPE_TELECOMMUNICATIONS,
                'description' => 'International calling services',
                'service_types' => [VoIPTaxRate::SERVICE_TYPE_INTERNATIONAL],
                'is_taxable' => true,
                'is_international' => true,
                'requires_jurisdiction_detection' => true,
                'priority' => 30,
            ],
            [
                'name' => 'VoIP Fixed',
                'code' => 'VOIP_FIXED',
                'category_type' => self::TYPE_TELECOMMUNICATIONS,
                'description' => 'Fixed VoIP services',
                'service_types' => [VoIPTaxRate::SERVICE_TYPE_VOIP_FIXED],
                'is_taxable' => true,
                'requires_jurisdiction_detection' => true,
                'priority' => 40,
            ],
            [
                'name' => 'VoIP Nomadic',
                'code' => 'VOIP_NOMADIC',
                'category_type' => self::TYPE_TELECOMMUNICATIONS,
                'description' => 'Nomadic VoIP services',
                'service_types' => [VoIPTaxRate::SERVICE_TYPE_VOIP_NOMADIC],
                'is_taxable' => true,
                'requires_jurisdiction_detection' => true,
                'priority' => 50,
            ],
            [
                'name' => 'Data Services',
                'code' => 'DATA',
                'category_type' => self::TYPE_DATA_SERVICES,
                'description' => 'Data transmission and internet services',
                'service_types' => [VoIPTaxRate::SERVICE_TYPE_DATA, VoIPTaxRate::SERVICE_TYPE_INTERNET],
                'is_taxable' => false,
                'default_tax_treatment' => self::TREATMENT_EXEMPT,
                'priority' => 60,
            ],
            [
                'name' => 'Equipment',
                'code' => 'EQUIPMENT',
                'category_type' => self::TYPE_EQUIPMENT,
                'description' => 'Telecommunications equipment sales',
                'service_types' => [VoIPTaxRate::SERVICE_TYPE_EQUIPMENT],
                'is_taxable' => true,
                'requires_jurisdiction_detection' => true,
                'priority' => 70,
            ],
        ];

        foreach ($defaultCategories as $categoryData) {
            $categoryData['company_id'] = $companyId;
            $categoryData['is_active'] = true;

            $categories[] = static::create($categoryData);
        }

        return $categories;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (! isset($category->priority)) {
                // Set default priority based on category type
                $priorities = [
                    self::TYPE_TELECOMMUNICATIONS => 100,
                    self::TYPE_INTERNET => 200,
                    self::TYPE_DATA_SERVICES => 300,
                    self::TYPE_EQUIPMENT => 400,
                    self::TYPE_INSTALLATION => 500,
                    self::TYPE_MAINTENANCE => 600,
                    self::TYPE_HOSTING => 700,
                    self::TYPE_SOFTWARE => 800,
                ];

                $category->priority = $priorities[$category->category_type] ?? 999;
            }
        });
    }
}
