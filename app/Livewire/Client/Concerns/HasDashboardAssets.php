<?php

namespace App\Livewire\Client\Concerns;

trait HasDashboardAssets
{
    protected function getAssets()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->assets()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getAssetStats(): array
    {
        if (! $this->client) {
            return [];
        }

        $assets = $this->client->assets();

        return [
            'total_assets' => $assets->count(),
            'active_assets' => $assets->where('status', 'active')->count(),
            'maintenance_due' => $assets->where('status', 'active')->where('next_maintenance_date', '<=', now()->addDays(30))->count(),
            'warranty_expiring' => $assets->where('status', 'active')->where('warranty_expire', '<=', now()->addDays(60))->count(),
        ];
    }

    protected function getAssetHealth()
    {
        if (! $this->client) {
            return [];
        }

        $assets = $this->client->assets()->where('status', 'active')->get();
        
        $criticalCount = $assets->filter(function ($asset) {
            return $asset->warranty_expire && $asset->warranty_expire->isPast();
        })->count();

        $warningCount = $assets->filter(function ($asset) {
            return $asset->warranty_expire && 
                   $asset->warranty_expire->isFuture() && 
                   $asset->warranty_expire->diffInDays(now()) <= 60;
        })->count();

        $healthyCount = $assets->count() - $criticalCount - $warningCount;

        $overallHealth = $assets->count() > 0 
            ? round((($healthyCount * 100) + ($warningCount * 50)) / $assets->count(), 0)
            : 100;

        return [
            'overall' => $overallHealth,
            'total' => $assets->count(),
            'healthy' => $healthyCount,
            'warning' => $warningCount,
            'critical' => $criticalCount,
            'categories' => $assets->groupBy('type')->map->count()->toArray(),
        ];
    }
}
