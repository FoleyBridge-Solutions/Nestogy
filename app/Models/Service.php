<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'service_type',
        'estimated_hours',
        'sla_days',
        'response_time_hours',
        'resolution_time_hours',
        'deliverables',
        'dependencies',
        'requirements',
        'requires_scheduling',
        'min_notice_hours',
        'duration_minutes',
        'availability_schedule',
        'default_assignee_id',
        'required_skills',
        'required_resources',
        'has_setup_fee',
        'setup_fee',
        'has_cancellation_fee',
        'cancellation_fee',
        'cancellation_notice_hours',
        'minimum_commitment_months',
        'maximum_duration_months',
        'auto_renew',
        'renewal_notice_days',
    ];

    protected $casts = [
        'estimated_hours' => 'decimal:2',
        'deliverables' => 'array',
        'dependencies' => 'array',
        'requirements' => 'array',
        'availability_schedule' => 'array',
        'required_skills' => 'array',
        'required_resources' => 'array',
        'requires_scheduling' => 'boolean',
        'has_setup_fee' => 'boolean',
        'setup_fee' => 'decimal:2',
        'has_cancellation_fee' => 'boolean',
        'cancellation_fee' => 'decimal:2',
        'auto_renew' => 'boolean',
    ];

    /**
     * Get the product this service extends
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the default assignee for this service
     */
    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    /**
     * Check if service requires commitment
     */
    public function hasMinimumCommitment(): bool
    {
        return $this->minimum_commitment_months > 0;
    }

    /**
     * Check if service has setup requirements
     */
    public function hasSetupRequirements(): bool
    {
        return $this->has_setup_fee || ! empty($this->requirements);
    }

    /**
     * Get total setup cost
     */
    public function getTotalSetupCost(): float
    {
        return $this->has_setup_fee ? $this->setup_fee : 0;
    }

    /**
     * Check if service is available on a specific day
     */
    public function isAvailableOn($dayOfWeek): bool
    {
        if (empty($this->availability_schedule)) {
            return true; // Available all days if not specified
        }

        return isset($this->availability_schedule[$dayOfWeek]) &&
               $this->availability_schedule[$dayOfWeek]['available'] === true;
    }

    /**
     * Get estimated completion date
     */
    public function getEstimatedCompletionDate($startDate = null): ?\DateTime
    {
        if (! $this->sla_days) {
            return null;
        }

        $start = $startDate ? new \DateTime($startDate) : new \DateTime;
        $interval = new \DateInterval('P'.$this->sla_days.'D');

        return $start->add($interval);
    }

    /**
     * Calculate cancellation fee for a given date
     */
    public function calculateCancellationFee($cancellationDate, $serviceDate): float
    {
        if (! $this->has_cancellation_fee) {
            return 0;
        }

        $hoursNotice = (strtotime($serviceDate) - strtotime($cancellationDate)) / 3600;

        if ($hoursNotice < $this->cancellation_notice_hours) {
            return $this->cancellation_fee;
        }

        return 0;
    }

    /**
     * Get service duration in human readable format
     */
    public function getFormattedDuration(): string
    {
        if (! $this->duration_minutes) {
            return 'Variable';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}min";
        } elseif ($hours > 0) {
            return "{$hours} hour".($hours > 1 ? 's' : '');
        } else {
            return "{$minutes} minutes";
        }
    }

    /**
     * Check if service can be scheduled for a specific time
     */
    public function canScheduleAt($dateTime): bool
    {
        if (! $this->requires_scheduling) {
            return true;
        }

        $scheduledTime = new \DateTime($dateTime);
        $now = new \DateTime;
        $hoursNotice = ($scheduledTime->getTimestamp() - $now->getTimestamp()) / 3600;

        return $hoursNotice >= $this->min_notice_hours;
    }
}
