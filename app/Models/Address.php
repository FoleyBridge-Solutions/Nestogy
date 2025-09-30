<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Address Model
 *
 * Represents client addresses from the client_addresses table.
 * Each address belongs to a client and company.
 *
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property string $type
 * @property string $address
 * @property string|null $address2
 * @property string $city
 * @property string $state
 * @property string $zip
 * @property string $country
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Address extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'client_addresses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'type',
        'address',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Address types
     */
    const TYPE_BILLING = 'billing';

    const TYPE_SHIPPING = 'shipping';

    const TYPE_SERVICE = 'service';

    const TYPE_OTHER = 'other';

    /**
     * Get the client that owns the address.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the formatted address as a single string.
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->address2,
            $this->city.', '.$this->state.' '.$this->zip,
            $this->country !== 'US' ? $this->country : null,
        ]);

        return implode("\n", $parts);
    }

    /**
     * Get the display name for the address (type + primary indicator).
     */
    public function getDisplayNameAttribute(): string
    {
        $name = ucfirst($this->type).' Address';

        if ($this->is_primary) {
            $name .= ' (Primary)';
        }

        return $name;
    }

    /**
     * Check if this is the primary address.
     */
    public function isPrimary(): bool
    {
        return $this->is_primary === true;
    }

    /**
     * Scope to get only primary addresses.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to get addresses by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get addresses for a specific client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Get all available address types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_BILLING => 'Billing',
            self::TYPE_SHIPPING => 'Shipping',
            self::TYPE_SERVICE => 'Service',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Get validation rules for address creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'type' => 'required|in:billing,shipping,service,other',
            'address' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|size:2',
            'is_primary' => 'boolean',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    /**
     * Get validation rules for address update.
     */
    public static function getUpdateValidationRules(int $addressId): array
    {
        return self::getValidationRules();
    }
}
