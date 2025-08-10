<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ClientFile extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'uploaded_by',
        'name',
        'description',
        'folder',
        'original_filename',
        'filename',
        'file_path',
        'file_size',
        'mime_type',
        'file_hash',
        'is_public',
        'download_count',
        'tags',
        'accessed_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'client_id' => 'integer',
        'uploaded_by' => 'integer',
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'download_count' => 'integer',
        'tags' => 'array',
        'accessed_at' => 'datetime',
    ];

    protected $dates = [
        'accessed_at',
        'deleted_at',
    ];

    /**
     * Get the client that owns the file.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    /**
     * Scope a query to only include files in a specific folder.
     */
    public function scopeInFolder($query, $folder)
    {
        return $query->where('folder', $folder);
    }

    /**
     * Scope a query to only include public files.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include private files.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Get the file's size in human readable format.
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
     * Get the file's extension.
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->original_filename, PATHINFO_EXTENSION);
    }

    /**
     * Get the file's icon based on file type.
     */
    public function getFileIconAttribute()
    {
        $extension = strtolower($this->file_extension);
        
        $icons = [
            // Documents
            'pdf' => '📄',
            'doc' => '📝',
            'docx' => '📝',
            'txt' => '📄',
            'rtf' => '📄',
            
            // Spreadsheets
            'xls' => '📊',
            'xlsx' => '📊',
            'csv' => '📊',
            
            // Presentations
            'ppt' => '📋',
            'pptx' => '📋',
            
            // Images
            'jpg' => '🖼️',
            'jpeg' => '🖼️',
            'png' => '🖼️',
            'gif' => '🖼️',
            'bmp' => '🖼️',
            'webp' => '🖼️',
            'svg' => '🖼️',
            
            // Archives
            'zip' => '🗜️',
            'rar' => '🗜️',
            '7z' => '🗜️',
            'tar' => '🗜️',
            'gz' => '🗜️',
            
            // Video
            'mp4' => '🎥',
            'avi' => '🎥',
            'mov' => '🎥',
            'wmv' => '🎥',
            'flv' => '🎥',
            'mkv' => '🎥',
            
            // Audio
            'mp3' => '🎵',
            'wav' => '🎵',
            'flac' => '🎵',
            'aac' => '🎵',
            'ogg' => '🎵',
            
            // Code
            'html' => '🌐',
            'css' => '🎨',
            'js' => '📜',
            'php' => '🐘',
            'py' => '🐍',
            'java' => '☕',
            'cpp' => '⚙️',
            'c' => '⚙️',
            
            // Other
            'exe' => '⚙️',
            'dmg' => '💽',
            'iso' => '💿',
        ];

        return $icons[$extension] ?? '📎';
    }

    /**
     * Get the file type category.
     */
    public function getFileTypeAttribute()
    {
        $extension = strtolower($this->file_extension);
        
        $types = [
            'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'],
            'spreadsheet' => ['xls', 'xlsx', 'csv', 'ods'],
            'presentation' => ['ppt', 'pptx', 'odp'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'],
            'audio' => ['mp3', 'wav', 'flac', 'aac', 'ogg'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'code' => ['html', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c'],
        ];

        foreach ($types as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }

        return 'other';
    }

    /**
     * Check if the file is an image.
     */
    public function isImage()
    {
        return $this->file_type === 'image';
    }

    /**
     * Check if the file is a document.
     */
    public function isDocument()
    {
        return $this->file_type === 'document';
    }

    /**
     * Get the full file URL for downloading.
     */
    public function getDownloadUrlAttribute()
    {
        return route('clients.files.standalone.download', $this);
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
     * Increment download count.
     */
    public function incrementDownloadCount()
    {
        $this->increment('download_count');
        $this->update(['accessed_at' => now()]);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($file) {
            // Delete the physical file when the record is deleted
            $file->deleteFile();
        });
    }

    /**
     * Get available file folders.
     */
    public static function getFolders()
    {
        return [
            'general' => 'General Files',
            'images' => 'Images',
            'documents' => 'Documents',
            'presentations' => 'Presentations',
            'spreadsheets' => 'Spreadsheets',
            'media' => 'Media Files',
            'archives' => 'Archives',
            'backups' => 'Backups',
            'temp' => 'Temporary Files',
        ];
    }
}