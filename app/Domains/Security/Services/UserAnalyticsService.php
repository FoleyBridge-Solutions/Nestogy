<?php

namespace App\Domains\Security\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserAnalyticsService
{
    public function getUserStatistics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('active', true)->count();
        $inactiveUsers = User::where('active', false)->count();
        $recentUsers = User::where('created_at', '>=', now()->subDays(30))->count();

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

    public function bulkUpdateUsers(array $userIds, array $data): int
    {
        $validFields = ['active', 'company_id'];
        $updateData = array_intersect_key($data, array_flip($validFields));

        if (empty($updateData)) {
            return 0;
        }

        return User::whereIn('id', $userIds)->update($updateData);
    }
}
