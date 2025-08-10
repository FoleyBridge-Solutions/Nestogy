<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission Model
 * 
 * Represents individual permissions that can be granted to roles or users.
 * Each permission is scoped to a specific domain (clients, assets, etc.) and action.
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $domain
 * @property string $action
 * @property string|null $description
 * @property bool $is_system
 * @property int|null $group_id
 */
class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'action',
        'description',
        'is_system',
        'group_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Permission domains
     */
    const DOMAIN_CLIENTS = 'clients';
    const DOMAIN_ASSETS = 'assets';
    const DOMAIN_FINANCIAL = 'financial';
    const DOMAIN_PROJECTS = 'projects';
    const DOMAIN_REPORTS = 'reports';
    const DOMAIN_TICKETS = 'tickets';
    const DOMAIN_USERS = 'users';
    const DOMAIN_SYSTEM = 'system';

    /**
     * Permission actions
     */
    const ACTION_VIEW = 'view';
    const ACTION_CREATE = 'create';
    const ACTION_EDIT = 'edit';
    const ACTION_DELETE = 'delete';
    const ACTION_MANAGE = 'manage';
    const ACTION_EXPORT = 'export';
    const ACTION_APPROVE = 'approve';
    const ACTION_IMPORT = 'import';

    /**
     * Get available domains
     */
    public static function getAvailableDomains(): array
    {
        return [
            self::DOMAIN_CLIENTS => 'Client Management',
            self::DOMAIN_ASSETS => 'Asset Management',
            self::DOMAIN_FINANCIAL => 'Financial Management',
            self::DOMAIN_PROJECTS => 'Project Management',
            self::DOMAIN_REPORTS => 'Reports & Analytics',
            self::DOMAIN_TICKETS => 'Ticket System',
            self::DOMAIN_USERS => 'User Management',
            self::DOMAIN_SYSTEM => 'System Administration',
        ];
    }

    /**
     * Get available actions
     */
    public static function getAvailableActions(): array
    {
        return [
            self::ACTION_VIEW => 'View/Read',
            self::ACTION_CREATE => 'Create/Add',
            self::ACTION_EDIT => 'Edit/Update',
            self::ACTION_DELETE => 'Delete/Remove',
            self::ACTION_MANAGE => 'Full Management',
            self::ACTION_EXPORT => 'Export Data',
            self::ACTION_APPROVE => 'Approve/Authorize',
            self::ACTION_IMPORT => 'Import Data',
        ];
    }

    /**
     * Get the permission group.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class);
    }

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Get the users who have this permission directly.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot(['company_id', 'granted'])
            ->withTimestamps();
    }

    /**
     * Scope permissions by domain.
     */
    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Scope permissions by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope system permissions.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope non-system permissions.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Create a permission with automatic slug generation.
     */
    public static function createPermission(
        string $name,
        string $domain,
        string $action,
        ?string $description = null,
        bool $isSystem = false,
        ?int $groupId = null
    ): self {
        $slug = strtolower($domain . '.' . $action);
        
        return self::create([
            'name' => $name,
            'slug' => $slug,
            'domain' => $domain,
            'action' => $action,
            'description' => $description,
            'is_system' => $isSystem,
            'group_id' => $groupId,
        ]);
    }

    /**
     * Get permission by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }

    /**
     * Check if permission exists by domain and action.
     */
    public static function existsByDomainAction(string $domain, string $action): bool
    {
        return self::where('domain', $domain)->where('action', $action)->exists();
    }

    /**
     * Get formatted display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: ucfirst($this->action) . ' ' . ucfirst($this->domain);
    }

    /**
     * Get domain label.
     */
    public function getDomainLabelAttribute(): string
    {
        $domains = self::getAvailableDomains();
        return $domains[$this->domain] ?? ucfirst($this->domain);
    }

    /**
     * Get action label.
     */
    public function getActionLabelAttribute(): string
    {
        $actions = self::getAvailableActions();
        return $actions[$this->action] ?? ucfirst($this->action);
    }
}