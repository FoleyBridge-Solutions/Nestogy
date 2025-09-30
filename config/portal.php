<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Client Portal Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the client portal including authentication,
    | invitations, and access control settings.
    |
    */

    'enabled' => env('PORTAL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Portal Authentication Settings
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'max_login_attempts' => env('PORTAL_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('PORTAL_LOCKOUT_DURATION', 30), // minutes
        'session_lifetime' => env('PORTAL_SESSION_LIFETIME', 120), // minutes
        'refresh_lifetime' => env('PORTAL_REFRESH_LIFETIME', 10080), // 7 days in minutes
        'password_min_length' => env('PORTAL_PASSWORD_MIN_LENGTH', 8),
        'password_require_uppercase' => env('PORTAL_PASSWORD_UPPERCASE', true),
        'password_require_lowercase' => env('PORTAL_PASSWORD_LOWERCASE', true),
        'password_require_numbers' => env('PORTAL_PASSWORD_NUMBERS', true),
        'password_require_symbols' => env('PORTAL_PASSWORD_SYMBOLS', false),
        'password_expiry_days' => env('PORTAL_PASSWORD_EXPIRY_DAYS', null),
        'two_factor_enabled' => env('PORTAL_2FA_ENABLED', true),
        'risk_assessment_enabled' => env('PORTAL_RISK_ASSESSMENT', true),
        'device_tracking_enabled' => env('PORTAL_DEVICE_TRACKING', true),
        'geo_blocking_enabled' => env('PORTAL_GEO_BLOCKING', false),
        'high_risk_countries' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Portal Invitation Settings
    |--------------------------------------------------------------------------
    */
    'invitations' => [
        'enabled' => env('PORTAL_INVITATIONS_ENABLED', true),
        'expiration_hours' => env('PORTAL_INVITATION_EXPIRATION', 72),
        'max_per_contact_per_day' => env('PORTAL_INVITATION_MAX_CONTACT', 5),
        'max_per_client_per_day' => env('PORTAL_INVITATION_MAX_CLIENT', 20),
        'require_email_verification' => env('PORTAL_INVITATION_VERIFY_EMAIL', true),
        'auto_login_after_acceptance' => env('PORTAL_INVITATION_AUTO_LOGIN', true),
        'password_requirements' => [
            'min_length' => env('PORTAL_INVITATION_PASSWORD_MIN', 8),
            'require_uppercase' => env('PORTAL_INVITATION_PASSWORD_UPPER', true),
            'require_lowercase' => env('PORTAL_INVITATION_PASSWORD_LOWER', true),
            'require_numbers' => env('PORTAL_INVITATION_PASSWORD_NUMBERS', true),
            'require_special' => env('PORTAL_INVITATION_PASSWORD_SPECIAL', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Portal Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        'contracts' => env('PORTAL_FEATURE_CONTRACTS', true),
        'invoices' => env('PORTAL_FEATURE_INVOICES', true),
        'quotes' => env('PORTAL_FEATURE_QUOTES', true),
        'tickets' => env('PORTAL_FEATURE_TICKETS', true),
        'assets' => env('PORTAL_FEATURE_ASSETS', true),
        'documents' => env('PORTAL_FEATURE_DOCUMENTS', true),
        'payments' => env('PORTAL_FEATURE_PAYMENTS', true),
        'notifications' => env('PORTAL_FEATURE_NOTIFICATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Portal Branding
    |--------------------------------------------------------------------------
    */
    'branding' => [
        'logo' => env('PORTAL_LOGO', null),
        'favicon' => env('PORTAL_FAVICON', null),
        'primary_color' => env('PORTAL_PRIMARY_COLOR', '#3b82f6'),
        'secondary_color' => env('PORTAL_SECONDARY_COLOR', '#1e40af'),
        'custom_css' => env('PORTAL_CUSTOM_CSS', null),
    ],
];
