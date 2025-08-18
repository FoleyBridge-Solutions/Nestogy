<?php

return [

    /*
     * The disk on which to store added files and derived images by default. Choose
     * one or more of the disks you've configured in config/filesystems.php.
     */
    'disk_name' => env('MEDIA_DISK', 's3'),

    /*
     * The maximum file size of an addition in bytes.
     * If not set the file size is unlimited.
     */
    'max_file_size' => 1024 * 1024 * 10, // 10MB

    /*
     * The class that contains the strategy for determining a media file's path.
     */
    'path_generator' => Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator::class,

    /*
     * The class that contains the strategy for determining how to add uploads to the media library.
     */
    'file_namer' => Spatie\MediaLibrary\Support\FileNamer\DefaultFileNamer::class,

    /*
     * When urls to files get generated, this class will be called. Use the default
     * if your files are stored locally above the site root or on s3.
     */
    'url_generator' => Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator::class,

    /*
     * Moves the file to the media library instead of copying it.
     */
    'moves_media_on_update' => false,

    /*
     * Whether to activate versioning when urls to files get generated.
     * When activated, this attaches a ?v=xx query string to the URL.
     */
    'version_urls' => false,

    /*
     * The class that contains the strategy for determining a media file's new filename,
     * including extension. It should implement MediaLibrary\Support\FileNamer\FileNamer.
     */
    'file_namer' => Spatie\MediaLibrary\Support\FileNamer\DefaultFileNamer::class,

    /*
     * The engine that should perform the image conversions.
     * Should be either `gd` or `imagick`.
     */
    'image_driver' => env('IMAGE_DRIVER', 'gd'),

    /*
     * FFMPEG & FFProbe binaries paths, only used if you try to generate video
     * thumbnails and have installed the php-ffmpeg/php-ffmpeg composer package.
     */
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),

    /*
     * The path where to store temporary files while performing image conversions.
     * If set to null, storage_path('media-library/temp') will be used.
     */
    'temporary_directory_path' => null,

    /*
     * Here you can override the class names of the jobs used by this package. Make sure
     * your custom jobs extend the ones provided by the package.
     */
    'jobs' => [
        'perform_conversions' => Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob::class,
    ],

    /*
     * When using the addMediaFromUrl method you may want to replace the default downloader.
     * This is particularly useful when the url of the image is behind a firewall and
     * need to add additional flags, possibly using curl.
     */
    'media_downloader' => Spatie\MediaLibrary\Downloaders\DefaultDownloader::class,

    'remote' => [
        /*
         * Any extra headers that should be included when uploading media to
         * a remote disk. Even though supported headers may vary between
         * different drivers, a sensible default has been provided.
         *
         * Supported by S3: CacheControl, Expires, ServerSideEncryption,
         * Tagging, UploadId, acl, mimetype, expires, cache-control,
         * content-type, content-disposition, content-encoding, content-length
         */
        'extra_headers' => [
            'CacheControl' => 'max-age=2592000', // 30 days for media files
            'ServerSideEncryption' => 'AES256',
        ],
    ],

    'responsive_images' => [
        /*
         * This class is responsible for calculating the target widths of the responsive
         * images. By default we optimize for filesize and create variations that each are 20%
         * smaller than the previous one. More info in the documentation.
         *
         * https://docs.spatie.be/laravel-medialibrary/v9/advanced-usage/generating-responsive-images
         */
        'width_calculator' => Spatie\MediaLibrary\ResponsiveImages\WidthCalculator\FileSizeOptimizedWidthCalculator::class,

        /*
         * By default rendering media to a responsive image will add some javascript and a tiny placeholder.
         * This ensures that the browser can already determine the correct layout.
         */
        'use_tiny_placeholders' => true,

        /*
         * This class will generate the tiny placeholder used for progressive image loading. By default
         * the media library will use a tiny blurred jpg image.
         */
        'tiny_placeholder_generator' => Spatie\MediaLibrary\ResponsiveImages\TinyPlaceholderGenerator\Blurred::class,
    ],

    /*
     * Define the allowed file types for different media collections
     */
    'allowed_file_types' => [
        'default' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf'],
        'archives' => ['zip', 'rar', '7z', 'tar', 'gz'],
        'videos' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        'audio' => ['mp3', 'wav', 'ogg', 'flac', 'aac'],
    ],

    /*
     * Define maximum file sizes for different media collections (in bytes)
     */
    'max_file_sizes' => [
        'default' => 10 * 1024 * 1024, // 10MB
        'images' => 5 * 1024 * 1024,   // 5MB
        'documents' => 20 * 1024 * 1024, // 20MB
        'videos' => 100 * 1024 * 1024,   // 100MB
        'audio' => 50 * 1024 * 1024,     // 50MB
    ],

    /*
     * Define image conversions for different collections
     */
    'conversions' => [
        'default' => [
            'thumb' => [
                'width' => 300,
                'height' => 300,
                'fit' => 'crop',
            ],
            'preview' => [
                'width' => 800,
                'height' => 600,
                'fit' => 'max',
            ],
        ],
        'avatars' => [
            'thumb' => [
                'width' => 150,
                'height' => 150,
                'fit' => 'crop',
            ],
        ],
        'attachments' => [
            'thumb' => [
                'width' => 200,
                'height' => 200,
                'fit' => 'crop',
            ],
        ],
    ],
];