<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * VoIP Tax Rate Model
 * 
 * Stores tax rates for telecommunications services across different jurisdictions.
 * Supports federal, state, and local tax rates with effective date management.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $tax_jurisdiction_id
 * @property int $tax_category_id
 * @property string $tax_type
 * @property string $tax_name
 * @property string $rate_type
 * @property float|null $percentage_rate
 * @property float|null $fixed_amount
 * @property float|null $minimum_threshold
 * @property float|null $maximum_amount
 * @property string $calculation_method
 * @property string $authority_name
 * @property string|null $tax_code
 * @property string|null $description
 * @property array|null $service_types
 * @property array|null $conditions
 * @property bool $is_active
 * @property bool $is_recoverable
 * @property bool $is_compound
 * @property int $priority
 * @property \Illuminate\Support\Carbon $effective_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string|null $external_id
 * @property string|null $source
 * @property \Illuminate\Support\Carbon|null $last_updated_from_source
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class VoIPTaxRate extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'voip_tax_rates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'tax_jurisdiction_id',
        'tax_category_id',
        'tax_type',
        'tax_name',
        'rate_type',
        'percentage_rate',
        'fixed_amount',
        'minimum_threshold',
        'maximum_amount',
        'calculation_method',
        'authority_name',
        'tax_code',
        'description',
        'service_types',
        'conditions',
        'is_active',
        'is_recoverable',
        'is_compound',
        'priority',
        'effective_date',
        'expiry_date',
        'external_id',
        'source',
        'last_updated_from_source',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'tax_jurisdiction_id' => 'integer',
        'tax_category_id' => 'integer',
        'percentage_rate' => 'decimal:4',
        'fixed_amount' => 'decimal:4',
        'minimum_threshold' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'service_types' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'is_recoverable' => 'boolean',
        'is_compound' => 'boolean',
        'priority' => 'integer',
        'effective_date' => 'datetime',
        'expiry_date' => 'datetime',
        'last_updated_from_source' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Tax type enumeration
     */
    const TAX_TYPE_FEDERAL = 'federal';
    const TAX_TYPE_STATE = 'state';
    const TAX_TYPE_LOCAL = 'local';
    const TAX_TYPE_MUNICIPAL = 'municipal';
    const TAX_TYPE_COUNTY = 'county';
    const TAX_TYPE_SPECIAL_DISTRICT = 'special_district';

    /**
     * Rate type enumeration
     */
    const RATE_TYPE_PERCENTAGE = 'percentage';
    const RATE_TYPE_FIXED = 'fixed';
    const RATE_TYPE_TIERED = 'tiered';
    const RATE_TYPE_PER_LINE = 'per_line';
    const RATE_TYPE_PER_MINUTE = 'per_minute';

    /**
     * Calculation method enumeration
     */
    const CALC_METHOD_STANDARD = 'standard';
    const CALC_METHOD_COMPOUND = 'compound';
    const CALC_METHOD_ADDITIVE = 'additive';
    const CALC_METHOD_INCLUSIVE = 'inclusive';
    const CALC_METHOD_EXCLUSIVE = 'exclusive';

    /**
     * Federal tax types
     */
    const FEDERAL_EXCISE_TAX = 'federal_excise_tax';
    const FEDERAL_USF = 'universal_service_fund';
    const FEDERAL_PICC = 'presubscribed_interexchange_carrier_charge';

    /**
     * State tax types
     */
    const STATE_PUC_FEE = 'state_puc_fee';
    const STATE_TELECOM_TAX = 'state_telecommunications_tax';
    const STATE_TRS_FEE = 'telecommunications_relay_service_fee';
    const STATE_911_SURCHARGE = 'state_911_surcharge';

    /**
     * Local tax types  
     */
    const LOCAL_FRANCHISE_FEE = 'local_franchise_fee';
    const LOCAL_RIGHT_OF_WAY = 'right_of_way_fee';
    const LOCAL_911_SURCHARGE = 'local_911_surcharge';
    const LOCAL_MUNICIPAL_TAX = 'municipal_telecommunications_tax';

    /**
     * Service types for VoIP taxation
     */
    const SERVICE_TYPE_LOCAL = 'local';
    const SERVICE_TYPE_LONG_DISTANCE = 'long_distance';
    const SERVICE_TYPE_INTERNATIONAL = 'international';
    const SERVICE_TYPE_DATA = 'data';
    const SERVICE_TYPE_INTERNET = 'internet';
    const SERVICE_TYPE_VOIP_FIXED = 'voip_fixed';
    const SERVICE_TYPE_VOIP_NOMADIC = 'voip_nomadic';
    const SERVICE_TYPE_HOSTED_PBX = 'hosted_pbx';
    const SERVICE_TYPE_SIP_TRUNKING = 'sip_trunking';
    const SERVICE_TYPE_PRI = 'pri';
    const SERVICE_TYPE_FEATURES = 'features';
    const SERVICE_TYPE_EQUIPMENT = 'equipment';

    /**
     * Get the jurisdiction this tax rate belongs to.
     */
    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }

    /**
     * Get the category this tax rate belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class, 'tax_category_id');
    }

    /**
     * Get the historical rate changes.
     */
    public function history(): HasMany
    {
        return $this->hasMany(TaxRateHistory::class);
    }

    /**
     * Check if the tax rate is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();
        
        // Check if effective date has passed
        if ($this->effective_date && $now->lt($this->effective_date)) {
            return false;
        }

        // Check if not expired
        if ($this->expiry_date && $now->gt($this->expiry_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the tax rate will be active on a specific date.
     */
    public function isActiveOnDate(Carbon $date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check effective date
        if ($this->effective_date && $date->lt($this->effective_date)) {
            return false;
        }

        // Check expiry date
        if ($this->expiry_date && $date->gt($this->expiry_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if this tax applies to a specific service type.
     */
    public function appliesTo(string $serviceType): bool
    {
        if (empty($this->service_types)) {
            return true; // Applies to all services if not specified
        }

        return in_array($serviceType, $this->service_types);
    }

    /**
     * Calculate tax amount for a given base amount.
     */
    public function calculateTaxAmount(float $baseAmount, array $options = []): float
    {
        if (!$this->isCurrentlyActive()) {
            return 0.0;
        }

        // Check minimum threshold
        if ($this->minimum_threshold && $baseAmount < $this->minimum_threshold) {
            return 0.0;
        }

        $taxAmount = 0.0;

        switch ($this->rate_type) {
            case self::RATE_TYPE_PERCENTAGE:
                $taxAmount = $baseAmount * ($this->percentage_rate / 100);
                break;

            case self::RATE_TYPE_FIXED:
                $taxAmount = $this->fixed_amount;
                break;

            case self::RATE_TYPE_PER_LINE:
                $lineCount = $options['line_count'] ?? 1;
                $taxAmount = $this->fixed_amount * $lineCount;
                break;

            case self::RATE_TYPE_PER_MINUTE:
                $minutes = $options['minutes'] ?? 0;
                $taxAmount = $this->fixed_amount * $minutes;
                break;

            case self::RATE_TYPE_TIERED:
                $taxAmount = $this->calculateTieredTax($baseAmount, $options);
                break;
        }

        // Apply maximum amount cap if set
        if ($this->maximum_amount && $taxAmount > $this->maximum_amount) {
            $taxAmount = $this->maximum_amount;
        }

        return round($taxAmount, 4);
    }

    /**
     * Calculate tiered tax amount.
     */
    protected function calculateTieredTax(float $baseAmount, array $options = []): float
    {
        $tiers = $this->conditions['tiers'] ?? [];
        $taxAmount = 0.0;
        $remainingAmount = $baseAmount;

        foreach ($tiers as $tier) {
            if ($remainingAmount <= 0) {
                break;
            }

            $tierAmount = min($remainingAmount, $tier['max_amount'] - $tier['min_amount']);
            $tierRate = $tier['rate'] ?? 0;

            if ($tier['rate_type'] === 'percentage') {
                $taxAmount += $tierAmount * ($tierRate / 100);
            } else {
                $taxAmount += $tierRate;
            }

            $remainingAmount -= $tierAmount;
        }

        return $taxAmount;
    }

    /**
     * Get formatted rate display.
     */
    public function getFormattedRate(): string
    {
        switch ($this->rate_type) {
            case self::RATE_TYPE_PERCENTAGE:
                return number_format($this->percentage_rate, 2) . '%';

            case self::RATE_TYPE_FIXED:
                return '$' . number_format($this->fixed_amount, 2);

            case self::RATE_TYPE_PER_LINE:
                return '$' . number_format($this->fixed_amount, 2) . ' per line';

            case self::RATE_TYPE_PER_MINUTE:
                return '$' . number_format($this->fixed_amount, 4) . ' per minute';

            case self::RATE_TYPE_TIERED:
                return 'Tiered rates';

            default:
                return 'N/A';
        }
    }

    /**
     * Get tax type label.
     */
    public function getTaxTypeLabel(): string
    {
        $labels = [
            self::TAX_TYPE_FEDERAL => 'Federal',
            self::TAX_TYPE_STATE => 'State',
            self::TAX_TYPE_LOCAL => 'Local',
            self::TAX_TYPE_MUNICIPAL => 'Municipal',
            self::TAX_TYPE_COUNTY => 'County',
            self::TAX_TYPE_SPECIAL_DISTRICT => 'Special District',
        ];

        return $labels[$this->tax_type] ?? ucfirst($this->tax_type);
    }

    /**
     * Scope to get active tax rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('effective_date', '<=', Carbon::now())
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>', Carbon::now());
                    });
    }

    /**
     * Scope to get tax rates by jurisdiction.
     */
    public function scopeByJurisdiction($query, int $jurisdictionId)
    {
        return $query->where('tax_jurisdiction_id', $jurisdictionId);
    }

    /**
     * Scope to get tax rates by category.
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('tax_category_id', $categoryId);
    }

    /**
     * Scope to get tax rates by type.
     */
    public function scopeByType($query, string $taxType)
    {
        return $query->where('tax_type', $taxType);
    }

    /**
     * Scope to get tax rates for specific service type.
     */
    public function scopeForServiceType($query, string $serviceType)
    {
        return $query->where(function ($q) use ($serviceType) {
            $q->whereNull('service_types')
              ->orWhereJsonContains('service_types', $serviceType);
        });
    }

    /**
     * Scope to get federal tax rates.
     */
    public function scopeFederal($query)
    {
        return $query->where('tax_type', self::TAX_TYPE_FEDERAL);
    }

    /**
     * Scope to get state tax rates.
     */
    public function scopeState($query)
    {
        return $query->where('tax_type', self::TAX_TYPE_STATE);
    }

    /**
     * Scope to get local tax rates.
     */
    public function scopeLocal($query)
    {
        return $query->whereIn('tax_type', [
            self::TAX_TYPE_LOCAL,
            self::TAX_TYPE_MUNICIPAL,
            self::TAX_TYPE_COUNTY,
            self::TAX_TYPE_SPECIAL_DISTRICT
        ]);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrderByPriority($query, string $direction = 'asc')
    {
        return $query->orderBy('priority', $direction);
    }

    /**
     * Get validation rules for tax rate creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'tax_jurisdiction_id' => 'required|integer|exists:tax_jurisdictions,id',
            'tax_category_id' => 'required|integer|exists:tax_categories,id',
            'tax_type' => 'required|in:federal,state,local,municipal,county,special_district',
            'tax_name' => 'required|string|max:255',
            'rate_type' => 'required|in:percentage,fixed,tiered,per_line,per_minute',
            'percentage_rate' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'minimum_threshold' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'calculation_method' => 'required|in:standard,compound,additive,inclusive,exclusive',
            'authority_name' => 'required|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'service_types' => 'nullable|array',
            'conditions' => 'nullable|array',
            'is_active' => 'boolean',
            'is_recoverable' => 'boolean',
            'is_compound' => 'boolean',
            'priority' => 'integer|min:0|max:999',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'external_id' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get available tax types.
     */
    public static function getAvailableTaxTypes(): array
    {
        return [
            self::TAX_TYPE_FEDERAL => 'Federal',
            self::TAX_TYPE_STATE => 'State',
            self::TAX_TYPE_LOCAL => 'Local',
            self::TAX_TYPE_MUNICIPAL => 'Municipal',
            self::TAX_TYPE_COUNTY => 'County',
            self::TAX_TYPE_SPECIAL_DISTRICT => 'Special District',
        ];
    }

    /**
     * Get available rate types.
     */
    public static function getAvailableRateTypes(): array
    {
        return [
            self::RATE_TYPE_PERCENTAGE => 'Percentage',
            self::RATE_TYPE_FIXED => 'Fixed Amount',
            self::RATE_TYPE_TIERED => 'Tiered',
            self::RATE_TYPE_PER_LINE => 'Per Line',
            self::RATE_TYPE_PER_MINUTE => 'Per Minute',
        ];
    }

    /**
     * Get available service types.
     */
    public static function getAvailableServiceTypes(): array
    {
        return [
            self::SERVICE_TYPE_LOCAL => 'Local Service',
            self::SERVICE_TYPE_LONG_DISTANCE => 'Long Distance',
            self::SERVICE_TYPE_INTERNATIONAL => 'International',
            self::SERVICE_TYPE_DATA => 'Data Services',
            self::SERVICE_TYPE_INTERNET => 'Internet',
            self::SERVICE_TYPE_VOIP_FIXED => 'VoIP Fixed',
            self::SERVICE_TYPE_VOIP_NOMADIC => 'VoIP Nomadic',
            self::SERVICE_TYPE_HOSTED_PBX => 'Hosted PBX',
            self::SERVICE_TYPE_SIP_TRUNKING => 'SIP Trunking',
            self::SERVICE_TYPE_PRI => 'PRI',
            self::SERVICE_TYPE_FEATURES => 'Features',
            self::SERVICE_TYPE_EQUIPMENT => 'Equipment',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($taxRate) {
            if (!isset($taxRate->priority)) {
                // Set default priority based on tax type
                $priorities = [
                    self::TAX_TYPE_FEDERAL => 100,
                    self::TAX_TYPE_STATE => 200,
                    self::TAX_TYPE_COUNTY => 300,
                    self::TAX_TYPE_LOCAL => 400,
                    self::TAX_TYPE_MUNICIPAL => 500,
                    self::TAX_TYPE_SPECIAL_DISTRICT => 600,
                ];
                
                $taxRate->priority = $priorities[$taxRate->tax_type] ?? 999;
            }
        });

        // Create history record when rates are updated
        static::updated(function ($taxRate) {
            $taxRate->history()->create([
                'company_id' => $taxRate->company_id,
                'old_values' => $taxRate->getOriginal(),
                'new_values' => $taxRate->getAttributes(),
                'changed_by' => auth()->id(),
                'change_reason' => 'Rate update',
            ]);
        });
    }
}