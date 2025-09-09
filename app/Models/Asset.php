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
        'support_status',
        'support_level',
        'supporting_contract_id',
        'supporting_schedule_id',
        'auto_assigned_support',
        'support_assigned_at',
        'support_assigned_by',
        'support_last_evaluated_at',
        'support_evaluation_rules',
        'support_notes',
        'purchase_date',
        'warranty_expire',
        'next_maintenance_date',
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
        'next_maintenance_date' => 'date',
        'install_date' => 'date',
        'archived_at' => 'datetime',
        'accessed_at' => 'datetime',
        'auto_assigned_support' => 'boolean',
        'support_assigned_at' => 'datetime',
        'support_last_evaluated_at' => 'datetime',
        'support_evaluation_rules' => 'array',
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
        'Storage',
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

    // Support statuses
    const SUPPORT_STATUS_SUPPORTED = 'supported';
    const SUPPORT_STATUS_UNSUPPORTED = 'unsupported';
    const SUPPORT_STATUS_PENDING_ASSIGNMENT = 'pending_assignment';
    const SUPPORT_STATUS_EXCLUDED = 'excluded';

    const SUPPORT_STATUSES = [
        self::SUPPORT_STATUS_SUPPORTED => 'Supported',
        self::SUPPORT_STATUS_UNSUPPORTED => 'Unsupported',
        self::SUPPORT_STATUS_PENDING_ASSIGNMENT => 'Pending Assignment',
        self::SUPPORT_STATUS_EXCLUDED => 'Excluded',
    ];

    // Support levels
    const SUPPORT_LEVELS = [
        'basic' => 'Basic Support',
        'standard' => 'Standard Support',
        'premium' => 'Premium Support',
        'enterprise' => 'Enterprise Support',
        'custom' => 'Custom Support',
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
     * Get the contract that provides support for this asset.
     */
    public function supportingContract()
    {
        return $this->belongsTo(Contract::class, 'supporting_contract_id');
    }

    /**
     * Get the contract schedule that defines support for this asset.
     */
    public function supportingSchedule()
    {
        return $this->belongsTo(ContractSchedule::class, 'supporting_schedule_id');
    }

    /**
     * Get the user who assigned support to this asset.
     */
    public function supportAssignedBy()
    {
        return $this->belongsTo(User::class, 'support_assigned_by');
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
     * Get the warranties for the asset.
     */
    public function warranties()
    {
        return $this->hasMany(\App\Domains\Asset\Models\AssetWarranty::class);
    }

    /**
     * Get the maintenance records for the asset.
     */
    public function maintenances()
    {
        return $this->hasMany(\App\Domains\Asset\Models\AssetMaintenance::class);
    }

    /**
     * Get the depreciation records for the asset.
     */
    public function depreciations()
    {
        return $this->hasMany(\App\Domains\Asset\Models\AssetDepreciation::class);
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

    /**
     * Get the device mappings for this asset.
     */
    public function deviceMappings()
    {
        return $this->hasMany(\App\Domains\Integration\Models\DeviceMapping::class);
    }

    /**
     * Check if asset is connected to an RMM system.
     */
    public function hasRmmConnection(): bool
    {
        return $this->deviceMappings()->active()->exists();
    }

    /**
     * Get the primary RMM connection for this asset.
     */
    public function primaryRmmConnection()
    {
        return $this->deviceMappings()->active()->first();
    }

    /**
     * Check if asset supports remote management.
     */
    public function supportsRemoteManagement(): bool
    {
        return $this->hasRmmConnection() && 
               $this->primaryRmmConnection()->integration->is_active;
    }

    // Support Status Methods

    /**
     * Check if asset is supported.
     */
    public function isSupported(): bool
    {
        return $this->support_status === self::SUPPORT_STATUS_SUPPORTED;
    }

    /**
     * Check if asset is unsupported.
     */
    public function isUnsupported(): bool
    {
        return $this->support_status === self::SUPPORT_STATUS_UNSUPPORTED;
    }

    /**
     * Check if asset is excluded from support.
     */
    public function isExcludedFromSupport(): bool
    {
        return $this->support_status === self::SUPPORT_STATUS_EXCLUDED;
    }

    /**
     * Check if asset has pending support assignment.
     */
    public function hasPendingSupportAssignment(): bool
    {
        return $this->support_status === self::SUPPORT_STATUS_PENDING_ASSIGNMENT;
    }

    /**
     * Check if asset was auto-assigned support.
     */
    public function hasAutoAssignedSupport(): bool
    {
        return $this->auto_assigned_support === true;
    }

    /**
     * Get support status badge color.
     */
    public function getSupportStatusColorAttribute(): string
    {
        $colors = [
            self::SUPPORT_STATUS_SUPPORTED => 'success',
            self::SUPPORT_STATUS_UNSUPPORTED => 'danger',
            self::SUPPORT_STATUS_PENDING_ASSIGNMENT => 'warning',
            self::SUPPORT_STATUS_EXCLUDED => 'secondary',
        ];

        return $colors[$this->support_status] ?? 'secondary';
    }

    /**
     * Get support status display name.
     */
    public function getSupportStatusDisplayAttribute(): string
    {
        return self::SUPPORT_STATUSES[$this->support_status] ?? 'Unknown';
    }

    /**
     * Get support level display name.
     */
    public function getSupportLevelDisplayAttribute(): string
    {
        if (!$this->support_level) {
            return 'None';
        }

        return self::SUPPORT_LEVELS[$this->support_level] ?? ucfirst($this->support_level);
    }

    /**
     * Check if asset needs support evaluation.
     */
    public function needsSupportEvaluation(int $daysOld = 30): bool
    {
        if (!$this->support_last_evaluated_at) {
            return true;
        }

        return $this->support_last_evaluated_at->lt(now()->subDays($daysOld));
    }

    /**
     * Get days since last support evaluation.
     */
    public function daysSinceLastSupportEvaluation(): ?int
    {
        if (!$this->support_last_evaluated_at) {
            return null;
        }

        return $this->support_last_evaluated_at->diffInDays(now());
    }

    /**
     * Scope to get supported assets.
     */
    public function scopeSupported($query)
    {
        return $query->where('support_status', self::SUPPORT_STATUS_SUPPORTED);
    }

    /**
     * Scope to get unsupported assets.
     */
    public function scopeUnsupported($query)
    {
        return $query->where('support_status', self::SUPPORT_STATUS_UNSUPPORTED);
    }

    /**
     * Scope to get excluded assets.
     */
    public function scopeExcludedFromSupport($query)
    {
        return $query->where('support_status', self::SUPPORT_STATUS_EXCLUDED);
    }

    /**
     * Scope to get assets with pending support assignment.
     */
    public function scopePendingSupportAssignment($query)
    {
        return $query->where('support_status', self::SUPPORT_STATUS_PENDING_ASSIGNMENT);
    }

    /**
     * Scope to get auto-assigned support assets.
     */
    public function scopeAutoAssignedSupport($query)
    {
        return $query->where('auto_assigned_support', true);
    }

    /**
     * Scope to get assets by support level.
     */
    public function scopeBySupportLevel($query, string $level)
    {
        return $query->where('support_level', $level);
    }

    /**
     * Scope to get assets needing support evaluation.
     */
    public function scopeNeedingSupportEvaluation($query, int $daysOld = 30)
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return $query->where(function ($q) use ($cutoffDate) {
            $q->whereNull('support_last_evaluated_at')
              ->orWhere('support_last_evaluated_at', '<', $cutoffDate);
        });
    }

    /**
     * Scope to get assets with support contract.
     */
    public function scopeWithSupportContract($query)
    {
        return $query->whereNotNull('supporting_contract_id');
    }

    /**
     * Scope to get assets with support schedule.
     */
    public function scopeWithSupportSchedule($query)
    {
        return $query->whereNotNull('supporting_schedule_id');
    }
}