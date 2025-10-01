<?php

namespace App\Livewire\Tickets;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TicketCreate extends Component
{
    public $client_id;
    public $contact_ids = [];
    public $subject = '';
    public $priority = 'Medium';
    public $assigned_to;
    public $asset_id;
    public $details = '';
    public $status = 'new';

    public function mount()
    {
        $selectedClient = session('selected_client_id');
        if ($selectedClient) {
            $this->client_id = $selectedClient;
        }
    }

    public function updatedClientId()
    {
        $this->contact_ids = [];
    }

    public function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'assigned_to' => 'nullable|exists:users,id',
            'asset_id' => 'nullable|exists:assets,id',
            'details' => 'required|string',
            'status' => 'required|string',
        ];
    }

    public function save()
    {
        $this->validate();

        $ticket = \App\Domains\Ticket\Models\Ticket::create([
            'client_id' => $this->client_id,
            'contact_id' => !empty($this->contact_ids) ? $this->contact_ids[0] : null,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'assigned_to' => $this->assigned_to,
            'asset_id' => $this->asset_id,
            'details' => $this->details,
            'status' => $this->status,
            'company_id' => Auth::user()->company_id,
            'created_by' => Auth::id(),
        ]);

        session()->flash('success', 'Ticket created successfully.');
        
        return redirect()->route('tickets.show', $ticket->id);
    }

    public function render()
    {
        $clients = Client::where('company_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $contacts = [];
        if ($this->client_id) {
            $contacts = Contact::where('client_id', $this->client_id)
                ->orderBy('name')
                ->get();
        }

        $assets = [];
        if ($this->client_id) {
            $assets = Asset::where('client_id', $this->client_id)
                ->orderBy('name')
                ->get();
        }

        $assignees = User::where('company_id', Auth::user()->company_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $selectedClient = $this->client_id ? Client::find($this->client_id) : null;

        return view('livewire.tickets.ticket-create', [
            'clients' => $clients,
            'contacts' => $contacts,
            'assets' => $assets,
            'assignees' => $assignees,
            'selectedClient' => $selectedClient,
        ]);
    }
}
