<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

trait HasArchiving
{
    public function archive(): bool
    {
        return $this->update(['archived_at' => Carbon::now()]);
    }

    public function unarchive(): bool
    {
        return $this->update(['archived_at' => null]);
    }

    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    public function isNotArchived(): bool
    {
        return is_null($this->archived_at);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchivedBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('archived_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    public function scopeArchivedToday(Builder $query): Builder
    {
        return $query->whereDate('archived_at', Carbon::today());
    }

    public function scopeArchivedThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('archived_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    public function scopeArchivedThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('archived_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ]);
    }

    public function getArchivedAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function setArchivedAtAttribute($value)
    {
        $this->attributes['archived_at'] = $value ? Carbon::parse($value) : null;
    }
}