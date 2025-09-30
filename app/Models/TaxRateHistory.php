<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tax Rate History Model
 *
 * Maintains audit trail of tax rate changes for compliance.
 * Records all modifications to tax rates with timestamps and user info.
 *
 * @property int $id
 * @property int $company_id
 * @property int $voip_tax_rate_id
 * @property array $old_values
 * @property array $new_values
 * @property array|null $changed_fields
 * @property string|null $change_reason
 * @property string|null $change_description
 * @property int|null $changed_by
 * @property string|null $source
 * @property string|null $batch_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TaxRateHistory extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'tax_rate_history';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'voip_tax_rate_id',
        'old_values',
        'new_values',
        'changed_fields',
        'change_reason',
        'change_description',
        'changed_by',
        'source',
        'batch_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'voip_tax_rate_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'changed_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tax rate this history record belongs to.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(VoIPTaxRate::class, 'voip_tax_rate_id');
    }

    /**
     * Get the user who made the change.
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get the changed fields as a formatted string.
     */
    public function getChangedFieldsText(): string
    {
        if (empty($this->changed_fields)) {
            return 'No specific fields tracked';
        }

        return implode(', ', array_map('ucfirst', $this->changed_fields));
    }

    /**
     * Get a summary of changes made.
     */
    public function getChangesSummary(): array
    {
        $summary = [];

        if (empty($this->old_values) || empty($this->new_values)) {
            return ['No change data available'];
        }

        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;

            if ($oldValue !== $newValue) {
                $summary[] = [
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'formatted' => $this->formatFieldChange($field, $oldValue, $newValue),
                ];
            }
        }

        return $summary;
    }

    /**
     * Format field change for display.
     */
    protected function formatFieldChange(string $field, $oldValue, $newValue): string
    {
        $fieldName = ucfirst(str_replace('_', ' ', $field));

        switch ($field) {
            case 'percentage_rate':
                return "{$fieldName}: {$oldValue}% → {$newValue}%";

            case 'fixed_amount':
            case 'minimum_threshold':
            case 'maximum_amount':
                return "{$fieldName}: \${$oldValue} → \${$newValue}";

            case 'effective_date':
            case 'expiry_date':
                $oldFormatted = $oldValue ? date('M j, Y', strtotime($oldValue)) : 'None';
                $newFormatted = $newValue ? date('M j, Y', strtotime($newValue)) : 'None';

                return "{$fieldName}: {$oldFormatted} → {$newFormatted}";

            case 'is_active':
            case 'is_recoverable':
            case 'is_compound':
                $oldText = $oldValue ? 'Yes' : 'No';
                $newText = $newValue ? 'Yes' : 'No';

                return "{$fieldName}: {$oldText} → {$newText}";

            default:
                return "{$fieldName}: {$oldValue} → {$newValue}";
        }
    }

    /**
     * Scope to get history for a specific tax rate.
     */
    public function scopeForTaxRate($query, int $taxRateId)
    {
        return $query->where('voip_tax_rate_id', $taxRateId);
    }

    /**
     * Scope to get history by change reason.
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('change_reason', $reason);
    }

    /**
     * Scope to get history by source.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope to get history by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by', $userId);
    }

    /**
     * Scope to get recent history.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($history) {
            // Calculate changed fields if not provided
            if (empty($history->changed_fields) && ! empty($history->old_values) && ! empty($history->new_values)) {
                $changedFields = [];

                foreach ($history->new_values as $field => $newValue) {
                    $oldValue = $history->old_values[$field] ?? null;
                    if ($oldValue !== $newValue) {
                        $changedFields[] = $field;
                    }
                }

                $history->changed_fields = $changedFields;
            }
        });
    }
}
