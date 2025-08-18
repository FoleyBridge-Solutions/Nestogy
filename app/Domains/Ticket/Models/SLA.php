<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToCompany;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * SLA Model for Domain-Driven Design
 * 
 * Represents Service Level Agreements with comprehensive terms including
 * response times, resolution times, business hours, escalation policies,
 * and performance targets.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_default
 * @property bool $is_active
 * @property int $critical_response_minutes
 * @property int $high_response_minutes
 * @property int $medium_response_minutes
 * @property int $low_response_minutes
 * @property int $critical_resolution_minutes
 * @property int $high_resolution_minutes
 * @property int $medium_resolution_minutes
 * @property int $low_resolution_minutes
 * @property string $business_hours_start
 * @property string $business_hours_end
 * @property array $business_days
 * @property string $timezone
 * @property string $coverage_type
 * @property bool $holiday_coverage
 * @property bool $exclude_weekends
 * @property bool $escalation_enabled
 * @property array|null $escalation_levels
 * @property int $breach_warning_percentage
 * @property float $uptime_percentage
 * @property float $first_call_resolution_target
 * @property float $customer_satisfaction_target
 * @property bool $notify_on_breach
 * @property bool $notify_on_warning
 * @property array|null $notification_emails
 * @property string $effective_from
 * @property string|null $effective_to
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SLA extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'slas';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_default',
        'is_active',
        'critical_response_minutes',
        'high_response_minutes',
        'medium_response_minutes',
        'low_response_minutes',
        'critical_resolution_minutes',
        'high_resolution_minutes',
        'medium_resolution_minutes',
        'low_resolution_minutes',
        'business_hours_start',
        'business_hours_end',
        'business_days',
        'timezone',
        'coverage_type',
        'holiday_coverage',
        'exclude_weekends',
        'escalation_enabled',
        'escalation_levels',
        'breach_warning_percentage',
        'uptime_percentage',
        'first_call_resolution_target',
        'customer_satisfaction_target',
        'notify_on_breach',
        'notify_on_warning',
        'notification_emails',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'critical_response_minutes' => 'integer',
        'high_response_minutes' => 'integer',
        'medium_response_minutes' => 'integer',
        'low_response_minutes' => 'integer',
        'critical_resolution_minutes' => 'integer',
        'high_resolution_minutes' => 'integer',
        'medium_resolution_minutes' => 'integer',
        'low_resolution_minutes' => 'integer',
        'business_days' => 'array',
        'holiday_coverage' => 'boolean',
        'exclude_weekends' => 'boolean',
        'escalation_enabled' => 'boolean',
        'escalation_levels' => 'array',
        'breach_warning_percentage' => 'integer',
        'uptime_percentage' => 'decimal:2',
        'first_call_resolution_target' => 'decimal:2',
        'customer_satisfaction_target' => 'decimal:2',
        'notify_on_breach' => 'boolean',
        'notify_on_warning' => 'boolean',
        'notification_emails' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Priority levels
     */
    const PRIORITY_CRITICAL = 'critical';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_LOW = 'low';

    /**
     * Coverage types
     */
    const COVERAGE_24_7 = '24/7';
    const COVERAGE_BUSINESS_HOURS = 'business_hours';
    const COVERAGE_CUSTOM = 'custom';

    /**
     * Get clients that use this SLA
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'sla_id');
    }

    /**
     * Scope to get active SLAs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default SLA for a company
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get effective SLAs at a given date
     */
    public function scopeEffectiveOn($query, $date = null)
    {
        $date = $date ?: now()->toDateString();
        
        return $query->where('effective_from', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $date);
                    });
    }

    /**
     * Get response time for a given priority
     */
    public function getResponseTimeMinutes(string $priority): int
    {
        $field = strtolower($priority) . '_response_minutes';
        return $this->$field ?? $this->low_response_minutes;
    }

    /**
     * Get resolution time for a given priority
     */
    public function getResolutionTimeMinutes(string $priority): int
    {
        $field = strtolower($priority) . '_resolution_minutes';
        return $this->$field ?? $this->low_resolution_minutes;
    }

    /**
     * Calculate response deadline from created timestamp
     */
    public function calculateResponseDeadline(Carbon $createdAt, string $priority): Carbon
    {
        $responseMinutes = $this->getResponseTimeMinutes($priority);
        
        if ($this->coverage_type === self::COVERAGE_24_7) {
            return $createdAt->addMinutes($responseMinutes);
        }
        
        return $this->addBusinessMinutes($createdAt, $responseMinutes);
    }

    /**
     * Calculate resolution deadline from created timestamp
     */
    public function calculateResolutionDeadline(Carbon $createdAt, string $priority): Carbon
    {
        $resolutionMinutes = $this->getResolutionTimeMinutes($priority);
        
        if ($this->coverage_type === self::COVERAGE_24_7) {
            return $createdAt->addMinutes($resolutionMinutes);
        }
        
        return $this->addBusinessMinutes($createdAt, $resolutionMinutes);
    }

    /**
     * Add business minutes to a timestamp considering business hours and days
     */
    protected function addBusinessMinutes(Carbon $startTime, int $minutes): Carbon
    {
        $current = $startTime->copy()->setTimezone($this->timezone);
        $businessStartTime = Carbon::createFromTimeString($this->business_hours_start);
        $businessEndTime = Carbon::createFromTimeString($this->business_hours_end);
        $minutesPerDay = $businessStartTime->diffInMinutes($businessEndTime);
        
        while ($minutes > 0) {
            // Skip to next business day if current time is outside business hours
            if (!$this->isBusinessTime($current)) {
                $current = $this->getNextBusinessTime($current);
                continue;
            }
            
            // Calculate minutes remaining in current business day
            $endOfBusinessDay = $current->copy()
                ->setTime($businessEndTime->hour, $businessEndTime->minute);
            $minutesRemainingInDay = $current->diffInMinutes($endOfBusinessDay);
            
            if ($minutes <= $minutesRemainingInDay) {
                // Can complete within current business day
                $current->addMinutes($minutes);
                $minutes = 0;
            } else {
                // Need to continue to next business day
                $minutes -= $minutesRemainingInDay;
                $current = $this->getNextBusinessTime($endOfBusinessDay);
            }
        }
        
        return $current;
    }

    /**
     * Check if given time is within business hours and days
     */
    public function isBusinessTime(Carbon $time): bool
    {
        $time = $time->copy()->setTimezone($this->timezone);
        
        // Check if it's a business day
        $dayOfWeek = strtolower($time->format('l'));
        if (!in_array($dayOfWeek, $this->business_days)) {
            return false;
        }
        
        // Check if it's within business hours
        $businessStart = Carbon::createFromTimeString($this->business_hours_start, $this->timezone);
        $businessEnd = Carbon::createFromTimeString($this->business_hours_end, $this->timezone);
        $currentTime = Carbon::createFromTimeString($time->format('H:i:s'), $this->timezone);
        
        return $currentTime->between($businessStart, $businessEnd);
    }

    /**
     * Get next business time after given timestamp
     */
    protected function getNextBusinessTime(Carbon $time): Carbon
    {
        $next = $time->copy()->setTimezone($this->timezone);
        $businessStart = Carbon::createFromTimeString($this->business_hours_start);
        
        // Start from next day
        $next->addDay()->setTime($businessStart->hour, $businessStart->minute);
        
        // Find next business day
        while (!$this->isBusinessDay($next)) {
            $next->addDay();
        }
        
        return $next;
    }

    /**
     * Check if given date is a business day
     */
    protected function isBusinessDay(Carbon $date): bool
    {
        $dayOfWeek = strtolower($date->format('l'));
        return in_array($dayOfWeek, $this->business_days);
    }

    /**
     * Check if SLA is breached for given timestamps
     */
    public function isBreached(Carbon $createdAt, string $priority, string $type = 'response', Carbon $resolvedAt = null): bool
    {
        if ($type === 'response') {
            $deadline = $this->calculateResponseDeadline($createdAt, $priority);
            return now()->gt($deadline);
        } elseif ($type === 'resolution') {
            $deadline = $this->calculateResolutionDeadline($createdAt, $priority);
            $checkTime = $resolvedAt ?: now();
            return $checkTime->gt($deadline);
        }
        
        return false;
    }

    /**
     * Check if SLA is approaching breach (warning threshold)
     */
    public function isApproachingBreach(Carbon $createdAt, string $priority, string $type = 'response'): bool
    {
        if ($type === 'response') {
            $deadline = $this->calculateResponseDeadline($createdAt, $priority);
            $totalMinutes = $createdAt->diffInMinutes($deadline);
            $elapsedMinutes = $createdAt->diffInMinutes(now());
            $percentageElapsed = ($elapsedMinutes / $totalMinutes) * 100;
            
            return $percentageElapsed >= $this->breach_warning_percentage;
        }
        
        return false;
    }

    /**
     * Get available priority levels
     */
    public static function getPriorityLevels(): array
    {
        return [
            self::PRIORITY_CRITICAL => 'Critical',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_LOW => 'Low',
        ];
    }

    /**
     * Get available coverage types
     */
    public static function getCoverageTypes(): array
    {
        return [
            self::COVERAGE_24_7 => '24/7 Coverage',
            self::COVERAGE_BUSINESS_HOURS => 'Business Hours Only',
            self::COVERAGE_CUSTOM => 'Custom Schedule',
        ];
    }

    /**
     * Get available business days
     */
    public static function getBusinessDays(): array
    {
        return [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one default SLA per company
        static::saving(function ($sla) {
            if ($sla->is_default) {
                static::where('company_id', $sla->company_id)
                    ->where('id', '!=', $sla->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}