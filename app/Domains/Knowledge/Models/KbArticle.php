<?php

namespace App\Domains\Knowledge\Models;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * Knowledge Base Article Model
 * 
 * @property int $id
 * @property int $company_id
 * @property int $category_id
 * @property int $author_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string $excerpt
 * @property string $status
 * @property string $visibility
 * @property array $tags
 * @property array $metadata
 * @property int $views_count
 * @property int $helpful_count
 * @property int $not_helpful_count
 * @property float $deflection_rate
 * @property string $version
 * @property \Carbon\Carbon $published_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class KbArticle extends BaseModel
{
    use SoftDeletes, Searchable;

    protected $fillable = [
        'company_id',
        'category_id',
        'author_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'visibility',
        'tags',
        'metadata',
        'views_count',
        'helpful_count',
        'not_helpful_count',
        'deflection_rate',
        'version',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'published_at' => 'datetime',
        'views_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'deflection_rate' => 'float',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_UNDER_REVIEW = 'under_review';

    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_INTERNAL = 'internal';
    const VISIBILITY_CLIENT = 'client';

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => strip_tags($this->content),
            'excerpt' => $this->excerpt,
            'tags' => $this->tags,
            'category' => $this->category?->name,
            'status' => $this->status,
            'visibility' => $this->visibility,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Category relationship
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'category_id');
    }

    /**
     * Author relationship
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Views relationship
     */
    public function views(): HasMany
    {
        return $this->hasMany(KbArticleView::class, 'article_id');
    }

    /**
     * Feedback relationship
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(KbArticleFeedback::class, 'article_id');
    }

    /**
     * Related articles relationship
     */
    public function relatedArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            KbArticle::class,
            'kb_article_related',
            'article_id',
            'related_article_id'
        )->withTimestamps();
    }

    /**
     * Clients with access (for client-specific articles)
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'kb_article_clients')
            ->withTimestamps();
    }

    /**
     * Scope for published articles
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope for public articles
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    /**
     * Scope for internal articles
     */
    public function scopeInternal($query)
    {
        return $query->where('visibility', self::VISIBILITY_INTERNAL);
    }

    /**
     * Scope for popular articles
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('views_count', 'desc')
            ->orderBy('helpful_count', 'desc')
            ->limit($limit);
    }

    /**
     * Scope for recent articles
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('published_at', 'desc')
            ->limit($limit);
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('views_count');
    }

    /**
     * Calculate helpfulness percentage
     */
    public function getHelpfulnessPercentageAttribute(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) {
            return 0;
        }
        return round(($this->helpful_count / $total) * 100, 2);
    }

    /**
     * Generate slug from title
     */
    public static function generateSlug(string $title): string
    {
        $slug = str()->slug($title);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = self::generateSlug($article->title);
            }
            if (empty($article->version)) {
                $article->version = '1.0.0';
            }
            if (empty($article->excerpt) && !empty($article->content)) {
                $article->excerpt = str()->limit(strip_tags($article->content), 160);
            }
        });

        static::updating(function ($article) {
            if ($article->isDirty('content')) {
                // Increment version on content changes
                $version = explode('.', $article->version);
                $version[2] = (int)$version[2] + 1;
                $article->version = implode('.', $version);
            }
        });
    }
}