<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'website',
        'notes',
        'status',
        'hourly_rate',
        'billing_contact',
        'technical_contact',
        'custom_fields',
        'contract_start_date',
        'contract_end_date',
        'lead',
        'type',
        'accessed_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'hourly_rate' => 'decimal:2',
        'custom_fields' => 'array',
        'contract_start_date' => 'datetime',
        'contract_end_date' => 'datetime',
        'lead' => 'boolean',
        'accessed_at' => 'datetime',
    ];

    protected $dates = [
        'contract_start_date',
        'contract_end_date',
        'accessed_at',
        'deleted_at',
    ];

    /**
     * Get the client's contacts.
     */
    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    /**
     * Get the client's primary contact.
     */
    public function primaryContact()
    {
        return $this->hasOne(ClientContact::class)->where('primary', true);
    }

    /**
     * Get the client's billing contact.
     */
    public function billingContact()
    {
        return $this->hasOne(ClientContact::class)->where('billing', true);
    }

    /**
     * Get the client's technical contact.
     */
    public function technicalContact()
    {
        return $this->hasOne(ClientContact::class)->where('technical', true);
    }

    /**
     * Get the client's addresses.
     */
    public function addresses()
    {
        return $this->hasMany(ClientAddress::class);
    }

    /**
     * Get the client's primary address.
     */
    public function primaryAddress()
    {
        return $this->hasOne(ClientAddress::class)->where('primary', true);
    }

    /**
     * Scope a query to only include active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include leads.
     */
    public function scopeLeads($query)
    {
        return $query->where('lead', true);
    }

    /**
     * Scope a query to exclude leads.
     */
    public function scopeClients($query)
    {
        return $query->where('lead', false);
    }

    /**
     * Get the client's full address as a string.
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the client's display name.
     */
    public function getDisplayNameAttribute()
    {
        return $this->company_name ?: $this->name;
    }
}