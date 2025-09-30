<?php

namespace App\Domains\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Intervention\Image\Facades\Image;

class FileUploadService
{
    protected array $config;
    protected array $allowedTypes;
    protected int $maxFileSize;

    public function __construct()
    {
        $this->config = config('media-library', []);
        $this->allowedTypes = $this->config['allowed_file_types']['default'] ?? [];
        $this->maxFileSize = $this->config['max_file_size'] ?? 10 * 1024 * 1024; // 10MB default
    }

    /**
     * Upload a single file
     */
    public function upload(UploadedFile $file, string $collection = 'default', ?HasMedia $model = null): array
    {
        // Validate file
        $validation = $this->validateFile($file, $collection);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'],
                'file' => $file->getClientOriginalName(),
            ];
        }

        try {
            if ($model && $model instanceof HasMedia) {
                // Use Spatie Media Library
                $media = $model->addMediaFromRequest('file')
                    ->toMediaCollection($collection);
                
                return [
                    'success' => true,
                    'file' => [
                        'id' => $media->id,
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                        'url' => $media->getUrl(),
                        'collection' => $collection,
                    ],
                ];
            } else {
                // Direct file storage
                $filename = $this->generateFilename($file);
                $path = $file->storeAs($collection, $filename, $this->config['disk_name']);
                
                return [
                    'success' => true,
                    'file' => [
                        'name' => $file->getClientOriginalName(),
                        'file_name' => $filename,
                        'path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'url' => Storage::disk($this->config['disk_name'])->url($path),
                        'collection' => $collection,
                    ],
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ];
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files, string $collection = 'default', ?HasMedia $model = null): array
    {
        $results = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $results[] = $this->upload($file, $collection, $model);
            }
        }

        return $results;
    }

    /**
     * Validate uploaded file
     */
    public function validateFile(UploadedFile $file, string $collection = 'default'): array
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return [
                'valid' => false,
                'error' => 'Invalid file upload.',
            ];
        }

        // Check file size
        $maxSize = $this->config['max_file_sizes'][$collection] ?? $this->maxFileSize;
        if ($file->getSize() > $maxSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . $this->formatBytes($maxSize) . '.',
            ];
        }

        // Check file type
        $allowedTypes = $this->config['allowed_file_types'][$collection] ?? $this->allowedTypes;
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes),
            ];
        }

        // Additional security checks
        $mimeType = $file->getMimeType();
        if (!$this->isAllowedMimeType($mimeType, $extension)) {
            return [
                'valid' => false,
                'error' => 'File type validation failed.',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $basename = Str::slug($basename);
        
        return $basename . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * Check if mime type is allowed for extension
     */
    protected function isAllowedMimeType(string $mimeType, string $extension): bool
    {
        $allowedMimes = [
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt' => ['text/plain'],
            'rtf' => ['application/rtf', 'text/rtf'],
            'zip' => ['application/zip'],
            'rar' => ['application/x-rar-compressed'],
            '7z' => ['application/x-7z-compressed'],
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav'],
            'ogg' => ['audio/ogg'],
        ];

        return isset($allowedMimes[$extension]) && in_array($mimeType, $allowedMimes[$extension]);
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Delete file
     */
    public function delete(string $path, string $disk = null): bool
    {
        $disk = $disk ?? $this->config['disk_name'];
        
        try {
            return Storage::disk($disk)->delete($path);
        } catch (\Exception $e) {
            logger()->error('Failed to delete file', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get file URL
     */
    public function getUrl(string $path, string $disk = null): string
    {
        $disk = $disk ?? $this->config['disk_name'];
        return Storage::disk($disk)->url($path);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path, string $disk = null): bool
    {
        $disk = $disk ?? $this->config['disk_name'];
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get file size
     */
    public function getSize(string $path, string $disk = null): int
    {
        $disk = $disk ?? $this->config['disk_name'];
        return Storage::disk($disk)->size($path);
    }

    /**
     * Create image thumbnail
     */
    public function createThumbnail(string $path, int $width = 300, int $height = 300, string $disk = null): ?string
    {
        $disk = $disk ?? $this->config['disk_name'];
        
        try {
            $fullPath = Storage::disk($disk)->path($path);
            $thumbnailPath = str_replace('.', '_thumb.', $path);
            $thumbnailFullPath = Storage::disk($disk)->path($thumbnailPath);
            
            $image = Image::make($fullPath);
            $image->fit($width, $height);
            $image->save($thumbnailFullPath);
            
            return $thumbnailPath;
        } catch (\Exception $e) {
            logger()->error('Failed to create thumbnail', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get allowed file types for collection
     */
    public function getAllowedTypes(string $collection = 'default'): array
    {
        return $this->config['allowed_file_types'][$collection] ?? $this->allowedTypes;
    }

    /**
     * Get max file size for collection
     */
    public function getMaxFileSize(string $collection = 'default'): int
    {
        return $this->config['max_file_sizes'][$collection] ?? $this->maxFileSize;
    }
}