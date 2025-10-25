<?php

namespace App\Domains\HR\Models;

use App\Domains\Core\Models\User;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeTimeEntry extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'shift_id',
        'pay_period_id',
        'clock_in',
        'clock_out',
        'total_minutes',
        'regular_minutes',
        'overtime_minutes',
        'double_time_minutes',
        'break_minutes',
        'entry_type',
        'status',
        'clock_in_ip',
        'clock_out_ip',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'notes',
        'rejection_reason',
        'exported_to_payroll',
        'exported_at',
        'payroll_batch_id',
        'metadata',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'total_minutes' => 'integer',
        'regular_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'double_time_minutes' => 'integer',
        'break_minutes' => 'integer',
        'clock_in_latitude' => 'decimal:7',
        'clock_in_longitude' => 'decimal:7',
        'clock_out_latitude' => 'decimal:7',
        'clock_out_longitude' => 'decimal:7',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'exported_to_payroll' => 'boolean',
        'exported_at' => 'datetime',
        'metadata' => 'array',
    ];

    const TYPE_CLOCK = 'clock';

    const TYPE_MANUAL = 'manual';

    const TYPE_IMPORTED = 'imported';

    const TYPE_ADJUSTED = 'adjusted';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_PAID = 'paid';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayPeriod::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function getElapsedMinutes(): int
    {
        if (! $this->clock_in) {
            return 0;
        }

        $endTime = $this->clock_out ?? now();

        return $this->clock_in->diffInMinutes($endTime);
    }

    public function getElapsedHours(): float
    {
        return round($this->getElapsedMinutes() / 60, 2);
    }

    public function getFormattedDuration(): string
    {
        $minutes = $this->total_minutes ?? $this->getElapsedMinutes();
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%d:%02d', $hours, $mins);
    }

    public function getTotalHours(): float
    {
        return round(($this->total_minutes ?? 0) / 60, 2);
    }

    public function getRegularHours(): float
    {
        return round(($this->regular_minutes ?? 0) / 60, 2);
    }

    public function getOvertimeHours(): float
    {
        return round(($this->overtime_minutes ?? 0) / 60, 2);
    }

    public function getDoubleTimeHours(): float
    {
        return round(($this->double_time_minutes ?? 0) / 60, 2);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED]);
    }

    public function scopeForPayPeriod($query, PayPeriod $payPeriod)
    {
        return $query->whereBetween('clock_in', [$payPeriod->start_date, $payPeriod->end_date]);
    }

    public function scopeNotExported($query)
    {
        return $query->where('exported_to_payroll', false);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeDateRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('clock_in', [$start, $end]);
    }

    protected static function newFactory()
    {
        return \Database\Factories\Domains\HR\EmployeeTimeEntryFactory::new();
    }
}
