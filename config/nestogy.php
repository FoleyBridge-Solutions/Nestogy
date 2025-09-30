<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nestogy ERP Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the main configuration settings for the Nestogy ERP
    | system. These settings control various aspects of the application
    | including company defaults, module availability, and system limits.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Company Settings
    |--------------------------------------------------------------------------
    |
    | Default company-wide settings including currency, timezone, and country.
    | These values are used as defaults when creating new companies or when
    | specific settings are not defined.
    |
    */
    'company' => [
        'default_currency' => env('NESTOGY_DEFAULT_CURRENCY', 'USD'),
        'default_timezone' => env('NESTOGY_DEFAULT_TIMEZONE', 'UTC'),
        'default_country' => env('NESTOGY_DEFAULT_COUNTRY', 'US'),
        'default_language' => env('NESTOGY_DEFAULT_LANGUAGE', 'en'),
        'fiscal_year_start' => env('NESTOGY_FISCAL_YEAR_START', '01-01'),
        'date_format' => env('NESTOGY_DATE_FORMAT', 'Y-m-d'),
        'time_format' => env('NESTOGY_TIME_FORMAT', 'H:i:s'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific modules within the ERP system. When a module
    | is disabled, its functionality will not be available to users.
    |
    */
    'modules' => [
        'tickets' => env('NESTOGY_MODULE_TICKETS', true),
        'invoices' => env('NESTOGY_MODULE_INVOICES', true),
        'assets' => env('NESTOGY_MODULE_ASSETS', true),
        'projects' => env('NESTOGY_MODULE_PROJECTS', true),
        'expenses' => env('NESTOGY_MODULE_EXPENSES', true),
        'reports' => env('NESTOGY_MODULE_REPORTS', true),
        'inventory' => env('NESTOGY_MODULE_INVENTORY', true),
        'knowledge_base' => env('NESTOGY_MODULE_KNOWLEDGE_BASE', true),
        'time_tracking' => env('NESTOGY_MODULE_TIME_TRACKING', true),
        'contracts' => env('NESTOGY_MODULE_CONTRACTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | System Limits
    |--------------------------------------------------------------------------
    |
    | Define various system limits including file upload sizes, session
    | timeouts, and other constraints to ensure system stability.
    |
    */
    'limits' => [
        'max_file_size' => env('NESTOGY_MAX_FILE_SIZE', 10240), // KB
        'max_files_per_upload' => env('NESTOGY_MAX_FILES_PER_UPLOAD', 5),
        'session_timeout' => env('NESTOGY_SESSION_TIMEOUT', 480), // minutes
        'api_rate_limit' => env('NESTOGY_API_RATE_LIMIT', 120), // requests per minute
        'export_limit' => env('NESTOGY_EXPORT_LIMIT', 10000), // max records per export
        'import_batch_size' => env('NESTOGY_IMPORT_BATCH_SIZE', 1000),
        'search_results_limit' => env('NESTOGY_SEARCH_RESULTS_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket System Configuration
    |--------------------------------------------------------------------------
    |
    | Settings specific to the ticket management system including priorities,
    | statuses, and auto-assignment rules.
    |
    */
    'tickets' => [
        'auto_assign' => env('NESTOGY_TICKETS_AUTO_ASSIGN', false),
        'default_priority' => env('NESTOGY_TICKETS_DEFAULT_PRIORITY', 'medium'),
        'default_status' => env('NESTOGY_TICKETS_DEFAULT_STATUS', 'open'),
        'auto_close_days' => env('NESTOGY_TICKETS_AUTO_CLOSE_DAYS', 30),
        'allow_guest_tickets' => env('NESTOGY_TICKETS_ALLOW_GUEST', false),
        'require_approval' => env('NESTOGY_TICKETS_REQUIRE_APPROVAL', false),
        'time_tracking_enabled' => env('NESTOGY_TICKETS_TIME_TRACKING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the invoicing system including numbering format, payment
    | terms, and tax configuration.
    |
    */
    'invoices' => [
        'number_format' => env('NESTOGY_INVOICE_NUMBER_FORMAT', 'INV-{YEAR}-{NUMBER}'),
        'starting_number' => env('NESTOGY_INVOICE_STARTING_NUMBER', 1000),
        'default_payment_terms' => env('NESTOGY_INVOICE_PAYMENT_TERMS', 30), // days
        'late_fee_percentage' => env('NESTOGY_INVOICE_LATE_FEE', 0),
        'tax_enabled' => env('NESTOGY_INVOICE_TAX_ENABLED', true),
        'default_tax_rate' => env('NESTOGY_INVOICE_DEFAULT_TAX_RATE', 0),
        'auto_send_reminders' => env('NESTOGY_INVOICE_AUTO_REMINDERS', true),
        'reminder_days' => env('NESTOGY_INVOICE_REMINDER_DAYS', '7,3,1'), // days before due
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Management Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for asset tracking and management including depreciation
    | methods and maintenance scheduling.
    |
    */
    'assets' => [
        'depreciation_enabled' => env('NESTOGY_ASSETS_DEPRECIATION', true),
        'default_depreciation_method' => env('NESTOGY_ASSETS_DEPRECIATION_METHOD', 'straight-line'),
        'maintenance_reminders' => env('NESTOGY_ASSETS_MAINTENANCE_REMINDERS', true),
        'qr_code_enabled' => env('NESTOGY_ASSETS_QR_CODE', true),
        'barcode_type' => env('NESTOGY_ASSETS_BARCODE_TYPE', 'CODE128'),
        'auto_generate_asset_tag' => env('NESTOGY_ASSETS_AUTO_TAG', true),
        'tag_prefix' => env('NESTOGY_ASSETS_TAG_PREFIX', 'ASSET-'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Project Management Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for project management including task assignments, milestones,
    | and project templates.
    |
    */
    'projects' => [
        'enable_templates' => env('NESTOGY_PROJECTS_TEMPLATES', true),
        'enable_milestones' => env('NESTOGY_PROJECTS_MILESTONES', true),
        'enable_gantt_chart' => env('NESTOGY_PROJECTS_GANTT', true),
        'default_task_duration' => env('NESTOGY_PROJECTS_DEFAULT_TASK_DURATION', 8), // hours
        'enable_time_tracking' => env('NESTOGY_PROJECTS_TIME_TRACKING', true),
        'enable_budgets' => env('NESTOGY_PROJECTS_BUDGETS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | System Features
    |--------------------------------------------------------------------------
    |
    | Toggle various system features on or off.
    |
    */
    'features' => [
        'multi_company' => env('NESTOGY_FEATURE_MULTI_COMPANY', true),
        'api_access' => env('NESTOGY_FEATURE_API', true),
        'two_factor_auth' => env('NESTOGY_FEATURE_2FA', true),
        'audit_log' => env('NESTOGY_FEATURE_AUDIT_LOG', true),
        'data_export' => env('NESTOGY_FEATURE_DATA_EXPORT', true),
        'custom_fields' => env('NESTOGY_FEATURE_CUSTOM_FIELDS', true),
        'webhooks' => env('NESTOGY_FEATURE_WEBHOOKS', true),
        'email_to_ticket' => env('NESTOGY_FEATURE_EMAIL_TO_TICKET', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Time Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | Default time tracking and billing settings. These serve as system-wide
    | defaults that can be overridden at company, client, or contract levels.
    |
    */
    'time_tracking' => [
        // Default hourly rates (fallback when no company rates are set)
        'default_rates' => [
            'standard' => env('NESTOGY_DEFAULT_STANDARD_RATE', 150.00),
            'after_hours' => env('NESTOGY_DEFAULT_AFTER_HOURS_RATE', 225.00),
            'emergency' => env('NESTOGY_DEFAULT_EMERGENCY_RATE', 300.00),
            'weekend' => env('NESTOGY_DEFAULT_WEEKEND_RATE', 200.00),
            'holiday' => env('NESTOGY_DEFAULT_HOLIDAY_RATE', 250.00),
        ],

        // Default rate multipliers
        'default_multipliers' => [
            'after_hours' => env('NESTOGY_DEFAULT_AFTER_HOURS_MULTIPLIER', 1.5),
            'emergency' => env('NESTOGY_DEFAULT_EMERGENCY_MULTIPLIER', 2.0),
            'weekend' => env('NESTOGY_DEFAULT_WEEKEND_MULTIPLIER', 1.5),
            'holiday' => env('NESTOGY_DEFAULT_HOLIDAY_MULTIPLIER', 2.0),
        ],

        // Default billing settings
        'default_rate_calculation_method' => env('NESTOGY_DEFAULT_RATE_METHOD', 'fixed_rates'),
        'default_minimum_billing_increment' => env('NESTOGY_DEFAULT_MIN_BILLING', 0.25), // 15 minutes
        'default_time_rounding_method' => env('NESTOGY_DEFAULT_ROUNDING', 'nearest'),

        // Business hours configuration (for determining after-hours rates)
        'business_hours' => [
            'start' => env('NESTOGY_BUSINESS_HOURS_START', '08:00'),
            'end' => env('NESTOGY_BUSINESS_HOURS_END', '18:00'),
            'timezone' => env('NESTOGY_BUSINESS_HOURS_TIMEZONE', 'America/New_York'),
        ],

        // Non-billable ticket types
        'non_billable_ticket_types' => [
            'warranty',
            'internal',
            'training',
            'maintenance',
        ],

        // Auto-detection settings
        'auto_detect_emergency' => env('NESTOGY_AUTO_DETECT_EMERGENCY', true),
        'emergency_keywords' => ['urgent', 'critical', 'down', 'outage', 'emergency'],

        // Time entry approval settings
        'require_approval' => env('NESTOGY_REQUIRE_TIME_APPROVAL', false),
        'approval_threshold_hours' => env('NESTOGY_APPROVAL_THRESHOLD', 8.0),
        'auto_approve_under_threshold' => env('NESTOGY_AUTO_APPROVE_SMALL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | Configuration for system maintenance mode.
    |
    */
    'maintenance' => [
        'enabled' => env('NESTOGY_MAINTENANCE_MODE', false),
        'message' => env('NESTOGY_MAINTENANCE_MESSAGE', 'System is under maintenance. Please check back later.'),
        'allowed_ips' => env('NESTOGY_MAINTENANCE_ALLOWED_IPS', ''),
    ],
];
