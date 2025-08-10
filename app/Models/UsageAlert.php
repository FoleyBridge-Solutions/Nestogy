<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * UsageAlert Model
 * 
 * Manages usage threshold monitoring and alerting system for real-time 
 * usage tracking with predictive analytics and automated notifications.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property int|null $usage_pool_id
 * @property int|null $usage_bucket_id
 * @property string $alert_name
 * @property string $alert_code
 * @property string $alert_type
 * @property string $usage_type
 * @property string $threshold_type
 * @property float $threshold_value
 * @property string $threshold_unit
 * @property bool $is_active
 * @property string $alert_status
 */
class UsageAlert extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'usage_alerts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'usage_pool_id',
        'usage_bucket_id',
        'alert_name',
        'alert_code',
        'alert_type',
        'description',
        'is_active',
        'usage_type',
        'service_types',
        'monitoring_scope',
        'measurement_period',
        'threshold_type',
        'threshold_value',
        'threshold_unit',
        'critical_threshold',
        'warning_threshold',
        'comparison_operator',
        'alert_conditions',
        'trigger_criteria',
        'require_consecutive_periods',
        'consecutive_period_count',
        'enable_predictive_alerts',
        'prediction_horizon_hours',
        'prediction_confidence_threshold',
        'prediction_model_params',
        'prediction_algorithm',
        'current_usage',
        'current_threshold_percentage',
        'alert_status',
        'last_check_at',
        'last_triggered_at',
        'trigger_count',
        'severity_level',
        'enable_escalation',
        'escalation_rules',
        'escalation_delay_minutes',
        'last_escalated_at',
        'escalation_level',
        'notification_methods',
        'notification_recipients',
        'email_notifications',
        'sms_notifications',
        'webhook_notifications',
        'dashboard_notifications',
        'email_template',
        'email_recipients',
        'email_subject_template',
        'email_include_charts',
        'email_include_recommendations',
        'sms_recipients',
        'sms_message_template',
        'sms_only_critical',
        'webhook_url',
        'webhook_method',
        'webhook_headers',
        'webhook_payload_template',
        'webhook_timeout_seconds',
        'webhook_retry_attempts',
        'enable_suppression',
        'suppression_window_minutes',
        'max_alerts_per_hour',
        'max_alerts_per_day',
        'suppression_until',
        'suppressed_alert_count',
        'respect_business_hours',
        'business_hours_schedule',
        'time_zone',
        'weekend_notifications',
        'holiday_notifications',
        'enable_automated_actions',
        'automated_actions',
        'auto_suspend_services',
        'auto_limit_usage',
        'auto_purchase_additional_usage',
        'automation_parameters',
        'enable_batching',
        'batch_size',
        'batch_interval_minutes',
        'last_batch_processed_at',
        'processing_priority',
        'recent_alerts',
        'alert_accuracy_percentage',
        'false_positive_count',
        'false_negative_count',
        'performance_metrics',
        'external_alert_id',
        'integration_settings',
        'sync_with_monitoring_systems',
        'last_external_sync_at',
        'is_test_alert',
        'last_test_triggered_at',
        'test_results',
        'test_mode_enabled',
        'requires_acknowledgment',
        'acknowledgment_log',
        'audit_trail_enabled',
        'compliance_requirements',
        'cost_savings_generated',
        'revenue_protected',
        'issues_prevented',
        'optimization_suggestions',
        'alert_lifecycle_stage',
        'alert_created_date',
        'alert_last_modified_date',
        'created_by',
        'updated_by',
        'modification_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'usage_pool_id' => 'integer',
        'usage_bucket_id' => 'integer',
        'is_active' => 'boolean',
        'service_types' => 'array',
        'threshold_value' => 'decimal:4',
        'critical_threshold' => 'decimal:4',
        'warning_threshold' => 'decimal:4',
        'alert_conditions' => 'array',
        'trigger_criteria' => 'array',
        'require_consecutive_periods' => 'boolean',
        'consecutive_period_count' => 'integer',
        'enable_predictive_alerts' => 'boolean',
        'prediction_horizon_hours' => 'integer',
        'prediction_confidence_threshold' => 'decimal:2',
        'prediction_model_params' => 'array',
        'current_usage' => 'decimal:4',
        'current_threshold_percentage' => 'decimal:2',
        'last_check_at' => 'datetime',
        'last_triggered_at' => 'datetime',
        'trigger_count' => 'integer',
        'enable_escalation' => 'boolean',
        'escalation_rules' => 'array',
        'escalation_delay_minutes' => 'integer',
        'last_escalated_at' => 'datetime',
        'escalation_level' => 'integer',
        'notification_methods' => 'array',
        'notification_recipients' => 'array',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'webhook_notifications' => 'boolean',
        'dashboard_notifications' => 'boolean',
        'email_recipients' => 'array',
        'email_include_charts' => 'boolean',
        'email_include_recommendations' => 'boolean',
        'sms_recipients' => 'array',
        'sms_only_critical' => 'boolean',
        'webhook_headers' => 'array',
        'webhook_payload_template' => 'array',
        'webhook_timeout_seconds' => 'integer',
        'webhook_retry_attempts' => 'integer',
        'enable_suppression' => 'boolean',
        'suppression_window_minutes' => 'integer',
        'max_alerts_per_hour' => 'integer',
        'max_alerts_per_day' => 'integer',
        'suppression_until' => 'datetime',
        'suppressed_alert_count' => 'integer',
        'respect_business_hours' => 'boolean',
        'business_hours_schedule' => 'array',
        'weekend_notifications' => 'boolean',
        'holiday_notifications' => 'boolean',
        'enable_automated_actions' => 'boolean',
        'automated_actions' => 'array',
        'auto_suspend_services' => 'boolean',
        'auto_limit_usage' => 'boolean',
        'auto_purchase_additional_usage' => 'boolean',
        'automation_parameters' => 'array',
        'enable_batching' => 'boolean',
        'batch_size' => 'integer',
        'batch_interval_minutes' => 'integer',
        'last_batch_processed_at' => 'datetime',
        'processing_priority' => 'integer',
        'recent_alerts' => 'array',
        'alert_accuracy_percentage' => 'decimal:2',
        'false_positive_count' => 'integer',
        'false_negative_count' => 'integer',
        'performance_metrics' => 'array',
        'integration_settings' => 'array',
        'sync_with_monitoring_systems' => 'boolean',
        'last_external_sync_at' => 'datetime',
        'is_test_alert' => 'boolean',
        'last_test_triggered_at' => 'datetime',
        'test_results' => 'array',
        'test_mode_enabled' => 'boolean',
        'requires_acknowledgment' => 'boolean',
        'acknowledgment_log' => 'array',
        'audit_trail_enabled' => 'boolean',
        'compliance_requirements' => 'array',
        'cost_savings_generated' => 'decimal:2',
        'revenue_protected' => 'decimal:2',
        'issues_prevented' => 'integer',
        'optimization_suggestions' => 'array',
        'alert_created_date' => 'datetime',
        'alert_last_modified_date' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Alert type constants
     */
    const ALERT_TYPE_THRESHOLD = 'threshold';
    const ALERT_TYPE_USAGE_PATTERN = 'usage_pattern';
    const ALERT_TYPE_ANOMALY = 'anomaly';
    const ALERT_TYPE_PREDICTIVE = 'predictive';
    const ALERT_TYPE_BILLING = 'billing';

    /**
     * Alert status constants
     */
    const STATUS_NORMAL = 'normal';
    const STATUS_WARNING = 'warning';
    const STATUS_CRITICAL = 'critical';
    const STATUS_TRIGGERED = 'triggered';

    /**
     * Threshold type constants
     */
    const THRESHOLD_TYPE_PERCENTAGE = 'percentage';
    const THRESHOLD_TYPE_ABSOLUTE = 'absolute';
    const THRESHOLD_TYPE_RATE_OF_CHANGE = 'rate_of_change';
    const THRESHOLD_TYPE_PREDICTIVE = 'predictive';

    /**
     * Monitoring scope constants
     */
    const SCOPE_CLIENT = 'client';
    const SCOPE_POOL = 'pool';
    const SCOPE_BUCKET = 'bucket';
    const SCOPE_SERVICE = 'service';
    const SCOPE_GLOBAL = 'global';

    /**
     * Measurement period constants
     */
    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_BILLING_CYCLE = 'billing_cycle';
    const PERIOD_REAL_TIME = 'real_time';

    /**
     * Severity level constants
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Get the client that owns this alert.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the usage pool this alert monitors (if applicable).
     */
    public function usagePool(): BelongsTo
    {
        return $this->belongsTo(UsagePool::class);
    }

    /**
     * Get the usage bucket this alert monitors (if applicable).
     */
    public function usageBucket(): BelongsTo
    {
        return $this->belongsTo(UsageBucket::class);
    }

    /**
     * Get the user who created this alert.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this alert.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the alert is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if the alert is in a triggered state.
     */
    public function isTriggered(): bool
    {
        return $this->alert_status === self::STATUS_TRIGGERED;
    }

    /**
     * Check if the alert is at warning level.
     */
    public function isWarning(): bool
    {
        return $this->alert_status === self::STATUS_WARNING;
    }

    /**
     * Check if the alert is at critical level.
     */
    public function isCritical(): bool
    {
        return $this->alert_status === self::STATUS_CRITICAL;
    }

    /**
     * Check if alert should be sent (rate limiting).
     */
    public function shouldSendAlert(): bool
    {
        if (!$this->enable_suppression) {
            return true;
        }

        // Check suppression window
        if ($this->suppression_until && now()->lt($this->suppression_until)) {
            return false;
        }

        // Check hourly and daily limits
        $hourlyCount = $this->getAlertsInTimeframe(60);
        $dailyCount = $this->getAlertsInTimeframe(1440);

        if ($hourlyCount >= $this->max_alerts_per_hour) {
            return false;
        }

        if ($dailyCount >= $this->max_alerts_per_day) {
            return false;
        }

        // Check business hours if enabled
        if ($this->respect_business_hours && !$this->isWithinBusinessHours()) {
            return false;
        }

        // Check weekend notifications
        if (!$this->weekend_notifications && now()->isWeekend()) {
            return false;
        }

        return true;
    }

    /**
     * Get alert count in timeframe (minutes).
     */
    protected function getAlertsInTimeframe(int $minutes): int
    {
        $recentAlerts = $this->recent_alerts ?? [];
        $cutoff = now()->subMinutes($minutes);
        
        return collect($recentAlerts)->filter(function ($alert) use ($cutoff) {
            return Carbon::parse($alert['timestamp'])->gt($cutoff);
        })->count();
    }

    /**
     * Check if current time is within business hours.
     */
    protected function isWithinBusinessHours(): bool
    {
        if (!$this->business_hours_schedule) {
            return true;
        }

        $now = now($this->time_zone ?? 'UTC');
        $dayOfWeek = $now->format('l'); // Full day name
        $currentTime = $now->format('H:i');

        $schedule = $this->business_hours_schedule[$dayOfWeek] ?? null;
        
        if (!$schedule || !$schedule['enabled']) {
            return false;
        }

        return $currentTime >= $schedule['start'] && $currentTime <= $schedule['end'];
    }

    /**
     * Evaluate if alert conditions are met.
     */
    public function evaluateConditions(float $currentUsage, array $options = []): bool
    {
        // Update current usage
        $this->update([
            'current_usage' => $currentUsage,
            'last_check_at' => now(),
        ]);

        $thresholdMet = $this->evaluateThreshold($currentUsage, $options);
        
        if (!$thresholdMet) {
            if ($this->alert_status !== self::STATUS_NORMAL) {
                $this->update(['alert_status' => self::STATUS_NORMAL]);
            }
            return false;
        }

        // Check consecutive periods requirement
        if ($this->require_consecutive_periods) {
            return $this->checkConsecutivePeriods($currentUsage);
        }

        return true;
    }

    /**
     * Evaluate threshold conditions.
     */
    protected function evaluateThreshold(float $currentUsage, array $options): bool
    {
        switch ($this->threshold_type) {
            case self::THRESHOLD_TYPE_PERCENTAGE:
                $capacity = $options['capacity'] ?? 100;
                $percentage = ($currentUsage / $capacity) * 100;
                $this->update(['current_threshold_percentage' => $percentage]);
                return $this->compareValue($percentage, $this->threshold_value);

            case self::THRESHOLD_TYPE_ABSOLUTE:
                return $this->compareValue($currentUsage, $this->threshold_value);

            case self::THRESHOLD_TYPE_RATE_OF_CHANGE:
                $previousUsage = $options['previous_usage'] ?? 0;
                $changeRate = $previousUsage > 0 ? (($currentUsage - $previousUsage) / $previousUsage) * 100 : 0;
                return $this->compareValue($changeRate, $this->threshold_value);

            case self::THRESHOLD_TYPE_PREDICTIVE:
                if ($this->enable_predictive_alerts) {
                    return $this->evaluatePredictiveThreshold($currentUsage, $options);
                }
                return false;

            default:
                return false;
        }
    }

    /**
     * Compare value using the configured operator.
     */
    protected function compareValue(float $value, float $threshold): bool
    {
        switch ($this->comparison_operator) {
            case '>=':
                return $value >= $threshold;
            case '>':
                return $value > $threshold;
            case '<=':
                return $value <= $threshold;
            case '<':
                return $value < $threshold;
            case '=':
                return abs($value - $threshold) < 0.01; // Float comparison with tolerance
            case '!=':
                return abs($value - $threshold) >= 0.01;
            default:
                return $value >= $threshold;
        }
    }

    /**
     * Evaluate predictive threshold.
     */
    protected function evaluatePredictiveThreshold(float $currentUsage, array $options): bool
    {
        // This would implement predictive analytics
        // For now, return false - implement based on specific ML requirements
        return false;
    }

    /**
     * Check consecutive periods requirement.
     */
    protected function checkConsecutivePeriods(float $currentUsage): bool
    {
        $recentAlerts = $this->recent_alerts ?? [];
        $consecutiveCount = 1; // Current period
        
        // Count recent consecutive threshold breaches
        $cutoffTime = now()->subHours(24); // Look back 24 hours
        
        foreach (array_reverse($recentAlerts) as $alert) {
            $alertTime = Carbon::parse($alert['timestamp']);
            if ($alertTime->gt($cutoffTime) && $alert['threshold_met']) {
                $consecutiveCount++;
            } else {
                break; // Non-consecutive breach found
            }
        }
        
        return $consecutiveCount >= $this->consecutive_period_count;
    }

    /**
     * Trigger the alert.
     */
    public function trigger(array $context = []): void
    {
        // Determine severity
        $severity = $this->determineSeverity($context);
        
        // Update alert status
        $this->update([
            'alert_status' => self::STATUS_TRIGGERED,
            'severity_level' => $severity,
            'last_triggered_at' => now(),
            'trigger_count' => $this->trigger_count + 1,
        ]);

        // Record in recent alerts
        $this->recordRecentAlert($context);

        // Send notifications if allowed
        if ($this->shouldSendAlert()) {
            $this->sendNotifications($context);
        } else {
            $this->incrementSuppressedCount();
        }

        // Execute automated actions if enabled
        if ($this->enable_automated_actions) {
            $this->executeAutomatedActions($context);
        }

        // Handle escalation
        if ($this->enable_escalation && $severity === self::SEVERITY_CRITICAL) {
            $this->handleEscalation($context);
        }
    }

    /**
     * Determine alert severity based on current conditions.
     */
    protected function determineSeverity(array $context): string
    {
        $usage = $this->current_usage;
        $capacity = $context['capacity'] ?? 100;
        $percentage = ($usage / $capacity) * 100;

        if ($percentage >= $this->critical_threshold) {
            return self::SEVERITY_CRITICAL;
        } elseif ($percentage >= $this->warning_threshold) {
            return self::SEVERITY_HIGH;
        } else {
            return $this->severity_level ?? self::SEVERITY_MEDIUM;
        }
    }

    /**
     * Record alert in recent alerts history.
     */
    protected function recordRecentAlert(array $context): void
    {
        $recentAlerts = $this->recent_alerts ?? [];
        $recentAlerts[] = [
            'timestamp' => now(),
            'usage' => $this->current_usage,
            'threshold_percentage' => $this->current_threshold_percentage,
            'severity' => $this->severity_level,
            'context' => $context,
        ];

        // Keep only last 50 alerts
        if (count($recentAlerts) > 50) {
            $recentAlerts = array_slice($recentAlerts, -50);
        }

        $this->update(['recent_alerts' => $recentAlerts]);
    }

    /**
     * Send alert notifications.
     */
    protected function sendNotifications(array $context): void
    {
        Log::info("Usage alert triggered", [
            'alert_id' => $this->id,
            'alert_name' => $this->alert_name,
            'client_id' => $this->client_id,
            'usage' => $this->current_usage,
            'threshold' => $this->threshold_value,
            'severity' => $this->severity_level,
        ]);

        // Here you would integrate with actual notification services
        // Email, SMS, webhooks, etc.
        
        // Update suppression window
        if ($this->enable_suppression) {
            $this->update([
                'suppression_until' => now()->addMinutes($this->suppression_window_minutes),
            ]);
        }
    }

    /**
     * Increment suppressed alert count.
     */
    protected function incrementSuppressedCount(): void
    {
        $this->increment('suppressed_alert_count');
    }

    /**
     * Execute automated actions.
     */
    protected function executeAutomatedActions(array $context): void
    {
        if ($this->auto_suspend_services) {
            Log::info("Auto-suspending services for alert", ['alert_id' => $this->id]);
            // Implement service suspension logic
        }

        if ($this->auto_limit_usage) {
            Log::info("Auto-limiting usage for alert", ['alert_id' => $this->id]);
            // Implement usage limiting logic
        }

        if ($this->auto_purchase_additional_usage) {
            Log::info("Auto-purchasing additional usage for alert", ['alert_id' => $this->id]);
            // Implement additional usage purchase logic
        }
    }

    /**
     * Handle escalation.
     */
    protected function handleEscalation(array $context): void
    {
        if ($this->last_escalated_at && 
            $this->last_escalated_at->addMinutes($this->escalation_delay_minutes)->gt(now())) {
            return; // Not enough time has passed for escalation
        }

        $this->update([
            'last_escalated_at' => now(),
            'escalation_level' => $this->escalation_level + 1,
        ]);

        Log::info("Alert escalated", [
            'alert_id' => $this->id,
            'escalation_level' => $this->escalation_level,
        ]);
    }

    /**
     * Acknowledge the alert.
     */
    public function acknowledge(int $userId, string $notes = null): void
    {
        $acknowledgment = [
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
            'notes' => $notes,
        ];

        $log = $this->acknowledgment_log ?? [];
        $log[] = $acknowledgment;

        $this->update([
            'acknowledgment_log' => $log,
            'alert_status' => self::STATUS_NORMAL,
        ]);
    }

    /**
     * Scope to get active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get triggered alerts.
     */
    public function scopeTriggered($query)
    {
        return $query->where('alert_status', self::STATUS_TRIGGERED);
    }

    /**
     * Scope to get alerts by type.
     */
    public function scopeByType($query, string $alertType)
    {
        return $query->where('alert_type', $alertType);
    }

    /**
     * Scope to get alerts by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity_level', $severity);
    }

    /**
     * Get available alert types.
     */
    public static function getAlertTypes(): array
    {
        return [
            self::ALERT_TYPE_THRESHOLD => 'Threshold',
            self::ALERT_TYPE_USAGE_PATTERN => 'Usage Pattern',
            self::ALERT_TYPE_ANOMALY => 'Anomaly',
            self::ALERT_TYPE_PREDICTIVE => 'Predictive',
            self::ALERT_TYPE_BILLING => 'Billing',
        ];
    }

    /**
     * Get severity levels.
     */
    public static function getSeverityLevels(): array
    {
        return [
            self::SEVERITY_LOW => 'Low',
            self::SEVERITY_MEDIUM => 'Medium',
            self::SEVERITY_HIGH => 'High',
            self::SEVERITY_CRITICAL => 'Critical',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($alert) {
            if (!$alert->alert_code) {
                $alert->alert_code = 'ALERT-' . strtoupper(uniqid());
            }
            
            if (!$alert->alert_created_date) {
                $alert->alert_created_date = now();
            }
        });

        static::updating(function ($alert) {
            $alert->updated_by = auth()->id() ?? 1;
            $alert->alert_last_modified_date = now();
        });
    }
}