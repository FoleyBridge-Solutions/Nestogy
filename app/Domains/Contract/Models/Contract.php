<?php

namespace App\Domains\Contract\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Project\Models\Project;
use App\Domains\Shared\Models\Quote;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
{
    use BelongsToCompany, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'contract_number',
        'contract_type',
        'title',
        'description',
        'status',
        'signature_status',
        'client_id',
        'contract_value',
        'currency_code',
        'payment_terms',
        'discount_percentage',
        'tax_rate',
        'start_date',
        'end_date',
        'term_months',
        'signed_date',
        'terms_and_conditions',
        'scope_of_work',
        'deliverables',
        'metadata',
        'auto_renew',
        'renewal_notice_days',
        'renewal_date',
        'quote_id',
        'project_id',
        'created_by',
        'approved_by',
        'document_path',
        'template_used',
        'requires_approval',
        'approved_at',
        'approval_notes',
        'sla_terms',
        'performance_metrics',
        'archived_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'deliverables' => 'array',
        'metadata' => 'array',
        'sla_terms' => 'array',
        'performance_metrics' => 'array',
        'contract_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'auto_renew' => 'boolean',
        'requires_approval' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_date' => 'date',
        'renewal_date' => 'date',
        'approved_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Contract status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_PENDING_SIGNATURE = 'pending_signature';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Signature status constants
     */
    const SIGNATURE_NOT_REQUIRED = 'not_required';
    const SIGNATURE_PENDING = 'pending';
    const SIGNATURE_CLIENT_SIGNED = 'client_signed';
    const SIGNATURE_COMPANY_SIGNED = 'company_signed';
    const SIGNATURE_FULLY_EXECUTED = 'fully_executed';
    const SIGNATURE_DECLINED = 'declined';
    const SIGNATURE_EXPIRED = 'expired';

    /**
     * Get the client that owns the contract.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the quote associated with the contract.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the project associated with the contract.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created the contract.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the contract.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the milestones for the contract.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(ContractMilestone::class);
    }

    /**
     * Get the signatures for the contract.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }

    /**
     * Get the approvals for the contract.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(ContractApproval::class);
    }

    /**
     * Get the invoices associated with the contract.
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'contract_invoice')
            ->withPivot(['invoice_type', 'invoiced_amount', 'description', 'milestone_id', 'billing_period_start', 'billing_period_end'])
            ->withTimestamps();
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'contract_value', 'signed_date', 'approved_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Contract {$eventName}");
    }

    /**
     * Scope a query to only include active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereNull('archived_at');
    }

    /**
     * Scope a query to only include contracts that are not archived.
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope a query to only include archived contracts.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Scope a query to only include contracts for a specific client.
     */
    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Check if the contract is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->archived_at === null;
    }

    /**
     * Check if the contract is expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * Archive the contract (soft delete).
     */
    public function archive(): bool
    {
        $this->archived_at = now();
        return $this->save();
    }

    /**
     * Restore the contract from archive.
     */
    public function restore(): bool
    {
        $this->archived_at = null;
        return $this->save();
    }

    /**
     * Get the audit logs for the contract.
     */
    public function auditLogs()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }
}