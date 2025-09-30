<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SaaS Platform Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings specific to the SaaS
    | functionality of the Nestogy platform.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Platform Company
    |--------------------------------------------------------------------------
    |
    | The company ID that represents the SaaS platform operator.
    | This company manages all tenant subscriptions and billing.
    |
    */

    'platform_company_id' => env('SAAS_PLATFORM_COMPANY_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Trial Configuration
    |--------------------------------------------------------------------------
    |
    | Default trial settings for new SaaS subscriptions.
    |
    */

    'trial' => [
        'days' => env('SAAS_DEFAULT_TRIAL_DAYS', 14),
        'require_payment_method' => env('SAAS_REQUIRE_PAYMENT_METHOD', true),
        'authorization_amount' => env('SAAS_AUTH_AMOUNT', 100), // $1.00 in cents
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Limits
    |--------------------------------------------------------------------------
    |
    | Default limits for SaaS subscriptions.
    |
    */

    'limits' => [
        'max_users_per_company' => env('SAAS_MAX_USERS_PER_COMPANY', 100),
        'max_clients_per_company' => env('SAAS_MAX_CLIENTS_PER_COMPANY', 1000),
        'max_tickets_per_month' => env('SAAS_MAX_TICKETS_PER_MONTH', 1000),
        'storage_limit_gb' => env('SAAS_STORAGE_LIMIT_GB', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for billing and subscription management.
    |
    */

    'billing' => [
        'currency' => env('SAAS_CURRENCY', 'USD'),
        'tax_rate' => env('SAAS_TAX_RATE', 0.08), // 8% default tax rate
        'grace_period_days' => env('SAAS_GRACE_PERIOD_DAYS', 3),
        'auto_suspend_after_days' => env('SAAS_AUTO_SUSPEND_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific SaaS features.
    |
    */

    'features' => [
        'allow_plan_changes' => env('SAAS_ALLOW_PLAN_CHANGES', true),
        'allow_cancellations' => env('SAAS_ALLOW_CANCELLATIONS', true),
        'require_admin_approval' => env('SAAS_REQUIRE_ADMIN_APPROVAL', false),
        'enable_usage_tracking' => env('SAAS_ENABLE_USAGE_TRACKING', true),
        'enable_tenant_suspension' => env('SAAS_ENABLE_TENANT_SUSPENSION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Settings for SaaS-related notifications.
    |
    */

    'notifications' => [
        'trial_warning_days' => [3, 1], // Send warnings 3 days and 1 day before expiration
        'payment_failure_retries' => 3,
        'admin_notification_emails' => [
            env('SAAS_ADMIN_EMAIL', 'admin@nestogy.com'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for third-party service integrations.
    |
    */

    'integrations' => [
        'stripe' => [
            'webhook_endpoint' => env('SAAS_STRIPE_WEBHOOK_ENDPOINT', '/webhooks/stripe'),
            'require_signature_verification' => env('SAAS_STRIPE_VERIFY_SIGNATURES', true),
        ],
        'analytics' => [
            'track_signup_events' => env('SAAS_TRACK_SIGNUPS', true),
            'track_subscription_events' => env('SAAS_TRACK_SUBSCRIPTIONS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Default subscription plan configurations. These can be overridden
    | by database records.
    |
    */

    // Plan definitions moved to database via SubscriptionPlanSeeder
    // This config section is deprecated - use SubscriptionPlan model instead
    'default_plans' => [],

];
