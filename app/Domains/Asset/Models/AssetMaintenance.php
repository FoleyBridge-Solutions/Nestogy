<?php

namespace App\Domains\Asset\Models;

use App\Models\Asset;
use App\Models\User;
use App\Models\Vendor;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetMaintenance extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'asset_maintenance';

    protected $fillable = [
        'company_id',
        'asset_id',
        'maintenance_type',
        'scheduled_date',
        'completed_date',
        'technician_id',
        'vendor_id',
        'cost',
        'description',
        'next_maintenance_date',
        'status',
        'notes',
        'parts_used',
        'hours_spent',
        'priority',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'asset_id' => 'integer',
        'technician_id' => 'integer',
        'vendor_id' => 'integer',
        'cost' => 'decimal:2',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'next_maintenance_date' => 'date',
        'hours_spent' => 'decimal:2',
        'parts_used' => 'array',
    ];

    protected $dates = [
        'scheduled_date',
        'completed_date',
        'next_maintenance_date',
        'deleted_at',
    ];

    // Maintenance types
    const MAINTENANCE_TYPES = [
        'preventive' => 'Preventive',
        'corrective' => 'Corrective',
        'emergency' => 'Emergency',
        'upgrade' => 'Upgrade',
        'inspection' => 'Inspection',
    ];

    // Maintenance statuses
    const STATUSES = [
        'scheduled' => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'overdue' => 'Overdue',
    ];

    // Priority levels
    const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    /**
     * Get the asset that this maintenance belongs to.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the technician assigned to this maintenance.
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the vendor for this maintenance.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Scope a query to only include maintenance of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('maintenance_type', $type);
    }

    /**
     * Scope a query to only include maintenance with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include scheduled maintenance.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include completed maintenance.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include overdue maintenance.
     */
    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled');
    }

    /**
     * Scope a query to only include maintenance due soon.
     */
    public function scopeDueSoon($query, $days = 7)
    {
        return $query->whereBetween('scheduled_date', [now(), now()->addDays($days)])
            ->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include maintenance for a specific asset.
     */
    public function scopeForAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    /**
     * Scope a query to only include maintenance assigned to a specific technician.
     */
    public function scopeForTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    /**
     * Scope a query to only include maintenance with a specific priority.
     */
    public function scopeWithPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Check if the maintenance is overdue.
     */
    public function isOverdue()
    {
        return $this->scheduled_date < now() &&
               ! in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Check if the maintenance is due soon.
     */
    public function isDueSoon($days = 7)
    {
        return $this->scheduled_date <= now()->addDays($days) &&
               $this->scheduled_date >= now() &&
               $this->status === 'scheduled';
    }

    /**
     * Check if the maintenance is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Get the maintenance type label.
     */
    public function getMaintenanceTypeLabelAttribute()
    {
        return self::MAINTENANCE_TYPES[$this->maintenance_type] ?? ucfirst($this->maintenance_type);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabelAttribute()
    {
        return self::PRIORITIES[$this->priority ?? 'normal'] ?? ucfirst($this->priority ?? 'normal');
    }

    /**
     * Get the status color class.
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'scheduled' => 'info',
            'in_progress' => 'warning',
            'completed' => 'success',
            'cancelled' => 'secondary',
            'overdue' => 'danger',
        ];

        $status = $this->isOverdue() ? 'overdue' : $this->status;

        return $colors[$status] ?? 'secondary';
    }

    /**
     * Get the priority color class.
     */
    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => 'secondary',
            'normal' => 'info',
            'high' => 'warning',
            'critical' => 'danger',
        ];

        return $colors[$this->priority ?? 'normal'] ?? 'info';
    }

    /**
     * Get the formatted cost.
     */
    public function getFormattedCostAttribute()
    {
        return $this->cost ? '$'.number_format($this->cost, 2) : null;
    }

    /**
     * Get the duration between scheduled and completed dates.
     */
    public function getDurationAttribute()
    {
        if (! $this->completed_date || ! $this->scheduled_date) {
            return null;
        }

        return $this->scheduled_date->diffInDays($this->completed_date);
    }

    /**
     * Mark maintenance as completed.
     */
    public function markCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
        ]);
    }

    /**
     * Mark maintenance as in progress.
     */
    public function markInProgress()
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    /**
     * Cancel maintenance.
     */
    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Schedule next maintenance based on this record.
     */
    public function scheduleNext($date, $description = null)
    {
        return self::create([
            'company_id' => $this->company_id,
            'asset_id' => $this->asset_id,
            'maintenance_type' => $this->maintenance_type,
            'scheduled_date' => $date,
            'technician_id' => $this->technician_id,
            'vendor_id' => $this->vendor_id,
            'description' => $description ?? $this->description,
            'status' => 'scheduled',
            'priority' => $this->priority,
        ]);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-update status to overdue when scheduled date passes
        static::saving(function ($maintenance) {
            if ($maintenance->isOverdue() && $maintenance->status === 'scheduled') {
                $maintenance->status = 'overdue';
            }
        });
    }

    /**
     * Get available maintenance types.
     */
    public static function getMaintenanceTypes()
    {
        return self::MAINTENANCE_TYPES;
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses()
    {
        return self::STATUSES;
    }

    /**
     * Get available priorities.
     */
    public static function getPriorities()
    {
        return self::PRIORITIES;
    }
}
