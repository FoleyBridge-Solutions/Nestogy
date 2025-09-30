<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FinancialReport Model
 *
 * Manages scheduled and on-demand financial reports with automated generation
 * and delivery capabilities.
 *
 * @property int $id
 * @property int $company_id
 * @property string $report_type
 * @property string $report_name
 * @property string|null $description
 * @property string $status
 * @property string $frequency
 * @property array|null $schedule_config
 * @property array|null $filters
 * @property array|null $metrics
 * @property string $format
 * @property array|null $recipients
 * @property array|null $parameters
 * @property \Illuminate\Support\Carbon|null $last_generated_at
 * @property \Illuminate\Support\Carbon|null $next_generation_at
 * @property string|null $file_path
 * @property int|null $file_size
 * @property bool $is_active
 * @property bool $auto_deliver
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class FinancialReport extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'financial_reports';

    protected $fillable = [
        'company_id',
        'report_type',
        'report_name',
        'description',
        'status',
        'frequency',
        'schedule_config',
        'filters',
        'metrics',
        'format',
        'recipients',
        'parameters',
        'last_generated_at',
        'next_generation_at',
        'file_path',
        'file_size',
        'is_active',
        'auto_deliver',
        'created_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'schedule_config' => 'array',
        'filters' => 'array',
        'metrics' => 'array',
        'recipients' => 'array',
        'parameters' => 'array',
        'last_generated_at' => 'datetime',
        'next_generation_at' => 'datetime',
        'file_size' => 'integer',
        'is_active' => 'boolean',
        'auto_deliver' => 'boolean',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    const DELETED_AT = 'archived_at';

    // Report Types
    const TYPE_DAILY = 'daily';

    const TYPE_WEEKLY = 'weekly';

    const TYPE_MONTHLY = 'monthly';

    const TYPE_QUARTERLY = 'quarterly';

    const TYPE_ANNUAL = 'annual';

    const TYPE_CUSTOM = 'custom';

    // Status
    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_GENERATING = 'generating';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    // Frequency
    const FREQUENCY_ONCE = 'once';

    const FREQUENCY_DAILY = 'daily';

    const FREQUENCY_WEEKLY = 'weekly';

    const FREQUENCY_MONTHLY = 'monthly';

    const FREQUENCY_QUARTERLY = 'quarterly';

    const FREQUENCY_ANNUALLY = 'annually';

    // Formats
    const FORMAT_PDF = 'pdf';

    const FORMAT_EXCEL = 'excel';

    const FORMAT_CSV = 'csv';

    const FORMAT_JSON = 'json';

    /**
     * Get the user who created this report
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if report is due for generation
     */
    public function isDueForGeneration(): bool
    {
        return $this->is_active &&
               $this->next_generation_at &&
               Carbon::now()->gte($this->next_generation_at);
    }

    /**
     * Mark report generation as started
     */
    public function markAsGenerating(): void
    {
        $this->update(['status' => self::STATUS_GENERATING]);
    }

    /**
     * Mark report generation as completed
     */
    public function markAsCompleted(string $filePath, int $fileSize): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'last_generated_at' => now(),
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'next_generation_at' => $this->calculateNextGeneration(),
        ]);
    }

    /**
     * Mark report generation as failed
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Calculate next generation time based on frequency
     */
    public function calculateNextGeneration(): ?Carbon
    {
        if ($this->frequency === self::FREQUENCY_ONCE) {
            return null;
        }

        $base = $this->last_generated_at ?? now();

        return match ($this->frequency) {
            self::FREQUENCY_DAILY => $base->copy()->addDay(),
            self::FREQUENCY_WEEKLY => $base->copy()->addWeek(),
            self::FREQUENCY_MONTHLY => $base->copy()->addMonth(),
            self::FREQUENCY_QUARTERLY => $base->copy()->addQuarter(),
            self::FREQUENCY_ANNUALLY => $base->copy()->addYear(),
            default => null,
        };
    }

    /**
     * Get file size in human readable format
     */
    public function getFormattedFileSize(): string
    {
        if (! $this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2).' '.$units[$unitIndex];
    }

    /**
     * Scope for active reports
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for reports due for generation
     */
    public function scopeDueForGeneration($query)
    {
        return $query->active()
            ->where('next_generation_at', '<=', now())
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_FAILED]);
    }

    /**
     * Scope by report type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Get available report types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_DAILY => 'Daily Report',
            self::TYPE_WEEKLY => 'Weekly Report',
            self::TYPE_MONTHLY => 'Monthly Report',
            self::TYPE_QUARTERLY => 'Quarterly Report',
            self::TYPE_ANNUAL => 'Annual Report',
            self::TYPE_CUSTOM => 'Custom Report',
        ];
    }

    /**
     * Get available frequencies
     */
    public static function getAvailableFrequencies(): array
    {
        return [
            self::FREQUENCY_ONCE => 'One Time',
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_QUARTERLY => 'Quarterly',
            self::FREQUENCY_ANNUALLY => 'Annually',
        ];
    }

    /**
     * Get available formats
     */
    public static function getAvailableFormats(): array
    {
        return [
            self::FORMAT_PDF => 'PDF',
            self::FORMAT_EXCEL => 'Excel',
            self::FORMAT_CSV => 'CSV',
            self::FORMAT_JSON => 'JSON',
        ];
    }
}
