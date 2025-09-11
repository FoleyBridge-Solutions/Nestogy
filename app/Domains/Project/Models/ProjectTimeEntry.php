<?php

namespace App\Domains\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ProjectTimeEntry extends Model
{
    protected $table = 'project_time_entries';

    protected $fillable = [
        'project_id',
        'user_id',
        'task_id',
        'description',
        'hours',
        'date',
        'billable',
        'billed',
        'rate',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'billable' => 'boolean',
        'billed' => 'boolean',
    ];

    /**
     * Get the project that owns the time entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who logged the time.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the task associated with this time entry.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Calculate the total cost of this time entry.
     */
    public function getTotalCostAttribute(): float
    {
        return $this->hours * ($this->rate ?? 0);
    }
}