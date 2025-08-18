<?php

namespace App\Domains\Asset\Services;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetService
{
    /**
     * Get paginated assets with filters.
     */
    public function getPaginatedAssets(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Asset::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at');

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('make', 'like', "%{$search}%");
            });
        }

        // Apply type filter
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply client filter
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Apply location filter
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        return $query->with(['client', 'location'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new asset.
     */
    public function create(array $data): Asset
    {
        $data['company_id'] = Auth::user()->company_id;
        
        return DB::transaction(function () use ($data) {
            $asset = Asset::create($data);
            
            // Track the creation in audit log if needed
            $this->logAssetActivity($asset, 'created');
            
            return $asset;
        });
    }

    /**
     * Update an existing asset.
     */
    public function update(Asset $asset, array $data): Asset
    {
        return DB::transaction(function () use ($asset, $data) {
            $asset->update($data);
            
            // Track the update in audit log if needed
            $this->logAssetActivity($asset, 'updated');
            
            return $asset->fresh();
        });
    }

    /**
     * Archive an asset (soft delete).
     */
    public function archive(Asset $asset): bool
    {
        return DB::transaction(function () use ($asset) {
            $asset->archived_at = now();
            $result = $asset->save();
            
            // Track the archival in audit log if needed
            $this->logAssetActivity($asset, 'archived');
            
            return $result;
        });
    }

    /**
     * Get assets for dropdown/select options.
     */
    public function getActiveAssets(): Collection
    {
        return Asset::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('status', 'Deployed')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);
    }

    /**
     * Get clients for filter dropdown.
     */
    public function getClientsForFilter(): Collection
    {
        return Client::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get locations for filter dropdown.
     */
    public function getLocationsForFilter(): Collection
    {
        return Location::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get contacts for filter dropdown.
     */
    public function getContactsForFilter(): Collection
    {
        return \App\Models\Contact::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get assets by type.
     */
    public function getAssetsByType(string $type): Collection
    {
        return Asset::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('type', $type)
            ->with(['client', 'location'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get assets by status.
     */
    public function getAssetsByStatus(string $status): Collection
    {
        return Asset::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('status', $status)
            ->with(['client', 'location'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get assets with expiring warranties.
     */
    public function getAssetsWithExpiringWarranties(int $days = 30): Collection
    {
        return Asset::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->warrantyExpiringSoon($days)
            ->with(['client', 'location'])
            ->orderBy('warranty_expire')
            ->get();
    }

    /**
     * Update asset status.
     */
    public function updateStatus(Asset $asset, string $status): Asset
    {
        return DB::transaction(function () use ($asset, $status) {
            $oldStatus = $asset->status;
            $asset->update(['status' => $status]);
            
            // Track the status change in audit log
            $this->logAssetActivity($asset, 'status_changed', [
                'from' => $oldStatus,
                'to' => $status
            ]);
            
            return $asset;
        });
    }

    /**
     * Assign asset to client.
     */
    public function assignToClient(Asset $asset, int $clientId): Asset
    {
        return DB::transaction(function () use ($asset, $clientId) {
            $oldClientId = $asset->client_id;
            $asset->update(['client_id' => $clientId]);
            
            // Track the assignment in audit log
            $this->logAssetActivity($asset, 'client_assigned', [
                'from_client_id' => $oldClientId,
                'to_client_id' => $clientId
            ]);
            
            return $asset;
        });
    }

    /**
     * Check asset in/out.
     */
    public function checkInOut(Asset $asset, bool $checkOut = true, ?int $contactId = null): Asset
    {
        return DB::transaction(function () use ($asset, $checkOut, $contactId) {
            $data = [
                'status' => $checkOut ? 'Deployed' : 'Ready To Deploy'
            ];
            
            if ($checkOut && $contactId) {
                $data['contact_id'] = $contactId;
            } elseif (!$checkOut) {
                $data['contact_id'] = null;
            }
            
            $asset->update($data);
            
            // Track the check in/out in audit log
            $this->logAssetActivity($asset, $checkOut ? 'checked_out' : 'checked_in', [
                'contact_id' => $contactId
            ]);
            
            return $asset;
        });
    }

    /**
     * Get asset analytics data.
     */
    public function getAnalytics(): array
    {
        $companyId = Auth::user()->company_id;
        
        return [
            'total_assets' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->count(),
            'deployed_assets' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->where('status', 'Deployed')
                ->count(),
            'available_assets' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->where('status', 'Ready To Deploy')
                ->count(),
            'assets_by_type' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray(),
            'assets_by_status' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray(),
            'expiring_warranties' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->warrantyExpiringSoon(30)
                ->count(),
        ];
    }

    /**
     * Find asset by criteria.
     */
    public function findByCriteria(array $criteria): ?Asset
    {
        $query = Asset::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at');
            
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->first();
    }

    /**
     * Get asset with full relationships loaded.
     */
    public function getAssetWithRelationships(Asset $asset): Asset
    {
        return $asset->load([
            'client',
            'location',
            'contact',
            'vendor',
            'network',
            'warranties',
            'maintenances',
            'depreciations',
            'tickets',
            'documents',
            'files'
        ]);
    }

    /**
     * Log asset activity for audit trail.
     */
    private function logAssetActivity(Asset $asset, string $action, array $metadata = []): void
    {
        // Implementation would depend on your audit logging system
        // This could integrate with Laravel's activity log package or custom audit system
        
        // Example implementation:
        // activity()
        //     ->performedOn($asset)
        //     ->withProperties($metadata)
        //     ->log($action);
    }
}