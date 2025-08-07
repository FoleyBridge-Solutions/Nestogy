<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tax Model
 * 
 * Represents tax rates for products and invoice items.
 * Supports percentage-based tax calculations.
 * 
 * @property int $id
 * @property string $name
 * @property float $percent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class Tax extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'taxes';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'percent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'percent' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Get products using this tax rate.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get invoice items using this tax rate.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Check if tax is archived.
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Get formatted tax rate.
     */
    public function getFormattedRate(): string
    {
        return number_format($this->percent, 2) . '%';
    }

    /**
     * Calculate tax amount for a given subtotal.
     */
    public function calculateTaxAmount(float $subtotal): float
    {
        return round($subtotal * ($this->percent / 100), 2);
    }

    /**
     * Calculate total including tax for a given subtotal.
     */
    public function calculateTotalWithTax(float $subtotal): float
    {
        return $subtotal + $this->calculateTaxAmount($subtotal);
    }

    /**
     * Get tax rate as decimal (for calculations).
     */
    public function getDecimalRate(): float
    {
        return $this->percent / 100;
    }

    /**
     * Check if this is a zero tax rate.
     */
    public function isZeroRate(): bool
    {
        return $this->percent == 0;
    }

    /**
     * Get display name with rate.
     */
    public function getDisplayName(): string
    {
        return $this->name . ' (' . $this->getFormattedRate() . ')';
    }

    /**
     * Get product count using this tax.
     */
    public function getProductCount(): int
    {
        return $this->products()->count();
    }

    /**
     * Get invoice item count using this tax.
     */
    public function getInvoiceItemCount(): int
    {
        return $this->invoiceItems()->count();
    }

    /**
     * Check if tax is in use.
     */
    public function isInUse(): bool
    {
        return $this->getProductCount() > 0 || $this->getInvoiceItemCount() > 0;
    }

    /**
     * Scope to search taxes.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }

    /**
     * Scope to get taxes by rate range.
     */
    public function scopeByRateRange($query, float $min, float $max)
    {
        return $query->whereBetween('percent', [$min, $max]);
    }

    /**
     * Scope to get zero rate taxes.
     */
    public function scopeZeroRate($query)
    {
        return $query->where('percent', 0);
    }

    /**
     * Scope to get non-zero rate taxes.
     */
    public function scopeNonZeroRate($query)
    {
        return $query->where('percent', '>', 0);
    }

    /**
     * Scope to order by rate.
     */
    public function scopeOrderByRate($query, string $direction = 'asc')
    {
        return $query->orderBy('percent', $direction);
    }

    /**
     * Get validation rules for tax creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:taxes,name',
            'percent' => 'required|numeric|min:0|max:100',
        ];
    }

    /**
     * Get validation rules for tax update.
     */
    public static function getUpdateValidationRules(int $taxId): array
    {
        return [
            'name' => 'required|string|max:255|unique:taxes,name,' . $taxId,
            'percent' => 'required|numeric|min:0|max:100',
        ];
    }

    /**
     * Create common tax rates.
     */
    public static function createCommonRates(): void
    {
        $commonRates = [
            ['name' => 'No Tax', 'percent' => 0.00],
            ['name' => 'Sales Tax', 'percent' => 8.25],
            ['name' => 'VAT', 'percent' => 20.00],
            ['name' => 'GST', 'percent' => 10.00],
        ];

        foreach ($commonRates as $rate) {
            static::firstOrCreate(['name' => $rate['name']], $rate);
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent deletion of taxes that are in use
        static::deleting(function ($tax) {
            if ($tax->isInUse()) {
                throw new \Exception('Cannot delete tax rate that is in use by products or invoice items');
            }
        });
    }
}