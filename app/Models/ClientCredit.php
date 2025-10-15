<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientCredit extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'source_type',
        'source_id',
        'amount',
        'used_amount',
        'available_amount',
        'currency',
        'type',
        'status',
        'credit_date',
        'expiry_date',
        'depleted_at',
        'voided_at',
        'reference_number',
        'reason',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'source_id' => 'integer',
        'amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'available_amount' => 'decimal:2',
        'credit_date' => 'date',
        'expiry_date' => 'date',
        'depleted_at' => 'datetime',
        'voided_at' => 'datetime',
        'metadata' => 'array',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const TYPE_OVERPAYMENT = 'overpayment';
    const TYPE_PREPAYMENT = 'prepayment';
    const TYPE_CREDIT_NOTE = 'credit_note';
    const TYPE_PROMOTIONAL = 'promotional';
    const TYPE_GOODWILL = 'goodwill';
    const TYPE_REFUND_CREDIT = 'refund_credit';
    const TYPE_ADJUSTMENT = 'adjustment';

    const STATUS_ACTIVE = 'active';
    const STATUS_DEPLETED = 'depleted';
    const STATUS_EXPIRED = 'expired';
    const STATUS_VOIDED = 'voided';

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ClientCreditApplication::class, 'client_credit_id');
    }

    public function activeApplications(): HasMany
    {
        return $this->applications()->where('is_active', true);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAvailableAmount(): float
    {
        return round($this->amount - $this->activeApplications()->sum('amount'), 2);
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || 
               ($this->expiry_date && $this->expiry_date->isPast());
    }

    public function isDepleted(): bool
    {
        return $this->status === self::STATUS_DEPLETED || $this->getAvailableAmount() <= 0;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               ! $this->isExpired() && 
               ! $this->isDepleted();
    }

    public function canApply(float $amount): bool
    {
        return $this->isActive() && $this->getAvailableAmount() >= $amount;
    }

    public function markAsDepleted(): void
    {
        $this->update([
            'status' => self::STATUS_DEPLETED,
            'depleted_at' => now(),
        ]);
    }

    public function void(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_VOIDED,
            'voided_at' => now(),
            'notes' => $this->notes . "\n\nVoided: " . $reason,
        ]);
    }

    public function recalculateAvailableAmount(): void
    {
        $usedAmount = $this->activeApplications()->sum('amount');
        $availableAmount = $this->amount - $usedAmount;

        $this->update([
            'used_amount' => $usedAmount,
            'available_amount' => $availableAmount,
        ]);

        if ($availableAmount <= 0 && $this->status === self::STATUS_ACTIVE) {
            $this->markAsDepleted();
        }
    }

    public static function generateReferenceNumber(): string
    {
        $companyId = Auth::user()?->company_id;
        $year = now()->year;

        $lastCredit = self::where('company_id', $companyId)
            ->where('reference_number', 'like', "CR-$year-%")
            ->orderBy('reference_number', 'desc')
            ->first();

        if ($lastCredit && preg_match("/^CR-$year-(\d+)$/", $lastCredit->reference_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return 'CR-'.$year.'-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_OVERPAYMENT => 'Overpayment',
            self::TYPE_PREPAYMENT => 'Prepayment',
            self::TYPE_CREDIT_NOTE => 'Credit Note',
            self::TYPE_PROMOTIONAL => 'Promotional',
            self::TYPE_GOODWILL => 'Goodwill',
            self::TYPE_REFUND_CREDIT => 'Refund Credit',
            self::TYPE_ADJUSTMENT => 'Adjustment',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($credit) {
            if (! $credit->company_id) {
                $credit->company_id = Auth::user()?->company_id;
            }

            if (! $credit->created_by) {
                $credit->created_by = Auth::id();
            }

            if (! $credit->reference_number) {
                $credit->reference_number = self::generateReferenceNumber();
            }

            if (! $credit->credit_date) {
                $credit->credit_date = now()->toDateString();
            }

            if (! $credit->available_amount) {
                $credit->available_amount = $credit->amount;
            }

            if (! $credit->status) {
                $credit->status = self::STATUS_ACTIVE;
            }
        });
    }
}
