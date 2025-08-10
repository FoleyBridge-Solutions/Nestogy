<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * RecurringInvoice Model
 * 
 * Manages recurring invoice schedules based on contracts with support for
 * various billing frequencies, escalations, prorations, and milestone-based billing.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property int $contract_id
 * @property string $title
 * @property string|null $description
 * @property string $billing_frequency
 * @property float $amount
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon $next_invoice_date
 * @property Carbon|null $last_invoice_date
 * @property int $invoice_due_days
 * @property bool $auto_generate
 * @property bool $auto_send
 * @property string|null $payment_terms
 * @property float $tax_rate
 * @property float $discount_percentage
 * @property int|null $billing_cycle_day
 * @property bool $proration_enabled
 * @property float $escalation_percentage
 * @property string $escalation_frequency
 * @property Carbon|null $last_escalation_date
 * @property string $status
 * @property Carbon|null $paused_at
 * @property string|null $pause_reason
 * @property int $invoices_generated
 * @property float $total_revenue_generated
 * @property array|null $metadata
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class RecurringInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'contract_id',
        'title',
        'description',
        'billing_frequency',
        'amount',
        'start_date',
        'end_date',
        'next_invoice_date',
        'last_invoice_date',
        'invoice_due_days',
        'auto_generate',
        'auto_send',
        'payment_terms',
        'tax_rate',
        'discount_percentage',
        'billing_cycle_day',
        'proration_enabled',
        'escalation_percentage',
        'escalation_frequency',
        'last_escalation_date',
        'status',
        'paused_at',
        'pause_reason',
        'invoices_generated',
        'total_revenue_generated',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'next_invoice_date' => 'datetime',
        'last_invoice_date' => 'datetime',
        'last_escalation_date' => 'datetime',
        'paused_at' => 'datetime',
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'discount_percentage' => 'decimal:2',
        'escalation_percentage' => 'decimal:2',
        'total_revenue_generated' => 'decimal:2',
        'auto_generate' => 'boolean',
        'auto_send' => 'boolean',
        'proration_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    // Billing frequency constants
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_BI_WEEKLY = 'bi_weekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_SEMI_ANNUALLY = 'semi_annually';
    const FREQUENCY_ANNUALLY = 'annually';
    const FREQUENCY_BI_ANNUALLY = 'bi_annually';

    // Escalation frequency constants
    const ESCALATION_ANNUAL = 'annual';
    const ESCALATION_BIENNIAL = 'biennial';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePaused($query)
    {
        return $query->where('status', self::STATUS_PAUSED);
    }

    public function scopeDue($query, Carbon $asOfDate = null)
    {
        $asOfDate = $asOfDate ?? now();
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('next_invoice_date', '<=', $asOfDate);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('next_invoice_date', '<', now());
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('billing_frequency', $frequency);
    }

    public function scopeAutoGenerate($query)
    {
        return $query->where('auto_generate', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Accessors & Mutators
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown'
        };
    }

    public function getBillingFrequencyLabelAttribute(): string
    {
        return match($this->billing_frequency) {
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_BI_WEEKLY => 'Bi-weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_QUARTERLY => 'Quarterly',
            self::FREQUENCY_SEMI_ANNUALLY => 'Semi-annually',
            self::FREQUENCY_ANNUALLY => 'Annually',
            self::FREQUENCY_BI_ANNUALLY => 'Bi-annually',
            default => ucwords(str_replace('_', ' ', $this->billing_frequency))
        };
    }

    public function getNextInvoiceAmountAttribute(): float
    {
        $amount = $this->amount;
        
        // Apply discount if applicable
        if ($this->discount_percentage > 0) {
            $amount = $amount * (1 - $this->discount_percentage / 100);
        }
        
        // Add tax if applicable
        if ($this->tax_rate > 0) {
            $amount = $amount * (1 + $this->tax_rate / 100);
        }
        
        return round($amount, 2);
    }

    public function getAnnualizedRevenueAttribute(): float
    {
        switch ($this->billing_frequency) {
            case self::FREQUENCY_WEEKLY:
                return $this->amount * 52;
            case self::FREQUENCY_BI_WEEKLY:
                return $this->amount * 26;
            case self::FREQUENCY_MONTHLY:
                return $this->amount * 12;
            case self::FREQUENCY_QUARTERLY:
                return $this->amount * 4;
            case self::FREQUENCY_SEMI_ANNUALLY:
                return $this->amount * 2;
            case self::FREQUENCY_ANNUALLY:
                return $this->amount;
            case self::FREQUENCY_BI_ANNUALLY:
                return $this->amount / 2;
            default:
                return $this->amount * 12; // Default to monthly
        }
    }

    public function getDaysUntilNextInvoiceAttribute(): int
    {
        if (!$this->next_invoice_date) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->next_invoice_date, false));
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->next_invoice_date && 
               $this->next_invoice_date->isPast();
    }

    public function getEscalationDueAttribute(): bool
    {
        if (!$this->escalation_percentage || $this->escalation_percentage <= 0) {
            return false;
        }

        if (!$this->last_escalation_date) {
            // Check if it's time for first escalation
            $monthsSinceStart = $this->start_date->diffInMonths(now());
            return ($this->escalation_frequency === self::ESCALATION_ANNUAL && $monthsSinceStart >= 12) ||
                   ($this->escalation_frequency === self::ESCALATION_BIENNIAL && $monthsSinceStart >= 24);
        }

        $monthsSinceEscalation = $this->last_escalation_date->diffInMonths(now());
        return ($this->escalation_frequency === self::ESCALATION_ANNUAL && $monthsSinceEscalation >= 12) ||
               ($this->escalation_frequency === self::ESCALATION_BIENNIAL && $monthsSinceEscalation >= 24);
    }

    public function getRemainingInvoicesAttribute(): ?int
    {
        if (!$this->end_date) {
            return null; // Indefinite
        }

        $periodsRemaining = 0;
        $currentDate = $this->next_invoice_date ?? now();
        
        while ($currentDate <= $this->end_date) {
            $periodsRemaining++;
            $currentDate = $this->calculateNextDate($currentDate);
        }

        return $periodsRemaining;
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if recurring invoice is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if recurring invoice is due for generation
     */
    public function isDueForGeneration(Carbon $asOfDate = null): bool
    {
        $asOfDate = $asOfDate ?? now();
        
        return $this->isActive() && 
               $this->auto_generate && 
               $this->next_invoice_date && 
               $this->next_invoice_date <= $asOfDate;
    }

    /**
     * Check if contract is still valid for this recurring invoice
     */
    public function isContractValid(): bool
    {
        return $this->contract && 
               $this->contract->isActive() && 
               (!$this->end_date || $this->end_date >= now());
    }

    /**
     * Calculate next invoice date based on frequency
     */
    public function calculateNextDate(Carbon $fromDate): Carbon
    {
        $nextDate = $fromDate->copy();

        switch ($this->billing_frequency) {
            case self::FREQUENCY_WEEKLY:
                return $nextDate->addWeek();
            case self::FREQUENCY_BI_WEEKLY:
                return $nextDate->addWeeks(2);
            case self::FREQUENCY_MONTHLY:
                if ($this->billing_cycle_day) {
                    return $nextDate->addMonth()->day($this->billing_cycle_day);
                }
                return $nextDate->addMonth();
            case self::FREQUENCY_QUARTERLY:
                return $nextDate->addMonths(3);
            case self::FREQUENCY_SEMI_ANNUALLY:
                return $nextDate->addMonths(6);
            case self::FREQUENCY_ANNUALLY:
                return $nextDate->addYear();
            case self::FREQUENCY_BI_ANNUALLY:
                return $nextDate->addYears(2);
            default:
                throw new \InvalidArgumentException("Unsupported billing frequency: {$this->billing_frequency}");
        }
    }

    /**
     * Pause the recurring invoice
     */
    public function pause(string $reason = null): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PAUSED,
            'paused_at' => now(),
            'pause_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Resume the recurring invoice
     */
    public function resume(): bool
    {
        if ($this->status !== self::STATUS_PAUSED) {
            return false;
        }

        // Recalculate next invoice date if needed
        if ($this->next_invoice_date < now()) {
            $nextDate = $this->calculateNextDate(now());
            $this->next_invoice_date = $nextDate;
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'paused_at' => null,
            'pause_reason' => null,
        ]);

        return true;
    }

    /**
     * Mark as completed
     */
    public function complete(): bool
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'next_invoice_date' => null,
        ]);

        return true;
    }

    /**
     * Cancel the recurring invoice
     */
    public function cancel(string $reason = null): bool
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'pause_reason' => $reason,
            'next_invoice_date' => null,
        ]);

        return true;
    }

    /**
     * Update schedule after invoice generation
     */
    public function updateAfterInvoiceGeneration(Invoice $invoice): void
    {
        $nextDate = $this->calculateNextDate($this->next_invoice_date);

        $this->update([
            'last_invoice_date' => $this->next_invoice_date,
            'next_invoice_date' => $this->end_date && $nextDate > $this->end_date ? null : $nextDate,
            'invoices_generated' => $this->invoices_generated + 1,
            'total_revenue_generated' => $this->total_revenue_generated + $invoice->amount,
        ]);

        // Mark as completed if we've reached the end date
        if (!$this->next_invoice_date) {
            $this->complete();
        }
    }

    /**
     * Apply escalation to the base amount
     */
    public function applyEscalation(): float
    {
        if (!$this->escalation_percentage || $this->escalation_percentage <= 0) {
            return $this->amount;
        }

        $escalatedAmount = $this->amount * (1 + $this->escalation_percentage / 100);

        $this->update([
            'amount' => $escalatedAmount,
            'last_escalation_date' => now(),
        ]);

        return $escalatedAmount;
    }

    /**
     * Get upcoming invoices preview
     */
    public function getUpcomingInvoicesPreview(int $count = 12): array
    {
        $invoices = [];
        $currentDate = $this->next_invoice_date ?? now();
        $amount = $this->next_invoice_amount;
        
        for ($i = 0; $i < $count && (!$this->end_date || $currentDate <= $this->end_date); $i++) {
            $invoices[] = [
                'date' => $currentDate->copy(),
                'due_date' => $currentDate->copy()->addDays($this->invoice_due_days),
                'amount' => $amount,
                'billing_cycle' => $this->invoices_generated + $i + 1,
            ];
            
            $currentDate = $this->calculateNextDate($currentDate);
        }
        
        return $invoices;
    }

    /**
     * Get recurring invoice statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_invoices_generated' => $this->invoices_generated,
            'total_revenue_generated' => $this->total_revenue_generated,
            'average_invoice_amount' => $this->invoices_generated > 0 ? 
                $this->total_revenue_generated / $this->invoices_generated : 0,
            'annualized_revenue' => $this->annualized_revenue,
            'days_until_next_invoice' => $this->days_until_next_invoice,
            'is_overdue' => $this->is_overdue,
            'escalation_due' => $this->escalation_due,
            'remaining_invoices' => $this->remaining_invoices,
            'projected_total_revenue' => $this->remaining_invoices ? 
                $this->total_revenue_generated + ($this->remaining_invoices * $this->next_invoice_amount) : null,
        ];
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($recurringInvoice) {
            if (!$recurringInvoice->company_id && auth()->user()) {
                $recurringInvoice->company_id = auth()->user()->company_id;
            }
            
            if (!$recurringInvoice->created_by && auth()->user()) {
                $recurringInvoice->created_by = auth()->id();
            }
        });

        static::updating(function ($recurringInvoice) {
            // Auto-complete if end date has passed and status is still active
            if ($recurringInvoice->status === self::STATUS_ACTIVE && 
                $recurringInvoice->end_date && 
                $recurringInvoice->end_date->isPast()) {
                $recurringInvoice->status = self::STATUS_COMPLETED;
                $recurringInvoice->next_invoice_date = null;
            }
        });
    }
}