<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientCalendarEvent extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'client_id',
        'title',
        'description',
        'event_type',
        'location',
        'start_datetime',
        'end_datetime',
        'all_day',
        'recurring',
        'recurrence_rule',
        'status',
        'priority',
        'attendees',
        'created_by',
        'reminder_minutes',
        'notes',
        'custom_fields',
        'accessed_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'all_day' => 'boolean',
        'recurring' => 'boolean',
        'attendees' => 'array',
        'created_by' => 'integer',
        'reminder_minutes' => 'integer',
        'custom_fields' => 'array',
        'accessed_at' => 'datetime',
    ];

    protected $dates = [
        'start_datetime',
        'end_datetime',
        'accessed_at',
        'deleted_at',
    ];

    /**
     * Get the client that owns the event.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the event.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scope a query to only include upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>=', now());
    }

    /**
     * Scope a query to only include past events.
     */
    public function scopePast($query)
    {
        return $query->where('end_datetime', '<', now());
    }

    /**
     * Scope a query to only include events for a specific date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_datetime', [$startDate, $endDate])
              ->orWhereBetween('end_datetime', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('start_datetime', '<=', $startDate)
                     ->where('end_datetime', '>=', $endDate);
              });
        });
    }

    /**
     * Scope a query to only include events by type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope a query to only include events by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if the event is currently happening.
     */
    public function isHappening()
    {
        $now = now();
        return $now->between($this->start_datetime, $this->end_datetime);
    }

    /**
     * Check if the event is in the future.
     */
    public function isUpcoming()
    {
        return $this->start_datetime->isFuture();
    }

    /**
     * Check if the event is in the past.
     */
    public function isPast()
    {
        return $this->end_datetime->isPast();
    }

    /**
     * Get the event duration in minutes.
     */
    public function getDurationMinutesAttribute()
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return null;
        }

        return $this->start_datetime->diffInMinutes($this->end_datetime);
    }

    /**
     * Get the event duration in human readable format.
     */
    public function getDurationHumanAttribute()
    {
        $minutes = $this->duration_minutes;
        
        if (!$minutes) {
            return 'Unknown duration';
        }

        if ($minutes < 60) {
            return $minutes . ' minutes';
        } elseif ($minutes < 1440) { // Less than 24 hours
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            
            if ($remainingMinutes == 0) {
                return $hours . ' hour' . ($hours > 1 ? 's' : '');
            } else {
                return $hours . 'h ' . $remainingMinutes . 'm';
            }
        } else {
            $days = floor($minutes / 1440);
            return $days . ' day' . ($days > 1 ? 's' : '');
        }
    }

    /**
     * Get the event's status color for display.
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'scheduled' => 'blue',
            'confirmed' => 'green',
            'tentative' => 'yellow',
            'cancelled' => 'red',
            'completed' => 'gray',
        ];

        return $colors[$this->status] ?? 'blue';
    }

    /**
     * Get the event's priority color for display.
     */
    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
        ];

        return $colors[$this->priority] ?? 'blue';
    }

    /**
     * Get formatted attendees list.
     */
    public function getFormattedAttendeesAttribute()
    {
        if (!$this->attendees || empty($this->attendees)) {
            return 'No attendees';
        }

        if (count($this->attendees) === 1) {
            return $this->attendees[0];
        }

        return $this->attendees[0] . ' +' . (count($this->attendees) - 1) . ' more';
    }

    /**
     * Check if event has a reminder set.
     */
    public function hasReminder()
    {
        return $this->reminder_minutes && $this->reminder_minutes > 0;
    }

    /**
     * Get available event types.
     */
    public static function getTypes()
    {
        return [
            'meeting' => 'Meeting',
            'call' => 'Phone Call',
            'site_visit' => 'Site Visit',
            'maintenance' => 'Maintenance',
            'project_milestone' => 'Project Milestone',
            'deadline' => 'Deadline',
            'training' => 'Training',
            'presentation' => 'Presentation',
            'review' => 'Review',
            'other' => 'Other',
        ];
    }

    /**
     * Get available event statuses.
     */
    public static function getStatuses()
    {
        return [
            'scheduled' => 'Scheduled',
            'confirmed' => 'Confirmed',
            'tentative' => 'Tentative',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
        ];
    }

    /**
     * Get available priorities.
     */
    public static function getPriorities()
    {
        return [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    /**
     * Get common reminder options in minutes.
     */
    public static function getReminderOptions()
    {
        return [
            0 => 'No reminder',
            5 => '5 minutes before',
            15 => '15 minutes before',
            30 => '30 minutes before',
            60 => '1 hour before',
            120 => '2 hours before',
            1440 => '1 day before',
            2880 => '2 days before',
            10080 => '1 week before',
        ];
    }
}