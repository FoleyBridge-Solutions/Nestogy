<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('clients.view') && $this->sameCompany($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.view') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('clients.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.edit') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.delete') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.manage') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.manage') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.edit') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can export clients.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('clients.export');
    }

    /**
     * Determine whether the user can import clients.
     */
    public function import(User $user): bool
    {
        return $user->hasPermission('clients.import');
    }

    /**
     * Determine whether the user can manage client tags.
     */
    public function manageTags(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.edit') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can convert lead to customer.
     */
    public function convertLead(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.edit') && $this->sameCompany($user, $client) && $client->lead === true;
    }

    /**
     * Determine whether the user can view client financial information.
     */
    public function viewFinancial(User $user, Client $client): bool
    {
        return $user->hasAnyPermission(['clients.manage', 'financial.view']) && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can manage client contacts.
     */
    public function manageContacts(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.contacts.manage') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can view client contacts.
     */
    public function viewContacts(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.contacts.view') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can export client contacts.
     */
    public function exportContacts(User $user): bool
    {
        return $user->hasPermission('clients.contacts.export');
    }

    /**
     * Determine whether the user can manage client locations.
     */
    public function manageLocations(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.locations.manage') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can view client locations.
     */
    public function viewLocations(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.locations.view') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can export client locations.
     */
    public function exportLocations(User $user): bool
    {
        return $user->hasPermission('clients.locations.export');
    }

    /**
     * Determine whether the user can manage client documents.
     */
    public function manageDocuments(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.documents.manage') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can view client documents.
     */
    public function viewDocuments(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.documents.view') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can export client documents.
     */
    public function exportDocuments(User $user): bool
    {
        return $user->hasPermission('clients.documents.export');
    }

    /**
     * Determine whether the user can manage client files.
     */
    public function manageFiles(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.files.manage') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can view client files.
     */
    public function viewFiles(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.files.view') && $this->sameCompany($user, $client);
    }

    /**
     * Determine whether the user can export client files.
     */
    public function exportFiles(User $user): bool
    {
        return $user->hasPermission('clients.files.export');
    }

    /**
     * Check if user and model belong to same company.
     */
    private function sameCompany(User $user, ?Client $client = null): bool
    {
        if (!$client) {
            return true; // For general operations without specific client
        }
        
        return $user->company_id === $client->company_id;
    }
}