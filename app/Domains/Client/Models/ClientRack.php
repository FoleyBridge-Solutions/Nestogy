<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientRack extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'description',
        'location',
        'rack_number',
        'height_units',
        'width_inches',
        'depth_inches',
        'max_weight_lbs',
        'power_capacity_watts',
        'power_used_watts',
        'cooling_requirements',
        'network_connections',
        'status',
        'temperature_celsius',
        'humidity_percent',
        'manufacturer',
        'model',
        'serial_number',
        'purchase_date',
        'warranty_expiry',
        'maintenance_schedule',
        'notes',
        'custom_fields',
        'accessed_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'rack_number' => 'integer',
        'height_units' => 'integer',
        'width_inches' => 'decimal:2',
        'depth_inches' => 'decimal:2',
        'max_weight_lbs' => 'decimal:2',
        'power_capacity_watts' => 'integer',
        'power_used_watts' => 'integer',
        'temperature_celsius' => 'decimal:1',
        'humidity_percent' => 'decimal:1',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'custom_fields' => 'array',
        'accessed_at' => 'datetime',
    ];

    protected $dates = [
        'purchase_date',
        'warranty_expiry',
        'accessed_at',
        'deleted_at',
    ];

    /**
     * Get the client that owns the rack.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to only include active racks.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive racks.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to only include maintenance racks.
     */
    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    /**
     * Scope a query to filter by location.
     */
    public function scopeLocation($query, $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    /**
     * Check if the rack has available power capacity.
     */
    public function hasAvailablePower($requiredWatts = 0)
    {
        $availablePower = $this->power_capacity_watts - $this->power_used_watts;

        return $availablePower >= $requiredWatts;
    }

    /**
     * Get available power capacity in watts.
     */
    public function getAvailablePowerAttribute()
    {
        return $this->power_capacity_watts - $this->power_used_watts;
    }

    /**
     * Get power utilization percentage.
     */
    public function getPowerUtilizationAttribute()
    {
        if (! $this->power_capacity_watts) {
            return 0;
        }

        return round(($this->power_used_watts / $this->power_capacity_watts) * 100, 1);
    }

    /**
     * Get the rack's status color for display.
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'active' => 'green',
            'inactive' => 'gray',
            'maintenance' => 'yellow',
            'decommissioned' => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get the rack's environmental status.
     */
    public function getEnvironmentalStatusAttribute()
    {
        $status = 'normal';

        // Check temperature (typical server room: 18-24°C)
        if ($this->temperature_celsius) {
            if ($this->temperature_celsius < 18 || $this->temperature_celsius > 24) {
                $status = 'warning';
            }
            if ($this->temperature_celsius < 15 || $this->temperature_celsius > 27) {
                $status = 'critical';
            }
        }

        // Check humidity (typical server room: 40-60%)
        if ($this->humidity_percent) {
            if ($this->humidity_percent < 40 || $this->humidity_percent > 60) {
                $status = 'warning';
            }
            if ($this->humidity_percent < 30 || $this->humidity_percent > 70) {
                $status = 'critical';
            }
        }

        return $status;
    }

    /**
     * Check if warranty is expired or expiring soon.
     */
    public function isWarrantyExpiring($days = 30)
    {
        if (! $this->warranty_expiry) {
            return false;
        }

        return $this->warranty_expiry->isPast() ||
               $this->warranty_expiry->diffInDays(now()) <= $days;
    }

    /**
     * Get rack capacity information.
     */
    public function getCapacityInfoAttribute()
    {
        return [
            'height_units' => $this->height_units,
            'power_capacity' => $this->power_capacity_watts,
            'power_used' => $this->power_used_watts,
            'power_available' => $this->available_power,
            'power_utilization' => $this->power_utilization,
        ];
    }

    /**
     * Get available rack statuses.
     */
    public static function getStatuses()
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'maintenance' => 'Under Maintenance',
            'decommissioned' => 'Decommissioned',
        ];
    }

    /**
     * Get common rack heights in units.
     */
    public static function getCommonHeights()
    {
        return [
            12 => '12U',
            18 => '18U',
            24 => '24U',
            42 => '42U',
            45 => '45U',
            48 => '48U',
        ];
    }

    /**
     * Get cooling requirement options.
     */
    public static function getCoolingRequirements()
    {
        return [
            'standard' => 'Standard Air Cooling',
            'precision' => 'Precision Air Cooling',
            'liquid' => 'Liquid Cooling',
            'immersion' => 'Immersion Cooling',
        ];
    }
}
