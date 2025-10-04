<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability;

/**
 * Permission Model
 *
 * DEPRECATED: This model is maintained for backward compatibility only.
 * The system now uses Bouncer's Ability model for permissions.
 *
 * @deprecated Use Silber\Bouncer\Database\Ability instead
 *
 * @property int $id
 * @property int $company_id
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
    use BelongsToCompany, HasFactory;

    protected $table = 'bouncer_abilities'; // Point to Bouncer abilities table

    protected $fillable = [
        'company_id',
        'name',
        'title',
        'entity_id',
        'entity_type',
        'only_owned',
        'options',
    ];

    protected $casts = [
        'only_owned' => 'boolean',
        'options' => 'json',
    ];

    /**
     * Permission domains - maintained for backward compatibility
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
     * Permission actions - maintained for backward compatibility
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
     * Get permission by name (Bouncer compatibility).
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::where('name', $slug)->first();
    }

    /**
     * Create a permission using Bouncer (backward compatibility).
     */
    public static function createPermission(
        string $name,
        string $domain,
        string $action,
        ?string $description = null,
        bool $isSystem = false,
        ?int $groupId = null
    ): self {
        $slug = strtolower($domain.'.'.$action);

        return self::create([
            'name' => $slug,
            'title' => $name,
        ]);
    }

    /**
     * Get formatted display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->title ?: $this->name;
    }

    /**
     * Get domain from permission name.
     */
    public function getDomainAttribute(): ?string
    {
        $parts = explode('.', $this->name);

        return $parts[0] ?? null;
    }

    /**
     * Get action from permission name.
     */
    public function getActionAttribute(): ?string
    {
        $parts = explode('.', $this->name);

        return $parts[1] ?? null;
    }

    /**
     * Get domain label.
     */
    public function getDomainLabelAttribute(): string
    {
        $domains = self::getAvailableDomains();

        return $domains[$this->domain] ?? ucfirst($this->domain ?? '');
    }

    /**
     * Get action label.
     */
    public function getActionLabelAttribute(): string
    {
        $actions = self::getAvailableActions();

        return $actions[$this->action] ?? ucfirst($this->action ?? '');
    }

    /**
     * Bouncer compatibility methods
     */

    /**
     * Scope permissions by domain.
     */
    public function scopeByDomain($query, string $domain)
    {
        return $query->where('name', 'like', $domain.'.%');
    }

    /**
     * Scope permissions by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('name', 'like', '%.'.$action);
    }

    /**
     * Check if permission exists by domain and action.
     */
    public static function existsByDomainAction(string $domain, string $action): bool
    {
        $slug = strtolower($domain.'.'.$action);

        return self::where('name', $slug)->exists();
    }
}
