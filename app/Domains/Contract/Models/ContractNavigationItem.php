<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ContractNavigationItem Model
 *
 * Represents configurable navigation items for contract management.
 * Fully company-scoped and supports hierarchical navigation.
 */
class ContractNavigationItem extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'contract_navigation_items';

    protected $fillable = [
        'company_id',
        'slug',
        'label',
        'icon',
        'route',
        'parent_slug',
        'sort_order',
        'permissions',
        'conditions',
        'config',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'conditions' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get child navigation items
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_slug', 'slug')
            ->where('company_id', $this->company_id)
            ->orderBy('sort_order');
    }

    /**
     * Get parent navigation item
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_slug', 'slug')
            ->where('company_id', $this->company_id);
    }

    /**
     * Scope to get active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root items (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_slug');
    }

    /**
     * Check if user has permission to see this item
     */
    public function hasPermission($user): bool
    {
        if (empty($this->permissions)) {
            return true;
        }

        // Check if user has any of the required permissions
        foreach ($this->permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if conditions are met to show this item
     */
    public function conditionsMet($context = []): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        // Simple condition evaluation - can be expanded
        foreach ($this->conditions as $condition) {
            // Implement condition checking logic
            // For now, return true
        }

        return true;
    }
}
