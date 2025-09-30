<?php

namespace App\Domains\Financial\Services\TaxEngine;

use App\Models\TaxApiQueryCache;
use Exception;

/**
 * US Census Bureau API Client
 *
 * Free API for accessing US Census data, geographic boundaries,
 * and demographic information. Useful for jurisdiction detection
 * and administrative boundary determination.
 *
 * API Documentation: https://www.census.gov/data/developers/guidance/api-user-guide.html
 * Geocoding API: https://geocoding.geo.census.gov/geocoder/
 */
class CensusBureauApiClient extends BaseApiClient
{
    protected string $geocodingBaseUrl = 'https://geocoding.geo.census.gov/geocoder';

    protected string $dataBaseUrl = 'https://api.census.gov/data';

    protected string $tigerwebBaseUrl = 'https://tigerweb.geo.census.gov/arcgis/rest/services';

    protected ?string $apiKey;

    public function __construct(int $companyId, array $config = [])
    {
        parent::__construct($companyId, TaxApiQueryCache::PROVIDER_CENSUS, $config);
        $this->apiKey = $config['api_key'] ?? config('services.census.api_key');
    }

    /**
     * Get rate limits for Census Bureau APIs
     * Generally very generous limits for government APIs
     */
    protected function getRateLimits(): array
    {
        return [
            TaxApiQueryCache::TYPE_GEOCODING => [
                'max_requests' => 500,
                'window' => 60, // per minute
            ],
            TaxApiQueryCache::TYPE_BOUNDARY => [
                'max_requests' => 500,
                'window' => 60,
            ],
            TaxApiQueryCache::TYPE_JURISDICTION => [
                'max_requests' => 500,
                'window' => 60,
            ],
        ];
    }

    /**
     * Geocode an address using Census Bureau geocoding service
     *
     * @param  string  $address  Street address
     * @param  string|null  $city  City name
     * @param  string|null  $state  State abbreviation
     * @param  string|null  $zip  ZIP code
     * @return array Geocoding result with coordinates and FIPS codes
     */
    public function geocodeAddress(string $address, ?string $city = null, ?string $state = null, ?string $zip = null): array
    {
        $parameters = [
            'street' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'benchmark' => 'Public_AR_Current',
            'format' => 'json',
        ];

        // Remove null values
        $parameters = array_filter($parameters, fn ($value) => $value !== null);

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_GEOCODING,
            $parameters,
            function () use ($parameters) {
                $response = $this->createHttpClient()
                    ->get("{$this->geocodingBaseUrl}/locations/address", $parameters);

                if (! $response->successful()) {
                    throw new Exception('Census geocoding failed: '.$response->body());
                }

                $data = $response->json();

                if (empty($data['result']['addressMatches'])) {
                    return [
                        'found' => false,
                        'input' => $parameters,
                        'matches' => [],
                        'source' => 'census',
                    ];
                }

                $matches = [];
                foreach ($data['result']['addressMatches'] as $match) {
                    $matches[] = [
                        'matched_address' => $match['matchedAddress'] ?? '',
                        'coordinates' => [
                            'latitude' => (float) ($match['coordinates']['y'] ?? 0),
                            'longitude' => (float) ($match['coordinates']['x'] ?? 0),
                        ],
                        'address_components' => $match['addressComponents'] ?? [],
                        'tiger_line' => [
                            'side' => $match['tigerLine']['side'] ?? null,
                            'tiger_line_id' => $match['tigerLine']['tigerLineId'] ?? null,
                        ],
                        'match_quality' => [
                            'type' => $match['matchDetails']['matchType'] ?? null,
                            'tie_breaker_field' => $match['matchDetails']['tieBreakingField'] ?? null,
                        ],
                    ];
                }

                return [
                    'found' => true,
                    'input' => $parameters,
                    'matches' => $matches,
                    'total_matches' => count($matches),
                    'source' => 'census',
                ];
            },
            30 // Cache geocoding results for 30 days
        );
    }

    /**
     * Get geographic information for coordinates
     *
     * @param  float  $latitude  Latitude
     * @param  float  $longitude  Longitude
     * @param  array  $layers  Geographic layers to include
     * @return array Geographic information including FIPS codes
     */
    public function getGeographicInfo(float $latitude, float $longitude, array $layers = []): array
    {
        $defaultLayers = [
            'States',
            'Counties',
            'Census Tracts',
            'Census Block Groups',
            'Census Blocks',
            'Tribal Block Groups',
            'Tribal Census Tracts',
            'Congressional Districts',
            'State Legislative Districts - Upper',
            'State Legislative Districts - Lower',
        ];

        $layers = empty($layers) ? $defaultLayers : $layers;

        $parameters = [
            'x' => $longitude,
            'y' => $latitude,
            'layers' => implode(',', $layers),
            'format' => 'json',
        ];

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_BOUNDARY,
            $parameters,
            function () use ($parameters) {
                $response = $this->createHttpClient()
                    ->get("{$this->geocodingBaseUrl}/geographies/coordinates", $parameters);

                if (! $response->successful()) {
                    throw new Exception('Census geographic info failed: '.$response->body());
                }

                $data = $response->json();

                if (empty($data['result']['geographies'])) {
                    return [
                        'found' => false,
                        'coordinates' => [
                            'latitude' => $parameters['y'],
                            'longitude' => $parameters['x'],
                        ],
                        'geographies' => [],
                        'source' => 'census',
                    ];
                }

                return [
                    'found' => true,
                    'coordinates' => [
                        'latitude' => $parameters['y'],
                        'longitude' => $parameters['x'],
                    ],
                    'geographies' => $data['result']['geographies'],
                    'vintage' => $data['result']['vintage'] ?? null,
                    'source' => 'census',
                ];
            },
            30 // Cache geographic info for 30 days
        );
    }

    /**
     * Get tax jurisdictions for an address
     *
     * @param  string  $address  Address to analyze
     * @param  string|null  $city  City name
     * @param  string|null  $state  State abbreviation
     * @param  string|null  $zip  ZIP code
     * @return array Tax jurisdiction information
     */
    public function getTaxJurisdictions(string $address, ?string $city = null, ?string $state = null, ?string $zip = null): array
    {
        // First geocode the address
        $geocoded = $this->geocodeAddress($address, $city, $state, $zip);

        if (! $geocoded['found'] || empty($geocoded['matches'])) {
            return [
                'found' => false,
                'address' => compact('address', 'city', 'state', 'zip'),
                'jurisdictions' => [],
            ];
        }

        $match = $geocoded['matches'][0];
        $coords = $match['coordinates'];

        // Get geographic boundaries
        $geoInfo = $this->getGeographicInfo($coords['latitude'], $coords['longitude']);

        if (! $geoInfo['found']) {
            return [
                'found' => false,
                'address' => compact('address', 'city', 'state', 'zip'),
                'coordinates' => $coords,
                'jurisdictions' => [],
            ];
        }

        // Extract jurisdiction information
        $jurisdictions = [];
        $geographies = $geoInfo['geographies'];

        // State jurisdiction
        if (! empty($geographies['States'])) {
            $state = $geographies['States'][0];
            $jurisdictions[] = [
                'type' => 'state',
                'name' => $state['NAME'] ?? null,
                'fips_code' => $state['STATE'] ?? null,
                'full_fips' => $state['STATE'] ?? null,
                'level' => 1,
            ];
        }

        // County jurisdiction
        if (! empty($geographies['Counties'])) {
            $county = $geographies['Counties'][0];
            $jurisdictions[] = [
                'type' => 'county',
                'name' => $county['NAME'] ?? null,
                'fips_code' => $county['COUNTY'] ?? null,
                'full_fips' => ($county['STATE'] ?? '').($county['COUNTY'] ?? ''),
                'level' => 2,
            ];
        }

        // Census tract (useful for local tax districts)
        if (! empty($geographies['Census Tracts'])) {
            $tract = $geographies['Census Tracts'][0];
            $jurisdictions[] = [
                'type' => 'census_tract',
                'name' => 'Census Tract '.($tract['NAME'] ?? ''),
                'fips_code' => $tract['TRACT'] ?? null,
                'full_fips' => ($tract['STATE'] ?? '').($tract['COUNTY'] ?? '').($tract['TRACT'] ?? ''),
                'level' => 3,
            ];
        }

        // Congressional district
        if (! empty($geographies['Congressional Districts'])) {
            $district = $geographies['Congressional Districts'][0];
            $jurisdictions[] = [
                'type' => 'congressional_district',
                'name' => 'Congressional District '.($district['CD'] ?? ''),
                'fips_code' => $district['CD'] ?? null,
                'full_fips' => ($district['STATE'] ?? '').($district['CD'] ?? ''),
                'level' => 4,
            ];
        }

        return [
            'found' => true,
            'address' => compact('address', 'city', 'state', 'zip'),
            'matched_address' => $match['matched_address'],
            'coordinates' => $coords,
            'jurisdictions' => $jurisdictions,
            'all_geographies' => $geographies,
        ];
    }

    /**
     * Get ZIP code information
     *
     * @param  string  $zipCode  5-digit ZIP code
     * @return array ZIP code information including boundaries
     */
    public function getZipCodeInfo(string $zipCode): array
    {
        // Use the ZIP Code Tabulation Areas (ZCTA) service
        $parameters = [
            'zip' => $zipCode,
            'format' => 'json',
        ];

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_BOUNDARY,
            $parameters,
            function () use ($zipCode) {
                // Get ZIP code boundary from TIGERweb
                $response = $this->createHttpClient()
                    ->get("{$this->tigerweb}/TIGERweb/tigerWMS_PhysicalFeatures2021/MapServer/identify", [
                        'geometry' => '',
                        'geometryType' => 'esriGeometryPoint',
                        'sr' => 4326,
                        'layers' => 'all',
                        'tolerance' => 3,
                        'returnGeometry' => 'true',
                        'f' => 'json',
                    ]);

                // For ZIP code info, we'll use a simpler approach
                // This would need to be enhanced with actual ZIP boundary queries
                return [
                    'zip_code' => $zipCode,
                    'found' => false, // Placeholder - actual implementation would query TIGER/Line data
                    'boundaries' => null,
                    'source' => 'census',
                    'note' => 'ZIP code boundary queries require additional TIGER/Line implementation',
                ];
            },
            30
        );
    }

    /**
     * Get state FIPS code by state abbreviation
     *
     * @param  string  $stateAbbrev  State abbreviation (e.g., 'CA', 'NY')
     * @return array State information including FIPS code
     */
    public function getStateFipsCode(string $stateAbbrev): array
    {
        $stateFipsCodes = [
            'AL' => ['fips' => '01', 'name' => 'Alabama'],
            'AK' => ['fips' => '02', 'name' => 'Alaska'],
            'AZ' => ['fips' => '04', 'name' => 'Arizona'],
            'AR' => ['fips' => '05', 'name' => 'Arkansas'],
            'CA' => ['fips' => '06', 'name' => 'California'],
            'CO' => ['fips' => '08', 'name' => 'Colorado'],
            'CT' => ['fips' => '09', 'name' => 'Connecticut'],
            'DE' => ['fips' => '10', 'name' => 'Delaware'],
            'FL' => ['fips' => '12', 'name' => 'Florida'],
            'GA' => ['fips' => '13', 'name' => 'Georgia'],
            'HI' => ['fips' => '15', 'name' => 'Hawaii'],
            'ID' => ['fips' => '16', 'name' => 'Idaho'],
            'IL' => ['fips' => '17', 'name' => 'Illinois'],
            'IN' => ['fips' => '18', 'name' => 'Indiana'],
            'IA' => ['fips' => '19', 'name' => 'Iowa'],
            'KS' => ['fips' => '20', 'name' => 'Kansas'],
            'KY' => ['fips' => '21', 'name' => 'Kentucky'],
            'LA' => ['fips' => '22', 'name' => 'Louisiana'],
            'ME' => ['fips' => '23', 'name' => 'Maine'],
            'MD' => ['fips' => '24', 'name' => 'Maryland'],
            'MA' => ['fips' => '25', 'name' => 'Massachusetts'],
            'MI' => ['fips' => '26', 'name' => 'Michigan'],
            'MN' => ['fips' => '27', 'name' => 'Minnesota'],
            'MS' => ['fips' => '28', 'name' => 'Mississippi'],
            'MO' => ['fips' => '29', 'name' => 'Missouri'],
            'MT' => ['fips' => '30', 'name' => 'Montana'],
            'NE' => ['fips' => '31', 'name' => 'Nebraska'],
            'NV' => ['fips' => '32', 'name' => 'Nevada'],
            'NH' => ['fips' => '33', 'name' => 'New Hampshire'],
            'NJ' => ['fips' => '34', 'name' => 'New Jersey'],
            'NM' => ['fips' => '35', 'name' => 'New Mexico'],
            'NY' => ['fips' => '36', 'name' => 'New York'],
            'NC' => ['fips' => '37', 'name' => 'North Carolina'],
            'ND' => ['fips' => '38', 'name' => 'North Dakota'],
            'OH' => ['fips' => '39', 'name' => 'Ohio'],
            'OK' => ['fips' => '40', 'name' => 'Oklahoma'],
            'OR' => ['fips' => '41', 'name' => 'Oregon'],
            'PA' => ['fips' => '42', 'name' => 'Pennsylvania'],
            'RI' => ['fips' => '44', 'name' => 'Rhode Island'],
            'SC' => ['fips' => '45', 'name' => 'South Carolina'],
            'SD' => ['fips' => '46', 'name' => 'South Dakota'],
            'TN' => ['fips' => '47', 'name' => 'Tennessee'],
            'TX' => ['fips' => '48', 'name' => 'Texas'],
            'UT' => ['fips' => '49', 'name' => 'Utah'],
            'VT' => ['fips' => '50', 'name' => 'Vermont'],
            'VA' => ['fips' => '51', 'name' => 'Virginia'],
            'WA' => ['fips' => '53', 'name' => 'Washington'],
            'WV' => ['fips' => '54', 'name' => 'West Virginia'],
            'WI' => ['fips' => '55', 'name' => 'Wisconsin'],
            'WY' => ['fips' => '56', 'name' => 'Wyoming'],
            'DC' => ['fips' => '11', 'name' => 'District of Columbia'],
        ];

        $stateAbbrev = strtoupper($stateAbbrev);

        if (! isset($stateFipsCodes[$stateAbbrev])) {
            return [
                'found' => false,
                'state_abbrev' => $stateAbbrev,
                'error' => 'Invalid state abbreviation',
            ];
        }

        return [
            'found' => true,
            'state_abbrev' => $stateAbbrev,
            'state_name' => $stateFipsCodes[$stateAbbrev]['name'],
            'fips_code' => $stateFipsCodes[$stateAbbrev]['fips'],
            'source' => 'census',
        ];
    }

    /**
     * Batch geocode multiple addresses
     *
     * @param  array  $addresses  Array of address components
     * @return array Batch geocoding results
     */
    public function batchGeocode(array $addresses): array
    {
        $results = [];

        foreach ($addresses as $index => $addressData) {
            try {
                $result = $this->geocodeAddress(
                    $addressData['address'] ?? '',
                    $addressData['city'] ?? null,
                    $addressData['state'] ?? null,
                    $addressData['zip'] ?? null
                );
                $results[$index] = $result;

            } catch (Exception $e) {
                $results[$index] = [
                    'found' => false,
                    'input' => $addressData,
                    'error' => $e->getMessage(),
                    'source' => 'census',
                ];
            }
        }

        return [
            'total_addresses' => count($addresses),
            'successful' => count(array_filter($results, fn ($r) => $r['found'])),
            'failed' => count(array_filter($results, fn ($r) => ! $r['found'])),
            'results' => $results,
        ];
    }
}
