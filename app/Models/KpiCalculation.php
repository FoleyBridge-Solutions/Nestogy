<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * KpiCalculation Model
 * 
 * Stores calculated KPI values with trend analysis and performance indicators.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $kpi_name
 * @property string $kpi_category
 * @property string $calculation_period
 * @property \Illuminate\Support\Carbon $calculation_date
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property float $kpi_value
 * @property float|null $target_value
 * @property float|null $previous_period_value
 * @property float|null $year_over_year_value
 * @property string|null $performance_status
 * @property string|null $trend_direction
 * @property float|null $trend_percentage
 * @property string $unit_type
 * @property string|null $display_format
 * @property array $calculation_components
 * @property array|null $drill_down_data
 * @property array|null $benchmarks
 * @property array|null $alerts_triggered
 * @property string|null $calculation_notes
 * @property bool $is_outlier
 * @property float|null $confidence_score
 * @property string|null $data_completeness
 * @property array|null $data_sources
 * @property int|null $calculation_time_ms
 * @property string|null $calculation_method
 * @property string $status
 * @property string|null $error_details
 * @property \Illuminate\Support\Carbon|null $calculated_at
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class KpiCalculation extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'kpi_calculations';

    protected $fillable = [
        'company_id',
        'kpi_name',
        'kpi_category',
        'calculation_period',
        'calculation_date',
        'period_start',
        'period_end',
        'kpi_value',
        'target_value',
        'previous_period_value',
        'year_over_year_value',
        'performance_status',
        'trend_direction',
        'trend_percentage',
        'unit_type',
        'display_format',
        'calculation_components',
        'drill_down_data',
        'benchmarks',
        'alerts_triggered',
        'calculation_notes',
        'is_outlier',
        'confidence_score',
        'data_completeness',
        'data_sources',
        'calculation_time_ms',
        'calculation_method',
        'status',
        'error_details',
        'calculated_at',
        'metadata',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'calculation_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'kpi_value' => 'decimal:6',
        'target_value' => 'decimal:6',
        'previous_period_value' => 'decimal:6',
        'year_over_year_value' => 'decimal:6',
        'trend_percentage' => 'decimal:4',
        'calculation_components' => 'array',
        'drill_down_data' => 'array',
        'benchmarks' => 'array',
        'alerts_triggered' => 'array',
        'is_outlier' => 'boolean',
        'confidence_score' => 'decimal:4',
        'data_sources' => 'array',
        'calculation_time_ms' => 'integer',
        'calculated_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // KPI Categories
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_CUSTOMER = 'customer';
    const CATEGORY_OPERATIONAL = 'operational';
    const CATEGORY_GROWTH = 'growth';
    const CATEGORY_PROFITABILITY = 'profitability';

    // Calculation Periods
    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_QUARTERLY = 'quarterly';
    const PERIOD_ANNUAL = 'annual';

    // Performance Status
    const PERFORMANCE_EXCELLENT = 'excellent';
    const PERFORMANCE_GOOD = 'good';
    const PERFORMANCE_WARNING = 'warning';
    const PERFORMANCE_CRITICAL = 'critical';

    // Trend Direction
    const TREND_UP = 'up';
    const TREND_DOWN = 'down';
    const TREND_STABLE = 'stable';

    // Unit Types
    const UNIT_NUMBER = 'number';
    const UNIT_PERCENTAGE = 'percentage';
    const UNIT_CURRENCY = 'currency';
    const UNIT_RATIO = 'ratio';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ERROR = 'error';

    // Data Completeness
    const DATA_COMPLETE = 'complete';
    const DATA_PARTIAL = 'partial';
    const DATA_ESTIMATED = 'estimated';

    /**
     * Create or update a KPI calculation
     */
    public static function recordKPI(
        int $companyId,
        string $kpiName,
        Carbon $calculationDate,
        float $kpiValue,
        array $options = []
    ): self {
        $data = array_merge([
            'company_id' => $companyId,
            'kpi_name' => $kpiName,
            'calculation_date' => $calculationDate,
            'period_start' => $calculationDate->copy()->startOfMonth(),
            'period_end' => $calculationDate->copy()->endOfMonth(),
            'kpi_value' => $kpiValue,
            'calculation_period' => self::PERIOD_MONTHLY,
            'unit_type' => self::UNIT_NUMBER,
            'status' => self::STATUS_COMPLETED,
            'calculated_at' => now(),
            'calculation_components' => [],
        ], $options);

        // Calculate trends and performance
        static::calculateTrends($data, $companyId, $kpiName, $calculationDate);

        return static::updateOrCreate([
            'company_id' => $companyId,
            'kpi_name' => $kpiName,
            'calculation_date' => $calculationDate,
        ], $data);
    }

    /**
     * Calculate trends and performance status
     */
    private static function calculateTrends(array &$data, int $companyId, string $kpiName, Carbon $calculationDate): void
    {
        // Get previous period value
        $previousPeriod = static::where('company_id', $companyId)
            ->where('kpi_name', $kpiName)
            ->where('calculation_date', '<', $calculationDate)
            ->orderBy('calculation_date', 'desc')
            ->first();

        if ($previousPeriod) {
            $data['previous_period_value'] = $previousPeriod->kpi_value;
            
            if ($previousPeriod->kpi_value != 0) {
                $data['trend_percentage'] = (($data['kpi_value'] - $previousPeriod->kpi_value) / abs($previousPeriod->kpi_value)) * 100;
                
                $data['trend_direction'] = match (true) {
                    $data['trend_percentage'] > 1 => self::TREND_UP,
                    $data['trend_percentage'] < -1 => self::TREND_DOWN,
                    default => self::TREND_STABLE,
                };
            }
        }

        // Get year-over-year value
        $yearAgo = $calculationDate->copy()->subYear();
        $yearOverYear = static::where('company_id', $companyId)
            ->where('kpi_name', $kpiName)
            ->where('calculation_date', '>=', $yearAgo->startOfMonth())
            ->where('calculation_date', '<=', $yearAgo->endOfMonth())
            ->first();

        if ($yearOverYear) {
            $data['year_over_year_value'] = $yearOverYear->kpi_value;
        }

        // Determine performance status
        $data['performance_status'] = static::determinePerformanceStatus($kpiName, $data);
    }

    /**
     * Determine performance status based on KPI type and value
     */
    private static function determinePerformanceStatus(string $kpiName, array $data): string
    {
        $value = $data['kpi_value'];
        $targetValue = $data['target_value'] ?? null;

        // If we have a target, use it for performance assessment
        if ($targetValue !== null) {
            $performance = ($value / $targetValue) * 100;
            
            return match (true) {
                $performance >= 95 => self::PERFORMANCE_EXCELLENT,
                $performance >= 80 => self::PERFORMANCE_GOOD,
                $performance >= 60 => self::PERFORMANCE_WARNING,
                default => self::PERFORMANCE_CRITICAL,
            };
        }

        // Default performance based on KPI type and trends
        $trendPercentage = $data['trend_percentage'] ?? 0;
        
        // Positive trend KPIs (higher is better)
        $positiveTrendKPIs = [
            'total_revenue', 'mrr', 'arr', 'customer_lifetime_value',
            'quote_conversion_rate', 'collection_efficiency'
        ];

        if (in_array($kpiName, $positiveTrendKPIs)) {
            return match (true) {
                $trendPercentage > 10 => self::PERFORMANCE_EXCELLENT,
                $trendPercentage > 5 => self::PERFORMANCE_GOOD,
                $trendPercentage > -5 => self::PERFORMANCE_WARNING,
                default => self::PERFORMANCE_CRITICAL,
            };
        }

        // Negative trend KPIs (lower is better)
        $negativeTrendKPIs = ['churn_rate', 'customer_acquisition_cost'];
        
        if (in_array($kpiName, $negativeTrendKPIs)) {
            return match (true) {
                $trendPercentage < -10 => self::PERFORMANCE_EXCELLENT,
                $trendPercentage < -5 => self::PERFORMANCE_GOOD,
                $trendPercentage < 5 => self::PERFORMANCE_WARNING,
                default => self::PERFORMANCE_CRITICAL,
            };
        }

        return self::PERFORMANCE_GOOD; // Default
    }

    /**
     * Check if KPI meets target
     */
    public function meetsTarget(): ?bool
    {
        if (!$this->target_value) {
            return null;
        }

        return $this->kpi_value >= $this->target_value;
    }

    /**
     * Get formatted KPI value
     */
    public function getFormattedValue(): string
    {
        return match ($this->unit_type) {
            self::UNIT_PERCENTAGE => number_format($this->kpi_value, 2) . '%',
            self::UNIT_CURRENCY => '$' . number_format($this->kpi_value, 2),
            self::UNIT_RATIO => number_format($this->kpi_value, 2) . ':1',
            default => number_format($this->kpi_value, 2),
        };
    }

    /**
     * Get performance indicator color
     */
    public function getPerformanceColor(): string
    {
        return match ($this->performance_status) {
            self::PERFORMANCE_EXCELLENT => '#10B981', // Green
            self::PERFORMANCE_GOOD => '#3B82F6',      // Blue
            self::PERFORMANCE_WARNING => '#F59E0B',   // Yellow
            self::PERFORMANCE_CRITICAL => '#EF4444',  // Red
            default => '#6B7280',                     // Gray
        };
    }

    /**
     * Scope by KPI name
     */
    public function scopeByKPI($query, string $kpiName)
    {
        return $query->where('kpi_name', $kpiName);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('kpi_category', $category);
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('calculation_date', [$startDate, $endDate]);
    }

    /**
     * Scope for completed calculations
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Get available KPI categories
     */
    public static function getAvailableCategories(): array
    {
        return [
            self::CATEGORY_FINANCIAL => 'Financial',
            self::CATEGORY_CUSTOMER => 'Customer',
            self::CATEGORY_OPERATIONAL => 'Operational',
            self::CATEGORY_GROWTH => 'Growth',
            self::CATEGORY_PROFITABILITY => 'Profitability',
        ];
    }

    /**
     * Get available unit types
     */
    public static function getAvailableUnitTypes(): array
    {
        return [
            self::UNIT_NUMBER => 'Number',
            self::UNIT_PERCENTAGE => 'Percentage',
            self::UNIT_CURRENCY => 'Currency',
            self::UNIT_RATIO => 'Ratio',
        ];
    }
}