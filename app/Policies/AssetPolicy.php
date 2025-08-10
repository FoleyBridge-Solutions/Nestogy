<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AssetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('assets.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.view') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('assets.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.edit') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.delete') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.manage') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.manage') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can export assets.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('assets.export');
    }

    /**
     * Determine whether the user can import assets.
     */
    public function import(User $user): bool
    {
        return $user->hasPermission('assets.manage');
    }

    /**
     * Determine whether the user can manage asset maintenance.
     */
    public function manageMaintenance(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.maintenance.manage') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can view asset maintenance.
     */
    public function viewMaintenance(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.maintenance.view') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can export asset maintenance.
     */
    public function exportMaintenance(User $user): bool
    {
        return $user->hasPermission('assets.maintenance.export');
    }

    /**
     * Determine whether the user can manage asset warranties.
     */
    public function manageWarranties(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.warranties.manage') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can view asset warranties.
     */
    public function viewWarranties(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.warranties.view') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can export asset warranties.
     */
    public function exportWarranties(User $user): bool
    {
        return $user->hasPermission('assets.warranties.export');
    }

    /**
     * Determine whether the user can manage asset depreciation.
     */
    public function manageDepreciation(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.depreciations.manage') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can view asset depreciation.
     */
    public function viewDepreciation(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.depreciations.view') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can export asset depreciation.
     */
    public function exportDepreciation(User $user): bool
    {
        return $user->hasPermission('assets.depreciations.export');
    }

    /**
     * Determine whether the user can check assets in/out.
     */
    public function checkInOut(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.edit') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can generate QR codes for assets.
     */
    public function generateQrCode(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.view') && $this->sameCompany($user, $asset);
    }

    /**
     * Determine whether the user can print asset labels.
     */
    public function printLabel(User $user, Asset $asset): bool
    {
        return $user->hasPermission('assets.view') && $this->sameCompany($user, $asset);
    }

    /**
     * Check if user and asset belong to same company.
     */
    private function sameCompany(User $user, Asset $asset): bool
    {
        return $user->company_id === $asset->company_id;
    }
}