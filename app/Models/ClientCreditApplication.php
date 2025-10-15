<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ClientCreditApplication extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'client_credit_applications';

    protected $fillable = [
        'company_id',
        'client_credit_id',
        'applicable_type',
        'applicable_id',
        'amount',
        'applied_date',
        'applied_by',
        'is_active',
        'unapplied_at',
        'unapplied_by',
        'unapplication_reason',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_credit_id' => 'integer',
        'applicable_id' => 'integer',
        'amount' => 'decimal:2',
        'applied_date' => 'date',
        'applied_by' => 'integer',
        'is_active' => 'boolean',
        'unapplied_at' => 'datetime',
        'unapplied_by' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function clientCredit(): BelongsTo
    {
        return $this->belongsTo(ClientCredit::class);
    }

    public function applicable(): MorphTo
    {
        return $this->morphTo();
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function unappliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unapplied_by');
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function unapply(string $reason, ?int $userId = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $this->update([
            'is_active' => false,
            'unapplied_at' => now(),
            'unapplied_by' => $userId ?? Auth::id(),
            'unapplication_reason' => $reason,
        ]);

        $this->clientCredit->recalculateAvailableAmount();

        if ($this->applicable instanceof Invoice) {
            $this->applicable->updatePaymentStatus();
        }

        return true;
    }

    public function reapply(?int $userId = null): bool
    {
        if ($this->is_active) {
            return false;
        }

        $this->update([
            'is_active' => true,
            'unapplied_at' => null,
            'unapplied_by' => null,
            'unapplication_reason' => null,
        ]);

        $this->clientCredit->recalculateAvailableAmount();

        if ($this->applicable instanceof Invoice) {
            $this->applicable->updatePaymentStatus();
        }

        return true;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (! $application->company_id) {
                $application->company_id = Auth::user()?->company_id;
            }

            if (! $application->applied_by) {
                $application->applied_by = Auth::id();
            }

            if (! $application->applied_date) {
                $application->applied_date = now()->toDateString();
            }
        });

        static::created(function ($application) {
            $application->clientCredit->recalculateAvailableAmount();
            
            if ($application->applicable instanceof Invoice) {
                $application->applicable->updatePaymentStatus();
            }
        });

        static::deleted(function ($application) {
            $application->clientCredit->recalculateAvailableAmount();
            
            if ($application->applicable instanceof Invoice) {
                $application->applicable->updatePaymentStatus();
            }
        });
    }
}
