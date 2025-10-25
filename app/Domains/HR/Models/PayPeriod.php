<?php

namespace App\Domains\HR\Models;

use App\Domains\Core\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayPeriod extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'start_date',
        'end_date',
        'status',
        'frequency',
        'approved_at',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    const STATUS_OPEN = 'open';

    const STATUS_IN_REVIEW = 'in_review';

    const STATUS_APPROVED = 'approved';

    const STATUS_PAID = 'paid';

    const FREQUENCY_WEEKLY = 'weekly';

    const FREQUENCY_BIWEEKLY = 'biweekly';

    const FREQUENCY_SEMIMONTHLY = 'semimonthly';

    const FREQUENCY_MONTHLY = 'monthly';

    public function timeEntries(): HasMany
    {
        return $this->hasMany(EmployeeTimeEntry::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', today())
            ->where('end_date', '>=', today());
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function getTotalMinutes(): int
    {
        return $this->timeEntries()->sum('total_minutes');
    }

    public function getTotalHours(): float
    {
        return round($this->getTotalMinutes() / 60, 2);
    }

    public function getLabel(): string
    {
        return $this->start_date->format('M d') . ' - ' . $this->end_date->format('M d, Y');
    }

    protected static function newFactory()
    {
        return \Database\Factories\Domains\HR\PayPeriodFactory::new();
    }
}
