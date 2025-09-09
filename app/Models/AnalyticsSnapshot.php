<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * AnalyticsSnapshot Model
 * 
 * Stores historical analytics data for trend analysis and performance tracking.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $snapshot_type
 * @property \Illuminate\Support\Carbon $snapshot_date
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property string $data_category
 * @property array $metrics_data
 * @property array $kpi_data
 * @property array|null $trend_data
 * @property array|null $breakdown_data
 * @property float|null $total_revenue
 * @property float|null $recurring_revenue
 * @property float|null $one_time_revenue
 * @property int|null $active_clients
 * @property int|null $new_clients
 * @property int|null $churned_clients
 * @property float|null $average_deal_size
 * @property float|null $customer_lifetime_value
 * @property float|null $customer_acquisition_cost
 * @property float|null $gross_profit_margin
 * @property float|null $net_profit_margin
 * @property int|null $invoices_sent
 * @property int|null $invoices_paid
 * @property float|null $collection_efficiency
 * @property float|null $outstanding_receivables
 * @property int|null $quotes_sent
 * @property int|null $quotes_accepted
 * @property float|null $quote_conversion_rate
 * @property array|null $voip_metrics
 * @property array|null $tax_metrics
 * @property array|null $contract_metrics
 * @property string $calculation_status
 * @property string|null $calculation_notes
 * @property \Illuminate\Support\Carbon|null $calculated_at
 * @property int|null $calculation_duration_ms
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AnalyticsSnapshot extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'analytics_snapshots';

    protected $fillable = [
        'company_id',
        'snapshot_type',
        'snapshot_date',
        'period_start',
        'period_end',
        'data_category',
        'metrics_data',
        'kpi_data',
        'trend_data',
        'breakdown_data',
        'total_revenue',
        'recurring_revenue',
        'one_time_revenue',
        'active_clients',
        'new_clients',
        'churned_clients',
        'average_deal_size',
        'customer_lifetime_value',
        'customer_acquisition_cost',
        'gross_profit_margin',
        'net_profit_margin',
        'invoices_sent',
        'invoices_paid',
        'collection_efficiency',
        'outstanding_receivables',
        'quotes_sent',
        'quotes_accepted',
        'quote_conversion_rate',
        'voip_metrics',
        'tax_metrics',
        'contract_metrics',
        'calculation_status',
        'calculation_notes',
        'calculated_at',
        'calculation_duration_ms',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'snapshot_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'metrics_data' => 'array',
        'kpi_data' => 'array',
        'trend_data' => 'array',
        'breakdown_data' => 'array',
        'total_revenue' => 'decimal:2',
        'recurring_revenue' => 'decimal:2',
        'one_time_revenue' => 'decimal:2',
        'active_clients' => 'integer',
        'new_clients' => 'integer',
        'churned_clients' => 'integer',
        'average_deal_size' => 'decimal:2',
        'customer_lifetime_value' => 'decimal:2',
        'customer_acquisition_cost' => 'decimal:2',
        'gross_profit_margin' => 'decimal:4',
        'net_profit_margin' => 'decimal:4',
        'invoices_sent' => 'integer',
        'invoices_paid' => 'integer',
        'collection_efficiency' => 'decimal:4',
        'outstanding_receivables' => 'decimal:2',
        'quotes_sent' => 'integer',
        'quotes_accepted' => 'integer',
        'quote_conversion_rate' => 'decimal:4',
        'voip_metrics' => 'array',
        'tax_metrics' => 'array',
        'contract_metrics' => 'array',
        'calculated_at' => 'datetime',
        'calculation_duration_ms' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Snapshot Types
    const TYPE_DAILY = 'daily';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_QUARTERLY = 'quarterly';
    const TYPE_ANNUAL = 'annual';

    // Data Categories
    const CATEGORY_REVENUE = 'revenue';
    const CATEGORY_CUSTOMERS = 'customers';
    const CATEGORY_OPERATIONS = 'operations';
    const CATEGORY_TAX = 'tax';
    const CATEGORY_CONTRACTS = 'contracts';

    // Calculation Status
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ERROR = 'error';

    /**
     * Create a new analytics snapshot
     */
    public static function createSnapshot(
        int $companyId,
        string $snapshotType,
        Carbon $snapshotDate,
        string $dataCategory,
        array $data
    ): self {
        return static::create([
            'company_id' => $companyId,
            'snapshot_type' => $snapshotType,
            'snapshot_date' => $snapshotDate,
            'period_start' => $data['period_start'] ?? $snapshotDate->copy()->startOfMonth(),
            'period_end' => $data['period_end'] ?? $snapshotDate->copy()->endOfMonth(),
            'data_category' => $dataCategory,
            'metrics_data' => $data['metrics_data'] ?? [],
            'kpi_data' => $data['kpi_data'] ?? [],
            'trend_data' => $data['trend_data'] ?? null,
            'breakdown_data' => $data['breakdown_data'] ?? null,
            'total_revenue' => $data['total_revenue'] ?? null,
            'recurring_revenue' => $data['recurring_revenue'] ?? null,
            'one_time_revenue' => $data['one_time_revenue'] ?? null,
            'active_clients' => $data['active_clients'] ?? null,
            'new_clients' => $data['new_clients'] ?? null,
            'churned_clients' => $data['churned_clients'] ?? null,
            'average_deal_size' => $data['average_deal_size'] ?? null,
            'customer_lifetime_value' => $data['customer_lifetime_value'] ?? null,
            'customer_acquisition_cost' => $data['customer_acquisition_cost'] ?? null,
            'gross_profit_margin' => $data['gross_profit_margin'] ?? null,
            'net_profit_margin' => $data['net_profit_margin'] ?? null,
            'invoices_sent' => $data['invoices_sent'] ?? null,
            'invoices_paid' => $data['invoices_paid'] ?? null,
            'collection_efficiency' => $data['collection_efficiency'] ?? null,
            'outstanding_receivables' => $data['outstanding_receivables'] ?? null,
            'quotes_sent' => $data['quotes_sent'] ?? null,
            'quotes_accepted' => $data['quotes_accepted'] ?? null,
            'quote_conversion_rate' => $data['quote_conversion_rate'] ?? null,
            'voip_metrics' => $data['voip_metrics'] ?? null,
            'tax_metrics' => $data['tax_metrics'] ?? null,
            'contract_metrics' => $data['contract_metrics'] ?? null,
            'calculation_status' => self::STATUS_COMPLETED,
            'calculated_at' => now(),
            'calculation_duration_ms' => $data['calculation_duration_ms'] ?? null,
        ]);
    }

    /**
     * Get trend comparison with previous period
     */
    public function getTrendComparison(): array
    {
        $previousSnapshot = static::where('company_id', $this->company_id)
            ->where('snapshot_type', $this->snapshot_type)
            ->where('data_category', $this->data_category)
            ->where('snapshot_date', '<', $this->snapshot_date)
            ->orderBy('snapshot_date', 'desc')
            ->first();

        if (!$previousSnapshot) {
            return [];
        }

        $comparison = [];
        $numericalFields = [
            'total_revenue', 'recurring_revenue', 'one_time_revenue',
            'active_clients', 'new_clients', 'churned_clients',
            'average_deal_size', 'customer_lifetime_value', 'customer_acquisition_cost',
            'gross_profit_margin', 'net_profit_margin', 'collection_efficiency',
            'outstanding_receivables', 'quotes_sent', 'quotes_accepted', 'quote_conversion_rate'
        ];

        foreach ($numericalFields as $field) {
            if ($this->$field !== null && $previousSnapshot->$field !== null) {
                $current = (float) $this->$field;
                $previous = (float) $previousSnapshot->$field;
                
                if ($previous > 0) {
                    $percentageChange = (($current - $previous) / $previous) * 100;
                    $comparison[$field] = [
                        'current' => $current,
                        'previous' => $previous,
                        'change' => $current - $previous,
                        'percentage_change' => round($percentageChange, 2),
                        'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable')
                    ];
                }
            }
        }

        return $comparison;
    }

    /**
     * Mark snapshot as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['calculation_status' => self::STATUS_PROCESSING]);
    }

    /**
     * Mark snapshot as completed
     */
    public function markAsCompleted(int $durationMs = null): void
    {
        $this->update([
            'calculation_status' => self::STATUS_COMPLETED,
            'calculated_at' => now(),
            'calculation_duration_ms' => $durationMs,
        ]);
    }

    /**
     * Mark snapshot as error
     */
    public function markAsError(string $notes = null): void
    {
        $this->update([
            'calculation_status' => self::STATUS_ERROR,
            'calculation_notes' => $notes,
        ]);
    }

    /**
     * Scope by snapshot type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('snapshot_type', $type);
    }

    /**
     * Scope by data category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('data_category', $category);
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }

    /**
     * Scope for completed snapshots
     */
    public function scopeCompleted($query)
    {
        return $query->where('calculation_status', self::STATUS_COMPLETED);
    }

    /**
     * Get available snapshot types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_DAILY => 'Daily',
            self::TYPE_WEEKLY => 'Weekly',
            self::TYPE_MONTHLY => 'Monthly',
            self::TYPE_QUARTERLY => 'Quarterly',
            self::TYPE_ANNUAL => 'Annual',
        ];
    }

    /**
     * Get available data categories
     */
    public static function getAvailableCategories(): array
    {
        return [
            self::CATEGORY_REVENUE => 'Revenue',
            self::CATEGORY_CUSTOMERS => 'Customers',
            self::CATEGORY_OPERATIONS => 'Operations',
            self::CATEGORY_TAX => 'Tax',
            self::CATEGORY_CONTRACTS => 'Contracts',
        ];
    }
}