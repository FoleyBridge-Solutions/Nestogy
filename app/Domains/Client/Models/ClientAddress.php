<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientAddress extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'client_id',
        'contact_id',
        'name',
        'description',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'country',
        'phone',
        'hours',
        'photo',
        'primary',
        'notes',
        'accessed_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'contact_id' => 'integer',
        'primary' => 'boolean',
        'accessed_at' => 'datetime',
    ];

    protected $dates = [
        'accessed_at',
        'deleted_at',
    ];

    /**
     * Get the client that owns the address.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the contact associated with this address.
     */
    public function contact()
    {
        return $this->belongsTo(ClientContact::class, 'contact_id');
    }

    /**
     * Scope a query to only include primary addresses.
     */
    public function scopePrimary($query)
    {
        return $query->where('primary', true);
    }

    /**
     * Scope a query to filter by state.
     */
    public function scopeInState($query, $state)
    {
        return $query->where('state', $state);
    }

    /**
     * Scope a query to filter by country.
     */
    public function scopeInCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Get the full address as a formatted string.
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->zip_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the address formatted for display.
     */
    public function getFormattedAddressAttribute()
    {
        $lines = [];
        
        if ($this->address_line_1) {
            $lines[] = $this->address_line_1;
        }
        
        if ($this->address_line_2) {
            $lines[] = $this->address_line_2;
        }
        
        $cityStateZip = array_filter([
            $this->city,
            $this->state,
            $this->zip_code,
        ]);
        
        if (!empty($cityStateZip)) {
            $lines[] = implode(', ', $cityStateZip);
        }
        
        if ($this->country && $this->country !== 'US') {
            $lines[] = $this->country;
        }
        
        return implode("\n", $lines);
    }

    /**
     * Get the address for Google Maps or similar services.
     */
    public function getMapAddressAttribute()
    {
        return $this->full_address;
    }

    /**
     * Check if this is a US address.
     */
    public function isUSAddress()
    {
        return $this->country === 'US';
    }

    /**
     * Get the display name for this address.
     */
    public function getDisplayNameAttribute()
    {
        return $this->name ?: 'Address';
    }
}