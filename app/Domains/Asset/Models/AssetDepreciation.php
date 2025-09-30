<?php

namespace App\Domains\Asset\Models;

use App\Models\Asset;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetDepreciation extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'asset_depreciations';

    protected $fillable = [
        'company_id',
        'asset_id',
        'original_cost',
        'salvage_value',
        'useful_life_years',
        'method',
        'depreciation_rate',
        'start_date',
        'annual_depreciation',
        'accumulated_depreciation',
        'notes',
        'units_produced',
        'total_units_expected',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'asset_id' => 'integer',
        'original_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'useful_life_years' => 'integer',
        'depreciation_rate' => 'decimal:4',
        'start_date' => 'date',
        'annual_depreciation' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'units_produced' => 'integer',
        'total_units_expected' => 'integer',
    ];

    protected $dates = [
        'start_date',
        'deleted_at',
    ];

    // Depreciation methods
    const DEPRECIATION_METHODS = [
        'straight_line' => 'Straight Line',
        'declining_balance' => 'Declining Balance',
        'double_declining' => 'Double Declining Balance',
        'sum_of_years' => 'Sum of Years Digits',
        'units_of_production' => 'Units of Production',
    ];

    /**
     * Get the asset that this depreciation belongs to.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Scope a query to only include depreciations with a specific method.
     */
    public function scopeWithMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Scope a query to only include depreciations for a specific asset.
     */
    public function scopeForAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    /**
     * Scope a query to only include depreciations started in a specific year.
     */
    public function scopeStartedInYear($query, $year)
    {
        return $query->whereYear('start_date', $year);
    }

    /**
     * Get the depreciation method label.
     */
    public function getMethodLabelAttribute()
    {
        return self::DEPRECIATION_METHODS[$this->method] ?? ucfirst(str_replace('_', ' ', $this->method));
    }

    /**
     * Get the current book value.
     */
    public function getCurrentBookValueAttribute()
    {
        return max(0, $this->original_cost - $this->accumulated_depreciation);
    }

    /**
     * Get the depreciable amount (original cost - salvage value).
     */
    public function getDepreciableAmountAttribute()
    {
        return $this->original_cost - ($this->salvage_value ?? 0);
    }

    /**
     * Get the depreciation percentage completed.
     */
    public function getDepreciationPercentageAttribute()
    {
        if ($this->depreciable_amount <= 0) {
            return 0;
        }

        return min(100, round(($this->accumulated_depreciation / $this->depreciable_amount) * 100, 2));
    }

    /**
     * Get years since depreciation started.
     */
    public function getYearsSinceStartAttribute()
    {
        return $this->start_date ? $this->start_date->diffInYears(now()) : 0;
    }

    /**
     * Get remaining useful life in years.
     */
    public function getRemainingUsefulLifeAttribute()
    {
        return max(0, $this->useful_life_years - $this->years_since_start);
    }

    /**
     * Check if the asset is fully depreciated.
     */
    public function isFullyDepreciated()
    {
        return $this->current_book_value <= ($this->salvage_value ?? 0);
    }

    /**
     * Calculate annual depreciation based on the method.
     */
    public function calculateAnnualDepreciation()
    {
        switch ($this->method) {
            case 'straight_line':
                $this->annual_depreciation = $this->calculateStraightLineDepreciation();
                break;
            case 'declining_balance':
                $this->annual_depreciation = $this->calculateDecliningBalanceDepreciation();
                break;
            case 'double_declining':
                $this->annual_depreciation = $this->calculateDoubleDecliningDepreciation();
                break;
            case 'sum_of_years':
                $this->annual_depreciation = $this->calculateSumOfYearsDepreciation();
                break;
            case 'units_of_production':
                $this->annual_depreciation = $this->calculateUnitsOfProductionDepreciation();
                break;
            default:
                $this->annual_depreciation = 0;
        }

        // Update accumulated depreciation
        $this->updateAccumulatedDepreciation();
    }

    /**
     * Calculate straight line depreciation.
     */
    private function calculateStraightLineDepreciation()
    {
        if ($this->useful_life_years <= 0) {
            return 0;
        }

        return $this->depreciable_amount / $this->useful_life_years;
    }

    /**
     * Calculate declining balance depreciation.
     */
    private function calculateDecliningBalanceDepreciation()
    {
        if (! $this->depreciation_rate || $this->depreciation_rate <= 0) {
            return 0;
        }

        return $this->current_book_value * ($this->depreciation_rate / 100);
    }

    /**
     * Calculate double declining balance depreciation.
     */
    private function calculateDoubleDecliningDepreciation()
    {
        if ($this->useful_life_years <= 0) {
            return 0;
        }

        $rate = (2 / $this->useful_life_years) * 100;

        return $this->current_book_value * ($rate / 100);
    }

    /**
     * Calculate sum of years digits depreciation.
     */
    private function calculateSumOfYearsDepreciation()
    {
        if ($this->useful_life_years <= 0) {
            return 0;
        }

        $sumOfYears = ($this->useful_life_years * ($this->useful_life_years + 1)) / 2;
        $currentYear = min($this->years_since_start + 1, $this->useful_life_years);
        $remainingYears = $this->useful_life_years - $currentYear + 1;

        return $this->depreciable_amount * ($remainingYears / $sumOfYears);
    }

    /**
     * Calculate units of production depreciation.
     */
    private function calculateUnitsOfProductionDepreciation()
    {
        if (! $this->total_units_expected || $this->total_units_expected <= 0) {
            return 0;
        }

        $unitsThisYear = $this->units_produced ?? 0;

        return $this->depreciable_amount * ($unitsThisYear / $this->total_units_expected);
    }

    /**
     * Update accumulated depreciation.
     */
    public function updateAccumulatedDepreciation()
    {
        $totalDepreciation = $this->annual_depreciation * $this->years_since_start;

        // Don't depreciate below salvage value
        $maxDepreciation = $this->depreciable_amount;
        $this->accumulated_depreciation = min($totalDepreciation, $maxDepreciation);
    }

    /**
     * Get depreciation schedule for all years.
     */
    public function getDepreciationSchedule()
    {
        $schedule = [];
        $remainingValue = $this->original_cost;
        $totalAccumulated = 0;

        for ($year = 1; $year <= $this->useful_life_years; $year++) {
            $yearlyDepreciation = $this->calculateYearlyDepreciation($year, $remainingValue);
            $totalAccumulated += $yearlyDepreciation;
            $remainingValue = $this->original_cost - $totalAccumulated;

            // Don't go below salvage value
            if ($remainingValue < ($this->salvage_value ?? 0)) {
                $yearlyDepreciation -= ($this->salvage_value ?? 0) - $remainingValue;
                $totalAccumulated = $this->original_cost - ($this->salvage_value ?? 0);
                $remainingValue = $this->salvage_value ?? 0;
            }

            $schedule[] = [
                'year' => $year,
                'date' => $this->start_date ? $this->start_date->copy()->addYears($year - 1) : null,
                'beginning_value' => $year === 1 ? $this->original_cost : $schedule[$year - 2]['ending_value'],
                'depreciation' => $yearlyDepreciation,
                'accumulated_depreciation' => $totalAccumulated,
                'ending_value' => max($remainingValue, $this->salvage_value ?? 0),
            ];

            if ($remainingValue <= ($this->salvage_value ?? 0)) {
                break;
            }
        }

        return $schedule;
    }

    /**
     * Calculate depreciation for a specific year.
     */
    private function calculateYearlyDepreciation($year, $currentBookValue)
    {
        switch ($this->method) {
            case 'straight_line':
                return $this->depreciable_amount / $this->useful_life_years;

            case 'declining_balance':
                return $currentBookValue * ($this->depreciation_rate / 100);

            case 'double_declining':
                $rate = 2 / $this->useful_life_years;

                return $currentBookValue * $rate;

            case 'sum_of_years':
                $sumOfYears = ($this->useful_life_years * ($this->useful_life_years + 1)) / 2;
                $remainingYears = $this->useful_life_years - $year + 1;

                return $this->depreciable_amount * ($remainingYears / $sumOfYears);

            case 'units_of_production':
                // This would need to be calculated based on actual units produced each year
                return $this->annual_depreciation;

            default:
                return 0;
        }
    }

    /**
     * Get formatted original cost.
     */
    public function getFormattedOriginalCostAttribute()
    {
        return '$'.number_format($this->original_cost, 2);
    }

    /**
     * Get formatted salvage value.
     */
    public function getFormattedSalvageValueAttribute()
    {
        return $this->salvage_value ? '$'.number_format($this->salvage_value, 2) : '$0.00';
    }

    /**
     * Get formatted annual depreciation.
     */
    public function getFormattedAnnualDepreciationAttribute()
    {
        return '$'.number_format($this->annual_depreciation, 2);
    }

    /**
     * Get formatted accumulated depreciation.
     */
    public function getFormattedAccumulatedDepreciationAttribute()
    {
        return '$'.number_format($this->accumulated_depreciation, 2);
    }

    /**
     * Get formatted current book value.
     */
    public function getFormattedCurrentBookValueAttribute()
    {
        return '$'.number_format($this->current_book_value, 2);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($depreciation) {
            $depreciation->calculateAnnualDepreciation();
        });
    }

    /**
     * Get available depreciation methods.
     */
    public static function getDepreciationMethods()
    {
        return self::DEPRECIATION_METHODS;
    }
}
