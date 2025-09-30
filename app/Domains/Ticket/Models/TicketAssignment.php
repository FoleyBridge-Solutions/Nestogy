<?php

namespace App\Domains\Ticket\Models;

use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket Assignment Model
 *
 * Represents assignment history and tracking for tickets
 */
class TicketAssignment extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'ticket_id',
        'company_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'unassigned_at',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'company_id' => 'integer',
        'assigned_to' => 'integer',
        'assigned_by' => 'integer',
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
