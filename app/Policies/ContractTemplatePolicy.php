<?php

namespace App\Policies;

use App\Domains\Contract\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contract templates.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('contract-templates.manage') || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the contract template.
     */
    public function view(User $user, ContractTemplate $template): bool
    {
        // User must have permission and template must belong to their company
        return ($user->can('contract-templates.manage') || $user->isAdmin()) && 
               $template->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can create contract templates.
     */
    public function create(User $user): bool
    {
        return $user->can('contract-templates.manage') || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the contract template.
     */
    public function update(User $user, ContractTemplate $template): bool
    {
        // User must have permission and template must belong to their company
        return ($user->can('contract-templates.manage') || $user->isAdmin()) && 
               $template->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can delete the contract template.
     */
    public function delete(User $user, ContractTemplate $template): bool
    {
        // User must have permission and template must belong to their company
        return ($user->can('contract-templates.manage') || $user->isAdmin()) && 
               $template->company_id === $user->company_id &&
               !$template->is_default; // Cannot delete default templates
    }

    /**
     * Determine whether the user can restore the contract template.
     */
    public function restore(User $user, ContractTemplate $template): bool
    {
        return $user->can('contract-templates.manage') && 
               $template->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can permanently delete the contract template.
     */
    public function forceDelete(User $user, ContractTemplate $template): bool
    {
        // Only super admins can force delete
        return $user->hasRole('super-admin') && 
               $template->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can duplicate the contract template.
     */
    public function duplicate(User $user, ContractTemplate $template): bool
    {
        return $user->can('contract-templates.manage') && 
               $template->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can export contract templates.
     */
    public function export(User $user): bool
    {
        return $user->can('contract-templates.export') || $user->can('contract-templates.manage');
    }

    /**
     * Determine whether the user can import contract templates.
     */
    public function import(User $user): bool
    {
        return $user->can('contract-templates.import') || $user->can('contract-templates.manage');
    }
}