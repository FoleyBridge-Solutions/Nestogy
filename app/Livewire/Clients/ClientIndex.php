<?php

namespace App\Livewire\Clients;

use App\Domains\Client\Models\Client;
use App\Domains\Core\Services\NavigationService;
use App\Exports\ClientsExport;
use App\Livewire\BaseIndexComponent;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ClientIndex extends BaseIndexComponent
{
    public $type = '';

    public $status = 'active';

    public $showLeads = false;

    public $selectedClient;

    public $showDeleteModal = false;

    public $showConvertModal = false;

    public $clientToDelete = null;

    public $leadToConvert = null;

    public $returnUrl = null;

    public function mount()
    {
        parent::mount();
        $this->selectedClient = NavigationService::getSelectedClient();
        $this->showLeads = request()->has('lead');
        $this->returnUrl = session('client_selection_return_url');

        if (! request()->has('status')) {
            $this->status = 'active';
        }
    }

    protected function getDefaultSort(): array
    {
        return [
            'field' => 'name',
            'direction' => 'asc',
        ];
    }

    protected function getSearchFields(): array
    {
        return [
            'name',
            'email',
            'phone',
            'type',
            'primaryContact.name',
            'primaryContact.email',
        ];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'type' => ['except' => ''],
            'status' => ['except' => 'active'],
            'showLeads' => ['except' => false],
            'perPage' => ['except' => 25],
        ];
    }

    protected function getBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Client::with(['primaryContact', 'primaryLocation', 'tags'])
            ->where('lead', $this->showLeads);
    }

    protected function applyCustomFilters($query)
    {
        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->status && $this->status !== 'all') {
            $query->where('status', $this->status);
        }

        return $query;
    }

    protected function getItems()
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    public function selectClient($clientId)
    {
        $client = Client::find($clientId);
        if ($client && $client->company_id === Auth::user()->company_id) {
            NavigationService::setSelectedClient($clientId);
            $this->selectedClient = $client;

            session()->flash('success', "Selected client: {$client->name}");

            // Dispatch event to refresh other components
            $this->dispatch('client-selected', clientId: $clientId);

            // Check if there's a return URL from the middleware redirect
            if ($returnUrl = session('client_selection_return_url')) {
                session()->forget('client_selection_return_url');

                return redirect($returnUrl)
                    ->with('success', "Selected client: {$client->name}");
            }

            // Otherwise, redirect to refresh the entire page with the new client context
            return redirect()->route('clients.index')
                ->with('success', "Selected client: {$client->name}");
        }
    }

    public function clearSelection()
    {
        NavigationService::clearSelectedClient();
        $this->selectedClient = null;

        // Dispatch event to refresh other components
        $this->dispatch('client-cleared');

        // Redirect to refresh the entire page
        return redirect()->route('clients.index')
            ->with('info', 'Client selection cleared');
    }

    public function confirmDelete($clientId)
    {
        $this->clientToDelete = $clientId;
        $this->showDeleteModal = true;
    }

    public function deleteClient()
    {
        if ($this->clientToDelete) {
            $client = Client::find($this->clientToDelete);
            if ($client && $client->company_id === Auth::user()->company_id) {
                $clientName = $client->name;

                // Clear selection if deleting selected client
                if ($this->selectedClient && $this->selectedClient->id === $client->id) {
                    NavigationService::clearSelectedClient();
                    $this->selectedClient = null;
                }

                $client->delete();
                session()->flash('success', "Client '{$clientName}' has been deleted.");
            }
        }

        $this->showDeleteModal = false;
        $this->clientToDelete = null;
    }

    public function confirmConvert($leadId)
    {
        $this->leadToConvert = $leadId;
        $this->showConvertModal = true;
    }

    public function convertLead()
    {
        if ($this->leadToConvert) {
            $client = Client::find($this->leadToConvert);
            if ($client && $client->company_id === Auth::user()->company_id && $client->lead) {
                $client->lead = false;
                $client->save();

                session()->flash('success', "Lead '{$client->name}' has been converted to a customer.");
            }
        }

        $this->showConvertModal = false;
        $this->leadToConvert = null;
    }

    public function exportCsv()
    {
        $filename = $this->showLeads ? 'leads-export.csv' : 'clients-export.csv';

        return Excel::download(new ClientsExport($this->showLeads), $filename);
    }

    public function render()
    {
        $clients = $this->getItems();

        return view('livewire.clients.client-index', [
            'clients' => $clients,
        ]);
    }
}
