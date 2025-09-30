<?php

namespace App\Domains\Security\Services;

use App\Domains\Product\Services\SubscriptionService;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
    protected $roleService;

    protected $subscriptionService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
        // Subscription service is optional to avoid circular dependency
        $this->subscriptionService = app(SubscriptionService::class);
    }

    /**
     * Get all users with optional filters
     */
    public function getAllUsers(array $filters = []): Collection
    {
        $query = User::query();

        // Apply company filter if provided
        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Apply role filter if provided
        if (! empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Apply status filter if provided
        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        // Apply search filter if provided
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
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

            // Handle role assignment - support both legacy 'role' field and new 'roles' array
            $roleToAssign = null;

            if (! empty($data['role'])) {
                // Legacy numeric role from forms (1-4)
                $roleToAssign = (int) $data['role'];
            } elseif (! empty($data['roles'])) {
                // Array of role names from API/other sources
                $roleToAssign = is_array($data['roles']) ? $data['roles'][0] : $data['roles'];
            }

            // Assign role using RoleService if specified
            if ($roleToAssign !== null) {
                $this->roleService->assignRole($user, $roleToAssign, $user->company_id);
            }

            DB::commit();

            // Update subscription user count after successful creation
            if ($this->subscriptionService) {
                $this->subscriptionService->handleUserCreated($user);
            }

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

            // Handle role update - support both legacy 'role' field and new 'roles' array
            $roleToAssign = null;

            if (isset($data['role'])) {
                // Legacy numeric role from forms (1-4)
                $roleToAssign = (int) $data['role'];
            } elseif (isset($data['roles'])) {
                // Array of role names from API/other sources
                $roleToAssign = is_array($data['roles']) ? $data['roles'][0] : $data['roles'];
            }

            // Update role using RoleService if specified
            if ($roleToAssign !== null) {
                $this->roleService->assignRole($user, $roleToAssign, $user->company_id);
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
        return User::whereHas('roles', function ($query) use ($roleName) {
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
            ->where(function ($q) use ($query) {
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
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'manager', 'technician', 'support']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * Archive a user (soft delete)
     */
    public function archiveUser(User $user): bool
    {
        $result = $user->delete(); // This triggers soft delete via archived_at

        // Update subscription user count after archiving
        if ($result && $this->subscriptionService) {
            $this->subscriptionService->handleUserDeleted($user);
        }

        return $result;
    }

    /**
     * Restore an archived user
     */
    public function restoreUser(User $user): bool
    {
        $result = $user->restore();

        // Update subscription user count after restoring
        if ($result && $this->subscriptionService) {
            $this->subscriptionService->handleUserCreated($user);
        }

        return $result;
    }

    /**
     * Permanently delete a user
     */
    public function deleteUser(User $user): bool
    {
        // Update subscription count before deletion
        if ($this->subscriptionService) {
            $this->subscriptionService->handleUserDeleted($user);
        }

        return $user->forceDelete();
    }

    /**
     * Update user status (active/inactive)
     */
    public function updateUserStatus(User $user, bool $status): User
    {
        $oldStatus = $user->status;
        $user->update(['status' => $status]);

        // Update subscription count if status changed
        if ($oldStatus != $status && $this->subscriptionService) {
            if ($status) {
                $this->subscriptionService->handleUserCreated($user);
            } else {
                $this->subscriptionService->handleUserDeleted($user);
            }
        }

        return $user;
    }

    /**
     * Update user password
     */
    public function updateUserPassword(User $user, string $password): User
    {
        $user->update(['password' => Hash::make($password)]);

        return $user;
    }

    /**
     * Update user role
     */
    public function updateUserRole(User $user, $role): User
    {
        $this->roleService->assignRole($user, $role, $user->company_id);

        return $user->fresh();
    }

    /**
     * Update user profile
     */
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

        return ! $hasActiveTickets && ! $hasActiveProjects;
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

    /**
     * Update user profile information
     */
    public function updateUserProfile(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            // Filter only profile-related fields
            $profileData = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ], function ($value) {
                return $value !== null;
            });

            // Handle avatar upload if provided
            if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                // Delete old avatar if exists
                if ($user->avatar) {
                    Storage::disk(config('filesystems.default'))->delete('users/'.$user->avatar);
                }

                // Store new avatar
                $avatarPath = $data['avatar']->store('users', config('filesystems.default'));
                $profileData['avatar'] = basename($avatarPath);
            }

            $user->update($profileData);

            DB::commit();

            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update user settings
     */
    public function updateUserSettings(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            // Update user-level settings
            $userSettings = array_filter([
                'timezone' => $data['timezone'] ?? null,
                'date_format' => $data['date_format'] ?? null,
                'time_format' => $data['time_format'] ?? null,
            ], function ($value) {
                return $value !== null;
            });

            if (! empty($userSettings)) {
                $user->update($userSettings);
            }

            // Update UserSetting model if it exists
            if ($user->userSetting) {
                $settingsData = array_filter([
                    'force_mfa' => isset($data['force_mfa']) ? (bool) $data['force_mfa'] : null,
                    'records_per_page' => isset($data['records_per_page']) ? (int) $data['records_per_page'] : null,
                    'dashboard_financial_enable' => isset($data['dashboard_financial_enable']) ? (bool) $data['dashboard_financial_enable'] : null,
                    'dashboard_technical_enable' => isset($data['dashboard_technical_enable']) ? (bool) $data['dashboard_technical_enable'] : null,
                    'theme' => isset($data['theme']) ? $data['theme'] : null,
                ], function ($value) {
                    return $value !== null;
                });

                if (! empty($settingsData)) {
                    $user->userSetting->update($settingsData);
                }
            }

            DB::commit();

            return $user->fresh(['userSetting']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
