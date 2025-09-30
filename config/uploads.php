<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration related to file uploads including
    | size limits, allowed file types, storage paths, and image processing
    | settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Upload Limits
    |--------------------------------------------------------------------------
    |
    | Define maximum file sizes and upload limits. Sizes are in kilobytes (KB).
    |
    */
    'max_size' => env('UPLOAD_MAX_SIZE', 10240), // 10MB in KB
    'max_size_mb' => env('UPLOAD_MAX_SIZE_MB', 10), // Same as above but in MB for display
    'chunk_size' => env('UPLOAD_CHUNK_SIZE', 2048), // 2MB chunks for large file uploads
    'max_files_per_request' => env('UPLOAD_MAX_FILES_PER_REQUEST', 10),
    'total_max_size' => env('UPLOAD_TOTAL_MAX_SIZE', 102400), // 100MB total per request

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | Define which file extensions are allowed for different upload contexts.
    | Add or remove extensions as needed for your security requirements.
    |
    */
    'allowed_types' => [
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'],
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt', 'ods', 'odp'],
        'archives' => ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'],
        'videos' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'],
        'audio' => ['mp3', 'wav', 'ogg', 'wma', 'aac', 'flac'],
        'code' => ['html', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c', 'h', 'json', 'xml', 'yml', 'yaml'],
        'data' => ['csv', 'sql', 'json', 'xml'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MIME Type Validation
    |--------------------------------------------------------------------------
    |
    | Enable strict MIME type checking for uploaded files.
    |
    */
    'validate_mime_types' => env('UPLOAD_VALIDATE_MIME_TYPES', true),
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Define storage paths for different types of uploads. These paths are
    | relative to the storage/app directory.
    |
    */
    'paths' => [
        'avatars' => env('UPLOAD_PATH_AVATARS', 'avatars'),
        'documents' => env('UPLOAD_PATH_DOCUMENTS', 'documents'),
        'tickets' => env('UPLOAD_PATH_TICKETS', 'tickets'),
        'expenses' => env('UPLOAD_PATH_EXPENSES', 'expenses'),
        'invoices' => env('UPLOAD_PATH_INVOICES', 'invoices'),
        'projects' => env('UPLOAD_PATH_PROJECTS', 'projects'),
        'assets' => env('UPLOAD_PATH_ASSETS', 'assets'),
        'temp' => env('UPLOAD_PATH_TEMP', 'temp'),
        'imports' => env('UPLOAD_PATH_IMPORTS', 'imports'),
        'exports' => env('UPLOAD_PATH_EXPORTS', 'exports'),
        'backups' => env('UPLOAD_PATH_BACKUPS', 'backups'),
        'sops' => env('UPLOAD_PATH_SOPS', 'sops'),
        'knowledge_base' => env('UPLOAD_PATH_KB', 'knowledge-base'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for image processing including thumbnail generation,
    | optimization, and format conversion.
    |
    */
    'image_processing' => [
        'enabled' => env('IMAGE_PROCESSING_ENABLED', true),
        'driver' => env('IMAGE_DRIVER', 'gd'), // gd or imagick
        'quality' => env('IMAGE_QUALITY', 85), // 0-100
        'format' => env('IMAGE_FORMAT', 'webp'), // Target format for conversions
        'strip_metadata' => env('IMAGE_STRIP_METADATA', true),
        'auto_orient' => env('IMAGE_AUTO_ORIENT', true),
        'max_width' => env('IMAGE_MAX_WIDTH', 2048),
        'max_height' => env('IMAGE_MAX_HEIGHT', 2048),
        'maintain_aspect_ratio' => env('IMAGE_MAINTAIN_ASPECT_RATIO', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Generation
    |--------------------------------------------------------------------------
    |
    | Settings for automatic thumbnail generation.
    |
    */
    'thumbnails' => [
        'enabled' => env('THUMBNAILS_ENABLED', true),
        'sizes' => [
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 300, 'height' => 300],
            'large' => ['width' => 600, 'height' => 600],
        ],
        'avatar_sizes' => [32, 64, 128, 256],
        'format' => env('THUMBNAIL_FORMAT', 'webp'),
        'quality' => env('THUMBNAIL_QUALITY', 80),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Naming
    |--------------------------------------------------------------------------
    |
    | Configuration for how uploaded files are named and organized.
    |
    */
    'naming' => [
        'strategy' => env('UPLOAD_NAMING_STRATEGY', 'hash'), // hash, timestamp, original, uuid
        'sanitize_filename' => env('UPLOAD_SANITIZE_FILENAME', true),
        'lowercase_extension' => env('UPLOAD_LOWERCASE_EXTENSION', true),
        'preserve_extension' => env('UPLOAD_PRESERVE_EXTENSION', true),
        'hash_algorithm' => env('UPLOAD_HASH_ALGORITHM', 'sha256'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Virus Scanning
    |--------------------------------------------------------------------------
    |
    | Configuration for virus scanning of uploaded files.
    |
    */
    'virus_scanning' => [
        'enabled' => env('VIRUS_SCANNING_ENABLED', false),
        'engine' => env('VIRUS_SCANNING_ENGINE', 'clamav'), // clamav, custom
        'quarantine_path' => env('VIRUS_QUARANTINE_PATH', 'quarantine'),
        'delete_infected' => env('VIRUS_DELETE_INFECTED', true),
        'scan_on_upload' => env('VIRUS_SCAN_ON_UPLOAD', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Temporary Files
    |--------------------------------------------------------------------------
    |
    | Settings for handling temporary file uploads.
    |
    */
    'temp_files' => [
        'cleanup_enabled' => env('TEMP_FILES_CLEANUP_ENABLED', true),
        'lifetime' => env('TEMP_FILES_LIFETIME', 24), // hours
        'cleanup_probability' => env('TEMP_FILES_CLEANUP_PROBABILITY', 0.1), // 10% chance on each request
    ],

    /*
    |--------------------------------------------------------------------------
    | Direct Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for direct file uploads (e.g., to S3).
    |
    */
    'direct_upload' => [
        'enabled' => env('DIRECT_UPLOAD_ENABLED', false),
        'disk' => env('DIRECT_UPLOAD_DISK', 's3'),
        'presigned_url_timeout' => env('DIRECT_UPLOAD_URL_TIMEOUT', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | File Validation Rules
    |--------------------------------------------------------------------------
    |
    | Additional validation rules for specific file types.
    |
    */
    'validation' => [
        'images' => [
            'min_width' => env('IMAGE_MIN_WIDTH', 10),
            'min_height' => env('IMAGE_MIN_HEIGHT', 10),
            'max_width' => env('IMAGE_MAX_WIDTH', 10000),
            'max_height' => env('IMAGE_MAX_HEIGHT', 10000),
            'aspect_ratios' => [], // e.g., ['1:1', '16:9', '4:3']
        ],
        'documents' => [
            'max_pages' => env('DOCUMENT_MAX_PAGES', 1000),
            'password_protected' => env('DOCUMENT_ALLOW_PASSWORD', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Disk Configuration
    |--------------------------------------------------------------------------
    |
    | Define which storage disk to use for different file types.
    |
    */
    'disks' => [
        'default' => env('UPLOAD_DISK_DEFAULT', 'local'),
        'avatars' => env('UPLOAD_DISK_AVATARS', 'public'),
        'documents' => env('UPLOAD_DISK_DOCUMENTS', 'local'),
        'temp' => env('UPLOAD_DISK_TEMP', 'local'),
        'backups' => env('UPLOAD_DISK_BACKUPS', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for serving files through a CDN.
    |
    */
    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'url' => env('CDN_URL'),
        'pull_zone' => env('CDN_PULL_ZONE'),
        'file_types' => ['images', 'documents'], // Which file types to serve via CDN
    ],
];
