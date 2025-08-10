<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * RevenueMetric Model
 * 
 * Tracks detailed revenue metrics and calculations for analytics and forecasting.
 * 
 * @property int $id
 * @property int $company_id
 * @property int|null $client_id
 * @property string $metric_type
 * @property string $period_type
 * @property \Illuminate\Support\Carbon $metric_date
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property float $metric_value
 * @property float|null $previous_value
 * @property float|null $growth_amount
 * @property float|null $growth_percentage
 * @property string|null $service_category
 * @property string|null $revenue_type
 * @property array|null $breakdown_data
 * @property array|null $calculation_details
 * @property int|null $customer_count
 * @property float|null $average_per_customer
 * @property string $currency_code
 * @property array|null $metadata
 * @property bool $is_projected
 * @property float|null $confidence_score
 * @property string|null $calculation_method
 * @property \Illuminate\Support\Carbon|null $calculated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RevenueMetric extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'revenue_metrics';

    protected $fillable = [
        'company_id',
        'client_id',
        'metric_type',
        'period_type',
        'metric_date',
        'period_start',
        'period_end',
        'metric_value',
        'previous_value',
        'growth_amount',
        'growth_percentage',
        'service_category',
        'revenue_type',
        'breakdown_data',
        'calculation_details',
        'customer_count',
        'average_per_customer',
        'currency_code',
        'metadata',
        'is_projected',
        'confidence_score',
        'calculation_method',
        'calculated_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'metric_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'metric_value' => 'decimal:2',
        'previous_value' => 'decimal:2',
        'growth_amount' => 'decimal:2',
        'growth_percentage' => 'decimal:4',
        'breakdown_data' => 'array',
        'calculation_details' => 'array',
        'customer_count' => 'integer',
        'average_per_customer' => 'decimal:2',
        'metadata' => 'array',
        'is_projected' => 'boolean',
        'confidence_score' => 'decimal:4',
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Metric Types
    const TYPE_MRR = 'mrr';
    const TYPE_ARR = 'arr';
    const TYPE_LTV = 'ltv';
    const TYPE_CHURN = 'churn';
    const TYPE_ARPU = 'arpu';
    const TYPE_EXPANSION = 'expansion';
    const TYPE_CONTRACTION = 'contraction';

    // Period Types
    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_QUARTERLY = 'quarterly';
    const PERIOD_ANNUAL = 'annual';

    // Service Categories
    const SERVICE_VOIP = 'voip';
    const SERVICE_EQUIPMENT = 'equipment';
    const SERVICE_PROFESSIONAL = 'professional_services';
    const SERVICE_SUPPORT = 'support';

    // Revenue Types
    const REVENUE_RECURRING = 'recurring';
    const REVENUE_ONE_TIME = 'one_time';
    const REVENUE_USAGE_BASED = 'usage_based';
    const REVENUE_OVERAGE = 'overage';

    /**
     * Get the client this metric belongs to
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Create or update a revenue metric
     */
    public static function recordMetric(
        int $companyId,
        string $metricType,
        Carbon $metricDate,
        float $metricValue,
        array $options = []
    ): self {
        $data = array_merge([
            'company_id' => $companyId,
            'metric_type' => $metricType,
            'metric_date' => $metricDate,
            'period_start' => $metricDate->copy()->startOfMonth(),
            'period_end' => $metricDate->copy()->endOfMonth(),
            'metric_value' => $metricValue,
            'period_type' => self::PERIOD_MONTHLY,
            'currency_code' => 'USD',
            'calculated_at' => now(),
        ], $options);

        // Calculate growth if we have previous data
        if (!isset($data['previous_value'])) {
            $previous = static::where('company_id', $companyId)
                ->where('metric_type', $metricType)
                ->where('client_id', $data['client_id'] ?? null)
                ->where('metric_date', '<', $metricDate)
                ->orderBy('metric_date', 'desc')
                ->first();

            if ($previous) {
                $data['previous_value'] = $previous->metric_value;
                $data['growth_amount'] = $metricValue - $previous->metric_value;
                $data['growth_percentage'] = $previous->metric_value > 0 
                    ? (($metricValue - $previous->metric_value) / $previous->metric_value) * 100 
                    : 0;
            }
        }

        return static::updateOrCreate([
            'company_id' => $companyId,
            'client_id' => $data['client_id'] ?? null,
            'metric_type' => $metricType,
            'metric_date' => $metricDate,
        ], $data);
    }

    /**
     * Get growth trend
     */
    public function getGrowthTrend(): string
    {
        if (!$this->growth_percentage) {
            return 'stable';
        }

        return $this->growth_percentage > 0 ? 'up' : 'down';
    }

    /**
     * Check if metric is positive trend (higher is better)
     */
    public function isPositiveTrend(): bool
    {
        $positiveTrendMetrics = [
            self::TYPE_MRR,
            self::TYPE_ARR,
            self::TYPE_LTV,
            self::TYPE_ARPU,
            self::TYPE_EXPANSION,
        ];

        return in_array($this->metric_type, $positiveTrendMetrics);
    }

    /**
     * Get formatted metric value
     */
    public function getFormattedValue(): string
    {
        return match ($this->metric_type) {
            self::TYPE_CHURN => number_format($this->metric_value, 2) . '%',
            self::TYPE_MRR, self::TYPE_ARR, self::TYPE_LTV, self::TYPE_ARPU => 
                '$' . number_format($this->metric_value, 2),
            default => number_format($this->metric_value, 2),
        };
    }

    /**
     * Scope by metric type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope by client
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for company-wide metrics
     */
    public function scopeCompanyWide($query)
    {
        return $query->whereNull('client_id');
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    /**
     * Scope for actual (non-projected) metrics
     */
    public function scopeActual($query)
    {
        return $query->where('is_projected', false);
    }

    /**
     * Scope for projected metrics
     */
    public function scopeProjected($query)
    {
        return $query->where('is_projected', true);
    }

    /**
     * Get available metric types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_MRR => 'Monthly Recurring Revenue',
            self::TYPE_ARR => 'Annual Recurring Revenue',
            self::TYPE_LTV => 'Customer Lifetime Value',
            self::TYPE_CHURN => 'Churn Rate',
            self::TYPE_ARPU => 'Average Revenue Per User',
            self::TYPE_EXPANSION => 'Expansion Revenue',
            self::TYPE_CONTRACTION => 'Contraction Revenue',
        ];
    }

    /**
     * Get available service categories
     */
    public static function getAvailableServiceCategories(): array
    {
        return [
            self::SERVICE_VOIP => 'VoIP Services',
            self::SERVICE_EQUIPMENT => 'Equipment',
            self::SERVICE_PROFESSIONAL => 'Professional Services',
            self::SERVICE_SUPPORT => 'Support',
        ];
    }

    /**
     * Get available revenue types
     */
    public static function getAvailableRevenueTypes(): array
    {
        return [
            self::REVENUE_RECURRING => 'Recurring',
            self::REVENUE_ONE_TIME => 'One-time',
            self::REVENUE_USAGE_BASED => 'Usage-based',
            self::REVENUE_OVERAGE => 'Overage',
        ];
    }
}