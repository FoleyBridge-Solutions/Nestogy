<?php

namespace App\Domains\Security\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Silber\Bouncer\BouncerFacade as Bouncer;

/**
 * Service for managing tenant-specific roles
 * 
 * Creates and manages default roles for each company (tenant)
 * based on templates defined in config/role-templates.php
 */
class TenantRoleService
{
    /**
     * Create default roles for a new company
     * 
     * @param int $companyId The company ID to create roles for
     * @return array Summary of created roles
     */
    public function createDefaultRoles(int $companyId): array
    {
        return DB::transaction(function () use ($companyId) {
            $created = [];
            $skipped = [];
            
            // Get role templates from config
            $templates = config('role-templates', []);
            
            foreach ($templates as $roleName => $config) {
                try {
                    // Check if role already exists for THIS SPECIFIC company scope
                    // Do NOT use Bouncer::scope()->to() here as it will match global roles
                    $existingRole = \Silber\Bouncer\Database\Role::where('name', $roleName)
                        ->where('scope', $companyId)
                        ->first();
                    
                    if ($existingRole) {
                        $skipped[] = $roleName;
                        continue;
                    }
                    
                    // Set Bouncer scope for permission assignment
                    Bouncer::scope()->to($companyId);
                    
                    // Create role scoped to company
                    // Note: Must explicitly set 'scope' in create array
                    $role = Bouncer::role()->create([
                        'name' => $roleName,
                        'title' => $config['title'] ?? ucfirst($roleName),
                        'scope' => $companyId, // Explicitly set scope
                    ]);
                    
                    // Assign permissions from template
                    $permissionsAssigned = 0;
                    foreach ($config['permissions'] ?? [] as $permission) {
                        // Check if permission exists
                        $ability = Bouncer::ability()->where('name', $permission)->first();
                        
                        if ($ability) {
                            Bouncer::allow($role)->to($permission);
                            $permissionsAssigned++;
                        }
                    }
                    
                    $created[] = [
                        'name' => $roleName,
                        'title' => $role->title,
                        'permissions' => $permissionsAssigned,
                    ];
                    
                } catch (\Exception $e) {
                    Log::error("Failed to create role {$roleName} for company {$companyId}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
            
            // Refresh Bouncer cache
            Bouncer::refresh();
            
            Log::info("Default roles created for company {$companyId}", [
                'created' => count($created),
                'skipped' => count($skipped),
                'roles' => $created,
            ]);
            
            return [
                'created' => $created,
                'skipped' => $skipped,
                'total' => count($created),
            ];
        });
    }
    
    /**
     * Update existing roles to match templates (adds missing permissions)
     * 
     * @param int $companyId The company ID
     * @return array Summary of updates
     */
    public function syncRolesToTemplates(int $companyId): array
    {
        return DB::transaction(function () use ($companyId) {
            $updated = [];
            
            // Set Bouncer scope
            Bouncer::scope()->to($companyId);
            
            $templates = config('role-templates', []);
            
            foreach ($templates as $roleName => $config) {
                $role = Bouncer::role()
                    ->where('name', $roleName)
                    ->where('scope', $companyId)
                    ->first();
                
                if (!$role) {
                    continue;
                }
                
                $added = 0;
                $currentPermissions = $role->abilities->pluck('name')->toArray();
                
                foreach ($config['permissions'] ?? [] as $permission) {
                    if (!in_array($permission, $currentPermissions)) {
                        $ability = Bouncer::ability()->where('name', $permission)->first();
                        
                        if ($ability) {
                            Bouncer::allow($role)->to($permission);
                            $added++;
                        }
                    }
                }
                
                if ($added > 0) {
                    $updated[] = [
                        'name' => $roleName,
                        'added' => $added,
                    ];
                }
            }
            
            Bouncer::refresh();
            
            return [
                'updated' => $updated,
                'total' => count($updated),
            ];
        });
    }
    
    /**
     * Get role template configuration
     * 
     * @param string|null $roleName Specific role or all if null
     * @return array
     */
    public function getTemplate(?string $roleName = null): array
    {
        $templates = config('role-templates', []);
        
        if ($roleName) {
            return $templates[$roleName] ?? [];
        }
        
        return $templates;
    }
    
    /**
     * Validate that all permissions in templates exist
     * 
     * @return array Missing permissions by role
     */
    public function validateTemplates(): array
    {
        $templates = config('role-templates', []);
        $missing = [];
        
        // Get all existing abilities
        $existingAbilities = Bouncer::ability()->pluck('name')->toArray();
        
        foreach ($templates as $roleName => $config) {
            foreach ($config['permissions'] ?? [] as $permission) {
                if (!in_array($permission, $existingAbilities)) {
                    if (!isset($missing[$roleName])) {
                        $missing[$roleName] = [];
                    }
                    $missing[$roleName][] = $permission;
                }
            }
        }
        
        return $missing;
    }
}
