<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * QuoteVersion Model
 *
 * Tracks version history and changes for quotes.
 * Stores snapshots of quote data for audit and comparison purposes.
 *
 * @property int $id
 * @property int $company_id
 * @property int $quote_id
 * @property int $version_number
 * @property array $quote_data
 * @property array $changes
 * @property string|null $change_reason
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class QuoteVersion extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'quote_versions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'quote_id',
        'version_number',
        'quote_data',
        'changes',
        'change_reason',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'quote_id' => 'integer',
        'version_number' => 'integer',
        'quote_data' => 'array',
        'changes' => 'array',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Get the quote this version belongs to.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the user who created this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the version label.
     */
    public function getVersionLabel(): string
    {
        return 'v'.$this->version_number;
    }

    /**
     * Get formatted changes summary.
     */
    public function getChangesSummary(): array
    {
        if (! $this->changes) {
            return [];
        }

        $summary = [];
        foreach ($this->changes as $field => $change) {
            $summary[] = [
                'field' => $field,
                'old_value' => $change['old'] ?? null,
                'new_value' => $change['new'] ?? null,
                'label' => $this->getFieldLabel($field),
            ];
        }

        return $summary;
    }

    /**
     * Get human-readable field label.
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'amount' => 'Amount',
            'discount_amount' => 'Discount Amount',
            'expire_date' => 'Expiration Date',
            'valid_until' => 'Valid Until',
            'note' => 'Notes',
            'terms_conditions' => 'Terms & Conditions',
            'status' => 'Status',
            'approval_status' => 'Approval Status',
        ];

        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Check if this is the latest version.
     */
    public function isLatest(): bool
    {
        $latestVersion = static::where('quote_id', $this->quote_id)
            ->orderBy('version_number', 'desc')
            ->first();

        return $latestVersion && $latestVersion->id === $this->id;
    }

    /**
     * Get the previous version.
     */
    public function getPreviousVersion(): ?QuoteVersion
    {
        return static::where('quote_id', $this->quote_id)
            ->where('version_number', '<', $this->version_number)
            ->orderBy('version_number', 'desc')
            ->first();
    }

    /**
     * Get the next version.
     */
    public function getNextVersion(): ?QuoteVersion
    {
        return static::where('quote_id', $this->quote_id)
            ->where('version_number', '>', $this->version_number)
            ->orderBy('version_number', 'asc')
            ->first();
    }

    /**
     * Scope to get versions for a specific quote.
     */
    public function scopeForQuote($query, int $quoteId)
    {
        return $query->where('quote_id', $quoteId);
    }

    /**
     * Scope to order by version number.
     */
    public function scopeOrderByVersion($query, string $direction = 'asc')
    {
        return $query->orderBy('version_number', $direction);
    }

    /**
     * Create version snapshot from quote.
     */
    public static function createSnapshot(Quote $quote, array $changes = [], ?string $reason = null): QuoteVersion
    {
        // Get the next version number
        $lastVersion = static::where('quote_id', $quote->id)
            ->orderBy('version_number', 'desc')
            ->first();

        $versionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;

        // Prepare quote data snapshot
        $quoteData = $quote->toArray();
        $quoteData['items'] = $quote->items->toArray();

        return static::create([
            'company_id' => $quote->company_id,
            'quote_id' => $quote->id,
            'version_number' => $versionNumber,
            'quote_data' => $quoteData,
            'changes' => $changes,
            'change_reason' => $reason,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Get validation rules for version creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'quote_id' => 'required|integer|exists:quotes,id',
            'version_number' => 'required|integer|min:1',
            'quote_data' => 'required|array',
            'changes' => 'nullable|array',
            'change_reason' => 'nullable|string|max:500',
        ];
    }
}
