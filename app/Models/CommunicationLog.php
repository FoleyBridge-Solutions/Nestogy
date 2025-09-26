<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'contact_id',
        'type',
        'channel',
        'contact_name',
        'contact_email',
        'contact_phone',
        'subject',
        'notes',
        'follow_up_required',
        'follow_up_date',
    ];

    protected $casts = [
        'follow_up_required' => 'boolean',
        'follow_up_date' => 'date',
    ];

    const TYPES = [
        'inbound' => 'Inbound',
        'outbound' => 'Outbound',
        'internal' => 'Internal Note',
        'follow_up' => 'Follow Up',
        'meeting' => 'Meeting',
        'support' => 'Support',
        'sales' => 'Sales',
        'billing' => 'Billing',
        'technical' => 'Technical',
        'portal_invitation' => 'Portal Invitation',
        'other' => 'Other',
    ];

    const CHANNELS = [
        'phone' => 'Phone Call',
        'email' => 'Email',
        'sms' => 'SMS/Text',
        'chat' => 'Chat/Instant Message',
        'in_person' => 'In Person',
        'video_call' => 'Video Call',
        'portal' => 'Client Portal',
        'social_media' => 'Social Media',
        'letter' => 'Letter/Mail',
        'fax' => 'Fax',
        'other' => 'Other',
    ];

    /**
     * Get the client that owns the communication log.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the communication log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contact associated with the communication.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get the channel label.
     */
    public function getChannelLabelAttribute(): string
    {
        return self::CHANNELS[$this->channel] ?? $this->channel;
    }

    /**
     * Scope a query to only include communications that need follow up.
     */
    public function scopeNeedingFollowUp($query)
    {
        return $query->where('follow_up_required', true)
                    ->where('follow_up_date', '<=', now());
    }

    /**
     * Scope a query to only include communications by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include communications by channel.
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Get the contact display name.
     */
    public function getContactDisplayNameAttribute(): string
    {
        if ($this->contact) {
            return $this->contact->name;
        }
        
        return $this->contact_name ?: 'N/A';
    }

    /**
     * Get a formatted date for display.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y g:i A');
    }
}