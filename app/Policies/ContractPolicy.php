<?php

namespace App\Policies;

use App\Domains\Contract\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contracts.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('contracts.view');
    }

    /**
     * Determine whether the user can view the contract.
     */
    public function view(User $user, Contract $contract): bool
    {
        // User must have permission and contract must belong to their company
        return $user->can('contracts.view') && 
               $contract->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can create contracts.
     */
    public function create(User $user): bool
    {
        return $user->can('contracts.create');
    }

    /**
     * Determine whether the user can update the contract.
     */
    public function update(User $user, Contract $contract): bool
    {
        // User must have permission and contract must belong to their company
        if (!$user->can('contracts.edit') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Additional business rules
        return $this->canModifyContract($user, $contract);
    }

    /**
     * Determine whether the user can delete the contract.
     */
    public function delete(User $user, Contract $contract): bool
    {
        // User must have delete permission and contract must belong to their company
        if (!$user->can('contracts.delete') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Only draft contracts can be deleted
        return $contract->status === Contract::STATUS_DRAFT;
    }

    /**
     * Determine whether the user can restore the contract.
     */
    public function restore(User $user, Contract $contract): bool
    {
        return $user->can('contracts.delete') && 
               $contract->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can permanently delete the contract.
     */
    public function forceDelete(User $user, Contract $contract): bool
    {
        // Only super admins can force delete
        return $user->hasRole('super-admin') && 
               $contract->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can approve the contract.
     */
    public function approve(User $user, Contract $contract): bool
    {
        // User must have approval permission and contract must belong to their company
        if (!$user->can('contracts.approve') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Only pending review contracts can be approved
        return $contract->status === Contract::STATUS_PENDING_REVIEW;
    }

    /**
     * Determine whether the user can send the contract for signature.
     */
    public function sendForSignature(User $user, Contract $contract): bool
    {
        // User must have signature permission and contract must belong to their company
        if (!$user->can('contracts.signature') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Contract must be ready for signature
        return in_array($contract->status, [
            Contract::STATUS_PENDING_SIGNATURE,
            Contract::STATUS_UNDER_NEGOTIATION
        ]);
    }

    /**
     * Determine whether the user can sign the contract.
     */
    public function sign(User $user, Contract $contract): bool
    {
        // User must have signature permission and contract must belong to their company
        if (!$user->can('contracts.signature') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Contract must be pending signature
        return $contract->status === Contract::STATUS_PENDING_SIGNATURE &&
               $contract->signature_status !== Contract::SIGNATURE_FULLY_EXECUTED;
    }

    /**
     * Determine whether the user can activate the contract.
     */
    public function activate(User $user, Contract $contract): bool
    {
        // User must have activation permission and contract must belong to their company
        if (!$user->can('contracts.activate') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Contract must be signed
        return $contract->status === Contract::STATUS_SIGNED &&
               $contract->signature_status === Contract::SIGNATURE_FULLY_EXECUTED;
    }

    /**
     * Determine whether the user can terminate the contract.
     */
    public function terminate(User $user, Contract $contract): bool
    {
        // User must have termination permission and contract must belong to their company
        if (!$user->can('contracts.terminate') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Only active or suspended contracts can be terminated
        return in_array($contract->status, [
            Contract::STATUS_ACTIVE,
            Contract::STATUS_SUSPENDED
        ]);
    }

    /**
     * Determine whether the user can suspend the contract.
     */
    public function suspend(User $user, Contract $contract): bool
    {
        // User must have suspension permission and contract must belong to their company
        if (!$user->can('contracts.suspend') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Only active contracts can be suspended
        return $contract->status === Contract::STATUS_ACTIVE;
    }

    /**
     * Determine whether the user can reactivate the contract.
     */
    public function reactivate(User $user, Contract $contract): bool
    {
        // User must have activation permission and contract must belong to their company
        if (!$user->can('contracts.activate') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Only suspended contracts can be reactivated
        return $contract->status === Contract::STATUS_SUSPENDED;
    }

    /**
     * Determine whether the user can create amendments.
     */
    public function createAmendment(User $user, Contract $contract): bool
    {
        // User must have amendment permission and contract must belong to their company
        if (!$user->can('contracts.amend') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Only active contracts can be amended
        return $contract->status === Contract::STATUS_ACTIVE;
    }

    /**
     * Determine whether the user can renew the contract.
     */
    public function renew(User $user, Contract $contract): bool
    {
        // User must have renewal permission and contract must belong to their company
        if (!$user->can('contracts.renew') || $contract->company_id !== $user->company_id) {
            return false;
        }

        // Contract must be active and eligible for renewal
        return $contract->status === Contract::STATUS_ACTIVE &&
               $contract->isDueForRenewal(90); // Allow renewal within 90 days of expiry
    }

    /**
     * Determine whether the user can generate contract documents.
     */
    public function generateDocument(User $user, Contract $contract): bool
    {
        // User must have view permission and contract must belong to their company
        return $user->can('contracts.view') && 
               $contract->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can view contract financial data.
     */
    public function viewFinancials(User $user, Contract $contract): bool
    {
        // User must have financial permission and contract must belong to their company
        return $user->can('contracts.financials') && 
               $contract->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can export contracts.
     */
    public function export(User $user): bool
    {
        return $user->can('contracts.export');
    }

    /**
     * Determine whether the user can import contracts.
     */
    public function import(User $user): bool
    {
        return $user->can('contracts.import');
    }

    /**
     * Determine whether the user can view contract analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->can('contracts.analytics');
    }

    /**
     * Determine whether the user can manage contract templates.
     */
    public function manageTemplates(User $user): bool
    {
        return $user->can('contract-templates.manage');
    }

    /**
     * Check if contract can be modified based on business rules.
     */
    protected function canModifyContract(User $user, Contract $contract): bool
    {
        // Contracts in certain statuses cannot be modified
        $nonModifiableStatuses = [
            Contract::STATUS_SIGNED,
            Contract::STATUS_ACTIVE,
            Contract::STATUS_TERMINATED,
            Contract::STATUS_EXPIRED,
            Contract::STATUS_CANCELLED,
        ];

        if (in_array($contract->status, $nonModifiableStatuses)) {
            return false;
        }

        // Super admins can modify most contracts (except terminated/cancelled)
        if ($user->hasRole('super-admin')) {
            return !in_array($contract->status, [
                Contract::STATUS_TERMINATED,
                Contract::STATUS_CANCELLED,
            ]);
        }

        // Regular users can only modify draft and pending review contracts
        return in_array($contract->status, [
            Contract::STATUS_DRAFT,
            Contract::STATUS_PENDING_REVIEW,
        ]);
    }

    /**
     * Check if user can perform bulk operations.
     */
    public function bulkActions(User $user): bool
    {
        return $user->can('contracts.bulk-actions');
    }

    /**
     * Check if user can access contract history/audit logs.
     */
    public function viewHistory(User $user, Contract $contract): bool
    {
        return $user->can('contracts.history') && 
               $contract->company_id === $user->company_id;
    }

    /**
     * Check if user can manage contract milestones.
     */
    public function manageMilestones(User $user, Contract $contract): bool
    {
        return $user->can('contracts.milestones') && 
               $contract->company_id === $user->company_id &&
               in_array($contract->status, [
                   Contract::STATUS_ACTIVE,
                   Contract::STATUS_SIGNED,
               ]);
    }
}