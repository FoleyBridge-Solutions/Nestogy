<?php

namespace App\Domains\Email\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_account_id',
        'name',
        'path',
        'remote_id',
        'type',
        'message_count',
        'unread_count',
        'is_subscribed',
        'is_selectable',
        'attributes',
        'last_synced_at',
    ];

    protected $casts = [
        'is_subscribed' => 'boolean',
        'is_selectable' => 'boolean',
        'attributes' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function unreadMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class)->where('is_read', false);
    }

    // Helper methods
    public function isSystemFolder(): bool
    {
        return in_array($this->type, ['inbox', 'sent', 'drafts', 'trash', 'spam']);
    }

    public function getDisplayName(): string
    {
        return match ($this->type) {
            'inbox' => 'Inbox',
            'sent' => 'Sent',
            'drafts' => 'Drafts',
            'trash' => 'Trash',
            'spam' => 'Spam',
            default => $this->name
        };
    }

    public function getIcon(): string
    {
        return match ($this->type) {
            'inbox' => 'inbox',
            'sent' => 'paper-airplane',
            'drafts' => 'document-text',
            'trash' => 'trash',
            'spam' => 'exclamation-triangle',
            default => 'folder'
        };
    }

    // Scopes
    public function scopeSystemFolders($query)
    {
        return $query->whereIn('type', ['inbox', 'sent', 'drafts', 'trash', 'spam']);
    }

    public function scopeCustomFolders($query)
    {
        return $query->where('type', 'custom');
    }

    public function scopeSelectable($query)
    {
        return $query->where('is_selectable', true);
    }

    public function scopeSubscribed($query)
    {
        return $query->where('is_subscribed', true);
    }

    public function scopeWithUnreadCount($query)
    {
        return $query->where('unread_count', '>', 0);
    }
}
