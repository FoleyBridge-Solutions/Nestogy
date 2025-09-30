<?php

return [
    /*
    |--------------------------------------------------------------------------
    | External Service Integrations
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for all external service integrations
    | used by the Nestogy ERP system including payment processors, email
    | services, and third-party APIs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Stripe Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Configuration for Stripe payment processing including API keys,
    | webhook settings, and currency options.
    |
    */
    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', false),
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'usd'),
        'statement_descriptor' => env('STRIPE_STATEMENT_DESCRIPTOR', 'NESTOGY'),
        'capture_method' => env('STRIPE_CAPTURE_METHOD', 'automatic'),
        'payment_method_types' => explode(',', env('STRIPE_PAYMENT_METHODS', 'card')),
        'webhook_tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plaid Financial Services
    |--------------------------------------------------------------------------
    |
    | Configuration for Plaid integration for bank account connections
    | and financial data aggregation.
    |
    */
    'plaid' => [
        'enabled' => env('PLAID_ENABLED', false),
        'client_id' => env('PLAID_CLIENT_ID'),
        'secret' => env('PLAID_SECRET'),
        'environment' => env('PLAID_ENVIRONMENT', 'sandbox'), // sandbox, development, production
        'products' => explode(',', env('PLAID_PRODUCTS', 'transactions,accounts')),
        'country_codes' => explode(',', env('PLAID_COUNTRY_CODES', 'US')),
        'webhook_url' => env('PLAID_WEBHOOK_URL'),
        'client_name' => env('PLAID_CLIENT_NAME', 'Nestogy ERP'),
        'language' => env('PLAID_LANGUAGE', 'en'),
        'redirect_uri' => env('PLAID_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Service Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for email integration including IMAP for ticket creation
    | and email parsing.
    |
    */
    'email' => [
        'imap_enabled' => env('IMAP_ENABLED', false),
        'imap_host' => env('IMAP_HOST'),
        'imap_port' => env('IMAP_PORT', 993),
        'imap_username' => env('IMAP_USERNAME'),
        'imap_password' => env('IMAP_PASSWORD'),
        'imap_encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'imap_validate_cert' => env('IMAP_VALIDATE_CERT', true),
        'imap_folder' => env('IMAP_FOLDER', 'INBOX'),
        'imap_fetch_limit' => env('IMAP_FETCH_LIMIT', 50),
        'delete_after_import' => env('IMAP_DELETE_AFTER_IMPORT', false),
        'mark_as_read' => env('IMAP_MARK_AS_READ', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio SMS/Voice Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Twilio SMS and voice services.
    |
    */
    'twilio' => [
        'enabled' => env('TWILIO_ENABLED', false),
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        'verify_service_sid' => env('TWILIO_VERIFY_SERVICE_SID'),
        'webhook_url' => env('TWILIO_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Slack workspace integration and notifications.
    |
    */
    'slack' => [
        'enabled' => env('SLACK_ENABLED', false),
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'bot_token' => env('SLACK_BOT_TOKEN'),
        'app_token' => env('SLACK_APP_TOKEN'),
        'signing_secret' => env('SLACK_SIGNING_SECRET'),
        'default_channel' => env('SLACK_DEFAULT_CHANNEL', '#general'),
        'username' => env('SLACK_USERNAME', 'Nestogy Bot'),
        'icon_emoji' => env('SLACK_ICON_EMOJI', ':robot_face:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Workspace Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Workspace services including Calendar,
    | Drive, and Gmail.
    |
    */
    'google' => [
        'enabled' => env('GOOGLE_ENABLED', false),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'scopes' => explode(',', env('GOOGLE_SCOPES', 'email,profile')),
        'access_type' => env('GOOGLE_ACCESS_TYPE', 'offline'),
        'approval_prompt' => env('GOOGLE_APPROVAL_PROMPT', 'force'),
        'calendar_enabled' => env('GOOGLE_CALENDAR_ENABLED', false),
        'drive_enabled' => env('GOOGLE_DRIVE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Microsoft 365 Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Microsoft 365 services including Outlook,
    | OneDrive, and Teams.
    |
    */
    'microsoft' => [
        'enabled' => env('MICROSOFT_ENABLED', false),
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'tenant_id' => env('MICROSOFT_TENANT_ID'),
        'redirect_uri' => env('MICROSOFT_REDIRECT_URI'),
        'scopes' => explode(',', env('MICROSOFT_SCOPES', 'User.Read')),
        'teams_enabled' => env('MICROSOFT_TEAMS_ENABLED', false),
        'outlook_enabled' => env('MICROSOFT_OUTLOOK_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Zapier Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Zapier webhook integration.
    |
    */
    'zapier' => [
        'enabled' => env('ZAPIER_ENABLED', false),
        'api_key' => env('ZAPIER_API_KEY'),
        'webhook_url' => env('ZAPIER_WEBHOOK_URL'),
        'subscribe_url' => env('ZAPIER_SUBSCRIBE_URL'),
        'unsubscribe_url' => env('ZAPIER_UNSUBSCRIBE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | QuickBooks Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for QuickBooks Online integration.
    |
    */
    'quickbooks' => [
        'enabled' => env('QUICKBOOKS_ENABLED', false),
        'client_id' => env('QUICKBOOKS_CLIENT_ID'),
        'client_secret' => env('QUICKBOOKS_CLIENT_SECRET'),
        'redirect_uri' => env('QUICKBOOKS_REDIRECT_URI'),
        'environment' => env('QUICKBOOKS_ENVIRONMENT', 'sandbox'), // sandbox or production
        'base_url' => env('QUICKBOOKS_BASE_URL'),
        'discovery_document_url' => env('QUICKBOOKS_DISCOVERY_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailchimp Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Mailchimp email marketing integration.
    |
    */
    'mailchimp' => [
        'enabled' => env('MAILCHIMP_ENABLED', false),
        'api_key' => env('MAILCHIMP_API_KEY'),
        'server_prefix' => env('MAILCHIMP_SERVER_PREFIX'),
        'list_id' => env('MAILCHIMP_LIST_ID'),
        'webhook_secret' => env('MAILCHIMP_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS Services Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Amazon Web Services including S3, SES, and SNS.
    |
    */
    'aws' => [
        's3' => [
            'enabled' => env('AWS_S3_ENABLED', false),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
        'ses' => [
            'enabled' => env('AWS_SES_ENABLED', false),
            'key' => env('AWS_SES_KEY'),
            'secret' => env('AWS_SES_SECRET'),
            'region' => env('AWS_SES_REGION', 'us-east-1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | General webhook settings for receiving external events.
    |
    */
    'webhooks' => [
        'timeout' => env('WEBHOOK_TIMEOUT', 30),
        'retry_times' => env('WEBHOOK_RETRY_TIMES', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 10),
        'verify_ssl' => env('WEBHOOK_VERIFY_SSL', true),
        'secret_key' => env('WEBHOOK_SECRET_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for external API calls.
    |
    */
    'rate_limits' => [
        'default' => env('API_RATE_LIMIT_DEFAULT', 60),
        'stripe' => env('API_RATE_LIMIT_STRIPE', 100),
        'plaid' => env('API_RATE_LIMIT_PLAID', 120),
        'google' => env('API_RATE_LIMIT_GOOGLE', 1000),
    ],
];
