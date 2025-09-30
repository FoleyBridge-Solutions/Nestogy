<?php

namespace Foleybridge\Nestogy\Domains\Report\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Report Model
 *
 * Represents saved reports with scheduling, sharing, and template capabilities
 * for comprehensive business intelligence and recurring report generation.
 */
class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'report_type',
        'report_config',
        'template_id',
        'created_by',
        'is_public',
        'is_scheduled',
        'schedule_type',
        'schedule_config',
        'last_generated_at',
        'next_generation_at',
        'recipients',
        'export_format',
        'status',
        'metadata',
    ];

    protected $casts = [
        'report_config' => 'array',
        'schedule_config' => 'array',
        'recipients' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'is_scheduled' => 'boolean',
        'last_generated_at' => 'datetime',
        'next_generation_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Report type constants
     */
    const TYPE_FINANCIAL = 'financial';

    const TYPE_TICKETS = 'tickets';

    const TYPE_ASSETS = 'assets';

    const TYPE_CLIENTS = 'clients';

    const TYPE_PROJECTS = 'projects';

    const TYPE_USERS = 'users';

    const TYPE_CUSTOM = 'custom';

    const TYPE_DASHBOARD = 'dashboard';

    /**
     * Schedule type constants
     */
    const SCHEDULE_DAILY = 'daily';

    const SCHEDULE_WEEKLY = 'weekly';

    const SCHEDULE_MONTHLY = 'monthly';

    const SCHEDULE_QUARTERLY = 'quarterly';

    const SCHEDULE_ANNUALLY = 'annually';

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_PAUSED = 'paused';

    const STATUS_ARCHIVED = 'archived';

    /**
     * Export format constants
     */
    const FORMAT_PDF = 'pdf';

    const FORMAT_CSV = 'csv';

    const FORMAT_XLSX = 'xlsx';

    const FORMAT_JSON = 'json';

    /**
     * Get the user who created this report
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the template this report is based on
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    /**
     * Get all generations of this report
     */
    public function generations(): HasMany
    {
        return $this->hasMany(ReportGeneration::class);
    }

    /**
     * Get all sharing settings for this report
     */
    public function shares(): HasMany
    {
        return $this->hasMany(ReportShare::class);
    }

    /**
     * Get the latest generation
     */
    public function latestGeneration()
    {
        return $this->generations()->latest()->first();
    }

    /**
     * Check if report is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->is_scheduled && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if report is overdue for generation
     */
    public function isOverdue(): bool
    {
        return $this->isScheduled() &&
               $this->next_generation_at &&
               $this->next_generation_at->isPast();
    }

    /**
     * Calculate next generation time based on schedule
     */
    public function calculateNextGeneration(): ?Carbon
    {
        if (! $this->isScheduled()) {
            return null;
        }

        $lastGeneration = $this->last_generated_at ?: now();

        return match ($this->schedule_type) {
            self::SCHEDULE_DAILY => $lastGeneration->addDay(),
            self::SCHEDULE_WEEKLY => $lastGeneration->addWeek(),
            self::SCHEDULE_MONTHLY => $lastGeneration->addMonth(),
            self::SCHEDULE_QUARTERLY => $lastGeneration->addMonths(3),
            self::SCHEDULE_ANNUALLY => $lastGeneration->addYear(),
            default => null
        };
    }

    /**
     * Mark report as generated
     */
    public function markAsGenerated(): void
    {
        $this->update([
            'last_generated_at' => now(),
            'next_generation_at' => $this->calculateNextGeneration(),
        ]);
    }

    /**
     * Get report configuration with defaults
     */
    public function getConfigurationAttribute(): array
    {
        return array_merge([
            'date_range' => ['start' => now()->subDays(30), 'end' => now()],
            'filters' => [],
            'metrics' => [],
            'grouping' => null,
            'chart_type' => 'bar',
            'show_charts' => true,
            'show_tables' => true,
            'show_summary' => true,
        ], $this->report_config ?? []);
    }

    /**
     * Get schedule configuration with defaults
     */
    public function getScheduleConfigurationAttribute(): array
    {
        return array_merge([
            'time' => '08:00',
            'timezone' => config('app.timezone'),
            'day_of_week' => 1, // Monday
            'day_of_month' => 1,
            'send_email' => true,
            'attach_file' => true,
        ], $this->schedule_config ?? []);
    }

    /**
     * Get formatted schedule description
     */
    public function getScheduleDescriptionAttribute(): string
    {
        if (! $this->isScheduled()) {
            return 'Not scheduled';
        }

        $config = $this->getScheduleConfigurationAttribute();

        return match ($this->schedule_type) {
            self::SCHEDULE_DAILY => "Daily at {$config['time']}",
            self::SCHEDULE_WEEKLY => 'Weekly on '.Carbon::create()->dayOfWeek($config['day_of_week'])->format('l')." at {$config['time']}",
            self::SCHEDULE_MONTHLY => "Monthly on day {$config['day_of_month']} at {$config['time']}",
            self::SCHEDULE_QUARTERLY => "Quarterly at {$config['time']}",
            self::SCHEDULE_ANNUALLY => "Annually at {$config['time']}",
            default => 'Custom schedule'
        };
    }

    /**
     * Clone report with new name
     */
    public function duplicate(?string $newName = null): self
    {
        $data = $this->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $data['name'] = $newName ?: $this->name.' (Copy)';
        $data['status'] = self::STATUS_DRAFT;
        $data['last_generated_at'] = null;
        $data['next_generation_at'] = null;

        return self::create($data);
    }

    /**
     * Scope for active reports
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for scheduled reports
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true)->active();
    }

    /**
     * Scope for overdue reports
     */
    public function scopeOverdue($query)
    {
        return $query->scheduled()
            ->where('next_generation_at', '<', now());
    }

    /**
     * Scope for public reports
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for reports by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope for reports created by user
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Get available report types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_FINANCIAL => 'Financial Reports',
            self::TYPE_TICKETS => 'Ticket Analytics',
            self::TYPE_ASSETS => 'Asset Reports',
            self::TYPE_CLIENTS => 'Client Reports',
            self::TYPE_PROJECTS => 'Project Reports',
            self::TYPE_USERS => 'User Reports',
            self::TYPE_CUSTOM => 'Custom Reports',
            self::TYPE_DASHBOARD => 'Dashboard Reports',
        ];
    }

    /**
     * Get available schedule types
     */
    public static function getScheduleTypes(): array
    {
        return [
            self::SCHEDULE_DAILY => 'Daily',
            self::SCHEDULE_WEEKLY => 'Weekly',
            self::SCHEDULE_MONTHLY => 'Monthly',
            self::SCHEDULE_QUARTERLY => 'Quarterly',
            self::SCHEDULE_ANNUALLY => 'Annually',
        ];
    }

    /**
     * Get available export formats
     */
    public static function getExportFormats(): array
    {
        return [
            self::FORMAT_PDF => 'PDF',
            self::FORMAT_CSV => 'CSV',
            self::FORMAT_XLSX => 'Excel',
            self::FORMAT_JSON => 'JSON',
        ];
    }

    /**
     * Get validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'report_type' => 'required|in:'.implode(',', array_keys(self::getAvailableTypes())),
            'report_config' => 'required|array',
            'template_id' => 'nullable|exists:report_templates,id',
            'is_public' => 'boolean',
            'is_scheduled' => 'boolean',
            'schedule_type' => 'nullable|required_if:is_scheduled,true|in:'.implode(',', array_keys(self::getScheduleTypes())),
            'schedule_config' => 'nullable|required_if:is_scheduled,true|array',
            'recipients' => 'nullable|array',
            'recipients.*' => 'email',
            'export_format' => 'required|in:'.implode(',', array_keys(self::getExportFormats())),
            'status' => 'required|in:draft,active,paused,archived',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (! $report->created_by) {
                $report->created_by = auth()->id();
            }

            if ($report->isScheduled() && ! $report->next_generation_at) {
                $report->next_generation_at = $report->calculateNextGeneration();
            }
        });

        static::updating(function ($report) {
            if ($report->isDirty(['is_scheduled', 'schedule_type', 'schedule_config'])) {
                if ($report->isScheduled()) {
                    $report->next_generation_at = $report->calculateNextGeneration();
                } else {
                    $report->next_generation_at = null;
                }
            }
        });
    }
}
