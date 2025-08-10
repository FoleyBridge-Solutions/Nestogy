<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Refund Request Model
 * 
 * Manages the complete refund request lifecycle including:
 * - Request creation and validation
 * - Multi-level approval workflows
 * - Equipment return processing
 * - Gateway integration for payment refunds
 * - SLA tracking and compliance
 */
class RefundRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'refund_requests';

    protected $fillable = [
        'company_id', 'client_id', 'credit_note_id', 'invoice_id', 'payment_id',
        'requested_by', 'approved_by', 'processed_by', 'request_number', 'external_reference',
        'refund_type', 'refund_method', 'status', 'priority', 'requested_amount',
        'approved_amount', 'processed_amount', 'currency_code', 'exchange_rate',
        'processing_fee', 'net_refund_amount', 'tax_refund_amount', 'tax_refund_breakdown',
        'voip_tax_refund', 'jurisdiction_tax_refunds', 'reason_code', 'reason_description',
        'customer_explanation', 'internal_notes', 'rejection_reason', 'service_period_start',
        'service_period_end', 'is_prorated', 'proration_calculation', 'unused_days',
        'total_period_days', 'equipment_details', 'equipment_condition',
        'condition_adjustment_percentage', 'equipment_received', 'equipment_received_date',
        'tracking_number', 'contract_id', 'early_termination', 'early_termination_fee',
        'contract_end_date', 'remaining_contract_months', 'original_gateway',
        'original_transaction_id', 'refund_gateway', 'refund_transaction_id',
        'gateway_response', 'gateway_metadata', 'bank_account_last_four',
        'routing_number_masked', 'account_type', 'account_holder_name', 'check_number',
        'mailing_address', 'check_printed_date', 'check_mailed_date', 'check_tracking_number',
        'approval_workflow', 'requires_manager_approval', 'requires_finance_approval',
        'requires_executive_approval', 'approval_threshold', 'sla_hours', 'sla_deadline',
        'sla_breached', 'processing_time_hours', 'customer_notified',
        'customer_notification_sent', 'notification_history', 'compliance_checks',
        'requires_legal_review', 'pci_compliant', 'audit_trail_id', 'requested_at',
        'reviewed_at', 'approved_at', 'processed_at', 'completed_at', 'cancelled_at',
        'metadata', 'source_system'
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'credit_note_id' => 'integer',
        'invoice_id' => 'integer',
        'payment_id' => 'integer',
        'requested_by' => 'integer',
        'approved_by' => 'integer',
        'processed_by' => 'integer',
        'contract_id' => 'integer',
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'processed_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'processing_fee' => 'decimal:2',
        'net_refund_amount' => 'decimal:2',
        'tax_refund_amount' => 'decimal:2',
        'voip_tax_refund' => 'decimal:4',
        'early_termination_fee' => 'decimal:2',
        'approval_threshold' => 'decimal:2',
        'condition_adjustment_percentage' => 'decimal:4',
        'is_prorated' => 'boolean',
        'equipment_received' => 'boolean',
        'early_termination' => 'boolean',
        'requires_manager_approval' => 'boolean',
        'requires_finance_approval' => 'boolean',
        'requires_executive_approval' => 'boolean',
        'sla_breached' => 'boolean',
        'customer_notified' => 'boolean',
        'requires_legal_review' => 'boolean',
        'pci_compliant' => 'boolean',
        'unused_days' => 'integer',
        'total_period_days' => 'integer',
        'remaining_contract_months' => 'integer',
        'sla_hours' => 'integer',
        'processing_time_hours' => 'integer',
        'tax_refund_breakdown' => 'array',
        'jurisdiction_tax_refunds' => 'array',
        'proration_calculation' => 'array',
        'equipment_details' => 'array',
        'gateway_response' => 'array',
        'gateway_metadata' => 'array',
        'approval_workflow' => 'array',
        'notification_history' => 'array',
        'compliance_checks' => 'array',
        'metadata' => 'array',
        'service_period_start' => 'date',
        'service_period_end' => 'date',
        'equipment_received_date' => 'date',
        'contract_end_date' => 'date',
        'check_printed_date' => 'date',
        'check_mailed_date' => 'date',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'customer_notification_sent' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Refund Types
    const TYPE_FULL_REFUND = 'full_refund';
    const TYPE_PARTIAL_REFUND = 'partial_refund';
    const TYPE_SERVICE_CREDIT = 'service_credit';
    const TYPE_EQUIPMENT_RETURN = 'equipment_return';
    const TYPE_CHARGEBACK_REFUND = 'chargeback_refund';
    const TYPE_GOODWILL_REFUND = 'goodwill_refund';
    const TYPE_BILLING_ERROR_REFUND = 'billing_error_refund';
    const TYPE_CANCELLATION_REFUND = 'cancellation_refund';
    const TYPE_PRORATION_REFUND = 'proration_refund';

    // Refund Methods
    const METHOD_ORIGINAL_PAYMENT = 'original_payment';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_ACH = 'ach';
    const METHOD_CHECK = 'check';
    const METHOD_PAYPAL = 'paypal';
    const METHOD_STRIPE = 'stripe';
    const METHOD_ACCOUNT_CREDIT = 'account_credit';
    const METHOD_MANUAL = 'manual';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Priority
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Reason Codes
    const REASON_BILLING_ERROR = 'billing_error';
    const REASON_SERVICE_CANCELLATION = 'service_cancellation';
    const REASON_EQUIPMENT_RETURN = 'equipment_return';
    const REASON_PORTING_FAILURE = 'porting_failure';
    const REASON_SERVICE_QUALITY = 'service_quality';
    const REASON_DUPLICATE_PAYMENT = 'duplicate_payment';
    const REASON_CUSTOMER_REQUEST = 'customer_request';
    const REASON_CHARGEBACK = 'chargeback';
    const REASON_FRAUD = 'fraud';
    const REASON_SYSTEM_ERROR = 'system_error';
    const REASON_REGULATORY_REQUIREMENT = 'regulatory_requirement';
    const REASON_CONTRACT_TERMINATION = 'contract_termination';

    // Equipment Conditions
    const CONDITION_NEW = 'new';
    const CONDITION_EXCELLENT = 'excellent';
    const CONDITION_GOOD = 'good';
    const CONDITION_FAIR = 'fair';
    const CONDITION_POOR = 'poor';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_MISSING = 'missing';

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

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(RefundTransaction::class);
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

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function scopeSlaBreached($query)
    {
        return $query->where('sla_breached', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('sla_deadline', '<', now())
                    ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('refund_type', $type);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('refund_method', $method);
    }

    public function scopeRequiringApproval($query)
    {
        return $query->where(function ($q) {
            $q->where('requires_manager_approval', true)
              ->orWhere('requires_finance_approval', true)
              ->orWhere('requires_executive_approval', true);
        });
    }

    /**
     * Business Logic Methods
     */

    /**
     * Generate request number
     */
    public static function generateRequestNumber(): string
    {
        $companyId = Auth::user()?->company_id;
        $year = now()->year;
        $month = now()->format('m');
        
        $lastRequest = self::where('company_id', $companyId)
            ->where('request_number', 'like', "REF-$year$month-%")
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest && preg_match("/^REF-$year$month-(\d+)$/", $lastRequest->request_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return 'REF-' . $year . $month . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if request is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Check if request is approvable
     */
    public function isApprovable(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    /**
     * Check if request can be processed
     */
    public function canBeProcessed(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if SLA is breached
     */
    public function isSlaBreached(): bool
    {
        return $this->sla_deadline && $this->sla_deadline < now() && 
               !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Calculate processing time in hours
     */
    public function getProcessingTimeHours(): ?int
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->requested_at->diffInHours($this->completed_at);
    }

    /**
     * Submit for review
     */
    public function submitForReview(): bool
    {
        if (!$this->canSubmitForReview()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_UNDER_REVIEW,
            'reviewed_at' => now()
        ]);

        $this->createApprovalWorkflow();
        $this->sendReviewNotifications();

        return true;
    }

    /**
     * Check if can submit for review
     */
    public function canSubmitForReview(): bool
    {
        return $this->status === self::STATUS_PENDING && 
               $this->requested_amount > 0;
    }

    /**
     * Approve refund request
     */
    public function approve(User $approver, ?float $approvedAmount = null, ?string $comments = null): bool
    {
        if (!$this->isApprovable()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approved_amount' => $approvedAmount ?? $this->requested_amount,
            'internal_notes' => $comments ? 
                ($this->internal_notes ? $this->internal_notes . "\n\n" : '') . 
                "Approved by {$approver->name}: $comments" : 
                $this->internal_notes
        ]);

        $this->createAuditEntry('approved', $approver, $comments);
        $this->sendApprovalNotifications();

        return true;
    }

    /**
     * Reject refund request
     */
    public function reject(User $rejector, string $reason): bool
    {
        if (!$this->isApprovable()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'internal_notes' => ($this->internal_notes ? $this->internal_notes . "\n\n" : '') . 
                              "Rejected by {$rejector->name}: $reason"
        ]);

        $this->createAuditEntry('rejected', $rejector, $reason);
        $this->sendRejectionNotifications();

        return true;
    }

    /**
     * Start processing
     */
    public function startProcessing(User $processor): bool
    {
        if (!$this->canBeProcessed()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_by' => $processor->id,
            'processed_at' => now()
        ]);

        $this->createAuditEntry('processing_started', $processor);
        
        return true;
    }

    /**
     * Complete refund request
     */
    public function complete(float $processedAmount, array $transactionData = []): bool
    {
        if ($this->status !== self::STATUS_PROCESSING) {
            return false;
        }

        $processingTime = $this->processed_at ? 
            $this->processed_at->diffInHours(now()) : 
            null;

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'processed_amount' => $processedAmount,
            'processing_time_hours' => $processingTime,
            'net_refund_amount' => $processedAmount - $this->processing_fee
        ]);

        $this->createAuditEntry('completed', $this->processor);
        $this->sendCompletionNotifications();

        return true;
    }

    /**
     * Cancel refund request
     */
    public function cancel(User $cancelledBy, string $reason): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'internal_notes' => ($this->internal_notes ? $this->internal_notes . "\n\n" : '') . 
                              "Cancelled by {$cancelledBy->name}: $reason"
        ]);

        $this->createAuditEntry('cancelled', $cancelledBy, $reason);
        $this->sendCancellationNotifications();

        return true;
    }

    /**
     * Get available refund types
     */
    public static function getRefundTypes(): array
    {
        return [
            self::TYPE_FULL_REFUND => 'Full Refund',
            self::TYPE_PARTIAL_REFUND => 'Partial Refund',
            self::TYPE_SERVICE_CREDIT => 'Service Credit',
            self::TYPE_EQUIPMENT_RETURN => 'Equipment Return',
            self::TYPE_CHARGEBACK_REFUND => 'Chargeback Refund',
            self::TYPE_GOODWILL_REFUND => 'Goodwill Refund',
            self::TYPE_BILLING_ERROR_REFUND => 'Billing Error Refund',
            self::TYPE_CANCELLATION_REFUND => 'Cancellation Refund',
            self::TYPE_PRORATION_REFUND => 'Proration Refund'
        ];
    }

    /**
     * Get available refund methods
     */
    public static function getRefundMethods(): array
    {
        return [
            self::METHOD_ORIGINAL_PAYMENT => 'Original Payment Method',
            self::METHOD_CREDIT_CARD => 'Credit Card',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_ACH => 'ACH',
            self::METHOD_CHECK => 'Check',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_STRIPE => 'Stripe',
            self::METHOD_ACCOUNT_CREDIT => 'Account Credit',
            self::METHOD_MANUAL => 'Manual'
        ];
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_UNDER_REVIEW => 'blue',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_PROCESSING => 'purple',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get formatted requested amount
     */
    public function getFormattedRequestedAmountAttribute(): string
    {
        return number_format($this->requested_amount, 2) . ' ' . $this->currency_code;
    }

    /**
     * Private helper methods
     */
    private function createApprovalWorkflow(): void
    {
        // Implementation would create approval workflow based on amount and type
    }

    private function sendReviewNotifications(): void
    {
        // Implementation would send notifications to reviewers
    }

    private function sendApprovalNotifications(): void
    {
        // Implementation would send approval notifications
    }

    private function sendRejectionNotifications(): void
    {
        // Implementation would send rejection notifications
    }

    private function sendCompletionNotifications(): void
    {
        // Implementation would send completion notifications
    }

    private function sendCancellationNotifications(): void
    {
        // Implementation would send cancellation notifications
    }

    private function createAuditEntry(string $action, User $user, ?string $details = null): void
    {
        // Implementation would create audit trail entries
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($request) {
            if (!$request->company_id) {
                $request->company_id = Auth::user()?->company_id;
            }
            
            if (!$request->requested_by) {
                $request->requested_by = Auth::id();
            }
            
            if (!$request->request_number) {
                $request->request_number = self::generateRequestNumber();
            }
            
            if (!$request->requested_at) {
                $request->requested_at = now();
            }
            
            // Set default SLA deadline
            if (!$request->sla_deadline) {
                $request->sla_deadline = now()->addHours($request->sla_hours ?? 48);
            }
        });
        
        static::updating(function ($request) {
            // Check for SLA breach
            if ($request->sla_deadline < now() && 
                !in_array($request->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]) &&
                !$request->sla_breached) {
                $request->sla_breached = true;
            }
            
            // Update processing time
            if ($request->isDirty('completed_at') && $request->completed_at) {
                $request->processing_time_hours = $request->getProcessingTimeHours();
            }
        });
    }
}