<?php

namespace App\Models;

use App\Domains\Ticket\Models\Ticket;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Project Model
 *
 * Represents client projects with tasks, deadlines, and progress tracking.
 * Projects can have associated tickets and be managed by users.
 *
 * @property int $id
 * @property string|null $prefix
 * @property int $number
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $due
 * @property int|null $manager_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property int $client_id
 */
class Project extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'projects';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'prefix',
        'number',
        'name',
        'description',
        'due',
        'manager_id',
        'completed_at',
        'client_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'number' => 'integer',
        'due' => 'date',
        'manager_id' => 'integer',
        'client_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Project status enumeration
     */
    const STATUS_ACTIVE = 'Active';

    const STATUS_COMPLETED = 'Completed';

    const STATUS_ON_HOLD = 'On Hold';

    const STATUS_CANCELLED = 'Cancelled';

    /**
     * Get the client that owns the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the project manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get tickets associated with this project.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the project's full identifier.
     */
    public function getFullNumber(): string
    {
        if ($this->prefix) {
            return $this->prefix.'-'.str_pad($this->number, 4, '0', STR_PAD_LEFT);
        }

        return 'PRJ-'.str_pad($this->number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the project status based on completion and due date.
     */
    public function getStatus(): string
    {
        if ($this->isCompleted()) {
            return self::STATUS_COMPLETED;
        }

        if ($this->isArchived()) {
            return self::STATUS_CANCELLED;
        }

        if ($this->isOverdue()) {
            return 'Overdue';
        }

        if ($this->isDueSoon()) {
            return 'Due Soon';
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Check if project is completed.
     */
    public function isCompleted(): bool
    {
        return ! is_null($this->completed_at);
    }

    /**
     * Check if project is archived.
     */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Check if project is active.
     */
    public function isActive(): bool
    {
        return ! $this->isCompleted() && ! $this->isArchived();
    }

    /**
     * Check if project is overdue.
     */
    public function isOverdue(): bool
    {
        if (! $this->due || $this->isCompleted()) {
            return false;
        }

        return Carbon::now()->gt($this->due);
    }

    /**
     * Check if project is due soon.
     */
    public function isDueSoon(int $days = 7): bool
    {
        if (! $this->due || $this->isCompleted()) {
            return false;
        }

        return Carbon::now()->diffInDays($this->due, false) <= $days &&
               Carbon::now()->lte($this->due);
    }

    /**
     * Get days until due date.
     */
    public function getDaysUntilDue(): ?int
    {
        if (! $this->due) {
            return null;
        }

        return Carbon::now()->diffInDays($this->due, false);
    }

    /**
     * Get project duration in days.
     */
    public function getDurationInDays(): int
    {
        $endDate = $this->completed_at ?: Carbon::now();

        return $this->created_at->diffInDays($endDate);
    }

    /**
     * Get project progress percentage based on tickets.
     */
    public function getProgressPercentage(): float
    {
        $totalTickets = $this->tickets()->count();

        if ($totalTickets === 0) {
            return $this->isCompleted() ? 100 : 0;
        }

        $completedTickets = $this->tickets()
            ->whereIn('status', ['Resolved', 'Closed'])
            ->count();

        return round(($completedTickets / $totalTickets) * 100, 2);
    }

    /**
     * Mark project as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }

    /**
     * Reopen project.
     */
    public function reopen(): void
    {
        $this->update(['completed_at' => null]);
    }

    /**
     * Get ticket count for this project.
     */
    public function getTicketCount(): int
    {
        return $this->tickets()->count();
    }

    /**
     * Get open ticket count for this project.
     */
    public function getOpenTicketCount(): int
    {
        return $this->tickets()->whereNotIn('status', ['Resolved', 'Closed'])->count();
    }

    /**
     * Get completed ticket count for this project.
     */
    public function getCompletedTicketCount(): int
    {
        return $this->tickets()->whereIn('status', ['Resolved', 'Closed'])->count();
    }

    /**
     * Get total time worked on project (from tickets).
     */
    public function getTotalTimeWorked(): string
    {
        $totalMinutes = $this->tickets()
            ->join('ticket_replies', 'tickets.id', '=', 'ticket_replies.ticket_id')
            ->whereNotNull('ticket_replies.time_worked')
            ->sum(\DB::raw('TIME_TO_SEC(ticket_replies.time_worked) / 60'));

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Get project health status.
     */
    public function getHealthStatus(): string
    {
        if ($this->isCompleted()) {
            return 'Completed';
        }

        if ($this->isOverdue()) {
            return 'Critical';
        }

        if ($this->isDueSoon()) {
            return 'Warning';
        }

        $progress = $this->getProgressPercentage();
        if ($progress >= 75) {
            return 'Good';
        } elseif ($progress >= 50) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }

    /**
     * Scope to get active projects.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Scope to get completed projects.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope to get overdue projects.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNull('completed_at')
            ->whereNotNull('due')
            ->where('due', '<', Carbon::now());
    }

    /**
     * Scope to get projects due soon.
     */
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->whereNull('completed_at')
            ->whereNotNull('due')
            ->where('due', '>=', Carbon::now())
            ->where('due', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Scope to search projects.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhere('prefix', 'like', '%'.$search.'%')
                ->orWhere('number', $search);
        });
    }

    /**
     * Scope to get projects by client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get projects by manager.
     */
    public function scopeForManager($query, int $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    /**
     * Get validation rules for project creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'prefix' => 'nullable|string|max:10',
            'number' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due' => 'nullable|date|after:today',
            'manager_id' => 'nullable|integer|exists:users,id',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    /**
     * Get validation rules for project update.
     */
    public static function getUpdateValidationRules(int $projectId): array
    {
        return self::getValidationRules();
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_ON_HOLD,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-increment project number for new projects
        static::creating(function ($project) {
            if (! $project->number) {
                $lastProject = static::where('client_id', $project->client_id)
                    ->where('prefix', $project->prefix)
                    ->orderBy('number', 'desc')
                    ->first();

                $project->number = $lastProject ? $lastProject->number + 1 : 1;
            }
        });
    }
}
