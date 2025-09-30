<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * UsageRecord Model
 *
 * Individual usage transaction tracking for VoIP services including calls, data, messages.
 * Designed for high-volume CDR ingestion with optimized performance and real-time processing.
 *
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property int|null $invoice_id
 * @property int|null $usage_pool_id
 * @property int|null $usage_bucket_id
 * @property string $transaction_id
 * @property string|null $cdr_id
 * @property string $usage_type
 * @property string $service_type
 * @property float $quantity
 * @property string $unit_type
 * @property float|null $duration_seconds
 * @property string|null $origination_number
 * @property string|null $destination_number
 * @property \Carbon\Carbon $usage_start_time
 * @property \Carbon\Carbon|null $usage_end_time
 * @property float $unit_rate
 * @property float $base_cost
 * @property float $tax_amount
 * @property float $total_cost
 * @property string $processing_status
 * @property bool $is_billable
 * @property bool $is_validated
 */
class UsageRecord extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'usage_records';

    /**
     * The name of the "deleted at" column.
     */
    const DELETED_AT = 'archived_at';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'invoice_id',
        'usage_pool_id',
        'usage_bucket_id',
        'transaction_id',
        'cdr_id',
        'external_id',
        'batch_id',
        'usage_type',
        'service_type',
        'usage_category',
        'billing_category',
        'quantity',
        'unit_type',
        'duration_seconds',
        'line_count',
        'data_volume_mb',
        'origination_number',
        'destination_number',
        'origination_country',
        'destination_country',
        'origination_state',
        'destination_state',
        'route_type',
        'carrier_name',
        'usage_start_time',
        'usage_end_time',
        'time_zone',
        'is_peak_time',
        'is_weekend',
        'unit_rate',
        'base_cost',
        'markup_amount',
        'discount_amount',
        'tax_amount',
        'total_cost',
        'currency_code',
        'call_quality',
        'completion_status',
        'status_reason',
        'quality_score',
        'processing_status',
        'is_billable',
        'is_validated',
        'is_disputed',
        'is_fraud_flagged',
        'validation_notes',
        'is_pooled_usage',
        'allocated_from_pool',
        'overage_amount',
        'usage_date',
        'usage_hour',
        'billing_period',
        'is_aggregated',
        'protocol',
        'codec',
        'technical_metadata',
        'custom_attributes',
        'cdr_source',
        'cdr_received_at',
        'processed_at',
        'processing_version',
        'raw_cdr_data',
        'regulatory_classification',
        'requires_audit',
        'compliance_notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'invoice_id' => 'integer',
        'usage_pool_id' => 'integer',
        'usage_bucket_id' => 'integer',
        'quantity' => 'decimal:4',
        'duration_seconds' => 'decimal:2',
        'line_count' => 'integer',
        'data_volume_mb' => 'decimal:4',
        'is_peak_time' => 'boolean',
        'is_weekend' => 'boolean',
        'unit_rate' => 'decimal:6',
        'base_cost' => 'decimal:4',
        'markup_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'quality_score' => 'integer',
        'is_billable' => 'boolean',
        'is_validated' => 'boolean',
        'is_disputed' => 'boolean',
        'is_fraud_flagged' => 'boolean',
        'is_pooled_usage' => 'boolean',
        'allocated_from_pool' => 'decimal:4',
        'overage_amount' => 'decimal:4',
        'usage_date' => 'date',
        'usage_hour' => 'integer',
        'is_aggregated' => 'boolean',
        'technical_metadata' => 'array',
        'custom_attributes' => 'array',
        'usage_start_time' => 'datetime',
        'usage_end_time' => 'datetime',
        'cdr_received_at' => 'datetime',
        'processed_at' => 'datetime',
        'raw_cdr_data' => 'array',
        'requires_audit' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Usage type constants
     */
    const USAGE_TYPE_VOICE = 'voice';

    const USAGE_TYPE_DATA = 'data';

    const USAGE_TYPE_SMS = 'sms';

    const USAGE_TYPE_MMS = 'mms';

    const USAGE_TYPE_FEATURE = 'feature';

    const USAGE_TYPE_EQUIPMENT = 'equipment';

    const USAGE_TYPE_API = 'api';

    /**
     * Service type constants
     */
    const SERVICE_TYPE_LOCAL = 'local';

    const SERVICE_TYPE_LONG_DISTANCE = 'long_distance';

    const SERVICE_TYPE_INTERNATIONAL = 'international';

    const SERVICE_TYPE_HOSTED_PBX = 'hosted_pbx';

    const SERVICE_TYPE_SIP_TRUNKING = 'sip_trunking';

    const SERVICE_TYPE_DATA = 'data';

    const SERVICE_TYPE_INTERNET = 'internet';

    const SERVICE_TYPE_VOIP_FIXED = 'voip_fixed';

    const SERVICE_TYPE_VOIP_NOMADIC = 'voip_nomadic';

    /**
     * Processing status constants
     */
    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSED = 'processed';

    const STATUS_BILLED = 'billed';

    const STATUS_DISPUTED = 'disputed';

    const STATUS_FAILED = 'failed';

    /**
     * Unit type constants
     */
    const UNIT_TYPE_MINUTE = 'minute';

    const UNIT_TYPE_MB = 'mb';

    const UNIT_TYPE_GB = 'gb';

    const UNIT_TYPE_MESSAGE = 'message';

    const UNIT_TYPE_CALL = 'call';

    const UNIT_TYPE_LINE = 'line';

    const UNIT_TYPE_API_CALL = 'api_call';

    /**
     * Completion status constants
     */
    const COMPLETION_COMPLETED = 'completed';

    const COMPLETION_FAILED = 'failed';

    const COMPLETION_PARTIAL = 'partial';

    /**
     * Get the client that owns this usage record.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the invoice associated with this usage record.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the usage pool associated with this record.
     */
    public function usagePool(): BelongsTo
    {
        return $this->belongsTo(UsagePool::class);
    }

    /**
     * Get the usage bucket associated with this record.
     */
    public function usageBucket(): BelongsTo
    {
        return $this->belongsTo(UsageBucket::class);
    }

    /**
     * Check if this is a voice usage record.
     */
    public function isVoiceUsage(): bool
    {
        return $this->usage_type === self::USAGE_TYPE_VOICE;
    }

    /**
     * Check if this is a data usage record.
     */
    public function isDataUsage(): bool
    {
        return $this->usage_type === self::USAGE_TYPE_DATA;
    }

    /**
     * Check if this is an international call.
     */
    public function isInternational(): bool
    {
        return $this->service_type === self::SERVICE_TYPE_INTERNATIONAL;
    }

    /**
     * Check if usage occurred during peak hours.
     */
    public function isPeakUsage(): bool
    {
        return $this->is_peak_time;
    }

    /**
     * Check if usage occurred on weekend.
     */
    public function isWeekendUsage(): bool
    {
        return $this->is_weekend;
    }

    /**
     * Check if this record has been processed.
     */
    public function isProcessed(): bool
    {
        return $this->processing_status === self::STATUS_PROCESSED;
    }

    /**
     * Check if this record has been billed.
     */
    public function isBilled(): bool
    {
        return $this->processing_status === self::STATUS_BILLED;
    }

    /**
     * Check if this record is disputed.
     */
    public function isDisputed(): bool
    {
        return $this->is_disputed;
    }

    /**
     * Check if this record is flagged for fraud.
     */
    public function isFraudFlagged(): bool
    {
        return $this->is_fraud_flagged;
    }

    /**
     * Get the formatted duration.
     */
    public function getFormattedDuration(): string
    {
        if (! $this->duration_seconds) {
            return '0:00';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get the formatted cost.
     */
    public function getFormattedCost(): string
    {
        return '$'.number_format($this->total_cost, 4);
    }

    /**
     * Get the quality description.
     */
    public function getQualityDescription(): string
    {
        if (! $this->quality_score) {
            return 'Unknown';
        }

        if ($this->quality_score >= 90) {
            return 'Excellent';
        } elseif ($this->quality_score >= 80) {
            return 'Good';
        } elseif ($this->quality_score >= 70) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }

    /**
     * Calculate usage duration from start and end times.
     */
    public function calculateDuration(): void
    {
        if ($this->usage_start_time && $this->usage_end_time) {
            $this->duration_seconds = $this->usage_end_time->diffInSeconds($this->usage_start_time);
        }
    }

    /**
     * Mark record as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processing_status' => self::STATUS_PROCESSED,
            'processed_at' => now(),
            'is_validated' => true,
        ]);
    }

    /**
     * Mark record as billed.
     */
    public function markAsBilled(int $invoiceId): void
    {
        $this->update([
            'processing_status' => self::STATUS_BILLED,
            'invoice_id' => $invoiceId,
            'is_aggregated' => true,
        ]);
    }

    /**
     * Flag record as disputed.
     */
    public function flagAsDisputed(?string $reason = null): void
    {
        $this->update([
            'processing_status' => self::STATUS_DISPUTED,
            'is_disputed' => true,
            'validation_notes' => $reason,
        ]);
    }

    /**
     * Apply usage to pool allocation.
     */
    public function applyToPool(float $amount): void
    {
        $this->update([
            'is_pooled_usage' => true,
            'allocated_from_pool' => $amount,
            'overage_amount' => max(0, $this->quantity - $amount),
        ]);
    }

    /**
     * Scope to get billable usage records.
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope to get processed records.
     */
    public function scopeProcessed($query)
    {
        return $query->where('processing_status', self::STATUS_PROCESSED);
    }

    /**
     * Scope to get pending records.
     */
    public function scopePending($query)
    {
        return $query->where('processing_status', self::STATUS_PENDING);
    }

    /**
     * Scope to get records by usage type.
     */
    public function scopeByUsageType($query, string $usageType)
    {
        return $query->where('usage_type', $usageType);
    }

    /**
     * Scope to get records by service type.
     */
    public function scopeByServiceType($query, string $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Scope to get records for a date range.
     */
    public function scopeDateRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('usage_start_time', [$start, $end]);
    }

    /**
     * Scope to get records for a specific billing period.
     */
    public function scopeBillingPeriod($query, string $billingPeriod)
    {
        return $query->where('billing_period', $billingPeriod);
    }

    /**
     * Scope to get disputed records.
     */
    public function scopeDisputed($query)
    {
        return $query->where('is_disputed', true);
    }

    /**
     * Scope to get fraud flagged records.
     */
    public function scopeFraudFlagged($query)
    {
        return $query->where('is_fraud_flagged', true);
    }

    /**
     * Scope to get international usage.
     */
    public function scopeInternational($query)
    {
        return $query->where('service_type', self::SERVICE_TYPE_INTERNATIONAL);
    }

    /**
     * Scope to get peak hour usage.
     */
    public function scopePeakHours($query)
    {
        return $query->where('is_peak_time', true);
    }

    /**
     * Get available usage types.
     */
    public static function getUsageTypes(): array
    {
        return [
            self::USAGE_TYPE_VOICE => 'Voice',
            self::USAGE_TYPE_DATA => 'Data',
            self::USAGE_TYPE_SMS => 'SMS',
            self::USAGE_TYPE_MMS => 'MMS',
            self::USAGE_TYPE_FEATURE => 'Feature',
            self::USAGE_TYPE_EQUIPMENT => 'Equipment',
            self::USAGE_TYPE_API => 'API',
        ];
    }

    /**
     * Get available service types.
     */
    public static function getServiceTypes(): array
    {
        return [
            self::SERVICE_TYPE_LOCAL => 'Local',
            self::SERVICE_TYPE_LONG_DISTANCE => 'Long Distance',
            self::SERVICE_TYPE_INTERNATIONAL => 'International',
            self::SERVICE_TYPE_HOSTED_PBX => 'Hosted PBX',
            self::SERVICE_TYPE_SIP_TRUNKING => 'SIP Trunking',
            self::SERVICE_TYPE_DATA => 'Data',
            self::SERVICE_TYPE_INTERNET => 'Internet',
            self::SERVICE_TYPE_VOIP_FIXED => 'VoIP Fixed',
            self::SERVICE_TYPE_VOIP_NOMADIC => 'VoIP Nomadic',
        ];
    }

    /**
     * Get processing statuses.
     */
    public static function getProcessingStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSED => 'Processed',
            self::STATUS_BILLED => 'Billed',
            self::STATUS_DISPUTED => 'Disputed',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate duration and set usage date
        static::creating(function ($record) {
            if (! $record->transaction_id) {
                $record->transaction_id = 'TXN-'.uniqid().'-'.time();
            }

            if ($record->usage_start_time) {
                $record->usage_date = $record->usage_start_time->toDateString();
                $record->usage_hour = $record->usage_start_time->hour;

                // Set time-based flags
                $record->is_peak_time = $record->usage_start_time->hour >= 8 && $record->usage_start_time->hour < 18;
                $record->is_weekend = $record->usage_start_time->isWeekend();
            }

            if ($record->usage_start_time && $record->usage_end_time && ! $record->duration_seconds) {
                $record->duration_seconds = $record->usage_end_time->diffInSeconds($record->usage_start_time);
            }

            // Set billing period if not provided
            if (! $record->billing_period) {
                $record->billing_period = now()->format('Y-m');
            }
        });

        // Update processed timestamp when status changes
        static::updated(function ($record) {
            if ($record->isDirty('processing_status') && $record->processing_status === self::STATUS_PROCESSED) {
                $record->processed_at = now();
                $record->save();
            }
        });
    }
}
