<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Credit Application Model
 * 
 * Manages the application of credit notes to invoices and accounts,
 * supporting various application methods, automatic matching,
 * and comprehensive audit tracking.
 */
class CreditApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'credit_applications';

    protected $fillable = [
        'company_id', 'credit_note_id', 'invoice_id', 'applied_by', 'reversed_by',
        'application_number', 'reference', 'application_type', 'application_method',
        'status', 'applied_amount', 'remaining_amount', 'currency_code', 'exchange_rate',
        'tax_applied_amount', 'tax_application_breakdown', 'applies_to_tax',
        'invoice_balance_before', 'invoice_balance_after', 'invoice_fully_credited',
        'invoice_credit_percentage', 'line_item_applications', 'applies_to_specific_items',
        'application_date', 'applied_at', 'reversed_at', 'effective_date',
        'is_reversed', 'reversal_reason', 'reversal_amount', 'reversal_details',
        'application_rules', 'auto_apply_to_future', 'priority', 'debit_gl_account',
        'credit_gl_account', 'gl_entries', 'gl_posted', 'gl_posted_at',
        'affects_revenue_recognition', 'revenue_impact', 'revenue_adjustment',
        'is_prorated', 'proration_details', 'service_period_start', 'service_period_end',
        'customer_notified', 'notification_sent_at', 'notification_details',
        'requires_approval', 'approved', 'approved_by', 'approved_at', 'approval_notes',
        'external_id', 'external_references', 'source_system', 'retry_count',
        'next_retry_at', 'error_log', 'failure_reason', 'application_notes',
        'internal_notes', 'metadata'
    ];

    protected $casts = [
        'company_id' => 'integer',
        'credit_note_id' => 'integer',
        'invoice_id' => 'integer',
        'applied_by' => 'integer',
        'reversed_by' => 'integer',
        'approved_by' => 'integer',
        'applied_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'tax_applied_amount' => 'decimal:2',
        'invoice_balance_before' => 'decimal:2',
        'invoice_balance_after' => 'decimal:2',
        'invoice_credit_percentage' => 'decimal:4',
        'reversal_amount' => 'decimal:2',
        'revenue_adjustment' => 'decimal:2',
        'priority' => 'integer',
        'retry_count' => 'integer',
        'applies_to_tax' => 'boolean',
        'invoice_fully_credited' => 'boolean',
        'applies_to_specific_items' => 'boolean',
        'is_reversed' => 'boolean',
        'auto_apply_to_future' => 'boolean',
        'gl_posted' => 'boolean',
        'affects_revenue_recognition' => 'boolean',
        'is_prorated' => 'boolean',
        'customer_notified' => 'boolean',
        'requires_approval' => 'boolean',
        'approved' => 'boolean',
        'tax_application_breakdown' => 'array',
        'line_item_applications' => 'array',
        'reversal_details' => 'array',
        'application_rules' => 'array',
        'gl_entries' => 'array',
        'revenue_impact' => 'array',
        'proration_details' => 'array',
        'notification_details' => 'array',
        'external_references' => 'array',
        'error_log' => 'array',
        'metadata' => 'array',
        'application_date' => 'date',
        'applied_at' => 'datetime',
        'reversed_at' => 'datetime',
        'effective_date' => 'date',
        'gl_posted_at' => 'datetime',
        'service_period_start' => 'date',
        'service_period_end' => 'date',
        'notification_sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Application Types
    const TYPE_AUTOMATIC = 'automatic';
    const TYPE_MANUAL = 'manual';
    const TYPE_PARTIAL = 'partial';
    const TYPE_FULL = 'full';
    const TYPE_OLDEST_FIRST = 'oldest_first';
    const TYPE_SPECIFIC_INVOICE = 'specific_invoice';
    const TYPE_FUTURE_INVOICES = 'future_invoices';

    // Application Methods
    const METHOD_DIRECT_APPLICATION = 'direct_application';
    const METHOD_ACCOUNT_CREDIT = 'account_credit';
    const METHOD_PREPAYMENT = 'prepayment';
    const METHOD_FUTURE_BILLING_CREDIT = 'future_billing_credit';
    const METHOD_PRORATION_ADJUSTMENT = 'proration_adjustment';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_APPLIED = 'applied';
    const STATUS_PARTIALLY_APPLIED = 'partially_applied';
    const STATUS_REVERSED = 'reversed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_FAILED = 'failed';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeApplied($query)
    {
        return $query->where('status', self::STATUS_APPLIED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReversed($query)
    {
        return $query->where('is_reversed', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('application_type', $type);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('application_method', $method);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('application_type', self::TYPE_AUTOMATIC);
    }

    public function scopeManual($query)
    {
        return $query->where('application_type', self::TYPE_MANUAL);
    }

    public function scopeRequiringApproval($query)
    {
        return $query->where('requires_approval', true)->where('approved', false);
    }

    public function scopeGlPosted($query)
    {
        return $query->where('gl_posted', true);
    }

    /**
     * Business Logic Methods
     */

    /**
     * Generate application number
     */
    public static function generateApplicationNumber(): string
    {
        $companyId = Auth::user()?->company_id;
        $year = now()->year;
        
        $lastApplication = self::where('company_id', $companyId)
            ->where('application_number', 'like', "CA-$year-%")
            ->orderBy('application_number', 'desc')
            ->first();

        if ($lastApplication && preg_match("/^CA-$year-(\d+)$/", $lastApplication->application_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return 'CA-' . $year . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if application can be reversed
     */
    public function canBeReversed(): bool
    {
        return $this->status === self::STATUS_APPLIED && !$this->is_reversed;
    }

    /**
     * Check if application is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if application is applied
     */
    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    /**
     * Apply credit to invoice
     */
    public function apply(): bool
    {
        if (!$this->canApply()) {
            return false;
        }

        DB::transaction(function () {
            // Calculate invoice balance before application
            $this->invoice_balance_before = $this->invoice ? $this->invoice->getBalance() : 0;

            // Apply the credit
            $this->status = self::STATUS_APPLIED;
            $this->applied_at = now();
            
            // Update invoice balance
            if ($this->invoice) {
                $this->applyToInvoice();
            } else {
                $this->applyToAccount();
            }
            
            // Update credit note remaining balance
            $this->creditNote->updateRemainingBalance();
            
            // Create GL entries
            if ($this->debit_gl_account && $this->credit_gl_account) {
                $this->createGlEntries();
            }
            
            // Send notifications
            if ($this->customer_notified) {
                $this->sendApplicationNotifications();
            }
            
            $this->save();
        });

        return true;
    }

    /**
     * Check if application can be applied
     */
    public function canApply(): bool
    {
        return $this->isPending() && 
               $this->applied_amount > 0 && 
               (!$this->requires_approval || $this->approved);
    }

    /**
     * Reverse credit application
     */
    public function reverse(User $reversedBy, string $reason): bool
    {
        if (!$this->canBeReversed()) {
            return false;
        }

        DB::transaction(function () use ($reversedBy, $reason) {
            $this->is_reversed = true;
            $this->reversed_by = $reversedBy->id;
            $this->reversed_at = now();
            $this->reversal_reason = $reason;
            $this->reversal_amount = $this->applied_amount;
            $this->status = self::STATUS_REVERSED;

            // Reverse invoice application
            if ($this->invoice) {
                $this->reverseInvoiceApplication();
            }

            // Reverse GL entries
            $this->reverseGlEntries();

            // Update credit note remaining balance
            $this->creditNote->updateRemainingBalance();

            $this->save();
        });

        return true;
    }

    /**
     * Approve application
     */
    public function approve(User $approver, ?string $notes = null): bool
    {
        if (!$this->requires_approval || $this->approved) {
            return false;
        }

        $this->approved = true;
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->save();

        // Auto-apply if configured
        if ($this->application_type === self::TYPE_AUTOMATIC) {
            $this->apply();
        }

        return true;
    }

    /**
     * Calculate application percentage of invoice
     */
    public function calculateInvoicePercentage(): float
    {
        if (!$this->invoice || $this->invoice->amount <= 0) {
            return 0;
        }

        return ($this->applied_amount / $this->invoice->amount) * 100;
    }

    /**
     * Get available application types
     */
    public static function getApplicationTypes(): array
    {
        return [
            self::TYPE_AUTOMATIC => 'Automatic',
            self::TYPE_MANUAL => 'Manual',
            self::TYPE_PARTIAL => 'Partial',
            self::TYPE_FULL => 'Full',
            self::TYPE_OLDEST_FIRST => 'Oldest First',
            self::TYPE_SPECIFIC_INVOICE => 'Specific Invoice',
            self::TYPE_FUTURE_INVOICES => 'Future Invoices'
        ];
    }

    /**
     * Get available application methods
     */
    public static function getApplicationMethods(): array
    {
        return [
            self::METHOD_DIRECT_APPLICATION => 'Direct Application',
            self::METHOD_ACCOUNT_CREDIT => 'Account Credit',
            self::METHOD_PREPAYMENT => 'Prepayment',
            self::METHOD_FUTURE_BILLING_CREDIT => 'Future Billing Credit',
            self::METHOD_PRORATION_ADJUSTMENT => 'Proration Adjustment'
        ];
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPLIED => 'green',
            self::STATUS_PARTIALLY_APPLIED => 'blue',
            self::STATUS_REVERSED => 'red',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_FAILED => 'red',
            default => 'gray'
        };
    }

    /**
     * Get formatted applied amount
     */
    public function getFormattedAppliedAmountAttribute(): string
    {
        return number_format($this->applied_amount, 2) . ' ' . $this->currency_code;
    }

    /**
     * Private helper methods
     */
    private function applyToInvoice(): void
    {
        if (!$this->invoice) {
            return;
        }

        // Apply to specific line items if configured
        if ($this->applies_to_specific_items && $this->line_item_applications) {
            $this->applyToLineItems();
        } else {
            // Apply to invoice total
            $this->applyToInvoiceTotal();
        }

        // Update invoice status if fully credited
        $newBalance = $this->invoice->getBalance() - $this->applied_amount;
        $this->invoice_balance_after = $newBalance;
        $this->invoice_fully_credited = $newBalance <= 0;
        $this->invoice_credit_percentage = $this->calculateInvoicePercentage();

        if ($this->invoice_fully_credited) {
            $this->invoice->update(['status' => 'paid']);
        }
    }

    private function applyToAccount(): void
    {
        // Implementation for account credit application
        // This would create account credit entries
    }

    private function applyToLineItems(): void
    {
        foreach ($this->line_item_applications as $itemId => $amount) {
            // Apply credit to specific invoice line items
            $invoiceItem = $this->invoice->items()->find($itemId);
            if ($invoiceItem) {
                // Implementation would update invoice item credit amounts
            }
        }
    }

    private function applyToInvoiceTotal(): void
    {
        // Implementation would apply credit to invoice total
        // This might involve updating invoice payments or credit applications
    }

    private function reverseInvoiceApplication(): void
    {
        // Implementation would reverse the invoice application
    }

    private function createGlEntries(): void
    {
        $glEntries = [
            [
                'account' => $this->debit_gl_account,
                'debit' => $this->applied_amount,
                'credit' => 0,
                'description' => "Credit application #{$this->application_number}"
            ],
            [
                'account' => $this->credit_gl_account,
                'debit' => 0,
                'credit' => $this->applied_amount,
                'description' => "Credit application #{$this->application_number}"
            ]
        ];

        $this->gl_entries = $glEntries;
        $this->gl_posted = true;
        $this->gl_posted_at = now();
    }

    private function reverseGlEntries(): void
    {
        if (!$this->gl_posted || !$this->gl_entries) {
            return;
        }

        // Create reversing entries
        $reversingEntries = [];
        foreach ($this->gl_entries as $entry) {
            $reversingEntries[] = [
                'account' => $entry['account'],
                'debit' => $entry['credit'], // Reverse debit/credit
                'credit' => $entry['debit'],
                'description' => "Reversal of " . $entry['description']
            ];
        }

        $this->reversal_details = [
            'original_entries' => $this->gl_entries,
            'reversing_entries' => $reversingEntries,
            'reversed_at' => now()
        ];
    }

    private function sendApplicationNotifications(): void
    {
        // Implementation would send notifications to customer
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($application) {
            if (!$application->company_id) {
                $application->company_id = Auth::user()?->company_id;
            }
            
            if (!$application->applied_by) {
                $application->applied_by = Auth::id();
            }
            
            if (!$application->application_number) {
                $application->application_number = self::generateApplicationNumber();
            }
            
            if (!$application->application_date) {
                $application->application_date = now()->toDateString();
            }
            
            // Set default currency from credit note
            if (!$application->currency_code && $application->creditNote) {
                $application->currency_code = $application->creditNote->currency_code;
            }
        });
        
        static::updated(function ($application) {
            // Auto-update credit note remaining balance when application status changes
            if ($application->isDirty('status') && $application->creditNote) {
                $application->creditNote->updateRemainingBalance();
            }
        });
    }
}