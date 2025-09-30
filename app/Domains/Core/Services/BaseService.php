<?php

namespace App\Domains\Core\Services;

use App\Exceptions\BaseException;
use App\Exceptions\PermissionException;
use App\Exceptions\ServiceException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseService
{
    protected string $modelClass;

    protected array $defaultEagerLoad = [];

    protected array $searchableFields = ['name'];

    protected string $defaultSortField = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function __construct()
    {
        $this->initializeService();
    }

    abstract protected function initializeService(): void;

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery();
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters);

        if (! empty($this->defaultEagerLoad)) {
            $query->with($this->defaultEagerLoad);
        }

        return $query->paginate($perPage);
    }

    public function getAll(array $filters = []): Collection
    {
        $query = $this->buildBaseQuery();
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters);

        if (! empty($this->defaultEagerLoad)) {
            $query->with($this->defaultEagerLoad);
        }

        return $query->get();
    }

    public function findById(int $id): ?Model
    {
        $query = $this->buildBaseQuery();

        if (! empty($this->defaultEagerLoad)) {
            $query->with($this->defaultEagerLoad);
        }

        return $query->find($id);
    }

    public function findByIdOrFail(int $id): Model
    {
        $query = $this->buildBaseQuery();

        if (! empty($this->defaultEagerLoad)) {
            $query->with($this->defaultEagerLoad);
        }

        return $query->findOrFail($id);
    }

    public function create(array $data): Model
    {
        try {
            return DB::transaction(function () use ($data) {
                $data = $this->prepareCreateData($data);
                $data['company_id'] = Auth::user()->company_id;

                $model = $this->modelClass::create($data);

                $this->afterCreate($model, $data);
                $this->logActivity($model, 'created');

                return $model;
            });
        } catch (QueryException $e) {
            throw $this->handleDatabaseException($e, 'create', $data);
        } catch (Throwable $e) {
            throw $this->handleGenericException($e, 'create', $data);
        }
    }

    public function update(Model $model, array $data): Model
    {
        try {
            return DB::transaction(function () use ($model, $data) {
                $data = $this->prepareUpdateData($data, $model);

                $model->update($data);
                $model = $model->fresh();

                $this->afterUpdate($model, $data);
                $this->logActivity($model, 'updated');

                return $model;
            });
        } catch (QueryException $e) {
            throw $this->handleDatabaseException($e, 'update', ['model_id' => $model->id, 'data' => $data]);
        } catch (Throwable $e) {
            throw $this->handleGenericException($e, 'update', ['model_id' => $model->id, 'data' => $data]);
        }
    }

    public function archive(Model $model): bool
    {
        return DB::transaction(function () use ($model) {
            $this->beforeArchive($model);

            if (method_exists($model, 'archive')) {
                $result = $model->archive();
            } else {
                $result = $model->update(['archived_at' => now()]);
            }

            $this->afterArchive($model);
            $this->logActivity($model, 'archived');

            return $result;
        });
    }

    public function restore(Model $model): bool
    {
        return DB::transaction(function () use ($model) {
            $this->beforeRestore($model);

            if (method_exists($model, 'restore')) {
                $result = $model->restore();
            } else {
                $result = $model->update(['archived_at' => null]);
            }

            $this->afterRestore($model);
            $this->logActivity($model, 'restored');

            return $result;
        });
    }

    public function delete(Model $model): bool
    {
        return DB::transaction(function () use ($model) {
            $this->beforeDelete($model);

            $result = $model->delete();

            $this->afterDelete($model);
            $this->logActivity($model, 'deleted');

            return $result;
        });
    }

    public function bulkUpdate(array $ids, array $data): int
    {
        return DB::transaction(function () use ($ids, $data) {
            $models = $this->buildBaseQuery()->whereIn('id', $ids)->get();
            $updated = 0;

            foreach ($models as $model) {
                if ($this->canUpdate($model)) {
                    $model->update($data);
                    $updated++;
                }
            }

            $this->logActivity(null, 'bulk_updated', [
                'ids' => $ids,
                'count' => $updated,
                'data' => $data,
            ]);

            return $updated;
        });
    }

    public function bulkArchive(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            $models = $this->buildBaseQuery()->whereIn('id', $ids)->get();
            $archived = 0;

            foreach ($models as $model) {
                if ($this->canArchive($model)) {
                    $this->archive($model);
                    $archived++;
                }
            }

            $this->logActivity(null, 'bulk_archived', [
                'ids' => $ids,
                'count' => $archived,
            ]);

            return $archived;
        });
    }

    public function search(string $query, int $limit = 10): Collection
    {
        $queryBuilder = $this->buildBaseQuery();

        $queryBuilder->where(function ($q) use ($query) {
            foreach ($this->searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$query}%");
            }
        });

        return $queryBuilder->limit($limit)->get();
    }

    protected function buildBaseQuery()
    {
        $query = $this->modelClass::where('company_id', Auth::user()->company_id);

        // Apply default filters (e.g., exclude archived)
        if (in_array('archived_at', $this->modelClass::make()->getFillable()) ||
            method_exists($this->modelClass::make(), 'getArchivedAtColumn')) {
            $query->whereNull('archived_at');
        }

        return $query;
    }

    protected function applyFilters($query, array $filters)
    {
        // Apply search filter
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        // Apply status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply type filter
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Apply date range filters
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $this->applyCustomFilters($query, $filters);
    }

    protected function applyCustomFilters($query, array $filters)
    {
        return $query;
    }

    protected function applySorting($query, array $filters)
    {
        $sortBy = $filters['sort'] ?? $this->defaultSortField;
        $sortDirection = $filters['direction'] ?? $this->defaultSortDirection;

        return $query->orderBy($sortBy, $sortDirection);
    }

    protected function prepareCreateData(array $data): array
    {
        return $data;
    }

    protected function prepareUpdateData(array $data, Model $model): array
    {
        return $data;
    }

    protected function afterCreate(Model $model, array $data): void
    {
        // Override in child classes for post-creation logic
    }

    protected function afterUpdate(Model $model, array $data): void
    {
        // Override in child classes for post-update logic
    }

    protected function beforeArchive(Model $model): void
    {
        // Override in child classes for pre-archive logic
    }

    protected function afterArchive(Model $model): void
    {
        // Override in child classes for post-archive logic
    }

    protected function beforeRestore(Model $model): void
    {
        // Override in child classes for pre-restore logic
    }

    protected function afterRestore(Model $model): void
    {
        // Override in child classes for post-restore logic
    }

    protected function beforeDelete(Model $model): void
    {
        // Override in child classes for pre-delete logic
    }

    protected function afterDelete(Model $model): void
    {
        // Override in child classes for post-delete logic
    }

    protected function canUpdate(Model $model): bool
    {
        return Auth::user()->can('update', $model);
    }

    protected function canArchive(Model $model): bool
    {
        return Auth::user()->can('delete', $model);
    }

    protected function canDelete(Model $model): bool
    {
        return Auth::user()->can('forceDelete', $model);
    }

    protected function logActivity(?Model $model, string $action, array $metadata = []): void
    {
        $logData = [
            'action' => $action,
            'model_type' => $this->modelClass,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id,
        ];

        if ($model) {
            $logData['model_id'] = $model->id;
            $logData['model_name'] = $model->name ?? $model->title ?? null;
        }

        if (! empty($metadata)) {
            $logData['metadata'] = $metadata;
        }

        Log::info('Service action performed', $logData);
    }

    public function getStatistics(): array
    {
        $query = $this->buildBaseQuery();

        $stats = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
        ];

        // Add type breakdown if model has type field
        if (in_array('type', $this->modelClass::make()->getFillable())) {
            $stats['by_type'] = (clone $query)
                ->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray();
        }

        // Add status breakdown if model has status field
        if (in_array('status', $this->modelClass::make()->getFillable())) {
            $stats['by_status'] = (clone $query)
                ->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray();
        }

        return array_merge($stats, $this->getCustomStatistics());
    }

    protected function getCustomStatistics(): array
    {
        return [];
    }

    public function validateBatch(array $ids): array
    {
        return $this->buildBaseQuery()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();
    }

    public function getForDropdown(string $labelField = 'name', string $valueField = 'id'): Collection
    {
        return $this->buildBaseQuery()
            ->orderBy($labelField)
            ->get([$valueField, $labelField]);
    }

    public function getRecentlyCreated(int $limit = 10): Collection
    {
        return $this->buildBaseQuery()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getRecentlyUpdated(int $limit = 10): Collection
    {
        return $this->buildBaseQuery()
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Handle database exceptions and convert to domain-specific exceptions
     */
    protected function handleDatabaseException(QueryException $e, string $operation, array $context = []): BaseException
    {
        $message = $e->getMessage();
        $domainName = $this->getDomainName();

        // Handle specific database errors
        if (str_contains($message, 'Duplicate entry')) {
            return $this->createDomainException(
                "Duplicate {$operation}: A record with this information already exists",
                409,
                $context,
                'A record with this information already exists.'
            );
        }

        if (str_contains($message, 'foreign key constraint')) {
            return $this->createDomainException(
                "Foreign key constraint violation during {$operation}",
                400,
                $context,
                'This record cannot be modified because it is referenced by other data.'
            );
        }

        if (str_contains($message, 'Data too long')) {
            return $this->createDomainException(
                "Data too long during {$operation}",
                422,
                $context,
                'The provided data is too long for one or more fields.'
            );
        }

        if (str_contains($message, 'cannot be null')) {
            return $this->createDomainException(
                "Required field missing during {$operation}",
                422,
                $context,
                'Required information is missing.'
            );
        }

        if (str_contains($message, 'Deadlock found')) {
            return $this->createDomainException(
                "Database deadlock during {$operation}",
                503,
                $context,
                'A temporary database conflict occurred. Please try again.'
            );
        }

        // Default database exception
        return $this->createDomainException(
            "Database error during {$operation}: {$message}",
            500,
            array_merge($context, ['sql_error' => $message]),
            'A database error occurred. Please try again later.'
        );
    }

    /**
     * Handle generic exceptions
     */
    protected function handleGenericException(Throwable $e, string $operation, array $context = []): BaseException
    {
        // Don't wrap if it's already a domain exception
        if ($e instanceof BaseException) {
            return $e;
        }

        if ($e instanceof ModelNotFoundException) {
            $modelName = class_basename($this->modelClass);

            return $this->createDomainException(
                "{$modelName} not found during {$operation}",
                404,
                $context,
                "The requested {$modelName} could not be found."
            );
        }

        // Default generic exception
        return $this->createDomainException(
            "Service error during {$operation}: {$e->getMessage()}",
            500,
            array_merge($context, [
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]),
            'An unexpected error occurred. Please try again later.'
        );
    }

    /**
     * Create a domain-specific exception
     */
    protected function createDomainException(
        string $message,
        int $statusCode,
        array $context = [],
        string $userMessage = ''
    ): BaseException {
        $domainName = $this->getDomainName();
        $exceptionClass = "App\\Domains\\{$domainName}\\Exceptions\\{$domainName}ServiceException";

        // Check if domain-specific exception class exists
        if (class_exists($exceptionClass)) {
            return new $exceptionClass($message, $statusCode, null, $context, $userMessage, $statusCode);
        }

        // Fallback to generic service exception
        return new class($message, $statusCode, null, $context, $userMessage, $statusCode) extends ServiceException
        {
            protected function getDefaultUserMessage(): string
            {
                return 'A service error occurred. Please try again later.';
            }
        };
    }

    /**
     * Get the domain name from the service class
     */
    protected function getDomainName(): string
    {
        $className = class_basename($this);

        return str_replace('Service', '', $className);
    }

    /**
     * Check if user has permission for the given action on the model
     */
    protected function checkPermission(string $action, ?Model $model = null): void
    {
        $user = Auth::user();

        if (! $user) {
            throw new class('Authentication required', 401) extends PermissionException
            {
                protected function getDefaultUserMessage(): string
                {
                    return 'You must be logged in to perform this action.';
                }
            };
        }

        $resource = $model ?? $this->modelClass;

        if (! $user->can($action, $resource)) {
            throw new class($action, class_basename($this->modelClass)) extends PermissionException
            {
                protected function getDefaultUserMessage(): string
                {
                    return 'You do not have permission to perform this action.';
                }
            };
        }
    }

    /**
     * Validate that a model belongs to the current user's company
     */
    protected function validateCompanyOwnership(Model $model): void
    {
        $user = Auth::user();

        if ($model->company_id !== $user->company_id) {
            throw new class('Access denied', 'Resource') extends PermissionException
            {
                protected function getDefaultUserMessage(): string
                {
                    return 'You do not have access to this resource.';
                }
            };
        }
    }

    /**
     * Validate that an array of IDs belong to the current user's company
     */
    protected function validateBatchCompanyOwnership(array $ids): array
    {
        $validIds = $this->buildBaseQuery()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        $invalidIds = array_diff($ids, $validIds);

        if (! empty($invalidIds)) {
            throw new class('Access denied', 'Resources') extends PermissionException
            {
                protected function getDefaultUserMessage(): string
                {
                    return 'You do not have access to some of the requested resources.';
                }
            };
        }

        return $validIds;
    }
}
