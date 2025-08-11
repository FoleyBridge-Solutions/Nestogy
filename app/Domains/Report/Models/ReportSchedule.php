<?php

namespace App\Domains\Report\Models;

use App\Models\BaseModel;
use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Report Schedule Model
 * 
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string $report_type
 * @property string $frequency
 * @property array|null $parameters
 * @property array $recipients
 * @property string $format
 * @property bool $is_active
 * @property \Carbon\Carbon $next_run_at
 * @property string $timezone
 * @property array|null $delivery_options
 * @property \Carbon\Carbon|null $last_run_at
 * @property array|null $last_run_result
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ReportSchedule extends BaseModel
{
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'report_type',
        'frequency',
        'parameters',
        'recipients',
        'format',
        'is_active',
        'next_run_at',
        'timezone',
        'delivery_options',
        'last_run_at',
        'last_run_result',
    ];

    protected $casts = [
        'parameters' => 'array',
        'recipients' => 'array',
        'delivery_options' => 'array',
        'last_run_result' => 'array',
        'is_active' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';

    const FORMAT_PDF = 'pdf';
    const FORMAT_EXCEL = 'excel';
    const FORMAT_CSV = 'csv';

    /**
     * Company relationship
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope for active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for schedules due to run
     */
    public function scopeDue($query)
    {
        return $query->where('next_run_at', '<=', now());
    }

    /**
     * Scope for schedules by frequency
     */
    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Scope for schedules by report type
     */
    public function scopeByReportType($query, string $reportType)
    {
        return $query->where('report_type', $reportType);
    }

    /**
     * Check if the schedule is due to run
     */
    public function isDue(): bool
    {
        return $this->is_active && $this->next_run_at <= now();
    }

    /**
     * Get the next run date as a formatted string
     */
    public function getNextRunFormattedAttribute(): string
    {
        return $this->next_run_at?->format('M j, Y g:i A') ?? 'Not scheduled';
    }

    /**
     * Get the last run date as a formatted string
     */
    public function getLastRunFormattedAttribute(): ?string
    {
        return $this->last_run_at?->format('M j, Y g:i A');
    }

    /**
     * Get frequency label
     */
    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_QUARTERLY => 'Quarterly',
            default => ucfirst($this->frequency),
        };
    }

    /**
     * Get report type label
     */
    public function getReportTypeLabelAttribute(): string
    {
        return match ($this->report_type) {
            'executive_dashboard' => 'Executive Dashboard',
            'qbr' => 'Quarterly Business Review',
            'client_health' => 'Client Health Scorecard',
            'sla_report' => 'SLA Report',
            'financial_summary' => 'Financial Summary',
            'service_metrics' => 'Service Metrics',
            default => str_replace('_', ' ', title_case($this->report_type)),
        };
    }

    /**
     * Get format label
     */
    public function getFormatLabelAttribute(): string
    {
        return match ($this->format) {
            self::FORMAT_PDF => 'PDF Document',
            self::FORMAT_EXCEL => 'Excel Spreadsheet',
            self::FORMAT_CSV => 'CSV File',
            default => strtoupper($this->format),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active) {
            return 'gray';
        }

        if ($this->isDue()) {
            return 'red';
        }

        if ($this->next_run_at <= now()->addDay()) {
            return 'yellow';
        }

        return 'green';
    }

    /**
     * Get recipients count
     */
    public function getRecipientsCountAttribute(): int
    {
        return count($this->recipients ?? []);
    }

    /**
     * Mark as executed
     */
    public function markAsExecuted(array $result = []): void
    {
        $this->update([
            'last_run_at' => now(),
            'last_run_result' => $result,
        ]);
    }

    /**
     * Get available report types
     */
    public static function getAvailableReportTypes(): array
    {
        return [
            'executive_dashboard' => 'Executive Dashboard',
            'qbr' => 'Quarterly Business Review',
            'client_health' => 'Client Health Scorecard',
            'sla_report' => 'SLA Report',
            'financial_summary' => 'Financial Summary',
            'service_metrics' => 'Service Metrics',
        ];
    }

    /**
     * Get available frequencies
     */
    public static function getAvailableFrequencies(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_QUARTERLY => 'Quarterly',
        ];
    }

    /**
     * Get available formats
     */
    public static function getAvailableFormats(): array
    {
        return [
            self::FORMAT_PDF => 'PDF Document',
            self::FORMAT_EXCEL => 'Excel Spreadsheet',
            self::FORMAT_CSV => 'CSV File',
        ];
    }
}