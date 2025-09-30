<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DashboardWidget Model
 *
 * Manages customizable dashboard widgets with configuration, positioning,
 * and user-specific settings.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property string $widget_type
 * @property string $widget_name
 * @property string|null $description
 * @property string $dashboard_type
 * @property array $configuration
 * @property array $display_settings
 * @property array $data_source
 * @property array $refresh_settings
 * @property array|null $permissions
 * @property int $sort_order
 * @property int|null $grid_row
 * @property int|null $grid_column
 * @property int $grid_width
 * @property int $grid_height
 * @property bool $is_visible
 * @property bool $is_active
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $last_updated_at
 * @property array|null $metadata
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class DashboardWidget extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'dashboard_widgets';

    protected $fillable = [
        'company_id',
        'user_id',
        'widget_type',
        'widget_name',
        'description',
        'dashboard_type',
        'configuration',
        'display_settings',
        'data_source',
        'refresh_settings',
        'permissions',
        'sort_order',
        'grid_row',
        'grid_column',
        'grid_width',
        'grid_height',
        'is_visible',
        'is_active',
        'is_default',
        'last_updated_at',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'configuration' => 'array',
        'display_settings' => 'array',
        'data_source' => 'array',
        'refresh_settings' => 'array',
        'permissions' => 'array',
        'sort_order' => 'integer',
        'grid_row' => 'integer',
        'grid_column' => 'integer',
        'grid_width' => 'integer',
        'grid_height' => 'integer',
        'is_visible' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_updated_at' => 'datetime',
        'metadata' => 'array',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    const DELETED_AT = 'archived_at';

    // Widget Types
    const TYPE_KPI_CARD = 'kpi_card';

    const TYPE_REVENUE_CHART = 'revenue_chart';

    const TYPE_TABLE = 'table';

    const TYPE_GAUGE = 'gauge';

    const TYPE_PIE_CHART = 'pie_chart';

    const TYPE_BAR_CHART = 'bar_chart';

    const TYPE_LINE_CHART = 'line_chart';

    const TYPE_AREA_CHART = 'area_chart';

    const TYPE_HEATMAP = 'heatmap';

    const TYPE_TREND_ANALYSIS = 'trend_analysis';

    const TYPE_CASH_FLOW_GAUGE = 'cash_flow_gauge';

    const TYPE_SERVICE_BREAKDOWN = 'service_breakdown';

    const TYPE_CUSTOMER_TABLE = 'customer_table';

    // Dashboard Types
    const DASHBOARD_EXECUTIVE = 'executive';

    const DASHBOARD_REVENUE = 'revenue';

    const DASHBOARD_OPERATIONS = 'operations';

    const DASHBOARD_CUSTOMER = 'customer';

    const DASHBOARD_FORECASTING = 'forecasting';

    /**
     * Get the user this widget belongs to (null for company-wide widgets)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this widget
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if widget should auto-refresh
     */
    public function shouldAutoRefresh(): bool
    {
        return $this->is_active &&
               isset($this->refresh_settings['auto_refresh']) &&
               $this->refresh_settings['auto_refresh'] === true;
    }

    /**
     * Get auto-refresh interval in seconds
     */
    public function getRefreshInterval(): int
    {
        return $this->refresh_settings['interval'] ?? 300; // 5 minutes default
    }

    /**
     * Check if user has permission to view this widget
     */
    public function canBeViewedBy(User $user): bool
    {
        // Company-wide widgets
        if (! $this->user_id) {
            return $user->company_id === $this->company_id;
        }

        // User-specific widgets
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check permission settings
        if ($this->permissions) {
            $allowedRoles = $this->permissions['roles'] ?? [];
            $allowedUsers = $this->permissions['users'] ?? [];

            if (in_array($user->id, $allowedUsers)) {
                return true;
            }

            // Check if user has any of the allowed roles
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Update widget position
     */
    public function updatePosition(int $row, int $column, ?int $width = null, ?int $height = null): void
    {
        $this->update([
            'grid_row' => $row,
            'grid_column' => $column,
            'grid_width' => $width ?? $this->grid_width,
            'grid_height' => $height ?? $this->grid_height,
            'last_updated_at' => now(),
        ]);
    }

    /**
     * Update widget configuration
     */
    public function updateConfiguration(array $configuration): void
    {
        $this->update([
            'configuration' => array_merge($this->configuration ?? [], $configuration),
            'last_updated_at' => now(),
        ]);
    }

    /**
     * Clone widget for another user or dashboard
     */
    public function cloneForUser(int $userId, ?string $dashboardType = null, ?int $createdBy = null): self
    {
        $clone = $this->replicate();
        $clone->user_id = $userId;
        $clone->dashboard_type = $dashboardType ?? $this->dashboard_type;
        $clone->widget_name = $this->widget_name.' (Copy)';
        $clone->is_default = false;
        $clone->created_by = $createdBy;
        $clone->save();

        return $clone;
    }

    /**
     * Scope for active widgets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for visible widgets
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope by dashboard type
     */
    public function scopeByDashboard($query, string $dashboardType)
    {
        return $query->where('dashboard_type', $dashboardType);
    }

    /**
     * Scope for user-specific widgets
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhereNull('user_id'); // Include company-wide widgets
        });
    }

    /**
     * Scope widgets by type
     */
    public function scopeByType($query, string $widgetType)
    {
        return $query->where('widget_type', $widgetType);
    }

    /**
     * Order by grid position
     */
    public function scopeOrderByPosition($query)
    {
        return $query->orderBy('sort_order')
            ->orderBy('grid_row')
            ->orderBy('grid_column');
    }

    /**
     * Get available widget types
     */
    public static function getAvailableWidgetTypes(): array
    {
        return [
            self::TYPE_KPI_CARD => 'KPI Card',
            self::TYPE_REVENUE_CHART => 'Revenue Chart',
            self::TYPE_TABLE => 'Data Table',
            self::TYPE_GAUGE => 'Gauge Chart',
            self::TYPE_PIE_CHART => 'Pie Chart',
            self::TYPE_BAR_CHART => 'Bar Chart',
            self::TYPE_LINE_CHART => 'Line Chart',
            self::TYPE_AREA_CHART => 'Area Chart',
            self::TYPE_HEATMAP => 'Heatmap',
            self::TYPE_TREND_ANALYSIS => 'Trend Analysis',
            self::TYPE_CASH_FLOW_GAUGE => 'Cash Flow Gauge',
            self::TYPE_SERVICE_BREAKDOWN => 'Service Breakdown',
            self::TYPE_CUSTOMER_TABLE => 'Customer Table',
        ];
    }

    /**
     * Get available dashboard types
     */
    public static function getAvailableDashboardTypes(): array
    {
        return [
            self::DASHBOARD_EXECUTIVE => 'Executive Dashboard',
            self::DASHBOARD_REVENUE => 'Revenue Analytics',
            self::DASHBOARD_OPERATIONS => 'Operations Dashboard',
            self::DASHBOARD_CUSTOMER => 'Customer Analytics',
            self::DASHBOARD_FORECASTING => 'Forecasting Dashboard',
        ];
    }

    /**
     * Get default widget configuration by type
     */
    public static function getDefaultConfiguration(string $widgetType): array
    {
        return match ($widgetType) {
            self::TYPE_KPI_CARD => [
                'metric' => 'total_revenue',
                'show_trend' => true,
                'show_comparison' => true,
                'comparison_period' => 'previous_month',
            ],
            self::TYPE_REVENUE_CHART => [
                'chart_type' => 'line',
                'time_period' => '12_months',
                'group_by' => 'month',
                'show_forecast' => false,
            ],
            self::TYPE_TABLE => [
                'page_size' => 10,
                'sortable' => true,
                'searchable' => true,
                'columns' => [],
            ],
            self::TYPE_GAUGE => [
                'min_value' => 0,
                'max_value' => 100,
                'target_value' => 80,
                'unit' => 'percentage',
            ],
            default => [],
        };
    }

    /**
     * Get default display settings by type
     */
    public static function getDefaultDisplaySettings(string $widgetType): array
    {
        return match ($widgetType) {
            self::TYPE_KPI_CARD => [
                'background_color' => '#ffffff',
                'text_color' => '#333333',
                'border_radius' => '8px',
                'show_icon' => true,
            ],
            self::TYPE_REVENUE_CHART => [
                'height' => '400px',
                'show_legend' => true,
                'show_grid' => true,
                'color_scheme' => 'blue',
            ],
            default => [
                'background_color' => '#ffffff',
                'border_radius' => '8px',
                'padding' => '16px',
            ],
        };
    }

    /**
     * Get default refresh settings
     */
    public static function getDefaultRefreshSettings(): array
    {
        return [
            'auto_refresh' => false,
            'interval' => 300, // 5 minutes
            'cache_duration' => 300,
        ];
    }
}
