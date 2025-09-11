<?php

namespace App\Services\TaxEngine;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Tax Service Factory
 *
 * Dynamically creates and returns the appropriate tax service
 * based on company configuration and state requirements.
 */
class TaxServiceFactory
{
    /**
     * Available tax services by state code
     * All US states and territories supported via TaxJar
     */
    protected static array $availableServices = [
        // US States
        'AL' => TaxJarService::class,
        'AK' => TaxJarService::class,
        'AZ' => TaxJarService::class,
        'AR' => TaxJarService::class,
        'CA' => TaxJarService::class,
        'CO' => TaxJarService::class,
        'CT' => TaxJarService::class,
        'DE' => TaxJarService::class,
        'FL' => TaxJarService::class,
        'GA' => TaxJarService::class,
        'HI' => TaxJarService::class,
        'ID' => TaxJarService::class,
        'IL' => TaxJarService::class,
        'IN' => TaxJarService::class,
        'IA' => TaxJarService::class,
        'KS' => TaxJarService::class,
        'KY' => TaxJarService::class,
        'LA' => TaxJarService::class,
        'ME' => TaxJarService::class,
        'MD' => TaxJarService::class,
        'MA' => TaxJarService::class,
        'MI' => TaxJarService::class,
        'MN' => TaxJarService::class,
        'MS' => TaxJarService::class,
        'MO' => TaxJarService::class,
        'MT' => TaxJarService::class,
        'NE' => TaxJarService::class,
        'NV' => TaxJarService::class,
        'NH' => TaxJarService::class,
        'NJ' => TaxJarService::class,
        'NM' => TaxJarService::class,
        'NY' => TaxJarService::class,
        'NC' => TaxJarService::class,
        'ND' => TaxJarService::class,
        'OH' => TaxJarService::class,
        'OK' => TaxJarService::class,
        'OR' => TaxJarService::class,
        'PA' => TaxJarService::class,
        'RI' => TaxJarService::class,
        'SC' => TaxJarService::class,
        'SD' => TaxJarService::class,
        'TN' => TaxJarService::class,
        'TX' => TaxJarService::class,
        'UT' => TaxJarService::class,
        'VT' => TaxJarService::class,
        'VA' => TaxJarService::class,
        'WA' => TaxJarService::class,
        'WV' => TaxJarService::class,
        'WI' => TaxJarService::class,
        'WY' => TaxJarService::class,
        // US Territories
        'DC' => TaxJarService::class, // District of Columbia
        'PR' => TaxJarService::class, // Puerto Rico
        'VI' => TaxJarService::class, // U.S. Virgin Islands
        'GU' => TaxJarService::class, // Guam
        'AS' => TaxJarService::class, // American Samoa
        'MP' => TaxJarService::class, // Northern Mariana Islands
    ];

    /**
     * Get tax service for a specific state and company
     */
    public static function getService(string $stateCode, int $companyId): ?TaxDataServiceInterface
    {
        $stateCode = strtoupper($stateCode);

        // Check if service is available
        if (!isset(self::$availableServices[$stateCode])) {
            Log::warning("Tax service not available for state: {$stateCode}");
            return null;
        }

        // Check company configuration
        $config = self::getCompanyTaxConfig($companyId, $stateCode);
        if (!$config || !$config->is_enabled) {
            Log::info("Tax service not enabled for company {$companyId} in state {$stateCode}");
            return null;
        }

        try {
            $serviceClass = self::$availableServices[$stateCode];
            $service = new $serviceClass();
            $service->setCompanyId($companyId);

            return $service;
        } catch (Exception $e) {
            Log::error("Failed to instantiate tax service for {$stateCode}", [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get all enabled tax services for a company
     */
    public static function getEnabledServices(int $companyId): array
    {
        $services = [];

        $configs = DB::table('company_tax_configurations')
            ->where('company_id', $companyId)
            ->where('is_enabled', true)
            ->get();

        foreach ($configs as $config) {
            $service = self::getService($config->state_code, $companyId);
            if ($service) {
                $services[$config->state_code] = $service;
            }
        }

        return $services;
    }

    /**
     * Get tax service for a specific address/location
     */
    public static function getServiceForLocation(array $address, int $companyId): ?TaxDataServiceInterface
    {
        $stateCode = $address['state_code'] ?? $address['state'] ?? null;

        if (!$stateCode) {
            Log::warning("No state code found in address for tax service lookup", [
                'address' => $address,
                'company_id' => $companyId
            ]);
            return null;
        }

        return self::getService($stateCode, $companyId);
    }

    /**
     * Configure tax service for a company
     */
    public static function configureService(int $companyId, string $stateCode, array $config): bool
    {
        try {
            $stateCode = strtoupper($stateCode);

            DB::table('company_tax_configurations')->updateOrInsert(
                [
                    'company_id' => $companyId,
                    'state_code' => $stateCode,
                ],
                array_merge($config, [
                    'state_name' => self::getStateName($stateCode),
                    'service_class' => self::$availableServices[$stateCode] ?? null,
                    'updated_at' => now(),
                ])
            );

            Log::info("Tax service configured for company {$companyId} in state {$stateCode}");

            return true;
        } catch (Exception $e) {
            Log::error("Failed to configure tax service", [
                'company_id' => $companyId,
                'state_code' => $stateCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Enable/disable tax service for a company
     */
    public static function setServiceEnabled(int $companyId, string $stateCode, bool $enabled): bool
    {
        try {
            DB::table('company_tax_configurations')
                ->where('company_id', $companyId)
                ->where('state_code', strtoupper($stateCode))
                ->update([
                    'is_enabled' => $enabled,
                    'updated_at' => now()
                ]);

            Log::info("Tax service " . ($enabled ? 'enabled' : 'disabled') . " for company {$companyId} in state {$stateCode}");

            return true;
        } catch (Exception $e) {
            Log::error("Failed to " . ($enabled ? 'enable' : 'disable') . " tax service", [
                'company_id' => $companyId,
                'state_code' => $stateCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get company tax configuration
     */
    protected static function getCompanyTaxConfig(int $companyId, string $stateCode)
    {
        return DB::table('company_tax_configurations')
            ->where('company_id', $companyId)
            ->where('state_code', strtoupper($stateCode))
            ->first();
    }

    /**
     * Get state name from code
     */
    protected static function getStateName(string $stateCode): string
    {
        $states = [
            // US States
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
            // US Territories
            'DC' => 'District of Columbia',
            'PR' => 'Puerto Rico',
            'VI' => 'U.S. Virgin Islands',
            'GU' => 'Guam',
            'AS' => 'American Samoa',
            'MP' => 'Northern Mariana Islands',
        ];

        return $states[strtoupper($stateCode)] ?? ucfirst(strtolower($stateCode));
    }

    /**
     * Register a new tax service for a state
     */
    public static function registerService(string $stateCode, string $serviceClass): void
    {
        if (!class_exists($serviceClass)) {
            throw new Exception("Service class {$serviceClass} does not exist");
        }

        if (!is_subclass_of($serviceClass, TaxDataServiceInterface::class)) {
            throw new Exception("Service class {$serviceClass} must implement TaxDataServiceInterface");
        }

        self::$availableServices[strtoupper($stateCode)] = $serviceClass;

        Log::info("Tax service registered for state {$stateCode}: {$serviceClass}");
    }

    /**
     * Get all available states
     */
    public static function getAvailableStates(): array
    {
        return array_keys(self::$availableServices);
    }

    /**
     * Get service status for all configured services
     */
    public static function getServiceStatus(int $companyId): array
    {
        $status = [];

        $configs = DB::table('company_tax_configurations')
            ->where('company_id', $companyId)
            ->get();

        foreach ($configs as $config) {
            $service = self::getService($config->state_code, $companyId);
            $status[$config->state_code] = [
                'configured' => true,
                'enabled' => $config->is_enabled,
                'service_available' => $service !== null,
                'last_updated' => $config->last_updated,
                'state_name' => $config->state_name,
            ];
        }

        return $status;
    }
}