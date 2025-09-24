<?php

namespace App\Domains\PhysicalMail\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Geocode an address using OpenStreetMap Nominatim API (free)
     */
    public function geocodeAddress(array $addressComponents): ?array
    {
        // Build address string
        $addressString = $this->buildAddressString($addressComponents);
        
        if (empty($addressString)) {
            return null;
        }
        
        // Check cache first
        $cacheKey = 'geocode:' . md5($addressString);
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            return $cached;
        }
        
        try {
            // Use OpenStreetMap Nominatim API (free, no API key required)
            $response = Http::withHeaders([
                'User-Agent' => config('app.name') . '/1.0',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'q' => $addressString,
                'limit' => 1,
                'countrycodes' => $addressComponents['country'] ?? 'us',
            ]);
            
            if ($response->successful() && $response->json()) {
                $data = $response->json()[0] ?? null;
                
                if ($data) {
                    $result = [
                        'latitude' => (float) $data['lat'],
                        'longitude' => (float) $data['lon'],
                        'formatted_address' => $data['display_name'] ?? $addressString,
                    ];
                    
                    // Cache for 30 days
                    Cache::put($cacheKey, $result, now()->addDays(30));
                    
                    return $result;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Geocoding failed', [
                'address' => $addressString,
                'error' => $e->getMessage(),
            ]);
        }
        
        // Fallback: Generate approximate coordinates based on ZIP code
        return $this->fallbackGeocoding($addressComponents);
    }
    
    /**
     * Build address string from components
     */
    private function buildAddressString(array $components): string
    {
        $parts = [];
        
        if (!empty($components['addressLine1'])) {
            $parts[] = $components['addressLine1'];
        }
        
        if (!empty($components['addressLine2'])) {
            $parts[] = $components['addressLine2'];
        }
        
        if (!empty($components['city'])) {
            $parts[] = $components['city'];
        }
        
        if (!empty($components['provinceOrState'])) {
            $parts[] = $components['provinceOrState'];
        } elseif (!empty($components['state'])) {
            $parts[] = $components['state'];
        }
        
        if (!empty($components['postalOrZip'])) {
            $parts[] = $components['postalOrZip'];
        } elseif (!empty($components['postalCode'])) {
            $parts[] = $components['postalCode'];
        }
        
        if (!empty($components['country'])) {
            $parts[] = $components['country'];
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Fallback geocoding using ZIP code approximation
     */
    private function fallbackGeocoding(array $components): ?array
    {
        $zipCode = $components['postalOrZip'] ?? $components['postalCode'] ?? null;
        
        if (!$zipCode) {
            return null;
        }
        
        // Basic US ZIP code to approximate coordinates mapping
        // This is a simplified approach for demo purposes
        $firstDigit = substr($zipCode, 0, 1);
        
        $regions = [
            '0' => ['lat' => 42.3601, 'lng' => -71.0589], // Boston area
            '1' => ['lat' => 40.7128, 'lng' => -74.0060], // New York area
            '2' => ['lat' => 38.9072, 'lng' => -77.0369], // Washington DC area
            '3' => ['lat' => 33.4484, 'lng' => -84.3880], // Atlanta area
            '4' => ['lat' => 36.1627, 'lng' => -86.7816], // Nashville area
            '5' => ['lat' => 44.9778, 'lng' => -93.2650], // Minneapolis area
            '6' => ['lat' => 41.8781, 'lng' => -87.6298], // Chicago area
            '7' => ['lat' => 29.7604, 'lng' => -95.3698], // Houston area
            '8' => ['lat' => 39.7392, 'lng' => -104.9903], // Denver area
            '9' => ['lat' => 37.7749, 'lng' => -122.4194], // San Francisco area
        ];
        
        $base = $regions[$firstDigit] ?? ['lat' => 39.8283, 'lng' => -98.5795]; // US center
        
        // Add some randomness to avoid all pins at same location
        $lat = $base['lat'] + (rand(-100, 100) / 1000);
        $lng = $base['lng'] + (rand(-100, 100) / 1000);
        
        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'formatted_address' => $this->buildAddressString($components),
        ];
    }
    
    /**
     * Batch geocode multiple addresses
     */
    public function batchGeocode(array $addresses): array
    {
        $results = [];
        
        foreach ($addresses as $key => $address) {
            $results[$key] = $this->geocodeAddress($address);
            
            // Rate limit to respect Nominatim's usage policy (1 request per second)
            usleep(1000000); // 1 second delay
        }
        
        return $results;
    }
}