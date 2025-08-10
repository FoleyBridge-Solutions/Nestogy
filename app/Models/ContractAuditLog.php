<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * ContractAuditLog Model
 * 
 * Comprehensive audit trail for all contract-related activities.
 * Tracks user actions, system events, and maintains compliance records.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $contract_id
 * @property int|null $user_id
 * @property string $action
 * @property string $category
 * @property string $description
 * @property array|null $details
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $occurred_at
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ContractAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'contract_id',
        'user_id',
        'action',
        'category',
        'description',
        'details',
        'ip_address',
        'user_agent',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'details' => 'array',
        'metadata' => 'array',
    ];

    // Category constants
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_APPROVAL = 'approval';
    const CATEGORY_SIGNATURE = 'signature';
    const CATEGORY_COMPLIANCE = 'compliance';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_MILESTONE = 'milestone';
    const CATEGORY_DOCUMENT = 'document';
    const CATEGORY_SYSTEM = 'system';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    /**
     * Accessors & Mutators
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_GENERAL => 'General',
            self::CATEGORY_APPROVAL => 'Approval',
            self::CATEGORY_SIGNATURE => 'Signature',
            self::CATEGORY_COMPLIANCE => 'Compliance',
            self::CATEGORY_FINANCIAL => 'Financial',
            self::CATEGORY_MILESTONE => 'Milestone',
            self::CATEGORY_DOCUMENT => 'Document',
            self::CATEGORY_SYSTEM => 'System',
            default => ucfirst($this->category)
        };
    }

    public function getFormattedDetailsAttribute(): string
    {
        if (empty($this->details)) {
            return '';
        }

        $formatted = [];
        foreach ($this->details as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $formatted[] = ucwords(str_replace('_', ' ', $key)) . ': ' . $value;
        }

        return implode(', ', $formatted);
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($auditLog) {
            if (!$auditLog->company_id && auth()->user()) {
                $auditLog->company_id = auth()->user()->company_id;
            }
            
            if (!$auditLog->occurred_at) {
                $auditLog->occurred_at = now();
            }
        });
    }
}