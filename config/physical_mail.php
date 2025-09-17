<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PostGrid Configuration
    |--------------------------------------------------------------------------
    |
    | Configure PostGrid API keys and settings for physical mail delivery.
    |
    */
    'postgrid' => [
        'test_mode' => env('APP_ENV') !== 'production' || env('POSTGRID_TEST_MODE', true),
        'test_key' => env('POSTGRID_TEST_KEY'),
        'live_key' => env('POSTGRID_LIVE_KEY'),
        'webhook_secret' => env('POSTGRID_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for physical mail sending.
    |
    */
    'defaults' => [
        'from_address' => [
            'firstName' => env('COMPANY_CONTACT_FIRST_NAME', 'Admin'),
            'lastName' => env('COMPANY_CONTACT_LAST_NAME', ''),
            'companyName' => env('COMPANY_NAME', 'Nestogy'),
            'addressLine1' => env('COMPANY_ADDRESS_LINE1', '123 Main St'),
            'addressLine2' => env('COMPANY_ADDRESS_LINE2'),
            'city' => env('COMPANY_CITY', 'New York'),
            'provinceOrState' => env('COMPANY_STATE', 'NY'),
            'postalOrZip' => env('COMPANY_ZIP', '10001'),
            'country' => env('COMPANY_COUNTRY', 'US'),
        ],
        
        'mailing_class' => env('DEFAULT_MAILING_CLASS', 'first_class'),
        'color' => env('DEFAULT_MAIL_COLOR', true),
        'double_sided' => env('DEFAULT_MAIL_DOUBLE_SIDED', true),
        'address_placement' => env('DEFAULT_ADDRESS_PLACEMENT', 'top_first_page'),
        'size' => env('DEFAULT_MAIL_SIZE', 'us_letter'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Template IDs
    |--------------------------------------------------------------------------
    |
    | PostGrid template IDs for various mail types.
    |
    */
    'templates' => [
        'invoice' => env('POSTGRID_INVOICE_TEMPLATE_ID'),
        'statement' => env('POSTGRID_STATEMENT_TEMPLATE_ID'),
        'reminder' => env('POSTGRID_REMINDER_TEMPLATE_ID'),
        'collection' => env('POSTGRID_COLLECTION_TEMPLATE_ID'),
        'welcome' => env('POSTGRID_WELCOME_TEMPLATE_ID'),
        'contract' => env('POSTGRID_CONTRACT_TEMPLATE_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which queues to use for physical mail jobs.
    |
    */
    'queues' => [
        'mail' => env('PHYSICAL_MAIL_QUEUE', 'physical-mail'),
        'webhooks' => env('PHYSICAL_MAIL_WEBHOOK_QUEUE', 'webhooks'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailing Classes
    |--------------------------------------------------------------------------
    |
    | Available mailing classes and their descriptions.
    |
    */
    'mailing_classes' => [
        'first_class' => 'First Class Mail (3-5 business days)',
        'standard_class' => 'Standard Class Mail (5-10 business days)',
        'express' => 'Express Mail (1-2 business days)',
        'certified' => 'Certified Mail (with tracking)',
        'certified_return_receipt' => 'Certified Mail with Return Receipt',
        'registered' => 'Registered Mail (highest security)',
        'usps_express_2_day' => 'USPS Express 2-Day',
        'usps_express_3_day' => 'USPS Express 3-Day',
        'ups_express_overnight' => 'UPS Express Overnight',
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra Services
    |--------------------------------------------------------------------------
    |
    | Additional services available for mail.
    |
    */
    'extra_services' => [
        'certified' => 'Certified Mail',
        'certified_return_receipt' => 'Certified Mail with Return Receipt',
        'registered' => 'Registered Mail',
    ],

    /*
    |--------------------------------------------------------------------------
    | Letter Sizes
    |--------------------------------------------------------------------------
    |
    | Available letter sizes by region.
    |
    */
    'letter_sizes' => [
        'US' => ['us_letter', 'us_legal'],
        'UK' => ['a4'],
        'CA' => ['us_letter'],
        'AU' => ['a4'],
        'default' => 'us_letter',
    ],

    /*
    |--------------------------------------------------------------------------
    | Postcard Sizes
    |--------------------------------------------------------------------------
    |
    | Available postcard sizes.
    |
    */
    'postcard_sizes' => [
        '6x4' => '6" x 4" Standard Postcard',
        '9x6' => '9" x 6" Large Postcard',
        '11x6' => '11" x 6" Jumbo Postcard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Self-Mailer Sizes
    |--------------------------------------------------------------------------
    |
    | Available self-mailer configurations.
    |
    */
    'self_mailer_sizes' => [
        '8.5x11_bifold' => '8.5" x 11" Bi-fold',
        '8.5x11_trifold' => '8.5" x 11" Tri-fold',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Settings
    |--------------------------------------------------------------------------
    |
    | Configure cost tracking and markup.
    |
    */
    'cost' => [
        'track_costs' => env('TRACK_MAIL_COSTS', true),
        'markup_percentage' => env('MAIL_MARKUP_PERCENTAGE', 0),
        'include_tax' => env('MAIL_INCLUDE_TAX', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook URLs
    |--------------------------------------------------------------------------
    |
    | URLs for PostGrid webhooks.
    |
    */
    'webhooks' => [
        'endpoint' => env('APP_URL') . '/api/webhooks/postgrid',
        'events' => [
            'letter.created',
            'letter.updated',
            'letter.cancelled',
            'postcard.created',
            'postcard.updated',
            'postcard.cancelled',
            'cheque.created',
            'cheque.updated',
            'cheque.cancelled',
            'contact.updated',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific features.
    |
    */
    'features' => [
        'ncoa' => env('ENABLE_NCOA', true), // National Change of Address
        'address_verification' => env('ENABLE_ADDRESS_VERIFICATION', true),
        'return_envelopes' => env('ENABLE_RETURN_ENVELOPES', false),
        'bulk_mail' => env('ENABLE_BULK_MAIL', true),
        'test_mode_progression' => env('ENABLE_TEST_PROGRESSION', true),
    ],
];