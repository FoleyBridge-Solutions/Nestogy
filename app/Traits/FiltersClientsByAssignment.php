<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for filtering resources by assigned clients
 * 
 * This trait ensures that technicians only see resources for clients they're assigned to
 */
trait FiltersClientsByAssignment
{
    /**
     * Apply client assignment filtering based on user assignments
     * 
     * Client assignments work as RESTRICTIONS:
     * - No assignments = access to all clients in company
     * - Has assignments = access only to assigned clients
     */
    protected function applyClientAssignmentFilter($query, ?User $user = null)
    {
        $user = $user ?? Auth::user();
        
        // Super admins see everything
        if ($user->isA('super-admin')) {
            return $query;
        }
        
        // Admins see all clients in their company
        if ($user->isA('admin')) {
            return $query->where('company_id', $user->company_id);
        }
        
        // For other users (technicians, etc.), check if they have client restrictions
        $assignedClientIds = $user->assignedClients()
            ->pluck('client_id')
            ->toArray();
        
        if (empty($assignedClientIds)) {
            // No assignments = access to all clients in their company
            return $query->where('company_id', $user->company_id);
        }
        
        // Has assignments = restricted to only those clients
        return $query->where('company_id', $user->company_id)
                    ->whereIn('id', $assignedClientIds);
    }
    
    /**
     * Get accessible client IDs for the current user
     * 
     * Client assignments work as RESTRICTIONS:
     * - No assignments = access to all clients in company
     * - Has assignments = access only to assigned clients
     */
    protected function getAccessibleClientIds(): array
    {
        $user = Auth::user();
        
        // Super admins see all clients
        if ($user->isA('super-admin')) {
            return \App\Models\Client::pluck('id')->toArray();
        }
        
        // Admins see all clients in their company
        if ($user->isA('admin')) {
            return \App\Models\Client::where('company_id', $user->company_id)
                ->pluck('id')
                ->toArray();
        }
        
        // For other users (technicians, etc.), check if they have client restrictions
        $assignedClientIds = $user->assignedClients()->pluck('client_id')->toArray();
        
        if (empty($assignedClientIds)) {
            // No assignments = access to all clients in their company
            return \App\Models\Client::where('company_id', $user->company_id)
                ->pluck('id')
                ->toArray();
        }
        
        // Has assignments = restricted to only those clients
        return $assignedClientIds;
    }
    
    /**
     * Check if user can access a specific client
     * 
     * Client assignments work as RESTRICTIONS:
     * - No assignments = access to all clients in company
     * - Has assignments = access only to assigned clients
     */
    protected function canAccessClient($clientId): bool
    {
        $user = Auth::user();
        
        // Super admins can access everything
        if ($user->isA('super-admin')) {
            return true;
        }
        
        // Admins can access all clients in their company
        if ($user->isA('admin')) {
            $client = \App\Models\Client::find($clientId);
            return $client && $client->company_id === $user->company_id;
        }
        
        // For other users, check restrictions
        $assignedClientIds = $user->assignedClients()->pluck('client_id')->toArray();
        
        if (empty($assignedClientIds)) {
            // No assignments = can access all clients in company
            $client = \App\Models\Client::find($clientId);
            return $client && $client->company_id === $user->company_id;
        }
        
        // Has assignments = can only access assigned clients
        return in_array($clientId, $assignedClientIds);
    }
    
    /**
     * Get clients accessible to the current user
     */
    protected function getAccessibleClients()
    {
        return Auth::user()->accessibleClients();
    }
}