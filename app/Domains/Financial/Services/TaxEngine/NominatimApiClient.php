<?php

namespace App\Domains\Financial\Services\TaxEngine;

use App\Models\TaxApiQueryCache;
use Exception;

/**
 * Nominatim API Client
 *
 * Free geocoding service using OpenStreetMap data.
 * Provides forward and reverse geocoding worldwide with no API key required.
 *
 * API Documentation: https://nominatim.org/release-docs/develop/api/
 * Usage Policy: https://operations.osmfoundation.org/policies/nominatim/
 */
class NominatimApiClient extends BaseApiClient
{
    protected string $baseUrl = 'https://nominatim.openstreetmap.org';

    protected string $userAgent;

    public function __construct(int $companyId, array $config = [])
    {
        parent::__construct($companyId, TaxApiQueryCache::PROVIDER_NOMINATIM, $config);

        // Nominatim requires a User-Agent header
        $this->userAgent = $config['user_agent'] ?? 'Nestogy-Tax-Engine/1.0 (contact@nestogy.com)';
    }

    /**
     * Get rate limits for Nominatim API
     * Nominatim has a strict 1 request per second limit
     */
    protected function getRateLimits(): array
    {
        return [
            TaxApiQueryCache::TYPE_GEOCODING => [
                'max_requests' => 1,
                'window' => 1, // 1 second
            ],
            TaxApiQueryCache::TYPE_REVERSE_GEOCODING => [
                'max_requests' => 1,
                'window' => 1, // 1 second
            ],
        ];
    }

    /**
     * Create HTTP client with Nominatim-specific headers
     */
    protected function createHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        return parent::createHttpClient()
            ->withHeaders([
                'User-Agent' => $this->userAgent,
            ]);
    }

    /**
     * Geocode an address to coordinates
     *
     * @param  string  $address  Address to geocode
     * @param  array  $options  Additional search options
     * @return array Geocoding results with coordinates and administrative areas
     */
    public function geocode(string $address, array $options = []): array
    {
        $parameters = array_merge([
            'q' => $address,
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => $options['limit'] ?? 1,
            'countrycodes' => $options['country_codes'] ?? null,
        ], $options);

        // Remove null values
        $parameters = array_filter($parameters, fn ($value) => $value !== null);

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_GEOCODING,
            $parameters,
            function () use ($parameters) {
                // Enforce rate limit by sleeping if needed
                $this->enforceRateLimit();

                $response = $this->createHttpClient()
                    ->get("{$this->baseUrl}/search", $parameters);

                if (! $response->successful()) {
                    throw new Exception('Nominatim geocoding failed: '.$response->body());
                }

                $data = $response->json();

                if (empty($data)) {
                    return [
                        'found' => false,
                        'query' => $parameters['q'],
                        'results' => [],
                        'source' => 'nominatim',
                    ];
                }

                $results = [];
                foreach ($data as $result) {
                    $results[] = $this->formatGeocodingResult($result);
                }

                return [
                    'found' => ! empty($results),
                    'query' => $parameters['q'],
                    'results' => $results,
                    'total_found' => count($data),
                    'source' => 'nominatim',
                ];
            },
            30 // Cache geocoding results for 30 days
        );
    }

    /**
     * Reverse geocode coordinates to address
     *
     * @param  float  $latitude  Latitude
     * @param  float  $longitude  Longitude
     * @param  array  $options  Additional options
     * @return array Reverse geocoding result with address components
     */
    public function reverseGeocode(float $latitude, float $longitude, array $options = []): array
    {
        $parameters = array_merge([
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'json',
            'addressdetails' => 1,
            'zoom' => $options['zoom'] ?? 18, // Address level detail
        ], $options);

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_REVERSE_GEOCODING,
            $parameters,
            function () use ($parameters, $latitude, $longitude) {
                // Enforce rate limit by sleeping if needed
                $this->enforceRateLimit();

                $response = $this->createHttpClient()
                    ->get("{$this->baseUrl}/reverse", $parameters);

                if (! $response->successful()) {
                    throw new Exception('Nominatim reverse geocoding failed: '.$response->body());
                }

                $data = $response->json();

                if (empty($data) || isset($data['error'])) {
                    return [
                        'found' => false,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'result' => null,
                        'error' => $data['error'] ?? 'No results found',
                        'source' => 'nominatim',
                    ];
                }

                return [
                    'found' => true,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'result' => $this->formatGeocodingResult($data),
                    'source' => 'nominatim',
                ];
            },
            30 // Cache reverse geocoding results for 30 days
        );
    }

    /**
     * Format geocoding result from Nominatim response
     */
    private function formatGeocodingResult(array $result): array
    {
        $address = $result['address'] ?? [];

        return [
            'display_name' => $result['display_name'] ?? '',
            'latitude' => (float) ($result['lat'] ?? 0),
            'longitude' => (float) ($result['lon'] ?? 0),
            'place_id' => $result['place_id'] ?? null,
            'osm_type' => $result['osm_type'] ?? null,
            'osm_id' => $result['osm_id'] ?? null,
            'importance' => $result['importance'] ?? null,
            'type' => $result['type'] ?? null,
            'class' => $result['class'] ?? null,
            'address' => [
                'house_number' => $address['house_number'] ?? null,
                'road' => $address['road'] ?? null,
                'neighbourhood' => $address['neighbourhood'] ?? null,
                'suburb' => $address['suburb'] ?? null,
                'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
                'municipality' => $address['municipality'] ?? null,
                'county' => $address['county'] ?? null,
                'state' => $address['state'] ?? null,
                'state_code' => $address['ISO3166-2-lvl4'] ?? null,
                'postcode' => $address['postcode'] ?? null,
                'country' => $address['country'] ?? null,
                'country_code' => strtoupper($address['country_code'] ?? ''),
            ],
            'administrative_levels' => [
                'country' => $address['country'] ?? null,
                'country_code' => strtoupper($address['country_code'] ?? ''),
                'state' => $address['state'] ?? null,
                'state_district' => $address['state_district'] ?? null,
                'county' => $address['county'] ?? null,
                'municipality' => $address['municipality'] ?? null,
                'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
                'city_district' => $address['city_district'] ?? null,
                'neighbourhood' => $address['neighbourhood'] ?? null,
                'suburb' => $address['suburb'] ?? null,
            ],
            'bounding_box' => $result['boundingbox'] ?? null,
        ];
    }

    /**
     * Find tax jurisdictions for an address
     *
     * @param  string  $address  Address to find jurisdictions for
     * @return array Tax jurisdictions that may apply
     */
    public function findTaxJurisdictions(string $address): array
    {
        $geocoded = $this->geocode($address);

        if (! $geocoded['found'] || empty($geocoded['results'])) {
            return [
                'found' => false,
                'address' => $address,
                'jurisdictions' => [],
            ];
        }

        $result = $geocoded['results'][0];
        $addressComponents = $result['address'];

        // Build potential tax jurisdictions
        $jurisdictions = [];

        // Federal level
        if (! empty($addressComponents['country_code'])) {
            $jurisdictions[] = [
                'type' => 'federal',
                'name' => $addressComponents['country'],
                'code' => $addressComponents['country_code'],
                'level' => 1,
            ];
        }

        // State level
        if (! empty($addressComponents['state'])) {
            $jurisdictions[] = [
                'type' => 'state',
                'name' => $addressComponents['state'],
                'code' => $addressComponents['state_code'],
                'level' => 2,
            ];
        }

        // County level
        if (! empty($addressComponents['county'])) {
            $jurisdictions[] = [
                'type' => 'county',
                'name' => $addressComponents['county'],
                'code' => null,
                'level' => 3,
            ];
        }

        // City/Municipality level
        if (! empty($addressComponents['city'])) {
            $jurisdictions[] = [
                'type' => 'city',
                'name' => $addressComponents['city'],
                'code' => null,
                'level' => 4,
            ];
        }

        return [
            'found' => true,
            'address' => $address,
            'coordinates' => [
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
            ],
            'formatted_address' => $result['display_name'],
            'jurisdictions' => $jurisdictions,
            'address_components' => $addressComponents,
        ];
    }

    /**
     * Get coordinates for multiple addresses (batch geocoding)
     * Note: This respects rate limits by adding delays
     *
     * @param  array  $addresses  Array of addresses to geocode
     * @return array Batch geocoding results
     */
    public function batchGeocode(array $addresses): array
    {
        $results = [];

        foreach ($addresses as $index => $address) {
            try {
                $result = $this->geocode($address);
                $results[$index] = $result;

                // Add delay between requests to respect rate limits
                if ($index < count($addresses) - 1) {
                    sleep(1); // Wait 1 second between requests
                }

            } catch (Exception $e) {
                $results[$index] = [
                    'found' => false,
                    'query' => $address,
                    'error' => $e->getMessage(),
                    'source' => 'nominatim',
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

    /**
     * Enforce rate limit by sleeping if necessary
     * Nominatim requires 1 second between requests
     */
    private function enforceRateLimit(): void
    {
        $lastRequest = TaxApiQueryCache::where('company_id', $this->companyId)
            ->where('api_provider', TaxApiQueryCache::PROVIDER_NOMINATIM)
            ->where('status', TaxApiQueryCache::STATUS_SUCCESS)
            ->latest('api_called_at')
            ->first();

        if ($lastRequest) {
            $timeSinceLastRequest = now()->diffInSeconds($lastRequest->api_called_at);

            if ($timeSinceLastRequest < 1) {
                $sleepTime = 1 - $timeSinceLastRequest + 0.1; // Add small buffer
                usleep($sleepTime * 1000000); // Convert to microseconds
            }
        }
    }

    /**
     * Search for places by name and type
     *
     * @param  string  $placeName  Name of the place
     * @param  string  $placeType  Type of place (city, county, state, etc.)
     * @param  string|null  $countryCode  Limit search to specific country
     * @return array Search results
     */
    public function searchPlaces(string $placeName, string $placeType, ?string $countryCode = null): array
    {
        $parameters = [
            'q' => $placeName,
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 10,
            'class' => 'place',
            'type' => $placeType,
        ];

        if ($countryCode) {
            $parameters['countrycodes'] = strtolower($countryCode);
        }

        return $this->makeRequest(
            TaxApiQueryCache::TYPE_GEOCODING,
            $parameters,
            function () use ($parameters, $placeType, $countryCode) {
                // Enforce rate limit by sleeping if needed
                $this->enforceRateLimit();

                $response = $this->createHttpClient()
                    ->get("{$this->baseUrl}/search", $parameters);

                if (! $response->successful()) {
                    throw new Exception('Nominatim place search failed: '.$response->body());
                }

                $data = $response->json();

                $results = [];
                foreach ($data as $result) {
                    $results[] = $this->formatGeocodingResult($result);
                }

                return [
                    'found' => ! empty($results),
                    'query' => $parameters['q'],
                    'place_type' => $placeType,
                    'country_code' => $countryCode,
                    'results' => $results,
                    'total_found' => count($data),
                    'source' => 'nominatim',
                ];
            },
            30 // Cache place searches for 30 days
        );
    }
}
