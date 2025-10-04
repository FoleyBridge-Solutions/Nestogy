<?php

namespace App\Domains\Client\Services;

use App\Models\Client;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientLocationService
{
    /**
     * Get all locations for a client
     */
    public function getLocations(Client $client): Collection
    {
        return $client->locations()
            ->orderBy('is_primary', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get primary location for a client
     */
    public function getPrimaryLocation(Client $client): ?Location
    {
        return $client->locations()
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Create a new location for a client
     */
    public function createLocation(Client $client, array $data): Location
    {
        DB::beginTransaction();

        try {
            // If this is set as primary, unset other primary locations
            if (! empty($data['is_primary']) && $data['is_primary']) {
                $client->locations()->update(['is_primary' => false]);
            }

            // Create the location
            $location = $client->locations()->create([
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'address2' => $data['address2'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'zip' => $data['zip'] ?? null,
                'country' => $data['country'] ?? 'US',
                'phone' => $data['phone'] ?? null,
                'fax' => $data['fax'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
                'is_billing' => $data['is_billing'] ?? false,
                'is_shipping' => $data['is_shipping'] ?? false,
                'timezone' => $data['timezone'] ?? 'America/New_York',
                'business_hours' => $data['business_hours'] ?? null,
                'notes' => $data['notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'company_id' => $client->company_id,
            ]);

            // Geocode if address provided but no coordinates
            if (! empty($data['address']) && empty($data['latitude'])) {
                $this->geocodeLocation($location);
            }

            DB::commit();

            Log::info('Location created for client', [
                'client_id' => $client->id,
                'location_id' => $location->id,
            ]);

            return $location;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create location', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update a location
     */
    public function updateLocation(Location $location, array $data): Location
    {
        DB::beginTransaction();

        try {
            $oldAddress = $location->address;

            // If setting as primary, unset other primary locations for this client
            if (! empty($data['is_primary']) && $data['is_primary'] && ! $location->is_primary) {
                Location::where('client_id', $location->client_id)
                    ->where('id', '!=', $location->id)
                    ->update(['is_primary' => false]);
            }

            $location->update([
                'name' => $data['name'] ?? $location->name,
                'address' => $data['address'] ?? $location->address,
                'address2' => $data['address2'] ?? $location->address2,
                'city' => $data['city'] ?? $location->city,
                'state' => $data['state'] ?? $location->state,
                'zip' => $data['zip'] ?? $location->zip,
                'country' => $data['country'] ?? $location->country,
                'phone' => $data['phone'] ?? $location->phone,
                'fax' => $data['fax'] ?? $location->fax,
                'is_primary' => $data['is_primary'] ?? $location->is_primary,
                'is_billing' => $data['is_billing'] ?? $location->is_billing,
                'is_shipping' => $data['is_shipping'] ?? $location->is_shipping,
                'timezone' => $data['timezone'] ?? $location->timezone,
                'business_hours' => $data['business_hours'] ?? $location->business_hours,
                'notes' => $data['notes'] ?? $location->notes,
                'latitude' => $data['latitude'] ?? $location->latitude,
                'longitude' => $data['longitude'] ?? $location->longitude,
            ]);

            // Re-geocode if address changed
            if ($oldAddress !== $location->address && empty($data['latitude'])) {
                $this->geocodeLocation($location);
            }

            DB::commit();

            Log::info('Location updated', ['location_id' => $location->id]);

            return $location->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update location', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a location
     */
    public function deleteLocation(Location $location): bool
    {
        try {
            // If this was the primary location, set another as primary
            if ($location->is_primary) {
                $nextLocation = Location::where('client_id', $location->client_id)
                    ->where('id', '!=', $location->id)
                    ->first();

                if ($nextLocation) {
                    $nextLocation->update(['is_primary' => true]);
                }
            }

            // Check for related assets, tickets, etc. before deletion
            if ($this->hasRelatedRecords($location)) {
                throw new \Exception('Cannot delete location with related records');
            }

            $location->delete();

            Log::info('Location deleted', ['location_id' => $location->id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete location', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get billing location for a client
     */
    public function getBillingLocation(Client $client): ?Location
    {
        return $client->locations()
            ->where('is_billing', true)
            ->first() ?? $this->getPrimaryLocation($client);
    }

    /**
     * Get shipping location for a client
     */
    public function getShippingLocation(Client $client): ?Location
    {
        return $client->locations()
            ->where('is_shipping', true)
            ->first() ?? $this->getPrimaryLocation($client);
    }

    /**
     * Search locations by address or name
     */
    public function searchLocations(Client $client, string $search): Collection
    {
        return $client->locations()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('zip', 'like', "%{$search}%");
            })
            ->get();
    }

    /**
     * Get locations within radius of coordinates
     */
    public function getLocationsWithinRadius(float $latitude, float $longitude, float $radiusMiles = 50): Collection
    {
        // Haversine formula for distance calculation
        $radiusKm = $radiusMiles * 1.60934;

        return Location::selectRaw('*, 
            ( 6371 * acos( cos( radians(?) ) *
              cos( radians( latitude ) ) *
              cos( radians( longitude ) - radians(?) ) +
              sin( radians(?) ) *
              sin( radians( latitude ) ) ) ) AS distance',
            [$latitude, $longitude, $latitude])
            ->havingRaw('( 6371 * acos( cos( radians(?) ) *
              cos( radians( latitude ) ) *
              cos( radians( longitude ) - radians(?) ) +
              sin( radians(?) ) *
              sin( radians( latitude ) ) ) ) < ?',
                [$latitude, $longitude, $latitude, $radiusKm])
            ->orderBy('distance')
            ->get();
    }

    /**
     * Geocode a location
     */
    protected function geocodeLocation(Location $location): void
    {
        // This would integrate with a geocoding service like Google Maps API
        // For now, just a placeholder
        try {
            $fullAddress = implode(', ', array_filter([
                $location->address,
                $location->city,
                $location->state,
                $location->zip,
                $location->country,
            ]));

            // In production, call geocoding API here
            // $coords = $this->geocodingService->geocode($fullAddress);
            // $location->update([
            //     'latitude' => $coords['lat'],
            //     'longitude' => $coords['lng']
            // ]);

            Log::info('Location geocoding skipped (no API configured)', [
                'location_id' => $location->id,
                'address' => $fullAddress,
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to geocode location', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if location has related records
     */
    protected function hasRelatedRecords(Location $location): bool
    {
        // Check for assets at this location
        if (class_exists(\App\Models\Asset::class)) {
            if (\App\Models\Asset::where('location_id', $location->id)->exists()) {
                return true;
            }
        }

        // Check for tickets at this location
        if (class_exists(\App\Domains\Ticket\Models\Ticket::class)) {
            if (\App\Domains\Ticket\Models\Ticket::where('location_id', $location->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get location statistics
     */
    public function getLocationStatistics(Location $location): array
    {
        $stats = [
            'asset_count' => 0,
            'ticket_count' => 0,
            'open_tickets' => 0,
            'contacts_count' => 0,
        ];

        // Count assets at location
        if (class_exists(\App\Models\Asset::class)) {
            $stats['asset_count'] = \App\Models\Asset::where('location_id', $location->id)->count();
        }

        // Count tickets at location
        if (class_exists(\App\Domains\Ticket\Models\Ticket::class)) {
            $stats['ticket_count'] = \App\Domains\Ticket\Models\Ticket::where('location_id', $location->id)->count();
            $stats['open_tickets'] = \App\Domains\Ticket\Models\Ticket::where('location_id', $location->id)
                ->whereIn('status', ['open', 'in_progress'])
                ->count();
        }

        // Count contacts at location
        if (class_exists(\App\Models\Contact::class)) {
            $stats['contacts_count'] = \App\Models\Contact::where('location_id', $location->id)->count();
        }

        return $stats;
    }

    /**
     * Bulk import locations for a client
     */
    public function bulkImportLocations(Client $client, array $locations): array
    {
        $imported = [];
        $failed = [];

        DB::beginTransaction();

        try {
            foreach ($locations as $index => $locationData) {
                try {
                    $location = $this->createLocation($client, $locationData);
                    $imported[] = $location;
                } catch (\Exception $e) {
                    $failed[] = [
                        'index' => $index,
                        'data' => $locationData,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (empty($failed)) {
                DB::commit();

                Log::info('Bulk location import completed', [
                    'client_id' => $client->id,
                    'imported_count' => count($imported),
                ]);
            } else {
                DB::rollBack();

                Log::warning('Bulk location import failed', [
                    'client_id' => $client->id,
                    'failed_count' => count($failed),
                ]);
            }

            return [
                'imported' => $imported,
                'failed' => $failed,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk location import error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Export locations to array
     */
    public function exportLocations(Client $client): array
    {
        return $client->locations->map(function ($location) {
            return [
                'name' => $location->name,
                'address' => $location->address,
                'address2' => $location->address2,
                'city' => $location->city,
                'state' => $location->state,
                'zip' => $location->zip,
                'country' => $location->country,
                'phone' => $location->phone,
                'fax' => $location->fax,
                'is_primary' => $location->is_primary,
                'is_billing' => $location->is_billing,
                'is_shipping' => $location->is_shipping,
                'timezone' => $location->timezone,
                'business_hours' => $location->business_hours,
                'notes' => $location->notes,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
            ];
        })->toArray();
    }
}
