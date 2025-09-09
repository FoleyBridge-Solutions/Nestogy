<?php

namespace App\Policies;

use App\Domains\Client\Models\ClientITDocumentation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClientITDocumentationPolicy
{
    /**
     * Determine whether the user can view any IT documentation.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('clients.documents.view') || 
               $user->isAn(['admin', 'technician']);
    }

    /**
     * Determine whether the user can view the IT documentation.
     */
    public function view(User $user, ClientITDocumentation $documentation): bool
    {
        // Check company isolation
        if ($user->company_id !== $documentation->company_id) {
            return false;
        }

        // Check access level permissions
        return $this->canAccessLevel($user, $documentation->access_level);
    }

    /**
     * Determine whether the user can create IT documentation.
     */
    public function create(User $user): bool
    {
        return $user->can('clients.documents.manage') || 
               $user->isAn(['admin', 'technician']);
    }

    /**
     * Determine whether the user can update the IT documentation.
     */
    public function update(User $user, ClientITDocumentation $documentation): bool
    {
        // Check company isolation
        if ($user->company_id !== $documentation->company_id) {
            return false;
        }

        // Admins can always update
        if ($user->hasRole('admin')) {
            return true;
        }

        // Authors can update their own documentation unless it's admin_only
        if ($documentation->authored_by === $user->id && $documentation->access_level !== 'admin_only') {
            return true;
        }

        // Technicians can update if they have specific permission and access level allows
        if ($user->can('clients.documents.manage')) {
            return $this->canAccessLevel($user, $documentation->access_level);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the IT documentation.
     */
    public function delete(User $user, ClientITDocumentation $documentation): bool
    {
        // Check company isolation
        if ($user->company_id !== $documentation->company_id) {
            return false;
        }

        // Only admins can delete, or authors of their own docs
        return $user->hasRole('admin') || 
               ($documentation->authored_by === $user->id && $user->can('clients.documents.manage'));
    }

    /**
     * Determine whether the user can restore the IT documentation.
     */
    public function restore(User $user, ClientITDocumentation $documentation): bool
    {
        // Check company isolation
        if ($user->company_id !== $documentation->company_id) {
            return false;
        }

        // Only admins can restore
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the IT documentation.
     */
    public function forceDelete(User $user, ClientITDocumentation $documentation): bool
    {
        // Check company isolation
        if ($user->company_id !== $documentation->company_id) {
            return false;
        }

        // Only admins can force delete
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can download attached files.
     */
    public function download(User $user, ClientITDocumentation $documentation): bool
    {
        return $this->view($user, $documentation);
    }

    /**
     * Determine whether the user can create new versions.
     */
    public function createVersion(User $user, ClientITDocumentation $documentation): bool
    {
        return $this->update($user, $documentation);
    }

    /**
     * Determine whether the user can duplicate documentation.
     */
    public function duplicate(User $user, ClientITDocumentation $documentation): bool
    {
        // Must be able to view the source and create new documentation
        return $this->view($user, $documentation) && $this->create($user);
    }

    /**
     * Determine whether the user can export documentation.
     */
    public function export(User $user): bool
    {
        return $user->can('clients.documents.export') || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkUpdate(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can complete reviews.
     */
    public function completeReview(User $user, ClientITDocumentation $documentation): bool
    {
        return $this->update($user, $documentation);
    }

    /**
     * Check if user can access documentation based on access level.
     */
    protected function canAccessLevel(User $user, string $accessLevel): bool
    {
        return match($accessLevel) {
            'public' => true,
            'confidential' => $user->isAn(['admin', 'technician']) || 
                            $user->can('clients.documents.view'),
            'restricted' => $user->hasRole('admin') || 
                          $user->can('clients.documents.manage'),
            'admin_only' => $user->hasRole('admin'),
            default => false,
        };
    }
}