<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ContractComment Model
 * 
 * Manages collaborative comments and discussions on contracts
 */
class ContractComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'negotiation_id',
        'version_id',
        'content',
        'comment_type',
        'section',
        'context',
        'parent_id',
        'thread_position',
        'is_internal',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'priority',
        'requires_response',
        'response_due',
        'mentions',
        'attachments',
        'user_id',
        'author_type',
    ];

    protected $casts = [
        'context' => 'array',
        'mentions' => 'array',
        'attachments' => 'array',
        'is_internal' => 'boolean',
        'is_resolved' => 'boolean',
        'requires_response' => 'boolean',
        'resolved_at' => 'datetime',
        'response_due' => 'datetime',
    ];

    // Comment types
    const TYPE_GENERAL = 'general';
    const TYPE_SUGGESTION = 'suggestion';
    const TYPE_OBJECTION = 'objection';
    const TYPE_APPROVAL = 'approval';
    const TYPE_QUESTION = 'question';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Author types
    const AUTHOR_INTERNAL = 'internal';
    const AUTHOR_CLIENT = 'client';
    const AUTHOR_SYSTEM = 'system';

    /**
     * Relationships
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Contract::class);
    }

    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(ContractNegotiation::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class, 'version_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('thread_position');
    }

    /**
     * Scopes
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeClientVisible($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('comment_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeRequiringResponse($query)
    {
        return $query->where('requires_response', true)->where('is_resolved', false);
    }

    public function scopeOverdue($query)
    {
        return $query->where('requires_response', true)
            ->where('is_resolved', false)
            ->where('response_due', '<', now());
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Helper methods
     */
    public function isResolved(): bool
    {
        return $this->is_resolved === true;
    }

    public function isOverdue(): bool
    {
        return $this->requires_response && 
               !$this->is_resolved && 
               $this->response_due && 
               $this->response_due->isPast();
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function hasReplies(): bool
    {
        return $this->replies()->count() > 0;
    }

    public function getThreadDepth(): int
    {
        $depth = 0;
        $current = $this;
        
        while ($current->parent_id) {
            $depth++;
            $current = $current->parent;
        }
        
        return $depth;
    }

    public function getThreadRoot(): self
    {
        $current = $this;
        
        while ($current->parent_id) {
            $current = $current->parent;
        }
        
        return $current;
    }

    public function getAllReplies(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->replies()->with('replies')->get()->flatMap(function ($reply) {
            return collect([$reply])->merge($reply->getAllReplies());
        });
    }

    public function resolve(\App\Models\User $user, string $notes = null): bool
    {
        $result = $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $user->id
        ]);
        
        // Create system comment about resolution
        if ($result && $notes) {
            self::create([
                'contract_id' => $this->contract_id,
                'negotiation_id' => $this->negotiation_id,
                'version_id' => $this->version_id,
                'parent_id' => $this->id,
                'content' => "Comment resolved: {$notes}",
                'comment_type' => self::TYPE_GENERAL,
                'user_id' => $user->id,
                'author_type' => self::AUTHOR_SYSTEM,
                'is_internal' => $this->is_internal,
                'thread_position' => $this->getNextThreadPosition()
            ]);
        }
        
        return $result;
    }

    public function reply(\App\Models\User $user, string $content, array $options = []): self
    {
        return self::create(array_merge([
            'contract_id' => $this->contract_id,
            'negotiation_id' => $this->negotiation_id,
            'version_id' => $this->version_id,
            'parent_id' => $this->id,
            'content' => $content,
            'comment_type' => self::TYPE_GENERAL,
            'user_id' => $user->id,
            'author_type' => self::AUTHOR_INTERNAL,
            'is_internal' => $this->is_internal,
            'thread_position' => $this->getNextThreadPosition()
        ], $options));
    }

    public function mention(array $userIds): bool
    {
        $mentions = $this->mentions ?? [];
        $mentions = array_unique(array_merge($mentions, $userIds));
        
        return $this->update(['mentions' => $mentions]);
    }

    public function addAttachment(string $filename, string $path, int $size): bool
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = [
            'filename' => $filename,
            'path' => $path,
            'size' => $size,
            'uploaded_at' => now()->toISOString()
        ];
        
        return $this->update(['attachments' => $attachments]);
    }

    protected function getNextThreadPosition(): int
    {
        if (!$this->parent_id) {
            return $this->replies()->max('thread_position') + 1;
        }
        
        return $this->parent->replies()->max('thread_position') + 1;
    }

    public function getDaysOld(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getFormattedContent(): string
    {
        $content = $this->content;
        
        // Replace user mentions with formatted names
        if ($this->mentions) {
            foreach ($this->mentions as $userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $content = str_replace("@{$userId}", "@{$user->name}", $content);
                }
            }
        }
        
        return $content;
    }

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_GENERAL => 'General Comment',
            self::TYPE_SUGGESTION => 'Suggestion',
            self::TYPE_OBJECTION => 'Objection',
            self::TYPE_APPROVAL => 'Approval',
            self::TYPE_QUESTION => 'Question'
        ];
    }

    public static function getPriorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent'
        ];
    }

    public function getTypeLabel(): string
    {
        return self::getTypeOptions()[$this->comment_type] ?? 'Unknown';
    }

    public function getPriorityLabel(): string
    {
        return self::getPriorityOptions()[$this->priority] ?? 'Unknown';
    }

    public function canBeEditedBy(\App\Models\User $user): bool
    {
        // Authors can edit their own comments within 15 minutes
        if ($this->user_id === $user->id) {
            return $this->created_at->diffInMinutes(now()) <= 15;
        }
        
        return false;
    }

    public function canBeResolvedBy(\App\Models\User $user): bool
    {
        // Comment author can resolve their own
        if ($this->user_id === $user->id) {
            return true;
        }
        
        // Check if user has permission in the negotiation
        if ($this->negotiation) {
            return $this->negotiation->canUserEdit($user);
        }
        
        return false;
    }
}