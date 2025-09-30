<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tax Jurisdiction Model
 *
 * Represents geographic tax jurisdictions for VoIP taxation.
 * Includes federal, state, county, and municipal jurisdictions.
 *
 * @property int $id
 * @property int $company_id
 * @property string $jurisdiction_type
 * @property string $name
 * @property string $code
 * @property string|null $fips_code
 * @property string|null $state_code
 * @property string|null $county_code
 * @property string|null $city_code
 * @property string|null $zip_code
 * @property array|null $zip_codes
 * @property array|null $boundaries
 * @property string|null $parent_jurisdiction_id
 * @property string $authority_name
 * @property string|null $authority_contact
 * @property string|null $website
 * @property string|null $phone
 * @property string|null $email
 * @property array|null $filing_requirements
 * @property bool $is_active
 * @property int $priority
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class TaxJurisdiction extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'tax_jurisdictions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'jurisdiction_type',
        'name',
        'code',
        'fips_code',
        'state_code',
        'county_code',
        'city_code',
        'zip_code',
        'zip_codes',
        'boundaries',
        'parent_jurisdiction_id',
        'authority_name',
        'authority_contact',
        'website',
        'phone',
        'email',
        'filing_requirements',
        'is_active',
        'priority',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'zip_codes' => 'array',
        'boundaries' => 'array',
        'parent_jurisdiction_id' => 'integer',
        'filing_requirements' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Jurisdiction type enumeration
     */
    const TYPE_FEDERAL = 'federal';

    const TYPE_STATE = 'state';

    const TYPE_COUNTY = 'county';

    const TYPE_CITY = 'city';

    const TYPE_MUNICIPALITY = 'municipality';

    const TYPE_SPECIAL_DISTRICT = 'special_district';

    const TYPE_ZIP_CODE = 'zip_code';

    /**
     * Get the tax rates for this jurisdiction.
     */
    public function taxRates(): HasMany
    {
        return $this->hasMany(VoIPTaxRate::class, 'tax_jurisdiction_id');
    }

    /**
     * Get active tax rates for this jurisdiction.
     */
    public function activeTaxRates(): HasMany
    {
        return $this->taxRates()->active();
    }

    /**
     * Get the parent jurisdiction.
     */
    public function parentJurisdiction()
    {
        return $this->belongsTo(TaxJurisdiction::class, 'parent_jurisdiction_id');
    }

    /**
     * Get child jurisdictions.
     */
    public function childJurisdictions(): HasMany
    {
        return $this->hasMany(TaxJurisdiction::class, 'parent_jurisdiction_id');
    }

    /**
     * Get tax exemptions for this jurisdiction.
     */
    public function taxExemptions(): HasMany
    {
        return $this->hasMany(TaxExemption::class, 'tax_jurisdiction_id');
    }

    /**
     * Check if an address is within this jurisdiction.
     */
    public function containsAddress(array $address): bool
    {
        // Check ZIP code match first (most common)
        if (! empty($address['zip_code'])) {
            if ($this->zip_code && $this->zip_code === $address['zip_code']) {
                return true;
            }

            if ($this->zip_codes && in_array($address['zip_code'], $this->zip_codes)) {
                return true;
            }
        }

        // Check state code
        if ($this->jurisdiction_type === self::TYPE_STATE) {
            return $this->state_code === ($address['state_code'] ?? $address['state']);
        }

        // Check FIPS codes if available
        if ($this->fips_code && ! empty($address['fips_code'])) {
            return strpos($address['fips_code'], $this->fips_code) === 0;
        }

        // Check boundaries if defined (for complex jurisdictions)
        if ($this->boundaries && ! empty($address['latitude']) && ! empty($address['longitude'])) {
            return $this->isPointInBoundaries($address['latitude'], $address['longitude']);
        }

        // Fallback to name matching for cities/municipalities
        if (in_array($this->jurisdiction_type, [self::TYPE_CITY, self::TYPE_MUNICIPALITY])) {
            $city = $address['city'] ?? '';

            return strtolower($this->name) === strtolower($city);
        }

        return false;
    }

    /**
     * Check if a point is within the jurisdiction boundaries.
     */
    protected function isPointInBoundaries(float $latitude, float $longitude): bool
    {
        if (empty($this->boundaries) || empty($this->boundaries['polygon'])) {
            return false;
        }

        $polygon = $this->boundaries['polygon'];
        $vertices = count($polygon);
        $inside = false;

        for ($i = 0, $j = $vertices - 1; $i < $vertices; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            if ((($yi > $latitude) !== ($yj > $latitude)) &&
                ($longitude < ($xj - $xi) * ($latitude - $yi) / ($yj - $yi) + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Get the full jurisdiction hierarchy path.
     */
    public function getFullPath(): string
    {
        $path = [$this->name];
        $parent = $this->parentJurisdiction;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parentJurisdiction;
        }

        return implode(' > ', $path);
    }

    /**
     * Get jurisdiction type label.
     */
    public function getJurisdictionTypeLabel(): string
    {
        $labels = [
            self::TYPE_FEDERAL => 'Federal',
            self::TYPE_STATE => 'State',
            self::TYPE_COUNTY => 'County',
            self::TYPE_CITY => 'City',
            self::TYPE_MUNICIPALITY => 'Municipality',
            self::TYPE_SPECIAL_DISTRICT => 'Special District',
            self::TYPE_ZIP_CODE => 'ZIP Code',
        ];

        return $labels[$this->jurisdiction_type] ?? ucfirst($this->jurisdiction_type);
    }

    /**
     * Check if this jurisdiction requires tax filing.
     */
    public function requiresTaxFiling(): bool
    {
        return ! empty($this->filing_requirements) &&
               ($this->filing_requirements['required'] ?? false);
    }

    /**
     * Get filing frequency for this jurisdiction.
     */
    public function getFilingFrequency(): ?string
    {
        return $this->filing_requirements['frequency'] ?? null;
    }

    /**
     * Get filing due date for this jurisdiction.
     */
    public function getFilingDueDate(): ?string
    {
        return $this->filing_requirements['due_date'] ?? null;
    }

    /**
     * Scope to get active jurisdictions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get jurisdictions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('jurisdiction_type', $type);
    }

    /**
     * Scope to get federal jurisdictions.
     */
    public function scopeFederal($query)
    {
        return $query->where('jurisdiction_type', self::TYPE_FEDERAL);
    }

    /**
     * Scope to get state jurisdictions.
     */
    public function scopeState($query)
    {
        return $query->where('jurisdiction_type', self::TYPE_STATE);
    }

    /**
     * Scope to get local jurisdictions.
     */
    public function scopeLocal($query)
    {
        return $query->whereIn('jurisdiction_type', [
            self::TYPE_COUNTY,
            self::TYPE_CITY,
            self::TYPE_MUNICIPALITY,
            self::TYPE_SPECIAL_DISTRICT,
        ]);
    }

    /**
     * Scope to search jurisdictions.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%')
                ->orWhere('authority_name', 'like', '%'.$search.'%');
        });
    }

    /**
     * Scope to get jurisdictions by state.
     */
    public function scopeByState($query, string $stateCode)
    {
        return $query->where('state_code', $stateCode);
    }

    /**
     * Scope to get jurisdictions by ZIP code.
     */
    public function scopeByZipCode($query, string $zipCode)
    {
        return $query->where(function ($q) use ($zipCode) {
            $q->where('zip_code', $zipCode)
                ->orWhereJsonContains('zip_codes', $zipCode);
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
     * Find jurisdictions for a given address.
     */
    public static function findByAddress(array $address): \Illuminate\Database\Eloquent\Collection
    {
        $matchedJurisdictions = [];

        // Get all potential jurisdictions
        $query = static::active();

        // Filter by state first if available
        if (! empty($address['state_code']) || ! empty($address['state'])) {
            $stateCode = $address['state_code'] ?? $address['state'];
            $query->where(function ($q) use ($stateCode) {
                $q->where('state_code', $stateCode)
                    ->orWhere('jurisdiction_type', self::TYPE_FEDERAL);
            });
        }

        // Filter by ZIP code if available
        if (! empty($address['zip_code'])) {
            $query->where(function ($q) use ($address) {
                $q->where('zip_code', $address['zip_code'])
                    ->orWhereJsonContains('zip_codes', $address['zip_code'])
                    ->orWhere('jurisdiction_type', self::TYPE_FEDERAL)
                    ->orWhere('jurisdiction_type', self::TYPE_STATE);
            });
        }

        $potentialJurisdictions = $query->get();

        // Check each jurisdiction to see if it contains the address
        foreach ($potentialJurisdictions as $jurisdiction) {
            if ($jurisdiction->containsAddress($address)) {
                $matchedJurisdictions[] = $jurisdiction;
            }
        }

        // Sort by priority and return as Eloquent Collection
        usort($matchedJurisdictions, function ($a, $b) {
            return $a->priority <=> $b->priority;
        });

        return new \Illuminate\Database\Eloquent\Collection($matchedJurisdictions);
    }

    /**
     * Get validation rules for jurisdiction creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'jurisdiction_type' => 'required|in:federal,state,county,city,municipality,special_district,zip_code',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:tax_jurisdictions,code',
            'fips_code' => 'nullable|string|max:10',
            'state_code' => 'nullable|string|size:2',
            'county_code' => 'nullable|string|max:10',
            'city_code' => 'nullable|string|max:10',
            'zip_code' => 'nullable|string|max:10',
            'zip_codes' => 'nullable|array',
            'zip_codes.*' => 'string|max:10',
            'boundaries' => 'nullable|array',
            'parent_jurisdiction_id' => 'nullable|integer|exists:tax_jurisdictions,id',
            'authority_name' => 'required|string|max:255',
            'authority_contact' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'filing_requirements' => 'nullable|array',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:999',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get available jurisdiction types.
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_FEDERAL => 'Federal',
            self::TYPE_STATE => 'State',
            self::TYPE_COUNTY => 'County',
            self::TYPE_CITY => 'City',
            self::TYPE_MUNICIPALITY => 'Municipality',
            self::TYPE_SPECIAL_DISTRICT => 'Special District',
            self::TYPE_ZIP_CODE => 'ZIP Code',
        ];
    }

    /**
     * Create default federal jurisdiction.
     */
    public static function createFederalJurisdiction(int $companyId): self
    {
        return static::create([
            'company_id' => $companyId,
            'jurisdiction_type' => self::TYPE_FEDERAL,
            'name' => 'United States Federal',
            'code' => 'US-FED',
            'fips_code' => 'US',
            'authority_name' => 'Federal Communications Commission',
            'website' => 'https://www.fcc.gov',
            'is_active' => true,
            'priority' => 1,
            'filing_requirements' => [
                'required' => true,
                'frequency' => 'quarterly',
                'due_date' => 'last_day_of_quarter_plus_30',
            ],
        ]);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jurisdiction) {
            if (! isset($jurisdiction->priority)) {
                // Set default priority based on jurisdiction type
                $priorities = [
                    self::TYPE_FEDERAL => 1,
                    self::TYPE_STATE => 10,
                    self::TYPE_COUNTY => 20,
                    self::TYPE_CITY => 30,
                    self::TYPE_MUNICIPALITY => 40,
                    self::TYPE_SPECIAL_DISTRICT => 50,
                    self::TYPE_ZIP_CODE => 60,
                ];

                $jurisdiction->priority = $priorities[$jurisdiction->jurisdiction_type] ?? 999;
            }
        });
    }
}
