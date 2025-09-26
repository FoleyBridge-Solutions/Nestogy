<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Company Model
 * 
 * Represents companies in the multi-tenant ERP system.
 * Each company has its own settings and can have multiple users, clients, etc.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $logo
 * @property string|null $locale
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Company extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'email',
        'website',
        'logo',
        'locale',
        'currency',
        'client_record_id',
        'is_active',
        'suspended_at',
        'suspension_reason',
        'hourly_rate_config',
        'default_standard_rate',
        'default_after_hours_rate',
        'default_emergency_rate',
        'default_weekend_rate',
        'default_holiday_rate',
        'after_hours_multiplier',
        'emergency_multiplier',
        'weekend_multiplier',
        'holiday_multiplier',
        'rate_calculation_method',
        'minimum_billing_increment',
        'time_rounding_method',
        // Hierarchy fields
        'parent_company_id',
        'company_type',
        'organizational_level',
        'subsidiary_settings',
        'access_level',
        'billing_type',
        'billing_parent_id',
        'can_create_subsidiaries',
        'max_subsidiary_depth',
        'inherited_permissions',
        // Email provider fields
        'email_provider_type',
        'email_provider_config',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'client_record_id' => 'integer',
        'is_active' => 'boolean',
        'suspended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'hourly_rate_config' => 'array',
        'default_standard_rate' => 'decimal:2',
        'default_after_hours_rate' => 'decimal:2',
        'default_emergency_rate' => 'decimal:2',
        'default_weekend_rate' => 'decimal:2',
        'default_holiday_rate' => 'decimal:2',
        'after_hours_multiplier' => 'decimal:2',
        'emergency_multiplier' => 'decimal:2',
        'weekend_multiplier' => 'decimal:2',
        'holiday_multiplier' => 'decimal:2',
        'minimum_billing_increment' => 'decimal:2',
        // Hierarchy casts
        'subsidiary_settings' => 'array',
        'inherited_permissions' => 'array',
        'can_create_subsidiaries' => 'boolean',
        // Email provider casts
        'email_provider_config' => 'array',
    ];

    /**
     * Default currency codes
     */
    const DEFAULT_CURRENCY = 'USD';
    
    /**
     * Supported currencies
     */
    const SUPPORTED_CURRENCIES = [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'CAD' => 'Canadian Dollar',
        'AUD' => 'Australian Dollar',
        'JPY' => 'Japanese Yen',
    ];

    /**
     * Get the company's settings.
     */
    public function setting(): HasOne
    {
        return $this->hasOne(Setting::class);
    }

    /**
     * Get the company's subscription.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class);
    }

    /**
     * Get the company's users.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the company's clients.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Get the company's customizations.
     */
    public function customization(): HasOne
    {
        return $this->hasOne(CompanyCustomization::class);
    }

    /**
     * Get the company's contract configurations.
     */
    public function contractConfigurations(): HasMany
    {
        return $this->hasMany(\App\Models\ContractConfiguration::class);
    }
    
    /**
     * Get the company's mail settings.
     */
    public function mailSettings(): HasOne
    {
        return $this->hasOne(CompanyMailSettings::class);
    }

    /**
     * Get the client record in Company 1 for billing (for tenant companies).
     */
    public function clientRecord()
    {
        return $this->belongsTo(Client::class, 'client_record_id');
    }

    // ===== HIERARCHY RELATIONSHIPS =====

    /**
     * Get the parent company.
     */
    public function parentCompany()
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    /**
     * Get direct child companies (subsidiaries).
     */
    public function childCompanies()
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /**
     * Get the billing parent company.
     */
    public function billingParent()
    {
        return $this->belongsTo(Company::class, 'billing_parent_id');
    }

    /**
     * Get companies that bill through this company.
     */
    public function billingChildren()
    {
        return $this->hasMany(Company::class, 'billing_parent_id');
    }

    /**
     * Get all hierarchy relationships where this company is the ancestor.
     */
    public function descendantHierarchies()
    {
        return $this->hasMany(CompanyHierarchy::class, 'ancestor_id');
    }

    /**
     * Get all hierarchy relationships where this company is the descendant.
     */
    public function ancestorHierarchies()
    {
        return $this->hasMany(CompanyHierarchy::class, 'descendant_id');
    }

    /**
     * Get subsidiary permissions granted by this company.
     */
    public function grantedPermissions()
    {
        return $this->hasMany(SubsidiaryPermission::class, 'granter_company_id');
    }

    /**
     * Get subsidiary permissions granted to this company.
     */
    public function receivedPermissions()
    {
        return $this->hasMany(SubsidiaryPermission::class, 'grantee_company_id');
    }

    /**
     * Get cross-company user access for this company.
     */
    public function crossCompanyUsers()
    {
        return $this->hasMany(CrossCompanyUser::class, 'company_id');
    }

    /**
     * Get the company's full address.
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the company's logo URL.
     */
    public function getLogoUrl(): ?string
    {
        if ($this->logo) {
            return asset('storage/companies/' . $this->logo);
        }

        return null;
    }

    /**
     * Get the currency symbol.
     */
    public function getCurrencySymbol(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
        ];

        return $symbols[$this->currency] ?? $this->currency;
    }

    /**
     * Get the currency name.
     */
    public function getCurrencyName(): string
    {
        return self::SUPPORTED_CURRENCIES[$this->currency] ?? $this->currency;
    }

    /**
     * Format amount with company currency.
     */
    public function formatCurrency(float $amount): string
    {
        return $this->getCurrencySymbol() . number_format($amount, 2);
    }

    /**
     * Check if company has a logo.
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo);
    }

    /**
     * Check if company has complete address information.
     */
    public function hasCompleteAddress(): bool
    {
        return !empty($this->address) && !empty($this->city) && !empty($this->state);
    }

    /**
     * Get the company's locale or default.
     */
    public function getLocale(): string
    {
        return $this->locale ?? 'en_US';
    }

    /**
     * Get the company's timezone from settings.
     */
    public function getTimezone(): string
    {
        return $this->setting?->timezone ?? 'America/New_York';
    }

    /**
     * Scope to search companies by name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
    }

    /**
     * Scope to get companies by currency.
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Get validation rules for company creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'locale' => 'nullable|string|max:10',
            'currency' => 'required|string|size:3|in:' . implode(',', array_keys(self::SUPPORTED_CURRENCIES)),
        ];
    }

    /**
     * Get validation rules for company update.
     */
    public static function getUpdateValidationRules(int $companyId): array
    {
        $rules = self::getValidationRules();
        // No unique constraints to modify for company updates
        return $rules;
    }

    /**
     * Get supported currencies for selection.
     */
    public static function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Rate calculation methods
     */
    const RATE_METHOD_FIXED = 'fixed_rates';
    const RATE_METHOD_MULTIPLIERS = 'multipliers';
    
    /**
     * Time rounding methods
     */
    const ROUNDING_NONE = 'none';
    const ROUNDING_UP = 'up';
    const ROUNDING_DOWN = 'down';
    const ROUNDING_NEAREST = 'nearest';
    
    /**
     * Rate types
     */
    const RATE_STANDARD = 'standard';
    const RATE_AFTER_HOURS = 'after_hours';
    const RATE_EMERGENCY = 'emergency';
    const RATE_WEEKEND = 'weekend';
    const RATE_HOLIDAY = 'holiday';

    /**
     * Get hourly rate for a specific rate type.
     */
    public function getHourlyRate(string $rateType): float
    {
        if ($this->rate_calculation_method === self::RATE_METHOD_FIXED) {
            return $this->getFixedRate($rateType);
        }
        
        // Use multiplier method
        $baseRate = $this->default_standard_rate ?? 150.00;
        $multiplier = $this->getMultiplier($rateType);
        
        return round($baseRate * $multiplier, 2);
    }
    
    /**
     * Get fixed rate for rate type.
     */
    protected function getFixedRate(string $rateType): float
    {
        $rates = [
            self::RATE_STANDARD => $this->default_standard_rate ?? 150.00,
            self::RATE_AFTER_HOURS => $this->default_after_hours_rate ?? 225.00,
            self::RATE_EMERGENCY => $this->default_emergency_rate ?? 300.00,
            self::RATE_WEEKEND => $this->default_weekend_rate ?? 200.00,
            self::RATE_HOLIDAY => $this->default_holiday_rate ?? 250.00,
        ];
        
        return $rates[$rateType] ?? $rates[self::RATE_STANDARD];
    }
    
    /**
     * Get multiplier for rate type.
     */
    public function getMultiplier(string $rateType): float
    {
        $multipliers = [
            self::RATE_STANDARD => 1.0,
            self::RATE_AFTER_HOURS => $this->after_hours_multiplier ?? 1.5,
            self::RATE_EMERGENCY => $this->emergency_multiplier ?? 2.0,
            self::RATE_WEEKEND => $this->weekend_multiplier ?? 1.5,
            self::RATE_HOLIDAY => $this->holiday_multiplier ?? 2.0,
        ];
        
        return $multipliers[$rateType] ?? 1.0;
    }
    
    /**
     * Get available rate types.
     */
    public static function getRateTypes(): array
    {
        return [
            self::RATE_STANDARD => 'Standard',
            self::RATE_AFTER_HOURS => 'After Hours',
            self::RATE_EMERGENCY => 'Emergency',
            self::RATE_WEEKEND => 'Weekend',
            self::RATE_HOLIDAY => 'Holiday',
        ];
    }
    
    /**
     * Round time according to company settings.
     */
    public function roundTime(float $hours): float
    {
        $increment = $this->minimum_billing_increment ?? 0.25;
        
        switch ($this->time_rounding_method) {
            case self::ROUNDING_UP:
                return ceil($hours / $increment) * $increment;
            case self::ROUNDING_DOWN:
                return floor($hours / $increment) * $increment;
            case self::ROUNDING_NEAREST:
                return round($hours / $increment) * $increment;
            default:
                return $hours;
        }
    }

    // ===== HIERARCHY HELPER METHODS =====

    /**
     * Check if this company is a root company (no parent).
     */
    public function isRoot(): bool
    {
        return $this->company_type === 'root' || $this->parent_company_id === null;
    }

    /**
     * Check if this company is a subsidiary.
     */
    public function isSubsidiary(): bool
    {
        return $this->company_type === 'subsidiary' && $this->parent_company_id !== null;
    }

    /**
     * Check if this company can create subsidiaries.
     */
    public function canCreateSubsidiaries(): bool
    {
        return $this->can_create_subsidiaries === true;
    }

    /**
     * Get all descendant companies (children, grandchildren, etc).
     */
    public function getAllDescendants()
    {
        return CompanyHierarchy::getDescendants($this->id)
            ->pluck('descendant_id')
            ->map(fn($id) => Company::find($id))
            ->filter();
    }

    /**
     * Get all ancestor companies (parent, grandparent, etc).
     */
    public function getAllAncestors()
    {
        return CompanyHierarchy::getAncestors($this->id)
            ->pluck('ancestor_id')
            ->map(fn($id) => Company::find($id))
            ->filter();
    }

    /**
     * Get the root company in the hierarchy.
     */
    public function getRootCompany(): ?Company
    {
        return CompanyHierarchy::getRoot($this->id);
    }

    /**
     * Check if this company can access another company's data.
     */
    public function canAccessCompany(int $companyId): bool
    {
        // Same company
        if ($this->id === $companyId) {
            return true;
        }

        // Check if it's in the hierarchy
        return CompanyHierarchy::areRelated($this->id, $companyId);
    }

    /**
     * Get the effective billing parent (could be self if independent).
     */
    public function getEffectiveBillingParent(): Company
    {
        if ($this->billing_type === 'independent' || !$this->billing_parent_id) {
            return $this;
        }

        return $this->billingParent ?? $this;
    }

    /**
     * Check if company has reached maximum subsidiary depth.
     */
    public function hasReachedMaxSubsidiaryDepth(): bool
    {
        return $this->organizational_level >= $this->max_subsidiary_depth;
    }

    /**
     * Get company hierarchy tree starting from this company.
     */
    public function getHierarchyTree(): array
    {
        return CompanyHierarchy::getTree($this->id);
    }

    /**
     * Create a subsidiary company.
     */
    public function createSubsidiary(array $data): ?Company
    {
        if (!$this->canCreateSubsidiaries()) {
            return null;
        }

        if ($this->hasReachedMaxSubsidiaryDepth()) {
            return null;
        }

        $subsidiaryData = array_merge($data, [
            'parent_company_id' => $this->id,
            'company_type' => 'subsidiary',
            'organizational_level' => $this->organizational_level + 1,
            'billing_type' => $data['billing_type'] ?? 'parent_billed',
            'billing_parent_id' => $data['billing_parent_id'] ?? $this->id,
        ]);

        $subsidiary = static::create($subsidiaryData);

        if ($subsidiary) {
            CompanyHierarchy::addToHierarchy($this->id, $subsidiary->id, 'subsidiary');
        }

        return $subsidiary;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default currency if not provided
        static::creating(function ($company) {
            if (empty($company->currency)) {
                $company->currency = self::DEFAULT_CURRENCY;
            }
        });

        // Create default settings when company is created
        static::created(function ($company) {
            Setting::create([
                'company_id' => $company->id,
                'current_database_version' => '1.0.0',
                'start_page' => 'clients.php',
                'default_net_terms' => 30,
                'default_hourly_rate' => 0.00,
                'invoice_next_number' => 1,
                'quote_next_number' => 1,
                'ticket_next_number' => 1,
                'theme' => 'blue',
                'timezone' => 'America/New_York',
            ]);
        });
    }
}