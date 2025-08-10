<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ClientDocument extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'uploaded_by',
        'name',
        'description',
        'category',
        'original_filename',
        'filename',
        'file_path',
        'file_size',
        'mime_type',
        'file_hash',
        'is_confidential',
        'expires_at',
        'tags',
        'version',
        'parent_document_id',
        'accessed_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'client_id' => 'integer',
        'uploaded_by' => 'integer',
        'file_size' => 'integer',
        'is_confidential' => 'boolean',
        'expires_at' => 'datetime',
        'tags' => 'array',
        'version' => 'integer',
        'parent_document_id' => 'integer',
        'accessed_at' => 'datetime',
    ];

    protected $dates = [
        'expires_at',
        'accessed_at',
        'deleted_at',
    ];

    /**
     * Get the client that owns the document.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    /**
     * Get the parent document (for versioning).
     */
    public function parentDocument()
    {
        return $this->belongsTo(self::class, 'parent_document_id');
    }

    /**
     * Get all versions of this document.
     */
    public function versions()
    {
        return $this->hasMany(self::class, 'parent_document_id')->orderBy('version');
    }

    /**
     * Get the latest version of this document.
     */
    public function latestVersion()
    {
        if ($this->parent_document_id) {
            return $this->parentDocument->versions()->latest('version')->first();
        }
        
        return $this->versions()->latest('version')->first() ?: $this;
    }

    /**
     * Scope a query to only include documents of a specific category.
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include confidential documents.
     */
    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }

    /**
     * Scope a query to only include public documents.
     */
    public function scopePublic($query)
    {
        return $query->where('is_confidential', false);
    }

    /**
     * Scope a query to only include non-expired documents.
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include expired documents.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if the document is expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get the document's file size in human readable format.
     */
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * Get the document's file extension.
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->original_filename, PATHINFO_EXTENSION);
    }

    /**
     * Get the document's icon based on file type.
     */
    public function getFileIconAttribute()
    {
        $extension = strtolower($this->file_extension);
        
        $icons = [
            'pdf' => '📄',
            'doc' => '📝',
            'docx' => '📝',
            'txt' => '📄',
            'rtf' => '📄',
            'xls' => '📊',
            'xlsx' => '📊',
            'csv' => '📊',
            'ppt' => '📋',
            'pptx' => '📋',
            'jpg' => '🖼️',
            'jpeg' => '🖼️',
            'png' => '🖼️',
            'gif' => '🖼️',
            'bmp' => '🖼️',
            'zip' => '🗜️',
            'rar' => '🗜️',
            '7z' => '🗜️',
            'mp4' => '🎥',
            'avi' => '🎥',
            'mov' => '🎥',
            'mp3' => '🎵',
            'wav' => '🎵',
        ];

        return $icons[$extension] ?? '📎';
    }

    /**
     * Check if the document is an image.
     */
    public function isImage()
    {
        return in_array(strtolower($this->file_extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }

    /**
     * Check if the document is a PDF.
     */
    public function isPdf()
    {
        return strtolower($this->file_extension) === 'pdf';
    }

    /**
     * Get the full file URL for downloading.
     */
    public function getDownloadUrlAttribute()
    {
        return route('clients.documents.standalone.download', $this);
    }

    /**
     * Get the file storage path.
     */
    public function getStoragePathAttribute()
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Check if the file exists in storage.
     */
    public function fileExists()
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Delete the physical file from storage.
     */
    public function deleteFile()
    {
        if ($this->fileExists()) {
            Storage::delete($this->file_path);
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            // Delete the physical file when the document is deleted
            $document->deleteFile();
        });
    }

    /**
     * Get available document categories.
     */
    public static function getCategories()
    {
        return [
            'contract' => 'Contracts',
            'invoice' => 'Invoices',
            'proposal' => 'Proposals',
            'report' => 'Reports',
            'correspondence' => 'Correspondence',
            'legal' => 'Legal Documents',
            'technical' => 'Technical Documentation',
            'marketing' => 'Marketing Materials',
            'financial' => 'Financial Documents',
            'other' => 'Other',
        ];
    }
}