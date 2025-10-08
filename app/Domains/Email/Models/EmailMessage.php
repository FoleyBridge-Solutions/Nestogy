<?php

namespace App\Domains\Email\Models;

use App\Models\CommunicationLog;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Email\Traits\HasEmailScopes;
use App\Domains\Email\Traits\HasEmailStatusOperations;
use App\Domains\Email\Traits\HasEmailHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    use HasFactory;
    use HasEmailScopes;
    use HasEmailStatusOperations;
    use HasEmailHelpers;

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
}
