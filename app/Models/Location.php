<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Location Model
 * 
 * Represents physical locations for clients.
 * Each location can have assets, contacts, and networks associated with it.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $country
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $phone
 * @property string|null $hours
 * @property string|null $photo
 * @property bool $primary
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $accessed_at
 * @property int|null $contact_id
 * @property int $client_id
 */
class Location extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'locations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'country',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'hours',
        'photo',
        'primary',
        'notes',
        'contact_id',
        'client_id',
        'company_id',
        'accessed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'accessed_at' => 'datetime',
        'contact_id' => 'integer',
        'client_id' => 'integer',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Get the client that owns the location.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the primary contact for this location.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get all contacts at this location.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the assets at this location.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the networks at this location.
     */
    public function networks(): HasMany
    {
        return $this->hasMany(Network::class);
    }

    /**
     * Get tickets related to this location.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the full address.
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the formatted address (multi-line for display).
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city && $this->state && $this->zip ? 
                $this->city . ', ' . $this->state . ' ' . $this->zip : null,
            $this->country && $this->country !== 'US' ? $this->country : null,
        ]);

        return implode("\n", $parts);
    }

    /**
     * Get the display name for location address.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->name;
        
        if ($this->isPrimary()) {
            $name .= ' (Primary Location)';
        }

        return $name;
    }

    /**
     * Get the photo URL.
     */
    public function getPhotoUrl(): ?string
    {
        if ($this->photo) {
            return asset('storage/locations/' . $this->photo);
        }

        return null;
    }

    /**
     * Check if location has a photo.
     */
    public function hasPhoto(): bool
    {
        return !empty($this->photo);
    }

    /**
     * Check if this is the primary location.
     */
    public function isPrimary(): bool
    {
        return $this->primary === true;
    }

    /**
     * Check if location is archived.
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Get formatted hours.
     */
    public function getFormattedHours(): string
    {
        return $this->hours ?: 'Not specified';
    }

    /**
     * Update last accessed timestamp.
     */
    public function updateAccessedAt(): void
    {
        $this->update(['accessed_at' => now()]);
    }

    /**
     * Get asset count for this location.
     */
    public function getAssetCount(): int
    {
        return $this->assets()->count();
    }

    /**
     * Get contact count for this location.
     */
    public function getContactCount(): int
    {
        return $this->contacts()->count();
    }

    /**
     * Get network count for this location.
     */
    public function getNetworkCount(): int
    {
        return $this->networks()->count();
    }

    /**
     * Scope to get only primary locations.
     */
    public function scopePrimary($query)
    {
        return $query->where('primary', true);
    }

    /**
     * Scope to get only non-primary locations.
     */
    public function scopeSecondary($query)
    {
        return $query->where('primary', false);
    }

    /**
     * Scope to search locations.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('address', 'like', '%' . $search . '%')
              ->orWhere('city', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope to get locations by client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get recently accessed locations.
     */
    public function scopeRecentlyAccessed($query, int $days = 30)
    {
        return $query->where('accessed_at', '>=', now()->subDays($days));
    }

    /**
     * Get validation rules for location creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'country' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'hours' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'primary' => 'boolean',
            'notes' => 'nullable|string',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    /**
     * Get validation rules for location update.
     */
    public static function getUpdateValidationRules(int $locationId): array
    {
        return self::getValidationRules();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one primary location per client
        static::saving(function ($location) {
            if ($location->primary) {
                // Set all other locations for this client as non-primary
                static::where('client_id', $location->client_id)
                      ->where('id', '!=', $location->id)
                      ->update(['primary' => false]);
            }
        });

        // Note: Auto-update on retrieved removed for performance
        // accessed_at updates are now handled strategically in controllers when viewing details
    }
}