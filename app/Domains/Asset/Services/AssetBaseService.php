<?php

namespace App\Domains\Asset\Services;

use App\Domains\Core\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;

abstract class AssetBaseService extends BaseService
{
    protected array $defaultEagerLoad = ['asset', 'asset.client'];
    
    protected function applyCustomFilters($query, array $filters): Builder
    {
        // Apply asset filter
        if (!empty($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }
        
        // Apply asset type filter (through asset relationship)
        if (!empty($filters['asset_type'])) {
            $query->whereHas('asset', function ($q) use ($filters) {
                $q->where('type', $filters['asset_type']);
            });
        }
        
        // Apply location filter (through asset relationship)
        if (!empty($filters['location_id'])) {
            $query->whereHas('asset', function ($q) use ($filters) {
                $q->where('location_id', $filters['location_id']);
            });
        }
        
        // Apply client filter (through asset relationship)
        if (!empty($filters['client_id'])) {
            $query->whereHas('asset', function ($q) use ($filters) {
                $q->where('client_id', $filters['client_id']);
            });
        }
        
        // Apply cost range filters
        if (!empty($filters['cost_from'])) {
            $query->where('cost', '>=', $filters['cost_from']);
        }
        
        if (!empty($filters['cost_to'])) {
            $query->where('cost', '<=', $filters['cost_to']);
        }
        
        // Apply maintenance type filter (for maintenance records)
        if (!empty($filters['maintenance_type'])) {
            $query->where('maintenance_type', $filters['maintenance_type']);
        }
        
        return parent::applyCustomFilters($query, $filters);
    }
    
    protected function buildBaseQuery(): Builder
    {
        $query = parent::buildBaseQuery();
        
        // Ensure we only get records for assets that belong to clients in the company
        $query->whereHas('asset.client', function ($q) {
            $q->where('company_id', auth()->user()->company_id);
        });
        
        return $query;
    }
    
    protected function validateAssetOwnership($assetId): void
    {
        $asset = \App\Models\Asset::with('client')
            ->where('id', $assetId)
            ->first();
        
        if (!$asset || $asset->client->company_id !== auth()->user()->company_id) {
            throw new \InvalidArgumentException('Invalid asset: Asset does not belong to your company.');
        }
    }
    
    protected function prepareCreateData(array $data): array
    {
        // Validate asset ownership
        if (!empty($data['asset_id'])) {
            $this->validateAssetOwnership($data['asset_id']);
        }
        
        // Set default status for maintenance records
        if (!isset($data['status']) && in_array('status', $this->modelClass::make()->getFillable())) {
            $data['status'] = 'scheduled';
        }
        
        // Set performed by to current user if not provided
        if (!isset($data['performed_by']) && in_array('performed_by', $this->modelClass::make()->getFillable())) {
            $data['performed_by'] = auth()->id();
        }
        
        return parent::prepareCreateData($data);
    }
    
    protected function prepareUpdateData(array $data, $model): array
    {
        // Validate asset ownership if asset_id is being changed
        if (!empty($data['asset_id']) && $data['asset_id'] !== $model->asset_id) {
            $this->validateAssetOwnership($data['asset_id']);
        }
        
        return parent::prepareUpdateData($data, $model);
    }
    
    public function getForAsset(int $assetId, array $filters = [])
    {
        $this->validateAssetOwnership($assetId);
        
        $filters['asset_id'] = $assetId;
        return $this->getPaginated($filters);
    }
    
    public function getAssetOptions(): array
    {
        return \App\Models\Asset::whereHas('client', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->with('client')
            ->get()
            ->mapWithKeys(function ($asset) {
                return [$asset->id => $asset->name . ' (' . $asset->client->company_name . ')'];
            })
            ->toArray();
    }
    
    public function getUpcomingMaintenanceItems(int $days = 30)
    {
        if (!method_exists($this->modelClass::make(), 'getDateColumn')) {
            return collect();
        }
        
        $dateColumn = $this->modelClass::make()->getDateColumn() ?? 'scheduled_date';
        
        return $this->buildBaseQuery()
            ->where($dateColumn, '>=', now())
            ->where($dateColumn, '<=', now()->addDays($days))
            ->where('status', '!=', 'completed')
            ->orderBy($dateColumn)
            ->get();
    }
    
    protected function getCustomStatistics(): array
    {
        $query = $this->buildBaseQuery();
        
        $stats = [
            'by_asset_type' => (clone $query)
                ->join('assets', 'assets.id', '=', $this->getTable() . '.asset_id')
                ->groupBy('assets.type')
                ->selectRaw('assets.type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray(),
        ];
        
        // Add status breakdown if available
        if (in_array('status', $this->modelClass::make()->getFillable())) {
            $stats['by_status'] = (clone $query)
                ->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray();
        }
        
        // Add cost statistics if available
        if (in_array('cost', $this->modelClass::make()->getFillable())) {
            $stats['cost_summary'] = [
                'total_cost' => (clone $query)->sum('cost'),
                'average_cost' => (clone $query)->avg('cost'),
                'min_cost' => (clone $query)->min('cost'),
                'max_cost' => (clone $query)->max('cost'),
            ];
        }
        
        return $stats;
    }
    
    protected function getTable(): string
    {
        return $this->modelClass::make()->getTable();
    }
}