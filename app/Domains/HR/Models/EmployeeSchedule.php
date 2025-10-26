<?php

namespace App\Domains\HR\Models;
use App\Domains\Core\Models\User;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSchedule extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'shift_id',
        'scheduled_date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_MISSED = 'missed';

    const STATUS_COMPLETED = 'completed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', today())
            ->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('scheduled_date', $date);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function getDurationMinutes(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    protected static function newFactory()
    {
        return \Database\Factories\Domains\HR\EmployeeScheduleFactory::new();
    }
}
