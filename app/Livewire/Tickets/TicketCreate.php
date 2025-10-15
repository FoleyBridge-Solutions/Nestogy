<?php

namespace App\Livewire\Tickets;

use App\Models\Asset;
use App\Domains\Client\Models\Client;
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
            'subject' => 'required|string|min:5|max:255',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'assigned_to' => 'nullable|exists:users,id',
            'asset_id' => 'nullable|exists:assets,id',
            'details' => 'required|string|min:10',
            'status' => 'required|string|in:Open,In Progress,On Hold,Resolved,Closed',
        ];
    }

    public function messages()
    {
        return [
            'client_id.required' => 'Please select a client for this ticket.',
            'subject.required' => 'Please enter a subject for this ticket.',
            'subject.min' => 'Subject must be at least 5 characters.',
            'details.required' => 'Please provide details about the issue.',
            'details.min' => 'Details must be at least 10 characters.',
            'priority.required' => 'Please select a priority level.',
        ];
    }

    public function save()
    {
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Ticket creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'client_id' => $this->client_id,
            ]);
            
            session()->flash('error', 'Failed to create ticket. Please try again or contact support.');
            return null;
        }
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
