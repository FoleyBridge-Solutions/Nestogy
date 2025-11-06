<?php

namespace App\Domains\Security\Scanners;

use ReflectionClass;
use Illuminate\Support\Facades\File;

/**
 * Scans Policy files to discover permissions used in can() checks
 * 
 * Example: Finds 'assets.view' from $user->can('assets.view') calls
 */
class PolicyScanner
{
    /**
     * Scan all policies and extract permission strings
     */
    public function scan(): array
    {
        $permissions = [];
        $policies = $this->findAllPolicies();

        foreach ($policies as $policyPath) {
            $discovered = $this->scanPolicyFile($policyPath);
            $permissions = array_merge($permissions, $discovered);
        }

        // Deduplicate and format
        return $this->formatPermissions($permissions);
    }

    /**
     * Find all policy files in app/Policies
     */
    private function findAllPolicies(): array
    {
        $policiesPath = app_path('Policies');
        
        if (!File::isDirectory($policiesPath)) {
            return [];
        }

        return File::allFiles($policiesPath);
    }

    /**
     * Scan a single policy file for permission checks
     */
    private function scanPolicyFile($filePath): array
    {
        $content = File::get($filePath);
        $permissions = [];

        // Extract policy class name for categorization
        preg_match('/class\s+(\w+Policy)/', $content, $classMatch);
        $policyClass = $classMatch[1] ?? null;
        
        // Get resource name from policy (e.g., AssetPolicy -> assets)
        $resource = $this->extractResourceFromPolicy($policyClass);

        // Find all $user->can('permission.string') calls
        // Matches: ->can('assets.view'), ->can("assets.create"), etc.
        preg_match_all("/->can\(['\"]([a-z0-9\.\-\_]+)['\"]\)/", $content, $matches);

        foreach ($matches[1] as $permission) {
            $permissions[] = [
                'name' => $permission,
                'resource' => $this->extractCategoryFromPermission($permission),
                'source_file' => basename($filePath),
                'source_type' => 'policy',
            ];
        }

        return $permissions;
    }

    /**
     * Extract resource name from policy class name
     * AssetPolicy -> assets, ClientPolicy -> clients
     */
    private function extractResourceFromPolicy(?string $policyClass): ?string
    {
        if (!$policyClass) {
            return null;
        }

        // Remove 'Policy' suffix and convert to lowercase plural
        $resource = str_replace('Policy', '', $policyClass);
        $resource = strtolower($resource);
        
        // Simple pluralization (you can enhance this)
        if (!str_ends_with($resource, 's')) {
            $resource .= 's';
        }

        return $resource;
    }

    /**
     * Extract category from permission name
     * 'assets.maintenance.view' -> 'assets'
     */
    private function extractCategoryFromPermission(string $permission): string
    {
        $parts = explode('.', $permission);
        return $parts[0];
    }

    /**
     * Generate human-readable title from permission name
     * 'assets.maintenance.view' -> 'View Asset Maintenance'
     */
    private function generateTitle(string $permission): string
    {
        $parts = explode('.', $permission);
        
        // Map action words
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
            'execute' => 'Execute',
            'send' => 'Send',
            'assign' => 'Assign',
            'close' => 'Close',
            'reboot' => 'Reboot',
        ];

        // Get action (last part)
        $action = end($parts);
        $actionTitle = $actionMap[$action] ?? ucfirst($action);

        // Get resource parts (everything except action)
        array_pop($parts);
        $resourceParts = array_map(function($part) {
            return ucfirst(str_replace(['-', '_'], ' ', $part));
        }, $parts);

        // Build title: "View Asset Maintenance"
        if (empty($resourceParts)) {
            return $actionTitle;
        }

        return $actionTitle . ' ' . implode(' ', $resourceParts);
    }

    /**
     * Format and deduplicate permissions
     */
    private function formatPermissions(array $permissions): array
    {
        // Group by permission name to deduplicate
        $grouped = [];
        
        foreach ($permissions as $perm) {
            $name = $perm['name'];
            
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'name' => $name,
                    'title' => $this->generateTitle($name),
                    'category' => $perm['resource'],
                    'source_type' => $perm['source_type'],
                    'source_files' => [],
                ];
            }
            
            $grouped[$name]['source_files'][] = $perm['source_file'];
        }

        // Convert to array and sort
        $result = array_values($grouped);
        usort($result, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Get permissions grouped by category
     */
    public function scanGrouped(): array
    {
        $permissions = $this->scan();
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
}
