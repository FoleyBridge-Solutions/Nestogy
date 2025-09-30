<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dunning Campaign Model
 *
 * Represents automated collection campaign management with sophisticated
 * triggering, escalation, and performance tracking capabilities.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string $campaign_type
 * @property int $trigger_days_overdue
 * @property float $minimum_amount
 * @property float|null $maximum_amount
 * @property array|null $client_status_filters
 * @property array|null $service_type_filters
 * @property array|null $jurisdiction_filters
 * @property string $risk_level
 * @property int|null $payment_history_threshold
 * @property int|null $max_failed_payments
 * @property bool $consider_contract_status
 * @property int $max_sequence_cycles
 * @property bool $auto_escalate
 * @property int|null $escalation_days
 * @property string|null $escalation_campaign_id
 * @property bool $enable_service_suspension
 * @property int|null $suspension_days_overdue
 * @property array|null $essential_services_to_maintain
 * @property bool $fdcpa_compliant
 * @property bool $tcpa_compliant
 * @property array|null $state_law_compliance
 * @property bool $require_dispute_resolution
 * @property float $success_rate
 * @property float $average_collection_time
 * @property float $cost_per_collection
 * @property int $total_campaigns_run
 * @property float $total_collected
 * @property bool $auto_start
 * @property \Illuminate\Support\Carbon|null $preferred_contact_time_start
 * @property \Illuminate\Support\Carbon|null $preferred_contact_time_end
 * @property array|null $blackout_dates
 * @property array|null $time_zone_settings
 */
class DunningCampaign extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'dunning_campaigns';

    protected $fillable = [
        'company_id', 'name', 'description', 'status', 'campaign_type',
        'trigger_days_overdue', 'minimum_amount', 'maximum_amount',
        'client_status_filters', 'service_type_filters', 'jurisdiction_filters',
        'risk_level', 'payment_history_threshold', 'max_failed_payments',
        'consider_contract_status', 'max_sequence_cycles', 'auto_escalate',
        'escalation_days', 'escalation_campaign_id', 'enable_service_suspension',
        'suspension_days_overdue', 'essential_services_to_maintain',
        'fdcpa_compliant', 'tcpa_compliant', 'state_law_compliance',
        'require_dispute_resolution', 'success_rate', 'average_collection_time',
        'cost_per_collection', 'total_campaigns_run', 'total_collected',
        'auto_start', 'preferred_contact_time_start', 'preferred_contact_time_end',
        'blackout_dates', 'time_zone_settings', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'trigger_days_overdue' => 'integer',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'client_status_filters' => 'array',
        'service_type_filters' => 'array',
        'jurisdiction_filters' => 'array',
        'payment_history_threshold' => 'integer',
        'max_failed_payments' => 'integer',
        'consider_contract_status' => 'boolean',
        'max_sequence_cycles' => 'integer',
        'auto_escalate' => 'boolean',
        'escalation_days' => 'integer',
        'enable_service_suspension' => 'boolean',
        'suspension_days_overdue' => 'integer',
        'essential_services_to_maintain' => 'array',
        'fdcpa_compliant' => 'boolean',
        'tcpa_compliant' => 'boolean',
        'state_law_compliance' => 'array',
        'require_dispute_resolution' => 'boolean',
        'success_rate' => 'decimal:2',
        'average_collection_time' => 'decimal:2',
        'cost_per_collection' => 'decimal:2',
        'total_campaigns_run' => 'integer',
        'total_collected' => 'decimal:2',
        'auto_start' => 'boolean',
        'preferred_contact_time_start' => 'datetime',
        'preferred_contact_time_end' => 'datetime',
        'blackout_dates' => 'array',
        'time_zone_settings' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_PAUSED = 'paused';

    const STATUS_DRAFT = 'draft';

    // Campaign type constants
    const TYPE_GENTLE = 'gentle';

    const TYPE_STANDARD = 'standard';

    const TYPE_AGGRESSIVE = 'aggressive';

    const TYPE_LEGAL = 'legal';

    const TYPE_SETTLEMENT = 'settlement';

    // Risk level constants
    const RISK_LOW = 'low';

    const RISK_MEDIUM = 'medium';

    const RISK_HIGH = 'high';

    const RISK_CRITICAL = 'critical';

    /**
     * Get the sequences for this campaign.
     */
    public function sequences(): HasMany
    {
        return $this->hasMany(DunningSequence::class, 'campaign_id')->orderBy('step_number');
    }

    /**
     * Get the actions executed for this campaign.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(DunningAction::class, 'campaign_id');
    }

    /**
     * Get the user who created this campaign.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this campaign.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if campaign is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if campaign can trigger for the given invoice.
     */
    public function canTriggerForInvoice(Invoice $invoice): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        // Check amount thresholds
        if ($invoice->getBalance() < $this->minimum_amount) {
            return false;
        }

        if ($this->maximum_amount && $invoice->getBalance() > $this->maximum_amount) {
            return false;
        }

        // Check days overdue
        $daysOverdue = $invoice->isOverdue() ?
            Carbon::now()->diffInDays($invoice->due_date) : 0;

        if ($daysOverdue < $this->trigger_days_overdue) {
            return false;
        }

        // Check client status filters
        if ($this->client_status_filters && ! in_array($invoice->client->status, $this->client_status_filters)) {
            return false;
        }

        // Check service type filters (for VoIP-specific campaigns)
        if ($this->service_type_filters && $invoice->hasVoIPServices()) {
            $invoiceServiceTypes = $invoice->voipItems->pluck('service_type')->unique()->toArray();
            if (! array_intersect($invoiceServiceTypes, $this->service_type_filters)) {
                return false;
            }
        }

        // Check jurisdiction filters
        if ($this->jurisdiction_filters && ! in_array($invoice->client->state, $this->jurisdiction_filters)) {
            return false;
        }

        // Check payment history threshold
        if ($this->payment_history_threshold) {
            $recentPaymentDays = $invoice->client->payments()
                ->where('created_at', '>=', Carbon::now()->subDays($this->payment_history_threshold))
                ->exists();

            if ($recentPaymentDays) {
                return false; // Recent payment, skip dunning
            }
        }

        // Check max failed payments
        if ($this->max_failed_payments) {
            $failedPaymentsCount = $invoice->client->payments()
                ->where('status', 'failed')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();

            if ($failedPaymentsCount >= $this->max_failed_payments) {
                // Escalate to different campaign or legal action
                return $this->campaign_type === self::TYPE_AGGRESSIVE ||
                       $this->campaign_type === self::TYPE_LEGAL;
            }
        }

        // Check contract status if required
        if ($this->consider_contract_status) {
            if (! $invoice->client->hasActiveContract()) {
                return $this->campaign_type === self::TYPE_AGGRESSIVE ||
                       $this->campaign_type === self::TYPE_LEGAL;
            }
        }

        return true;
    }

    /**
     * Get eligible invoices for this campaign.
     */
    public function getEligibleInvoices()
    {
        $query = Invoice::query()
            ->with(['client', 'items'])
            ->where('company_id', $this->company_id)
            ->overdue()
            ->where('amount', '>=', $this->minimum_amount);

        if ($this->maximum_amount) {
            $query->where('amount', '<=', $this->maximum_amount);
        }

        // Apply client status filters
        if ($this->client_status_filters) {
            $query->whereHas('client', function ($q) {
                $q->whereIn('status', $this->client_status_filters);
            });
        }

        // Apply jurisdiction filters
        if ($this->jurisdiction_filters) {
            $query->whereHas('client', function ($q) {
                $q->whereIn('state', $this->jurisdiction_filters);
            });
        }

        return $query->get()->filter(function ($invoice) {
            return $this->canTriggerForInvoice($invoice);
        });
    }

    /**
     * Calculate campaign performance metrics.
     */
    public function calculatePerformanceMetrics(): array
    {
        $actions = $this->actions()->where('created_at', '>=', Carbon::now()->subDays(30));

        $totalActions = $actions->count();
        $successfulActions = $actions->where('resulted_in_payment', true)->count();
        $totalCollected = $actions->sum('amount_collected');
        $totalCosts = $actions->sum('cost_per_action');

        $successRate = $totalActions > 0 ? ($successfulActions / $totalActions) * 100 : 0;
        $roi = $totalCosts > 0 ? (($totalCollected - $totalCosts) / $totalCosts) * 100 : 0;

        $avgCollectionTime = $actions->where('resulted_in_payment', true)
            ->avg('days_overdue');

        return [
            'total_actions' => $totalActions,
            'successful_actions' => $successfulActions,
            'success_rate' => round($successRate, 2),
            'total_collected' => $totalCollected,
            'total_costs' => $totalCosts,
            'roi' => round($roi, 2),
            'average_collection_time' => round($avgCollectionTime ?: 0, 2),
            'cost_per_collection' => $successfulActions > 0 ?
                round($totalCosts / $successfulActions, 2) : 0,
        ];
    }

    /**
     * Update campaign performance metrics.
     */
    public function updatePerformanceMetrics(): void
    {
        $metrics = $this->calculatePerformanceMetrics();

        $this->update([
            'success_rate' => $metrics['success_rate'],
            'average_collection_time' => $metrics['average_collection_time'],
            'cost_per_collection' => $metrics['cost_per_collection'],
            'total_campaigns_run' => $this->total_campaigns_run + 1,
            'total_collected' => $this->total_collected + $metrics['total_collected'],
        ]);
    }

    /**
     * Check if it's within the preferred contact time.
     */
    public function isWithinContactHours(?Carbon $datetime = null): bool
    {
        $datetime = $datetime ?: Carbon::now();

        if (! $this->preferred_contact_time_start || ! $this->preferred_contact_time_end) {
            return true;
        }

        $startTime = Carbon::parse($this->preferred_contact_time_start)->format('H:i');
        $endTime = Carbon::parse($this->preferred_contact_time_end)->format('H:i');
        $currentTime = $datetime->format('H:i');

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Check if the date is a blackout date.
     */
    public function isBlackoutDate(?Carbon $date = null): bool
    {
        $date = $date ?: Carbon::now();

        if (! $this->blackout_dates) {
            return false;
        }

        $checkDate = $date->format('Y-m-d');

        return in_array($checkDate, $this->blackout_dates);
    }

    /**
     * Get the next available contact time.
     */
    public function getNextAvailableContactTime(?Carbon $from = null): Carbon
    {
        $from = $from ?: Carbon::now();

        while ($this->isBlackoutDate($from) || ! $this->isWithinContactHours($from)) {
            $from->addHour();

            // Skip to next day if outside contact hours
            if (! $this->isWithinContactHours($from)) {
                $from->startOfDay()->addDay();
                if ($this->preferred_contact_time_start) {
                    $startTime = Carbon::parse($this->preferred_contact_time_start);
                    $from->setTime($startTime->hour, $startTime->minute);
                }
            }
        }

        return $from;
    }

    /**
     * Scope to get active campaigns.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get campaigns by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('campaign_type', $type);
    }

    /**
     * Scope to get campaigns by risk level.
     */
    public function scopeRiskLevel($query, string $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_DRAFT => 'Draft',
        ];
    }

    /**
     * Get available campaign types.
     */
    public static function getAvailableCampaignTypes(): array
    {
        return [
            self::TYPE_GENTLE => 'Gentle Reminders',
            self::TYPE_STANDARD => 'Standard Dunning',
            self::TYPE_AGGRESSIVE => 'Aggressive Collection',
            self::TYPE_LEGAL => 'Legal Preparation',
            self::TYPE_SETTLEMENT => 'Settlement Processing',
        ];
    }

    /**
     * Get available risk levels.
     */
    public static function getAvailableRiskLevels(): array
    {
        return [
            self::RISK_LOW => 'Low Risk',
            self::RISK_MEDIUM => 'Medium Risk',
            self::RISK_HIGH => 'High Risk',
            self::RISK_CRITICAL => 'Critical Risk',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (! $campaign->created_by) {
                $campaign->created_by = auth()->id() ?? 1;
            }
        });

        static::updating(function ($campaign) {
            $campaign->updated_by = auth()->id() ?? 1;
        });
    }
}
