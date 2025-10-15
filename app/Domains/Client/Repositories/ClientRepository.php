<?php

namespace App\Domains\Client\Repositories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientRepository
{
    /**
     * Find client with relations
     */
    public function findWithRelations(int $id, array $relations = []): ?Client
    {
        return Client::with($relations)->find($id);
    }

    /**
     * Get filtered query
     */
    public function getFilteredQuery(array $filters): Builder
    {
        $query = Client::query();

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('company_name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        return $query;
    }

    /**
     * Get paginated clients
     */
    public function getPaginated(array $filters, int $perPage = 25, array $relations = []): LengthAwarePaginator
    {
        $query = $this->getFilteredQuery($filters);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }

    /**
     * Get active clients for company
     */
    public function getActive(int $companyId): Collection
    {
        return Client::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get all clients for company
     */
    public function getByCompany(int $companyId): Collection
    {
        return Client::where('company_id', $companyId)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Search clients
     */
    public function search(string $query, int $companyId, int $limit = 10): Collection
    {
        return Client::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('company_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Create client
     */
    public function create(array $data): Client
    {
        return Client::create($data);
    }

    /**
     * Update client
     */
    public function update(Client $client, array $data): bool
    {
        return $client->update($data);
    }

    /**
     * Delete client
     */
    public function delete(Client $client): bool
    {
        return $client->delete();
    }
}
