<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'api_ninjas' => [
        'key' => env('API_NINJAS_KEY'),
        'timeout' => env('API_NINJAS_TIMEOUT', 10),
        'base_url' => env('API_NINJAS_BASE_URL', 'https://api.api-ninjas.com/v1'),
    ],

    'ipgeolocation' => [
        'key' => env('IPGEOLOCATION_API_KEY'),
        'timeout' => env('IPGEOLOCATION_TIMEOUT', 5),
    ],

    'claude' => [
        'oauth_token' => env('CLAUDE_CODE_OAUTH_TOKEN'),
    ],

];
