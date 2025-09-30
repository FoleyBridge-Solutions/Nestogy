<?php

namespace App\Domains\Knowledge\Models;

use App\Domains\Client\Models\ClientContact;
use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Knowledge Base Article View Tracking Model
 *
 * @property int $id
 * @property int $company_id
 * @property int $article_id
 * @property int|null $user_id
 * @property int|null $contact_id
 * @property string $viewer_type
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property string|null $search_query
 * @property int $time_spent_seconds
 * @property bool $led_to_ticket
 * @property array|null $metadata
 * @property \Carbon\Carbon $viewed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class KbArticleView extends BaseModel
{
    protected $fillable = [
        'company_id',
        'article_id',
        'user_id',
        'contact_id',
        'viewer_type',
        'ip_address',
        'user_agent',
        'referrer',
        'search_query',
        'time_spent_seconds',
        'led_to_ticket',
        'metadata',
        'viewed_at',
    ];

    protected $casts = [
        'led_to_ticket' => 'boolean',
        'metadata' => 'array',
        'viewed_at' => 'datetime',
        'time_spent_seconds' => 'integer',
    ];

    const VIEWER_TYPE_ANONYMOUS = 'anonymous';

    const VIEWER_TYPE_USER = 'user';

    const VIEWER_TYPE_CLIENT = 'client';

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
     * Scope for views that led to tickets
     */
    public function scopeLedToTicket($query)
    {
        return $query->where('led_to_ticket', true);
    }

    /**
     * Scope for views that deflected tickets
     */
    public function scopeDeflectedTicket($query)
    {
        return $query->where('led_to_ticket', false)
            ->whereNotNull('search_query');
    }

    /**
     * Scope for views by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    /**
     * Scope for unique viewers
     */
    public function scopeUniqueViewers($query)
    {
        return $query->select('article_id', 'ip_address', 'viewer_type')
            ->groupBy('article_id', 'ip_address', 'viewer_type');
    }

    /**
     * Track a new article view
     */
    public static function track(
        int $articleId,
        ?int $userId = null,
        ?int $contactId = null,
        ?string $searchQuery = null,
        array $additionalData = []
    ): self {
        $viewerType = self::VIEWER_TYPE_ANONYMOUS;

        if ($userId) {
            $viewerType = self::VIEWER_TYPE_USER;
        } elseif ($contactId) {
            $viewerType = self::VIEWER_TYPE_CLIENT;
        }

        $view = self::create([
            'company_id' => $additionalData['company_id'] ?? auth()->user()?->company_id,
            'article_id' => $articleId,
            'user_id' => $userId,
            'contact_id' => $contactId,
            'viewer_type' => $viewerType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->headers->get('referer'),
            'search_query' => $searchQuery,
            'viewed_at' => now(),
            'metadata' => array_merge($additionalData, [
                'session_id' => session()->getId(),
                'locale' => app()->getLocale(),
            ]),
        ]);

        // Increment article view count
        KbArticle::where('id', $articleId)->increment('views_count');

        return $view;
    }

    /**
     * Update time spent on article
     */
    public function updateTimeSpent(int $seconds): void
    {
        $this->update(['time_spent_seconds' => $seconds]);
    }

    /**
     * Mark as led to ticket
     */
    public function markAsLedToTicket(): void
    {
        $this->update(['led_to_ticket' => true]);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($view) {
            if (empty($view->viewed_at)) {
                $view->viewed_at = now();
            }
        });
    }
}
