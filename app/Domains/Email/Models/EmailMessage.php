<?php

namespace App\Domains\Email\Models;

use App\Models\CommunicationLog;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_account_id',
        'email_folder_id',
        'message_id',
        'uid',
        'remote_id',
        'thread_id',
        'reply_to_message_id',
        'subject',
        'from_address',
        'from_name',
        'to_addresses',
        'cc_addresses',
        'bcc_addresses',
        'reply_to_addresses',
        'body_text',
        'body_html',
        'preview',
        'sent_at',
        'received_at',
        'size_bytes',
        'priority',
        'is_read',
        'is_flagged',
        'is_draft',
        'is_answered',
        'is_deleted',
        'has_attachments',
        'is_ticket_created',
        'ticket_id',
        'is_communication_logged',
        'communication_log_id',
        'headers',
        'flags',
    ];

    protected $casts = [
        'to_addresses' => 'array',
        'cc_addresses' => 'array',
        'bcc_addresses' => 'array',
        'reply_to_addresses' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'is_read' => 'boolean',
        'is_flagged' => 'boolean',
        'is_draft' => 'boolean',
        'is_answered' => 'boolean',
        'is_deleted' => 'boolean',
        'has_attachments' => 'boolean',
        'is_ticket_created' => 'boolean',
        'is_communication_logged' => 'boolean',
        'headers' => 'array',
        'flags' => 'array',
    ];

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function emailFolder(): BelongsTo
    {
        return $this->belongsTo(EmailFolder::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }

    public function replyToMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'reply_to_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'reply_to_message_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function communicationLog(): BelongsTo
    {
        return $this->belongsTo(CommunicationLog::class);
    }

    // Helper methods
    public function getThreadMessages(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->thread_id) {
            return collect([$this]);
        }

        return static::where('thread_id', $this->thread_id)
            ->orderBy('sent_at')
            ->get();
    }

    public function generatePreview(int $length = 200): string
    {
        $text = $this->body_text ?: strip_tags($this->body_html);

        return Str::limit($text, $length);
    }

    public function getAllRecipients(): array
    {
        $recipients = [];

        if ($this->to_addresses) {
            $recipients = array_merge($recipients, $this->to_addresses);
        }

        if ($this->cc_addresses) {
            $recipients = array_merge($recipients, $this->cc_addresses);
        }

        if ($this->bcc_addresses) {
            $recipients = array_merge($recipients, $this->bcc_addresses);
        }

        return array_unique($recipients);
    }

    public function isFromClient(): bool
    {
        // Check if the from_address belongs to any client
        return \App\Models\Client::where('email', $this->from_address)
            ->orWhereJsonContains('contact_emails', $this->from_address)
            ->exists();
    }

    public function getClientFromSender(): ?\App\Models\Client
    {
        return \App\Models\Client::where('email', $this->from_address)
            ->orWhereJsonContains('contact_emails', $this->from_address)
            ->first();
    }

    public function markAsRead(): self
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true]);
            $this->emailFolder->decrement('unread_count');
        }

        return $this;
    }

    public function markAsUnread(): self
    {
        if ($this->is_read) {
            $this->update(['is_read' => false]);
            $this->emailFolder->increment('unread_count');
        }

        return $this;
    }

    public function flag(): self
    {
        $this->update(['is_flagged' => true]);

        return $this;
    }

    public function unflag(): self
    {
        $this->update(['is_flagged' => false]);

        return $this;
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function scopeDrafts($query)
    {
        return $query->where('is_draft', true);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeWithAttachments($query)
    {
        return $query->where('has_attachments', true);
    }

    public function scopeInThread($query, string $threadId)
    {
        return $query->where('thread_id', $threadId);
    }

    public function scopeFromDate($query, $date)
    {
        return $query->where('sent_at', '>=', $date);
    }

    public function scopeToDate($query, $date)
    {
        return $query->where('sent_at', '<=', $date);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('subject', 'like', "%{$term}%")
                ->orWhere('body_text', 'like', "%{$term}%")
                ->orWhere('from_address', 'like', "%{$term}%")
                ->orWhere('from_name', 'like', "%{$term}%");
        });
    }

    public function scopeFromSender($query, string $email)
    {
        return $query->where('from_address', $email);
    }

    public function scopeToRecipient($query, string $email)
    {
        return $query->where(function ($q) use ($email) {
            $q->whereJsonContains('to_addresses', $email)
                ->orWhereJsonContains('cc_addresses', $email)
                ->orWhereJsonContains('bcc_addresses', $email);
        });
    }
}
