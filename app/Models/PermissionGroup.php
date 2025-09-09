<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PermissionGroup Model
 * 
 * Represents groups of permissions for better organization in UI.
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 */
class PermissionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the permissions in this group.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'group_id');
    }

    /**
     * Scope ordered groups.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Create a permission group with automatic slug generation.
     */
    public static function createGroup(
        string $name,
        ?string $description = null,
        int $sortOrder = 0
    ): self {
        $slug = str()->slug($name);
        
        return self::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Find group by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }
}