<?php

namespace App\Policies;

use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use Illuminate\Auth\Access\Response;

class EmployeeTimeEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hr.time-entries.view') || $user->isAdmin();
    }

    public function view(User $user, EmployeeTimeEntry $employeeTimeEntry): bool
    {
        return ($user->can('hr.time-entries.view') || $user->id === $employeeTimeEntry->user_id || $user->isAdmin())
            && $this->sameCompany($user, $employeeTimeEntry);
    }

    public function create(User $user): bool
    {
        return $user->can('hr.time-entries.create') || $user->isAdmin();
    }

    public function update(User $user, EmployeeTimeEntry $employeeTimeEntry): bool
    {
        if ($employeeTimeEntry->exported_to_payroll) {
            return false;
        }

        return ($user->can('hr.time-entries.edit') || $user->id === $employeeTimeEntry->user_id || $user->isAdmin())
            && $this->sameCompany($user, $employeeTimeEntry);
    }

    public function delete(User $user, EmployeeTimeEntry $employeeTimeEntry): bool
    {
        if ($employeeTimeEntry->exported_to_payroll) {
            return false;
        }

        return ($user->can('hr.time-entries.delete') || $user->isAdmin())
            && $this->sameCompany($user, $employeeTimeEntry);
    }

    public function approve(User $user, EmployeeTimeEntry $employeeTimeEntry): bool
    {
        return ($user->can('hr.time-entries.approve') || $user->isAdmin())
            && $this->sameCompany($user, $employeeTimeEntry);
    }

    public function restore(User $user, EmployeeTimeEntry $employeeTimeEntry): bool
    {
        return $user->can('hr.time-entries.manage') || $user->isAdmin();
    }

    public function forceDelete(User $user, EmployeeTimeEntry $employeeTimeEntry): bool
    {
        return $user->can('hr.time-entries.manage') || $user->isAdmin();
    }

    private function sameCompany(User $user, EmployeeTimeEntry $employeeTimeEntry): bool
    {
        return $user->company_id === $employeeTimeEntry->company_id;
    }
}
