<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'fileable_type',
        'fileable_id',
        'name',
        'description',
        'file_path',
        'file_name',
        'original_name',
        'file_size',
        'mime_type',
        'file_type',
        'is_public',
        'uploaded_by',
        'metadata',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'fileable_id' => 'integer',
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'uploaded_by' => 'integer',
        'metadata' => 'array',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // File types
    const FILE_TYPES = [
        'image' => 'Image',
        'document' => 'Document',
        'video' => 'Video',
        'audio' => 'Audio',
        'archive' => 'Archive',
        'spreadsheet' => 'Spreadsheet',
        'presentation' => 'Presentation',
        'other' => 'Other',
    ];

    /**
     * Get the parent fileable model (Asset, Client, etc.).
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope to only include files of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope to only include public files.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to only include private files.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope to only include images.
     */
    public function scopeImages($query)
    {
        return $query->where('file_type', 'image');
    }

    /**
     * Get the file type label.
     */
    public function getFileTypeLabelAttribute()
    {
        return self::FILE_TYPES[$this->file_type] ?? ucfirst($this->file_type);
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->file_size;
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . @$units[$factor];
    }

    /**
     * Get the file extension.
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Get the full file URL.
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Check if the file is an image.
     */
    public function isImage()
    {
        return $this->file_type === 'image' || str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the file is a PDF.
     */
    public function isPdf()
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Check if the file is a video.
     */
    public function isVideo()
    {
        return $this->file_type === 'video' || str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if the file is an audio file.
     */
    public function isAudio()
    {
        return $this->file_type === 'audio' || str_starts_with($this->mime_type, 'audio/');
    }

    /**
     * Get the icon class for the file type.
     */
    public function getIconClassAttribute()
    {
        $iconMap = [
            'image' => 'fa-image',
            'document' => 'fa-file-text',
            'video' => 'fa-video',
            'audio' => 'fa-music',
            'archive' => 'fa-archive',
            'spreadsheet' => 'fa-table',
            'presentation' => 'fa-presentation',
        ];

        return $iconMap[$this->file_type] ?? 'fa-file';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-determine file type based on MIME type
        static::saving(function ($file) {
            if (!$file->file_type && $file->mime_type) {
                $file->file_type = $file->determineFileType($file->mime_type);
            }
        });
    }

    /**
     * Determine file type from MIME type.
     */
    private function determineFileType($mimeType)
    {
        $typeMap = [
            'image/' => 'image',
            'video/' => 'video',
            'audio/' => 'audio',
            'application/pdf' => 'document',
            'application/msword' => 'document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
            'application/vnd.ms-excel' => 'spreadsheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'spreadsheet',
            'application/vnd.ms-powerpoint' => 'presentation',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'presentation',
            'application/zip' => 'archive',
            'application/x-rar-compressed' => 'archive',
            'application/x-7z-compressed' => 'archive',
        ];

        foreach ($typeMap as $pattern => $type) {
            if (str_starts_with($mimeType, $pattern) || $mimeType === $pattern) {
                return $type;
            }
        }

        return 'other';
    }

    /**
     * Get available file types.
     */
    public static function getFileTypes()
    {
        return self::FILE_TYPES;
    }
}
