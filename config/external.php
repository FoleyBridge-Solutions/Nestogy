<?php

return [
    /*
    |--------------------------------------------------------------------------
    | External Service Configurations
    |--------------------------------------------------------------------------
    |
    | This file consolidates configurations for external services and tools
    | that were previously stored in separate JSON/YAML files.
    |
    */

    'flux_ui' => [
        'mcp_servers' => [
            'fluxui-server' => [
                'command' => '/var/www/Nestogy/flux-ui-mcp/build/index.js',
            ],
        ],
    ],

    'tactical_rmm' => [
        'api_version' => '1.2.0',
        'base_url' => env('TACTICAL_RMM_URL', ''),
        'api_key' => env('TACTICAL_RMM_API_KEY', ''),
        'timeout' => env('TACTICAL_RMM_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | RMM Integrations
    |--------------------------------------------------------------------------
    */
    'rmm_integrations' => [
        'connectwise' => [
            'enabled' => env('CONNECTWISE_ENABLED', false),
            'api_url' => env('CONNECTWISE_API_URL', ''),
            'company_id' => env('CONNECTWISE_COMPANY_ID', ''),
            'public_key' => env('CONNECTWISE_PUBLIC_KEY', ''),
            'private_key' => env('CONNECTWISE_PRIVATE_KEY', ''),
        ],
        'datto' => [
            'enabled' => env('DATTO_ENABLED', false),
            'api_url' => env('DATTO_API_URL', 'https://api.datto.com/v1'),
            'api_key' => env('DATTO_API_KEY', ''),
            'api_secret' => env('DATTO_API_SECRET', ''),
        ],
        'ninja_one' => [
            'enabled' => env('NINJA_ONE_ENABLED', false),
            'instance_url' => env('NINJA_ONE_INSTANCE_URL', ''),
            'client_id' => env('NINJA_ONE_CLIENT_ID', ''),
            'client_secret' => env('NINJA_ONE_CLIENT_SECRET', ''),
        ],
    ],
];