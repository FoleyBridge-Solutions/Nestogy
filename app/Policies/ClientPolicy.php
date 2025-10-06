<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Perform pre-authorization checks.
     * Super admins have unrestricted access.
     * Admins and technicians must pass through individual policy methods for company checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Only super admins get automatic bypass
        // Everyone else (including admins) must pass through policy methods
        // so company checks are enforced
        if ($user->isA('super-admin')) {
            return true;
        }

        return null; // Fall through to specific permission checks
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all clients in their company
        if ($user->isA('admin')) {
            return true;
        }
        
        return $user->can('clients.view') && $this->sameCompany($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        // Check same company first - deny if different company
        if (!$this->sameCompany($user, $client)) {
            return false;
        }

        // Admins have full access to all clients in their company
        if ($user->isA('admin')) {
            return true;
        }

        // For technicians - check if they have client restrictions
        if ($user->isA('technician')) {
            // If technician has NO client assignments, they can access all clients
            if ($user->assignedClients()->count() === 0) {
                return $user->can('clients.*') || $user->can('clients.view');
            }

            // If technician has client assignments, only allow access to assigned clients
            if (!$user->isAssignedToClient($client->id)) {
                return false;
            }
        }

        // Check for wildcard permission or specific view permission
        if ($user->can('clients.*') || $user->can('clients.view')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admins can create clients
        if ($user->isA('admin')) {
            return true;
        }
        
        return $user->can('clients.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        // Check same company first - deny if different company
        if (!$this->sameCompany($user, $client)) {
            return false;
        }

        // Admins have full access to all clients in their company
        if ($user->isA('admin')) {
            return true;
        }

        // For technicians - check if they have client restrictions
        if ($user->isA('technician')) {
            // If technician has NO client assignments, they can access all clients
            if ($user->assignedClients()->count() === 0) {
                return $user->can('clients.edit');
            }

            // If technician has client assignments, only allow access to assigned clients
            if (!$user->isAssignedToClient($client->id)) {
                return false;
            }
        }

        return $user->can('clients.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.manage');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.manage');
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.edit');
    }

    /**
     * Determine whether the user can export clients.
     */
    public function export(User $user): bool
    {
        if ($user->isA('admin')) {
            return true;
        }
        
        return $user->can('clients.export');
    }

    /**
     * Determine whether the user can import clients.
     */
    public function import(User $user): bool
    {
        if ($user->isA('admin')) {
            return true;
        }
        
        return $user->can('clients.import');
    }

    /**
     * Determine whether the user can manage client tags.
     */
    public function manageTags(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.edit');
    }

    /**
     * Determine whether the user can convert lead to customer.
     */
    public function convertLead(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        if (!$client->lead) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.edit');
    }

    /**
     * Determine whether the user can view client financial information.
     */
    public function viewFinancial(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.manage') || $user->can('financial.view');
    }

    /**
     * Determine whether the user can manage client contacts.
     */
    public function manageContacts(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.contacts.manage');
    }

    /**
     * Determine whether the user can view client contacts.
     */
    public function viewContacts(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.contacts.view');
    }

    /**
     * Determine whether the user can export client contacts.
     */
    public function exportContacts(User $user): bool
    {
        return $user->can('clients.contacts.export');
    }

    /**
     * Determine whether the user can manage client locations.
     */
    public function manageLocations(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.locations.manage');
    }

    /**
     * Determine whether the user can view client locations.
     */
    public function viewLocations(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.locations.view');
    }

    /**
     * Determine whether the user can export client locations.
     */
    public function exportLocations(User $user): bool
    {
        return $user->can('clients.locations.export');
    }

    /**
     * Determine whether the user can manage client documents.
     */
    public function manageDocuments(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.documents.manage');
    }

    /**
     * Determine whether the user can view client documents.
     */
    public function viewDocuments(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.documents.view');
    }

    /**
     * Determine whether the user can export client documents.
     */
    public function exportDocuments(User $user): bool
    {
        return $user->can('clients.documents.export');
    }

    /**
     * Determine whether the user can manage client files.
     */
    public function manageFiles(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.files.manage');
    }

    /**
     * Determine whether the user can view client files.
     */
    public function viewFiles(User $user, Client $client): bool
    {
        if (!$this->sameCompany($user, $client)) {
            return false;
        }
        
        return $user->isA('admin') || $user->can('clients.files.view');
    }

    /**
     * Determine whether the user can export client files.
     */
    public function exportFiles(User $user): bool
    {
        return $user->can('clients.files.export');
    }

    /**
     * Check if user and model belong to same company.
     */
    private function sameCompany(User $user, ?Client $client = null): bool
    {
        if (! $client) {
            return true; // For general operations without specific client
        }

        return $user->company_id === $client->company_id;
    }
}
