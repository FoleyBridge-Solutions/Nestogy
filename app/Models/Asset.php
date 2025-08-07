<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;
use App\Traits\HasArchive;

class Asset extends Model
{
    use HasFactory, BelongsToCompany, HasArchive;

    protected $fillable = [
        'company_id',
        'client_id',
        'type',
        'name',
        'description',
        'make',
        'model',
        'serial',
        'os',
        'ip',
        'nat_ip',
        'mac',
        'uri',
        'uri_2',
        'status',
        'purchase_date',
        'warranty_expire',
        'install_date',
        'notes',
        'vendor_id',
        'location_id',
        'contact_id',
        'network_id',
        'rmm_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expire' => 'date',
        'install_date' => 'date',
        'archived_at' => 'datetime',
        'accessed_at' => 'datetime',
    ];

    // Asset types
    const TYPES = [
        'Server',
        'Desktop',
        'Laptop',
        'Tablet',
        'Phone',
        'Printer',
        'Switch',
        'Router',
        'Firewall',
        'Access Point',
        'Other'
    ];

    // Asset statuses
    const STATUSES = [
        'Ready To Deploy',
        'Deployed',
        'Archived',
        'Broken - Pending Repair',
        'Broken - Not Repairable',
        'Out for Repair',
        'Lost/Stolen',
        'Unknown'
    ];

    /**
     * Get the client that owns the asset.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the vendor for the asset.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the location for the asset.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the contact for the asset.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the network for the asset.
     */
    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * Get the tickets for the asset.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the logins for the asset.
     */
    public function logins()
    {
        return $this->hasMany(Login::class);
    }

    /**
     * Get the documents for the asset.
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Get the files for the asset.
     */
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Scope a query to only include assets of a given type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include assets with a given status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include assets at a given location.
     */
    public function scopeAtLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope a query to only include assets assigned to a given contact.
     */
    public function scopeAssignedTo($query, $contactId)
    {
        return $query->where('contact_id', $contactId);
    }

    /**
     * Scope a query to only include assets with warranties expiring soon.
     */
    public function scopeWarrantyExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('warranty_expire')
            ->whereBetween('warranty_expire', [now(), now()->addDays($days)]);
    }

    /**
     * Get the asset's full name with type.
     */
    public function getFullNameAttribute()
    {
        return $this->type . ' - ' . $this->name;
    }

    /**
     * Check if warranty is expired.
     */
    public function getIsWarrantyExpiredAttribute()
    {
        return $this->warranty_expire && $this->warranty_expire->isPast();
    }

    /**
     * Check if warranty is expiring soon.
     */
    public function getIsWarrantyExpiringSoonAttribute()
    {
        return $this->warranty_expire && 
               $this->warranty_expire->isFuture() && 
               $this->warranty_expire->diffInDays(now()) <= 30;
    }

    /**
     * Get the asset's age in years.
     */
    public function getAgeInYearsAttribute()
    {
        if (!$this->purchase_date) {
            return null;
        }
        
        return $this->purchase_date->diffInYears(now());
    }

    /**
     * Generate QR code data for the asset.
     */
    public function getQrCodeDataAttribute()
    {
        return route('assets.show', $this->id);
    }

    /**
     * Get icon for asset type.
     */
    public function getIconAttribute()
    {
        $icons = [
            'Server' => 'fa-server',
            'Desktop' => 'fa-desktop',
            'Laptop' => 'fa-laptop',
            'Tablet' => 'fa-tablet',
            'Phone' => 'fa-mobile',
            'Printer' => 'fa-print',
            'Switch' => 'fa-network-wired',
            'Router' => 'fa-route',
            'Firewall' => 'fa-shield-alt',
            'Access Point' => 'fa-wifi',
            'Other' => 'fa-cube'
        ];

        return $icons[$this->type] ?? 'fa-cube';
    }

    /**
     * Get status color class.
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'Ready To Deploy' => 'success',
            'Deployed' => 'primary',
            'Archived' => 'secondary',
            'Broken - Pending Repair' => 'warning',
            'Broken - Not Repairable' => 'danger',
            'Out for Repair' => 'warning',
            'Lost/Stolen' => 'danger',
            'Unknown' => 'secondary'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Touch accessed_at timestamp.
     */
    public function touchAccessed()
    {
        $this->accessed_at = now();
        $this->save();
    }
}