<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

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
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

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
    const ROLE_ADMIN = 3;

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
        return !is_null($this->archived_at);
    }

    /**
     * Get user's role from settings.
     */
    public function getRole(): int
    {
        return $this->userSetting?->role ?? self::ROLE_ACCOUNTANT;
    }

    /**
     * Check if user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->getRole() === self::ROLE_ADMIN;
    }

    /**
     * Check if user has tech role.
     */
    public function isTech(): bool
    {
        return $this->getRole() === self::ROLE_TECH;
    }

    /**
     * Check if user has accountant role.
     */
    public function isAccountant(): bool
    {
        return $this->getRole() === self::ROLE_ACCOUNTANT;
    }

    /**
     * Get user's avatar URL.
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/users/' . $this->avatar);
        }
        
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=identicon&s=150';
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
            'email' => 'required|string|email|max:255|unique:users,email,' . $userId,
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'extension_key' => 'nullable|string|max:18',
        ];
    }
}
