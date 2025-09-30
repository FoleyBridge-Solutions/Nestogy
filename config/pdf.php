<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default PDF Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default PDF driver that will be used to generate
    | PDF documents. You may set this to any of the drivers defined in the
    | "drivers" array below.
    |
    */

    'default' => env('PDF_DRIVER', 'dompdf'),

    /*
    |--------------------------------------------------------------------------
    | PDF Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the PDF drivers for your application. You may
    | even configure multiple drivers for the same PDF library to allow
    | you to have multiple PDF configurations.
    |
    */

    'drivers' => [
        'dompdf' => [
            'driver' => 'dompdf',
            'options' => [
                'enable_php' => false,
                'enable_javascript' => true,
                'enable_remote' => true,
                'paper' => 'a4',
                'orientation' => 'portrait',
                'defines' => [
                    'font_dir' => storage_path('fonts/'),
                    'font_cache' => storage_path('fonts/'),
                    'temp_dir' => sys_get_temp_dir(),
                    'chroot' => realpath(base_path()),
                    'enable_font_subsetting' => false,
                    'pdf_backend' => 'CPDF',
                    'default_media_type' => 'screen',
                    'default_paper_size' => 'a4',
                    'default_font' => 'serif',
                    'dpi' => 96,
                    'enable_php' => false,
                    'enable_javascript' => true,
                    'enable_remote' => true,
                    'font_height_ratio' => 1.1,
                    'enable_html5_parser' => false,
                ],
            ],
        ],

        'spatie' => [
            'driver' => 'spatie',
            'binary_path' => env('WKHTMLTOPDF_PATH', '/usr/local/bin/wkhtmltopdf'),
            'options' => [
                'page-size' => 'A4',
                'orientation' => 'Portrait',
                'margin-top' => '0.75in',
                'margin-right' => '0.75in',
                'margin-bottom' => '0.75in',
                'margin-left' => '0.75in',
                'encoding' => 'UTF-8',
                'javascript-delay' => 1000,
                'no-stop-slow-scripts' => true,
                'enable-local-file-access' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Templates
    |--------------------------------------------------------------------------
    |
    | Here you may define templates for different types of PDF documents
    | that your application generates.
    |
    */

    'templates' => [
        'default' => [
            'view' => 'pdf.default',
            'paper' => 'a4',
            'orientation' => 'portrait',
            'margins' => [
                'top' => 20,
                'right' => 20,
                'bottom' => 20,
                'left' => 20,
            ],
        ],

        'invoice' => [
            'view' => 'pdf.invoice',
            'paper' => 'a4',
            'orientation' => 'portrait',
            'margins' => [
                'top' => 20,
                'right' => 20,
                'bottom' => 20,
                'left' => 20,
            ],
        ],

        'report' => [
            'view' => 'pdf.report',
            'paper' => 'a4',
            'orientation' => 'portrait',
            'margins' => [
                'top' => 20,
                'right' => 20,
                'bottom' => 20,
                'left' => 20,
            ],
        ],

        'ticket' => [
            'view' => 'pdf.ticket',
            'paper' => 'a4',
            'orientation' => 'portrait',
            'margins' => [
                'top' => 15,
                'right' => 15,
                'bottom' => 15,
                'left' => 15,
            ],
        ],

        'asset_report' => [
            'view' => 'pdf.asset-report',
            'paper' => 'a4',
            'orientation' => 'landscape',
            'margins' => [
                'top' => 15,
                'right' => 15,
                'bottom' => 15,
                'left' => 15,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where generated PDFs should be stored and how they should
    | be named.
    |
    */

    'storage' => [
        'disk' => env('PDF_STORAGE_DISK', 's3'),
        'path' => env('PDF_STORAGE_PATH', 'pdfs'),
        'filename_format' => '{type}_{id}_{timestamp}.pdf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security settings for PDF generation.
    |
    */

    'security' => [
        'owner_password' => env('PDF_OWNER_PASSWORD'),
        'user_password' => env('PDF_USER_PASSWORD'),
        'permissions' => [
            'print' => true,
            'modify' => false,
            'copy' => true,
            'add_annotations' => false,
        ],
    ],
];
