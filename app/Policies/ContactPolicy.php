<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContactPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins can always view contacts
        if ($user->isAdmin()) {
            return true;
        }
        
        return $user->can('clients.contacts.view') && $this->sameCompany($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contact $contact): bool
    {
        // Admins can always view contacts from their company
        if ($user->isAdmin() && $this->sameCompany($user, $contact)) {
            return true;
        }
        
        return $user->can('clients.contacts.view') && $this->sameCompany($user, $contact);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admins can always create contacts
        if ($user->isAdmin()) {
            return true;
        }
        
        return $user->can('clients.contacts.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contact $contact): bool
    {
        // Admins can always update contacts from their company
        if ($user->isAdmin() && $this->sameCompany($user, $contact)) {
            return true;
        }
        
        return $user->can('clients.contacts.manage') && $this->sameCompany($user, $contact);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contact $contact): bool
    {
        // Admins can always delete contacts from their company
        if ($user->isAdmin() && $this->sameCompany($user, $contact)) {
            return true;
        }
        
        return $user->can('clients.contacts.manage') && $this->sameCompany($user, $contact);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contact $contact): bool
    {
        // Admins can always restore contacts from their company
        if ($user->isAdmin() && $this->sameCompany($user, $contact)) {
            return true;
        }
        
        return $user->can('clients.contacts.manage') && $this->sameCompany($user, $contact);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contact $contact): bool
    {
        // Admins can always force delete contacts from their company
        if ($user->isAdmin() && $this->sameCompany($user, $contact)) {
            return true;
        }
        
        return $user->can('clients.contacts.manage') && $this->sameCompany($user, $contact);
    }

    /**
     * Determine whether the user can export contacts.
     */
    public function export(User $user): bool
    {
        // Admins can always export contacts
        if ($user->isAdmin()) {
            return true;
        }
        
        return $user->can('clients.contacts.export');
    }

    /**
     * Check if user and model belong to same company.
     */
    private function sameCompany(User $user, ?Contact $contact = null): bool
    {
        if (!$contact) {
            return true; // For general operations without specific contact
        }
        
        return $user->company_id === $contact->company_id;
    }
}