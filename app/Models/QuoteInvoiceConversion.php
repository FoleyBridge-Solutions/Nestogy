<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * QuoteInvoiceConversion Model
 * 
 * Tracks the conversion process from quotes to invoices with contract generation,
 * milestone billing, recurring setups, and comprehensive audit trails.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $quote_id
 * @property int|null $invoice_id
 * @property int|null $contract_id
 * @property string $conversion_type
 * @property string $status
 * @property array|null $conversion_settings
 * @property array|null $pricing_adjustments
 * @property array|null $tax_calculations
 * @property array|null $milestone_schedule
 * @property array|null $recurring_schedule
 * @property bool $requires_service_activation
 * @property array|null $service_activation_data
 * @property string $activation_status
 * @property \Illuminate\Support\Carbon|null $service_activated_at
 * @property array|null $voip_service_mapping
 * @property array|null $equipment_allocation
 * @property array|null $porting_requirements
 * @property array|null $compliance_mappings
 * @property float $original_quote_value
 * @property float|null $converted_value
 * @property float $adjustment_amount
 * @property string|null $adjustment_reason
 * @property string $currency_code
 * @property float|null $exchange_rate
 * @property \Illuminate\Support\Carbon|null $rate_locked_at
 * @property array|null $revenue_schedule
 * @property float $deferred_revenue
 * @property float $recognized_revenue
 * @property array|null $conversion_workflow
 * @property array|null $approval_chain
 * @property array|null $completed_steps
 * @property int $current_step
 * @property int $total_steps
 * @property array|null $error_log
 * @property int $retry_count
 * @property int $max_retries
 * @property \Illuminate\Support\Carbon|null $last_retry_at
 * @property \Illuminate\Support\Carbon|null $next_retry_at
 * @property array|null $integration_data
 * @property bool $automated_conversion
 * @property array|null $automation_rules
 * @property string|null $batch_id
 * @property \Illuminate\Support\Carbon|null $conversion_started_at
 * @property \Illuminate\Support\Carbon|null $conversion_completed_at
 * @property int|null $processing_duration
 * @property array|null $performance_metrics
 * @property array|null $audit_trail
 * @property array|null $compliance_checks
 * @property bool $regulatory_approved
 * @property string|null $compliance_notes
 * @property int $initiated_by
 * @property int|null $completed_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class QuoteInvoiceConversion extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'quote_invoice_conversions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'quote_id',
        'invoice_id',
        'contract_id',
        'conversion_type',
        'status',
        'conversion_settings',
        'pricing_adjustments',
        'tax_calculations',
        'milestone_schedule',
        'recurring_schedule',
        'requires_service_activation',
        'service_activation_data',
        'activation_status',
        'service_activated_at',
        'voip_service_mapping',
        'equipment_allocation',
        'porting_requirements',
        'compliance_mappings',
        'original_quote_value',
        'converted_value',
        'adjustment_amount',
        'adjustment_reason',
        'currency_code',
        'exchange_rate',
        'rate_locked_at',
        'revenue_schedule',
        'deferred_revenue',
        'recognized_revenue',
        'conversion_workflow',
        'approval_chain',
        'completed_steps',
        'current_step',
        'total_steps',
        'error_log',
        'retry_count',
        'max_retries',
        'last_retry_at',
        'next_retry_at',
        'integration_data',
        'automated_conversion',
        'automation_rules',
        'batch_id',
        'conversion_started_at',
        'conversion_completed_at',
        'processing_duration',
        'performance_metrics',
        'audit_trail',
        'compliance_checks',
        'regulatory_approved',
        'compliance_notes',
        'initiated_by',
        'completed_by',
        'approved_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'quote_id' => 'integer',
        'invoice_id' => 'integer',
        'contract_id' => 'integer',
        'conversion_settings' => 'array',
        'pricing_adjustments' => 'array',
        'tax_calculations' => 'array',
        'milestone_schedule' => 'array',
        'recurring_schedule' => 'array',
        'requires_service_activation' => 'boolean',
        'service_activation_data' => 'array',
        'service_activated_at' => 'datetime',
        'voip_service_mapping' => 'array',
        'equipment_allocation' => 'array',
        'porting_requirements' => 'array',
        'compliance_mappings' => 'array',
        'original_quote_value' => 'decimal:2',
        'converted_value' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'rate_locked_at' => 'datetime',
        'revenue_schedule' => 'array',
        'deferred_revenue' => 'decimal:2',
        'recognized_revenue' => 'decimal:2',
        'conversion_workflow' => 'array',
        'approval_chain' => 'array',
        'completed_steps' => 'array',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'error_log' => 'array',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'last_retry_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'integration_data' => 'array',
        'automated_conversion' => 'boolean',
        'automation_rules' => 'array',
        'conversion_started_at' => 'datetime',
        'conversion_completed_at' => 'datetime',
        'processing_duration' => 'integer',
        'performance_metrics' => 'array',
        'audit_trail' => 'array',
        'compliance_checks' => 'array',
        'regulatory_approved' => 'boolean',
        'initiated_by' => 'integer',
        'completed_by' => 'integer',
        'approved_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Conversion type enumeration
     */
    const TYPE_DIRECT_INVOICE = 'direct_invoice';
    const TYPE_CONTRACT_WITH_INVOICE = 'contract_with_invoice';
    const TYPE_MILESTONE_INVOICING = 'milestone_invoicing';
    const TYPE_RECURRING_SETUP = 'recurring_setup';
    const TYPE_HYBRID_CONVERSION = 'hybrid_conversion';

    /**
     * Conversion status enumeration
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_CONTRACT_GENERATED = 'contract_generated';
    const STATUS_CONTRACT_SIGNED = 'contract_signed';
    const STATUS_INVOICE_GENERATED = 'invoice_generated';
    const STATUS_RECURRING_SETUP = 'recurring_setup';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Service activation status enumeration
     */
    const ACTIVATION_NOT_REQUIRED = 'not_required';
    const ACTIVATION_PENDING = 'pending';
    const ACTIVATION_IN_PROGRESS = 'in_progress';
    const ACTIVATION_COMPLETED = 'completed';
    const ACTIVATION_FAILED = 'failed';

    /**
     * Get the quote being converted.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the generated invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the generated contract.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the user who initiated the conversion.
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the user who completed the conversion.
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get the user who approved the conversion.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if conversion is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if conversion failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if conversion is in progress.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [
            self::STATUS_PROCESSING,
            self::STATUS_CONTRACT_GENERATED,
            self::STATUS_CONTRACT_SIGNED,
            self::STATUS_INVOICE_GENERATED,
            self::STATUS_RECURRING_SETUP,
        ]);
    }

    /**
     * Check if conversion requires service activation.
     */
    public function requiresServiceActivation(): bool
    {
        return $this->requires_service_activation;
    }

    /**
     * Check if service is activated.
     */
    public function isServiceActivated(): bool
    {
        return $this->activation_status === self::ACTIVATION_COMPLETED;
    }

    /**
     * Get conversion progress percentage.
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_steps === 0) {
            return 0;
        }

        return intval(($this->current_step / $this->total_steps) * 100);
    }

    /**
     * Get processing duration in seconds.
     */
    public function getProcessingDuration(): ?int
    {
        if (!$this->conversion_started_at) {
            return null;
        }

        $endTime = $this->conversion_completed_at ?? now();
        return $this->conversion_started_at->diffInSeconds($endTime);
    }

    /**
     * Get conversion value variance.
     */
    public function getValueVariance(): ?float
    {
        if (!$this->converted_value) {
            return null;
        }

        return $this->converted_value - $this->original_quote_value + $this->adjustment_amount;
    }

    /**
     * Get conversion value variance percentage.
     */
    public function getValueVariancePercentage(): ?float
    {
        $variance = $this->getValueVariance();
        if ($variance === null || $this->original_quote_value == 0) {
            return null;
        }

        return ($variance / $this->original_quote_value) * 100;
    }

    /**
     * Add step to conversion workflow.
     */
    public function addWorkflowStep(string $step, string $description, array $data = []): void
    {
        $workflow = $this->conversion_workflow ?? [];
        $workflow[] = [
            'step' => $step,
            'description' => $description,
            'data' => $data,
            'timestamp' => now(),
            'user_id' => auth()->id(),
        ];

        $this->update(['conversion_workflow' => $workflow]);
    }

    /**
     * Mark step as completed.
     */
    public function completeStep(string $step): void
    {
        $completedSteps = $this->completed_steps ?? [];
        if (!in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
            
            $this->update([
                'completed_steps' => $completedSteps,
                'current_step' => count($completedSteps),
            ]);
        }
    }

    /**
     * Log error.
     */
    public function logError(string $error, array $context = []): void
    {
        $errorLog = $this->error_log ?? [];
        $errorLog[] = [
            'error' => $error,
            'context' => $context,
            'timestamp' => now(),
            'user_id' => auth()->id(),
        ];

        $this->update(['error_log' => $errorLog]);
    }

    /**
     * Schedule retry.
     */
    public function scheduleRetry(int $delayMinutes = 30): void
    {
        if ($this->retry_count >= $this->max_retries) {
            $this->update(['status' => self::STATUS_FAILED]);
            return;
        }

        $this->update([
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
            'next_retry_at' => now()->addMinutes($delayMinutes),
        ]);
    }

    /**
     * Add to audit trail.
     */
    public function addToAuditTrail(string $action, array $data = []): void
    {
        $auditTrail = $this->audit_trail ?? [];
        $auditTrail[] = [
            'action' => $action,
            'data' => $data,
            'timestamp' => now(),
            'user_id' => auth()->id(),
        ];

        $this->update(['audit_trail' => $auditTrail]);
    }

    /**
     * Update conversion status with audit trail.
     */
    public function updateStatus(string $status, string $reason = null): void
    {
        $oldStatus = $this->status;
        
        $this->update(['status' => $status]);
        
        $this->addToAuditTrail('status_changed', [
            'old_status' => $oldStatus,
            'new_status' => $status,
            'reason' => $reason,
        ]);
    }

    /**
     * Calculate revenue recognition.
     */
    public function updateRevenueRecognition(): void
    {
        if (!$this->revenue_schedule) {
            return;
        }

        $totalRecognized = 0;
        $currentDate = now();

        foreach ($this->revenue_schedule as $schedule) {
            $recognitionDate = Carbon::parse($schedule['recognition_date']);
            if ($recognitionDate->lte($currentDate)) {
                $totalRecognized += $schedule['amount'];
            }
        }

        $this->update([
            'recognized_revenue' => $totalRecognized,
            'deferred_revenue' => max(0, $this->converted_value - $totalRecognized),
        ]);
    }

    /**
     * Get formatted original quote value.
     */
    public function getFormattedOriginalValue(): string
    {
        return $this->formatCurrency($this->original_quote_value);
    }

    /**
     * Get formatted converted value.
     */
    public function getFormattedConvertedValue(): string
    {
        return $this->formatCurrency($this->converted_value ?? 0);
    }

    /**
     * Format currency amount.
     */
    protected function formatCurrency(float $amount): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
        ];

        $symbol = $symbols[$this->currency_code] ?? $this->currency_code;
        return $symbol . number_format($amount, 2);
    }

    /**
     * Scope to get conversions by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get completed conversions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get failed conversions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get conversions needing retry.
     */
    public function scopeNeedsRetry($query)
    {
        return $query->where('next_retry_at', '<=', now())
                    ->where('retry_count', '<', DB::raw('max_retries'))
                    ->whereIn('status', [self::STATUS_FAILED, self::STATUS_PROCESSING]);
    }

    /**
     * Scope to get conversions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('conversion_type', $type);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set defaults when creating
        static::creating(function ($conversion) {
            if (!$conversion->status) {
                $conversion->status = self::STATUS_PENDING;
            }

            if (!$conversion->activation_status) {
                $conversion->activation_status = self::ACTIVATION_NOT_REQUIRED;
            }

            if (!$conversion->current_step) {
                $conversion->current_step = 1;
            }

            if (!$conversion->total_steps) {
                $conversion->total_steps = $conversion->calculateTotalSteps();
            }

            if (!$conversion->max_retries) {
                $conversion->max_retries = 3;
            }

            if (!$conversion->retry_count) {
                $conversion->retry_count = 0;
            }

            if (!$conversion->deferred_revenue) {
                $conversion->deferred_revenue = 0;
            }

            if (!$conversion->recognized_revenue) {
                $conversion->recognized_revenue = 0;
            }

            if (!$conversion->adjustment_amount) {
                $conversion->adjustment_amount = 0;
            }

            // Initialize audit trail
            $conversion->audit_trail = [[
                'action' => 'conversion_created',
                'data' => [
                    'conversion_type' => $conversion->conversion_type,
                    'original_value' => $conversion->original_quote_value,
                ],
                'timestamp' => now(),
                'user_id' => $conversion->initiated_by,
            ]];
        });

        // Update processing duration when completed
        static::updating(function ($conversion) {
            if ($conversion->isDirty('status') && $conversion->status === self::STATUS_COMPLETED) {
                if ($conversion->conversion_started_at && !$conversion->processing_duration) {
                    $conversion->processing_duration = $conversion->conversion_started_at->diffInSeconds(now());
                    $conversion->conversion_completed_at = now();
                }
            }
        });
    }

    /**
     * Calculate total steps based on conversion type.
     */
    protected function calculateTotalSteps(): int
    {
        switch ($this->conversion_type) {
            case self::TYPE_DIRECT_INVOICE:
                return 2; // validate, convert
            case self::TYPE_CONTRACT_WITH_INVOICE:
                return 4; // validate, contract, sign, invoice
            case self::TYPE_MILESTONE_INVOICING:
                return 3; // validate, contract, milestones
            case self::TYPE_RECURRING_SETUP:
                return 5; // validate, contract, sign, recurring, initial_invoice
            case self::TYPE_HYBRID_CONVERSION:
                return 6; // validate, contracts, invoices, recurring, integration
            default:
                return 1;
        }
    }
}