<?php

namespace App\Domains\HR\Policies;

use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;

class EmployeeTimeEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-hr') || true;
    }

    public function view(User $user, EmployeeTimeEntry $entry): bool
    {
        return $user->id === $entry->user_id || $user->can('manage-hr');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-hr');
    }

    public function update(User $user, EmployeeTimeEntry $entry): bool
    {
        return $user->can('manage-hr');
    }

    public function delete(User $user, EmployeeTimeEntry $entry): bool
    {
        return $user->can('manage-hr');
    }

    public function approve(User $user, EmployeeTimeEntry $entry): bool
    {
        return $user->can('manage-hr') && $user->id !== $entry->user_id;
    }

    public function export(User $user): bool
    {
        return $user->can('manage-hr');
    }
}
