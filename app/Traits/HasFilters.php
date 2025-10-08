<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasFilters
{
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeCreatedBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);
    }

    public function scopeCreatedToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeCreatedThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ]);
    }

    public function scopeCreatedThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ]);
    }

    public function scopeUpdatedToday(Builder $query): Builder
    {
        return $query->whereDate('updated_at', Carbon::today());
    }

    public function scopeUpdatedThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('updated_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ]);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForLocation(Builder $query, int $locationId): Builder
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $this->applySingleFilter($query, $key, $value);
        }

        return $query;
    }

    protected function applySingleFilter(Builder $query, string $key, $value): void
    {
        $filterMethod = $this->getFilterMethod($key);

        if ($filterMethod && method_exists($this, $filterMethod)) {
            $this->$filterMethod($query, $value);
            return;
        }

        $this->applyCustomFilter($query, $key, $value);
    }

    protected function getFilterMethod(string $key): ?string
    {
        $filterMethods = [
            'search' => 'applySearchFilter',
            'status' => 'applyStatusFilter',
            'type' => 'applyTypeFilter',
            'client_id' => 'applyClientIdFilter',
            'location_id' => 'applyLocationIdFilter',
            'user_id' => 'applyUserIdFilter',
            'date_from' => 'applyDateFromFilter',
            'date_to' => 'applyDateToFilter',
            'created_today' => 'applyCreatedTodayFilter',
            'created_this_week' => 'applyCreatedThisWeekFilter',
            'created_this_month' => 'applyCreatedThisMonthFilter',
            'active_only' => 'applyActiveOnlyFilter',
            'archived' => 'applyArchivedFilter',
        ];

        return $filterMethods[$key] ?? null;
    }

    protected function applySearchFilter(Builder $query, $value): void
    {
        if (method_exists($this, 'scopeSearch')) {
            $query->search($value);
        }
    }

    protected function applyStatusFilter(Builder $query, $value): void
    {
        $query->byStatus($value);
    }

    protected function applyTypeFilter(Builder $query, $value): void
    {
        $query->byType($value);
    }

    protected function applyClientIdFilter(Builder $query, $value): void
    {
        $query->forClient($value);
    }

    protected function applyLocationIdFilter(Builder $query, $value): void
    {
        $query->forLocation($value);
    }

    protected function applyUserIdFilter(Builder $query, $value): void
    {
        $query->forUser($value);
    }

    protected function applyDateFromFilter(Builder $query, $value): void
    {
        $query->where('created_at', '>=', Carbon::parse($value)->startOfDay());
    }

    protected function applyDateToFilter(Builder $query, $value): void
    {
        $query->where('created_at', '<=', Carbon::parse($value)->endOfDay());
    }

    protected function applyCreatedTodayFilter(Builder $query, $value): void
    {
        if ($value) {
            $query->createdToday();
        }
    }

    protected function applyCreatedThisWeekFilter(Builder $query, $value): void
    {
        if ($value) {
            $query->createdThisWeek();
        }
    }

    protected function applyCreatedThisMonthFilter(Builder $query, $value): void
    {
        if ($value) {
            $query->createdThisMonth();
        }
    }

    protected function applyActiveOnlyFilter(Builder $query, $value): void
    {
        if ($value) {
            $query->active();
        }
    }

    protected function applyArchivedFilter(Builder $query, $value): void
    {
        if ($value) {
            $query->archived();
        } else {
            $query->notArchived();
        }
    }

    protected function applyCustomFilter(Builder $query, string $key, $value): Builder
    {
        // Override in models to handle custom filters
        return $query;
    }

    public function scopeSortBy(Builder $query, string $field, string $direction = 'asc'): Builder
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        // Handle relationship sorting
        if (str_contains($field, '.')) {
            [$relation, $column] = explode('.', $field, 2);

            return $query->leftJoin(
                $this->getRelationTable($relation),
                $this->getTable().'.'.$this->getRelationForeignKey($relation),
                '=',
                $this->getRelationTable($relation).'.id'
            )->orderBy($this->getRelationTable($relation).'.'.$column, $direction);
        }

        return $query->orderBy($field, $direction);
    }

    protected function getRelationTable(string $relation): string
    {
        return $this->$relation()->getRelated()->getTable();
    }

    protected function getRelationForeignKey(string $relation): string
    {
        return $relation.'_id';
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOldestFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeAlphabetical(Builder $query): Builder
    {
        return $query->orderBy('name', 'asc');
    }

    public function scopeReverseAlphabetical(Builder $query): Builder
    {
        return $query->orderBy('name', 'desc');
    }
}
