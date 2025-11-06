<?php

namespace App\Domains\Security\Registry;

use App\Domains\Security\Scanners\PolicyScanner;
use App\Domains\Security\Scanners\ControllerScanner;
use App\Domains\Security\Scanners\LivewireScanner;
use Illuminate\Support\Facades\Cache;
use Silber\Bouncer\BouncerFacade as Bouncer;

/**
 * Central registry for managing and caching discovered permissions
 */
class PermissionRegistry
{
    private const CACHE_KEY = 'permission_registry';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all permissions (cached)
     */
    public function getAll(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->discover();
        });
    }

    /**
     * Discover permissions from all sources
     */
    public function discover(): array
    {
        $policyPerms = app(PolicyScanner::class)->scan();
        $controllerPerms = app(ControllerScanner::class)->scan();
        $livewirePerms = app(LivewireScanner::class)->scan();

        return $this->merge($policyPerms, $controllerPerms, $livewirePerms);
    }

    /**
     * Merge permissions from multiple sources
     */
    public function merge(array ...$arrays): array
    {
        $merged = [];
        
        foreach ($arrays as $permissions) {
            foreach ($permissions as $perm) {
                $name = $perm['name'];
                
                if (!isset($merged[$name])) {
                    $merged[$name] = $perm;
                    if (!isset($merged[$name]['source_files'])) {
                        $merged[$name]['source_files'] = [];
                    }
                } else {
                    // Merge source files
                    if (isset($perm['source_files'])) {
                        $merged[$name]['source_files'] = array_unique(
                            array_merge($merged[$name]['source_files'], $perm['source_files'])
                        );
                    }
                }
            }
        }
        
        return array_values($merged);
    }

    /**
     * Get permissions grouped by category
     */
    public function groupByCategory(bool $fresh = false): array
    {
        $permissions = $this->getAll($fresh);
        $grouped = [];

        foreach ($permissions as $perm) {
            $category = $perm['category'];
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'title' => ucfirst($category),
                    'permissions' => [],
                ];
            }
            
            $grouped[$category]['permissions'][] = $perm;
        }

        ksort($grouped);
        return $grouped;
    }

    /**
     * Get permissions from database (Bouncer abilities)
     */
    public function getFromDatabase(): array
    {
        return Bouncer::ability()
            ->orderBy('name')
            ->get()
            ->map(function ($ability) {
                return [
                    'name' => $ability->name,
                    'title' => $ability->title,
                    'category' => $this->extractCategory($ability->name),
                ];
            })
            ->toArray();
    }

    /**
     * Compare discovered vs database permissions
     */
    public function compareWithDatabase(): array
    {
        $discovered = $this->getAll(true);
        $database = $this->getFromDatabase();

        $discoveredNames = array_column($discovered, 'name');
        $databaseNames = array_column($database, 'name');

        return [
            'missing_in_db' => array_values(array_diff($discoveredNames, $databaseNames)),
            'only_in_db' => array_values(array_diff($databaseNames, $discoveredNames)),
            'in_sync' => array_values(array_intersect($discoveredNames, $databaseNames)),
        ];
    }

    /**
     * Clear the permission cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Extract category from permission name
     */
    private function extractCategory(string $permission): string
    {
        $parts = explode('.', $permission);
        return $parts[0];
    }
}
