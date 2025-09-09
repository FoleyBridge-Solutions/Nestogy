<?php

namespace App\Domains\Knowledge\Models;

use App\Models\BaseModel;
use App\Models\User;
use App\Domains\Client\Models\ClientContact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Knowledge Base Article Feedback Model
 * 
 * @property int $id
 * @property int $company_id
 * @property int $article_id
 * @property int|null $user_id
 * @property int|null $contact_id
 * @property bool $is_helpful
 * @property string|null $feedback_text
 * @property string $feedback_type
 * @property string|null $ip_address
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class KbArticleFeedback extends BaseModel
{
    protected $table = 'kb_article_feedback';

    protected $fillable = [
        'company_id',
        'article_id',
        'user_id',
        'contact_id',
        'is_helpful',
        'feedback_text',
        'feedback_type',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
        'metadata' => 'array',
    ];

    const TYPE_RATING = 'rating';
    const TYPE_COMMENT = 'comment';
    const TYPE_SUGGESTION = 'suggestion';
    const TYPE_REPORT = 'report';

    /**
     * Article relationship
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    /**
     * User relationship (for internal users)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Contact relationship (for client portal users)
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(ClientContact::class, 'contact_id');
    }

    /**
     * Scope for helpful feedback
     */
    public function scopeHelpful($query)
    {
        return $query->where('is_helpful', true);
    }

    /**
     * Scope for not helpful feedback
     */
    public function scopeNotHelpful($query)
    {
        return $query->where('is_helpful', false);
    }

    /**
     * Scope for feedback with comments
     */
    public function scopeWithComments($query)
    {
        return $query->whereNotNull('feedback_text');
    }

    /**
     * Submit feedback for an article
     */
    public static function submit(
        int $articleId,
        bool $isHelpful,
        ?string $feedbackText = null,
        ?int $userId = null,
        ?int $contactId = null,
        string $type = self::TYPE_RATING
    ): self {
        // Check for existing feedback from same user/contact/IP
        $ipAddress = request()->ip();
        
        $existingQuery = self::where('article_id', $articleId)
            ->where('feedback_type', self::TYPE_RATING);
        
        if ($userId) {
            $existingQuery->where('user_id', $userId);
        } elseif ($contactId) {
            $existingQuery->where('contact_id', $contactId);
        } else {
            $existingQuery->where('ip_address', $ipAddress);
        }
        
        $existing = $existingQuery->first();
        
        if ($existing) {
            // Update existing feedback
            $existing->update([
                'is_helpful' => $isHelpful,
                'feedback_text' => $feedbackText,
                'feedback_type' => $type,
            ]);
            $feedback = $existing;
        } else {
            // Create new feedback
            $feedback = self::create([
                'company_id' => auth()->user()?->company_id ?? KbArticle::find($articleId)->company_id,
                'article_id' => $articleId,
                'user_id' => $userId,
                'contact_id' => $contactId,
                'is_helpful' => $isHelpful,
                'feedback_text' => $feedbackText,
                'feedback_type' => $type,
                'ip_address' => $ipAddress,
                'metadata' => [
                    'user_agent' => request()->userAgent(),
                    'referrer' => request()->headers->get('referer'),
                ],
            ]);
        }

        // Update article counters
        self::updateArticleCounters($articleId);

        return $feedback;
    }

    /**
     * Update article helpful/not helpful counters
     */
    protected static function updateArticleCounters(int $articleId): void
    {
        $article = KbArticle::find($articleId);
        if (!$article) {
            return;
        }

        $helpfulCount = self::where('article_id', $articleId)
            ->where('feedback_type', self::TYPE_RATING)
            ->where('is_helpful', true)
            ->count();

        $notHelpfulCount = self::where('article_id', $articleId)
            ->where('feedback_type', self::TYPE_RATING)
            ->where('is_helpful', false)
            ->count();

        $article->update([
            'helpful_count' => $helpfulCount,
            'not_helpful_count' => $notHelpfulCount,
        ]);
    }

    /**
     * Get feedback summary for an article
     */
    public static function getSummary(int $articleId): array
    {
        $feedback = self::where('article_id', $articleId)
            ->where('feedback_type', self::TYPE_RATING)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_helpful = true THEN 1 ELSE 0 END) as helpful,
                SUM(CASE WHEN is_helpful = false THEN 1 ELSE 0 END) as not_helpful
            ')
            ->first();

        $percentage = 0;
        if ($feedback->total > 0) {
            $percentage = round(($feedback->helpful / $feedback->total) * 100, 2);
        }

        return [
            'total' => $feedback->total ?? 0,
            'helpful' => $feedback->helpful ?? 0,
            'not_helpful' => $feedback->not_helpful ?? 0,
            'percentage' => $percentage,
            'recent_comments' => self::where('article_id', $articleId)
                ->whereNotNull('feedback_text')
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($feedback) {
            if (empty($feedback->ip_address)) {
                $feedback->ip_address = request()->ip();
            }
        });
    }
}