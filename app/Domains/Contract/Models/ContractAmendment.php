<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ContractAmendment Model
 *
 * Tracks all changes and amendments to contracts throughout their lifecycle.
 * Critical for maintaining audit trail and compliance requirements.
 *
 * @property int $id
 * @property int $contract_id
 * @property int $company_id
 * @property int $amendment_number
 * @property string $amendment_type
 * @property array $changes
 * @property array|null $original_values
 * @property string $reason
 * @property \Illuminate\Support\Carbon $effective_date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $applied_at
 * @property int|null $applied_by
 * @property int $created_by
 * @property string|null $approval_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ContractAmendment extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_amendments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'contract_id',
        'company_id',
        'amendment_number',
        'amendment_type',
        'changes',
        'original_values',
        'reason',
        'effective_date',
        'status',
        'applied_at',
        'applied_by',
        'created_by',
        'approval_notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'contract_id' => 'integer',
        'company_id' => 'integer',
        'amendment_number' => 'integer',
        'changes' => 'array',
        'original_values' => 'array',
        'effective_date' => 'date',
        'applied_at' => 'datetime',
        'applied_by' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Amendment type constants
     */
    const TYPE_RENEWAL = 'renewal';

    const TYPE_PRICING = 'pricing';

    const TYPE_TERM = 'term';

    const TYPE_SLA = 'sla';

    const TYPE_SCOPE = 'scope';

    const TYPE_GENERAL = 'general';

    /**
     * Amendment status constants
     */
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_APPLIED = 'applied';

    const STATUS_REJECTED = 'rejected';

    /**
     * Get the contract this amendment belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the user who created this amendment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who applied this amendment.
     */
    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * Check if amendment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if amendment has been applied.
     */
    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    /**
     * Get available amendment types.
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_RENEWAL => 'Contract Renewal',
            self::TYPE_PRICING => 'Pricing Change',
            self::TYPE_TERM => 'Term Modification',
            self::TYPE_SLA => 'SLA Update',
            self::TYPE_SCOPE => 'Scope Change',
            self::TYPE_GENERAL => 'General Amendment',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_APPLIED => 'Applied',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }
}
