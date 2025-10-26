<?php

namespace App\Domains\HR\Models;
use App\Domains\Core\Models\User;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOffRequest extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'start_date',
        'end_date',
        'is_full_day',
        'start_time',
        'end_time',
        'total_hours',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_full_day' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'total_hours' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    const TYPE_VACATION = 'vacation';

    const TYPE_SICK = 'sick';

    const TYPE_PERSONAL = 'personal';

    const TYPE_UNPAID = 'unpaid';

    const TYPE_HOLIDAY = 'holiday';

    const TYPE_BEREAVEMENT = 'bereavement';

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_DENIED = 'denied';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', today());
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    public function getDurationDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_VACATION => 'Vacation',
            self::TYPE_SICK => 'Sick Leave',
            self::TYPE_PERSONAL => 'Personal Day',
            self::TYPE_UNPAID => 'Unpaid Leave',
            self::TYPE_HOLIDAY => 'Holiday',
            self::TYPE_BEREAVEMENT => 'Bereavement',
            default => ucfirst($this->type),
        };
    }
}
