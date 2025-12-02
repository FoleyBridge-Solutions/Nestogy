<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenRouter Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for OpenRouter API. This is a static value and should not
    | be changed unless OpenRouter changes their API endpoint.
    |
    */

    'base_url' => 'https://openrouter.ai/api/v1',

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for API responses.
    | This is a server-level setting and can be overridden via ENV.
    |
    */

    'timeout' => env('OPENROUTER_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | Default fallback model if not specified in company settings.
    | This is only used as a fallback - each company should configure
    | their preferred model in their AI settings.
    |
    */

    'default_model' => 'openai/gpt-3.5-turbo',

    /*
    |--------------------------------------------------------------------------
    | Model Aliases
    |--------------------------------------------------------------------------
    |
    | Friendly aliases for commonly used models. These are static mappings
    | that help with model selection in the UI.
    |
    */

    'model_aliases' => [
        'gpt4' => 'openai/gpt-4-turbo-preview',
        'gpt3' => 'openai/gpt-3.5-turbo',
        'claude' => 'anthropic/claude-3-opus',
        'claude-sonnet' => 'anthropic/claude-3-sonnet',
        'gemini' => 'google/gemini-pro',
        'llama' => 'meta-llama/llama-2-70b-chat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Popular Models
    |--------------------------------------------------------------------------
    |
    | A curated list of popular models to show in dropdowns.
    | This helps users choose appropriate models without overwhelming them.
    |
    */

    'popular_models' => [
        'openai/gpt-4-turbo-preview' => [
            'name' => 'GPT-4 Turbo',
            'description' => 'Most capable OpenAI model, best for complex tasks',
            'cost' => 'high',
        ],
        'openai/gpt-3.5-turbo' => [
            'name' => 'GPT-3.5 Turbo',
            'description' => 'Fast and cost-effective for most tasks',
            'cost' => 'low',
        ],
        'anthropic/claude-3-opus' => [
            'name' => 'Claude 3 Opus',
            'description' => 'Anthropic\'s most powerful model',
            'cost' => 'high',
        ],
        'anthropic/claude-3-sonnet' => [
            'name' => 'Claude 3 Sonnet',
            'description' => 'Balanced performance and cost',
            'cost' => 'medium',
        ],
        'google/gemini-pro' => [
            'name' => 'Gemini Pro',
            'description' => 'Google\'s multimodal AI model',
            'cost' => 'medium',
        ],
    ],

];
