<?php

namespace App\Domains\Core\Controllers\Traits;

trait UsesSelectedClient
{
    /**
     * Get the currently selected client from session
     */
    protected function getSelectedClient()
    {
        return \App\Domains\Core\Services\NavigationService::getSelectedClient();
    }

    /**
     * Apply client filter to query if client is selected
     */
    protected function applyClientFilter($query)
    {
        $selectedClient = $this->getSelectedClient();
        if ($selectedClient) {
            $query->where('client_id', $selectedClient->id);
        }

        return $query;
    }

    /**
     * Add client_id to filters array if client is selected
     */
    protected function addClientToFilters(array $filters): array
    {
        $selectedClient = $this->getSelectedClient();
        if ($selectedClient) {
            $filters['client_id'] = $selectedClient->id;
        }

        return $filters;
    }

    /**
     * Get selected client ID or null
     */
    protected function getSelectedClientId(): ?int
    {
        $client = $this->getSelectedClient();

        return $client?->id;
    }
}
