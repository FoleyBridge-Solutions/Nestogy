<?php

namespace App\Domains\Security\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class UserQueryService
{
    public function getAllUsers(array $filters = []): Collection
    {
        $query = User::query();

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

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

    public function getUsersByCompany(int $companyId): Collection
    {
        return User::where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    public function getUsersByRole(string $roleName): Collection
    {
        return User::whereHas('roles', function ($query) use ($roleName) {
            $query->where('name', $roleName);
        })
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

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

    public function canDeleteUser(User $user): bool
    {
        $hasActiveTickets = $user->assignedTickets()->whereNotIn('status', ['closed', 'resolved'])->exists();
        $hasActiveProjects = $user->assignedProjects()->where('status', '!=', 'completed')->exists();

        return ! $hasActiveTickets && ! $hasActiveProjects;
    }
}
