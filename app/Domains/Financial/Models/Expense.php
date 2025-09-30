<?php

namespace App\Domains\Financial\Models;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'project_id',
        'submitted_by',
        'approved_by',
        'rejected_by',
        'category_id',
        'title',
        'description',
        'amount',
        'currency',
        'expense_date',
        'vendor',
        'receipt_path',
        'payment_method',
        'reference_number',
        'status',
        'approval_notes',
        'rejection_reason',
        'tags',
        'is_billable',
        'markup_percentage',
        'markup_amount',
        'total_billable_amount',
        'invoiced_at',
        'invoice_id',
        'mileage',
        'mileage_rate',
        'location',
        'business_purpose',
        'attendees',
        'is_recurring',
        'recurring_frequency',
        'recurring_until',
        'parent_expense_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'markup_amount' => 'decimal:2',
        'total_billable_amount' => 'decimal:2',
        'mileage' => 'decimal:2',
        'mileage_rate' => 'decimal:2',
        'expense_date' => 'date',
        'invoiced_at' => 'datetime',
        'recurring_until' => 'date',
        'is_billable' => 'boolean',
        'is_recurring' => 'boolean',
        'tags' => 'json',
        'attendees' => 'json',
        'metadata' => 'json',
    ];

    protected $dates = [
        'expense_date',
        'invoiced_at',
        'recurring_until',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Expense status constants
     */
    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_PAID = 'paid';

    const STATUS_INVOICED = 'invoiced';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Payment method constants
     */
    const METHOD_CASH = 'cash';

    const METHOD_CREDIT_CARD = 'credit_card';

    const METHOD_DEBIT_CARD = 'debit_card';

    const METHOD_CHECK = 'check';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_PETTY_CASH = 'petty_cash';

    const METHOD_PERSONAL = 'personal';

    const METHOD_COMPANY_CARD = 'company_card';

    /**
     * Recurring frequency constants
     */
    const FREQUENCY_WEEKLY = 'weekly';

    const FREQUENCY_BIWEEKLY = 'biweekly';

    const FREQUENCY_MONTHLY = 'monthly';

    const FREQUENCY_QUARTERLY = 'quarterly';

    const FREQUENCY_ANNUALLY = 'annually';

    /**
     * Get the client this expense is for
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the project this expense is for
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who submitted this expense
     */
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the user who approved this expense
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected this expense
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the expense category
     */
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Get the parent expense if this is a recurring child
     */
    public function parentExpense()
    {
        return $this->belongsTo(Expense::class, 'parent_expense_id');
    }

    /**
     * Get child expenses if this is a recurring parent
     */
    public function childExpenses()
    {
        return $this->hasMany(Expense::class, 'parent_expense_id');
    }

    /**
     * Get the invoice this expense was billed to
     */
    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }

    /**
     * Scope for submitted expenses
     */
    public function scopeSubmitted($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_APPROVED,
            self::STATUS_PAID,
            self::STATUS_INVOICED,
        ]);
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    /**
     * Scope for approved expenses
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected expenses
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for billable expenses
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope for non-billable expenses
     */
    public function scopeNonBillable($query)
    {
        return $query->where('is_billable', false);
    }

    /**
     * Scope for uninvoiced billable expenses
     */
    public function scopeUninvoiced($query)
    {
        return $query->where('is_billable', true)
            ->whereNull('invoiced_at')
            ->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for expenses by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('submitted_by', $userId);
    }

    /**
     * Get expense statuses list
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_PAID => 'Paid',
            self::STATUS_INVOICED => 'Invoiced',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get payment methods list
     */
    public static function getPaymentMethods()
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_CREDIT_CARD => 'Credit Card',
            self::METHOD_DEBIT_CARD => 'Debit Card',
            self::METHOD_CHECK => 'Check',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_PETTY_CASH => 'Petty Cash',
            self::METHOD_PERSONAL => 'Personal',
            self::METHOD_COMPANY_CARD => 'Company Card',
        ];
    }

    /**
     * Get recurring frequencies list
     */
    public static function getFrequencies()
    {
        return [
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_BIWEEKLY => 'Bi-weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_QUARTERLY => 'Quarterly',
            self::FREQUENCY_ANNUALLY => 'Annually',
        ];
    }

    /**
     * Check if expense can be approved
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_APPROVAL,
        ]);
    }

    /**
     * Check if expense can be rejected
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_APPROVAL,
        ]);
    }

    /**
     * Check if expense is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if expense is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if expense is billable
     */
    public function isBillable(): bool
    {
        return $this->is_billable === true;
    }

    /**
     * Check if expense has been invoiced
     */
    public function isInvoiced(): bool
    {
        return ! is_null($this->invoiced_at);
    }

    /**
     * Approve expense
     */
    public function approve(User $approver, ?string $notes = null): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approval_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Reject expense
     */
    public function reject(User $rejector, string $reason): bool
    {
        if (! $this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_by' => $rejector->id,
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Submit expense for approval
     */
    public function submit(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PENDING_APPROVAL,
        ]);

        return true;
    }

    /**
     * Calculate total billable amount with markup
     */
    public function calculateBillableAmount(): float
    {
        if (! $this->is_billable) {
            return 0.00;
        }

        $total = (float) $this->amount;

        if ($this->markup_percentage > 0) {
            $total += ($total * ($this->markup_percentage / 100));
        } elseif ($this->markup_amount > 0) {
            $total += (float) $this->markup_amount;
        }

        return $total;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$'.number_format($this->amount, 2);
    }

    /**
     * Get formatted billable amount
     */
    public function getFormattedBillableAmountAttribute(): string
    {
        return '$'.number_format($this->total_billable_amount ?? $this->calculateBillableAmount(), 2);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute(): string
    {
        switch ($this->status) {
            case self::STATUS_APPROVED:
                return 'bg-green-100 text-green-800';
            case self::STATUS_PENDING_APPROVAL:
                return 'bg-yellow-100 text-yellow-800';
            case self::STATUS_SUBMITTED:
                return 'bg-blue-100 text-blue-800';
            case self::STATUS_REJECTED:
                return 'bg-red-100 text-red-800';
            case self::STATUS_PAID:
                return 'bg-purple-100 text-purple-800';
            case self::STATUS_INVOICED:
                return 'bg-indigo-100 text-indigo-800';
            case self::STATUS_DRAFT:
                return 'bg-gray-100 text-gray-800';
            case self::STATUS_CANCELLED:
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
}
