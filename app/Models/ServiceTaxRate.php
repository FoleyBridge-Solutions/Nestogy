<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ServiceTaxRate Model
 *
 * Tax rates for all service types including telecommunications, cloud services,
 * SaaS, and professional services. Supports complex tax scenarios including
 * regulatory fees (E911, USF), excise taxes, and jurisdiction-based rates.
 *
 * @property int $id
 * @property int $company_id
 * @property int $tax_jurisdiction_id
 * @property int $tax_category_id
 * @property string $service_type
 * @property string $tax_type
 * @property string $tax_name
 * @property string $authority_name
 * @property string|null $tax_code
 * @property string|null $description
 * @property string|null $regulatory_code
 * @property string $rate_type
 * @property float|null $percentage_rate
 * @property float|null $fixed_amount
 * @property float|null $minimum_threshold
 * @property float|null $maximum_amount
 * @property string $calculation_method
 * @property array|null $service_types
 * @property array|null $conditions
 * @property bool $is_active
 * @property bool $is_recoverable
 * @property bool $is_compound
 * @property int $priority
 * @property \Carbon\Carbon $effective_date
 * @property \Carbon\Carbon|null $expiry_date
 * @property string|null $external_id
 * @property string|null $source
 * @property \Carbon\Carbon|null $last_updated_from_source
 * @property array|null $metadata
 */
class ServiceTaxRate extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'service_tax_rates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'tax_jurisdiction_id',
        'tax_category_id',
        'service_type',
        'tax_type',
        'tax_name',
        'authority_name',
        'tax_code',
        'description',
        'regulatory_code',
        'rate_type',
        'percentage_rate',
        'fixed_amount',
        'minimum_threshold',
        'maximum_amount',
        'calculation_method',
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
        'service_types' => 'array',
        'conditions' => 'array',
        'metadata' => 'array',
        'percentage_rate' => 'float',
        'fixed_amount' => 'float',
        'minimum_threshold' => 'float',
        'maximum_amount' => 'float',
        'is_active' => 'boolean',
        'is_recoverable' => 'boolean',
        'is_compound' => 'boolean',
        'priority' => 'integer',
        'effective_date' => 'datetime',
        'expiry_date' => 'datetime',
        'last_updated_from_source' => 'datetime',
    ];

    /**
     * Service types
     */
    const SERVICE_VOIP = 'voip';

    const SERVICE_TELECOM = 'telecom';

    const SERVICE_CLOUD = 'cloud';

    const SERVICE_SAAS = 'saas';

    const SERVICE_HOSTING = 'hosting';

    const SERVICE_MANAGED = 'managed_services';

    const SERVICE_PROFESSIONAL = 'professional';

    const SERVICE_EQUIPMENT = 'equipment';

    /**
     * Tax types
     */
    const TAX_FEDERAL = 'federal';

    const TAX_STATE = 'state';

    const TAX_LOCAL = 'local';

    const TAX_MUNICIPAL = 'municipal';

    const TAX_COUNTY = 'county';

    const TAX_SPECIAL_DISTRICT = 'special_district';

    const TAX_REGULATORY = 'regulatory';

    const TAX_EXCISE = 'excise';

    const TAX_SALES = 'sales';

    const TAX_VAT = 'vat';

    const TAX_CUSTOM = 'custom';

    /**
     * Regulatory codes for special taxes
     */
    const REG_E911 = 'e911';

    const REG_USF = 'usf';

    const REG_ACCESS_RECOVERY = 'access_recovery';

    const REG_REGULATORY_RECOVERY = 'regulatory_recovery';

    const REG_STATE_USF = 'state_usf';

    /**
     * Rate types
     */
    const RATE_PERCENTAGE = 'percentage';

    const RATE_FIXED = 'fixed';

    const RATE_TIERED = 'tiered';

    const RATE_PER_LINE = 'per_line';

    const RATE_PER_MINUTE = 'per_minute';

    const RATE_PER_UNIT = 'per_unit';

    /**
     * Calculation methods
     */
    const CALC_STANDARD = 'standard';

    const CALC_COMPOUND = 'compound';

    const CALC_ADDITIVE = 'additive';

    const CALC_INCLUSIVE = 'inclusive';

    const CALC_EXCLUSIVE = 'exclusive';

    /**
     * Get the tax jurisdiction.
     */
    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }

    /**
     * Get the tax category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class, 'tax_category_id');
    }

    /**
     * Check if this is a regulatory fee.
     */
    public function isRegulatoryFee(): bool
    {
        return $this->tax_type === self::TAX_REGULATORY || ! empty($this->regulatory_code);
    }

    /**
     * Check if this is a telecommunications tax.
     */
    public function isTelecomTax(): bool
    {
        return in_array($this->service_type, [self::SERVICE_VOIP, self::SERVICE_TELECOM]);
    }

    /**
     * Get the effective rate for calculation.
     */
    public function getEffectiveRate(): float
    {
        if ($this->rate_type === self::RATE_PERCENTAGE) {
            return $this->percentage_rate ?? 0;
        }

        return 0;
    }

    /**
     * Get the fixed charge amount.
     */
    public function getFixedCharge(): float
    {
        if (in_array($this->rate_type, [self::RATE_FIXED, self::RATE_PER_LINE, self::RATE_PER_MINUTE, self::RATE_PER_UNIT])) {
            return $this->fixed_amount ?? 0;
        }

        return 0;
    }

    /**
     * Scope for active rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_date')
                    ->orWhere('effective_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            });
    }

    /**
     * Scope for service type.
     */
    public function scopeForService($query, string $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Scope for regulatory fees.
     */
    public function scopeRegulatoryFees($query)
    {
        return $query->where('tax_type', self::TAX_REGULATORY)
            ->orWhereNotNull('regulatory_code');
    }

    /**
     * Scope for specific regulatory code.
     */
    public function scopeByRegulatoryCode($query, string $code)
    {
        return $query->where('regulatory_code', $code);
    }

    /**
     * Get validation rules.
     */
    public static function getValidationRules(): array
    {
        return [
            'tax_jurisdiction_id' => 'required|exists:tax_jurisdictions,id',
            'tax_category_id' => 'required|exists:tax_categories,id',
            'service_type' => 'required|string|max:50',
            'tax_type' => 'required|in:'.implode(',', static::getTaxTypes()),
            'tax_name' => 'required|string|max:255',
            'authority_name' => 'required|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'regulatory_code' => 'nullable|string|max:50',
            'rate_type' => 'required|in:'.implode(',', static::getRateTypes()),
            'percentage_rate' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'calculation_method' => 'required|in:'.implode(',', static::getCalculationMethods()),
            'is_active' => 'boolean',
            'is_recoverable' => 'boolean',
            'is_compound' => 'boolean',
            'priority' => 'integer|min:0|max:999',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
        ];
    }

    /**
     * Get available service types.
     */
    public static function getServiceTypes(): array
    {
        return [
            self::SERVICE_VOIP,
            self::SERVICE_TELECOM,
            self::SERVICE_CLOUD,
            self::SERVICE_SAAS,
            self::SERVICE_HOSTING,
            self::SERVICE_MANAGED,
            self::SERVICE_PROFESSIONAL,
            self::SERVICE_EQUIPMENT,
        ];
    }

    /**
     * Get available tax types.
     */
    public static function getTaxTypes(): array
    {
        return [
            self::TAX_FEDERAL,
            self::TAX_STATE,
            self::TAX_LOCAL,
            self::TAX_MUNICIPAL,
            self::TAX_COUNTY,
            self::TAX_SPECIAL_DISTRICT,
            self::TAX_REGULATORY,
            self::TAX_EXCISE,
            self::TAX_SALES,
            self::TAX_VAT,
            self::TAX_CUSTOM,
        ];
    }

    /**
     * Get available rate types.
     */
    public static function getRateTypes(): array
    {
        return [
            self::RATE_PERCENTAGE,
            self::RATE_FIXED,
            self::RATE_TIERED,
            self::RATE_PER_LINE,
            self::RATE_PER_MINUTE,
            self::RATE_PER_UNIT,
        ];
    }

    /**
     * Get available calculation methods.
     */
    public static function getCalculationMethods(): array
    {
        return [
            self::CALC_STANDARD,
            self::CALC_COMPOUND,
            self::CALC_ADDITIVE,
            self::CALC_INCLUSIVE,
            self::CALC_EXCLUSIVE,
        ];
    }

    /**
     * Get regulatory codes.
     */
    public static function getRegulatoryCodes(): array
    {
        return [
            self::REG_E911 => 'E911 Emergency Service',
            self::REG_USF => 'Universal Service Fund',
            self::REG_ACCESS_RECOVERY => 'Access Recovery Charge',
            self::REG_REGULATORY_RECOVERY => 'Regulatory Recovery Fee',
            self::REG_STATE_USF => 'State Universal Service Fund',
        ];
    }
}
