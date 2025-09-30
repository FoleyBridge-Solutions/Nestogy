<?php

namespace App\Domains\Ticket\Models;

use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCommentAttachment extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'ticket_comment_id',
        'company_id',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'content',
        'uploaded_by',
    ];

    protected $casts = [
        'ticket_comment_id' => 'integer',
        'company_id' => 'integer',
        'size' => 'integer',
        'uploaded_by' => 'integer',
    ];

    /**
     * Get the comment that owns the attachment
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'ticket_comment_id');
    }

    /**
     * Get the user who uploaded the attachment
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the decoded content
     */
    public function getDecodedContent(): string
    {
        return base64_decode($this->content);
    }

    /**
     * Set content from raw data
     */
    public function setContentFromRaw(string $rawContent): void
    {
        $this->content = base64_encode($rawContent);
    }

    /**
     * Get human readable file size
     */
    public function getHumanReadableSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if attachment is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if attachment is a PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Check if attachment is a document
     */
    public function isDocument(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ];

        return in_array($this->mime_type, $documentTypes);
    }

    /**
     * Get the appropriate icon for the file type
     */
    public function getIcon(): string
    {
        if ($this->isImage()) {
            return 'photo';
        }

        if ($this->isPdf()) {
            return 'document-text';
        }

        if ($this->isDocument()) {
            return 'document';
        }

        return 'paper-clip';
    }

    /**
     * Get the appropriate color for the file type
     */
    public function getColor(): string
    {
        if ($this->isImage()) {
            return 'blue';
        }

        if ($this->isPdf()) {
            return 'red';
        }

        if ($this->isDocument()) {
            return 'green';
        }

        return 'gray';
    }
}
