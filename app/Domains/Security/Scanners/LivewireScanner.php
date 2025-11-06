<?php

namespace App\Domains\Security\Scanners;

use Illuminate\Support\Facades\File;

/**
 * Scans Livewire components to discover permissions from authorize() and can() calls
 */
class LivewireScanner
{
    /**
     * Scan all Livewire components for permission checks
     */
    public function scan(): array
    {
        $permissions = [];
        $components = $this->findLivewireComponents();

        foreach ($components as $componentPath) {
            $discovered = $this->scanComponentFile($componentPath);
            $permissions = array_merge($permissions, $discovered);
        }

        return $this->formatPermissions($permissions);
    }

    /**
     * Find all Livewire component files
     */
    private function findLivewireComponents(): array
    {
        $livewirePath = app_path('Livewire');
        
        if (!File::isDirectory($livewirePath)) {
            return [];
        }

        return File::allFiles($livewirePath);
    }

    /**
     * Scan a component file for permission checks
     */
    private function scanComponentFile($filePath): array
    {
        $content = File::get($filePath);
        $permissions = [];
        
        $fileName = basename($filePath);

        // Pattern 1: $this->authorize('permission', $model)
        preg_match_all("/\\\$this->authorize\(['\"]([a-zA-Z]+)['\"]/", $content, $authorizeMatches);
        
        // Pattern 2: auth()->user()->can('permission.string')
        preg_match_all("/auth\(\)->user\(\)->can\(['\"]([a-z0-9\.\-\_]+)['\"]\)/", $content, $authCanMatches);
        
        // Pattern 3: $user->can('permission.string')
        preg_match_all("/\\\$user->can\(['\"]([a-z0-9\.\-\_]+)['\"]\)/", $content, $userCanMatches);
        
        // Pattern 4: ->can('permission', $model)
        preg_match_all("/->can\(['\"]([a-z0-9\.\-\_]+)['\"]/", $content, $canMatches);

        $allMatches = array_merge(
            $authorizeMatches[1] ?? [],
            $authCanMatches[1] ?? [],
            $userCanMatches[1] ?? [],
            $canMatches[1] ?? []
        );

        foreach ($allMatches as $permission) {
            // Skip policy methods
            $policyMethods = ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'];
            if (in_array($permission, $policyMethods)) {
                continue;
            }
            
            // Only include permissions with dot notation
            if (strpos($permission, '.') !== false) {
                $permissions[] = [
                    'name' => $permission,
                    'source_file' => $fileName,
                    'source_type' => 'livewire',
                ];
            }
        }

        return $permissions;
    }

    /**
     * Format and deduplicate permissions
     */
    private function formatPermissions(array $permissions): array
    {
        $grouped = [];
        
        foreach ($permissions as $perm) {
            $name = $perm['name'];
            
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'name' => $name,
                    'title' => $this->generateTitle($name),
                    'category' => $this->extractCategory($name),
                    'source_type' => $perm['source_type'],
                    'source_files' => [],
                ];
            }
            
            $grouped[$name]['source_files'][] = $perm['source_file'];
        }

        $result = array_values($grouped);
        usort($result, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Extract category from permission name
     */
    private function extractCategory(string $permission): string
    {
        $parts = explode('.', $permission);
        return $parts[0];
    }

    /**
     * Generate human-readable title
     */
    private function generateTitle(string $permission): string
    {
        $parts = explode('.', $permission);
        
        $actionMap = [
            'view' => 'View',
            'create' => 'Create',
            'edit' => 'Edit',
            'update' => 'Update',
            'delete' => 'Delete',
            'manage' => 'Manage',
            'export' => 'Export',
            'import' => 'Import',
        ];

        $action = end($parts);
        $actionTitle = $actionMap[$action] ?? ucfirst($action);

        array_pop($parts);
        $resourceParts = array_map(function($part) {
            return ucfirst(str_replace(['-', '_'], ' ', $part));
        }, $parts);

        if (empty($resourceParts)) {
            return $actionTitle;
        }

        return $actionTitle . ' ' . implode(' ', $resourceParts);
    }
}
