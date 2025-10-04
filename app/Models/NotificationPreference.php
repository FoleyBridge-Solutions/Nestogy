<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'ticket_created',
        'ticket_assigned',
        'ticket_status_changed',
        'ticket_resolved',
        'ticket_comment_added',
        'sla_breach_warning',
        'sla_breached',
        'daily_digest',
        'email_enabled',
        'in_app_enabled',
        'digest_time',
    ];

    protected $casts = [
        'ticket_created' => 'boolean',
        'ticket_assigned' => 'boolean',
        'ticket_status_changed' => 'boolean',
        'ticket_resolved' => 'boolean',
        'ticket_comment_added' => 'boolean',
        'sla_breach_warning' => 'boolean',
        'sla_breached' => 'boolean',
        'daily_digest' => 'boolean',
        'email_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function defaultPreferences(): array
    {
        return [
            'ticket_created' => true,
            'ticket_assigned' => true,
            'ticket_status_changed' => true,
            'ticket_resolved' => true,
            'ticket_comment_added' => true,
            'sla_breach_warning' => true,
            'sla_breached' => true,
            'daily_digest' => false,
            'email_enabled' => true,
            'in_app_enabled' => true,
            'digest_time' => '08:00',
        ];
    }

    public static function getOrCreateForUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            static::defaultPreferences()
        );
    }

    public function shouldNotify(string $eventType): bool
    {
        if (! $this->email_enabled && ! $this->in_app_enabled) {
            return false;
        }

        return (bool) ($this->{$eventType} ?? false);
    }

    public function shouldSendEmail(string $eventType): bool
    {
        return $this->email_enabled && $this->shouldNotify($eventType);
    }

    public function shouldSendInApp(string $eventType): bool
    {
        return $this->in_app_enabled && $this->shouldNotify($eventType);
    }
}
