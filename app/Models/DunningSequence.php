<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;

/**
 * Dunning Sequence Model
 * 
 * Represents individual steps in a dunning campaign with detailed
 * configuration for timing, actions, and escalation logic.
 * 
 * @property int $id
 * @property int $campaign_id
 * @property string $name
 * @property string|null $description
 * @property int $step_number
 * @property string $status
 * @property int $days_after_previous
 * @property int|null $days_after_trigger
 * @property \Illuminate\Support\Carbon|null $preferred_send_time
 * @property array|null $excluded_days
 * @property string $action_type
 * @property string|null $email_template_id
 * @property string|null $sms_template_id
 * @property string|null $letter_template_id
 * @property string|null $custom_message
 * @property array|null $personalization_tokens
 * @property bool $is_escalation_step
 * @property string|null $escalation_severity
 * @property bool $requires_manager_approval
 * @property bool $auto_escalate_on_failure
 * @property array|null $services_to_suspend
 * @property array|null $essential_services_to_maintain
 * @property bool $graceful_suspension
 * @property int|null $suspension_notice_hours
 * @property bool $include_payment_link
 * @property bool $offer_payment_plan
 * @property float|null $settlement_percentage
 * @property int|null $settlement_deadline_days
 * @property float|null $late_fee_amount
 * @property bool $compound_late_fees
 * @property bool $final_notice
 * @property bool $legal_threat
 * @property string|null $legal_disclaimer
 * @property array|null $required_disclosures
 * @property bool $right_to_dispute_notice
 * @property array|null $success_conditions
 * @property array|null $failure_conditions
 * @property int $max_retry_attempts
 * @property int $retry_interval_hours
 * @property int $times_executed
 * @property float $success_rate
 * @property float $average_response_time
 * @property array|null $performance_metrics
 * @property bool $pause_sequence_on_contact
 * @property bool $pause_sequence_on_payment
 * @property bool $pause_sequence_on_dispute
 * @property int|null $sequence_timeout_days
 */
class DunningSequence extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'dunning_sequences';

    protected $fillable = [
        'company_id', 'campaign_id', 'name', 'description', 'step_number', 'status',
        'days_after_previous', 'days_after_trigger', 'preferred_send_time', 'excluded_days',
        'action_type', 'email_template_id', 'sms_template_id', 'letter_template_id',
        'custom_message', 'personalization_tokens', 'is_escalation_step', 'escalation_severity',
        'requires_manager_approval', 'auto_escalate_on_failure', 'services_to_suspend',
        'essential_services_to_maintain', 'graceful_suspension', 'suspension_notice_hours',
        'include_payment_link', 'offer_payment_plan', 'settlement_percentage',
        'settlement_deadline_days', 'late_fee_amount', 'compound_late_fees',
        'final_notice', 'legal_threat', 'legal_disclaimer', 'required_disclosures',
        'right_to_dispute_notice', 'success_conditions', 'failure_conditions',
        'max_retry_attempts', 'retry_interval_hours', 'times_executed',
        'success_rate', 'average_response_time', 'performance_metrics',
        'pause_sequence_on_contact', 'pause_sequence_on_payment', 'pause_sequence_on_dispute',
        'sequence_timeout_days', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'company_id' => 'integer',
        'campaign_id' => 'integer',
        'step_number' => 'integer',
        'days_after_previous' => 'integer',
        'days_after_trigger' => 'integer',
        'preferred_send_time' => 'datetime',
        'excluded_days' => 'array',
        'personalization_tokens' => 'array',
        'is_escalation_step' => 'boolean',
        'requires_manager_approval' => 'boolean',
        'auto_escalate_on_failure' => 'boolean',
        'services_to_suspend' => 'array',
        'essential_services_to_maintain' => 'array',
        'graceful_suspension' => 'boolean',
        'suspension_notice_hours' => 'integer',
        'include_payment_link' => 'boolean',
        'offer_payment_plan' => 'boolean',
        'settlement_percentage' => 'decimal:2',
        'settlement_deadline_days' => 'integer',
        'late_fee_amount' => 'decimal:2',
        'compound_late_fees' => 'boolean',
        'final_notice' => 'boolean',
        'legal_threat' => 'boolean',
        'required_disclosures' => 'array',
        'right_to_dispute_notice' => 'boolean',
        'success_conditions' => 'array',
        'failure_conditions' => 'array',
        'max_retry_attempts' => 'integer',
        'retry_interval_hours' => 'integer',
        'times_executed' => 'integer',
        'success_rate' => 'decimal:2',
        'average_response_time' => 'decimal:2',
        'performance_metrics' => 'array',
        'pause_sequence_on_contact' => 'boolean',
        'pause_sequence_on_payment' => 'boolean',
        'pause_sequence_on_dispute' => 'boolean',
        'sequence_timeout_days' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_DRAFT = 'draft';

    // Action type constants
    const ACTION_EMAIL = 'email';
    const ACTION_SMS = 'sms';
    const ACTION_PHONE_CALL = 'phone_call';
    const ACTION_LETTER = 'letter';
    const ACTION_PORTAL_NOTIFICATION = 'portal_notification';
    const ACTION_SERVICE_SUSPENSION = 'service_suspension';
    const ACTION_SERVICE_RESTORATION = 'service_restoration';
    const ACTION_LEGAL_HANDOFF = 'legal_handoff';
    const ACTION_COLLECTION_AGENCY = 'collection_agency';
    const ACTION_PAYMENT_PLAN_OFFER = 'payment_plan_offer';
    const ACTION_SETTLEMENT_OFFER = 'settlement_offer';

    // Escalation severity constants
    const ESCALATION_LOW = 'low';
    const ESCALATION_MEDIUM = 'medium';
    const ESCALATION_HIGH = 'high';
    const ESCALATION_CRITICAL = 'critical';

    /**
     * Get the campaign this sequence belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DunningCampaign::class, 'campaign_id');
    }

    /**
     * Get the actions executed for this sequence.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(DunningAction::class, 'sequence_id');
    }

    /**
     * Get the user who created this sequence.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this sequence.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if sequence is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if this is a communication action.
     */
    public function isCommunicationAction(): bool
    {
        return in_array($this->action_type, [
            self::ACTION_EMAIL,
            self::ACTION_SMS,
            self::ACTION_PHONE_CALL,
            self::ACTION_LETTER,
            self::ACTION_PORTAL_NOTIFICATION
        ]);
    }

    /**
     * Check if this is a service action.
     */
    public function isServiceAction(): bool
    {
        return in_array($this->action_type, [
            self::ACTION_SERVICE_SUSPENSION,
            self::ACTION_SERVICE_RESTORATION
        ]);
    }

    /**
     * Check if this is an escalation action.
     */
    public function isEscalationAction(): bool
    {
        return in_array($this->action_type, [
            self::ACTION_LEGAL_HANDOFF,
            self::ACTION_COLLECTION_AGENCY
        ]);
    }

    /**
     * Calculate when this sequence step should execute.
     */
    public function calculateExecutionTime(Carbon $triggerDate, Carbon $previousStepDate = null): Carbon
    {
        if ($this->days_after_trigger !== null) {
            $executionDate = $triggerDate->copy()->addDays($this->days_after_trigger);
        } else {
            $baseDate = $previousStepDate ?: $triggerDate;
            $executionDate = $baseDate->copy()->addDays($this->days_after_previous);
        }

        // Apply preferred send time
        if ($this->preferred_send_time) {
            $preferredTime = Carbon::parse($this->preferred_send_time);
            $executionDate->setTime($preferredTime->hour, $preferredTime->minute);
        }

        // Skip excluded days
        while ($this->isExcludedDay($executionDate)) {
            $executionDate->addDay();
        }

        // Use campaign's contact hours if available
        if ($this->campaign && !$this->campaign->isWithinContactHours($executionDate)) {
            $executionDate = $this->campaign->getNextAvailableContactTime($executionDate);
        }

        return $executionDate;
    }

    /**
     * Check if the given date is an excluded day.
     */
    public function isExcludedDay(Carbon $date): bool
    {
        if (!$this->excluded_days) {
            return false;
        }

        $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday
        $dateString = $date->format('Y-m-d');

        return in_array($dayOfWeek, $this->excluded_days) || 
               in_array($dateString, $this->excluded_days);
    }

    /**
     * Get the personalized message for the given invoice and client.
     */
    public function getPersonalizedMessage(Invoice $invoice, Client $client): string
    {
        $message = $this->custom_message ?: '';

        if (!$this->personalization_tokens) {
            return $message;
        }

        $tokens = [
            '{{client_name}}' => $client->getDisplayName(),
            '{{client_first_name}}' => $client->name,
            '{{invoice_number}}' => $invoice->getFullNumber(),
            '{{invoice_date}}' => $invoice->date->format('M d, Y'),
            '{{due_date}}' => $invoice->due_date->format('M d, Y'),
            '{{amount_due}}' => $invoice->getFormattedBalance(),
            '{{days_overdue}}' => Carbon::now()->diffInDays($invoice->due_date),
            '{{payment_link}}' => $this->include_payment_link ? $invoice->getPublicUrl() : '',
            '{{company_name}}' => $client->company->name ?? 'Our Company',
        ];

        // Add late fees if applicable
        if ($this->late_fee_amount) {
            $lateFees = $this->calculateLateFees($invoice);
            $tokens['{{late_fees}}'] = '$' . number_format($lateFees, 2);
            $tokens['{{total_with_fees}}'] = '$' . number_format($invoice->getBalance() + $lateFees, 2);
        }

        // Add settlement offer if applicable
        if ($this->settlement_percentage) {
            $settlementAmount = $invoice->getBalance() * ($this->settlement_percentage / 100);
            $tokens['{{settlement_amount}}'] = '$' . number_format($settlementAmount, 2);
            $tokens['{{settlement_deadline}}'] = Carbon::now()->addDays($this->settlement_deadline_days ?: 10)->format('M d, Y');
        }

        return str_replace(array_keys($tokens), array_values($tokens), $message);
    }

    /**
     * Calculate late fees for the invoice.
     */
    public function calculateLateFees(Invoice $invoice): float
    {
        if (!$this->late_fee_amount) {
            return 0;
        }

        $daysOverdue = Carbon::now()->diffInDays($invoice->due_date);
        
        if ($this->compound_late_fees) {
            // Compound late fees monthly
            $months = ceil($daysOverdue / 30);
            return $this->late_fee_amount * $months;
        }

        return $this->late_fee_amount;
    }

    /**
     * Check if the sequence should pause based on conditions.
     */
    public function shouldPauseSequence(DunningAction $action): bool
    {
        if ($this->pause_sequence_on_payment && $action->resulted_in_payment) {
            return true;
        }

        if ($this->pause_sequence_on_contact && $action->response_type === 'contact') {
            return true;
        }

        if ($this->pause_sequence_on_dispute && $action->response_type === 'dispute') {
            return true;
        }

        return false;
    }

    /**
     * Update sequence performance metrics.
     */
    public function updatePerformanceMetrics(): void
    {
        $recentActions = $this->actions()
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->get();

        $totalActions = $recentActions->count();
        $successfulActions = $recentActions->where('resulted_in_payment', true)->count();
        $avgResponseTime = $recentActions->where('responded_at')->avg(function ($action) {
            return $action->responded_at ? 
                Carbon::parse($action->attempted_at)->diffInHours($action->responded_at) : null;
        });

        $this->update([
            'times_executed' => $this->times_executed + $totalActions,
            'success_rate' => $totalActions > 0 ? ($successfulActions / $totalActions) * 100 : 0,
            'average_response_time' => $avgResponseTime ?: 0,
        ]);
    }

    /**
     * Scope to get active sequences.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get sequences by action type.
     */
    public function scopeActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope to get escalation steps.
     */
    public function scopeEscalation($query)
    {
        return $query->where('is_escalation_step', true);
    }

    /**
     * Scope to order by step number.
     */
    public function scopeOrderByStep($query)
    {
        return $query->orderBy('step_number');
    }

    /**
     * Get available action types.
     */
    public static function getAvailableActionTypes(): array
    {
        return [
            self::ACTION_EMAIL => 'Email',
            self::ACTION_SMS => 'SMS',
            self::ACTION_PHONE_CALL => 'Phone Call',
            self::ACTION_LETTER => 'Letter',
            self::ACTION_PORTAL_NOTIFICATION => 'Portal Notification',
            self::ACTION_SERVICE_SUSPENSION => 'Service Suspension',
            self::ACTION_SERVICE_RESTORATION => 'Service Restoration',
            self::ACTION_LEGAL_HANDOFF => 'Legal Handoff',
            self::ACTION_COLLECTION_AGENCY => 'Collection Agency',
            self::ACTION_PAYMENT_PLAN_OFFER => 'Payment Plan Offer',
            self::ACTION_SETTLEMENT_OFFER => 'Settlement Offer',
        ];
    }

    /**
     * Get available escalation severities.
     */
    public static function getAvailableEscalationSeverities(): array
    {
        return [
            self::ESCALATION_LOW => 'Low',
            self::ESCALATION_MEDIUM => 'Medium',
            self::ESCALATION_HIGH => 'High',
            self::ESCALATION_CRITICAL => 'Critical',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sequence) {
            if (!$sequence->created_by) {
                $sequence->created_by = auth()->id() ?? 1;
            }
        });

        static::updating(function ($sequence) {
            $sequence->updated_by = auth()->id() ?? 1;
        });
    }
}