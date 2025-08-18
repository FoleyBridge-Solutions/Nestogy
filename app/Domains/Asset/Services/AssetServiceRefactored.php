<?php

namespace App\Domains\Asset\Services;

use App\Services\BaseService;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;

class AssetServiceRefactored extends BaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = Asset::class;
        $this->defaultEagerLoad = ['client', 'location'];
        $this->searchableFields = ['name', 'serial', 'model', 'make'];
        $this->defaultSortField = 'created_at';
        $this->defaultSortDirection = 'desc';
    }

    protected function applyCustomFilters($query, array $filters)
    {
        // Apply client filter
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Apply location filter
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        return $query;
    }

    protected function afterCreate(\Illuminate\Database\Eloquent\Model $model, array $data): void
    {
        // Custom logic after asset creation
        if (isset($data['warranty_expire'])) {
            // Could schedule warranty expiration notifications
        }
    }

    protected function afterUpdate(\Illuminate\Database\Eloquent\Model $model, array $data): void
    {
        // Custom logic after asset update
        if (isset($data['status']) && $data['status'] === 'Deployed') {
            // Could trigger deployment workflows
        }
    }

    protected function beforeArchive(\Illuminate\Database\Eloquent\Model $model): void
    {
        // Ensure asset is not checked out before archiving
        if ($model->status === 'Deployed' && $model->contact_id) {
            throw new \Exception('Cannot archive asset that is currently checked out');
        }
    }

    protected function getCustomStatistics(): array
    {
        $companyId = auth()->user()->company_id;
        
        return [
            'deployed_assets' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->where('status', 'Deployed')
                ->count(),
            'available_assets' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->where('status', 'Ready To Deploy')
                ->count(),
            'expiring_warranties' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->where('warranty_expire', '<=', now()->addDays(30))
                ->where('warranty_expire', '>=', now())
                ->count(),
        ];
    }

    // Specific methods that don't fit the base patterns
    public function updateStatus(Asset $asset, string $status): Asset
    {
        return $this->update($asset, ['status' => $status]);
    }

    public function assignToClient(Asset $asset, int $clientId): Asset
    {
        return $this->update($asset, ['client_id' => $clientId]);
    }

    public function checkInOut(Asset $asset, bool $checkOut = true, ?int $contactId = null): Asset
    {
        $data = [
            'status' => $checkOut ? 'Deployed' : 'Ready To Deploy'
        ];
        
        if ($checkOut && $contactId) {
            $data['contact_id'] = $contactId;
        } elseif (!$checkOut) {
            $data['contact_id'] = null;
        }
        
        return $this->update($asset, $data);
    }

    public function getAssetsByType(string $type): Collection
    {
        return $this->getAll(['type' => $type]);
    }

    public function getAssetsByStatus(string $status): Collection
    {
        return $this->getAll(['status' => $status]);
    }

    public function getAssetsWithExpiringWarranties(int $days = 30): Collection
    {
        $query = $this->buildBaseQuery()
            ->where('warranty_expire', '<=', now()->addDays($days))
            ->where('warranty_expire', '>=', now())
            ->with($this->defaultEagerLoad)
            ->orderBy('warranty_expire');

        return $query->get();
    }

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

    // Methods for filter dropdowns - these use the base service pattern
    public function getClientsForFilter(): Collection
    {
        return Client::where('company_id', auth()->user()->company_id)
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getLocationsForFilter(): Collection
    {
        return Location::where('company_id', auth()->user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getContactsForFilter(): Collection
    {
        return \App\Models\Contact::where('company_id', auth()->user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function findByCriteria(array $criteria): ?Asset
    {
        $query = $this->buildBaseQuery();
            
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->first();
    }
}