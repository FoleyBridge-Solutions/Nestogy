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
        // All authenticated users can view clients list
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        // User can view client if they belong to the same company
        return $user->company_id === $client->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create clients
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        // User can update client if they belong to the same company
        return $user->company_id === $client->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        // User can delete client if they belong to the same company
        // and have appropriate role (admin or manager)
        return $user->company_id === $client->company_id
            && $this->userHasRole($user, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        // User can restore client if they belong to the same company
        // and have appropriate role (admin or manager)
        return $user->company_id === $client->company_id
            && $this->userHasRole($user, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        // Only admins can permanently delete clients
        return $user->company_id === $client->company_id
            && $this->userHasRole($user, ['admin']);
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function archive(User $user, Client $client): bool
    {
        // User can archive client if they belong to the same company
        return $user->company_id === $client->company_id;
    }

    /**
     * Determine whether the user can export clients.
     */
    public function export(User $user): bool
    {
        // All authenticated users can export their company's clients
        return true;
    }

    /**
     * Determine whether the user can import clients.
     */
    public function import(User $user): bool
    {
        // Only admins and managers can import clients
        return $this->userHasRole($user, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can manage client tags.
     */
    public function manageTags(User $user, Client $client): bool
    {
        // User can manage tags if they belong to the same company
        return $user->company_id === $client->company_id;
    }

    /**
     * Determine whether the user can convert lead to customer.
     */
    public function convertLead(User $user, Client $client): bool
    {
        // User can convert lead if they belong to the same company
        // and the client is actually a lead
        return $user->company_id === $client->company_id 
            && $client->lead === true;
    }

    /**
     * Determine whether the user can view client financial information.
     */
    public function viewFinancial(User $user, Client $client): bool
    {
        // User can view financial info if they belong to the same company
        // and have appropriate role or permission
        return $user->company_id === $client->company_id
            && ($this->userHasRole($user, ['admin', 'manager', 'accountant'])
                || $this->userHasPermission($user, 'view_client_financial'));
    }

    /**
     * Determine whether the user can manage client locations.
     */
    public function manageLocations(User $user, Client $client): bool
    {
        // User can manage locations if they belong to the same company
        return $user->company_id === $client->company_id;
    }

    /**
     * Determine whether the user can manage client contacts.
     */
    public function manageContacts(User $user, Client $client): bool
    {
        // User can manage contacts if they belong to the same company
        return $user->company_id === $client->company_id;
    }

    /**
     * Check if user has any of the given roles
     */
    private function userHasRole(User $user, array $roles): bool
    {
        // Check if user has the hasRole method (from a role package)
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($roles);
        }

        // Fallback to checking user_role in user_settings
        $userRole = $user->userSetting->role ?? null;
        
        $roleMap = [
            1 => 'accountant',
            2 => 'technician',
            3 => 'admin'
        ];

        $userRoleName = $roleMap[$userRole] ?? null;
        
        return in_array($userRoleName, $roles);
    }

    /**
     * Check if user has a specific permission
     */
    private function userHasPermission(User $user, string $permission): bool
    {
        // Check if user has the hasPermission method (from a permission package)
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        // Fallback logic based on role
        $userRole = $user->userSetting->role ?? null;
        
        // Admin (role 3) has all permissions
        if ($userRole === 3) {
            return true;
        }

        // Define permission mappings for other roles
        $permissions = [
            'view_client_financial' => [1, 3], // Accountant and Admin
        ];

        return isset($permissions[$permission]) 
            && in_array($userRole, $permissions[$permission]);
    }
}