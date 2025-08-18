<?php

namespace App\Domains\Contract\Models;

use App\Domains\Financial\Models\Invoice;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractMilestone extends Model
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
        'name',
        'description',
        'due_date',
        'completed_date',
        'status',
        'amount',
        'is_billable',
        'is_invoiced',
        'invoice_id',
        'deliverables',
        'requires_approval',
        'approved_at',
        'approved_by',
        'approval_notes',
        'progress_percentage',
        'progress_notes',
        'depends_on_milestone_id',
        'sort_order',
        'send_reminder',
        'reminder_days_before',
        'reminder_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'deliverables' => 'array',
        'amount' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_invoiced' => 'boolean',
        'requires_approval' => 'boolean',
        'send_reminder' => 'boolean',
        'due_date' => 'date',
        'completed_date' => 'date',
        'approved_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    /**
     * Get the contract that owns the milestone.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the invoice associated with the milestone.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user who approved the milestone.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the milestone this milestone depends on.
     */
    public function dependency(): BelongsTo
    {
        return $this->belongsTo(ContractMilestone::class, 'depends_on_milestone_id');
    }

    /**
     * Scope a query to only include pending milestones.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include completed milestones.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if the milestone is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && 
               $this->due_date && 
               $this->due_date->isPast();
    }
}