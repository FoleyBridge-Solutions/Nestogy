<?php

namespace App\Domains\Knowledge\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Knowledge Base Category Model
 * 
 * @property int $id
 * @property int $company_id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property int $sort_order
 * @property bool $is_active
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class KbCategory extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Parent category relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'parent_id');
    }

    /**
     * Child categories relationship
     */
    public function children(): HasMany
    {
        return $this->hasMany(KbCategory::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Articles in this category
     */
    public function articles(): HasMany
    {
        return $this->hasMany(KbArticle::class, 'category_id');
    }

    /**
     * Published articles in this category
     */
    public function publishedArticles(): HasMany
    {
        return $this->hasMany(KbArticle::class, 'category_id')
            ->published();
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for root categories (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get all descendants (recursive)
     */
    public function getDescendantsAttribute()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants);
        }
        
        return $descendants;
    }

    /**
     * Get all ancestors (recursive)
     */
    public function getAncestorsAttribute()
    {
        $ancestors = collect();
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }
        
        return $ancestors;
    }

    /**
     * Get breadcrumb path
     */
    public function getBreadcrumbAttribute(): array
    {
        $breadcrumb = $this->ancestors->reverse()->push($this);
        return $breadcrumb->pluck('name', 'slug')->toArray();
    }

    /**
     * Get full path string
     */
    public function getFullPathAttribute(): string
    {
        return implode(' > ', $this->breadcrumb);
    }

    /**
     * Count all articles including subcategories
     */
    public function getTotalArticlesCountAttribute(): int
    {
        $count = $this->articles()->count();
        
        foreach ($this->children as $child) {
            $count += $child->total_articles_count;
        }
        
        return $count;
    }

    /**
     * Generate slug from name
     */
    public static function generateSlug(string $name, ?int $parentId = null): string
    {
        $slug = str()->slug($name);
        
        $query = static::where('slug', 'LIKE', "{$slug}%");
        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }
        
        $count = $query->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = self::generateSlug($category->name, $category->parent_id);
            }
            if (is_null($category->sort_order)) {
                $maxOrder = static::where('company_id', $category->company_id)
                    ->where('parent_id', $category->parent_id)
                    ->max('sort_order');
                $category->sort_order = ($maxOrder ?? 0) + 1;
            }
        });

        // Cascade soft deletes to children
        static::deleting(function ($category) {
            if ($category->isForceDeleting()) {
                $category->children()->forceDelete();
            } else {
                $category->children()->delete();
            }
        });
    }
}