<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tax Exemption Usage Model
 *
 * Tracks usage of tax exemptions for audit and reporting purposes.
 * Records when and how tax exemptions are applied.
 *
 * @property int $id
 * @property int $company_id
 * @property int $tax_exemption_id
 * @property int|null $client_id
 * @property int|null $invoice_id
 * @property int|null $quote_id
 * @property int|null $invoice_item_id
 * @property float $original_tax_amount
 * @property float $exempted_amount
 * @property float $final_tax_amount
 * @property string|null $exemption_reason
 * @property array|null $calculation_details
 * @property \Illuminate\Support\Carbon $used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TaxExemptionUsage extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'tax_exemption_usage';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'tax_exemption_id',
        'client_id',
        'invoice_id',
        'quote_id',
        'invoice_item_id',
        'original_tax_amount',
        'exempted_amount',
        'final_tax_amount',
        'exemption_reason',
        'calculation_details',
        'used_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'tax_exemption_id' => 'integer',
        'client_id' => 'integer',
        'invoice_id' => 'integer',
        'quote_id' => 'integer',
        'invoice_item_id' => 'integer',
        'original_tax_amount' => 'decimal:4',
        'exempted_amount' => 'decimal:4',
        'final_tax_amount' => 'decimal:4',
        'calculation_details' => 'array',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tax exemption this usage record belongs to.
     */
    public function taxExemption(): BelongsTo
    {
        return $this->belongsTo(TaxExemption::class);
    }

    /**
     * Get the client this usage record belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the invoice this usage record belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the quote this usage record belongs to.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the invoice item this usage record belongs to.
     */
    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    /**
     * Calculate the exemption percentage applied.
     */
    public function getExemptionPercentage(): float
    {
        if ($this->original_tax_amount <= 0) {
            return 0.0;
        }

        return round(($this->exempted_amount / $this->original_tax_amount) * 100, 2);
    }

    /**
     * Get formatted exempted amount.
     */
    public function getFormattedExemptedAmount(): string
    {
        return '$'.number_format($this->exempted_amount, 2);
    }

    /**
     * Get formatted original tax amount.
     */
    public function getFormattedOriginalAmount(): string
    {
        return '$'.number_format($this->original_tax_amount, 2);
    }

    /**
     * Get formatted final tax amount.
     */
    public function getFormattedFinalAmount(): string
    {
        return '$'.number_format($this->final_tax_amount, 2);
    }

    /**
     * Scope to get usage for a specific exemption.
     */
    public function scopeForExemption($query, int $exemptionId)
    {
        return $query->where('tax_exemption_id', $exemptionId);
    }

    /**
     * Scope to get usage for a specific client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get usage for a specific invoice.
     */
    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope to get usage for a specific quote.
     */
    public function scopeForQuote($query, int $quoteId)
    {
        return $query->where('quote_id', $quoteId);
    }

    /**
     * Scope to get usage within date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('used_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent usage.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('used_at', '>=', now()->subDays($days));
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($usage) {
            if (! $usage->used_at) {
                $usage->used_at = now();
            }
        });
    }
}
