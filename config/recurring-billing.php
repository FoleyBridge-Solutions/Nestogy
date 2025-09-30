<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VoIP Recurring Billing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the VoIP recurring billing system including performance
    | optimization settings, batch processing limits, and error handling.
    |
    */

    'performance' => [
        // Batch processing limits for high-volume operations
        'bulk_invoice_batch_size' => 500,
        'bulk_usage_processing_batch_size' => 1000,
        'bulk_tax_calculation_batch_size' => 250,

        // Memory management settings
        'memory_limit_override' => '512M',
        'max_execution_time' => 1800, // 30 minutes

        // Queue processing settings
        'queue_connection' => env('RECURRING_BILLING_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('RECURRING_BILLING_QUEUE_NAME', 'recurring-billing'),
        'max_concurrent_jobs' => env('RECURRING_BILLING_MAX_CONCURRENT_JOBS', 5),

        // Database optimization
        'enable_query_optimization' => true,
        'chunk_size' => 100,
        'use_database_transactions' => true,
        'transaction_timeout' => 300,
    ],

    'caching' => [
        // Cache settings for frequently accessed data
        'enable_caching' => env('RECURRING_BILLING_CACHE_ENABLED', true),
        'cache_ttl' => 3600, // 1 hour
        'cache_prefix' => 'recurring_billing',

        // Cached data types
        'cache_client_data' => true,
        'cache_tax_rates' => true,
        'cache_service_tiers' => true,
        'cache_usage_calculations' => true,

        // Cache keys
        'keys' => [
            'client_billing_data' => 'client_billing_data_{client_id}',
            'voip_tax_rates' => 'voip_tax_rates_{jurisdiction}',
            'service_tier_config' => 'service_tier_config_{recurring_id}',
            'usage_summary' => 'usage_summary_{recurring_id}_{date}',
        ],
    ],

    'error_handling' => [
        // Retry configuration
        'max_retries' => 3,
        'retry_delay' => 60, // seconds
        'exponential_backoff' => true,
        'max_retry_delay' => 3600, // 1 hour

        // Error notification settings
        'notify_on_critical_errors' => true,
        'critical_error_threshold' => 10, // failures per hour
        'error_notification_emails' => [
            env('RECURRING_BILLING_ERROR_EMAIL', 'admin@company.com'),
        ],

        // Logging configuration
        'log_level' => env('RECURRING_BILLING_LOG_LEVEL', 'info'),
        'log_channel' => env('RECURRING_BILLING_LOG_CHANNEL', 'daily'),
        'detailed_logging' => env('RECURRING_BILLING_DETAILED_LOGGING', false),
    ],

    'monitoring' => [
        // Performance monitoring
        'enable_performance_monitoring' => true,
        'slow_query_threshold' => 1000, // milliseconds
        'memory_usage_alerts' => true,
        'memory_threshold' => 80, // percentage

        // Health checks
        'health_check_intervals' => [
            'queue_health' => 300, // 5 minutes
            'database_health' => 600, // 10 minutes
            'service_health' => 900, // 15 minutes
        ],

        // Metrics collection
        'collect_metrics' => env('RECURRING_BILLING_COLLECT_METRICS', true),
        'metrics_retention_days' => 30,
    ],

    'automation' => [
        // Automated processing schedules
        'daily_processing_time' => '02:00',
        'weekly_processing_day' => 'sunday',
        'monthly_processing_day' => 1,

        // Processing windows
        'max_processing_window' => 4, // hours
        'overlap_prevention' => true,

        // Failure handling
        'auto_retry_failed_jobs' => true,
        'quarantine_threshold' => 5, // failed attempts
        'manual_review_required' => true,
    ],

    'voip_integration' => [
        // VoIP service provider settings
        'default_tax_jurisdiction' => env('DEFAULT_TAX_JURISDICTION', 'US'),
        'enable_real_time_tax_calculation' => true,
        'tax_calculation_timeout' => 30, // seconds

        // Usage data processing
        'usage_data_retention_months' => 24,
        'real_time_usage_updates' => false,
        'usage_aggregation_interval' => 'hourly',

        // Service tier defaults
        'default_pricing_model' => 'tiered',
        'default_overage_calculation' => 'per_unit',
        'escalation_rounding' => 'up', // up, down, nearest
    ],

    'compliance' => [
        // Regulatory compliance settings
        'enable_audit_logging' => true,
        'audit_retention_years' => 7,
        'gdpr_compliance' => true,
        'data_encryption_at_rest' => true,

        // Tax compliance
        'automatic_tax_updates' => true,
        'tax_validation_required' => true,
        'multi_jurisdiction_support' => true,
    ],

];
