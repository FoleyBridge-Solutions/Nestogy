<?php

namespace App\Livewire\Client\Concerns;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractMilestone;

trait HasDashboardContracts
{
    protected function getContracts()
    {
        if (! $this->client) {
            return collect();
        }

        return Contract::where('client_id', $this->client->id)
            ->with(['contractMilestones', 'signatures', 'invoices'])
            ->get();
    }

    protected function getContractStats(): array
    {
        $contracts = $this->getContracts();

        return [
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'pending_signatures' => $contracts->flatMap->signatures->where('status', 'pending')->count(),
            'overdue_milestones' => $contracts->flatMap->contractMilestones
                ->where('due_date', '<', now())
                ->where('status', '!=', 'completed')
                ->count(),
            'total_contract_value' => $contracts->sum('contract_value'),
        ];
    }

    protected function getUpcomingMilestones(): array
    {
        if (! $this->canViewContracts() || ! $this->client) {
            return [];
        }

        return ContractMilestone::whereHas('contract', function ($query) {
            $query->where('client_id', $this->client->id);
        })
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->toArray();
    }
}
