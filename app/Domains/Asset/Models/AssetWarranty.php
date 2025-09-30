<?php

namespace App\Domains\Asset\Models;

use App\Models\Asset;
use App\Models\Vendor;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetWarranty extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'asset_warranties';

    protected $fillable = [
        'company_id',
        'asset_id',
        'warranty_start_date',
        'warranty_end_date',
        'warranty_provider',
        'warranty_type',
        'terms',
        'coverage_details',
        'vendor_id',
        'cost',
        'renewal_cost',
        'auto_renewal',
        'contact_email',
        'contact_phone',
        'reference_number',
        'notes',
        'status',
        'claim_count',
        'last_claim_date',
        'renewal_reminder_sent',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'asset_id' => 'integer',
        'vendor_id' => 'integer',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'last_claim_date' => 'date',
        'cost' => 'decimal:2',
        'renewal_cost' => 'decimal:2',
        'auto_renewal' => 'boolean',
        'claim_count' => 'integer',
        'renewal_reminder_sent' => 'boolean',
    ];

    protected $dates = [
        'warranty_start_date',
        'warranty_end_date',
        'last_claim_date',
        'deleted_at',
    ];

    // Warranty types
    const WARRANTY_TYPES = [
        'manufacturer' => 'Manufacturer Warranty',
        'extended' => 'Extended Warranty',
        'third_party' => 'Third Party Warranty',
        'service_contract' => 'Service Contract',
    ];

    // Warranty statuses
    const STATUSES = [
        'active' => 'Active',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
        'pending' => 'Pending',
        'suspended' => 'Suspended',
    ];

    /**
     * Get the asset that this warranty belongs to.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the vendor for this warranty.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Scope a query to only include warranties of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('warranty_type', $type);
    }

    /**
     * Scope a query to only include warranties with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include active warranties.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('warranty_end_date', '>', now());
    }

    /**
     * Scope a query to only include expired warranties.
     */
    public function scopeExpired($query)
    {
        return $query->where('warranty_end_date', '<', now())
            ->orWhere('status', 'expired');
    }

    /**
     * Scope a query to only include warranties expiring soon.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
            ->whereBetween('warranty_end_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope a query to only include warranties for a specific asset.
     */
    public function scopeForAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    /**
     * Scope a query to only include warranties from a specific provider.
     */
    public function scopeFromProvider($query, $provider)
    {
        return $query->where('warranty_provider', 'like', "%{$provider}%");
    }

    /**
     * Scope a query to only include warranties with auto-renewal enabled.
     */
    public function scopeAutoRenewal($query)
    {
        return $query->where('auto_renewal', true);
    }

    /**
     * Check if the warranty is expired.
     */
    public function isExpired()
    {
        return $this->warranty_end_date < now() || $this->status === 'expired';
    }

    /**
     * Check if the warranty is expiring soon.
     */
    public function isExpiringSoon($days = 30)
    {
        return $this->warranty_end_date <= now()->addDays($days) &&
               $this->warranty_end_date > now() &&
               $this->status === 'active';
    }

    /**
     * Check if the warranty is active.
     */
    public function isActive()
    {
        return $this->status === 'active' && $this->warranty_end_date > now();
    }

    /**
     * Get the warranty type label.
     */
    public function getWarrantyTypeLabelAttribute()
    {
        return self::WARRANTY_TYPES[$this->warranty_type] ?? ucfirst(str_replace('_', ' ', $this->warranty_type));
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the status color class.
     */
    public function getStatusColorAttribute()
    {
        if ($this->isExpired()) {
            return 'danger';
        }

        if ($this->isExpiringSoon()) {
            return 'warning';
        }

        $colors = [
            'active' => 'success',
            'expired' => 'danger',
            'cancelled' => 'secondary',
            'pending' => 'info',
            'suspended' => 'warning',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Get the formatted cost.
     */
    public function getFormattedCostAttribute()
    {
        return $this->cost ? '$'.number_format($this->cost, 2) : null;
    }

    /**
     * Get the formatted renewal cost.
     */
    public function getFormattedRenewalCostAttribute()
    {
        return $this->renewal_cost ? '$'.number_format($this->renewal_cost, 2) : null;
    }

    /**
     * Get the warranty duration in days.
     */
    public function getDurationInDaysAttribute()
    {
        if (! $this->warranty_start_date || ! $this->warranty_end_date) {
            return null;
        }

        return $this->warranty_start_date->diffInDays($this->warranty_end_date);
    }

    /**
     * Get the warranty duration in months.
     */
    public function getDurationInMonthsAttribute()
    {
        if (! $this->warranty_start_date || ! $this->warranty_end_date) {
            return null;
        }

        return $this->warranty_start_date->diffInMonths($this->warranty_end_date);
    }

    /**
     * Get the remaining warranty days.
     */
    public function getRemainingDaysAttribute()
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->warranty_end_date);
    }

    /**
     * Get the warranty coverage percentage based on time elapsed.
     */
    public function getCoverageUsedPercentageAttribute()
    {
        if (! $this->warranty_start_date || ! $this->warranty_end_date) {
            return 0;
        }

        $totalDays = $this->warranty_start_date->diffInDays($this->warranty_end_date);
        $usedDays = $this->warranty_start_date->diffInDays(now());

        if ($totalDays <= 0) {
            return 100;
        }

        return min(100, round(($usedDays / $totalDays) * 100, 2));
    }

    /**
     * Get days until expiry (negative if expired).
     */
    public function getDaysUntilExpiryAttribute()
    {
        return now()->diffInDays($this->warranty_end_date, false);
    }

    /**
     * Check if warranty needs renewal reminder.
     */
    public function needsRenewalReminder($reminderDays = 30)
    {
        return $this->isExpiringSoon($reminderDays) &&
               ! $this->renewal_reminder_sent &&
               $this->status === 'active';
    }

    /**
     * Mark warranty as expired.
     */
    public function markExpired()
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Mark warranty as cancelled.
     */
    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Renew warranty.
     */
    public function renew($newEndDate, $renewalCost = null)
    {
        $this->update([
            'warranty_end_date' => $newEndDate,
            'cost' => $renewalCost ?? $this->renewal_cost ?? $this->cost,
            'status' => 'active',
            'renewal_reminder_sent' => false,
        ]);
    }

    /**
     * Record a warranty claim.
     */
    public function recordClaim()
    {
        $this->update([
            'claim_count' => $this->claim_count + 1,
            'last_claim_date' => now(),
        ]);
    }

    /**
     * Mark renewal reminder as sent.
     */
    public function markReminderSent()
    {
        $this->update(['renewal_reminder_sent' => true]);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-update status to expired when end date passes
        static::saving(function ($warranty) {
            if ($warranty->warranty_end_date < now() && $warranty->status === 'active') {
                $warranty->status = 'expired';
            }
        });
    }

    /**
     * Get available warranty types.
     */
    public static function getWarrantyTypes()
    {
        return self::WARRANTY_TYPES;
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses()
    {
        return self::STATUSES;
    }

    /**
     * Get warranties expiring within specified days.
     */
    public static function getExpiringWarranties($days = 30)
    {
        return self::expiringSoon($days)
            ->with(['asset.client'])
            ->orderBy('warranty_end_date')
            ->get();
    }

    /**
     * Get expired warranties.
     */
    public static function getExpiredWarranties()
    {
        return self::expired()
            ->with(['asset.client'])
            ->orderBy('warranty_end_date', 'desc')
            ->get();
    }
}
