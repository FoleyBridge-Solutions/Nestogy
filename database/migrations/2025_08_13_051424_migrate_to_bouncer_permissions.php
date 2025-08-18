<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing permissions to Bouncer abilities
        $this->migratePermissions();
        
        // Migrate existing roles to Bouncer roles
        $this->migrateRoles();
        
        // Migrate role-permission relationships
        $this->migrateRolePermissions();
        
        // Migrate user role assignments
        $this->migrateUserRoles();
        
        // Migrate direct user permissions
        $this->migrateUserPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear Bouncer tables (but don't drop them - they might be used elsewhere)
        DB::table('bouncer_permissions')->delete();
        DB::table('bouncer_assigned_roles')->delete(); 
        DB::table('bouncer_roles')->delete();
        DB::table('bouncer_abilities')->delete();
    }
    
    /**
     * Migrate existing permissions to Bouncer abilities.
     */
    private function migratePermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return; // No existing permissions table
        }
        
        $existingPermissions = DB::table('permissions')->get();
        
        foreach ($existingPermissions as $permission) {
            // Convert to Bouncer ability
            $abilityData = [
                'name' => $permission->slug, // Use slug as the ability name
                'title' => $permission->name,
                'created_at' => $permission->created_at ?? now(),
                'updated_at' => $permission->updated_at ?? now(),
            ];
            
            // Insert into Bouncer abilities table, ignore duplicates
            DB::table('bouncer_abilities')->insertOrIgnore($abilityData);
        }
        
        echo "Migrated " . $existingPermissions->count() . " permissions to Bouncer abilities.\n";
    }
    
    /**
     * Migrate existing roles to Bouncer roles.
     */
    private function migrateRoles(): void
    {
        if (!Schema::hasTable('roles')) {
            return; // No existing roles table
        }
        
        $existingRoles = DB::table('roles')->get();
        
        foreach ($existingRoles as $role) {
            $roleData = [
                'name' => $role->slug, // Use slug as the role name
                'title' => $role->name,
                'created_at' => $role->created_at ?? now(),
                'updated_at' => $role->updated_at ?? now(),
            ];
            
            // Insert into Bouncer roles table, ignore duplicates
            DB::table('bouncer_roles')->insertOrIgnore($roleData);
        }
        
        echo "Migrated " . $existingRoles->count() . " roles to Bouncer.\n";
    }
    
    /**
     * Migrate role-permission relationships.
     */
    private function migrateRolePermissions(): void
    {
        if (!Schema::hasTable('role_permissions') || 
            !Schema::hasTable('permissions') || 
            !Schema::hasTable('roles')) {
            return;
        }
        
        $rolePermissions = DB::table('role_permissions')
            ->join('roles', 'role_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->select(
                'roles.slug as role_slug',
                'permissions.slug as permission_slug',
                'role_permissions.created_at'
            )
            ->get();
            
        // Get all companies to scope role permissions per company
        $companies = DB::table('companies')->pluck('id');
            
        foreach ($rolePermissions as $rp) {
            // Find the Bouncer role and ability IDs
            $bouncerRole = DB::table('bouncer_roles')->where('name', $rp->role_slug)->first();
            $bouncerAbility = DB::table('bouncer_abilities')->where('name', $rp->permission_slug)->first();
            
            if ($bouncerRole && $bouncerAbility) {
                // Create permission record for each company scope
                foreach ($companies as $companyId) {
                    DB::table('bouncer_permissions')->insertOrIgnore([
                        'ability_id' => $bouncerAbility->id,
                        'entity_id' => $bouncerRole->id,
                        'entity_type' => 'bouncer_roles', // Use table name, not class name
                        'forbidden' => false,
                        'scope' => $companyId, // Scope to each company
                    ]);
                }
            }
        }
        
        echo "Migrated " . $rolePermissions->count() . " role-permission relationships across " . $companies->count() . " companies.\n";
    }
    
    /**
     * Migrate user role assignments with company scoping.
     */
    private function migrateUserRoles(): void
    {
        if (!Schema::hasTable('user_roles')) {
            return;
        }
        
        $userRoles = DB::table('user_roles')
            ->join('users', 'user_roles.user_id', '=', 'users.id')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->select(
                'user_roles.user_id',
                'roles.slug as role_slug',
                'user_roles.company_id',
                'user_roles.created_at'
            )
            ->get();
            
        foreach ($userRoles as $userRole) {
            $bouncerRole = DB::table('bouncer_roles')->where('name', $userRole->role_slug)->first();
            
            if ($bouncerRole) {
                // Create Bouncer role assignment with company scope
                DB::table('bouncer_assigned_roles')->insertOrIgnore([
                    'role_id' => $bouncerRole->id,
                    'entity_id' => $userRole->user_id,
                    'entity_type' => 'App\\Models\\User',
                    'restricted_to_id' => null,
                    'restricted_to_type' => null,
                    'scope' => $userRole->company_id, // Company-based scoping
                ]);
            }
        }
        
        echo "Migrated " . $userRoles->count() . " user role assignments.\n";
    }
    
    /**
     * Migrate direct user permissions.
     */
    private function migrateUserPermissions(): void
    {
        if (!Schema::hasTable('user_permissions')) {
            return;
        }
        
        $userPermissions = DB::table('user_permissions')
            ->join('users', 'user_permissions.user_id', '=', 'users.id')
            ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.id')
            ->select(
                'user_permissions.user_id',
                'permissions.slug as permission_slug',
                'user_permissions.company_id',
                'user_permissions.granted',
                'user_permissions.created_at'
            )
            ->get();
            
        foreach ($userPermissions as $userPerm) {
            $bouncerAbility = DB::table('bouncer_abilities')->where('name', $userPerm->permission_slug)->first();
            
            if ($bouncerAbility) {
                // Create Bouncer permission assignment
                DB::table('bouncer_permissions')->insertOrIgnore([
                    'ability_id' => $bouncerAbility->id,
                    'entity_id' => $userPerm->user_id,
                    'entity_type' => 'App\\Models\\User',
                    'forbidden' => !$userPerm->granted, // Convert granted to forbidden logic
                    'scope' => $userPerm->company_id, // Company-based scoping
                ]);
            }
        }
        
        echo "Migrated " . $userPermissions->count() . " direct user permissions.\n";
    }
};