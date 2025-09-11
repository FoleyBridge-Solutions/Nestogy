<?php

namespace App\Domains\Email\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_message_id',
        'filename',
        'content_type',
        'size_bytes',
        'content_id',
        'is_inline',
        'encoding',
        'disposition',
        'storage_disk',
        'storage_path',
        'hash',
        'is_image',
        'thumbnail_path',
        'metadata',
    ];

    protected $casts = [
        'is_inline' => 'boolean',
        'is_image' => 'boolean',
        'metadata' => 'array',
    ];

    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }

    // Helper methods
    public function getFormattedSize(): string
    {
        $bytes = $this->size_bytes;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }

    public function getFileExtension(): string
    {
        return Str::lower(pathinfo($this->filename, PATHINFO_EXTENSION));
    }

    public function getMimeType(): string
    {
        return $this->content_type;
    }

    public function isImageFile(): bool
    {
        return $this->is_image || Str::startsWith($this->content_type, 'image/');
    }

    public function isPdfFile(): bool
    {
        return $this->content_type === 'application/pdf' || 
               $this->getFileExtension() === 'pdf';
    }

    public function isDocumentFile(): bool
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

        return in_array($this->content_type, $documentTypes);
    }

    public function getIconClass(): string
    {
        if ($this->isImageFile()) {
            return 'heroicon-o-photo';
        }

        if ($this->isPdfFile()) {
            return 'heroicon-o-document-text';
        }

        if ($this->isDocumentFile()) {
            return 'heroicon-o-document';
        }

        return match ($this->getFileExtension()) {
            'zip', 'rar', '7z', 'tar', 'gz' => 'heroicon-o-archive-box',
            'mp3', 'wav', 'ogg', 'm4a' => 'heroicon-o-musical-note',
            'mp4', 'avi', 'mov', 'wmv', 'flv' => 'heroicon-o-film',
            'txt' => 'heroicon-o-document-text',
            default => 'heroicon-o-paper-clip'
        };
    }

    public function getUrl(): string
    {
        if (Storage::disk($this->storage_disk)->exists($this->storage_path)) {
            return route('email.attachments.download', $this->id);
        }

        return '#';
    }

    public function getThumbnailUrl(): ?string
    {
        if ($this->thumbnail_path && Storage::disk($this->storage_disk)->exists($this->thumbnail_path)) {
            return route('email.attachments.thumbnail', $this->id);
        }

        return null;
    }

    public function download(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk($this->storage_disk)->download($this->storage_path, $this->filename);
    }

    public function getContents(): string
    {
        return Storage::disk($this->storage_disk)->get($this->storage_path);
    }

    public function delete(): bool
    {
        // Delete file from storage
        if (Storage::disk($this->storage_disk)->exists($this->storage_path)) {
            Storage::disk($this->storage_disk)->delete($this->storage_path);
        }

        // Delete thumbnail if exists
        if ($this->thumbnail_path && Storage::disk($this->storage_disk)->exists($this->thumbnail_path)) {
            Storage::disk($this->storage_disk)->delete($this->thumbnail_path);
        }

        // Delete database record
        return parent::delete();
    }

    // Scopes
    public function scopeImages($query)
    {
        return $query->where('is_image', true);
    }

    public function scopeInline($query)
    {
        return $query->where('is_inline', true);
    }

    public function scopeAttachments($query)
    {
        return $query->where('is_inline', false);
    }

    public function scopeByContentType($query, string $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    public function scopeLargerThan($query, int $sizeBytes)
    {
        return $query->where('size_bytes', '>', $sizeBytes);
    }

    public function scopeSmallerThan($query, int $sizeBytes)
    {
        return $query->where('size_bytes', '<', $sizeBytes);
    }
}