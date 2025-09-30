<?php

namespace App\Domains\Client\Services;

use App\Domains\Core\Services\BaseService;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;

abstract class ClientBaseService extends BaseService
{
    protected array $defaultEagerLoad = ['client'];
    
    protected function applyCustomFilters($query, array $filters): Builder
    {
        // Apply client filter
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        
        // Apply active filter (for client-related resources)
        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->where('is_active', true);
            } else {
                $query->where('is_active', false);
            }
        }
        
        // Apply category filter (common for client sub-resources)
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        // Apply access level filter (for documentation, credentials, etc.)
        if (!empty($filters['access_level'])) {
            $query->where('access_level', $filters['access_level']);
        }
        
        return parent::applyCustomFilters($query, $filters);
    }
    
    protected function buildBaseQuery(): Builder
    {
        $query = parent::buildBaseQuery();
        
        // Ensure we only get resources for clients that belong to the company
        $query->whereHas('client', function ($q) {
            $q->where('company_id', auth()->user()->company_id);
        });
        
        return $query;
    }
    
    protected function validateClientOwnership($clientId): void
    {
        $client = Client::find($clientId);
        
        if (!$client || $client->company_id !== auth()->user()->company_id) {
            throw new \InvalidArgumentException('Invalid client: Client does not belong to your company.');
        }
    }
    
    protected function prepareCreateData(array $data): array
    {
        // Validate client ownership if client_id is provided
        if (!empty($data['client_id'])) {
            $this->validateClientOwnership($data['client_id']);
        }
        
        // Set default active status
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }
        
        return parent::prepareCreateData($data);
    }
    
    protected function prepareUpdateData(array $data, $model): array
    {
        // Validate client ownership if client_id is being changed
        if (!empty($data['client_id']) && $data['client_id'] !== $model->client_id) {
            $this->validateClientOwnership($data['client_id']);
        }
        
        return parent::prepareUpdateData($data, $model);
    }
    
    public function getForClient(int $clientId, array $filters = [])
    {
        $this->validateClientOwnership($clientId);
        
        $filters['client_id'] = $clientId;
        return $this->getPaginated($filters);
    }
    
    public function getClientOptions(): array
    {
        return Client::where('company_id', auth()->user()->company_id)
            ->orderBy('company_name')
            ->pluck('company_name', 'id')
            ->toArray();
    }
    
    protected function getCustomStatistics(): array
    {
        $query = $this->buildBaseQuery();
        
        $stats = [
            'by_client' => (clone $query)
                ->join('clients', 'clients.id', '=', $this->getTable() . '.client_id')
                ->groupBy('clients.company_name')
                ->selectRaw('clients.company_name as client_name, count(*) as count')
                ->pluck('count', 'client_name')
                ->toArray(),
        ];
        
        // Add active/inactive breakdown
        if (in_array('is_active', $this->modelClass::make()->getFillable())) {
            $stats['active'] = (clone $query)->where('is_active', true)->count();
            $stats['inactive'] = (clone $query)->where('is_active', false)->count();
        }
        
        return $stats;
    }
    
    protected function getTable(): string
    {
        return $this->modelClass::make()->getTable();
    }
}