<?php

namespace App\Policies;

use App\Domains\Lead\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any leads.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-leads') || $user->can('manage-leads');
    }

    /**
     * Determine whether the user can view the lead.
     */
    public function view(User $user, Lead $lead): bool
    {
        // Must belong to same company
        if ($user->company_id !== $lead->company_id) {
            return false;
        }

        return $user->can('view-leads') || 
               $user->can('manage-leads') ||
               $lead->assigned_user_id === $user->id;
    }

    /**
     * Determine whether the user can create leads.
     */
    public function create(User $user): bool
    {
        return $user->can('create-leads') || $user->can('manage-leads');
    }

    /**
     * Determine whether the user can update the lead.
     */
    public function update(User $user, Lead $lead): bool
    {
        // Must belong to same company
        if ($user->company_id !== $lead->company_id) {
            return false;
        }

        return $user->can('edit-leads') || 
               $user->can('manage-leads') ||
               $lead->assigned_user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the lead.
     */
    public function delete(User $user, Lead $lead): bool
    {
        // Must belong to same company
        if ($user->company_id !== $lead->company_id) {
            return false;
        }

        return $user->can('delete-leads') || $user->can('manage-leads');
    }

    /**
     * Determine whether the user can convert leads to clients.
     */
    public function convert(User $user, Lead $lead): bool
    {
        // Must belong to same company
        if ($user->company_id !== $lead->company_id) {
            return false;
        }

        return $user->can('convert-leads') || 
               $user->can('manage-leads') ||
               $lead->assigned_user_id === $user->id;
    }

    /**
     * Determine whether the user can assign leads to others.
     */
    public function assign(User $user, Lead $lead): bool
    {
        // Must belong to same company
        if ($user->company_id !== $lead->company_id) {
            return false;
        }

        return $user->can('assign-leads') || $user->can('manage-leads');
    }

    /**
     * Determine whether the user can update lead scores.
     */
    public function updateScore(User $user, Lead $lead): bool
    {
        // Must belong to same company
        if ($user->company_id !== $lead->company_id) {
            return false;
        }

        return $user->can('manage-leads') || 
               $user->can('score-leads') ||
               $lead->assigned_user_id === $user->id;
    }

    /**
     * Determine whether the user can perform bulk actions on leads.
     */
    public function bulkAction(User $user): bool
    {
        return $user->can('bulk-edit-leads') || $user->can('manage-leads');
    }
}