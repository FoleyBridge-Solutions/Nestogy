<?php

namespace App\Livewire\Client\Concerns;

trait HasDashboardPermissions
{
    protected function canViewContracts(): bool
    {
        return $this->contact->isPrimary() ||
               $this->contact->isBilling() ||
               in_array('can_view_contracts', $this->contact->portal_permissions ?? []);
    }

    protected function canViewInvoices(): bool
    {
        return $this->contact->isPrimary() ||
               $this->contact->isBilling() ||
               in_array('can_view_invoices', $this->contact->portal_permissions ?? []);
    }

    protected function canViewTickets(): bool
    {
        return $this->contact->isPrimary() ||
               $this->contact->isTechnical() ||
               in_array('can_view_tickets', $this->contact->portal_permissions ?? []);
    }

    protected function canViewAssets(): bool
    {
        return $this->contact->isPrimary() ||
               $this->contact->isTechnical() ||
               in_array('can_view_assets', $this->contact->portal_permissions ?? []);
    }

    protected function canViewProjects(): bool
    {
        return $this->contact->isPrimary() ||
               in_array('can_view_projects', $this->contact->portal_permissions ?? []);
    }
}
