<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomQuickAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'description',
        'icon',
        'color',
        'type',
        'target',
        'parameters',
        'open_in',
        'visibility',
        'allowed_roles',
        'permission',
        'position',
        'is_active',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'allowed_roles' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $attributes = [
        'icon' => 'bolt',
        'color' => 'blue',
        'type' => 'route',
        'open_in' => 'same_tab',
        'visibility' => 'private',
        'position' => 0,
        'is_active' => true,
        'usage_count' => 0,
    ];

    /**
     * Scope for active actions
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for actions visible to a specific user
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            // Private actions for the user
            $q->where(function ($subQ) use ($user) {
                $subQ->where('visibility', 'private')
                    ->where('user_id', $user->id);
            })
            // Company-wide actions
                ->orWhere(function ($subQ) use ($user) {
                    $subQ->where('visibility', 'company')
                        ->where('company_id', $user->company_id);
                })
            // Role-based actions
                ->orWhere(function ($subQ) use ($user) {
                    $subQ->where('visibility', 'role')
                        ->where('company_id', $user->company_id)
                        ->whereJsonContains('allowed_roles', $user->roles->pluck('name')->toArray());
                });
        });
    }

    /**
     * Get the company that owns the action
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user that owns the action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get users who have favorited this action
     */
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'quick_action_favorites')
            ->withTimestamps()
            ->withPivot('position');
    }

    /**
     * Check if the action can be executed by a user
     */
    public function canBeExecutedBy(User $user): bool
    {
        // Check visibility
        if ($this->visibility === 'private' && $this->user_id !== $user->id) {
            return false;
        }

        if ($this->visibility === 'company' && $this->company_id !== $user->company_id) {
            return false;
        }

        if ($this->visibility === 'role') {
            $userRoles = $user->roles->pluck('name')->toArray();
            $allowedRoles = $this->allowed_roles ?? [];
            if (empty(array_intersect($userRoles, $allowedRoles))) {
                return false;
            }
        }

        // Check permission if specified
        if ($this->permission && ! $user->can($this->permission)) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count and update last used timestamp
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get the action configuration for rendering
     */
    public function getActionConfig(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'color' => $this->color,
            'type' => $this->type,
            'target' => $this->target,
            'parameters' => $this->parameters,
            'open_in' => $this->open_in,
            'custom_id' => $this->id,
        ];
    }
}
