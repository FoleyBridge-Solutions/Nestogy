<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TicketReply Model
 * 
 * Represents replies/updates to tickets with time tracking and visibility control.
 * Supports public, private, and internal reply types.
 * 
 * @property int $id
 * @property string $reply
 * @property string $type
 * @property string|null $time_worked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property int $replied_by
 * @property int $ticket_id
 */
class TicketReply extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'ticket_replies';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'reply',
        'type',
        'time_worked',
        'replied_by',
        'ticket_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'replied_by' => 'integer',
        'ticket_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Reply type enumeration
     */
    const TYPE_PUBLIC = 'public';
    const TYPE_PRIVATE = 'private';
    const TYPE_INTERNAL = 'internal';

    /**
     * Type labels mapping
     */
    const TYPE_LABELS = [
        self::TYPE_PUBLIC => 'Public',
        self::TYPE_PRIVATE => 'Private',
        self::TYPE_INTERNAL => 'Internal',
    ];

    /**
     * Get the ticket this reply belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who created this reply.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /**
     * Get the type label.
     */
    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'Unknown';
    }

    /**
     * Check if reply is public.
     */
    public function isPublic(): bool
    {
        return $this->type === self::TYPE_PUBLIC;
    }

    /**
     * Check if reply is private.
     */
    public function isPrivate(): bool
    {
        return $this->type === self::TYPE_PRIVATE;
    }

    /**
     * Check if reply is internal.
     */
    public function isInternal(): bool
    {
        return $this->type === self::TYPE_INTERNAL;
    }

    /**
     * Check if reply is visible to client.
     */
    public function isVisibleToClient(): bool
    {
        return $this->type === self::TYPE_PUBLIC;
    }

    /**
     * Check if reply is archived.
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Check if time was tracked for this reply.
     */
    public function hasTimeTracked(): bool
    {
        return !empty($this->time_worked);
    }

    /**
     * Get time worked in minutes.
     */
    public function getTimeWorkedInMinutes(): int
    {
        if (!$this->time_worked) {
            return 0;
        }

        // Convert TIME format (HH:MM:SS) to minutes
        $parts = explode(':', $this->time_worked);
        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);
        $seconds = (int) ($parts[2] ?? 0);

        return ($hours * 60) + $minutes + ($seconds > 0 ? 1 : 0);
    }

    /**
     * Get formatted time worked.
     */
    public function getFormattedTimeWorked(): string
    {
        if (!$this->time_worked) {
            return '00:00';
        }

        $parts = explode(':', $this->time_worked);
        $hours = str_pad($parts[0] ?? '0', 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($parts[1] ?? '0', 2, '0', STR_PAD_LEFT);

        return $hours . ':' . $minutes;
    }

    /**
     * Set time worked from minutes.
     */
    public function setTimeWorkedFromMinutes(int $minutes): void
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        $this->time_worked = sprintf('%02d:%02d:00', $hours, $mins);
    }

    /**
     * Get reply excerpt for previews.
     */
    public function getExcerpt(int $length = 100): string
    {
        return strlen($this->reply) > $length 
            ? substr($this->reply, 0, $length) . '...'
            : $this->reply;
    }

    /**
     * Get reply word count.
     */
    public function getWordCount(): int
    {
        return str_word_count(strip_tags($this->reply));
    }

    /**
     * Get reply character count.
     */
    public function getCharacterCount(): int
    {
        return strlen(strip_tags($this->reply));
    }

    /**
     * Get type color for UI.
     */
    public function getTypeColor(): string
    {
        return match($this->type) {
            self::TYPE_PUBLIC => '#28a745',
            self::TYPE_PRIVATE => '#fd7e14',
            self::TYPE_INTERNAL => '#6c757d',
            default => '#6c757d',
        };
    }

    /**
     * Get type icon for UI.
     */
    public function getTypeIcon(): string
    {
        return match($this->type) {
            self::TYPE_PUBLIC => 'fas fa-eye',
            self::TYPE_PRIVATE => 'fas fa-eye-slash',
            self::TYPE_INTERNAL => 'fas fa-users',
            default => 'fas fa-comment',
        };
    }

    /**
     * Check if reply can be edited by user.
     */
    public function canBeEditedBy(User $user): bool
    {
        // Only the author can edit within first hour, or admins anytime
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->replied_by === $user->id) {
            return $this->created_at->diffInHours(now()) < 1;
        }

        return false;
    }

    /**
     * Check if reply can be deleted by user.
     */
    public function canBeDeletedBy(User $user): bool
    {
        // Only admins can delete replies
        return $user->isAdmin();
    }

    /**
     * Scope to get public replies.
     */
    public function scopePublic($query)
    {
        return $query->where('type', self::TYPE_PUBLIC);
    }

    /**
     * Scope to get private replies.
     */
    public function scopePrivate($query)
    {
        return $query->where('type', self::TYPE_PRIVATE);
    }

    /**
     * Scope to get internal replies.
     */
    public function scopeInternal($query)
    {
        return $query->where('type', self::TYPE_INTERNAL);
    }

    /**
     * Scope to get replies visible to client.
     */
    public function scopeVisibleToClient($query)
    {
        return $query->where('type', self::TYPE_PUBLIC);
    }

    /**
     * Scope to get replies by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('replied_by', $userId);
    }

    /**
     * Scope to get replies with time tracked.
     */
    public function scopeWithTimeTracked($query)
    {
        return $query->whereNotNull('time_worked');
    }

    /**
     * Scope to get recent replies.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to search replies.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('reply', 'like', '%' . $search . '%');
    }

    /**
     * Get validation rules for reply creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'reply' => 'required|string',
            'type' => 'required|in:public,private,internal',
            'time_worked' => 'nullable|regex:/^([0-9]{1,2}):([0-5][0-9]):([0-5][0-9])$/',
            'replied_by' => 'required|integer|exists:users,id',
            'ticket_id' => 'required|integer|exists:tickets,id',
        ];
    }

    /**
     * Get validation rules for reply update.
     */
    public static function getUpdateValidationRules(int $replyId): array
    {
        return [
            'reply' => 'required|string',
            'type' => 'required|in:public,private,internal',
            'time_worked' => 'nullable|regex:/^([0-9]{1,2}):([0-5][0-9]):([0-5][0-9])$/',
        ];
    }

    /**
     * Get available reply types.
     */
    public static function getAvailableTypes(): array
    {
        return self::TYPE_LABELS;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Update ticket's updated_at timestamp when reply is created
        static::created(function ($reply) {
            $reply->ticket->touch();
        });

        // Set default type if not provided
        static::creating(function ($reply) {
            if (empty($reply->type)) {
                $reply->type = self::TYPE_PUBLIC;
            }
        });
    }
}