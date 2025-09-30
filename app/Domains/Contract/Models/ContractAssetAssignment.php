<?php

namespace App\Domains\Contract\Models;

use App\Models\Asset;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ContractAssetAssignment Model
 *
 * Represents the assignment of assets to contracts for per-device billing.
 * Supports service configuration, billing rates, and automation.
 *
 * @property int $id
 * @property int $company_id
 * @property int $contract_id
 * @property int $asset_id
 * @property array|null $assigned_services
 * @property array|null $service_pricing
 * @property float $billing_rate
 * @property string $billing_frequency
 * @property array|null $service_configuration
 * @property array|null $monitoring_settings
 * @property array|null $maintenance_schedule
 * @property array|null $backup_configuration
 * @property float $base_monthly_rate
 * @property float $additional_service_charges
 * @property array|null $pricing_modifiers
 * @property array|null $billing_rules
 * @property string $status
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $last_billed_at
 * @property \Illuminate\Support\Carbon|null $next_billing_date
 * @property bool $auto_assigned
 * @property array|null $assignment_rules
 * @property array|null $automation_triggers
 * @property \Illuminate\Support\Carbon|null $last_service_update
 * @property array|null $usage_metrics
 * @property float $current_month_charges
 * @property array|null $billing_history
 * @property array|null $sla_requirements
 * @property array|null $compliance_settings
 * @property array|null $security_requirements
 * @property string|null $assignment_notes
 * @property array|null $metadata
 * @property int|null $assigned_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ContractAssetAssignment extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_asset_assignments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'contract_id',
        'asset_id',
        'assigned_services',
        'service_pricing',
        'billing_rate',
        'billing_frequency',
        'service_configuration',
        'monitoring_settings',
        'maintenance_schedule',
        'backup_configuration',
        'base_monthly_rate',
        'additional_service_charges',
        'pricing_modifiers',
        'billing_rules',
        'status',
        'start_date',
        'end_date',
        'last_billed_at',
        'next_billing_date',
        'auto_assigned',
        'assignment_rules',
        'automation_triggers',
        'last_service_update',
        'usage_metrics',
        'current_month_charges',
        'billing_history',
        'sla_requirements',
        'compliance_settings',
        'security_requirements',
        'assignment_notes',
        'metadata',
        'assigned_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'contract_id' => 'integer',
        'asset_id' => 'integer',
        'assigned_services' => 'array',
        'service_pricing' => 'array',
        'billing_rate' => 'decimal:2',
        'service_configuration' => 'array',
        'monitoring_settings' => 'array',
        'maintenance_schedule' => 'array',
        'backup_configuration' => 'array',
        'base_monthly_rate' => 'decimal:2',
        'additional_service_charges' => 'decimal:2',
        'pricing_modifiers' => 'array',
        'billing_rules' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_billed_at' => 'datetime',
        'next_billing_date' => 'date',
        'auto_assigned' => 'boolean',
        'assignment_rules' => 'array',
        'automation_triggers' => 'array',
        'last_service_update' => 'datetime',
        'usage_metrics' => 'array',
        'current_month_charges' => 'decimal:2',
        'billing_history' => 'array',
        'sla_requirements' => 'array',
        'compliance_settings' => 'array',
        'security_requirements' => 'array',
        'metadata' => 'array',
        'assigned_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_TERMINATED = 'terminated';

    const STATUS_PENDING = 'pending';

    /**
     * Billing frequency constants
     */
    const FREQUENCY_MONTHLY = 'monthly';

    const FREQUENCY_QUARTERLY = 'quarterly';

    const FREQUENCY_ANNUALLY = 'annually';

    const FREQUENCY_ONE_TIME = 'one_time';

    /**
     * Get the contract this assignment belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the asset assigned to this contract.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the user who assigned this asset.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who last updated this assignment.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if assignment is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if assignment is auto-assigned.
     */
    public function isAutoAssigned(): bool
    {
        return $this->auto_assigned;
    }

    /**
     * Get assigned services.
     */
    public function getAssignedServices(): array
    {
        return $this->assigned_services ?? [];
    }

    /**
     * Check if a service is assigned.
     */
    public function hasService(string $service): bool
    {
        return in_array($service, $this->getAssignedServices());
    }

    /**
     * Get service pricing.
     */
    public function getServicePricing(string $service): ?float
    {
        $pricing = $this->service_pricing ?? [];

        return $pricing[$service] ?? null;
    }

    /**
     * Calculate total monthly charges for this asset.
     */
    public function calculateMonthlyCharges(): float
    {
        $total = $this->base_monthly_rate + $this->additional_service_charges;

        // Apply pricing modifiers
        if ($modifiers = $this->pricing_modifiers) {
            if (isset($modifiers['discount'])) {
                $total -= $modifiers['discount'];
            }
            if (isset($modifiers['surcharge'])) {
                $total += $modifiers['surcharge'];
            }
            if (isset($modifiers['percentage_discount'])) {
                $total *= (1 - ($modifiers['percentage_discount'] / 100));
            }
        }

        return max(0, $total);
    }

    /**
     * Get billing schedule based on frequency.
     */
    public function getBillingSchedule(): array
    {
        $schedule = [];
        $startDate = $this->start_date;
        $endDate = $this->end_date ?? now()->addYear();

        $current = $startDate->copy();

        while ($current <= $endDate) {
            $schedule[] = $current->copy();

            switch ($this->billing_frequency) {
                case self::FREQUENCY_MONTHLY:
                    $current->addMonth();
                    break;
                case self::FREQUENCY_QUARTERLY:
                    $current->addQuarter();
                    break;
                case self::FREQUENCY_ANNUALLY:
                    $current->addYear();
                    break;
                default:
                    break 2; // Exit loop for one-time billing
            }
        }

        return $schedule;
    }

    /**
     * Update billing history.
     */
    public function addBillingRecord(array $record): void
    {
        $history = $this->billing_history ?? [];
        $history[] = array_merge($record, ['recorded_at' => now()->toISOString()]);

        $this->update([
            'billing_history' => $history,
            'last_billed_at' => now(),
            'current_month_charges' => $record['amount'] ?? 0,
        ]);
    }

    /**
     * Get monitoring settings.
     */
    public function getMonitoringSettings(): array
    {
        return $this->monitoring_settings ?? [];
    }

    /**
     * Get maintenance schedule.
     */
    public function getMaintenanceSchedule(): array
    {
        return $this->maintenance_schedule ?? [];
    }

    /**
     * Get backup configuration.
     */
    public function getBackupConfiguration(): array
    {
        return $this->backup_configuration ?? [];
    }

    /**
     * Get SLA requirements.
     */
    public function getSlaRequirements(): array
    {
        return $this->sla_requirements ?? [];
    }

    /**
     * Check if assignment meets SLA requirements.
     */
    public function meetsSlaRequirements(): bool
    {
        $requirements = $this->getSlaRequirements();

        if (empty($requirements)) {
            return true;
        }

        // This would implement SLA checking logic
        // For now, assume it meets requirements
        return true;
    }

    /**
     * Update usage metrics.
     */
    public function updateUsageMetrics(array $metrics): void
    {
        $current = $this->usage_metrics ?? [];
        $updated = array_merge($current, $metrics);

        $this->update([
            'usage_metrics' => $updated,
            'last_service_update' => now(),
        ]);
    }

    /**
     * Scope for active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for assignments by contract.
     */
    public function scopeByContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    /**
     * Scope for assignments by asset.
     */
    public function scopeByAsset($query, int $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    /**
     * Scope for auto-assigned assets.
     */
    public function scopeAutoAssigned($query)
    {
        return $query->where('auto_assigned', true);
    }

    /**
     * Scope for assignments by billing frequency.
     */
    public function scopeByBillingFrequency($query, string $frequency)
    {
        return $query->where('billing_frequency', $frequency);
    }

    /**
     * Scope for assignments due for billing.
     */
    public function scopeDueForBilling($query)
    {
        return $query->whereNotNull('next_billing_date')
            ->whereDate('next_billing_date', '<=', now());
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set next billing date on creation
        static::creating(function ($assignment) {
            if (! $assignment->next_billing_date) {
                $assignment->next_billing_date = $assignment->start_date;
            }
        });
    }
}
