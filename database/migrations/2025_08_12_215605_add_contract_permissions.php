<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $contractPermissions = [
            // Basic CRUD
            ['name' => 'View Contracts', 'slug' => 'contracts.view', 'domain' => 'financial', 'action' => 'view'],
            ['name' => 'Create Contracts', 'slug' => 'contracts.create', 'domain' => 'financial', 'action' => 'create'],
            ['name' => 'Edit Contracts', 'slug' => 'contracts.edit', 'domain' => 'financial', 'action' => 'edit'],
            ['name' => 'Delete Contracts', 'slug' => 'contracts.delete', 'domain' => 'financial', 'action' => 'delete'],
            
            // Contract lifecycle management
            ['name' => 'Approve Contracts', 'slug' => 'contracts.approve', 'domain' => 'financial', 'action' => 'approve'],
            ['name' => 'Sign Contracts', 'slug' => 'contracts.signature', 'domain' => 'financial', 'action' => 'signature'],
            ['name' => 'Activate Contracts', 'slug' => 'contracts.activate', 'domain' => 'financial', 'action' => 'activate'],
            ['name' => 'Terminate Contracts', 'slug' => 'contracts.terminate', 'domain' => 'financial', 'action' => 'terminate'],
            ['name' => 'Suspend Contracts', 'slug' => 'contracts.suspend', 'domain' => 'financial', 'action' => 'suspend'],
            
            // Contract management features
            ['name' => 'Amend Contracts', 'slug' => 'contracts.amend', 'domain' => 'financial', 'action' => 'amend'],
            ['name' => 'Renew Contracts', 'slug' => 'contracts.renew', 'domain' => 'financial', 'action' => 'renew'],
            ['name' => 'View Contract Financials', 'slug' => 'contracts.financials', 'domain' => 'financial', 'action' => 'financials'],
            ['name' => 'Export Contracts', 'slug' => 'contracts.export', 'domain' => 'financial', 'action' => 'export'],
            ['name' => 'Import Contracts', 'slug' => 'contracts.import', 'domain' => 'financial', 'action' => 'import'],
            ['name' => 'View Contract Analytics', 'slug' => 'contracts.analytics', 'domain' => 'financial', 'action' => 'analytics'],
            ['name' => 'Manage Contract Templates', 'slug' => 'contract-templates.manage', 'domain' => 'financial', 'action' => 'manage'],
            ['name' => 'Bulk Contract Actions', 'slug' => 'contracts.bulk-actions', 'domain' => 'financial', 'action' => 'bulk'],
            ['name' => 'View Contract History', 'slug' => 'contracts.history', 'domain' => 'financial', 'action' => 'history'],
            ['name' => 'Manage Contract Milestones', 'slug' => 'contracts.milestones', 'domain' => 'financial', 'action' => 'milestones'],
        ];

        foreach ($contractPermissions as $permission) {
            $permission['description'] = $permission['name'];
            $permission['is_system'] = true;
            $permission['created_at'] = now();
            $permission['updated_at'] = now();
            
            DB::table('permissions')->insert($permission);
        }
        
        // Assign all contract permissions to existing admin roles
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $superAdminRoleId = DB::table('roles')->where('slug', 'super-admin')->value('id');
        
        if ($adminRoleId || $superAdminRoleId) {
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', array_column($contractPermissions, 'slug'))
                ->pluck('id');
            
            foreach ($permissionIds as $permissionId) {
                if ($adminRoleId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id' => $adminRoleId,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                if ($superAdminRoleId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id' => $superAdminRoleId,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = [
            'contracts.view',
            'contracts.create',
            'contracts.edit',
            'contracts.delete',
            'contracts.approve',
            'contracts.signature',
            'contracts.activate',
            'contracts.terminate',
            'contracts.suspend',
            'contracts.amend',
            'contracts.renew',
            'contracts.financials',
            'contracts.export',
            'contracts.import',
            'contracts.analytics',
            'contract-templates.manage',
            'contracts.bulk-actions',
            'contracts.history',
            'contracts.milestones',
        ];
        
        DB::table('permissions')->whereIn('slug', $slugs)->delete();
    }
};