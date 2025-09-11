<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Services\VoIPTaxService;
use App\Models\TaxExemption;
use App\Models\TaxExemptionUsage;
use App\Models\Recurring;
use App\Traits\BelongsToCompany;

/**
 * Invoice Model
 * 
 * Represents client invoices with line items, payments, and status tracking.
 * Supports multi-currency and public access via URL keys.
 * 
 * @property int $id
 * @property string|null $prefix
 * @property int $number
 * @property string|null $scope
 * @property string $status
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon $due
 * @property float $discount_amount
 * @property float $amount
 * @property string $currency_code
 * @property string|null $note
 * @property string|null $url_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property int $category_id
 * @property int $client_id
 */
class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'prefix',
        'number',
        'scope',
        'status',
        'is_recurring',
        'recurring_invoice_id',
        'recurring_frequency',
        'next_recurring_date',
        'date',
        'due',
        'due_date',
        'discount_amount',
        'amount',
        'currency_code',
        'note',
        'url_key',
        'category_id',
        'client_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'number' => 'integer',
        'is_recurring' => 'boolean',
        'recurring_invoice_id' => 'integer',
        'next_recurring_date' => 'date',
        'date' => 'date',
        'due' => 'date',
        'due_date' => 'date',
        'discount_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'category_id' => 'integer',
        'client_id' => 'integer',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Invoice status enumeration
     */
    const STATUS_DRAFT = 'Draft';
    const STATUS_SENT = 'Sent';
    const STATUS_PAID = 'Paid';
    const STATUS_OVERDUE = 'Overdue';
    const STATUS_CANCELLED = 'Cancelled';

    /**
     * Get the client this invoice belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the category this invoice belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the recurring invoice this invoice was generated from.
     */
    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(Recurring::class, 'recurring_invoice_id');
    }

    /**
     * Get payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get tickets associated with this invoice.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get tax exemptions for this invoice's client.
     */
    public function taxExemptions(): HasMany
    {
        return $this->hasMany(TaxExemption::class, 'client_id', 'client_id')
                    ->where('company_id', $this->company_id);
    }

    /**
     * Get tax exemption usage records for this invoice.
     */
    public function taxExemptionUsage(): HasMany
    {
        return $this->hasMany(TaxExemptionUsage::class);
    }

    /**
     * Get tax calculations for this invoice.
     */
    public function taxCalculations()
    {
        return $this->morphMany(TaxCalculation::class, 'calculable');
    }

    /**
     * Get the latest tax calculation for this invoice.
     */
    public function latestTaxCalculation()
    {
        return $this->taxCalculations()
                   ->where('status', '!=', 'voided')
                   ->latest()
                   ->first();
    }

    /**
     * Get formatted tax breakdown by jurisdiction.
     */
    public function getFormattedTaxBreakdown(): array
    {
        $taxCalculation = $this->latestTaxCalculation();
        
        if (!$taxCalculation) {
            return [
                'total_tax' => 0,
                'jurisdictions' => [],
                'breakdown' => [],
                'has_detailed_breakdown' => false,
            ];
        }

        $jurisdictions = $taxCalculation->getJurisdictionBreakdown();
        $breakdown = $taxCalculation->getTaxBreakdownSummary();

        return [
            'total_tax' => $taxCalculation->total_tax_amount,
            'effective_rate' => $taxCalculation->effective_tax_rate,
            'jurisdictions' => $jurisdictions,
            'breakdown' => $breakdown,
            'has_detailed_breakdown' => count($jurisdictions) > 0 || count($breakdown) > 0,
            'calculation' => $taxCalculation,
        ];
    }

    /**
     * Calculate VoIP taxes for all invoice items.
     */
    public function calculateVoIPTaxes(?array $serviceAddress = null): array
    {
        $taxService = new VoIPTaxService();
        $taxService->setCompanyId($this->company_id);
        $allCalculations = [];
        $totalTaxAmount = 0;

        $address = $serviceAddress ?? $this->getServiceAddress();

        foreach ($this->items as $item) {
            if ($item->service_type) {
                $params = [
                    'amount' => $item->subtotal - $item->discount,
                    'service_type' => $item->service_type,
                    'service_address' => $address,
                    'client_id' => $this->client_id,
                    'calculation_date' => $this->date,
                    'line_count' => $item->line_count ?? 1,
                    'minutes' => $item->minutes ?? 0,
                ];

                $calculation = $taxService->calculateTaxes($params);
                $allCalculations[] = array_merge($calculation, ['item_id' => $item->id]);
                $totalTaxAmount += $calculation['total_tax_amount'];

                // Record exemption usage if any exemptions were applied
                if (!empty($calculation['exemptions_applied'])) {
                    $taxService->recordExemptionUsage(
                        $calculation['exemptions_applied'],
                        $this->id
                    );
                }
            }
        }

        return [
            'calculations' => $allCalculations,
            'total_tax_amount' => $totalTaxAmount,
            'summary' => $taxService->getCalculationSummary($allCalculations),
        ];
    }

    /**
     * Get service address for tax calculation.
     */
    public function getServiceAddress(): array
    {
        if ($this->client) {
            return [
                'address' => $this->client->address,
                'city' => $this->client->city,
                'state' => $this->client->state,
                'state_code' => $this->client->state,
                'zip_code' => $this->client->zip_code,
                'country' => $this->client->country,
            ];
        }

        return [];
    }

    /**
     * Get VoIP service items on this invoice.
     */
    public function voipItems()
    {
        return $this->items()->voipServices();
    }

    /**
     * Check if invoice has VoIP services.
     */
    public function hasVoIPServices(): bool
    {
        return $this->voipItems()->exists();
    }

    /**
     * Get tax breakdown for all VoIP services.
     */
    public function getVoIPTaxBreakdown(): array
    {
        $breakdown = [];
        
        foreach ($this->voipItems as $item) {
            if ($item->voip_tax_data) {
                $breakdown[$item->id] = [
                    'item_name' => $item->name,
                    'service_type' => $item->service_type,
                    'tax_breakdown' => $item->voip_tax_data['tax_breakdown'] ?? [],
                    'total_tax' => $item->voip_tax_data['total_tax_amount'] ?? 0,
                ];
            }
        }

        return $breakdown;
    }

    /**
     * Get compliance report data.
     */
    public function getComplianceReportData(): array
    {
        return [
            'invoice_number' => $this->getFullNumber(),
            'client_name' => $this->client->name ?? 'Unknown',
            'invoice_date' => $this->date->toDateString(),
            'service_address' => $this->getServiceAddress(),
            'voip_items' => $this->voipItems->map(function ($item) {
                return [
                    'name' => $item->name,
                    'service_type' => $item->service_type,
                    'amount' => $item->subtotal - $item->discount,
                    'tax_amount' => $item->tax,
                    'line_count' => $item->line_count,
                    'minutes' => $item->minutes,
                    'tax_data' => $item->voip_tax_data,
                ];
            })->toArray(),
            'exemptions_used' => $this->taxExemptionUsage->map(function ($usage) {
                return [
                    'exemption_name' => $usage->taxExemption->exemption_name ?? 'Unknown',
                    'exemption_type' => $usage->taxExemption->exemption_type ?? 'Unknown',
                    'original_amount' => $usage->original_tax_amount,
                    'exempted_amount' => $usage->exempted_amount,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get the invoice's full number.
     */
    public function getFullNumber(): string
    {
        if ($this->prefix) {
            return $this->prefix . '-' . str_pad($this->number, 4, '0', STR_PAD_LEFT);
        }

        return 'INV-' . str_pad($this->number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        // If no due_date is set, invoice cannot be overdue
        if (!$this->due_date) {
            return false;
        }
        
        return $this->status !== self::STATUS_PAID &&
               $this->status !== self::STATUS_CANCELLED &&
               Carbon::now()->gt($this->due_date);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Get total paid amount.
     */
    public function getTotalPaid(): float
    {
        return round($this->payments()->sum('amount'), 2);
    }

    /**
     * Get remaining balance.
     */
    public function getBalance(): float
    {
        return round($this->amount - $this->getTotalPaid(), 2);
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->getBalance() <= 0;
    }

    /**
     * Get subtotal (before discount and tax).
     */
    public function getSubtotal(): float
    {
        return round($this->items()->sum('subtotal'), 2);
    }

    /**
     * Get total tax amount.
     */
    public function getTotalTax(): float
    {
        return round($this->items()->sum('tax'), 2);
    }

    /**
     * Calculate and update invoice totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->getSubtotal();
        $tax = $this->getTotalTax();
        $total = $subtotal - $this->discount_amount + $tax;

        $this->update(['amount' => $total]);
    }

    /**
     * Recalculate all taxes using VoIP tax engine.
     */
    public function recalculateVoIPTaxes(?array $serviceAddress = null): void
    {
        if (!$this->hasVoIPServices()) {
            return;
        }

        $taxCalculations = $this->calculateVoIPTaxes($serviceAddress);
        
        // Update individual items with new tax calculations
        foreach ($taxCalculations['calculations'] as $calculation) {
            $item = $this->items()->find($calculation['item_id']);
            if ($item) {
                $item->update([
                    'tax' => $calculation['total_tax_amount'],
                    'voip_tax_data' => $calculation,
                ]);
            }
        }

        // Recalculate invoice totals
        $this->calculateTotals();

        \Log::info('Invoice VoIP taxes recalculated', [
            'invoice_id' => $this->id,
            'total_tax' => $taxCalculations['total_tax_amount'],
            'items_processed' => count($taxCalculations['calculations'])
        ]);
    }

    /**
     * Get public URL for client access.
     */
    public function getPublicUrl(): string
    {
        if (!$this->url_key) {
            $this->generateUrlKey();
        }

        return url('/invoice/' . $this->url_key);
    }

    /**
     * Generate URL key for public access.
     */
    public function generateUrlKey(): void
    {
        $this->update(['url_key' => bin2hex(random_bytes(16))]);
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['status' => self::STATUS_SENT]);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return $this->formatCurrency($this->amount);
    }

    /**
     * Get formatted balance.
     */
    public function getFormattedBalance(): string
    {
        return $this->formatCurrency($this->getBalance());
    }

    /**
     * Format amount with currency.
     */
    public function formatCurrency(float $amount): string
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($amount, 2);
    }

    /**
     * Get currency symbol.
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

        return $symbols[$this->currency_code] ?? $this->currency_code;
    }

    /**
     * Scope to get overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED])
                    ->where('due_date', '<', Carbon::now());
    }

    /**
     * Scope to get paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to get unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    /**
     * Scope to get invoices by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to search invoices.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('number', $search)
              ->orWhere('note', 'like', '%' . $search . '%');
        });
    }

    /**
     * Get validation rules for invoice creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'prefix' => 'nullable|string|max:10',
            'number' => 'required|integer|min:1',
            'scope' => 'nullable|string|max:255',
            'status' => 'required|in:Draft,Sent,Paid,Overdue,Cancelled',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'discount_amount' => 'numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'note' => 'nullable|string',
            'category_id' => 'required|integer|exists:categories,id',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_PAID,
            self::STATUS_OVERDUE,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-increment invoice number
        static::creating(function ($invoice) {
            if (!$invoice->number) {
                $lastInvoice = static::where('client_id', $invoice->client_id)
                    ->where('prefix', $invoice->prefix)
                    ->orderBy('number', 'desc')
                    ->first();

                $invoice->number = $lastInvoice ? $lastInvoice->number + 1 : 1;
            }

            // Generate URL key
            if (!$invoice->url_key) {
                $invoice->url_key = bin2hex(random_bytes(16));
            }
        });

        // Update status based on due date
        static::retrieved(function ($invoice) {
            if ($invoice->isOverdue() && $invoice->status === self::STATUS_SENT) {
                $invoice->update(['status' => self::STATUS_OVERDUE]);
            }
        });
    }
}