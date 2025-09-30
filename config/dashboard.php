<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the dashboard system,
    | including lazy loading, caching, and performance settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which widgets should be lazy loaded and their loading strategy
    |
    */
    'lazy_loading' => [
        'enabled' => env('DASHBOARD_LAZY_LOADING', true),

        // Widgets that should always load immediately (never lazy)
        'immediate' => [
            'alert-panel',  // Critical alerts should always be visible
            'kpi-grid',     // Key metrics above the fold
        ],

        // Widgets that load when visible in viewport
        'viewport' => [
            'revenue-chart',
            'ticket-chart',
            'client-health',
            'team-performance',
            'sla-monitor',
            'resource-allocation',
            'response-times',
            'financial-kpis',
            'invoice-status',
            'payment-tracking',
            'collection-metrics',
        ],

        // Widgets that load after page is ready
        'deferred' => [
            'activity-feed',
            'quick-actions',
            'overdue-invoices',
            'my-tickets',
            'knowledge-base',
            'customer-satisfaction',
            'recent-solutions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for dashboard widgets
    |
    */
    'cache' => [
        'enabled' => env('DASHBOARD_CACHE_ENABLED', true),

        // Default cache duration in seconds
        'ttl' => env('DASHBOARD_CACHE_TTL', 300), // 5 minutes

        // Widget-specific cache durations
        'widget_ttl' => [
            'kpi-grid' => 300,        // 5 minutes
            'revenue-chart' => 600,    // 10 minutes
            'ticket-chart' => 300,     // 5 minutes
            'client-health' => 900,    // 15 minutes
            'team-performance' => 1800, // 30 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable performance tracking for dashboard widgets
    |
    */
    'performance' => [
        'track_load_times' => env('DASHBOARD_TRACK_PERFORMANCE', true),
        'log_channel' => env('DASHBOARD_LOG_CHANNEL', 'performance'),
        'slow_threshold' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget Refresh Settings
    |--------------------------------------------------------------------------
    |
    | Configure auto-refresh behavior for widgets
    |
    */
    'refresh' => [
        'auto_refresh' => env('DASHBOARD_AUTO_REFRESH', true),
        'interval' => env('DASHBOARD_REFRESH_INTERVAL', 30), // seconds

        // Widgets that should auto-refresh
        'widgets' => [
            'alert-panel' => 15,      // Refresh every 15 seconds
            'activity-feed' => 30,    // Refresh every 30 seconds
            'ticket-queue' => 60,     // Refresh every 60 seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget Priority
    |--------------------------------------------------------------------------
    |
    | Define loading priority for widgets (lower number = higher priority)
    |
    */
    'priority' => [
        'alert-panel' => 1,
        'kpi-grid' => 2,
        'ticket-queue' => 3,
        'revenue-chart' => 4,
        'ticket-chart' => 5,
        'client-health' => 6,
        'team-performance' => 7,
        'sla-monitor' => 8,
        'activity-feed' => 9,
        'quick-actions' => 10,
    ],
];
