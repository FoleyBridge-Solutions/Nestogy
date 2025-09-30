<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

/**
 * Contact Model
 *
 * Represents contacts associated with clients and locations.
 * Contacts can have different roles (primary, billing, technical) and authentication capabilities.
 *
 * @property int $id
 * @property string $name
 * @property string|null $title
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $extension
 * @property string|null $mobile
 * @property string|null $photo
 * @property string|null $pin
 * @property string|null $notes
 * @property string|null $auth_method
 * @property string|null $password_hash
 * @property string|null $password_reset_token
 * @property \Illuminate\Support\Carbon|null $token_expire
 * @property bool $primary
 * @property bool $important
 * @property bool $billing
 * @property bool $technical
 * @property string|null $department
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $accessed_at
 * @property int|null $location_id
 * @property int|null $vendor_id
 * @property int $client_id
 */
class Contact extends Authenticatable
{
    use BelongsToCompany, HasFactory, Notifiable, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'contacts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'title',
        'email',
        'phone',
        'extension',
        'mobile',
        'photo',
        'pin',
        'notes',
        'auth_method',
        'password_hash',
        'password_reset_token',
        'token_expire',
        'primary',
        'important',
        'billing',
        'technical',
        'department',
        'location_id',
        'vendor_id',
        'client_id',
        'company_id',
        'accessed_at',
        // Portal access fields
        'has_portal_access',
        'portal_permissions',
        'last_login_at',
        'last_login_ip',
        'login_count',
        'failed_login_count',
        'locked_until',
        'email_verified_at',
        'remember_token',
        'password_changed_at',
        'must_change_password',
        'session_timeout_minutes',
        'allowed_ip_addresses',
        // Portal invitation fields
        'invitation_token',
        'invitation_sent_at',
        'invitation_expires_at',
        'invitation_accepted_at',
        'invitation_sent_by',
        'invitation_status',
        // Communication preferences
        'preferred_contact_method',
        'best_time_to_contact',
        'timezone',
        'language',
        'do_not_disturb',
        'marketing_opt_in',
        // Professional details
        'linkedin_url',
        'assistant_name',
        'assistant_email',
        'assistant_phone',
        'reports_to_id',
        'work_schedule',
        'professional_bio',
        // Location & Availability
        'office_location_id',
        'is_emergency_contact',
        'is_after_hours_contact',
        'out_of_office_start',
        'out_of_office_end',
        // Social & Web presence
        'website',
        'twitter_handle',
        'facebook_url',
        'instagram_handle',
        'company_blog',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
        'password_reset_token',
        'pin',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'primary' => 'boolean',
        'important' => 'boolean',
        'billing' => 'boolean',
        'technical' => 'boolean',
        'token_expire' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'accessed_at' => 'datetime',
        'location_id' => 'integer',
        'vendor_id' => 'integer',
        'client_id' => 'integer',
        // Portal access casts
        'has_portal_access' => 'boolean',
        'portal_permissions' => 'array',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'must_change_password' => 'boolean',
        'allowed_ip_addresses' => 'array',
        'login_count' => 'integer',
        'failed_login_count' => 'integer',
        'session_timeout_minutes' => 'integer',
        // New field casts
        'do_not_disturb' => 'boolean',
        'marketing_opt_in' => 'boolean',
        'reports_to_id' => 'integer',
        'office_location_id' => 'integer',
        'is_emergency_contact' => 'boolean',
        'is_after_hours_contact' => 'boolean',
        'out_of_office_start' => 'date',
        'out_of_office_end' => 'date',
        // Portal invitation fields
        'invitation_sent_at' => 'datetime',
        'invitation_expires_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Authentication methods
     */
    const AUTH_PASSWORD = 'password';

    const AUTH_PIN = 'pin';

    const AUTH_NONE = 'none';

    /**
     * Get the client that owns the contact.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the location this contact is associated with.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the vendor this contact is associated with.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get locations where this contact is the primary contact.
     */
    public function primaryLocations(): HasMany
    {
        return $this->hasMany(Location::class, 'contact_id');
    }

    /**
     * Get assets assigned to this contact.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get tickets created by or assigned to this contact.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the contact this contact reports to.
     */
    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'reports_to_id');
    }

    /**
     * Get the contacts that report to this contact.
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(Contact::class, 'reports_to_id');
    }

    /**
     * Get the office location for this contact.
     */
    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'office_location_id');
    }

    /**
     * Get addresses related to this contact.
     * Returns a collection of addresses from location and client.
     */
    public function getAddressesAttribute()
    {
        $addresses = collect();

        // If contact has a location, add it as an address
        if ($this->location_id && $this->location) {
            // Create a pseudo-address object from location data
            $locationAddress = (object) [
                'id' => 'location_'.$this->location->id,
                'display_name' => $this->location->display_name,
                'formatted_address' => $this->location->formatted_address,
                'phone' => $this->location->phone,
                'type' => 'location',
            ];
            $addresses->push($locationAddress);
        }

        // Add client addresses
        if ($this->client && $this->client->addresses) {
            foreach ($this->client->addresses as $address) {
                $addresses->push($address);
            }
        }

        return $addresses;
    }

    /**
     * Get the contact's photo URL.
     */
    public function getPhotoUrl(): string
    {
        if ($this->photo) {
            return asset('storage/contacts/'.$this->photo);
        }

        if ($this->email) {
            return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email))).'?d=identicon&s=150';
        }

        return asset('images/default-avatar.png');
    }

    /**
     * Get the contact's avatar URL (alias for getPhotoUrl for Laravel compatibility).
     */
    public function getAvatarUrl(): string
    {
        return $this->getPhotoUrl();
    }

    /**
     * Get the contact's full name with title.
     */
    public function getFullNameWithTitle(): string
    {
        if ($this->title) {
            return $this->name.' ('.$this->title.')';
        }

        return $this->name;
    }

    /**
     * Get the contact's primary phone number.
     */
    public function getPrimaryPhone(): ?string
    {
        return $this->mobile ?: $this->phone;
    }

    /**
     * Get formatted phone with extension.
     */
    public function getFormattedPhone(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $phone = $this->phone;
        if ($this->extension) {
            $phone .= ' ext. '.$this->extension;
        }

        return $phone;
    }

    /**
     * Check if contact has a photo.
     */
    public function hasPhoto(): bool
    {
        return ! empty($this->photo);
    }

    /**
     * Check if this is the primary contact.
     */
    public function isPrimary(): bool
    {
        return $this->primary === true;
    }

    /**
     * Check if this is an important contact.
     */
    public function isImportant(): bool
    {
        return $this->important === true;
    }

    /**
     * Check if this is a billing contact.
     */
    public function isBilling(): bool
    {
        return $this->billing === true;
    }

    /**
     * Check if this is a technical contact.
     */
    public function isTechnical(): bool
    {
        return $this->technical === true;
    }

    /**
     * Check if contact is archived.
     */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Check if contact can authenticate.
     */
    public function canAuthenticate(): bool
    {
        return ! empty($this->auth_method) && $this->auth_method !== self::AUTH_NONE;
    }

    /**
     * Check if password reset token is valid.
     */
    public function hasValidResetToken(): bool
    {
        return ! empty($this->password_reset_token) &&
               $this->token_expire &&
               $this->token_expire->isFuture();
    }

    /**
     * Set password for contact.
     */
    public function setPassword(string $password): void
    {
        $this->update([
            'password_hash' => Hash::make($password),
            'auth_method' => self::AUTH_PASSWORD,
        ]);
    }

    /**
     * Set PIN for contact.
     */
    public function setPin(string $pin): void
    {
        $this->update([
            'pin' => Hash::make($pin),
            'auth_method' => self::AUTH_PIN,
        ]);
    }

    /**
     * Verify password.
     */
    public function verifyPassword(string $password): bool
    {
        return $this->auth_method === self::AUTH_PASSWORD &&
               Hash::check($password, $this->password_hash);
    }

    /**
     * Verify PIN.
     */
    public function verifyPin(string $pin): bool
    {
        return $this->auth_method === self::AUTH_PIN &&
               Hash::check($pin, $this->pin);
    }

    /**
     * Generate password reset token.
     */
    public function generateResetToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->update([
            'password_reset_token' => Hash::make($token),
            'token_expire' => now()->addHours(24),
        ]);

        return $token;
    }

    /**
     * Update last accessed timestamp.
     */
    public function updateAccessedAt(): void
    {
        $this->update(['accessed_at' => now()]);
    }

    /**
     * Get contact roles as array.
     */
    public function getRoles(): array
    {
        $roles = [];

        if ($this->primary) {
            $roles[] = 'Primary';
        }
        if ($this->billing) {
            $roles[] = 'Billing';
        }
        if ($this->technical) {
            $roles[] = 'Technical';
        }
        if ($this->important) {
            $roles[] = 'Important';
        }

        return $roles;
    }

    /**
     * Get contact roles as string.
     */
    public function getRolesString(): string
    {
        $roles = $this->getRoles();

        return implode(', ', $roles) ?: 'General';
    }

    /**
     * Get type labels attribute (accessor for type_labels).
     */
    public function getTypeLabelsAttribute(): array
    {
        return $this->getRoles();
    }

    /**
     * Scope to get only primary contacts.
     */
    public function scopePrimary($query)
    {
        return $query->where('primary', true);
    }

    /**
     * Scope to get only billing contacts.
     */
    public function scopeBilling($query)
    {
        return $query->where('billing', true);
    }

    /**
     * Scope to get only technical contacts.
     */
    public function scopeTechnical($query)
    {
        return $query->where('technical', true);
    }

    /**
     * Scope to get only important contacts.
     */
    public function scopeImportant($query)
    {
        return $query->where('important', true);
    }

    /**
     * Scope to search contacts.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')
                ->orWhere('title', 'like', '%'.$search.'%');
        });
    }

    /**
     * Scope to get contacts by client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get contacts by location.
     */
    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope to get contacts with authentication enabled.
     */
    public function scopeWithAuth($query)
    {
        return $query->whereNotNull('auth_method')
            ->where('auth_method', '!=', self::AUTH_NONE);
    }

    /**
     * Get validation rules for contact creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'extension' => 'nullable|string|max:10',
            'mobile' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'pin' => 'nullable|string|min:4|max:10',
            'notes' => 'nullable|string',
            'auth_method' => 'nullable|in:password,pin,none',
            'primary' => 'boolean',
            'important' => 'boolean',
            'billing' => 'boolean',
            'technical' => 'boolean',
            'department' => 'nullable|string|max:255',
            'location_id' => 'nullable|integer|exists:locations,id',
            'vendor_id' => 'nullable|integer|exists:vendors,id',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    /**
     * Get validation rules for contact update.
     */
    public static function getUpdateValidationRules(int $contactId): array
    {
        return self::getValidationRules();
    }

    /**
     * Get available authentication methods.
     */
    public static function getAuthMethods(): array
    {
        return [
            self::AUTH_NONE => 'None',
            self::AUTH_PASSWORD => 'Password',
            self::AUTH_PIN => 'PIN',
        ];
    }

    /**
     * Get the password for authentication (Laravel expects 'password' attribute).
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the password attribute (accessor for Laravel auth).
     */
    public function getPasswordAttribute()
    {
        return $this->password_hash;
    }

    /**
     * Set the password attribute (mutator for Laravel auth).
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = Hash::make($value);
    }

    /**
     * Find contact by email for authentication.
     */
    public static function findByEmail($email)
    {
        return static::where('email', $email)
            ->where('has_portal_access', true)
            ->with('client')
            ->first();
    }

    /**
     * Check if the contact account is locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Increment failed login attempts.
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_count');

        // Lock account after 5 failed attempts
        if ($this->failed_login_count >= 5) {
            $this->update(['locked_until' => now()->addMinutes(30)]);
        }
    }

    /**
     * Reset failed login attempts on successful login.
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_count' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Update login information after successful authentication.
     */
    public function updateLoginInfo($ipAddress = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
            'login_count' => ($this->login_count ?? 0) + 1,
        ]);

        $this->resetFailedLoginAttempts();
    }

    /**
     * Check if the contact can access the client portal.
     */
    public function canAccessPortal(): bool
    {
        return $this->has_portal_access &&
               ! $this->isLocked() &&
               $this->client &&
               ($this->client->status === 'active' || ! $this->client->trashed());
    }

    /**
     * Check if the invitation is valid.
     */
    public function hasValidInvitation(): bool
    {
        return $this->invitation_status === 'sent' &&
               $this->invitation_token &&
               $this->invitation_expires_at &&
               $this->invitation_expires_at->isFuture();
    }

    /**
     * Check if the invitation has expired.
     */
    public function isInvitationExpired(): bool
    {
        return $this->invitation_expires_at &&
               $this->invitation_expires_at->isPast();
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitationSentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitation_sent_by');
    }

    /**
     * Mark invitation as accepted.
     */
    public function acceptInvitation(): void
    {
        $this->update([
            'invitation_accepted_at' => now(),
            'invitation_status' => 'accepted',
            'invitation_token' => null, // Clear token after use
        ]);
    }
}
