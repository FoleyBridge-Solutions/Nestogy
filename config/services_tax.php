<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service Tax Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file defines tax rules and requirements for different
    | service types. Each service type can have specific taxes, rates, and
    | validation requirements.
    |
    */

    'voip' => [
        'name' => 'VoIP/Telecommunications',
        'taxes' => [
            'federal_excise' => [
                'name' => 'Federal Excise Tax',
                'rate' => 0.03, // 3%
                'type' => 'percentage',
            ],
            'e911' => [
                'name' => 'E911 Emergency Service Fee',
                'rate' => 0.75, // $0.75 per line
                'type' => 'flat',
                'per' => 'line',
            ],
            'usf' => [
                'name' => 'Universal Service Fund',
                'rate' => 0.184, // 18.4% (Q4 2024 rate)
                'type' => 'percentage',
                'description' => 'Federal Universal Service Fund contribution',
            ],
            'state_excise' => [
                'name' => 'State Telecommunications Excise Tax',
                'rate' => 0.0625, // Varies by state
                'type' => 'percentage',
            ],
            'access_recovery' => [
                'name' => 'Access Recovery Charge',
                'rate' => 2.00, // $2.00 per line
                'type' => 'flat',
                'per' => 'line',
            ],
            'regulatory_recovery' => [
                'name' => 'Regulatory Recovery Fee',
                'rate' => 0.0125, // 1.25%
                'type' => 'percentage',
            ],
        ],
        'required_fields' => [
            'line_count', // Number of phone lines
            'service_address', // For jurisdiction determination
        ],
        'optional_fields' => [
            'minutes', // For usage-based billing
            'international_minutes',
            'toll_free_minutes',
        ],
    ],

    'telecom' => [
        'name' => 'Traditional Telecommunications',
        'taxes' => [
            'federal_excise' => [
                'name' => 'Federal Excise Tax',
                'rate' => 0.03,
                'type' => 'percentage',
            ],
            'usf' => [
                'name' => 'Universal Service Fund',
                'rate' => 0.184,
                'type' => 'percentage',
            ],
            'access_recovery' => [
                'name' => 'Interstate Access Recovery',
                'rate' => 1.50,
                'type' => 'flat',
                'per' => 'line',
            ],
        ],
        'required_fields' => ['line_count', 'service_address'],
    ],

    'cloud' => [
        'name' => 'Cloud Services',
        'taxes' => [
            'sales_tax' => [
                'name' => 'Sales Tax',
                'type' => 'jurisdiction_based',
                'description' => 'Based on service location',
            ],
            'digital_services' => [
                'name' => 'Digital Services Tax',
                'rate' => 0.02, // 2% in some jurisdictions
                'type' => 'percentage',
            ],
        ],
        'required_fields' => ['service_address'],
        'optional_fields' => ['storage_gb', 'bandwidth_gb', 'compute_hours'],
    ],

    'saas' => [
        'name' => 'Software as a Service',
        'taxes' => [
            'sales_tax' => [
                'name' => 'Sales Tax',
                'type' => 'jurisdiction_based',
            ],
            'digital_goods' => [
                'name' => 'Digital Goods Tax',
                'rate' => 0.0275, // Varies by state
                'type' => 'percentage',
            ],
        ],
        'required_fields' => ['billing_address'],
        'optional_fields' => ['user_count', 'api_calls'],
    ],

    'hosting' => [
        'name' => 'Web Hosting Services',
        'taxes' => [
            'sales_tax' => [
                'name' => 'Sales Tax',
                'type' => 'jurisdiction_based',
            ],
        ],
        'required_fields' => ['service_address'],
        'optional_fields' => ['bandwidth_gb', 'storage_gb', 'domains'],
    ],

    'professional' => [
        'name' => 'Professional Services',
        'taxes' => [
            'sales_tax' => [
                'name' => 'Sales Tax',
                'type' => 'jurisdiction_based',
                'description' => 'May be exempt in some jurisdictions',
            ],
        ],
        'required_fields' => ['service_address'],
        'optional_fields' => ['hours', 'project_type'],
    ],

    'equipment' => [
        'name' => 'Equipment Sales/Lease',
        'taxes' => [
            'sales_tax' => [
                'name' => 'Sales Tax',
                'type' => 'jurisdiction_based',
            ],
            'property_tax' => [
                'name' => 'Personal Property Tax',
                'rate' => 0.01, // 1% annually
                'type' => 'percentage',
                'description' => 'For leased equipment',
            ],
        ],
        'required_fields' => ['delivery_address'],
        'optional_fields' => ['lease_term', 'equipment_value'],
    ],

    'managed_services' => [
        'name' => 'Managed IT Services',
        'taxes' => [
            'sales_tax' => [
                'name' => 'Sales Tax',
                'type' => 'jurisdiction_based',
            ],
        ],
        'required_fields' => ['service_address'],
        'optional_fields' => ['device_count', 'user_count', 'site_count'],
    ],

    'default' => [
        'name' => 'General Services',
        'taxes' => [
            'sales_tax' => [
                'name' => 'Sales Tax',
                'type' => 'jurisdiction_based',
            ],
        ],
        'required_fields' => ['service_address'],
        'optional_fields' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Calculation Settings
    |--------------------------------------------------------------------------
    */

    'calculation' => [
        'rounding_precision' => 4, // Round to 4 decimal places
        'compound_taxes' => false, // Whether taxes compound on each other
        'include_tax_in_price' => false, // VAT-style inclusive pricing
    ],

    /*
    |--------------------------------------------------------------------------
    | Jurisdiction Settings
    |--------------------------------------------------------------------------
    */

    'jurisdictions' => [
        'use_geocoding' => true, // Use address geocoding for accurate jurisdiction
        'default_country' => 'US',
        'cache_duration' => 86400, // Cache jurisdiction lookups for 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Settings
    |--------------------------------------------------------------------------
    */

    'compliance' => [
        'track_exemptions' => true,
        'audit_calculations' => true,
        'store_tax_breakdown' => true,
        'retention_days' => 2555, // 7 years
    ],
];
