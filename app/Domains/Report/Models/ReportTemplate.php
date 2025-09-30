<?php

namespace Foleybridge\Nestogy\Domains\Report\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Report Template Model
 *
 * Defines reusable report configurations and templates for consistent
 * reporting across the organization with predefined metrics and layouts.
 */
class ReportTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'category',
        'report_type',
        'template_config',
        'default_filters',
        'default_metrics',
        'chart_config',
        'layout_config',
        'created_by',
        'is_public',
        'is_system',
        'version',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'template_config' => 'array',
        'default_filters' => 'array',
        'default_metrics' => 'array',
        'chart_config' => 'array',
        'layout_config' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'is_system' => 'boolean',
        'version' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Template categories
     */
    const CATEGORY_FINANCIAL = 'financial';

    const CATEGORY_OPERATIONAL = 'operational';

    const CATEGORY_PERFORMANCE = 'performance';

    const CATEGORY_COMPLIANCE = 'compliance';

    const CATEGORY_EXECUTIVE = 'executive';

    const CATEGORY_CUSTOM = 'custom';

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all reports created from this template
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'template_id');
    }

    /**
     * Get template configuration with defaults
     */
    public function getConfigurationAttribute(): array
    {
        return array_merge([
            'title_template' => '{report_type} Report - {date_range}',
            'subtitle_template' => 'Generated on {generated_date}',
            'header_config' => [
                'show_logo' => true,
                'show_company_info' => true,
                'show_date_range' => true,
            ],
            'footer_config' => [
                'show_page_numbers' => true,
                'show_generation_info' => true,
            ],
            'styling' => [
                'color_scheme' => 'default',
                'font_family' => 'system',
                'font_size' => 'normal',
            ],
        ], $this->template_config ?? []);
    }

    /**
     * Get default chart configuration
     */
    public function getChartConfigurationAttribute(): array
    {
        return array_merge([
            'default_chart_type' => 'bar',
            'color_palette' => ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
            'show_legend' => true,
            'show_grid' => true,
            'animation_enabled' => true,
            'responsive' => true,
        ], $this->chart_config ?? []);
    }

    /**
     * Get layout configuration
     */
    public function getLayoutConfigurationAttribute(): array
    {
        return array_merge([
            'sections' => [
                'summary' => ['enabled' => true, 'order' => 1],
                'charts' => ['enabled' => true, 'order' => 2],
                'tables' => ['enabled' => true, 'order' => 3],
                'details' => ['enabled' => false, 'order' => 4],
            ],
            'columns' => 2,
            'spacing' => 'normal',
            'page_size' => 'a4',
            'orientation' => 'portrait',
        ], $this->layout_config ?? []);
    }

    /**
     * Create a report from this template
     */
    public function createReport(array $customConfig = []): Report
    {
        $reportData = array_merge([
            'name' => $this->name,
            'description' => $this->description,
            'report_type' => $this->report_type,
            'template_id' => $this->id,
            'report_config' => array_merge_recursive(
                $this->getConfigurationAttribute(),
                $customConfig
            ),
        ], $customConfig);

        return Report::create($reportData);
    }

    /**
     * Clone template with new name
     */
    public function duplicate(?string $newName = null): self
    {
        $data = $this->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        $data['name'] = $newName ?: $this->name.' (Copy)';
        $data['is_system'] = false;
        $data['version'] = 1.0;

        return self::create($data);
    }

    /**
     * Update template version
     */
    public function updateVersion(?float $newVersion = null): void
    {
        $version = $newVersion ?: ($this->version + 0.1);
        $this->update(['version' => $version]);
    }

    /**
     * Get template usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'total_reports' => $this->reports()->count(),
            'active_reports' => $this->reports()->where('status', 'active')->count(),
            'last_used' => $this->reports()->latest()->first()?->created_at,
            'most_recent_generation' => $this->reports()
                ->whereNotNull('last_generated_at')
                ->orderBy('last_generated_at', 'desc')
                ->first()?->last_generated_at,
        ];
    }

    /**
     * Validate template configuration
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        $config = $this->getConfigurationAttribute();

        // Validate required metrics
        if (empty($this->default_metrics)) {
            $errors[] = 'Template must define at least one default metric';
        }

        // Validate chart configuration
        $chartConfig = $this->getChartConfigurationAttribute();
        if (! in_array($chartConfig['default_chart_type'], ['bar', 'line', 'pie', 'doughnut', 'area'])) {
            $errors[] = 'Invalid default chart type';
        }

        // Validate layout sections
        $layoutConfig = $this->getLayoutConfigurationAttribute();
        if (empty(array_filter($layoutConfig['sections'], fn ($section) => $section['enabled']))) {
            $errors[] = 'At least one layout section must be enabled';
        }

        return $errors;
    }

    /**
     * Scope for public templates
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for system templates
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for user-created templates
     */
    public function scopeUserCreated($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope for templates by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for templates by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Get available categories
     */
    public static function getAvailableCategories(): array
    {
        return [
            self::CATEGORY_FINANCIAL => 'Financial',
            self::CATEGORY_OPERATIONAL => 'Operational',
            self::CATEGORY_PERFORMANCE => 'Performance',
            self::CATEGORY_COMPLIANCE => 'Compliance',
            self::CATEGORY_EXECUTIVE => 'Executive',
            self::CATEGORY_CUSTOM => 'Custom',
        ];
    }

    /**
     * Get system templates definitions
     */
    public static function getSystemTemplates(): array
    {
        return [
            [
                'name' => 'Financial Overview',
                'description' => 'Comprehensive financial performance report with revenue, expenses, and profit analysis',
                'category' => self::CATEGORY_FINANCIAL,
                'report_type' => 'financial',
                'default_metrics' => ['revenue', 'expenses', 'profit', 'cash_flow'],
                'template_config' => [
                    'sections' => [
                        'executive_summary' => true,
                        'revenue_analysis' => true,
                        'expense_breakdown' => true,
                        'profit_trends' => true,
                    ],
                ],
                'chart_config' => [
                    'default_chart_type' => 'line',
                    'color_palette' => ['#10B981', '#EF4444', '#3B82F6'],
                ],
                'is_system' => true,
                'is_public' => true,
            ],
            [
                'name' => 'Ticket Performance Dashboard',
                'description' => 'Support ticket analytics with SLA compliance and resolution metrics',
                'category' => self::CATEGORY_OPERATIONAL,
                'report_type' => 'tickets',
                'default_metrics' => ['total_tickets', 'resolution_time', 'sla_compliance', 'customer_satisfaction'],
                'template_config' => [
                    'sections' => [
                        'kpi_summary' => true,
                        'sla_analysis' => true,
                        'workload_distribution' => true,
                        'trend_analysis' => true,
                    ],
                ],
                'chart_config' => [
                    'default_chart_type' => 'bar',
                    'color_palette' => ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
                ],
                'is_system' => true,
                'is_public' => true,
            ],
            [
                'name' => 'Executive Summary',
                'description' => 'High-level business metrics and KPIs for executive reporting',
                'category' => self::CATEGORY_EXECUTIVE,
                'report_type' => 'dashboard',
                'default_metrics' => ['revenue', 'client_count', 'ticket_volume', 'profit_margin'],
                'template_config' => [
                    'sections' => [
                        'kpi_cards' => true,
                        'trend_charts' => true,
                        'performance_indicators' => true,
                    ],
                ],
                'chart_config' => [
                    'default_chart_type' => 'area',
                    'color_palette' => ['#8B5CF6', '#10B981', '#F59E0B'],
                ],
                'is_system' => true,
                'is_public' => true,
            ],
        ];
    }

    /**
     * Create system templates
     */
    public static function createSystemTemplates(): void
    {
        foreach (self::getSystemTemplates() as $templateData) {
            self::updateOrCreate(
                [
                    'name' => $templateData['name'],
                    'is_system' => true,
                ],
                array_merge($templateData, [
                    'version' => 1.0,
                    'created_by' => null,
                ])
            );
        }
    }

    /**
     * Get validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:'.implode(',', array_keys(self::getAvailableCategories())),
            'report_type' => 'required|in:financial,tickets,assets,clients,projects,users,custom,dashboard',
            'template_config' => 'required|array',
            'default_filters' => 'nullable|array',
            'default_metrics' => 'required|array|min:1',
            'chart_config' => 'nullable|array',
            'layout_config' => 'nullable|array',
            'is_public' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (! $template->created_by && ! $template->is_system) {
                $template->created_by = auth()->id();
            }

            if (! $template->version) {
                $template->version = 1.0;
            }
        });
    }
}
