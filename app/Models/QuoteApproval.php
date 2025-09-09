<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * QuoteApproval Model
 * 
 * Tracks approval workflow for quotes with multi-tier approval process.
 * Supports manager and executive approval levels with comments and timestamps.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $quote_id
 * @property int $user_id
 * @property string $approval_level
 * @property string $status
 * @property string|null $comments
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class QuoteApproval extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'quote_approvals';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'quote_id',
        'user_id',
        'approval_level',
        'status',
        'comments',
        'approved_at',
        'rejected_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'quote_id' => 'integer',
        'user_id' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Approval level enumeration
     */
    const LEVEL_MANAGER = 'manager';
    const LEVEL_EXECUTIVE = 'executive';
    const LEVEL_FINANCE = 'finance';

    /**
     * Approval status enumeration
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the quote this approval belongs to.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the user who performed this approval.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if approval is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if approval is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if approval is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Approve the quote at this level.
     */
    public function approve(string $comments = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'comments' => $comments,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the quote at this level.
     */
    public function reject(string $comments): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'comments' => $comments,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Get the approval level label.
     */
    public function getLevelLabel(): string
    {
        $labels = [
            self::LEVEL_MANAGER => 'Manager',
            self::LEVEL_EXECUTIVE => 'Executive',
            self::LEVEL_FINANCE => 'Finance',
        ];

        return $labels[$this->approval_level] ?? 'Unknown';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    /**
     * Scope to get approvals by level.
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('approval_level', $level);
    }

    /**
     * Scope to get pending approvals.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved approvals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to get rejected approvals.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Get validation rules for approval creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'quote_id' => 'required|integer|exists:quotes,id',
            'user_id' => 'required|integer|exists:users,id',
            'approval_level' => 'required|in:manager,executive,finance',
            'status' => 'required|in:pending,approved,rejected',
            'comments' => 'nullable|string',
        ];
    }

    /**
     * Get available approval levels.
     */
    public static function getAvailableLevels(): array
    {
        return [
            self::LEVEL_MANAGER,
            self::LEVEL_EXECUTIVE,
            self::LEVEL_FINANCE,
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }
}