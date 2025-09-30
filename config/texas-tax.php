<?php

return [

    /*
    |--------------------------------------------------------------------------
    | State Tax Data API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for official state tax data APIs.
    | This provides FREE access to official tax jurisdiction rates and
    | address mapping data updated quarterly.
    |
    | Note: This config is maintained for backward compatibility.
    | New implementations should use database-driven configuration
    | in the company_tax_configurations table.
    |
    */

    'api' => [
        'base_url' => env('STATE_TAX_API_URL', 'https://api.comptroller.texas.gov/sift/v1/sift/public'),
        'api_key' => env('STATE_TAX_API_KEY'),
        'timeout' => env('STATE_TAX_TIMEOUT', 60), // seconds
        'retry_attempts' => env('STATE_TAX_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation Settings
    |--------------------------------------------------------------------------
    |
    | Configure how the system automatically updates tax data.
    |
    */

    'automation' => [
        // Enable automatic quarterly updates
        'enabled' => env('STATE_TAX_AUTO_UPDATE', true),

        // Quarters to process (most states release data quarterly)
        'quarters' => ['Q1', 'Q2', 'Q3', 'Q4'],

        // Days to wait after quarter end before checking for new data
        'quarter_delay_days' => 30,

        // Whether to send notifications on successful updates
        'send_notifications' => env('STATE_TAX_NOTIFICATIONS', true),

        // Email addresses to notify on updates
        'notification_emails' => explode(',', env('STATE_TAX_NOTIFICATION_EMAILS', '')),

        // Slack webhook for notifications
        'slack_webhook' => env('STATE_TAX_SLACK_WEBHOOK'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Jurisdiction Prioritization
    |--------------------------------------------------------------------------
    |
    | Jurisdictions to prioritize for address data downloads.
    | Based on population and business activity.
    |
    */

    'priority_jurisdictions' => [
        // Default priority jurisdictions (can be overridden per state)
        'default' => [
            'tier_1' => [], // Major metropolitan areas
            'tier_2' => [], // Large suburban areas
            'tier_3' => [], // Medium business activity
        ],

        // State-specific priority jurisdictions
        'TX' => [
            'tier_1' => [
                '201', // Harris County (Houston)
                '113', // Dallas County
                '029', // Bexar County (San Antonio)
                '453', // Travis County (Austin)
                '439', // Tarrant County (Fort Worth)
            ],
            'tier_2' => [
                '157', // Fort Bend County
                '085', // Collin County
                '121', // Denton County
                '491', // Williamson County
                '139', // Ellis County
            ],
            'tier_3' => [
                '167', // Galveston County
                '339', // Montgomery County
                '257', // Brazoria County
                '215', // Hidalgo County
                '061', // Cameron County
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing downloaded tax data files.
    |
    */

    'storage' => [
        // Local storage path for tax data files
        'path' => storage_path('app/state-tax-data'),

        // Whether to keep downloaded ZIP files after extraction
        'keep_zip_files' => env('STATE_TAX_KEEP_ZIPS', false),

        // Number of quarters of data to retain
        'retention_quarters' => 8,

        // Backup to cloud storage
        'backup_to_cloud' => env('STATE_TAX_CLOUD_BACKUP', false),
        'cloud_disk' => env('STATE_TAX_CLOUD_DISK', 's3'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize performance during large data imports.
    |
    */

    'performance' => [
        // Batch size for database inserts
        'batch_size' => 1000,

        // Memory limit for processing large files
        'memory_limit' => '512M',

        // Enable progress bars in console output
        'show_progress' => true,

        // Use database transactions for imports
        'use_transactions' => true,

        // Parallel processing for multiple jurisdictions
        'parallel_jurisdictions' => false, // Set to true if using queue workers
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | What to do when API access fails.
    |
    */

    'fallback' => [
        // Try local files if API fails
        'use_local_files' => true,

        // Local file path pattern
        'local_file_pattern' => base_path('tax_jurisdiction_rates-{quarter}.csv'),

        // Use cached data if both API and local files fail
        'use_cached_data' => true,

        // Maximum age of cached data (days)
        'max_cached_age_days' => 120,

        // Alert emails when fallback is used
        'fallback_alert_emails' => explode(',', env('STATE_TAX_FALLBACK_ALERTS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | How tax data integrates with the MSP platform.
    |
    */

    'integration' => [
        // Automatically update existing quotes/invoices when rates change
        'auto_update_existing' => false,

        // Log all tax calculations for auditing
        'log_calculations' => true,

        // Cache tax lookups for performance
        'cache_lookups' => true,
        'cache_ttl' => 86400, // 24 hours

        // Default company ID for tax rates
        'default_company_id' => 1,

        // Service types that use state tax rates
        'applicable_services' => [
            'equipment',
            'tangible_goods',
            'software_licenses',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Savings Tracking
    |--------------------------------------------------------------------------
    |
    | Track savings compared to paid tax services.
    |
    */

    'cost_tracking' => [
        // Enable cost savings tracking
        'enabled' => true,

        // Estimated monthly cost of alternatives (for reporting)
        'alternative_costs' => [
            'taxcloud' => 800, // TaxCloud enterprise
            'avalara' => 1200, // Avalara enterprise
            'taxjar' => 600,   // TaxJar enterprise
        ],

        // Our cost (essentially free, just server resources)
        'our_cost' => 0,

        // Track calculations per month for ROI reporting
        'track_calculation_volume' => true,
    ],
];
