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

            switch ($key) {
                case 'search':
                    if (method_exists($this, 'scopeSearch')) {
                        $query->search($value);
                    }
                    break;

                case 'status':
                    $query->byStatus($value);
                    break;

                case 'type':
                    $query->byType($value);
                    break;

                case 'client_id':
                    $query->forClient($value);
                    break;

                case 'location_id':
                    $query->forLocation($value);
                    break;

                case 'user_id':
                    $query->forUser($value);
                    break;

                case 'date_from':
                    $query->where('created_at', '>=', Carbon::parse($value)->startOfDay());
                    break;

                case 'date_to':
                    $query->where('created_at', '<=', Carbon::parse($value)->endOfDay());
                    break;

                case 'created_today':
                    if ($value) {
                        $query->createdToday();
                    }
                    break;

                case 'created_this_week':
                    if ($value) {
                        $query->createdThisWeek();
                    }
                    break;

                case 'created_this_month':
                    if ($value) {
                        $query->createdThisMonth();
                    }
                    break;

                case 'active_only':
                    if ($value) {
                        $query->active();
                    }
                    break;

                case 'archived':
                    if ($value) {
                        $query->archived();
                    } else {
                        $query->notArchived();
                    }
                    break;

                default:
                    // Handle custom filters
                    $this->applyCustomFilter($query, $key, $value);
                    break;
            }
        }

        return $query;
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
