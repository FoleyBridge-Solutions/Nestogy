<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * CashFlowProjection Model
 * 
 * Manages cash flow projections with multiple scenarios and confidence intervals.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $projection_type
 * @property string $projection_model
 * @property \Illuminate\Support\Carbon $projection_date
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property float $projected_inflow
 * @property float $projected_outflow
 * @property float $net_cash_flow
 * @property float $opening_balance
 * @property float $closing_balance
 * @property array|null $inflow_breakdown
 * @property array|null $outflow_breakdown
 * @property array|null $assumptions
 * @property array|null $risk_factors
 * @property float|null $confidence_interval_low
 * @property float|null $confidence_interval_high
 * @property float|null $confidence_percentage
 * @property float|null $actual_inflow
 * @property float|null $actual_outflow
 * @property float|null $actual_net_flow
 * @property float|null $variance_percentage
 * @property string|null $accuracy_rating
 * @property array|null $seasonal_adjustments
 * @property array|null $recurring_items
 * @property array|null $one_time_items
 * @property array|null $contract_renewals
 * @property array|null $new_business
 * @property array|null $churn_projections
 * @property bool $is_locked
 * @property string $status
 * @property string|null $notes
 * @property array|null $metadata
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CashFlowProjection extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'cash_flow_projections';

    protected $fillable = [
        'company_id',
        'projection_type',
        'projection_model',
        'projection_date',
        'period_start',
        'period_end',
        'projected_inflow',
        'projected_outflow',
        'net_cash_flow',
        'opening_balance',
        'closing_balance',
        'inflow_breakdown',
        'outflow_breakdown',
        'assumptions',
        'risk_factors',
        'confidence_interval_low',
        'confidence_interval_high',
        'confidence_percentage',
        'actual_inflow',
        'actual_outflow',
        'actual_net_flow',
        'variance_percentage',
        'accuracy_rating',
        'seasonal_adjustments',
        'recurring_items',
        'one_time_items',
        'contract_renewals',
        'new_business',
        'churn_projections',
        'is_locked',
        'status',
        'notes',
        'metadata',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'projection_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'projected_inflow' => 'decimal:2',
        'projected_outflow' => 'decimal:2',
        'net_cash_flow' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'inflow_breakdown' => 'array',
        'outflow_breakdown' => 'array',
        'assumptions' => 'array',
        'risk_factors' => 'array',
        'confidence_interval_low' => 'decimal:2',
        'confidence_interval_high' => 'decimal:2',
        'confidence_percentage' => 'decimal:2',
        'actual_inflow' => 'decimal:2',
        'actual_outflow' => 'decimal:2',
        'actual_net_flow' => 'decimal:2',
        'variance_percentage' => 'decimal:4',
        'seasonal_adjustments' => 'array',
        'recurring_items' => 'array',
        'one_time_items' => 'array',
        'contract_renewals' => 'array',
        'new_business' => 'array',
        'churn_projections' => 'array',
        'is_locked' => 'boolean',
        'metadata' => 'array',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Projection Types
    const TYPE_WEEKLY = 'weekly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_QUARTERLY = 'quarterly';
    const TYPE_ANNUAL = 'annual';
    const TYPE_CUSTOM = 'custom';

    // Projection Models
    const MODEL_LINEAR = 'linear';
    const MODEL_SEASONAL = 'seasonal';
    const MODEL_ML_BASED = 'ml_based';
    const MODEL_MANUAL = 'manual';

    // Status
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    // Accuracy Ratings
    const ACCURACY_EXCELLENT = 'excellent';
    const ACCURACY_GOOD = 'good';
    const ACCURACY_FAIR = 'fair';
    const ACCURACY_POOR = 'poor';

    /**
     * Get the user who created this projection
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this projection
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Record actual cash flow results
     */
    public function recordActuals(float $actualInflow, float $actualOutflow): void
    {
        $actualNetFlow = $actualInflow - $actualOutflow;
        $variance = $this->net_cash_flow != 0 
            ? (($actualNetFlow - $this->net_cash_flow) / abs($this->net_cash_flow)) * 100 
            : 0;

        $this->update([
            'actual_inflow' => $actualInflow,
            'actual_outflow' => $actualOutflow,
            'actual_net_flow' => $actualNetFlow,
            'variance_percentage' => $variance,
            'accuracy_rating' => $this->calculateAccuracyRating($variance),
            'is_locked' => true,
        ]);
    }

    /**
     * Calculate accuracy rating based on variance
     */
    private function calculateAccuracyRating(float $variance): string
    {
        $absVariance = abs($variance);
        
        return match (true) {
            $absVariance <= 5 => self::ACCURACY_EXCELLENT,
            $absVariance <= 15 => self::ACCURACY_GOOD,
            $absVariance <= 30 => self::ACCURACY_FAIR,
            default => self::ACCURACY_POOR,
        };
    }

    /**
     * Check if projection is accurate (within acceptable variance)
     */
    public function isAccurate(float $acceptableVariance = 15): bool
    {
        return $this->variance_percentage !== null && 
               abs($this->variance_percentage) <= $acceptableVariance;
    }

    /**
     * Approve the projection
     */
    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Publish the projection
     */
    public function publish(): void
    {
        $this->update(['status' => self::STATUS_PUBLISHED]);
    }

    /**
     * Create scenario projections
     */
    public function createScenarios(): array
    {
        $baseInflow = $this->projected_inflow;
        $baseOutflow = $this->projected_outflow;

        return [
            'optimistic' => [
                'projected_inflow' => $baseInflow * 1.2,
                'projected_outflow' => $baseOutflow * 0.9,
                'net_cash_flow' => ($baseInflow * 1.2) - ($baseOutflow * 0.9),
            ],
            'pessimistic' => [
                'projected_inflow' => $baseInflow * 0.8,
                'projected_outflow' => $baseOutflow * 1.1,
                'net_cash_flow' => ($baseInflow * 0.8) - ($baseOutflow * 1.1),
            ],
            'realistic' => [
                'projected_inflow' => $baseInflow,
                'projected_outflow' => $baseOutflow,
                'net_cash_flow' => $this->net_cash_flow,
            ],
        ];
    }

    /**
     * Scope by projection type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('projection_type', $type);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for published projections
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }

    /**
     * Get available projection types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_WEEKLY => 'Weekly',
            self::TYPE_MONTHLY => 'Monthly',
            self::TYPE_QUARTERLY => 'Quarterly',
            self::TYPE_ANNUAL => 'Annual',
            self::TYPE_CUSTOM => 'Custom',
        ];
    }

    /**
     * Get available models
     */
    public static function getAvailableModels(): array
    {
        return [
            self::MODEL_LINEAR => 'Linear Trend',
            self::MODEL_SEASONAL => 'Seasonal Adjustment',
            self::MODEL_ML_BASED => 'Machine Learning',
            self::MODEL_MANUAL => 'Manual Entry',
        ];
    }

    /**
     * Get available statuses
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }
}