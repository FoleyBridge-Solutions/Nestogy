<?php

namespace App\Policies;

use App\Domains\Marketing\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarketingCampaignPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any campaigns.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-campaigns') || $user->can('manage-campaigns');
    }

    /**
     * Determine whether the user can view the campaign.
     */
    public function view(User $user, MarketingCampaign $campaign): bool
    {
        // Must belong to same company
        if ($user->company_id !== $campaign->company_id) {
            return false;
        }

        return $user->can('view-campaigns') ||
               $user->can('manage-campaigns') ||
               $campaign->created_by_user_id === $user->id;
    }

    /**
     * Determine whether the user can create campaigns.
     */
    public function create(User $user): bool
    {
        return $user->can('create-campaigns') || $user->can('manage-campaigns');
    }

    /**
     * Determine whether the user can update the campaign.
     */
    public function update(User $user, MarketingCampaign $campaign): bool
    {
        // Must belong to same company
        if ($user->company_id !== $campaign->company_id) {
            return false;
        }

        // Can't edit campaigns that are active unless you have manage permission
        if ($campaign->status === MarketingCampaign::STATUS_ACTIVE && ! $user->can('manage-campaigns')) {
            return false;
        }

        return $user->can('edit-campaigns') ||
               $user->can('manage-campaigns') ||
               $campaign->created_by_user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the campaign.
     */
    public function delete(User $user, MarketingCampaign $campaign): bool
    {
        // Must belong to same company
        if ($user->company_id !== $campaign->company_id) {
            return false;
        }

        // Can only delete draft campaigns unless you have manage permission
        if ($campaign->status !== MarketingCampaign::STATUS_DRAFT && ! $user->can('manage-campaigns')) {
            return false;
        }

        return $user->can('delete-campaigns') || $user->can('manage-campaigns');
    }

    /**
     * Determine whether the user can start/stop campaigns.
     */
    public function control(User $user, MarketingCampaign $campaign): bool
    {
        // Must belong to same company
        if ($user->company_id !== $campaign->company_id) {
            return false;
        }

        return $user->can('control-campaigns') ||
               $user->can('manage-campaigns') ||
               $campaign->created_by_user_id === $user->id;
    }

    /**
     * Determine whether the user can enroll leads/contacts in campaigns.
     */
    public function enroll(User $user, MarketingCampaign $campaign): bool
    {
        // Must belong to same company
        if ($user->company_id !== $campaign->company_id) {
            return false;
        }

        return $user->can('enroll-campaigns') ||
               $user->can('manage-campaigns') ||
               $campaign->created_by_user_id === $user->id;
    }

    /**
     * Determine whether the user can view campaign analytics.
     */
    public function viewAnalytics(User $user, MarketingCampaign $campaign): bool
    {
        // Must belong to same company
        if ($user->company_id !== $campaign->company_id) {
            return false;
        }

        return $user->can('view-campaign-analytics') ||
               $user->can('manage-campaigns') ||
               $campaign->created_by_user_id === $user->id;
    }

    /**
     * Determine whether the user can clone campaigns.
     */
    public function clone(User $user, MarketingCampaign $campaign): bool
    {
        // Must belong to same company
        if ($user->company_id !== $campaign->company_id) {
            return false;
        }

        return $user->can('create-campaigns') || $user->can('manage-campaigns');
    }

    /**
     * Determine whether the user can send test emails.
     */
    public function testEmail(User $user, MarketingCampaign $campaign): bool
    {
        // Must belong to same company
        if ($user->company_id !== $campaign->company_id) {
            return false;
        }

        return $user->can('test-campaigns') ||
               $user->can('manage-campaigns') ||
               $campaign->created_by_user_id === $user->id;
    }
}
