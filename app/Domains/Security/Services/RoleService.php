<?php

namespace App\Domains\Security\Services;

use App\Models\User;
use App\Models\UserSetting;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Illuminate\Support\Facades\DB;

/**
 * RoleService
 * 
 * Bridges the legacy UserSetting role system (numeric 1-4) with the new Bouncer 
 * role system (string names). Provides seamless role management across both systems.
 */
class RoleService
{
    /**
     * Legacy role ID to Bouncer role name mapping
     */
    const ROLE_MAPPING = [
        1 => 'user',        // Basic User - limited access
        2 => 'tech',        // Technician - technical tasks, no financial/user management  
        3 => 'admin',       // Administrator - full company access
        4 => 'super-admin', // Super Administrator - platform-wide access
    ];

    /**
     * Bouncer role name to legacy role ID mapping
     */
    const REVERSE_ROLE_MAPPING = [
        'user' => 1,
        'tech' => 2, 
        'admin' => 3,
        'super-admin' => 4,
        'accountant' => 2, // Map accountant to technician level for UI compatibility
    ];

    /**
     * Role display names for UI
     */
    const ROLE_NAMES = [
        1 => 'User - Basic access, can view assigned items',
        2 => 'Technician - Can manage tickets and technical tasks', 
        3 => 'Admin - Full access within company',
        4 => 'Super Admin - Platform-wide access',
    ];

    /**
     * Available Bouncer roles with descriptions
     */
    const BOUNCER_ROLES = [
        'user' => 'Basic User',
        'tech' => 'Technician',
        'admin' => 'Administrator', 
        'super-admin' => 'Super Administrator',
        'accountant' => 'Accountant',
    ];

    /**
     * Assign role to user (updates both systems)
     * 
     * @param User $user
     * @param int|string $role Legacy role ID or Bouncer role name
     * @param int|null $companyId Company scope for Bouncer
     * @return bool
     */
    public function assignRole(User $user, $role, ?int $companyId = null): bool
    {
        DB::beginTransaction();
        
        try {
            $companyId = $companyId ?? $user->company_id;
            
            // Convert role to both formats
            if (is_numeric($role)) {
                $legacyRoleId = (int) $role;
                $bouncerRole = self::ROLE_MAPPING[$legacyRoleId] ?? null;
            } else {
                $bouncerRole = $role;
                $legacyRoleId = self::REVERSE_ROLE_MAPPING[$role] ?? 1;
            }

            if (!$bouncerRole || !isset(self::BOUNCER_ROLES[$bouncerRole])) {
                throw new \InvalidArgumentException("Invalid role: {$role}");
            }

            // Update UserSetting
            UserSetting::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'role' => $legacyRoleId,
                    'company_id' => $companyId,
                ]
            );

            // Update Bouncer role with company scoping
            Bouncer::scope()->to($companyId);
            
            // Remove existing roles to ensure clean assignment
            $this->removeAllRoles($user);
            
            // Assign new Bouncer role
            Bouncer::assign($bouncerRole)->to($user);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get user's current role in both formats
     * 
     * @param User $user
     * @return array ['legacy_id' => int, 'bouncer_name' => string, 'display_name' => string]
     */
    public function getUserRole(User $user): array
    {
        // Get legacy role from UserSetting
        $legacyRoleId = $user->userSetting->role ?? 1;
        
        // Get Bouncer role
        Bouncer::scope()->to($user->company_id);
        $bouncerRoles = $user->getRoles()->pluck('name')->toArray();
        $primaryBouncerRole = $bouncerRoles[0] ?? self::ROLE_MAPPING[$legacyRoleId] ?? 'user';

        return [
            'legacy_id' => $legacyRoleId,
            'bouncer_name' => $primaryBouncerRole,
            'display_name' => self::ROLE_NAMES[$legacyRoleId] ?? 'User',
            'all_bouncer_roles' => $bouncerRoles,
        ];
    }

    /**
     * Remove all roles from user (both systems)
     * 
     * @param User $user
     * @return bool
     */
    public function removeAllRoles(User $user): bool
    {
        // Remove Bouncer roles
        Bouncer::scope()->to($user->company_id);
        foreach ($user->getRoles() as $role) {
            $roleName = is_object($role) ? $role->name : $role;
            Bouncer::retract($roleName)->from($user);
        }

        return true;
    }

    /**
     * Check if user has specific role
     * 
     * @param User $user
     * @param int|string $role Legacy role ID or Bouncer role name
     * @return bool
     */
    public function hasRole(User $user, $role): bool
    {
        if (is_numeric($role)) {
            // Check legacy role
            return ($user->userSetting->role ?? 1) == $role;
        } else {
            // Check Bouncer role
            Bouncer::scope()->to($user->company_id);
            return $user->isA($role);
        }
    }

    /**
     * Check if user has role level or higher
     * 
     * @param User $user
     * @param int $minRoleId Minimum role level (1=user, 2=tech, 3=admin, 4=super-admin)
     * @return bool
     */
    public function hasRoleLevel(User $user, int $minRoleId): bool
    {
        $userRoleId = $user->userSetting->role ?? 1;
        return $userRoleId >= $minRoleId;
    }

    /**
     * Get all available roles for dropdowns
     * 
     * @param bool $includeSuperAdmin Whether to include super admin option
     * @return array
     */
    public function getAvailableRoles(bool $includeSuperAdmin = false): array
    {
        $roles = self::ROLE_NAMES;
        
        if (!$includeSuperAdmin) {
            unset($roles[4]); // Remove super admin
        }

        return $roles;
    }

    /**
     * Migrate legacy roles to Bouncer for existing users
     * 
     * @param int|null $companyId Specific company or all companies
     * @return int Number of users migrated
     */
    public function migrateLegacyRoles(?int $companyId = null): int
    {
        $query = User::with('userSetting');
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $users = $query->get();
        $migrated = 0;

        foreach ($users as $user) {
            if ($user->userSetting && isset(self::ROLE_MAPPING[$user->userSetting->role])) {
                try {
                    $this->assignRole($user, $user->userSetting->role, $user->company_id);
                    $migrated++;
                } catch (\Exception $e) {
                    // Log error but continue with other users
                    \Log::error("Failed to migrate role for user {$user->id}: " . $e->getMessage());
                }
            }
        }

        return $migrated;
    }

    /**
     * Get role statistics for company
     * 
     * @param int $companyId
     * @return array
     */
    public function getRoleStats(int $companyId): array
    {
        $stats = [];
        
        foreach (self::ROLE_NAMES as $roleId => $roleName) {
            $count = UserSetting::where('company_id', $companyId)
                ->where('role', $roleId)
                ->count();
            
            $stats[$roleId] = [
                'name' => $roleName,
                'count' => $count,
                'bouncer_role' => self::ROLE_MAPPING[$roleId] ?? 'user',
            ];
        }

        return $stats;
    }
}