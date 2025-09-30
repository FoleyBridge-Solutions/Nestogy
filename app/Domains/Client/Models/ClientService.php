<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientService extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'description',
        'service_type',
        'category',
        'status',
        'start_date',
        'end_date',
        'renewal_date',
        'billing_cycle',
        'monthly_cost',
        'setup_cost',
        'total_contract_value',
        'currency',
        'auto_renewal',
        'contract_terms',
        'sla_terms',
        'service_level',
        'priority_level',
        'assigned_technician',
        'backup_technician',
        'escalation_contact',
        'service_hours',
        'response_time',
        'resolution_time',
        'availability_target',
        'performance_metrics',
        'monitoring_enabled',
        'backup_schedule',
        'maintenance_schedule',
        'last_review_date',
        'next_review_date',
        'client_satisfaction',
        'notes',
        'tags',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'assigned_technician' => 'integer',
        'backup_technician' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_date' => 'date',
        'monthly_cost' => 'decimal:2',
        'setup_cost' => 'decimal:2',
        'total_contract_value' => 'decimal:2',
        'auto_renewal' => 'boolean',
        'monitoring_enabled' => 'boolean',
        'last_review_date' => 'date',
        'next_review_date' => 'date',
        'client_satisfaction' => 'integer',
        'tags' => 'array',
        'service_hours' => 'array',
        'performance_metrics' => 'array',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'renewal_date',
        'last_review_date',
        'next_review_date',
        'deleted_at',
    ];

    /**
     * Get the client that owns the service.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the assigned technician.
     */
    public function technician()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_technician');
    }

    /**
     * Get the backup technician.
     */
    public function backupTechnician()
    {
        return $this->belongsTo(\App\Models\User::class, 'backup_technician');
    }

    /**
     * Scope a query to only include services of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    /**
     * Scope a query to only include services of a specific category.
     */
    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include active services.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include services with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include services ending soon.
     */
    public function scopeEndingSoon($query, $days = 30)
    {
        return $query->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope a query to only include services due for renewal.
     */
    public function scopeDueForRenewal($query, $days = 30)
    {
        return $query->whereNotNull('renewal_date')
            ->whereBetween('renewal_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope a query to only include services needing review.
     */
    public function scopeNeedingReview($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('next_review_date')
                ->orWhere('next_review_date', '<=', now());
        });
    }

    /**
     * Scope a query to only include monitored services.
     */
    public function scopeMonitored($query)
    {
        return $query->where('monitoring_enabled', true);
    }

    /**
     * Check if the service is ending soon.
     */
    public function isEndingSoon($days = 30)
    {
        return $this->end_date &&
               $this->end_date->isFuture() &&
               $this->end_date->diffInDays(now()) <= $days;
    }

    /**
     * Check if the service is due for renewal.
     */
    public function isDueForRenewal($days = 30)
    {
        return $this->renewal_date &&
               $this->renewal_date->isFuture() &&
               $this->renewal_date->diffInDays(now()) <= $days;
    }

    /**
     * Check if the service needs review.
     */
    public function needsReview()
    {
        return ! $this->next_review_date || $this->next_review_date->isPast();
    }

    /**
     * Get the service status color for UI.
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'active':
                return 'success';
            case 'pending':
                return 'warning';
            case 'suspended':
                return 'danger';
            case 'cancelled':
                return 'secondary';
            case 'completed':
                return 'info';
            default:
                return 'secondary';
        }
    }

    /**
     * Get the service status label for UI.
     */
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'active':
                return 'Active';
            case 'pending':
                return 'Pending';
            case 'suspended':
                return 'Suspended';
            case 'cancelled':
                return 'Cancelled';
            case 'completed':
                return 'Completed';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get the annual revenue from this service.
     */
    public function getAnnualRevenueAttribute()
    {
        if (! $this->monthly_cost) {
            return 0;
        }

        $multiplier = match ($this->billing_cycle) {
            'weekly' => 52,
            'monthly' => 12,
            'quarterly' => 4,
            'semi-annually' => 2,
            'annually' => 1,
            default => 12
        };

        return $this->monthly_cost * $multiplier;
    }

    /**
     * Get the remaining contract value.
     */
    public function getRemainingValueAttribute()
    {
        if (! $this->end_date || ! $this->monthly_cost) {
            return 0;
        }

        $monthsRemaining = now()->diffInMonths($this->end_date, false);
        if ($monthsRemaining <= 0) {
            return 0;
        }

        return $this->monthly_cost * $monthsRemaining;
    }

    /**
     * Get available service types.
     */
    public static function getServiceTypes()
    {
        return [
            'managed_services' => 'Managed Services',
            'cloud_services' => 'Cloud Services',
            'backup_services' => 'Backup Services',
            'security_services' => 'Security Services',
            'monitoring' => 'Monitoring Services',
            'support' => 'Support Services',
            'consulting' => 'Consulting Services',
            'maintenance' => 'Maintenance Services',
            'hosting' => 'Hosting Services',
            'licensing' => 'Software Licensing',
            'training' => 'Training Services',
            'project' => 'Project Services',
            'other' => 'Other Services',
        ];
    }

    /**
     * Get available service categories.
     */
    public static function getServiceCategories()
    {
        return [
            'infrastructure' => 'Infrastructure',
            'network' => 'Network Services',
            'security' => 'Security',
            'backup' => 'Backup & Recovery',
            'cloud' => 'Cloud Services',
            'email' => 'Email Services',
            'web' => 'Web Services',
            'database' => 'Database Services',
            'application' => 'Application Services',
            'desktop' => 'Desktop Support',
            'mobile' => 'Mobile Services',
            'voip' => 'VoIP Services',
            'compliance' => 'Compliance Services',
            'other' => 'Other',
        ];
    }

    /**
     * Get available service statuses.
     */
    public static function getServiceStatuses()
    {
        return [
            'active' => 'Active',
            'pending' => 'Pending',
            'suspended' => 'Suspended',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
        ];
    }

    /**
     * Get available billing cycles.
     */
    public static function getBillingCycles()
    {
        return [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semi-annually' => 'Semi-Annually',
            'annually' => 'Annually',
            'one-time' => 'One-Time',
        ];
    }

    /**
     * Get available service levels.
     */
    public static function getServiceLevels()
    {
        return [
            'basic' => 'Basic',
            'standard' => 'Standard',
            'premium' => 'Premium',
            'enterprise' => 'Enterprise',
            'custom' => 'Custom',
        ];
    }

    /**
     * Get available priority levels.
     */
    public static function getPriorityLevels()
    {
        return [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'critical' => 'Critical',
        ];
    }
}
