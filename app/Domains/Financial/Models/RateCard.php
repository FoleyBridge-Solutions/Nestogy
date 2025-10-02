<?php

namespace App\Domains\Financial\Models;

use App\Models\Client;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RateCard extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'description',
        'service_type',
        'hourly_rate',
        'effective_from',
        'effective_to',
        'is_default',
        'is_active',
        'applies_to_all_services',
        'minimum_hours',
        'rounding_increment',
        'rounding_method',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'hourly_rate' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'applies_to_all_services' => 'boolean',
        'minimum_hours' => 'decimal:2',
        'rounding_increment' => 'integer',
    ];

    const ROUNDING_NONE = 'none';
    const ROUNDING_UP = 'up';
    const ROUNDING_DOWN = 'down';
    const ROUNDING_NEAREST = 'nearest';

    const SERVICE_TYPE_STANDARD = 'standard';
    const SERVICE_TYPE_AFTER_HOURS = 'after_hours';
    const SERVICE_TYPE_EMERGENCY = 'emergency';
    const SERVICE_TYPE_WEEKEND = 'weekend';
    const SERVICE_TYPE_HOLIDAY = 'holiday';
    const SERVICE_TYPE_PROJECT = 'project';
    const SERVICE_TYPE_CONSULTING = 'consulting';

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeEffectiveOn($query, Carbon $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForServiceType($query, string $serviceType)
    {
        return $query->where(function ($q) use ($serviceType) {
            $q->where('service_type', $serviceType)
                ->orWhere('applies_to_all_services', true);
        });
    }

    public function isEffective(?Carbon $date = null): bool
    {
        $date = $date ?: now();

        if ($this->effective_from && $date->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && $date->gt($this->effective_to)) {
            return false;
        }

        return true;
    }

    public function calculateBillableHours(float $actualHours): float
    {
        if ($this->minimum_hours && $actualHours < $this->minimum_hours) {
            $actualHours = $this->minimum_hours;
        }

        if ($this->rounding_increment && $this->rounding_method) {
            $actualHours = $this->roundHours($actualHours);
        }

        return round($actualHours, 2);
    }

    protected function roundHours(float $hours): float
    {
        $incrementMinutes = $this->rounding_increment;
        $hoursInIncrement = $incrementMinutes / 60;
        
        switch ($this->rounding_method) {
            case self::ROUNDING_UP:
                return ceil($hours / $hoursInIncrement) * $hoursInIncrement;
            
            case self::ROUNDING_DOWN:
                return floor($hours / $hoursInIncrement) * $hoursInIncrement;
            
            case self::ROUNDING_NEAREST:
                return round($hours / $hoursInIncrement) * $hoursInIncrement;
            
            default:
                return $hours;
        }
    }

    public function calculateAmount(float $hours): float
    {
        $billableHours = $this->calculateBillableHours($hours);
        return round($billableHours * $this->hourly_rate, 2);
    }

    public static function getRoundingMethods(): array
    {
        return [
            self::ROUNDING_NONE => 'No Rounding',
            self::ROUNDING_UP => 'Round Up',
            self::ROUNDING_DOWN => 'Round Down',
            self::ROUNDING_NEAREST => 'Round to Nearest',
        ];
    }

    public static function getServiceTypes(): array
    {
        return [
            self::SERVICE_TYPE_STANDARD => 'Standard Hours',
            self::SERVICE_TYPE_AFTER_HOURS => 'After Hours',
            self::SERVICE_TYPE_EMERGENCY => 'Emergency',
            self::SERVICE_TYPE_WEEKEND => 'Weekend',
            self::SERVICE_TYPE_HOLIDAY => 'Holiday',
            self::SERVICE_TYPE_PROJECT => 'Project Work',
            self::SERVICE_TYPE_CONSULTING => 'Consulting',
        ];
    }

    public static function getDefaultIncrements(): array
    {
        return [
            6 => '6 minutes (0.1 hour)',
            15 => '15 minutes (0.25 hour)',
            30 => '30 minutes (0.5 hour)',
            60 => '60 minutes (1 hour)',
        ];
    }

    public static function findApplicableRate(int $clientId, string $serviceType, ?Carbon $date = null): ?self
    {
        $date = $date ?: now();

        return self::where('client_id', $clientId)
            ->active()
            ->effectiveOn($date)
            ->forServiceType($serviceType)
            ->orderByDesc('is_default')
            ->first();
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($rateCard) {
            if ($rateCard->is_default && $rateCard->client_id) {
                self::where('client_id', $rateCard->client_id)
                    ->where('id', '!=', $rateCard->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
