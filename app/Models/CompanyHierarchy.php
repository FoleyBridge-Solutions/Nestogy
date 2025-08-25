<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * CompanyHierarchy Model
 * 
 * Manages complex organizational hierarchies using the Closure Table pattern.
 * Supports efficient queries for ancestors, descendants, and path operations.
 * 
 * @property int $id
 * @property int $ancestor_id
 * @property int $descendant_id  
 * @property int $depth
 * @property string|null $path
 * @property string|null $path_names
 * @property string $relationship_type
 * @property array|null $relationship_metadata
 */
class CompanyHierarchy extends Model
{
    use HasFactory;

    protected $fillable = [
        'ancestor_id',
        'descendant_id',
        'depth',
        'path',
        'path_names',
        'relationship_type',
        'relationship_metadata',
    ];

    protected $casts = [
        'relationship_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ancestor company.
     */
    public function ancestor(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'ancestor_id');
    }

    /**
     * Get the descendant company.
     */
    public function descendant(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'descendant_id');
    }

    /**
     * Get all descendants of a company.
     */
    public static function getDescendants(int $companyId, ?int $maxDepth = null): Collection
    {
        $query = static::with(['descendant'])
            ->where('ancestor_id', $companyId)
            ->where('depth', '>', 0);

        if ($maxDepth) {
            $query->where('depth', '<=', $maxDepth);
        }

        return $query->get();
    }

    /**
     * Get all ancestors of a company.
     */
    public static function getAncestors(int $companyId, ?int $maxDepth = null): Collection
    {
        $query = static::with(['ancestor'])
            ->where('descendant_id', $companyId)
            ->where('depth', '>', 0);

        if ($maxDepth) {
            $query->where('depth', '<=', $maxDepth);
        }

        return $query->get();
    }

    /**
     * Get direct children of a company.
     */
    public static function getChildren(int $companyId): Collection
    {
        return static::with(['descendant'])
            ->where('ancestor_id', $companyId)
            ->where('depth', 1)
            ->get();
    }

    /**
     * Get the direct parent of a company.
     */
    public static function getParent(int $companyId): ?CompanyHierarchy
    {
        return static::with(['ancestor'])
            ->where('descendant_id', $companyId)
            ->where('depth', 1)
            ->first();
    }

    /**
     * Get siblings of a company (shares same parent).
     */
    public static function getSiblings(int $companyId): Collection
    {
        $parent = static::getParent($companyId);
        
        if (!$parent) {
            return collect();
        }

        return static::with(['descendant'])
            ->where('ancestor_id', $parent->ancestor_id)
            ->where('descendant_id', '!=', $companyId)
            ->where('depth', 1)
            ->get();
    }

    /**
     * Get the root company for a given company.
     */
    public static function getRoot(int $companyId): ?Company
    {
        $rootHierarchy = static::with(['ancestor'])
            ->where('descendant_id', $companyId)
            ->orderBy('depth', 'desc')
            ->first();

        return $rootHierarchy ? $rootHierarchy->ancestor : Company::find($companyId);
    }

    /**
     * Check if one company is an ancestor of another.
     */
    public static function isAncestor(int $ancestorId, int $descendantId): bool
    {
        return static::where('ancestor_id', $ancestorId)
            ->where('descendant_id', $descendantId)
            ->where('depth', '>', 0)
            ->exists();
    }

    /**
     * Check if one company is a descendant of another.
     */
    public static function isDescendant(int $descendantId, int $ancestorId): bool
    {
        return static::isAncestor($ancestorId, $descendantId);
    }

    /**
     * Check if two companies are related in the hierarchy.
     */
    public static function areRelated(int $companyId1, int $companyId2): bool
    {
        return static::isAncestor($companyId1, $companyId2) || 
               static::isAncestor($companyId2, $companyId1);
    }

    /**
     * Get the full hierarchy tree from a root company.
     */
    public static function getTree(int $rootCompanyId): array
    {
        $descendants = static::getDescendants($rootCompanyId);
        $tree = [];

        // Group by depth for easier tree building
        $byDepth = $descendants->groupBy('depth');

        // Start with root
        $rootCompany = Company::find($rootCompanyId);
        if (!$rootCompany) {
            return [];
        }

        $tree = [
            'company' => $rootCompany,
            'children' => static::buildTreeLevel($rootCompanyId, $byDepth, 1)
        ];

        return $tree;
    }

    /**
     * Build a specific level of the tree recursively.
     */
    private static function buildTreeLevel(int $parentId, Collection $byDepth, int $depth): array
    {
        $children = [];
        $currentLevel = $byDepth->get($depth, collect());

        foreach ($currentLevel as $hierarchy) {
            if (static::isDirectChild($parentId, $hierarchy->descendant_id, $byDepth)) {
                $children[] = [
                    'company' => $hierarchy->descendant,
                    'hierarchy' => $hierarchy,
                    'children' => static::buildTreeLevel($hierarchy->descendant_id, $byDepth, $depth + 1)
                ];
            }
        }

        return $children;
    }

    /**
     * Check if a company is a direct child of another.
     */
    private static function isDirectChild(int $parentId, int $childId, Collection $byDepth): bool
    {
        $directChildren = $byDepth->get(1, collect());
        
        return $directChildren->contains(function ($hierarchy) use ($parentId, $childId) {
            return $hierarchy->ancestor_id == $parentId && $hierarchy->descendant_id == $childId;
        });
    }

    /**
     * Add a company to the hierarchy.
     */
    public static function addToHierarchy(int $parentId, int $childId, string $relationshipType = 'subsidiary'): bool
    {
        if ($parentId === $childId) {
            return false; // Cannot be parent of self
        }

        if (static::isDescendant($parentId, $childId)) {
            return false; // Would create circular reference
        }

        // Get all ancestors of the parent
        $parentAncestors = static::where('descendant_id', $parentId)->get();

        $hierarchies = [];

        // Add relationship to all ancestors of parent
        foreach ($parentAncestors as $ancestor) {
            $hierarchies[] = [
                'ancestor_id' => $ancestor->ancestor_id,
                'descendant_id' => $childId,
                'depth' => $ancestor->depth + 1,
                'relationship_type' => $relationshipType,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Add direct parent-child relationship
        $hierarchies[] = [
            'ancestor_id' => $parentId,
            'descendant_id' => $childId,
            'depth' => 1,
            'relationship_type' => $relationshipType,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Add self-reference
        $hierarchies[] = [
            'ancestor_id' => $childId,
            'descendant_id' => $childId,
            'depth' => 0,
            'relationship_type' => $relationshipType,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        static::insert($hierarchies);
        static::updatePaths($childId);

        return true;
    }

    /**
     * Remove a company and all its descendants from hierarchy.
     */
    public static function removeFromHierarchy(int $companyId): bool
    {
        $descendants = static::getDescendants($companyId)->pluck('descendant_id');
        $descendants->push($companyId);

        // Remove all hierarchy records for this company and its descendants
        static::whereIn('descendant_id', $descendants)->delete();
        static::whereIn('ancestor_id', $descendants)->delete();

        return true;
    }

    /**
     * Move a company to a new parent.
     */
    public static function moveCompany(int $companyId, int $newParentId): bool
    {
        if ($companyId === $newParentId) {
            return false;
        }

        if (static::isDescendant($newParentId, $companyId)) {
            return false; // Would create circular reference
        }

        // Remove from current hierarchy
        $descendants = static::getDescendants($companyId)->pluck('descendant_id');
        $descendants->push($companyId);

        // Remove all ancestor relationships for this subtree
        foreach ($descendants as $descendantId) {
            static::where('descendant_id', $descendantId)
                ->where('ancestor_id', '!=', $descendantId)
                ->delete();
        }

        // Add back to new location
        $relationshipType = static::where('descendant_id', $companyId)
            ->where('depth', 1)
            ->value('relationship_type') ?? 'subsidiary';

        return static::addToHierarchy($newParentId, $companyId, $relationshipType);
    }

    /**
     * Update path information for a company and its descendants.
     */
    protected static function updatePaths(int $companyId): void
    {
        $company = Company::find($companyId);
        if (!$company) {
            return;
        }

        // Get all ancestors ordered by depth (deepest first)
        $ancestors = static::with(['ancestor'])
            ->where('descendant_id', $companyId)
            ->where('depth', '>', 0)
            ->orderBy('depth', 'desc')
            ->get();

        $pathIds = [];
        $pathNames = [];

        foreach ($ancestors as $ancestor) {
            $pathIds[] = $ancestor->ancestor_id;
            $pathNames[] = $ancestor->ancestor->name;
        }

        $pathIds[] = $companyId;
        $pathNames[] = $company->name;

        $path = '/' . implode('/', $pathIds) . '/';
        $pathNamesStr = implode(' / ', $pathNames);

        // Update all hierarchy records for this company
        static::where('descendant_id', $companyId)
            ->update([
                'path' => $path,
                'path_names' => $pathNamesStr
            ]);

        // Recursively update descendants
        $children = static::getChildren($companyId);
        foreach ($children as $child) {
            static::updatePaths($child->descendant_id);
        }
    }

    /**
     * Scope to filter by relationship type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Scope to filter by maximum depth.
     */
    public function scopeMaxDepth(Builder $query, int $maxDepth): Builder
    {
        return $query->where('depth', '<=', $maxDepth);
    }

    /**
     * Scope to get only direct relationships.
     */
    public function scopeDirectOnly(Builder $query): Builder
    {
        return $query->where('depth', 1);
    }
}