<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all notification-related configuration including
    | channels, templates, and notification preferences.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific notification channels. When a channel is
    | disabled, no notifications will be sent through that channel.
    |
    */
    'channels' => [
        'mail' => env('NOTIFICATIONS_EMAIL', true),
        'database' => env('NOTIFICATIONS_DATABASE', true),
        'sms' => env('NOTIFICATIONS_SMS', false),
        'slack' => env('NOTIFICATIONS_SLACK', false),
        'webhook' => env('NOTIFICATIONS_WEBHOOK', false),
        'push' => env('NOTIFICATIONS_PUSH', false),
        'teams' => env('NOTIFICATIONS_TEAMS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default notification settings that apply globally.
    |
    */
    'defaults' => [
        'from_name' => env('NOTIFICATION_FROM_NAME', 'Nestogy ERP'),
        'from_email' => env('NOTIFICATION_FROM_EMAIL', 'noreply@nestogy.com'),
        'reply_to_email' => env('NOTIFICATION_REPLY_TO', 'support@nestogy.com'),
        'footer_text' => env('NOTIFICATION_FOOTER_TEXT', '© ' . date('Y') . ' Nestogy ERP. All rights reserved.'),
        'logo_url' => env('NOTIFICATION_LOGO_URL'),
        'brand_color' => env('NOTIFICATION_BRAND_COLOR', '#4F46E5'),
        'queue_notifications' => env('NOTIFICATION_QUEUE', true),
        'queue_name' => env('NOTIFICATION_QUEUE_NAME', 'notifications'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    |
    | Define email templates for different notification types.
    |
    */
    'templates' => [
        // Ticket notifications
        'ticket_created' => 'emails.tickets.created',
        'ticket_assigned' => 'emails.tickets.assigned',
        'ticket_updated' => 'emails.tickets.updated',
        'ticket_replied' => 'emails.tickets.replied',
        'ticket_closed' => 'emails.tickets.closed',
        'ticket_reopened' => 'emails.tickets.reopened',
        'ticket_escalated' => 'emails.tickets.escalated',
        
        // Invoice notifications
        'invoice_created' => 'emails.invoices.created',
        'invoice_sent' => 'emails.invoices.sent',
        'invoice_paid' => 'emails.invoices.paid',
        'invoice_overdue' => 'emails.invoices.overdue',
        'invoice_reminder' => 'emails.invoices.reminder',
        'invoice_cancelled' => 'emails.invoices.cancelled',
        
        // Payment notifications
        'payment_received' => 'emails.payments.received',
        'payment_failed' => 'emails.payments.failed',
        'payment_refunded' => 'emails.payments.refunded',
        
        // User notifications
        'user_welcome' => 'emails.users.welcome',
        'user_activated' => 'emails.users.activated',
        'user_deactivated' => 'emails.users.deactivated',
        'password_reset' => 'emails.users.password-reset',
        'password_changed' => 'emails.users.password-changed',
        'email_verification' => 'emails.users.email-verification',
        'two_factor_code' => 'emails.users.two-factor-code',
        
        // Project notifications
        'project_created' => 'emails.projects.created',
        'project_assigned' => 'emails.projects.assigned',
        'project_updated' => 'emails.projects.updated',
        'project_completed' => 'emails.projects.completed',
        'project_milestone' => 'emails.projects.milestone',
        'project_task_assigned' => 'emails.projects.task-assigned',
        
        // Asset notifications
        'asset_assigned' => 'emails.assets.assigned',
        'asset_maintenance_due' => 'emails.assets.maintenance-due',
        'asset_warranty_expiring' => 'emails.assets.warranty-expiring',
        'asset_checked_in' => 'emails.assets.checked-in',
        'asset_checked_out' => 'emails.assets.checked-out',
        
        // System notifications
        'backup_completed' => 'emails.system.backup-completed',
        'backup_failed' => 'emails.system.backup-failed',
        'security_alert' => 'emails.system.security-alert',
        'system_error' => 'emails.system.error',
        'maintenance_scheduled' => 'emails.system.maintenance',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Preferences
    |--------------------------------------------------------------------------
    |
    | Define which notifications are enabled by default and their settings.
    |
    */
    'preferences' => [
        'tickets' => [
            'new_ticket' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'ticket_reply' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'ticket_assigned' => ['channels' => ['mail', 'database', 'push'], 'enabled' => true],
            'ticket_status_change' => ['channels' => ['database'], 'enabled' => true],
            'ticket_escalation' => ['channels' => ['mail', 'database', 'sms'], 'enabled' => true],
        ],
        'invoices' => [
            'invoice_created' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'invoice_sent' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'payment_received' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'invoice_overdue' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'payment_reminder' => ['channels' => ['mail'], 'enabled' => true],
        ],
        'projects' => [
            'project_assigned' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'task_assigned' => ['channels' => ['mail', 'database', 'push'], 'enabled' => true],
            'milestone_reached' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'project_completed' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'deadline_approaching' => ['channels' => ['mail', 'database', 'push'], 'enabled' => true],
        ],
        'system' => [
            'security_alert' => ['channels' => ['mail', 'database', 'sms'], 'enabled' => true],
            'backup_status' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'system_maintenance' => ['channels' => ['mail', 'database'], 'enabled' => true],
            'error_notification' => ['channels' => ['mail', 'slack'], 'enabled' => true],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Settings specific to SMS notifications.
    |
    */
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'twilio'), // twilio, nexmo, aws-sns
        'from_number' => env('SMS_FROM_NUMBER'),
        'country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '+1'),
        'max_length' => env('SMS_MAX_LENGTH', 160),
        'unicode_enabled' => env('SMS_UNICODE_ENABLED', false),
        'delivery_reports' => env('SMS_DELIVERY_REPORTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for push notifications to mobile and web apps.
    |
    */
    'push' => [
        'provider' => env('PUSH_PROVIDER', 'fcm'), // fcm, apns, onesignal
        'fcm_server_key' => env('FCM_SERVER_KEY'),
        'apns_certificate' => env('APNS_CERTIFICATE_PATH'),
        'apns_password' => env('APNS_CERTIFICATE_PASSWORD'),
        'apns_environment' => env('APNS_ENVIRONMENT', 'production'),
        'web_push_public_key' => env('WEB_PUSH_PUBLIC_KEY'),
        'web_push_private_key' => env('WEB_PUSH_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for webhook notifications.
    |
    */
    'webhook' => [
        'timeout' => env('WEBHOOK_NOTIFICATION_TIMEOUT', 30),
        'retry_times' => env('WEBHOOK_NOTIFICATION_RETRY', 3),
        'retry_delay' => env('WEBHOOK_NOTIFICATION_RETRY_DELAY', 60), // seconds
        'verify_ssl' => env('WEBHOOK_NOTIFICATION_VERIFY_SSL', true),
        'user_agent' => env('WEBHOOK_NOTIFICATION_USER_AGENT', 'Nestogy-Webhook/1.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Scheduling
    |--------------------------------------------------------------------------
    |
    | Configure scheduled notifications and digests.
    |
    */
    'scheduling' => [
        'digest_enabled' => env('NOTIFICATION_DIGEST_ENABLED', true),
        'digest_frequency' => env('NOTIFICATION_DIGEST_FREQUENCY', 'daily'), // hourly, daily, weekly
        'digest_time' => env('NOTIFICATION_DIGEST_TIME', '09:00'),
        'digest_timezone' => env('NOTIFICATION_DIGEST_TIMEZONE', 'UTC'),
        'reminder_enabled' => env('NOTIFICATION_REMINDER_ENABLED', true),
        'reminder_intervals' => [7, 3, 1], // days before due date
        'quiet_hours_enabled' => env('NOTIFICATION_QUIET_HOURS', true),
        'quiet_hours_start' => env('NOTIFICATION_QUIET_START', '22:00'),
        'quiet_hours_end' => env('NOTIFICATION_QUIET_END', '08:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Batching
    |--------------------------------------------------------------------------
    |
    | Configure notification batching to prevent spam.
    |
    */
    'batching' => [
        'enabled' => env('NOTIFICATION_BATCHING_ENABLED', true),
        'delay' => env('NOTIFICATION_BATCHING_DELAY', 5), // minutes
        'max_batch_size' => env('NOTIFICATION_MAX_BATCH_SIZE', 10),
        'batch_similar' => env('NOTIFICATION_BATCH_SIMILAR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Filtering
    |--------------------------------------------------------------------------
    |
    | Configure notification filtering and suppression rules.
    |
    */
    'filtering' => [
        'duplicate_prevention' => env('NOTIFICATION_DUPLICATE_PREVENTION', true),
        'duplicate_window' => env('NOTIFICATION_DUPLICATE_WINDOW', 60), // minutes
        'rate_limiting' => env('NOTIFICATION_RATE_LIMITING', true),
        'rate_limit_per_user' => env('NOTIFICATION_RATE_LIMIT_USER', 50), // per hour
        'rate_limit_per_channel' => env('NOTIFICATION_RATE_LIMIT_CHANNEL', 100), // per hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Localization
    |--------------------------------------------------------------------------
    |
    | Configure notification language and localization settings.
    |
    */
    'localization' => [
        'enabled' => env('NOTIFICATION_LOCALIZATION', true),
        'default_locale' => env('NOTIFICATION_DEFAULT_LOCALE', 'en'),
        'fallback_locale' => env('NOTIFICATION_FALLBACK_LOCALE', 'en'),
        'user_preference' => env('NOTIFICATION_USER_LOCALE_PREFERENCE', true),
        'rtl_support' => env('NOTIFICATION_RTL_SUPPORT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Logging
    |--------------------------------------------------------------------------
    |
    | Configure notification logging and tracking.
    |
    */
    'logging' => [
        'enabled' => env('NOTIFICATION_LOGGING_ENABLED', true),
        'log_channel' => env('NOTIFICATION_LOG_CHANNEL', 'notifications'),
        'log_level' => env('NOTIFICATION_LOG_LEVEL', 'info'),
        'log_failures' => env('NOTIFICATION_LOG_FAILURES', true),
        'track_opens' => env('NOTIFICATION_TRACK_OPENS', true),
        'track_clicks' => env('NOTIFICATION_TRACK_CLICKS', true),
        'retention_days' => env('NOTIFICATION_LOG_RETENTION', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency Notifications
    |--------------------------------------------------------------------------
    |
    | Configure emergency notification settings.
    |
    */
    'emergency' => [
        'enabled' => env('EMERGENCY_NOTIFICATIONS_ENABLED', true),
        'bypass_quiet_hours' => env('EMERGENCY_BYPASS_QUIET_HOURS', true),
        'bypass_user_preferences' => env('EMERGENCY_BYPASS_PREFERENCES', true),
        'channels' => ['mail', 'sms', 'push', 'slack'],
        'recipients' => array_filter(explode(',', env('EMERGENCY_RECIPIENTS', ''))),
        'escalation_enabled' => env('EMERGENCY_ESCALATION_ENABLED', true),
        'escalation_delay' => env('EMERGENCY_ESCALATION_DELAY', 15), // minutes
    ],
];