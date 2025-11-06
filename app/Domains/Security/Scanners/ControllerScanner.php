<?php

namespace App\Domains\Security\Scanners;

use Illuminate\Support\Facades\File;

/**
 * Scans Controller files to discover permissions from authorize() and can() calls
 */
class ControllerScanner
{
    /**
     * Scan all controllers for permission checks
     */
    public function scan(): array
    {
        $permissions = [];
        
        // Scan domain controllers
        $domainControllers = $this->findDomainControllers();
        foreach ($domainControllers as $controllerPath) {
            $discovered = $this->scanControllerFile($controllerPath);
            $permissions = array_merge($permissions, $discovered);
        }

        // Deduplicate and format
        return $this->formatPermissions($permissions);
    }

    /**
     * Find all domain controller files
     */
    private function findDomainControllers(): array
    {
        $controllers = [];
        $domainsPath = app_path('Domains');
        
        if (!File::isDirectory($domainsPath)) {
            return [];
        }

        // Find all Controllers directories in domains
        $directories = File::directories($domainsPath);
        
        foreach ($directories as $domainDir) {
            $controllersPath = $domainDir . '/Controllers';
            if (File::isDirectory($controllersPath)) {
                $files = File::allFiles($controllersPath);
                $controllers = array_merge($controllers, $files);
            }
        }

        return $controllers;
    }

    /**
     * Scan a controller file for permission checks
     */
    private function scanControllerFile($filePath): array
    {
        $content = File::get($filePath);
        $permissions = [];
        
        $fileName = basename($filePath);

        // Pattern 1: $this->authorize('permission', Model::class)
        // Extract just the permission string
        preg_match_all("/\\\$this->authorize\(['\"]([a-zA-Z]+)['\"]/", $content, $authorizeMatches);
        
        // Pattern 2: ->can('permission.string')
        preg_match_all("/->can\(['\"]([a-z0-9\.\-\_]+)['\"]\)/", $content, $canMatches);
        
        // Pattern 3: Gate::allows('permission.string')
        preg_match_all("/Gate::allows\(['\"]([a-z0-9\.\-\_]+)['\"]\)/", $content, $gateMatches);
        
        // Combine all matches
        $allMatches = array_merge(
            $authorizeMatches[1] ?? [],
            $canMatches[1] ?? [],
            $gateMatches[1] ?? []
        );

        foreach ($allMatches as $permission) {
            // Skip policy methods (viewAny, view, create, etc.)
            $policyMethods = ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'];
            if (in_array($permission, $policyMethods)) {
                continue;
            }
            
            // Only include permissions with dot notation
            if (strpos($permission, '.') !== false) {
                $permissions[] = [
                    'name' => $permission,
                    'source_file' => $fileName,
                    'source_type' => 'controller',
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
            'approve' => 'Approve',
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
