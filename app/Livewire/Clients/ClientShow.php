<?php

namespace App\Livewire\Clients;

use App\Domains\Client\Services\ClientMetricsService;
use App\Domains\Client\Services\ClientService;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClientShow extends Component
{
    public $client;

    public $stats;

    public $metrics;

    public $recentActivity;

    public $upcomingRenewals;

    // Tab state is managed by Flux tabs component
    public $showCreateContactModal = false;

    public $showCreateLocationModal = false;

    public $showCreateTicketModal = false;

    public $showCreateProjectModal = false;

    protected $listeners = [
        'refreshClient' => 'loadClient',
        'contactCreated' => 'loadClient',
        'locationCreated' => 'loadClient',
        'ticketCreated' => 'loadClient',
        'projectCreated' => 'loadClient',
    ];

    public function mount($clientId)
    {
        $this->client = Client::findOrFail($clientId);

        // Check authorization
        if (! Auth::user()->can('view', $this->client)) {
            abort(403);
        }

        $this->loadClient();
    }

    public function loadClient()
    {
        // Update client access timestamp
        app(ClientService::class)->updateClientAccess($this->client);

        // Load relationships
        $this->client->load([
            'contacts' => function ($query) {
                $query->whereNull('archived_at')->orderBy('primary', 'desc')->orderBy('name');
            },
            'locations' => function ($query) {
                $query->whereNull('archived_at')->orderBy('primary', 'desc')->orderBy('name');
            },
            'assets' => function ($query) {
                $query->whereNull('archived_at')->orderBy('name');
            },
            'tickets' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'invoices' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'projects' => function ($query) {
                $query->whereNull('archived_at')->orderBy('created_at', 'desc')->limit(5);
            },
            'recurringInvoices' => function ($query) {
                $query->where('status', true)->orderBy('next_date');
            },
        ]);

        // Get client statistics
        $this->stats = app(ClientService::class)->getClientStats($this->client);

        // Get calculated metrics
        $this->metrics = app(ClientMetricsService::class)->getMetrics($this->client);

        // Get recent activity
        $this->recentActivity = app(ClientService::class)->getClientActivity($this->client, 20);

        // Get upcoming renewals
        $this->upcomingRenewals = [
            'domains' => collect(),
            'certificates' => collect(),
        ];
    }

    public function createContact()
    {
        $this->showCreateContactModal = true;
    }

    public function createLocation()
    {
        $this->showCreateLocationModal = true;
    }

    public function createTicket()
    {
        $this->showCreateTicketModal = true;
    }

    public function createProject()
    {
        $this->showCreateProjectModal = true;
    }

    public function archiveClient()
    {
        if (! Auth::user()->can('delete', $this->client)) {
            session()->flash('error', 'You are not authorized to archive this client.');

            return;
        }

        $this->client->archived_at = now();
        $this->client->save();

        session()->flash('success', 'Client has been archived.');

        return redirect()->route('clients.index');
    }

    public function deleteClient()
    {
        if (! Auth::user()->can('delete', $this->client)) {
            session()->flash('error', 'You are not authorized to delete this client.');

            return;
        }

        $clientName = $this->client->name;
        $this->client->delete();

        session()->flash('success', "Client '{$clientName}' has been deleted.");

        return redirect()->route('clients.index');
    }

    public function render()
    {
        return view('livewire.clients.client-show');
    }
}
