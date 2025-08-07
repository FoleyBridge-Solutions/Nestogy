<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasArchive;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Ticket Model
 * 
 * Represents support tickets with priority, status, and time tracking.
 * Tickets can be assigned to users, associated with assets, and have replies.
 * 
 * @property int $id
 * @property string|null $prefix
 * @property int $number
 * @property string|null $source
 * @property string|null $category
 * @property string $subject
 * @property string $details
 * @property string|null $priority
 * @property string $status
 * @property bool $billable
 * @property \Illuminate\Support\Carbon|null $schedule
 * @property bool $onsite
 * @property string|null $vendor_ticket_number
 * @property string|null $feedback
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property int $created_by
 * @property int|null $assigned_to
 * @property int|null $closed_by
 * @property int|null $vendor_id
 * @property int $client_id
 * @property int|null $contact_id
 * @property int|null $location_id
 * @property int|null $asset_id
 * @property int|null $invoice_id
 * @property int|null $project_id
 */
class Ticket extends Model
{
    use HasFactory, BelongsToCompany, HasArchive;

    /**
     * The table associated with the model.
     */
    protected $table = 'tickets';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'prefix',
        'number',
        'source',
        'category',
        'subject',
        'details',
        'priority',
        'status',
        'billable',
        'schedule',
        'scheduled_at',
        'onsite',
        'vendor_ticket_number',
        'feedback',
        'closed_at',
        'created_by',
        'assigned_to',
        'closed_by',
        'vendor_id',
        'client_id',
        'contact_id',
        'location_id',
        'asset_id',
        'invoice_id',
        'project_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'number' => 'integer',
        'billable' => 'boolean',
        'onsite' => 'boolean',
        'schedule' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_by' => 'integer',
        'assigned_to' => 'integer',
        'closed_by' => 'integer',
        'vendor_id' => 'integer',
        'client_id' => 'integer',
        'contact_id' => 'integer',
        'location_id' => 'integer',
        'asset_id' => 'integer',
        'invoice_id' => 'integer',
        'project_id' => 'integer',
    ];

    /**
     * Ticket priority enumeration
     */
    const PRIORITY_LOW = 'Low';
    const PRIORITY_MEDIUM = 'Medium';
    const PRIORITY_HIGH = 'High';
    const PRIORITY_CRITICAL = 'Critical';

    /**
     * Ticket status enumeration
     */
    const STATUS_OPEN = 'Open';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_RESOLVED = 'Resolved';
    const STATUS_CLOSED = 'Closed';
    const STATUS_ON_HOLD = 'On Hold';

    /**
     * Ticket source enumeration
     */
    const SOURCE_EMAIL = 'Email';
    const SOURCE_PHONE = 'Phone';
    const SOURCE_PORTAL = 'Portal';
    const SOURCE_WALK_IN = 'Walk-in';
    const SOURCE_INTERNAL = 'Internal';

    /**
     * Get the client that owns the ticket.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the ticket.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to the ticket.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who closed the ticket.
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Get the contact associated with the ticket.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the location associated with the ticket.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the asset associated with the ticket.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the vendor associated with the ticket.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the project associated with the ticket.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the invoice associated with the ticket.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the ticket replies.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * Get public replies only.
     */
    public function publicReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->where('type', 'public');
    }

    /**
     * Get private replies only.
     */
    public function privateReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->where('type', 'private');
    }

    /**
     * Get internal replies only.
     */
    public function internalReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->where('type', 'internal');
    }

    /**
     * Get the ticket's full number.
     */
    public function getFullNumber(): string
    {
        if ($this->prefix) {
            return $this->prefix . '-' . str_pad($this->number, 4, '0', STR_PAD_LEFT);
        }

        return 'TKT-' . str_pad($this->number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if ticket is open.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD]);
    }

    /**
     * Check if ticket is closed.
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Check if ticket is overdue.
     */
    public function isOverdue(): bool
    {
        if (!$this->schedule || $this->isClosed()) {
            return false;
        }

        return Carbon::now()->gt($this->schedule);
    }

    /**
     * Check if ticket is scheduled for today.
     */
    public function isScheduledToday(): bool
    {
        if (!$this->schedule) {
            return false;
        }

        return $this->schedule->isToday();
    }

    /**
     * Check if ticket is high priority.
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    /**
     * Check if ticket is billable.
     */
    public function isBillable(): bool
    {
        return $this->billable === true;
    }

    /**
     * Check if ticket requires onsite visit.
     */
    public function isOnsite(): bool
    {
        return $this->onsite === true;
    }

    /**
     * Check if ticket is archived.
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Get ticket age in hours.
     */
    public function getAgeInHours(): int
    {
        return $this->created_at->diffInHours(Carbon::now());
    }

    /**
     * Get ticket age in days.
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(Carbon::now());
    }

    /**
     * Get time since last update in hours.
     */
    public function getTimeSinceLastUpdateInHours(): int
    {
        $lastReply = $this->replies()->latest()->first();
        $lastUpdate = $lastReply ? $lastReply->created_at : $this->updated_at;
        
        return $lastUpdate->diffInHours(Carbon::now());
    }

    /**
     * Get total time worked on ticket.
     */
    public function getTotalTimeWorked(): string
    {
        $totalMinutes = $this->replies()
            ->whereNotNull('time_worked')
            ->sum(\DB::raw('TIME_TO_SEC(time_worked) / 60'));

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Get reply count.
     */
    public function getReplyCount(): int
    {
        return $this->replies()->count();
    }

    /**
     * Get public reply count.
     */
    public function getPublicReplyCount(): int
    {
        return $this->publicReplies()->count();
    }

    /**
     * Get last reply.
     */
    public function getLastReply(): ?TicketReply
    {
        return $this->replies()->latest()->first();
    }

    /**
     * Get last public reply.
     */
    public function getLastPublicReply(): ?TicketReply
    {
        return $this->publicReplies()->latest()->first();
    }

    /**
     * Close the ticket.
     */
    public function close(int $closedBy, string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_by' => $closedBy,
            'closed_at' => now(),
        ]);

        if ($reason) {
            $this->replies()->create([
                'reply' => $reason,
                'type' => 'internal',
                'replied_by' => $closedBy,
            ]);
        }
    }

    /**
     * Reopen the ticket.
     */
    public function reopen(): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'closed_by' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Assign ticket to user.
     */
    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Get priority color for UI.
     */
    public function getPriorityColor(): string
    {
        return match($this->priority) {
            self::PRIORITY_CRITICAL => '#dc3545',
            self::PRIORITY_HIGH => '#fd7e14',
            self::PRIORITY_MEDIUM => '#28a745',
            self::PRIORITY_LOW => '#6c757d',
            default => '#6c757d',
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => '#dc3545',
            self::STATUS_IN_PROGRESS => '#fd7e14',
            self::STATUS_ON_HOLD => '#6c757d',
            self::STATUS_RESOLVED => '#28a745',
            self::STATUS_CLOSED => '#6c757d',
            default => '#6c757d',
        };
    }

    /**
     * Scope to get open tickets.
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD]);
    }

    /**
     * Scope to get closed tickets.
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Scope to get tickets by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get tickets by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get high priority tickets.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    /**
     * Scope to get overdue tickets.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED])
                    ->whereNotNull('schedule')
                    ->where('schedule', '<', Carbon::now());
    }

    /**
     * Scope to get tickets scheduled for today.
     */
    public function scopeScheduledToday($query)
    {
        return $query->whereDate('schedule', Carbon::today());
    }

    /**
     * Scope to get billable tickets.
     */
    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    /**
     * Scope to get onsite tickets.
     */
    public function scopeOnsite($query)
    {
        return $query->where('onsite', true);
    }

    /**
     * Scope to search tickets.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('subject', 'like', '%' . $search . '%')
              ->orWhere('details', 'like', '%' . $search . '%')
              ->orWhere('number', $search)
              ->orWhere('vendor_ticket_number', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope to get tickets by client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get tickets assigned to user.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Get validation rules for ticket creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'prefix' => 'nullable|string|max:10',
            'number' => 'required|integer|min:1',
            'source' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'details' => 'required|string',
            'priority' => 'nullable|in:Low,Medium,High,Critical',
            'status' => 'required|in:Open,In Progress,Resolved,Closed,On Hold',
            'billable' => 'boolean',
            'schedule' => 'nullable|datetime|after:now',
            'onsite' => 'boolean',
            'vendor_ticket_number' => 'nullable|string|max:255',
            'feedback' => 'nullable|string|max:255',
            'created_by' => 'required|integer|exists:users,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'vendor_id' => 'nullable|integer|exists:vendors,id',
            'client_id' => 'required|integer|exists:clients,id',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'location_id' => 'nullable|integer|exists:locations,id',
            'asset_id' => 'nullable|integer|exists:assets,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ];
    }

    /**
     * Get validation rules for ticket update.
     */
    public static function getUpdateValidationRules(int $ticketId): array
    {
        return self::getValidationRules();
    }

    /**
     * Get available priorities.
     */
    public static function getAvailablePriorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_CRITICAL,
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_ON_HOLD,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ];
    }

    /**
     * Get available sources.
     */
    public static function getAvailableSources(): array
    {
        return [
            self::SOURCE_EMAIL,
            self::SOURCE_PHONE,
            self::SOURCE_PORTAL,
            self::SOURCE_WALK_IN,
            self::SOURCE_INTERNAL,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-increment ticket number for new tickets
        static::creating(function ($ticket) {
            if (!$ticket->number) {
                $lastTicket = static::where('client_id', $ticket->client_id)
                    ->where('prefix', $ticket->prefix)
                    ->orderBy('number', 'desc')
                    ->first();

                $ticket->number = $lastTicket ? $lastTicket->number + 1 : 1;
            }

            // Set default priority and status
            if (empty($ticket->priority)) {
                $ticket->priority = self::PRIORITY_MEDIUM;
            }
            
            if (empty($ticket->status)) {
                $ticket->status = self::STATUS_OPEN;
            }
        });
    }
}