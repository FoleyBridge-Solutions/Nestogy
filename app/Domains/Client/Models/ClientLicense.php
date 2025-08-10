<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientLicense extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'name',
        'description',
        'license_type',
        'license_key',
        'vendor',
        'version',
        'seats',
        'purchase_date',
        'renewal_date',
        'expiry_date',
        'purchase_cost',
        'renewal_cost',
        'is_active',
        'auto_renewal',
        'support_level',
        'license_terms',
        'notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'client_id' => 'integer',
        'seats' => 'integer',
        'purchase_date' => 'date',
        'renewal_date' => 'date',
        'expiry_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'renewal_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'auto_renewal' => 'boolean',
    ];

    protected $dates = [
        'purchase_date',
        'renewal_date',
        'expiry_date',
        'deleted_at',
    ];

    /**
     * Get the client that owns the license.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to only include licenses of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('license_type', $type);
    }

    /**
     * Scope a query to only include active licenses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive licenses.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include non-expired licenses.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>', now());
        });
    }

    /**
     * Scope a query to only include expired licenses.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now());
    }

    /**
     * Scope a query to only include licenses expiring soon (within 30 days).
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Check if the license is expired.
     */
    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the license is expiring soon (within 30 days).
     */
    public function isExpiringSoon($days = 30)
    {
        return $this->expiry_date && 
               $this->expiry_date->isFuture() && 
               $this->expiry_date->diffInDays(now()) <= $days;
    }

    /**
     * Get the license status.
     */
    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if ($this->isExpired()) {
            return 'expired';
        }
        
        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }
        
        return 'active';
    }

    /**
     * Get the license status color for UI.
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'active':
                return 'success';
            case 'expiring_soon':
                return 'warning';
            case 'expired':
                return 'danger';
            case 'inactive':
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    /**
     * Get the license status label for UI.
     */
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'active':
                return 'Active';
            case 'expiring_soon':
                return 'Expiring Soon';
            case 'expired':
                return 'Expired';
            case 'inactive':
                return 'Inactive';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get the days until expiry.
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }
        
        return $this->expiry_date->diffInDays(now(), false);
    }

    /**
     * Get available license types.
     */
    public static function getLicenseTypes()
    {
        return [
            'software' => 'Software License',
            'hardware' => 'Hardware License',
            'service' => 'Service License',
            'cloud' => 'Cloud Service',
            'subscription' => 'Subscription',
            'perpetual' => 'Perpetual License',
            'oem' => 'OEM License',
            'volume' => 'Volume License',
            'concurrent' => 'Concurrent License',
            'named_user' => 'Named User License',
            'other' => 'Other',
        ];
    }

    /**
     * Get available support levels.
     */
    public static function getSupportLevels()
    {
        return [
            'none' => 'No Support',
            'basic' => 'Basic Support',
            'standard' => 'Standard Support',
            'premium' => 'Premium Support',
            'enterprise' => 'Enterprise Support',
            'custom' => 'Custom Support',
        ];
    }
}