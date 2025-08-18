<?php

namespace App\Domains\Contract\Models;

use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractApproval extends Model
{
    use BelongsToCompany, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'contract_id',
        'approval_type',
        'approval_level',
        'approval_order',
        'approver_id',
        'approver_role',
        'delegated_to_id',
        'status',
        'requested_at',
        'due_date',
        'approved_at',
        'rejected_at',
        'comments',
        'conditions',
        'rejection_reason',
        'can_resubmit',
        'amount_limit',
        'amount_exceeded',
        'notification_sent_at',
        'reminder_sent_at',
        'reminder_count',
        'escalated_at',
        'escalated_to_id',
        'required_documents',
        'all_documents_received',
        'checklist',
        'approval_method',
        'ip_address',
        'user_agent',
        'audit_trail',
        'depends_on_approval_id',
        'can_approve_parallel',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'required_documents' => 'array',
        'checklist' => 'array',
        'audit_trail' => 'array',
        'amount_limit' => 'decimal:2',
        'can_resubmit' => 'boolean',
        'amount_exceeded' => 'boolean',
        'all_documents_received' => 'boolean',
        'can_approve_parallel' => 'boolean',
        'requested_at' => 'datetime',
        'due_date' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    /**
     * Get the contract that owns the approval.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the user who is the approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the user to whom approval was delegated.
     */
    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to_id');
    }

    /**
     * Get the user to whom approval was escalated.
     */
    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_id');
    }

    /**
     * Get the approval this approval depends on.
     */
    public function dependency(): BelongsTo
    {
        return $this->belongsTo(ContractApproval::class, 'depends_on_approval_id');
    }

    /**
     * Scope a query to only include pending approvals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved approvals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected approvals.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if the approval is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    /**
     * Check if the approval has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the approval has been rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}