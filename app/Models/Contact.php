<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
class Contact extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

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
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
        'password_reset_token',
        'pin',
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
     * Get the contact's photo URL.
     */
    public function getPhotoUrl(): string
    {
        if ($this->photo) {
            return asset('storage/contacts/' . $this->photo);
        }
        
        if ($this->email) {
            return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=identicon&s=150';
        }

        return asset('images/default-avatar.png');
    }

    /**
     * Get the contact's full name with title.
     */
    public function getFullNameWithTitle(): string
    {
        if ($this->title) {
            return $this->name . ' (' . $this->title . ')';
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
        if (!$this->phone) {
            return null;
        }

        $phone = $this->phone;
        if ($this->extension) {
            $phone .= ' ext. ' . $this->extension;
        }

        return $phone;
    }

    /**
     * Check if contact has a photo.
     */
    public function hasPhoto(): bool
    {
        return !empty($this->photo);
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
        return !is_null($this->archived_at);
    }

    /**
     * Check if contact can authenticate.
     */
    public function canAuthenticate(): bool
    {
        return !empty($this->auth_method) && $this->auth_method !== self::AUTH_NONE;
    }

    /**
     * Check if password reset token is valid.
     */
    public function hasValidResetToken(): bool
    {
        return !empty($this->password_reset_token) && 
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
        
        if ($this->primary) $roles[] = 'Primary';
        if ($this->billing) $roles[] = 'Billing';
        if ($this->technical) $roles[] = 'Technical';
        if ($this->important) $roles[] = 'Important';

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
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('email', 'like', '%' . $search . '%')
              ->orWhere('phone', 'like', '%' . $search . '%')
              ->orWhere('title', 'like', '%' . $search . '%');
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
}