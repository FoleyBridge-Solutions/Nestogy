<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Credit Note Model
 *
 * Manages the complete lifecycle of credit notes including:
 * - Draft creation and validation
 * - Multi-level approval workflows
 * - Tax calculations and VoIP integration
 * - Application to invoices and account credits
 * - Revenue recognition impact tracking
 * - Audit trail and compliance
 */
class CreditNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'credit_notes';

    protected $fillable = [
        'company_id', 'client_id', 'invoice_id', 'created_by', 'approved_by',
        'category_id', 'prefix', 'number', 'reference_number', 'type', 'status',
        'subtotal', 'tax_amount', 'total_amount', 'applied_amount', 'remaining_balance',
        'currency_code', 'exchange_rate', 'tax_breakdown', 'voip_tax_reversal',
        'jurisdiction_taxes', 'reason_code', 'reason_description', 'internal_notes',
        'customer_notes', 'contract_id', 'recurring_invoice_id', 'affects_recurring',
        'proration_details', 'approval_workflow', 'approval_threshold',
        'requires_executive_approval', 'requires_finance_review', 'requires_legal_review',
        'affects_revenue_recognition', 'revenue_impact', 'gl_account_code',
        'credit_date', 'expiry_date', 'approved_at', 'applied_at', 'voided_at',
        'external_id', 'gateway_refund_id', 'gateway_response', 'metadata',
        'original_invoice_data',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'invoice_id' => 'integer',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'category_id' => 'integer',
        'contract_id' => 'integer',
        'recurring_invoice_id' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'voip_tax_reversal' => 'decimal:4',
        'approval_threshold' => 'decimal:2',
        'affects_recurring' => 'boolean',
        'requires_executive_approval' => 'boolean',
        'requires_finance_review' => 'boolean',
        'requires_legal_review' => 'boolean',
        'affects_revenue_recognition' => 'boolean',
        'tax_breakdown' => 'array',
        'jurisdiction_taxes' => 'array',
        'proration_details' => 'array',
        'approval_workflow' => 'array',
        'revenue_impact' => 'array',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'original_invoice_data' => 'array',
        'credit_date' => 'date',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',
        'voided_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Credit Note Types
    const TYPE_FULL_REFUND = 'full_refund';

    const TYPE_PARTIAL_REFUND = 'partial_refund';

    const TYPE_SERVICE_CREDIT = 'service_credit';

    const TYPE_ADJUSTMENT_CREDIT = 'adjustment_credit';

    const TYPE_PROMOTIONAL_CREDIT = 'promotional_credit';

    const TYPE_GOODWILL_CREDIT = 'goodwill_credit';

    const TYPE_CHARGEBACK_CREDIT = 'chargeback_credit';

    const TYPE_TAX_ADJUSTMENT = 'tax_adjustment';

    const TYPE_BILLING_CORRECTION = 'billing_correction';

    // Credit Note Status
    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_APPLIED = 'applied';

    const STATUS_PARTIALLY_APPLIED = 'partially_applied';

    const STATUS_VOIDED = 'voided';

    const STATUS_EXPIRED = 'expired';

    // Reason Codes
    const REASON_BILLING_ERROR = 'billing_error';

    const REASON_SERVICE_CANCELLATION = 'service_cancellation';

    const REASON_EQUIPMENT_RETURN = 'equipment_return';

    const REASON_PORTING_FAILURE = 'porting_failure';

    const REASON_SERVICE_QUALITY = 'service_quality';

    const REASON_CUSTOMER_REQUEST = 'customer_request';

    const REASON_CHARGEBACK = 'chargeback';

    const REASON_DUPLICATE_BILLING = 'duplicate_billing';

    const REASON_RATE_ADJUSTMENT = 'rate_adjustment';

    const REASON_REGULATORY_ADJUSTMENT = 'regulatory_adjustment';

    const REASON_GOODWILL = 'goodwill';

    const REASON_PROMOTIONAL = 'promotional';

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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CreditApplication::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(CreditNoteApproval::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    /**
     * Scopes for common queries
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

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
            ->orWhere(function ($q) {
                $q->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', now());
            });
    }

    public function scopeWithBalance($query)
    {
        return $query->where('remaining_balance', '>', 0);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByReasonCode($query, $reasonCode)
    {
        return $query->where('reason_code', $reasonCode);
    }

    /**
     * Business Logic Methods
     */

    /**
     * Generate credit note number
     */
    public static function generateNumber(?string $prefix = null): string
    {
        $companyId = Auth::user()?->company_id;
        $prefix = $prefix ?? 'CN';
        $year = now()->year;

        $lastCreditNote = self::where('company_id', $companyId)
            ->where('number', 'like', "$prefix-$year-%")
            ->orderBy('number', 'desc')
            ->first();

        if ($lastCreditNote && preg_match("/^$prefix-$year-(\d+)$/", $lastCreditNote->number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.'-'.$year.'-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if credit note is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL]);
    }

    /**
     * Check if credit note is approvable
     */
    public function isApprovable(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if credit note can be applied
     */
    public function canBeApplied(): bool
    {
        return $this->status === self::STATUS_APPROVED && $this->remaining_balance > 0;
    }

    /**
     * Check if credit note is expired
     */
    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date < now()->toDateString();
    }

    /**
     * Get available types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_FULL_REFUND => 'Full Refund',
            self::TYPE_PARTIAL_REFUND => 'Partial Refund',
            self::TYPE_SERVICE_CREDIT => 'Service Credit',
            self::TYPE_ADJUSTMENT_CREDIT => 'Adjustment Credit',
            self::TYPE_PROMOTIONAL_CREDIT => 'Promotional Credit',
            self::TYPE_GOODWILL_CREDIT => 'Goodwill Credit',
            self::TYPE_CHARGEBACK_CREDIT => 'Chargeback Credit',
            self::TYPE_TAX_ADJUSTMENT => 'Tax Adjustment',
            self::TYPE_BILLING_CORRECTION => 'Billing Correction',
        ];
    }

    /**
     * Get available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_APPLIED => 'Applied',
            self::STATUS_PARTIALLY_APPLIED => 'Partially Applied',
            self::STATUS_VOIDED => 'Voided',
            self::STATUS_EXPIRED => 'Expired',
        ];
    }

    /**
     * Get available reason codes
     */
    public static function getReasonCodes(): array
    {
        return [
            self::REASON_BILLING_ERROR => 'Billing Error',
            self::REASON_SERVICE_CANCELLATION => 'Service Cancellation',
            self::REASON_EQUIPMENT_RETURN => 'Equipment Return',
            self::REASON_PORTING_FAILURE => 'Porting Failure',
            self::REASON_SERVICE_QUALITY => 'Service Quality Issue',
            self::REASON_CUSTOMER_REQUEST => 'Customer Request',
            self::REASON_CHARGEBACK => 'Chargeback',
            self::REASON_DUPLICATE_BILLING => 'Duplicate Billing',
            self::REASON_RATE_ADJUSTMENT => 'Rate Adjustment',
            self::REASON_REGULATORY_ADJUSTMENT => 'Regulatory Adjustment',
            self::REASON_GOODWILL => 'Goodwill',
            self::REASON_PROMOTIONAL => 'Promotional',
        ];
    }

    /**
     * Submit for approval
     */
    public function submitForApproval(): bool
    {
        if (! $this->canSubmitForApproval()) {
            return false;
        }

        DB::transaction(function () {
            $this->update([
                'status' => self::STATUS_PENDING_APPROVAL,
            ]);

            // Create approval workflow
            $this->createApprovalWorkflow();

            // Send notifications
            $this->sendApprovalNotifications();
        });

        return true;
    }

    /**
     * Check if can submit for approval
     */
    public function canSubmitForApproval(): bool
    {
        return $this->status === self::STATUS_DRAFT &&
               $this->total_amount > 0 &&
               $this->items()->count() > 0;
    }

    /**
     * Approve credit note
     */
    public function approve(User $approver, ?string $comments = null): bool
    {
        if (! $this->isApprovable()) {
            return false;
        }

        DB::transaction(function () use ($approver, $comments) {
            $this->update([
                'status' => self::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // Update approval records
            $this->updateApprovalStatus('approved', $approver, $comments);

            // Create audit trail
            $this->createAuditEntry('approved', $approver, $comments);

            // Send notifications
            $this->sendApprovalCompletionNotifications();
        });

        return true;
    }

    /**
     * Reject credit note
     */
    public function reject(User $rejector, string $reason): bool
    {
        if (! $this->isApprovable()) {
            return false;
        }

        DB::transaction(function () use ($rejector, $reason) {
            $this->update([
                'status' => self::STATUS_DRAFT,
            ]);

            // Update approval records
            $this->updateApprovalStatus('rejected', $rejector, $reason);

            // Create audit trail
            $this->createAuditEntry('rejected', $rejector, $reason);

            // Send notifications
            $this->sendRejectionNotifications($reason);
        });

        return true;
    }

    /**
     * Void credit note
     */
    public function void(User $voidedBy, string $reason): bool
    {
        if (in_array($this->status, [self::STATUS_VOIDED, self::STATUS_EXPIRED])) {
            return false;
        }

        DB::transaction(function () use ($voidedBy, $reason) {
            // Reverse any applications
            $this->reverseApplications($reason);

            $this->update([
                'status' => self::STATUS_VOIDED,
                'voided_at' => now(),
                'internal_notes' => ($this->internal_notes ? $this->internal_notes."\n\n" : '').
                                  "Voided by {$voidedBy->name} on ".now()->format('Y-m-d H:i:s').
                                  "\nReason: $reason",
            ]);

            // Create audit trail
            $this->createAuditEntry('voided', $voidedBy, $reason);

            // Send notifications
            $this->sendVoidNotifications($reason);
        });

        return true;
    }

    /**
     * Calculate tax amounts using VoIP tax engine
     */
    public function calculateTaxes(): array
    {
        $taxCalculation = [
            'subtotal' => $this->subtotal,
            'tax_amount' => 0,
            'voip_tax_reversal' => 0,
            'jurisdiction_taxes' => [],
            'tax_breakdown' => [],
        ];

        if (! $this->invoice || ! $this->client) {
            return $taxCalculation;
        }

        // For refunds, we reverse the original tax calculations
        if ($this->invoice->tax_breakdown) {
            $originalTaxes = $this->invoice->tax_breakdown;
            $refundRatio = $this->subtotal / ($this->invoice->subtotal ?: 1);

            foreach ($originalTaxes as $tax) {
                $refundTaxAmount = $tax['amount'] * $refundRatio;

                $taxCalculation['tax_breakdown'][] = [
                    'jurisdiction_id' => $tax['jurisdiction_id'] ?? null,
                    'tax_name' => $tax['tax_name'],
                    'tax_rate' => $tax['tax_rate'],
                    'taxable_amount' => $tax['taxable_amount'] * $refundRatio,
                    'amount' => $refundTaxAmount,
                    'is_reversal' => true,
                ];

                $taxCalculation['tax_amount'] += $refundTaxAmount;

                if (isset($tax['is_voip_tax']) && $tax['is_voip_tax']) {
                    $taxCalculation['voip_tax_reversal'] += $refundTaxAmount;
                }
            }
        }

        return $taxCalculation;
    }

    /**
     * Update remaining balance after application
     */
    public function updateRemainingBalance(): void
    {
        $appliedAmount = $this->applications()
            ->where('status', 'applied')
            ->sum('applied_amount');

        $this->update([
            'applied_amount' => $appliedAmount,
            'remaining_balance' => $this->total_amount - $appliedAmount,
        ]);

        // Update status based on remaining balance
        if ($this->remaining_balance <= 0) {
            $this->update(['status' => self::STATUS_APPLIED, 'applied_at' => now()]);
        } elseif ($appliedAmount > 0) {
            $this->update(['status' => self::STATUS_PARTIALLY_APPLIED]);
        }
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount, 2).' '.$this->currency_code;
    }

    /**
     * Get formatted remaining balance
     */
    public function getFormattedRemainingBalanceAttribute(): string
    {
        return number_format($this->remaining_balance, 2).' '.$this->currency_code;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING_APPROVAL => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_APPLIED => 'green',
            self::STATUS_PARTIALLY_APPLIED => 'orange',
            self::STATUS_VOIDED => 'red',
            self::STATUS_EXPIRED => 'red',
            default => 'gray'
        };
    }

    /**
     * Private helper methods
     */
    private function createApprovalWorkflow(): void
    {
        // Implementation would create approval workflow based on company policies
        // This would integrate with the CreditNoteApproval model
    }

    private function sendApprovalNotifications(): void
    {
        // Implementation would send notifications to approvers
    }

    private function sendApprovalCompletionNotifications(): void
    {
        // Implementation would send completion notifications
    }

    private function sendRejectionNotifications(string $reason): void
    {
        // Implementation would send rejection notifications
    }

    private function sendVoidNotifications(string $reason): void
    {
        // Implementation would send void notifications
    }

    private function updateApprovalStatus(string $status, User $user, ?string $comments = null): void
    {
        // Implementation would update approval records
    }

    private function createAuditEntry(string $action, User $user, ?string $details = null): void
    {
        // Implementation would create audit trail entries
    }

    private function reverseApplications(string $reason): void
    {
        // Implementation would reverse any credit applications
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($creditNote) {
            if (! $creditNote->company_id) {
                $creditNote->company_id = Auth::user()?->company_id;
            }

            if (! $creditNote->created_by) {
                $creditNote->created_by = Auth::id();
            }

            if (! $creditNote->number) {
                $creditNote->number = self::generateNumber($creditNote->prefix);
            }

            if (! $creditNote->credit_date) {
                $creditNote->credit_date = now()->toDateString();
            }

            // Initialize remaining balance
            $creditNote->remaining_balance = $creditNote->total_amount;
        });

        static::updating(function ($creditNote) {
            // Auto-expire credits if past expiry date
            if ($creditNote->expiry_date &&
                $creditNote->expiry_date < now()->toDateString() &&
                ! in_array($creditNote->status, [self::STATUS_VOIDED, self::STATUS_EXPIRED])) {
                $creditNote->status = self::STATUS_EXPIRED;
            }
        });
    }
}
