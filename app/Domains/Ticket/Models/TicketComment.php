<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TicketComment Model
 * 
 * Unified comment system for tickets, replacing the fragmented TicketReply system.
 * Supports public/internal visibility, various sources, and sentiment analysis.
 */
class TicketComment extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'company_id',
        'content',
        'visibility',
        'source',
        'author_id',
        'author_type',
        'metadata',
        'parent_id',
        'is_resolution',
        'time_entry_id',
        // Sentiment analysis fields
        'sentiment_score',
        'sentiment_label',
        'sentiment_analyzed_at',
        'sentiment_confidence',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'company_id' => 'integer',
        'author_id' => 'integer',
        'parent_id' => 'integer',
        'time_entry_id' => 'integer',
        'metadata' => 'array',
        'is_resolution' => 'boolean',
        'sentiment_analyzed_at' => 'datetime',
        'sentiment_score' => 'decimal:2',
        'sentiment_confidence' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Visibility constants
     */
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_INTERNAL = 'internal';

    /**
     * Source constants
     */
    const SOURCE_MANUAL = 'manual';
    const SOURCE_WORKFLOW = 'workflow';
    const SOURCE_SYSTEM = 'system';
    const SOURCE_API = 'api';
    const SOURCE_EMAIL = 'email';

    /**
     * Author type constants
     */
    const AUTHOR_USER = 'user';
    const AUTHOR_SYSTEM = 'system';
    const AUTHOR_WORKFLOW = 'workflow';
    const AUTHOR_CUSTOMER = 'customer';

    /**
     * Sentiment constants
     */
    const SENTIMENT_POSITIVE = 'POSITIVE';
    const SENTIMENT_WEAK_POSITIVE = 'WEAK_POSITIVE';
    const SENTIMENT_NEUTRAL = 'NEUTRAL';
    const SENTIMENT_WEAK_NEGATIVE = 'WEAK_NEGATIVE';
    const SENTIMENT_NEGATIVE = 'NEGATIVE';

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TicketTimeEntry::class, 'time_entry_id');
    }

    /**
     * Get the attachments for the comment
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketCommentAttachment::class, 'ticket_comment_id');
    }

    // ===========================================
    // ACCESSORS & HELPERS
    // ===========================================

    /**
     * Check if comment has attachments
     */
    public function hasAttachments(): bool
    {
        return $this->attachments()->exists();
    }

    /**
     * Add an attachment from uploaded file data
     * @param string $filename The filename to save as
     * @param string $originalFilename The original filename uploaded
     * @param string $mimeType The MIME type of the file
     * @param string $content The base64 encoded content
     * @param int $size The file size in bytes
     * @param int|null $uploadedBy The user ID who uploaded it
     * @return TicketCommentAttachment
     */
    public function addAttachment(
        string $filename,
        string $originalFilename,
        string $mimeType,
        string $content,
        int $size,
        ?int $uploadedBy = null
    ): TicketCommentAttachment {
        return $this->attachments()->create([
            'company_id' => $this->company_id,
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'content' => $content,
            'size' => $size,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    /**
     * Check if comment is public
     */
    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Check if comment is internal
     */
    public function isInternal(): bool
    {
        return $this->visibility === self::VISIBILITY_INTERNAL;
    }

    /**
     * Check if comment is visible to client
     */
    public function isVisibleToClient(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Check if comment is a resolution comment
     */
    public function isResolution(): bool
    {
        return (bool) $this->is_resolution;
    }

    /**
     * Check if comment has time tracked
     */
    public function hasTimeTracked(): bool
    {
        return !is_null($this->time_entry_id);
    }

    /**
     * Get comment excerpt for previews
     */
    public function getExcerpt(int $length = 100): string
    {
        $plainContent = strip_tags($this->content);
        return strlen($plainContent) > $length 
            ? substr($plainContent, 0, $length) . '...'
            : $plainContent;
    }

    /**
     * Get visibility label
     */
    public function getVisibilityLabel(): string
    {
        return match($this->visibility) {
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_INTERNAL => 'Internal',
            default => 'Unknown',
        };
    }

    /**
     * Get visibility color for UI
     */
    public function getVisibilityColor(): string
    {
        return match($this->visibility) {
            self::VISIBILITY_PUBLIC => 'green',
            self::VISIBILITY_INTERNAL => 'amber',
            default => 'zinc',
        };
    }

    /**
     * Get visibility icon for UI
     */
    public function getVisibilityIcon(): string
    {
        return match($this->visibility) {
            self::VISIBILITY_PUBLIC => 'eye',
            self::VISIBILITY_INTERNAL => 'lock-closed',
            default => 'question-mark-circle',
        };
    }

    /**
     * Get source label
     */
    public function getSourceLabel(): string
    {
        return match($this->source) {
            self::SOURCE_MANUAL => 'Manual',
            self::SOURCE_WORKFLOW => 'Workflow',
            self::SOURCE_SYSTEM => 'System',
            self::SOURCE_API => 'API',
            self::SOURCE_EMAIL => 'Email',
            default => 'Unknown',
        };
    }

    /**
     * Check if comment can be edited by user
     */
    public function canBeEditedBy(User $user): bool
    {
        // System and workflow comments cannot be edited
        if (in_array($this->source, [self::SOURCE_SYSTEM, self::SOURCE_WORKFLOW])) {
            return false;
        }

        // Admins can edit any manual comment
        if ($user->hasRole('admin')) {
            return true;
        }

        // Authors can edit their own comments within 1 hour
        if ($this->author_id === $user->id && $this->source === self::SOURCE_MANUAL) {
            return $this->created_at->diffInHours(now()) < 1;
        }

        return false;
    }

    /**
     * Check if comment can be deleted by user
     */
    public function canBeDeletedBy(User $user): bool
    {
        // System and workflow comments cannot be deleted
        if (in_array($this->source, [self::SOURCE_SYSTEM, self::SOURCE_WORKFLOW])) {
            return false;
        }

        // Resolution comments cannot be deleted
        if ($this->is_resolution) {
            return false;
        }

        // Only admins can delete comments
        return $user->hasRole('admin');
    }

    // ===========================================
    // SENTIMENT ANALYSIS
    // ===========================================

    /**
     * Get text content for sentiment analysis
     */
    public function getSentimentAnalysisText(): string
    {
        return trim(strip_tags($this->content));
    }

    /**
     * Check if comment has sentiment analysis
     */
    public function hasSentimentAnalysis(): bool
    {
        return !is_null($this->sentiment_score) && !is_null($this->sentiment_analyzed_at);
    }

    /**
     * Check if comment sentiment is negative
     */
    public function hasNegativeSentiment(): bool
    {
        return in_array($this->sentiment_label, [self::SENTIMENT_NEGATIVE, self::SENTIMENT_WEAK_NEGATIVE]);
    }

    /**
     * Check if comment sentiment is positive
     */
    public function hasPositiveSentiment(): bool
    {
        return in_array($this->sentiment_label, [self::SENTIMENT_POSITIVE, self::SENTIMENT_WEAK_POSITIVE]);
    }

    /**
     * Check if comment sentiment needs attention
     */
    public function sentimentNeedsAttention(): bool
    {
        return $this->hasNegativeSentiment() && ($this->sentiment_confidence ?? 0) > 0.6;
    }

    /**
     * Get sentiment color for UI display
     */
    public function getSentimentColor(): string
    {
        if (!$this->hasSentimentAnalysis()) {
            return 'zinc';
        }

        return match($this->sentiment_label) {
            self::SENTIMENT_POSITIVE => 'emerald',
            self::SENTIMENT_WEAK_POSITIVE => 'lime',
            self::SENTIMENT_NEUTRAL => 'slate',
            self::SENTIMENT_WEAK_NEGATIVE => 'amber',
            self::SENTIMENT_NEGATIVE => 'red',
            default => 'zinc'
        };
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeInternal($query)
    {
        return $query->where('visibility', self::VISIBILITY_INTERNAL);
    }

    public function scopeVisibleToClient($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeByAuthor($query, int $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeResolutionComments($query)
    {
        return $query->where('is_resolution', true);
    }

    public function scopeWithTimeTracked($query)
    {
        return $query->whereNotNull('time_entry_id');
    }

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Sentiment scopes
    public function scopeWithSentimentAnalysis($query)
    {
        return $query->whereNotNull('sentiment_analyzed_at');
    }

    public function scopeWithoutSentimentAnalysis($query)
    {
        return $query->whereNull('sentiment_analyzed_at');
    }

    public function scopePositiveSentiment($query)
    {
        return $query->whereIn('sentiment_label', [self::SENTIMENT_POSITIVE, self::SENTIMENT_WEAK_POSITIVE]);
    }

    public function scopeNegativeSentiment($query)
    {
        return $query->whereIn('sentiment_label', [self::SENTIMENT_NEGATIVE, self::SENTIMENT_WEAK_NEGATIVE]);
    }

    public function scopeSentimentNeedsAttention($query)
    {
        return $query->whereIn('sentiment_label', [self::SENTIMENT_NEGATIVE, self::SENTIMENT_WEAK_NEGATIVE])
                     ->where('sentiment_confidence', '>', 0.6);
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    public static function getVisibilityOptions(): array
    {
        return [
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_INTERNAL => 'Internal',
        ];
    }

    public static function getSourceOptions(): array
    {
        return [
            self::SOURCE_MANUAL => 'Manual',
            self::SOURCE_WORKFLOW => 'Workflow',
            self::SOURCE_SYSTEM => 'System',
            self::SOURCE_API => 'API',
            self::SOURCE_EMAIL => 'Email',
        ];
    }

    public static function getAuthorTypeOptions(): array
    {
        return [
            self::AUTHOR_USER => 'User',
            self::AUTHOR_SYSTEM => 'System',
            self::AUTHOR_WORKFLOW => 'Workflow',
            self::AUTHOR_CUSTOMER => 'Customer',
        ];
    }

    // ===========================================
    // MODEL EVENTS
    // ===========================================

    protected static function boot()
    {
        parent::boot();

        // Update ticket's updated_at timestamp when comment is created
        static::created(function ($comment) {
            $comment->ticket->touch();
        });

        // Set default values
        static::creating(function ($comment) {
            if (empty($comment->visibility)) {
                $comment->visibility = self::VISIBILITY_PUBLIC;
            }
            
            if (empty($comment->source)) {
                $comment->source = self::SOURCE_MANUAL;
            }
            
            if (empty($comment->author_type)) {
                $comment->author_type = self::AUTHOR_USER;
            }
        });
    }
}