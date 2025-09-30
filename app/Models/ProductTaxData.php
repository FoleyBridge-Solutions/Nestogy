<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product Tax Data Model
 *
 * Stores category-specific tax data and calculated taxes for products/services.
 *
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int|null $tax_profile_id
 * @property array $tax_data
 * @property array|null $calculated_taxes
 * @property int|null $jurisdiction_id
 * @property float|null $effective_tax_rate
 * @property float|null $total_tax_amount
 * @property \Illuminate\Support\Carbon|null $last_calculated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProductTaxData extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'product_tax_data';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'product_id',
        'tax_profile_id',
        'tax_data',
        'calculated_taxes',
        'jurisdiction_id',
        'effective_tax_rate',
        'total_tax_amount',
        'last_calculated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'product_id' => 'integer',
        'tax_profile_id' => 'integer',
        'tax_data' => 'array',
        'calculated_taxes' => 'array',
        'jurisdiction_id' => 'integer',
        'effective_tax_rate' => 'float',
        'total_tax_amount' => 'float',
        'last_calculated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product this tax data belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the tax profile used.
     */
    public function taxProfile(): BelongsTo
    {
        return $this->belongsTo(TaxProfile::class);
    }

    /**
     * Get the tax jurisdiction.
     */
    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class, 'jurisdiction_id');
    }

    /**
     * Get a specific tax data field.
     */
    public function getTaxDataField(string $field, $default = null)
    {
        return $this->tax_data[$field] ?? $default;
    }

    /**
     * Set a specific tax data field.
     */
    public function setTaxDataField(string $field, $value): void
    {
        $data = $this->tax_data ?? [];
        $data[$field] = $value;
        $this->tax_data = $data;
    }

    /**
     * Check if tax calculation is recent.
     */
    public function isCalculationRecent(int $minutes = 60): bool
    {
        if (! $this->last_calculated_at) {
            return false;
        }

        return $this->last_calculated_at->diffInMinutes(now()) < $minutes;
    }

    /**
     * Update calculated tax data.
     */
    public function updateCalculatedTaxes(array $calculation): void
    {
        $this->calculated_taxes = $calculation;
        $this->effective_tax_rate = $calculation['effective_tax_rate'] ?? 0;
        $this->total_tax_amount = $calculation['total_tax_amount'] ?? 0;
        $this->last_calculated_at = now();
        $this->save();
    }
}
