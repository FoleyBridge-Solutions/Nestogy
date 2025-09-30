<?php

namespace App\Models;

use App\Traits\HasEnhancedPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Silber\Bouncer\Database\HasRolesAndAbilities;

/**
 * User Model
 *
 * Represents system users with authentication and role-based access.
 * Supports multi-tenant architecture with company association.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property bool $status
 * @property string|null $token
 * @property string|null $avatar
 * @property string|null $specific_encryption_ciphertext
 * @property string|null $php_session
 * @property string|null $extension_key
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class User extends Authenticatable
{
    use HasEnhancedPermissions, HasFactory, HasRolesAndAbilities, Notifiable, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'status',
        'token',
        'avatar',
        'specific_encryption_ciphertext',
        'php_session',
        'extension_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'token',
        'specific_encryption_ciphertext',
        'php_session',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * User roles enumeration
     */
    const ROLE_ACCOUNTANT = 1;

    const ROLE_TECH = 2;

    const ROLE_ADMIN = 3;           // Tenant administrator

    const ROLE_SUPER_ADMIN = 4;     // Platform operator (Company 1 only)

    /**
     * Get the user's settings.
     */
    public function userSetting(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Get the company that owns the user.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the clients assigned to this user (for technicians).
     */
    public function assignedClients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'user_clients')
            ->withPivot(['access_level', 'is_primary', 'assigned_at', 'expires_at', 'notes'])
            ->withTimestamps()
            ->whereNull('user_clients.expires_at')
            ->orWhere('user_clients.expires_at', '>', now());
    }

    /**
     * Get all clients this user can access (assigned or via permissions).
     */
    public function accessibleClients()
    {
        // Super admins and admins can access all clients in their company
        if ($this->isA('super-admin') || $this->isA('admin')) {
            return Client::where('company_id', $this->company_id);
        }

        // Other users only see assigned clients
        return $this->assignedClients();
    }

    /**
     * Check if user is assigned to a specific client.
     */
    public function isAssignedToClient($clientId): bool
    {
        // Super admins and admins have access to all clients
        if ($this->isA('super-admin') || $this->isA('admin')) {
            return Client::where('id', $clientId)
                ->where('company_id', $this->company_id)
                ->exists();
        }

        // Check if user is assigned to the client
        return $this->assignedClients()
            ->where('clients.id', $clientId)
            ->exists();
    }

    /**
     * Get user's access level for a specific client.
     */
    public function getClientAccessLevel($clientId): ?string
    {
        // Super admins have admin access to all clients
        if ($this->isA('super-admin')) {
            return 'admin';
        }

        // Admins have manage access to all clients in their company
        if ($this->isA('admin')) {
            return Client::where('id', $clientId)
                ->where('company_id', $this->company_id)
                ->exists() ? 'manage' : null;
        }

        // Get specific assignment access level
        $assignment = $this->assignedClients()
            ->where('clients.id', $clientId)
            ->first();

        return $assignment ? $assignment->pivot->access_level : null;
    }

    /**
     * Get tickets created by this user.
     */
    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    /**
     * Get tickets assigned to this user.
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * Get tickets closed by this user.
     */
    public function closedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'closed_by');
    }

    /**
     * Get ticket replies by this user.
     */
    public function ticketReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'replied_by');
    }

    /**
     * Get projects managed by this user.
     */
    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    /**
     * Get project memberships for this user.
     */
    public function projectMembers(): HasMany
    {
        return $this->hasMany(\App\Domains\Project\Models\ProjectMember::class, 'user_id');
    }

    /**
     * Get projects where this user is a team member.
     */
    public function memberOfProjects(): BelongsToMany
    {
        return $this->belongsToMany(\App\Domains\Project\Models\Project::class, 'project_members', 'user_id', 'project_id')
            ->withPivot(['role', 'hourly_rate', 'can_edit', 'can_manage_tasks', 'joined_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the user's favorite clients.
     */
    public function favoriteClients(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Client::class, 'user_favorite_clients', 'user_id', 'client_id')
            ->wherePivot('user_id', $this->id)
            ->where('clients.status', 'active')
            ->withTimestamps()
            ->orderBy('user_favorite_clients.created_at', 'desc');
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }

    /**
     * Check if user is archived.
     */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Get user's role from settings.
     */
    public function getRole(): int
    {
        return $this->userSetting?->role ?? self::ROLE_ACCOUNTANT;
    }

    /**
     * Check if user has admin role (tenant administrator).
     * Uses Bouncer for role checking.
     */
    public function isAdmin(): bool
    {
        return $this->isA('admin');
    }

    /**
     * Check if user has super admin role (platform operator).
     * For backward compatibility - checks if admin in company 1.
     */
    public function isSuperAdmin(): bool
    {
        return $this->isA('admin') && $this->company_id === 1;
    }

    /**
     * Check if user has any admin role (tenant or super).
     */
    public function isAnyAdmin(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can access cross-tenant features.
     */
    public function canAccessCrossTenant(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user has tech role.
     * Uses Bouncer for role checking.
     */
    public function isTech(): bool
    {
        return $this->isA('tech');
    }

    /**
     * Check if user has accountant role.
     * Uses Bouncer for role checking.
     */
    public function isAccountant(): bool
    {
        return $this->isA('accountant');
    }

    /**
     * Get user's avatar URL.
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/users/'.$this->avatar);
        }

        // Use ui-avatars.com for consistency with navbar
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF&size=150';
    }

    /**
     * Scope to get only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope to get only inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope to get users by role.
     */
    public function scopeByRole($query, int $role)
    {
        return $query->whereHas('userSetting', function ($q) use ($role) {
            $q->where('role', $role);
        });
    }

    /**
     * Get validation rules for user creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'extension_key' => 'nullable|string|max:18',
        ];
    }

    /**
     * Get validation rules for user update.
     */
    public static function getUpdateValidationRules(int $userId): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$userId,
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'extension_key' => 'nullable|string|max:18',
        ];
    }

    /**
     * Bouncer Integration & Backward Compatibility Methods
     */

    /**
     * Get the scope for Bouncer (company-based multi-tenancy).
     */
    public function getBouncerScope()
    {
        return $this->company_id;
    }

    /**
     * Enhanced permission checking with wildcard support.
     */
    public function hasPermission(string $ability, ?int $companyId = null): bool
    {
        // If checking for a different company, temporarily set scope
        if ($companyId && $companyId !== $this->company_id) {
            return \Bouncer::scope()->to($companyId)->can($this, $ability);
        }

        // Use the enhanced permission service that supports wildcards
        return app(\App\Domains\Security\Services\PermissionService::class)->userHasPermission($this, $ability);
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $abilities, ?int $companyId = null): bool
    {
        foreach ($abilities as $ability) {
            if ($this->hasPermission($ability, $companyId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $abilities, ?int $companyId = null): bool
    {
        foreach ($abilities as $ability) {
            if (! $this->hasPermission($ability, $companyId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Bouncer-compatible role checking (backward compatibility).
     */
    public function hasRole(string|array $role, ?int $companyId = null): bool
    {
        $roles = is_array($role) ? $role : [$role];

        // If checking for a different company, temporarily set scope
        if ($companyId && $companyId !== $this->company_id) {
            $originalScope = \Bouncer::scope();
            \Bouncer::scope()->to($companyId);

            foreach ($roles as $roleName) {
                if ($this->isA($roleName)) {
                    \Bouncer::scope($originalScope);

                    return true;
                }
            }
            \Bouncer::scope($originalScope);

            return false;
        }

        // Use Bouncer's isA method which works with current scope
        foreach ($roles as $roleName) {
            if ($this->isA($roleName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign role using Bouncer.
     */
    public function assignRole(string $role, ?int $companyId = null): self
    {
        $scope = $companyId ?? $this->company_id;

        if ($scope) {
            \Bouncer::scope()->to($scope)->dontCache();
        }

        $this->assign($role);

        return $this;
    }

    /**
     * Remove role using Bouncer.
     */
    public function removeRole(string $role, ?int $companyId = null): self
    {
        $scope = $companyId ?? $this->company_id;

        if ($scope) {
            \Bouncer::scope()->to($scope)->dontCache();
        }

        $this->retract($role);

        return $this;
    }

    /**
     * Give permission directly using Bouncer.
     */
    public function givePermissionTo(string $ability, ?int $companyId = null): self
    {
        $scope = $companyId ?? $this->company_id;

        if ($scope) {
            \Bouncer::scope()->to($scope)->dontCache();
        }

        \Bouncer::allow($this)->to($ability);

        return $this;
    }

    /**
     * Revoke permission using Bouncer.
     */
    public function revokePermissionTo(string $ability, ?int $companyId = null): self
    {
        $scope = $companyId ?? $this->company_id;

        if ($scope) {
            \Bouncer::scope()->to($scope)->dontCache();
        }

        \Bouncer::disallow($this)->to($ability);

        return $this;
    }

    /**
     * Get all permissions for user (Bouncer integration).
     */
    public function getAllPermissions(?int $companyId = null)
    {
        $scope = $companyId ?? $this->company_id;

        // Set scope if different from current user's company
        if ($scope && $scope !== $this->company_id) {
            \Bouncer::scope()->to($scope);
        }

        // Get abilities using Bouncer's built-in method
        return $this->getAbilities();
    }

    /**
     * Check domain access using Bouncer.
     */
    public function canAccessDomain(string $domain, ?int $companyId = null): bool
    {
        return $this->hasPermission($domain.'.view', $companyId);
    }

    /**
     * Check domain action using Bouncer.
     */
    public function canPerformAction(string $domain, string $action, ?int $companyId = null): bool
    {
        return $this->hasPermission($domain.'.'.$action, $companyId);
    }

    /**
     * Override hasVerifiedEmail to always return true when SMTP is not configured.
     * This prevents email verification lockout when no email service is available.
     */
    public function hasVerifiedEmail(): bool
    {
        // If SMTP is not configured (using log driver), consider all emails verified
        if (config('mail.mailer') === 'log' || ! config('mail.host')) {
            return true;
        }

        // Normal email verification check
        return ! is_null($this->email_verified_at);
    }

    /**
     * Override markEmailAsVerified to handle non-SMTP environments.
     */
    public function markEmailAsVerified(): bool
    {
        // If SMTP is not configured, mark as verified immediately
        if (config('mail.mailer') === 'log' || ! config('mail.host')) {
            return $this->forceFill([
                'email_verified_at' => $this->freshTimestamp(),
            ])->save();
        }

        // Normal email verification
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }
}
