<?php

namespace App\Domains\Contract\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ContractComponentAssignment Model
 * 
 * Represents the assignment of a component to a specific contract
 */
class ContractComponentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'component_id',
        'configuration',
        'pricing_override',
        'variable_values',
        'status',
        'sort_order',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'configuration' => 'array',
        'pricing_override' => 'array',
        'variable_values' => 'array',
        'assigned_at' => 'datetime',
    ];

    // Assignment statuses
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';

    /**
     * Relationships
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Contract\Models\Contract::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(ContractComponent::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
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
        return $query->whereHas('component', function ($q) use ($category) {
            $q->where('category', $category);
        });
    }

    /**
     * Helper methods
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function calculatePrice(): float
    {
        // Use pricing override if available, otherwise use component pricing
        if (!empty($this->pricing_override)) {
            return $this->calculateOverridePrice();
        }

        return $this->component->calculatePrice($this->variable_values ?? []);
    }

    protected function calculateOverridePrice(): float
    {
        $override = $this->pricing_override;
        
        if (!isset($override['type'])) {
            return 0.0;
        }

        switch ($override['type']) {
            case 'fixed':
                return (float) ($override['amount'] ?? 0);
                
            case 'percentage':
                $basePrice = $this->component->calculatePrice($this->variable_values ?? []);
                $percentage = (float) ($override['percentage'] ?? 100);
                return $basePrice * ($percentage / 100);
                
            default:
                return 0.0;
        }
    }

    public function getConfigurationValue(string $key, $default = null)
    {
        $config = $this->configuration ?? [];
        return $config[$key] ?? $default;
    }

    public function getVariableValue(string $key, $default = null)
    {
        $variables = $this->variable_values ?? [];
        return $variables[$key] ?? $default;
    }

    public function renderContent(): string
    {
        $template = $this->component->template_content ?? '';
        $variables = $this->variable_values ?? [];
        
        // Simple template variable replacement
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        
        return $template;
    }
}