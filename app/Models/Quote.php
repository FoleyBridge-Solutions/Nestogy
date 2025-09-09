<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\QuotePricingCalculations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Services\VoIPTaxService;
use App\Services\TaxEngine\LocalTaxRateService;
use App\Models\TaxExemption;
use App\Models\TaxExemptionUsage;

/**
 * Quote Model
 * 
 * Enterprise-grade quote management with multi-tier approval workflows,
 * versioning, expiration handling, and VoIP-specific features.
 * 
 * @property int $id
 * @property int $company_id
 * @property string|null $prefix
 * @property int $number
 * @property int $version
 * @property string|null $scope
 * @property string $status
 * @property string $approval_status
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $expire_date
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property float $discount_amount
 * @property string $discount_type
 * @property float $amount
 * @property string $currency_code
 * @property string|null $note
 * @property string|null $terms_conditions
 * @property string|null $url_key
 * @property bool $auto_renew
 * @property int|null $auto_renew_days
 * @property string|null $template_name
 * @property array|null $voip_config
 * @property array|null $pricing_model
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $viewed_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property int $category_id
 * @property int $client_id
 * @property int|null $parent_quote_id
 * @property int|null $converted_invoice_id
 * @property int|null $created_by
 * @property int|null $approved_by
 */
class Quote extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, QuotePricingCalculations;

    /**
     * The table associated with the model.
     */
    protected $table = 'quotes';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'prefix',
        'number',
        'scope',
        'status',
        'approval_status',
        'date',
        'expire',
        'valid_until',
        'discount_amount',
        'amount',
        'currency_code',
        'note',
        'terms_conditions',
        'url_key',
        'auto_renew',
        'auto_renew_days',
        'template_name',
        'voip_config',
        'pricing_model',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'declined_at',
        'category_id',
        'client_id',
        'parent_quote_id',
        'converted_invoice_id',
        'created_by',
        'approved_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'number' => 'integer',
        'date' => 'date',
        'expire' => 'date',
        'valid_until' => 'date',
        'discount_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'auto_renew' => 'boolean',
        'auto_renew_days' => 'integer',
        'voip_config' => 'array',
        'pricing_model' => 'array',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'category_id' => 'integer',
        'client_id' => 'integer',
        'parent_quote_id' => 'integer',
        'converted_invoice_id' => 'integer',
        'created_by' => 'integer',
        'approved_by' => 'integer',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Quote status enumeration
     */
    const STATUS_DRAFT = 'Draft';
    const STATUS_SENT = 'Sent';
    const STATUS_VIEWED = 'Viewed';
    const STATUS_ACCEPTED = 'Accepted';
    const STATUS_DECLINED = 'Declined';
    const STATUS_EXPIRED = 'Expired';
    const STATUS_CONVERTED = 'Converted';
    const STATUS_CANCELLED = 'Cancelled';

    /**
     * Approval status enumeration
     */
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_MANAGER_APPROVED = 'manager_approved';
    const APPROVAL_EXECUTIVE_APPROVED = 'executive_approved';
    const APPROVAL_REJECTED = 'rejected';
    const APPROVAL_NOT_REQUIRED = 'not_required';

    /**
     * Discount type enumeration
     */
    const DISCOUNT_PERCENTAGE = 'percentage';
    const DISCOUNT_FIXED = 'fixed';

    /**
     * VoIP pricing model types
     */
    const PRICING_FLAT_RATE = 'flat_rate';
    const PRICING_TIERED = 'tiered';
    const PRICING_USAGE_BASED = 'usage_based';
    const PRICING_HYBRID = 'hybrid';

    /**
     * Get the client this quote belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the category this quote belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the quote items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the approvals for this quote.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(QuoteApproval::class);
    }

    /**
     * Get quote versions (revisions).
     */
    public function versions(): HasMany
    {
        return $this->hasMany(QuoteVersion::class);
    }

    /**
     * Get the parent quote if this is a revision.
     */
    public function parentQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'parent_quote_id');
    }

    /**
     * Get child quote revisions.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Quote::class, 'parent_quote_id');
    }

    /**
     * Get the converted invoice.
     */
    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    /**
     * Get the user who created this quote.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this quote.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get tax exemptions for this quote's client.
     */
    public function taxExemptions(): HasMany
    {
        return $this->hasMany(TaxExemption::class, 'client_id', 'client_id')
                    ->where('company_id', $this->company_id);
    }

    /**
     * Get tax exemption usage records for this quote.
     */
    public function taxExemptionUsage(): HasMany
    {
        return $this->hasMany(TaxExemptionUsage::class);
    }

    /**
     * Get tax calculations for this quote.
     */
    public function taxCalculations()
    {
        return $this->morphMany(TaxCalculation::class, 'calculable');
    }

    /**
     * Get the latest tax calculation for this quote.
     */
    public function latestTaxCalculation()
    {
        return $this->taxCalculations()
                   ->where('status', '!=', 'voided')
                   ->latest()
                   ->first();
    }

    /**
     * Get real-time tax calculation using local tax rates.
     */
    public function calculateRealTimeTax(): array
    {
        $taxService = new LocalTaxRateService($this->company_id);
        
        // Determine service type from items (default to general)
        $serviceType = 'general';
        $firstItem = $this->items()->first();
        if ($firstItem && !empty($firstItem->service_type)) {
            $serviceType = $firstItem->service_type;
        }
        
        return $taxService->calculateTax(
            $this->amount,
            $serviceType,
            $this->client ? [
                'line1' => $this->client->address ?? '',
                'city' => $this->client->city ?? '',
                'state' => $this->client->state ?? '',
                'zip' => $this->client->postal_code ?? $this->client->zip_code ?? ''
            ] : null
        );
    }

    /**
     * Get formatted tax breakdown by jurisdiction.
     */
    public function getFormattedTaxBreakdown(): array
    {
        // First try to get real-time calculation
        $realTimeCalculation = $this->calculateRealTimeTax();
        
        if ($realTimeCalculation['success'] && !empty($realTimeCalculation['jurisdictions'])) {
            return [
                'has_breakdown' => true,
                'total_tax' => $realTimeCalculation['tax_amount'],
                'total_rate' => $realTimeCalculation['tax_rate'],
                'jurisdictions' => $realTimeCalculation['jurisdictions'],
                'source' => 'real_time_local'
            ];
        }
        
        // Fallback to stored tax calculation
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
     * Calculate VoIP taxes for all quote items.
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
                        null,
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
     * Get VoIP service items on this quote.
     */
    public function voipItems()
    {
        return $this->items()->voipServices();
    }

    /**
     * Check if quote has VoIP services.
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
     * Get the quote's full number.
     */
    public function getFullNumber(): string
    {
        $prefix = $this->prefix ?: 'QTE';
        $number = str_pad($this->number, 4, '0', STR_PAD_LEFT);
        // $version = $this->version > 1 ? '.v' . $this->version : '';
        
        return $prefix . '-' . $number; // . $version;
    }

    /**
     * Check if quote is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expire_date && !$this->valid_until) {
            return false;
        }

        $expiryDate = $this->valid_until ?: $this->expire_date;
        return $expiryDate && Carbon::now()->gt($expiryDate);
    }

    /**
     * Check if quote is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if quote is sent.
     */
    public function isSent(): bool
    {
        return in_array($this->status, [
            self::STATUS_SENT,
            self::STATUS_VIEWED,
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED
        ]);
    }

    /**
     * Check if quote is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if quote is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if quote is converted to invoice.
     */
    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED || !is_null($this->converted_invoice_id);
    }

    /**
     * Check if quote needs approval.
     * Note: approval_status field not in database
     */
    public function needsApproval(): bool
    {
        // return in_array($this->approval_status, [
        //     self::APPROVAL_PENDING,
        //     self::APPROVAL_MANAGER_APPROVED
        // ]);
        return false; // Default to no approval needed
    }

    /**
     * Check if quote is fully approved.
     */
    public function isFullyApproved(): bool
    {
        return in_array($this->approval_status, [
            self::APPROVAL_EXECUTIVE_APPROVED,
            self::APPROVAL_NOT_REQUIRED
        ]);
    }

    /**
     * Check if quote approval was rejected.
     */
    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpiration(): ?int
    {
        $expiryDate = $this->valid_until ?: $this->expire_date;
        if (!$expiryDate) {
            return null;
        }

        return Carbon::now()->diffInDays($expiryDate, false);
    }

    /**
     * Get subtotal (before discount and tax).
     */
    public function getSubtotal(): float
    {
        return $this->items()->sum('subtotal');
    }

    /**
     * Get total tax amount.
     */
    public function getTotalTax(): float
    {
        return $this->items()->sum('tax');
    }

    /**
     * Get discount amount based on type.
     */
    public function getDiscountAmount(): float
    {
        // Note: discount_type field not in database, default to fixed
        // if ($this->discount_type === self::DISCOUNT_PERCENTAGE) {
        //     return ($this->getSubtotal() * $this->discount_amount) / 100;
        // }
        
        return $this->discount_amount ?? 0;
    }

    /**
     * Calculate and update quote totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->getSubtotal();
        $discount = $this->getDiscountAmount();
        $tax = $this->getTotalTax();
        $total = $subtotal - $discount + $tax;

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

        // Recalculate quote totals
        $this->calculateTotals();

        \Log::info('Quote VoIP taxes recalculated', [
            'quote_id' => $this->id,
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

        return url('/quote/' . $this->url_key);
    }

    /**
     * Generate URL key for public access.
     */
    public function generateUrlKey(): void
    {
        $this->update(['url_key' => bin2hex(random_bytes(16))]);
    }

    /**
     * Mark quote as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now()
        ]);
    }

    /**
     * Mark quote as viewed.
     */
    public function markAsViewed(): void
    {
        if ($this->status === self::STATUS_SENT) {
            $this->update([
                'status' => self::STATUS_VIEWED,
                'viewed_at' => now()
            ]);
        }
    }

    /**
     * Mark quote as accepted.
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now()
        ]);
    }

    /**
     * Mark quote as declined.
     */
    public function markAsDeclined(): void
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
            'declined_at' => now()
        ]);
    }

    /**
     * Convert quote to invoice.
     */
    public function convertToInvoice(): Invoice
    {
        $invoice = Invoice::create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'category_id' => $this->category_id,
            'date' => now(),
            'due_date' => now()->addDays(30),
            'currency_code' => $this->currency_code,
            'discount_amount' => $this->getDiscountAmount(),
            'note' => $this->note,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        // Copy items
        foreach ($this->items as $item) {
            $invoice->items()->create([
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => $item->discount,
                'tax_id' => $item->tax_id,
                'category_id' => $item->category_id,
                'product_id' => $item->product_id,
            ]);
        }

        $invoice->calculateTotals();

        // If quote has VoIP services, recalculate taxes for the invoice
        if ($this->hasVoIPServices()) {
            $invoice->recalculateVoIPTaxes();
        }

        // Update quote status
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'converted_invoice_id' => $invoice->id
        ]);

        return $invoice;
    }

    /**
     * Create a new version of this quote.
     */
    public function createRevision(array $changes = []): Quote
    {
        $newQuote = $this->replicate();
        // $newQuote->version = $this->version + 1; // version field not in database
        $newQuote->parent_quote_id = $this->id;
        $newQuote->status = self::STATUS_DRAFT;
        // $newQuote->approval_status = self::APPROVAL_PENDING; // approval_status field not in database
        $newQuote->url_key = null;
        $newQuote->sent_at = null;
        $newQuote->viewed_at = null;
        $newQuote->accepted_at = null;
        $newQuote->declined_at = null;
        
        // Apply any changes
        foreach ($changes as $key => $value) {
            $newQuote->$key = $value;
        }
        
        $newQuote->save();

        // Copy items
        foreach ($this->items as $item) {
            $newQuote->items()->create([
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => $item->discount,
                'tax_id' => $item->tax_id,
                'category_id' => $item->category_id,
                'product_id' => $item->product_id,
            ]);
        }

        $newQuote->calculateTotals();
        return $newQuote;
    }

    /**
     * Auto-renew quote if conditions are met.
     */
    public function autoRenew(): ?Quote
    {
        if (!$this->auto_renew || !$this->auto_renew_days) {
            return null;
        }

        if (!$this->isExpired()) {
            return null;
        }

        $renewedQuote = $this->createRevision([
            'date' => now(),
            'expire_date' => now()->addDays($this->auto_renew_days),
            'valid_until' => now()->addDays($this->auto_renew_days),
        ]);

        return $renewedQuote;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return $this->formatCurrency($this->amount);
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
     * Scope to get quotes by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get expired quotes.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('expire_date', '<', Carbon::now())
              ->orWhere('valid_until', '<', Carbon::now());
        });
    }

    /**
     * Scope to get quotes expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        $futureDate = Carbon::now()->addDays($days);
        
        return $query->where(function ($q) use ($futureDate) {
            $q->whereBetween('expire_date', [Carbon::now(), $futureDate])
              ->orWhereBetween('valid_until', [Carbon::now(), $futureDate]);
        });
    }

    /**
     * Scope to search quotes.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('number', $search)
              ->orWhere('scope', 'like', '%' . $search . '%')
              ->orWhere('note', 'like', '%' . $search . '%');
        });
    }

    /**
     * Get validation rules for quote creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'prefix' => 'nullable|string|max:10',
            'number' => 'required|integer|min:1',
            'scope' => 'nullable|string|max:255',
            'status' => 'required|in:Draft,Sent,Viewed,Accepted,Declined,Expired,Converted,Cancelled',
            'approval_status' => 'required|in:pending,manager_approved,executive_approved,rejected,not_required',
            'date' => 'required|date',
            'expire_date' => 'nullable|date|after:date',
            'valid_until' => 'nullable|date|after:date',
            'discount_amount' => 'numeric|min:0',
            'discount_type' => 'required|in:percentage,fixed',
            'currency_code' => 'required|string|size:3',
            'note' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'auto_renew' => 'boolean',
            'auto_renew_days' => 'nullable|integer|min:1|max:365',
            'template_name' => 'nullable|string|max:100',
            'voip_config' => 'nullable|array',
            'pricing_model' => 'nullable|array',
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
            self::STATUS_VIEWED,
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED,
            self::STATUS_EXPIRED,
            self::STATUS_CONVERTED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Get available approval statuses.
     */
    public static function getAvailableApprovalStatuses(): array
    {
        return [
            self::APPROVAL_PENDING,
            self::APPROVAL_MANAGER_APPROVED,
            self::APPROVAL_EXECUTIVE_APPROVED,
            self::APPROVAL_REJECTED,
            self::APPROVAL_NOT_REQUIRED,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-increment quote number and set defaults
        static::creating(function ($quote) {
            if (!$quote->number) {
                $lastQuote = static::where('company_id', $quote->company_id)
                    ->where('prefix', $quote->prefix)
                    ->orderBy('number', 'desc')
                    ->first();

                $quote->number = $lastQuote ? $lastQuote->number + 1 : 1;
            }

            // Note: version, approval_status, discount_type fields not in database
            // if (!$quote->version) {
            //     $quote->version = 1;
            // }

            if (!$quote->url_key) {
                $quote->url_key = bin2hex(random_bytes(16));
            }

            // if (!$quote->approval_status) {
            //     $quote->approval_status = self::APPROVAL_PENDING;
            // }

            // if (!$quote->discount_type) {
            //     $quote->discount_type = self::DISCOUNT_FIXED;
            // }
        });

        // Update status based on expiration
        static::retrieved(function ($quote) {
            if ($quote->isExpired() && $quote->status === self::STATUS_SENT) {
                $quote->update(['status' => self::STATUS_EXPIRED]);
            }
        });
    }
}