<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

/**
 * QuotePolicy
 *
 * Authorization policy for quote management with company-scoped permissions
 * and approval workflow authorization.
 */
class QuotePolicy
{
    /**
     * Determine whether the user can view any quotes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('financial.quotes.view');
    }

    /**
     * Determine whether the user can view the quote.
     */
    public function view(User $user, Quote $quote): bool
    {
        // User can view if they have permission and quote belongs to their company
        return $user->can('financial.quotes.view')
            && $quote->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can create quotes.
     */
    public function create(User $user): bool
    {
        return $user->can('financial.quotes.manage');
    }

    /**
     * Determine whether the user can update the quote.
     */
    public function update(User $user, Quote $quote): bool
    {
        // User can update if they have permission and quote belongs to their company
        if (! $user->can('financial.quotes.manage') || $quote->company_id !== $user->company_id) {
            return false;
        }

        // Additional business rules for updating quotes
        // Only draft quotes or rejected quotes can be edited by regular users
        if (! $quote->isDraft() && $quote->approval_status !== Quote::APPROVAL_REJECTED) {
            // Admins and super-admins can edit non-draft quotes
            return $user->isAn('admin') || $user->isA('super-admin');
        }

        return true;
    }

    /**
     * Determine whether the user can delete the quote.
     */
    public function delete(User $user, Quote $quote): bool
    {
        // User can delete if they have permission and quote belongs to their company
        if (! $user->can('financial.quotes.manage') || $quote->company_id !== $user->company_id) {
            return false;
        }

        // Only draft quotes can be deleted
        if (! $quote->isDraft()) {
            // Admins can delete non-draft quotes
            return $user->isAn('admin') || $user->isA('super-admin');
        }

        return true;
    }

    /**
     * Determine whether the user can restore the quote.
     */
    public function restore(User $user, Quote $quote): bool
    {
        return $user->can('financial.quotes.manage')
            && $quote->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can permanently delete the quote.
     */
    public function forceDelete(User $user, Quote $quote): bool
    {
        return $user->can('financial.quotes.manage')
            && $quote->company_id === $user->company_id
            && ($user->isAn('admin') || $user->isA('super-admin'));
    }

    /**
     * Determine whether the user can approve the quote.
     */
    public function approve(User $user, Quote $quote): bool
    {
        // Must have approval permission and quote must belong to company
        if (! $user->can('financial.quotes.approve') || $quote->company_id !== $user->company_id) {
            return false;
        }

        // Quote must need approval
        if (! $quote->needsApproval()) {
            return false;
        }

        // Check if user can approve based on their role and the quote amount
        return $this->canApproveBasedOnAmount($user, $quote->amount);
    }

    /**
     * Determine whether the user can send the quote.
     */
    public function send(User $user, Quote $quote): bool
    {
        // Must have permission and quote must belong to company
        if (! $user->can('financial.quotes.manage') || $quote->company_id !== $user->company_id) {
            return false;
        }

        // Quote must be approved or not require approval
        return $quote->isFullyApproved() || $quote->approval_status === Quote::APPROVAL_NOT_REQUIRED;
    }

    /**
     * Determine whether the user can convert the quote to invoice.
     */
    public function convert(User $user, Quote $quote): bool
    {
        // Must have both quote and invoice permissions
        if (! $user->can('financial.quotes.manage') ||
            ! $user->can('financial.invoices.manage') ||
            $quote->company_id !== $user->company_id) {
            return false;
        }

        // Quote must be accepted
        return $quote->isAccepted();
    }

    /**
     * Determine whether the user can duplicate the quote.
     */
    public function duplicate(User $user, Quote $quote): bool
    {
        // Can duplicate if can view the original and can create new quotes
        return $this->view($user, $quote) && $this->create($user);
    }

    /**
     * Determine whether the user can create revisions of the quote.
     */
    public function revise(User $user, Quote $quote): bool
    {
        // Must have permission and quote must belong to company
        if (! $user->can('financial.quotes.manage') || $quote->company_id !== $user->company_id) {
            return false;
        }

        // Can revise sent, viewed, declined, or expired quotes
        return in_array($quote->status, [
            Quote::STATUS_SENT,
            Quote::STATUS_VIEWED,
            Quote::STATUS_DECLINED,
            Quote::STATUS_EXPIRED,
        ]);
    }

    /**
     * Determine whether the user can export quotes.
     */
    public function export(User $user): bool
    {
        return $user->can('financial.quotes.export');
    }

    /**
     * Determine whether the user can view quote versions/history.
     */
    public function viewVersions(User $user, Quote $quote): bool
    {
        return $this->view($user, $quote);
    }

    /**
     * Determine whether the user can view quote approvals.
     */
    public function viewApprovals(User $user, Quote $quote): bool
    {
        // Can view if they can view the quote and have approval permission
        return $this->view($user, $quote) && $user->can('financial.quotes.approve');
    }

    /**
     * Determine whether the user can manage quote templates.
     */
    public function manageTemplates(User $user): bool
    {
        return $user->can('financial.quotes.templates');
    }

    /**
     * Determine whether the user can use quote templates.
     */
    public function useTemplates(User $user): bool
    {
        return $user->can('financial.quotes.manage');
    }

    /**
     * Determine whether the user can generate PDFs for the quote.
     */
    public function generatePdf(User $user, Quote $quote): bool
    {
        return $this->view($user, $quote);
    }

    /**
     * Determine whether the user can access quote analytics/reports.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->can('financial.quotes.analytics') ||
               $user->can('reports.financial');
    }

    /**
     * Check if user can approve based on quote amount and their role.
     */
    private function canApproveBasedOnAmount(User $user, float $amount): bool
    {
        // Define approval thresholds
        $managerThreshold = 5000;
        $executiveThreshold = 25000;

        // Admins can approve anything
        if (($user->isAn('admin') || $user->isA('super-admin'))) {
            return true;
        }

        // Executives can approve up to executive threshold
        if ($user->isAn('executive')) {
            return $amount <= $executiveThreshold;
        }

        // Managers can approve up to manager threshold
        if ($user->isAn('manager')) {
            return $amount <= $managerThreshold;
        }

        // Finance users can approve based on their specific permissions
        if ($user->isAn('finance')) {
            return $user->can('financial.quotes.approve.unlimited') ||
                   $amount <= $managerThreshold;
        }

        return false;
    }

    /**
     * Determine if the user can perform manager-level approvals.
     */
    public function approveAsManager(User $user, Quote $quote): bool
    {
        return $this->approve($user, $quote) &&
               ($user->isAn('manager') || $user->isAn('executive') || ($user->isAn('admin') || $user->isA('super-admin')));
    }

    /**
     * Determine if the user can perform executive-level approvals.
     */
    public function approveAsExecutive(User $user, Quote $quote): bool
    {
        return $this->approve($user, $quote) &&
               ($user->isAn('executive') || ($user->isAn('admin') || $user->isA('super-admin')));
    }

    /**
     * Determine if the user can perform finance-level approvals.
     */
    public function approveAsFinance(User $user, Quote $quote): bool
    {
        return $this->approve($user, $quote) &&
               ($user->isAn('finance') || $user->isAn('executive') || ($user->isAn('admin') || $user->isA('super-admin')));
    }

    /**
     * Determine whether the user can manage VoIP configurations.
     */
    public function manageVoipConfig(User $user): bool
    {
        return $user->can('financial.quotes.manage') &&
               $user->can('voip.configuration');
    }

    /**
     * Determine whether the user can access quote workflow features.
     */
    public function manageWorkflow(User $user, Quote $quote): bool
    {
        return $user->can('financial.quotes.workflow') &&
               $quote->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can modify quote status.
     */
    public function changeStatus(User $user, Quote $quote): bool
    {
        if (! $user->can('financial.quotes.manage') || $quote->company_id !== $user->company_id) {
            return false;
        }

        // Admins can change any status
        if (($user->isAn('admin') || $user->isA('super-admin'))) {
            return true;
        }

        // Regular users can only change status of their own quotes in certain states
        return $quote->created_by === $user->id &&
               in_array($quote->status, [Quote::STATUS_DRAFT, Quote::STATUS_SENT, Quote::STATUS_VIEWED]);
    }

    /**
     * Determine whether the user can cancel the quote.
     */
    public function cancel(User $user, Quote $quote): bool
    {
        // Super admins can cancel any quote (except those in final states)
        if ($user->isSuperAdmin()) {
            // Cannot cancel quotes that are already in final states
            if (in_array($quote->status, [Quote::STATUS_CANCELLED, Quote::STATUS_EXPIRED, Quote::STATUS_CONVERTED])) {
                return false;
            }

            // Can cancel quotes that have been sent out (not drafts)
            return in_array($quote->status, [Quote::STATUS_SENT, Quote::STATUS_VIEWED, Quote::STATUS_ACCEPTED, Quote::STATUS_DECLINED]);
        }

        // Must have permission and quote must belong to company
        if (! $user->can('financial.quotes.manage') || $quote->company_id !== $user->company_id) {
            return false;
        }

        // Cannot cancel quotes that are already in final states
        if (in_array($quote->status, [Quote::STATUS_CANCELLED, Quote::STATUS_EXPIRED, Quote::STATUS_CONVERTED])) {
            return false;
        }

        // Can only cancel quotes that have been sent out (not drafts)
        return in_array($quote->status, [Quote::STATUS_SENT, Quote::STATUS_VIEWED, Quote::STATUS_ACCEPTED, Quote::STATUS_DECLINED]);
    }

    /**
     * Determine whether a client contact can view the quote in the client portal.
     */
    public function viewInClientPortal(\App\Models\Contact $contact, Quote $quote): bool
    {
        // Must be an authenticated client contact
        if (! $contact || ! $contact->client) {
            return false;
        }

        // Quote must belong to the client
        if ($quote->client_id !== $contact->client_id) {
            return false;
        }

        // Contact must have portal access
        if (! $contact->has_portal_access || ! $contact->canAccessPortal()) {
            return false;
        }

        // Contact must have quote viewing permissions
        $canView = $contact->isPrimary() ||
                   $contact->isBilling() ||
                   in_array('can_view_quotes', $contact->portal_permissions ?? []);

        if (! $canView) {
            return false;
        }

        // Quote must be in a state where it can be viewed by clients
        // Only allow viewing of quotes that have been sent out (not drafts)
        return in_array($quote->status, [
            Quote::STATUS_SENT,
            Quote::STATUS_VIEWED,
            Quote::STATUS_ACCEPTED,
            Quote::STATUS_DECLINED,
            Quote::STATUS_EXPIRED,
        ]);
    }
}
