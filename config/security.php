<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all security-related configuration including password
    | policies, session management, rate limiting, and encryption settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | Define password requirements and policies for user accounts.
    |
    */
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'max_length' => env('PASSWORD_MAX_LENGTH', 128),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', false),
        'symbols_list' => env('PASSWORD_SYMBOLS_LIST', '!@#$%^&*()_+-=[]{}|;:,.<>?'),
        'prevent_reuse' => env('PASSWORD_PREVENT_REUSE', true),
        'reuse_history' => env('PASSWORD_REUSE_HISTORY', 5), // Number of previous passwords to check
        'expire_days' => env('PASSWORD_EXPIRE_DAYS', 90), // 0 to disable
        'expire_warning_days' => env('PASSWORD_EXPIRE_WARNING_DAYS', 14),
        'force_change_on_first_login' => env('PASSWORD_FORCE_CHANGE_FIRST_LOGIN', true),
        'bcrypt_rounds' => env('BCRYPT_ROUNDS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configuration for session management and security.
    |
    */
    'session' => [
        'timeout' => env('SESSION_TIMEOUT', 480), // minutes (8 hours)
        'timeout_warning' => env('SESSION_TIMEOUT_WARNING', 5), // minutes before timeout
        'remember_timeout' => env('REMEMBER_TIMEOUT', 43200), // minutes (30 days)
        'single_session' => env('SESSION_SINGLE_SESSION', false), // Prevent multiple simultaneous sessions
        'regenerate_id' => env('SESSION_REGENERATE_ID', true),
        'secure_cookie' => env('SESSION_SECURE_COOKIE', true), // HTTPS only
        'same_site' => env('SESSION_SAME_SITE', 'lax'), // strict, lax, none
        'http_only' => env('SESSION_HTTP_ONLY', true),
        'encrypt' => env('SESSION_ENCRYPT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Settings for two-factor authentication.
    |
    */
    'two_factor' => [
        'enabled' => env('2FA_ENABLED', true),
        'enforced' => env('2FA_ENFORCED', false), // Force all users to enable 2FA
        'methods' => [
            'totp' => env('2FA_METHOD_TOTP', true), // Time-based One-Time Password
            'sms' => env('2FA_METHOD_SMS', false),
            'email' => env('2FA_METHOD_EMAIL', true),
            'backup_codes' => env('2FA_METHOD_BACKUP_CODES', true),
        ],
        'code_length' => env('2FA_CODE_LENGTH', 6),
        'code_expiry' => env('2FA_CODE_EXPIRY', 10), // minutes
        'backup_codes_count' => env('2FA_BACKUP_CODES_COUNT', 10),
        'remember_device' => env('2FA_REMEMBER_DEVICE', true),
        'remember_days' => env('2FA_REMEMBER_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for various actions to prevent abuse.
    |
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        'login_attempts' => env('RATE_LIMIT_LOGIN', 5),
        'login_decay_minutes' => env('RATE_LIMIT_LOGIN_DECAY', 15),
        'api_requests' => env('RATE_LIMIT_API', 120), // per minute
        'file_uploads' => env('RATE_LIMIT_UPLOADS', 10), // per minute
        'password_reset' => env('RATE_LIMIT_PASSWORD_RESET', 3), // per hour
        'registration' => env('RATE_LIMIT_REGISTRATION', 3), // per hour
        'email_verification' => env('RATE_LIMIT_EMAIL_VERIFICATION', 5), // per hour
        'export_requests' => env('RATE_LIMIT_EXPORTS', 10), // per hour
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Security & Lookup
    |--------------------------------------------------------------------------
    |
    | IP-based security settings including whitelisting, blacklisting,
    | and IP lookup services for enhanced threat detection.
    |
    */
    'ip_security' => [
        'whitelist_enabled' => env('IP_WHITELIST_ENABLED', false),
        'whitelist' => array_filter(explode(',', env('IP_WHITELIST', ''))),
        'blacklist_enabled' => env('IP_BLACKLIST_ENABLED', true),
        'blacklist' => array_filter(explode(',', env('IP_BLACKLIST', ''))),
        'geo_blocking_enabled' => env('GEO_BLOCKING_ENABLED', false),
        'allowed_countries' => array_filter(explode(',', env('GEO_ALLOWED_COUNTRIES', ''))),
        'blocked_countries' => array_filter(explode(',', env('GEO_BLOCKED_COUNTRIES', ''))),
        'high_risk_countries' => array_filter(explode(',', env('HIGH_RISK_COUNTRIES', 'CN,RU,KP,IR'))),
        'vpn_detection' => env('VPN_DETECTION_ENABLED', false),
        'tor_blocking' => env('TOR_BLOCKING_ENABLED', false),
        'block_vpn' => env('BLOCK_VPN_CONNECTIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Lookup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for IP geolocation and threat intelligence services.
    |
    */
    'ip_lookup' => [
        'enabled' => env('NESTOGY_IP_LOOKUP_ENABLED', true),
        'cache_hours' => env('NESTOGY_IP_LOOKUP_CACHE_HOURS', 24),
        'api_timeout' => env('NESTOGY_IP_LOOKUP_TIMEOUT', 10),
        'cleanup_days' => env('NESTOGY_IP_LOOKUP_CLEANUP_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspicious Login Detection
    |--------------------------------------------------------------------------
    |
    | Configuration for detecting and handling suspicious login attempts.
    |
    */
    'suspicious_login' => [
        'enabled' => env('NESTOGY_SUSPICIOUS_LOGIN_ENABLED', true),
        'email_enabled' => env('NESTOGY_SUSPICIOUS_LOGIN_EMAIL_ENABLED', true),
        'token_expiry' => env('NESTOGY_SUSPICIOUS_LOGIN_TOKEN_EXPIRY', 60), // minutes
        'risk_threshold' => env('NESTOGY_SUSPICIOUS_LOGIN_RISK_THRESHOLD', 60), // 0-100
        'auto_block_high_risk' => env('NESTOGY_SUSPICIOUS_LOGIN_AUTO_BLOCK', false),
        'location_analysis' => [
            'country_check' => env('NESTOGY_LOCATION_ANALYSIS_COUNTRY_CHECK', true),
            'region_check' => env('NESTOGY_LOCATION_ANALYSIS_REGION_CHECK', true),
            'isp_check' => env('NESTOGY_LOCATION_ANALYSIS_ISP_CHECK', true),
            'vpn_check' => env('NESTOGY_LOCATION_ANALYSIS_VPN_CHECK', true),
            'distance_threshold' => env('NESTOGY_LOCATION_ANALYSIS_DISTANCE_THRESHOLD', 500), // km
            'impossible_travel_check' => env('NESTOGY_LOCATION_ANALYSIS_IMPOSSIBLE_TRAVEL', true),
        ],
        'device_analysis' => [
            'enabled' => env('NESTOGY_DEVICE_ANALYSIS_ENABLED', true),
            'fingerprint_check' => env('NESTOGY_DEVICE_FINGERPRINT_CHECK', true),
            'new_device_risk' => env('NESTOGY_NEW_DEVICE_RISK_SCORE', 30), // risk points
        ],
        'behavior_analysis' => [
            'enabled' => env('NESTOGY_BEHAVIOR_ANALYSIS_ENABLED', true),
            'failed_attempts_threshold' => env('NESTOGY_FAILED_ATTEMPTS_THRESHOLD', 3),
            'concurrent_sessions_threshold' => env('NESTOGY_CONCURRENT_SESSIONS_THRESHOLD', 2),
        ],
        'trusted_devices' => [
            'enabled' => env('NESTOGY_TRUSTED_DEVICES_ENABLED', true),
            'default_expiry_days' => env('NESTOGY_TRUSTED_DEVICE_EXPIRY_DAYS', 30),
            'auto_extend' => env('NESTOGY_TRUSTED_DEVICE_AUTO_EXTEND', true),
            'max_devices_per_user' => env('NESTOGY_MAX_TRUSTED_DEVICES', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Geo Blocking Configuration
    |--------------------------------------------------------------------------
    |
    | Enhanced geo-blocking with multiple service providers.
    |
    */
    'geo_blocking' => [
        'enabled' => env('GEO_BLOCKING_ENABLED', false),
        'mode' => env('GEO_BLOCKING_MODE', 'block'), // 'allow' or 'block'
        'countries' => array_filter(explode(',', env('GEO_BLOCKING_COUNTRIES', ''))),
        'stealth_mode' => env('GEO_BLOCKING_STEALTH_MODE', false),
        'default_policy' => env('GEO_BLOCKING_DEFAULT_POLICY', 'allow'), // 'allow' or 'block'
        'high_risk_countries' => [
            'CN', 'RU', 'KP', 'IR', 'SY', 'BY', 'MM', 'AF', 'IQ', 'LY', 'SO', 'SS', 'YE', 'VE'
        ],
        'services' => [
            'ipapi' => env('GEO_SERVICE_IPAPI_ENABLED', true),
            'ipgeolocation' => env('GEO_SERVICE_IPGEOLOCATION_ENABLED', false),
            'maxmind' => env('GEO_SERVICE_MAXMIND_ENABLED', false),
        ],
        'api_keys' => [
            'ipgeolocation' => env('IPGEOLOCATION_API_KEY'),
        ],
        'maxmind_database_path' => env('MAXMIND_DATABASE_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for data encryption.
    |
    */
    'encryption' => [
        'algorithm' => env('ENCRYPTION_ALGORITHM', 'AES-256-CBC'),
        'key_rotation_enabled' => env('ENCRYPTION_KEY_ROTATION', false),
        'key_rotation_days' => env('ENCRYPTION_KEY_ROTATION_DAYS', 90),
        'encrypt_at_rest' => env('ENCRYPT_AT_REST', true),
        'encrypt_backups' => env('ENCRYPT_BACKUPS', true),
        'encrypt_exports' => env('ENCRYPT_EXPORTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | HTTP security headers configuration.
    |
    */
    'headers' => [
        'strict_transport_security' => env('HEADER_HSTS', 'max-age=31536000; includeSubDomains'),
        'x_content_type_options' => env('HEADER_X_CONTENT_TYPE', 'nosniff'),
        'x_frame_options' => env('HEADER_X_FRAME', 'SAMEORIGIN'),
        'x_xss_protection' => env('HEADER_X_XSS', '1; mode=block'),
        'referrer_policy' => env('HEADER_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'content_security_policy' => env('HEADER_CSP', "default-src 'self'"),
        'permissions_policy' => env('HEADER_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Cross-Origin Resource Sharing settings.
    |
    */
    'cors' => [
        'enabled' => env('CORS_ENABLED', true),
        'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '*'))),
        'allowed_methods' => array_filter(explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS'))),
        'allowed_headers' => array_filter(explode(',', env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With'))),
        'exposed_headers' => array_filter(explode(',', env('CORS_EXPOSED_HEADERS', ''))),
        'max_age' => env('CORS_MAX_AGE', 86400),
        'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Security
    |--------------------------------------------------------------------------
    |
    | Settings for account security and user management.
    |
    */
    'account' => [
        'lockout_enabled' => env('ACCOUNT_LOCKOUT_ENABLED', true),
        'lockout_attempts' => env('ACCOUNT_LOCKOUT_ATTEMPTS', 5),
        'lockout_duration' => env('ACCOUNT_LOCKOUT_DURATION', 30), // minutes
        'require_email_verification' => env('ACCOUNT_REQUIRE_EMAIL_VERIFICATION', true),
        'email_verification_expiry' => env('ACCOUNT_EMAIL_VERIFICATION_EXPIRY', 24), // hours
        'inactive_days' => env('ACCOUNT_INACTIVE_DAYS', 90), // Auto-disable after days of inactivity
        'delete_inactive_days' => env('ACCOUNT_DELETE_INACTIVE_DAYS', 365), // Auto-delete after days
        'password_reset_expiry' => env('ACCOUNT_PASSWORD_RESET_EXPIRY', 60), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security
    |--------------------------------------------------------------------------
    |
    | API-specific security settings.
    |
    */
    'api' => [
        'key_length' => env('API_KEY_LENGTH', 64),
        'key_prefix' => env('API_KEY_PREFIX', 'nst_'),
        'key_expiry_days' => env('API_KEY_EXPIRY_DAYS', 365), // 0 for no expiry
        'require_https' => env('API_REQUIRE_HTTPS', true),
        'log_requests' => env('API_LOG_REQUESTS', true),
        'signature_required' => env('API_SIGNATURE_REQUIRED', false),
        'signature_algorithm' => env('API_SIGNATURE_ALGORITHM', 'sha256'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Security
    |--------------------------------------------------------------------------
    |
    | Security settings for file handling.
    |
    */
    'files' => [
        'scan_uploads' => env('FILE_SCAN_UPLOADS', true),
        'quarantine_suspicious' => env('FILE_QUARANTINE_SUSPICIOUS', true),
        'block_executables' => env('FILE_BLOCK_EXECUTABLES', true),
        'sanitize_names' => env('FILE_SANITIZE_NAMES', true),
        'check_mime_types' => env('FILE_CHECK_MIME_TYPES', true),
        'max_path_length' => env('FILE_MAX_PATH_LENGTH', 255),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for security audit logging.
    |
    */
    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'log_login_attempts' => env('AUDIT_LOG_LOGIN', true),
        'log_data_access' => env('AUDIT_LOG_DATA_ACCESS', true),
        'log_data_changes' => env('AUDIT_LOG_DATA_CHANGES', true),
        'log_admin_actions' => env('AUDIT_LOG_ADMIN_ACTIONS', true),
        'log_api_access' => env('AUDIT_LOG_API_ACCESS', true),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 365),
        'encrypt_logs' => env('AUDIT_ENCRYPT_LOGS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security
    |--------------------------------------------------------------------------
    |
    | Settings for content filtering and security.
    |
    */
    'content' => [
        'xss_protection' => env('CONTENT_XSS_PROTECTION', true),
        'sql_injection_protection' => env('CONTENT_SQL_INJECTION_PROTECTION', true),
        'html_purifier' => env('CONTENT_HTML_PURIFIER', true),
        'allowed_html_tags' => env('CONTENT_ALLOWED_HTML_TAGS', '<p><br><strong><em><u><a><ul><ol><li>'),
        'strip_scripts' => env('CONTENT_STRIP_SCRIPTS', true),
        'encode_special_chars' => env('CONTENT_ENCODE_SPECIAL_CHARS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Security
    |--------------------------------------------------------------------------
    |
    | Security settings for notifications and alerts.
    |
    */
    'notifications' => [
        'security_alerts' => env('NOTIFY_SECURITY_ALERTS', true),
        'alert_channels' => array_filter(explode(',', env('NOTIFY_ALERT_CHANNELS', 'email,database'))),
        'alert_recipients' => array_filter(explode(',', env('NOTIFY_ALERT_RECIPIENTS', ''))),
        'suspicious_activity' => env('NOTIFY_SUSPICIOUS_ACTIVITY', true),
        'failed_login_threshold' => env('NOTIFY_FAILED_LOGIN_THRESHOLD', 3),
        'new_device_login' => env('NOTIFY_NEW_DEVICE_LOGIN', true),
    ],
];