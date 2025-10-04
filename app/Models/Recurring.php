<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

/**
 * Recurring Invoice Model
 *
 * Sophisticated recurring billing system with VoIP-specific features including
 * usage-based billing, tiered pricing, proration calculations, and tax integration.
 * Supports contract escalations, multi-service billing, and automated processing.
 *
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property int|null $category_id
 * @property int|null $quote_id
 * @property string|null $prefix
 * @property int $number
 * @property string|null $scope
 * @property string $frequency
 * @property \Carbon\Carbon|null $last_sent
 * @property \Carbon\Carbon $next_date
 * @property \Carbon\Carbon|null $end_date
 * @property bool $status
 * @property string $billing_type
 * @property float $discount_amount
 * @property string $discount_type
 * @property float $amount
 * @property string $currency_code
 * @property string|null $note
 * @property string|null $internal_notes
 * @property array|null $voip_config
 * @property array|null $pricing_model
 * @property array|null $service_tiers
 * @property array|null $usage_allowances
 * @property array|null $overage_rates
 * @property bool $auto_invoice_generation
 * @property int $invoice_terms_days
 * @property bool $email_invoice
 * @property string|null $email_template
 * @property bool $proration_enabled
 * @property string $proration_method
 * @property bool $contract_escalation
 * @property float|null $escalation_percentage
 * @property int|null $escalation_months
 * @property \Carbon\Carbon|null $last_escalation
 * @property array|null $tax_settings
 * @property int|null $max_invoices
 * @property int $invoices_generated
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $archived_at
 */
class Recurring extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'recurring';

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'category_id',
        'prefix',
        'number',
        'scope',
        'frequency',
        'last_sent',
        'next_date',
        'status',
        'discount_amount',
        'amount',
        'currency_code',
        'note',
        'service_tiers',
        'usage_allowances',
        'overage_rates',
        'auto_invoice_generation',
        'invoice_terms_days',
        'email_invoice',
        'email_template',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'category_id' => 'integer',
        'quote_id' => 'integer',
        'number' => 'integer',
        'last_sent' => 'datetime',
        'next_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => 'boolean',
        'discount_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'voip_config' => 'array',
        'pricing_model' => 'array',
        'service_tiers' => 'array',
        'usage_allowances' => 'array',
        'overage_rates' => 'array',
        'auto_invoice_generation' => 'boolean',
        'invoice_terms_days' => 'integer',
        'email_invoice' => 'boolean',
        'proration_enabled' => 'boolean',
        'contract_escalation' => 'boolean',
        'escalation_percentage' => 'decimal:2',
        'escalation_months' => 'integer',
        'last_escalation' => 'datetime',
        'tax_settings' => 'array',
        'max_invoices' => 'integer',
        'invoices_generated' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Billing frequency constants
     */
    const FREQUENCY_WEEKLY = 'Weekly';

    const FREQUENCY_BIWEEKLY = 'Bi-Weekly';

    const FREQUENCY_MONTHLY = 'Monthly';

    const FREQUENCY_QUARTERLY = 'Quarterly';

    const FREQUENCY_BIANNUALLY = 'Bi-Annually';

    const FREQUENCY_ANNUALLY = 'Annually';

    const FREQUENCY_CUSTOM = 'Custom';

    /**
     * Billing type constants
     */
    const BILLING_TYPE_FIXED = 'fixed';

    const BILLING_TYPE_USAGE_BASED = 'usage_based';

    const BILLING_TYPE_TIERED = 'tiered';

    const BILLING_TYPE_HYBRID = 'hybrid';

    /**
     * Discount type constants
     */
    const DISCOUNT_TYPE_FIXED = 'fixed';

    const DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    /**
     * Proration method constants
     */
    const PRORATION_DAILY = 'daily';

    const PRORATION_MONTHLY = 'monthly';

    const PRORATION_NONE = 'none';

    /**
     * VoIP service types for recurring billing
     */
    const SERVICE_HOSTED_PBX = 'hosted_pbx';

    const SERVICE_SIP_TRUNKING = 'sip_trunking';

    const SERVICE_PHONE_SYSTEM = 'phone_system';

    const SERVICE_INTERNET = 'internet';

    const SERVICE_SUPPORT = 'support';

    const SERVICE_EQUIPMENT_LEASE = 'equipment_lease';

    const SERVICE_LONG_DISTANCE = 'long_distance';

    const SERVICE_INTERNATIONAL = 'international';

    const SERVICE_E911 = 'e911';

    const SERVICE_NUMBER_PORTING = 'number_porting';

    /**
     * Get the client this recurring billing belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the category this recurring billing belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the quote this recurring billing was created from.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the recurring billing items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'recurring_id');
    }

    /**
     * Get generated invoices from this recurring billing.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recurring_id');
    }

    /**
     * Get usage data records for this recurring billing.
     * TODO: Uncomment when RecurringUsageData model is created
     */
    // public function usageData(): HasMany
    // {
    //     return $this->hasMany(RecurringUsageData::class);
    // }

    /**
     * Get service tiers for this recurring billing.
     * TODO: Uncomment when RecurringServiceTier model is created
     */
    // public function serviceTiers(): HasMany
    // {
    //     return $this->hasMany(RecurringServiceTier::class);
    // }

    /**
     * Get billing history records.
     * TODO: Uncomment when RecurringBillingHistory model is created
     */
    // public function billingHistory(): HasMany
    // {
    //     return $this->hasMany(RecurringBillingHistory::class);
    // }

    /**
     * Get proration adjustments.
     * TODO: Uncomment when RecurringProrationAdjustment model is created
     */
    // public function prorationAdjustments(): HasMany
    // {
    //     return $this->hasMany(RecurringProrationAdjustment::class);
    // }

    /**
     * Get contract escalation records.
     * TODO: Uncomment when RecurringContractEscalation model is created
     */
    // public function contractEscalations(): HasMany
    // {
    //     return $this->hasMany(RecurringContractEscalation::class);
    // }

    /**
     * Get VoIP service items for this recurring billing.
     */
    public function voipItems()
    {
        return $this->items()->whereNotNull('service_type');
    }

    /**
     * Get the recurring billing's full number.
     */
    public function getFullNumber(): string
    {
        $prefix = $this->prefix ?: 'REC';

        return $prefix.'-'.str_pad($this->number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if recurring billing is active.
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }

    /**
     * Check if recurring billing is due for processing.
     */
    public function isDue(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->end_date && Carbon::now()->gt($this->end_date)) {
            return false;
        }

        if ($this->max_invoices && $this->invoices_generated >= $this->max_invoices) {
            return false;
        }

        return Carbon::now()->gte($this->next_date);
    }

    /**
     * Check if recurring billing has reached its maximum invoices.
     */
    public function hasReachedMaxInvoices(): bool
    {
        return $this->max_invoices && $this->invoices_generated >= $this->max_invoices;
    }

    /**
     * Check if recurring billing has expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date && Carbon::now()->gt($this->end_date);
    }

    /**
     * Check if contract escalation is due.
     */
    public function isEscalationDue(): bool
    {
        if (! $this->contract_escalation || ! $this->escalation_months) {
            return false;
        }

        $lastEscalation = $this->last_escalation ?: $this->created_at;

        return Carbon::now()->gte($lastEscalation->addMonths($this->escalation_months));
    }

    /**
     * Check if this recurring billing uses VoIP services.
     */
    public function hasVoIPServices(): bool
    {
        return $this->voipItems()->exists();
    }

    /**
     * Calculate next billing date based on frequency.
     */
    public function calculateNextDate(?Carbon $fromDate = null): Carbon
    {
        $current = $fromDate ?: ($this->next_date ?: Carbon::now());

        return match ($this->frequency) {
            self::FREQUENCY_WEEKLY => $current->addWeek(),
            self::FREQUENCY_BIWEEKLY => $current->addWeeks(2),
            self::FREQUENCY_MONTHLY => $current->addMonth(),
            self::FREQUENCY_QUARTERLY => $current->addMonths(3),
            self::FREQUENCY_BIANNUALLY => $current->addMonths(6),
            self::FREQUENCY_ANNUALLY => $current->addYear(),
            default => $current->addMonth(),
        };
    }

    /**
     * Calculate prorated amount for partial periods.
     */
    public function calculateProration(float $amount, Carbon $startDate, Carbon $endDate): float
    {
        if (! $this->proration_enabled) {
            return $amount;
        }

        $billingPeriodStart = $this->next_date->copy()->subMonth();
        $billingPeriodEnd = $this->next_date;

        switch ($this->proration_method) {
            case self::PRORATION_DAILY:
                $totalDays = $billingPeriodStart->diffInDays($billingPeriodEnd);
                $usedDays = $startDate->diffInDays($endDate);

                return ($amount / $totalDays) * $usedDays;

            case self::PRORATION_MONTHLY:
                if ($startDate->day > 15) {
                    return $amount * 0.5; // Half month
                }

                return $amount;

            default:
                return $amount;
        }
    }

    /**
     * Apply contract escalation if due.
     */
    public function applyContractEscalation(): bool
    {
        if (! $this->isEscalationDue()) {
            return false;
        }

        $oldAmount = $this->amount;
        $escalationAmount = $this->amount * ($this->escalation_percentage / 100);
        $newAmount = $this->amount + $escalationAmount;

        $this->update([
            'amount' => $newAmount,
            'last_escalation' => Carbon::now(),
        ]);

        // Record escalation history
        // TODO: Uncomment when RecurringContractEscalation model is created
        // $this->contractEscalations()->create([
        //     'company_id' => $this->company_id,
        //     'escalation_date' => Carbon::now(),
        //     'old_amount' => $oldAmount,
        //     'new_amount' => $newAmount,
        //     'escalation_amount' => $escalationAmount,
        //     'escalation_percentage' => $this->escalation_percentage,
        // ]);

        Log::info('Contract escalation applied', [
            'recurring_id' => $this->id,
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'escalation_percentage' => $this->escalation_percentage,
        ]);

        return true;
    }

    /**
     * Calculate usage-based charges for VoIP services.
     */
    public function calculateUsageCharges(?Carbon $billingPeriodStart = null, ?Carbon $billingPeriodEnd = null): array
    {
        if (! \Schema::hasColumn('recurring', 'billing_type') || 
            ($this->billing_type !== self::BILLING_TYPE_USAGE_BASED && $this->billing_type !== self::BILLING_TYPE_HYBRID)) {
            return ['total' => 0, 'breakdown' => []];
        }

        $periodStart = $billingPeriodStart ?: $this->next_date->copy()->subMonth();
        $periodEnd = $billingPeriodEnd ?: $this->next_date;

        // TODO: Uncomment when RecurringUsageData model is created
        // $usageData = $this->usageData()
        //     ->whereBetween('usage_date', [$periodStart, $periodEnd])
        //     ->get();

        // For now, get usage data from metadata
        $usageData = collect($this->metadata['usage_data'] ?? [])
            ->where('usage_date', '>=', $periodStart->toDateString())
            ->where('usage_date', '<=', $periodEnd->toDateString());

        $totalUsageCharges = 0;
        $breakdown = [];

        foreach ($this->service_tiers ?? [] as $tier) {
            $serviceUsage = $usageData->where('service_type', $tier['service_type'])->sum('usage_amount');
            $allowance = $tier['monthly_allowance'] ?? 0;

            if ($serviceUsage > $allowance) {
                $overage = $serviceUsage - $allowance;
                $overageRate = $tier['overage_rate'] ?? 0;
                $overageCharges = $overage * $overageRate;

                $totalUsageCharges += $overageCharges;
                $breakdown[] = [
                    'service_type' => $tier['service_type'],
                    'allowance' => $allowance,
                    'usage' => $serviceUsage,
                    'overage' => $overage,
                    'rate' => $overageRate,
                    'charges' => $overageCharges,
                ];
            }
        }

        return [
            'total' => $totalUsageCharges,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate tiered pricing based on usage volumes.
     */
    public function calculateTieredPricing(float $usage, array $tiers): float
    {
        $totalCost = 0;
        $remainingUsage = $usage;

        foreach ($tiers as $tier) {
            if ($remainingUsage <= 0) {
                break;
            }

            $tierUsage = min($remainingUsage, $tier['max_usage'] - $tier['min_usage']);
            $tierCost = $tierUsage * $tier['rate'];
            $totalCost += $tierCost;
            $remainingUsage -= $tierUsage;
        }

        return $totalCost;
    }

    /**
     * Generate invoice from recurring billing.
     */
    public function generateInvoice(?array $overrides = []): Invoice
    {
        $invoiceData = array_merge([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'category_id' => $this->category_id,
            'recurring_id' => $this->id,
            'prefix' => $this->client->invoice_prefix ?? 'INV',
            'date' => now(),
            'due_date' => now()->addDays($this->invoice_terms_days),
            'currency_code' => $this->currency_code,
            'discount_amount' => $this->discount_amount,
            'discount_type' => $this->discount_type,
            'note' => $this->note,
            'status' => Invoice::STATUS_DRAFT,
        ], $overrides);

        $invoice = Invoice::create($invoiceData);

        // Copy recurring items to invoice
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
                'service_type' => $item->service_type,
                'line_count' => $item->line_count,
                'minutes' => $item->minutes,
            ]);
        }

        // Calculate usage charges if applicable
        if (\Schema::hasColumn('recurring', 'billing_type') && 
            in_array($this->billing_type, [self::BILLING_TYPE_USAGE_BASED, self::BILLING_TYPE_HYBRID])) {
            $usageCharges = $this->calculateUsageCharges();

            if ($usageCharges['total'] > 0) {
                $invoice->items()->create([
                    'name' => 'Usage Charges',
                    'description' => 'Usage overage charges for billing period',
                    'quantity' => 1,
                    'price' => $usageCharges['total'],
                    'discount' => 0,
                    'metadata' => $usageCharges['breakdown'],
                ]);
            }
        }

        // Apply VoIP taxes if enabled
        if ($this->hasVoIPServices() && ($this->tax_settings['enable_voip_tax'] ?? true)) {
            $invoice->recalculateVoIPTaxes();
        }

        // Calculate invoice totals
        $invoice->calculateTotals();

        // Update recurring billing tracking
        $this->update([
            'last_sent' => now(),
            'next_date' => $this->calculateNextDate(),
            'invoices_generated' => $this->invoices_generated + 1,
        ]);

        // Record billing history
        // TODO: Uncomment when RecurringBillingHistory model is created
        // $this->billingHistory()->create([
        //     'company_id' => $this->company_id,
        //     'invoice_id' => $invoice->id,
        //     'billing_date' => now(),
        //     'amount' => $invoice->amount,
        //     'usage_charges' => $usageCharges['total'] ?? 0,
        //     'tax_amount' => $invoice->getTotalTax(),
        // ]);

        // For now, store billing history in metadata
        $billingHistory = $this->metadata['billing_history'] ?? [];
        $billingHistory[] = [
            'invoice_id' => $invoice->id,
            'billing_date' => now()->toISOString(),
            'amount' => $invoice->amount,
            'usage_charges' => $usageCharges['total'] ?? 0,
            'tax_amount' => $invoice->getTotalTax(),
        ];

        $this->update([
            'metadata' => array_merge($this->metadata ?? [], [
                'billing_history' => $billingHistory,
            ]),
        ]);

        Log::info('Recurring invoice generated', [
            'recurring_id' => $this->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'client_id' => $this->client_id,
        ]);

        return $invoice;
    }

    /**
     * Add proration adjustment for mid-cycle changes.
     * TODO: Update return type when RecurringProrationAdjustment model is created
     */
    public function addProrationAdjustment(array $adjustmentData): array
    {
        // For now, store in metadata until related models are created
        $adjustments = $this->metadata['proration_adjustments'] ?? [];
        $adjustments[] = array_merge($adjustmentData, [
            'id' => count($adjustments) + 1,
            'created_at' => now()->toISOString(),
        ]);

        $this->update([
            'metadata' => array_merge($this->metadata ?? [], [
                'proration_adjustments' => $adjustments,
            ]),
        ]);

        Log::info('Proration adjustment added', [
            'recurring_id' => $this->id,
            'adjustment_id' => count($adjustments),
            'amount' => $adjustmentData['amount'],
        ]);

        return end($adjustments);
    }

    /**
     * Calculate and update recurring billing totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $discount = $this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE
            ? ($subtotal * $this->discount_amount) / 100
            : $this->discount_amount;

        $total = $subtotal - $discount;

        $this->update(['amount' => $total]);
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

        return $symbol.number_format($amount, 2);
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
     * Pause recurring billing.
     */
    public function pause(?Carbon $resumeDate = null): void
    {
        $this->update([
            'status' => false,
            'metadata' => array_merge($this->metadata ?? [], [
                'paused_at' => now()->toISOString(),
                'resume_date' => $resumeDate?->toISOString(),
            ]),
        ]);

        Log::info('Recurring billing paused', [
            'recurring_id' => $this->id,
            'resume_date' => $resumeDate?->toDateString(),
        ]);
    }

    /**
     * Resume recurring billing.
     */
    public function resume(?Carbon $nextDate = null): void
    {
        $metadata = $this->metadata ?? [];
        unset($metadata['paused_at'], $metadata['resume_date']);

        $this->update([
            'status' => true,
            'next_date' => $nextDate ?: $this->calculateNextDate(),
            'metadata' => $metadata,
        ]);

        Log::info('Recurring billing resumed', [
            'recurring_id' => $this->id,
            'next_date' => $this->next_date->toDateString(),
        ]);
    }

    /**
     * Scope for active recurring billing.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for due recurring billing.
     */
    public function scopeDue($query)
    {
        return $query->where('status', true)
            ->where('next_date', '<=', Carbon::now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('max_invoices')
                    ->orWhereRaw('invoices_generated < max_invoices');
            });
    }

    /**
     * Scope for recurring billing by frequency.
     */
    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Scope for recurring billing by billing type.
     */
    public function scopeByBillingType($query, string $billingType)
    {
        return $query->where('billing_type', $billingType);
    }

    /**
     * Scope for VoIP recurring billing.
     */
    public function scopeVoipServices($query)
    {
        return $query->whereNotNull('voip_config');
    }

    /**
     * Scope for expiring recurring billing.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('end_date')
            ->where('end_date', '>=', Carbon::now())
            ->where('end_date', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Get validation rules.
     */
    public static function getValidationRules(): array
    {
        return [
            'client_id' => 'required|integer|exists:clients,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'quote_id' => 'nullable|integer|exists:quotes,id',
            'prefix' => 'nullable|string|max:10',
            'scope' => 'nullable|string|max:255',
            'frequency' => 'required|in:Weekly,Bi-Weekly,Monthly,Quarterly,Bi-Annually,Annually,Custom',
            'next_date' => 'required|date',
            'end_date' => 'nullable|date|after:next_date',
            'status' => 'boolean',
            'billing_type' => 'required|in:fixed,usage_based,tiered,hybrid',
            'discount_amount' => 'numeric|min:0',
            'discount_type' => 'required|in:fixed,percentage',
            'currency_code' => 'required|string|size:3',
            'note' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'voip_config' => 'nullable|array',
            'pricing_model' => 'nullable|array',
            'service_tiers' => 'nullable|array',
            'usage_allowances' => 'nullable|array',
            'overage_rates' => 'nullable|array',
            'auto_invoice_generation' => 'boolean',
            'invoice_terms_days' => 'integer|min:0|max:365',
            'email_invoice' => 'boolean',
            'email_template' => 'nullable|string|max:100',
            'proration_enabled' => 'boolean',
            'proration_method' => 'required|in:daily,monthly,none',
            'contract_escalation' => 'boolean',
            'escalation_percentage' => 'nullable|numeric|min:0|max:100',
            'escalation_months' => 'nullable|integer|min:1|max:60',
            'tax_settings' => 'nullable|array',
            'max_invoices' => 'nullable|integer|min:1',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get available frequencies.
     */
    public static function getAvailableFrequencies(): array
    {
        return [
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_BIWEEKLY => 'Bi-Weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_QUARTERLY => 'Quarterly',
            self::FREQUENCY_BIANNUALLY => 'Bi-Annually',
            self::FREQUENCY_ANNUALLY => 'Annually',
            self::FREQUENCY_CUSTOM => 'Custom',
        ];
    }

    /**
     * Get available billing types.
     */
    public static function getAvailableBillingTypes(): array
    {
        return [
            self::BILLING_TYPE_FIXED => 'Fixed Amount',
            self::BILLING_TYPE_USAGE_BASED => 'Usage Based',
            self::BILLING_TYPE_TIERED => 'Tiered Pricing',
            self::BILLING_TYPE_HYBRID => 'Hybrid (Fixed + Usage)',
        ];
    }

    /**
     * Get available VoIP service types.
     */
    public static function getAvailableVoipServices(): array
    {
        return [
            self::SERVICE_HOSTED_PBX => 'Hosted PBX',
            self::SERVICE_SIP_TRUNKING => 'SIP Trunking',
            self::SERVICE_PHONE_SYSTEM => 'Phone System',
            self::SERVICE_INTERNET => 'Internet Service',
            self::SERVICE_SUPPORT => 'Support & Maintenance',
            self::SERVICE_EQUIPMENT_LEASE => 'Equipment Lease',
            self::SERVICE_LONG_DISTANCE => 'Long Distance',
            self::SERVICE_INTERNATIONAL => 'International Calling',
            self::SERVICE_E911 => 'E911 Service',
            self::SERVICE_NUMBER_PORTING => 'Number Porting',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($recurring) {
            if (! $recurring->number) {
                $lastRecurring = static::where('company_id', $recurring->company_id)
                    ->where('prefix', $recurring->prefix)
                    ->orderBy('number', 'desc')
                    ->first();

                $recurring->number = $lastRecurring ? $lastRecurring->number + 1 : 1;
            }

            // Set default values
            if (\Schema::hasColumn('recurring', 'billing_type') && ! $recurring->billing_type) {
                $recurring->billing_type = self::BILLING_TYPE_FIXED;
            }

            if (\Schema::hasColumn('recurring', 'discount_type') && ! $recurring->discount_type) {
                $recurring->discount_type = self::DISCOUNT_TYPE_FIXED;
            }

            if (\Schema::hasColumn('recurring', 'proration_method') && ! $recurring->proration_method) {
                $recurring->proration_method = self::PRORATION_DAILY;
            }

            if (! $recurring->invoice_terms_days) {
                $recurring->invoice_terms_days = 30;
            }
        });

        // Update next_date when recurring is retrieved and overdue
        static::retrieved(function ($recurring) {
            if ($recurring->isDue() && $recurring->isActive()) {
                // This would typically trigger a job to process the recurring billing
                // For now, just log it
                Log::info('Recurring billing is due for processing', [
                    'recurring_id' => $recurring->id,
                    'client_id' => $recurring->client_id,
                    'next_date' => $recurring->next_date->toDateString(),
                ]);
            }
        });
    }
}
