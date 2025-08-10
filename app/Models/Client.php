<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
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
        'company_id' => 'integer',
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
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the client's primary contact.
     */
    public function primaryContact()
    {
        return $this->hasOne(Contact::class)->where('primary', true);
    }

    /**
     * Get the client's billing contact.
     */
    public function billingContact()
    {
        return $this->hasOne(Contact::class)->where('billing', true);
    }

    /**
     * Get the client's technical contact.
     */
    public function technicalContact()
    {
        return $this->hasOne(Contact::class)->where('technical', true);
    }

    /**
     * Get the client's locations/addresses.
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the client's primary location.
     */
    public function primaryLocation()
    {
        return $this->hasOne(Location::class)->where('primary', true);
    }

    /**
     * Get the tags associated with the client.
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'client_tags');
    }

    /**
     * Get the client's assets.
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the client's tickets.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the client's invoices.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the client's projects.
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the client's recurring invoices.
     */
    public function recurringInvoices()
    {
        return $this->hasMany(Recurring::class);
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

    /**
     * Get the client's balance.
     */
    public function getBalance()
    {
        $totalInvoiced = $this->invoices()->sum('total');
        $totalPaid = $this->invoices()->sum('paid');
        return $totalInvoiced - $totalPaid;
    }

    /**
     * Get the client's monthly recurring revenue.
     */
    public function getMonthlyRecurring()
    {
        return $this->recurringInvoices()
            ->where('status', true)
            ->sum('amount');
    }

    /**
     * Check if this client is a lead.
     */
    public function isLead()
    {
        return (bool) $this->lead;
    }

    /**
     * Convert lead to customer.
     */
    public function convertToCustomer()
    {
        $this->lead = false;
        $this->save();
        return $this;
    }

    /**
     * Sync tags for the client.
     */
    public function syncTags($tagIds)
    {
        return $this->tags()->sync($tagIds);
    }
}