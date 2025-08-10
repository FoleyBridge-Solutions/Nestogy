<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class UserService
{
    /**
     * Get all users with optional filters
     */
    public function getAllUsers(array $filters = []): Collection
    {
        $query = User::query();

        // Apply company filter if provided
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Apply role filter if provided
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Apply status filter if provided
        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        // Apply search filter if provided
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        DB::beginTransaction();
        
        try {
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Set default values
            $data['active'] = $data['active'] ?? true;
            $data['email_verified_at'] = $data['email_verified_at'] ?? now();

            $user = User::create($data);

            // Assign roles if provided
            if (!empty($data['roles'])) {
                $user->assignRole($data['roles']);
            }

            DB::commit();
            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update an existing user
     */
    public function updateUser(User $user, array $data): User
    {
        DB::beginTransaction();
        
        try {
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            // Update roles if provided
            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            DB::commit();
            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Deactivate a user
     */
    public function deactivateUser(User $user): User
    {
        $user->update(['active' => false]);
        return $user;
    }

    /**
     * Activate a user
     */
    public function activateUser(User $user): User
    {
        $user->update(['active' => true]);
        return $user;
    }

    /**
     * Get users by company
     */
    public function getUsersByCompany(int $companyId): Collection
    {
        return User::where('company_id', $companyId)
                  ->where('active', true)
                  ->orderBy('name')
                  ->get();
    }

    /**
     * Get users with specific role
     */
    public function getUsersByRole(string $roleName): Collection
    {
        return User::whereHas('roles', function($query) use ($roleName) {
                      $query->where('name', $roleName);
                  })
                  ->where('active', true)
                  ->orderBy('name')
                  ->get();
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('active', true)->count();
        $inactiveUsers = User::where('active', false)->count();
        $recentUsers = User::where('created_at', '>=', now()->subDays(30))->count();

        // Users by role
        $usersByRole = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                          ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                          ->where('model_has_roles.model_type', User::class)
                          ->select('roles.name', DB::raw('count(*) as count'))
                          ->groupBy('roles.name')
                          ->pluck('count', 'name')
                          ->toArray();

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'recent_users' => $recentUsers,
            'users_by_role' => $usersByRole,
        ];
    }

    /**
     * Search users
     */
    public function searchUsers(string $query, int $limit = 10): Collection
    {
        return User::where('active', true)
                  ->where(function($q) use ($query) {
                      $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
                  })
                  ->limit($limit)
                  ->orderBy('name')
                  ->get();
    }

    /**
     * Get assignable users for a company
     */
    public function getAssignableUsers(int $companyId): Collection
    {
        return User::where('company_id', $companyId)
                  ->where('active', true)
                  ->whereHas('roles', function($query) {
                      $query->whereIn('name', ['admin', 'manager', 'technician', 'support']);
                  })
                  ->orderBy('name')
                  ->get(['id', 'name', 'email']);
    }

    /**
     * Update user last login
     */
    public function updateLastLogin(User $user): void
    {
        $user->update(['last_login_at' => now()]);
    }

    /**
     * Check if user can be deleted
     */
    public function canDeleteUser(User $user): bool
    {
        // Check if user has any active assignments
        $hasActiveTickets = $user->assignedTickets()->whereNotIn('status', ['closed', 'resolved'])->exists();
        $hasActiveProjects = $user->assignedProjects()->where('status', '!=', 'completed')->exists();
        
        return !$hasActiveTickets && !$hasActiveProjects;
    }

    /**
     * Get user permissions summary
     */
    public function getUserPermissions(User $user): array
    {
        $roles = $user->getRoleNames()->toArray();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        
        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
        ];
    }

    /**
     * Bulk update users
     */
    public function bulkUpdateUsers(array $userIds, array $data): int
    {
        $validFields = ['active', 'company_id'];
        $updateData = array_intersect_key($data, array_flip($validFields));
        
        if (empty($updateData)) {
            return 0;
        }

        return User::whereIn('id', $userIds)->update($updateData);
    }

    /**
     * Get user activity summary
     */
    public function getUserActivity(User $user, int $days = 30): array
    {
        $since = now()->subDays($days);
        
        return [
            'tickets_created' => $user->createdTickets()->where('created_at', '>=', $since)->count(),
            'tickets_assigned' => $user->assignedTickets()->where('created_at', '>=', $since)->count(),
            'projects_created' => $user->createdProjects()->where('created_at', '>=', $since)->count(),
            'projects_assigned' => $user->assignedProjects()->where('created_at', '>=', $since)->count(),
            'last_login' => $user->last_login_at,
        ];
    }
}