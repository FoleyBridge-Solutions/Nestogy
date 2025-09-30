<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ContractComponent Model
 *
 * Represents reusable contract components that can be assembled into contracts
 */
class ContractComponent extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'category',
        'component_type',
        'configuration',
        'pricing_model',
        'dependencies',
        'template_content',
        'variables',
        'status',
        'is_system',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'configuration' => 'array',
        'pricing_model' => 'array',
        'dependencies' => 'array',
        'variables' => 'array',
        'is_system' => 'boolean',
    ];

    // Component categories
    const CATEGORY_SERVICE = 'service';

    const CATEGORY_BILLING = 'billing';

    const CATEGORY_SLA = 'sla';

    const CATEGORY_LEGAL = 'legal';

    // Component statuses
    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_DEPRECATED = 'deprecated';

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ContractComponentAssignment::class, 'component_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Helper methods
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompatibleWith(ContractComponent $other): bool
    {
        $dependencies = $this->dependencies ?? [];

        // Check if this component is incompatible with the other
        $incompatible = $dependencies['incompatible'] ?? [];
        if (in_array($other->component_type, $incompatible)) {
            return false;
        }

        return true;
    }

    public function getRequiredComponents(): array
    {
        $dependencies = $this->dependencies ?? [];

        return $dependencies['required'] ?? [];
    }

    public function calculatePrice(array $variables = []): float
    {
        $pricingModel = $this->pricing_model ?? [];

        if (! isset($pricingModel['type'])) {
            return 0.0;
        }

        switch ($pricingModel['type']) {
            case 'fixed':
                return (float) ($pricingModel['amount'] ?? 0);

            case 'per_unit':
                $units = $variables['units'] ?? 1;
                $rate = (float) ($pricingModel['rate'] ?? 0);

                return $units * $rate;

            case 'tiered':
                return $this->calculateTieredPrice($pricingModel, $variables);

            case 'formula':
                return $this->calculateFormulaPrice($pricingModel, $variables);

            default:
                return 0.0;
        }
    }

    protected function calculateTieredPrice(array $pricingModel, array $variables): float
    {
        $quantity = $variables['quantity'] ?? 1;
        $tiers = $pricingModel['tiers'] ?? [];

        foreach ($tiers as $tier) {
            if ($quantity <= ($tier['max'] ?? PHP_INT_MAX)) {
                return (float) ($tier['rate'] ?? 0) * $quantity;
            }
        }

        return 0.0;
    }

    protected function calculateFormulaPrice(array $pricingModel, array $variables): float
    {
        // Simple formula evaluation - could be enhanced with a proper expression parser
        $formula = $pricingModel['formula'] ?? '';
        $baseRate = (float) ($pricingModel['base_rate'] ?? 0);

        // For now, just return base rate - could implement more complex formulas later
        return $baseRate;
    }

    /**
     * Get available system components
     */
    public static function getSystemComponents(): array
    {
        return [
            // Service Components
            [
                'name' => 'MSP Monitoring',
                'description' => '24/7 network and server monitoring',
                'category' => self::CATEGORY_SERVICE,
                'component_type' => 'msp_monitoring',
                'configuration' => [
                    'monitoring_level' => ['basic', 'advanced', 'premium'],
                    'response_time' => '5_minutes',
                    'escalation_matrix' => true,
                ],
                'pricing_model' => [
                    'type' => 'per_unit',
                    'rate' => 15.00,
                    'unit' => 'device',
                ],
                'template_content' => 'Provider will monitor {{monitoring_level}} parameters on all assigned devices with {{response_time}} response time.',
                'variables' => [
                    ['name' => 'monitoring_level', 'type' => 'select', 'required' => true],
                    ['name' => 'response_time', 'type' => 'select', 'required' => true],
                ],
            ],
            [
                'name' => 'Backup Services',
                'description' => 'Automated backup and disaster recovery',
                'category' => self::CATEGORY_SERVICE,
                'component_type' => 'backup_service',
                'configuration' => [
                    'backup_frequency' => ['daily', 'weekly'],
                    'retention_period' => '30_days',
                    'disaster_recovery' => true,
                ],
                'pricing_model' => [
                    'type' => 'tiered',
                    'tiers' => [
                        ['max' => 10, 'rate' => 20.00],
                        ['max' => 50, 'rate' => 15.00],
                        ['max' => null, 'rate' => 10.00],
                    ],
                ],
                'template_content' => 'Provider will perform {{backup_frequency}} backups with {{retention_period}} retention.',
                'variables' => [
                    ['name' => 'backup_frequency', 'type' => 'select', 'required' => true],
                    ['name' => 'retention_period', 'type' => 'select', 'required' => true],
                ],
            ],
            [
                'name' => 'Help Desk Support',
                'description' => 'End-user technical support services',
                'category' => self::CATEGORY_SERVICE,
                'component_type' => 'help_desk',
                'configuration' => [
                    'support_hours' => ['business_hours', '24x7'],
                    'support_method' => ['phone', 'email', 'chat', 'portal'],
                    'sla_response' => '1_hour',
                ],
                'pricing_model' => [
                    'type' => 'per_unit',
                    'rate' => 8.00,
                    'unit' => 'user',
                ],
                'template_content' => 'Provider will provide {{support_hours}} help desk support via {{support_method}} with {{sla_response}} response time.',
                'variables' => [
                    ['name' => 'support_hours', 'type' => 'select', 'required' => true],
                    ['name' => 'support_method', 'type' => 'multiselect', 'required' => true],
                    ['name' => 'sla_response', 'type' => 'select', 'required' => true],
                ],
            ],

            // SLA Components
            [
                'name' => 'Standard SLA',
                'description' => 'Standard service level agreement terms',
                'category' => self::CATEGORY_SLA,
                'component_type' => 'standard_sla',
                'configuration' => [
                    'uptime_guarantee' => '99.5%',
                    'response_time' => '4_hours',
                    'resolution_time' => '24_hours',
                ],
                'template_content' => 'Provider guarantees {{uptime_guarantee}} uptime with {{response_time}} response and {{resolution_time}} resolution times.',
                'variables' => [
                    ['name' => 'uptime_guarantee', 'type' => 'select', 'required' => true],
                    ['name' => 'response_time', 'type' => 'select', 'required' => true],
                    ['name' => 'resolution_time', 'type' => 'select', 'required' => true],
                ],
            ],

            // Legal Components
            [
                'name' => 'Standard Termination Clause',
                'description' => 'Standard contract termination terms',
                'category' => self::CATEGORY_LEGAL,
                'component_type' => 'termination_clause',
                'configuration' => [
                    'notice_period' => '30_days',
                    'early_termination_fee' => false,
                ],
                'template_content' => 'Either party may terminate this agreement with {{notice_period}} written notice.',
                'variables' => [
                    ['name' => 'notice_period', 'type' => 'select', 'required' => true],
                ],
            ],
        ];
    }
}
