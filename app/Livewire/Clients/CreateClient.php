<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateClient extends Component
{
    use WithFileUploads;

    // Tab management
    public $currentTab = 'basic';

    public function setTab($tab)
    {
        $this->currentTab = $tab;
    }

    public function nextTab()
    {
        $tabs = ['basic', 'address', 'billing', 'additional'];
        $currentIndex = array_search($this->currentTab, $tabs);

        if ($currentIndex !== false && $currentIndex < count($tabs) - 1) {
            $this->currentTab = $tabs[$currentIndex + 1];
        }
    }

    public function previousTab()
    {
        $tabs = ['basic', 'address', 'billing', 'additional'];
        $currentIndex = array_search($this->currentTab, $tabs);

        if ($currentIndex !== false && $currentIndex > 0) {
            $this->currentTab = $tabs[$currentIndex - 1];
        }
    }

    // Form fields
    public $isLead = false;

    public $type = 'individual';

    public $name = '';

    public $company = '';

    public $email = '';

    public $phone = '';

    public $website = '';

    public $tax_id_number = '';

    public $referral = '';

    // Address fields
    public $address = '';

    public $city = '';

    public $state = '';

    public $zip_code = '';

    public $country = '';

    // Billing fields
    public $status = 'active';

    public $hourly_rate = '';

    public $currency_code = 'USD';

    public $net_terms = 30;

    // Additional fields
    public $notes = '';

    public $tags = [];

    // Available tags
    public $availableTags = [];

    protected $rules = [
        'name' => 'required|min:2|max:255',
        'email' => 'required|email|unique:clients,email',
        'type' => 'required|in:individual,business',
        'company' => 'nullable|max:255',
        'phone' => 'nullable|max:50',
        'website' => 'nullable|url|max:255',
        'tax_id_number' => 'nullable|max:50',
        'referral' => 'nullable|max:255',
        'address' => 'nullable|max:255',
        'city' => 'nullable|max:100',
        'state' => 'nullable|max:100',
        'zip_code' => 'nullable|max:20',
        'country' => 'nullable|max:2',
        'status' => 'required|in:active,inactive,suspended',
        'hourly_rate' => 'nullable|numeric|min:0',
        'currency_code' => 'required|in:USD,EUR,GBP,CAD,AUD',
        'net_terms' => 'nullable|numeric|min:0',
        'notes' => 'nullable|max:5000',
    ];

    protected $messages = [
        'name.required' => 'The client name is required.',
        'email.required' => 'The email address is required.',
        'email.email' => 'Please enter a valid email address.',
        'email.unique' => 'This email address is already registered.',
        'website.url' => 'Please enter a valid URL starting with http:// or https://',
    ];

    public function mount()
    {
        $this->isLead = request()->has('lead');
        $this->availableTags = Tag::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();
    }

    public function updatedType($value)
    {
        // Clear company field if switching to business type
        if ($value === 'business') {
            $this->company = '';
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        DB::transaction(function () {
            $client = new Client;

            // Set basic fields
            $client->company_id = auth()->user()->company_id;
            $client->created_by = auth()->id();
            $client->lead = $this->isLead;
            $client->type = $this->type;
            $client->name = $this->name;
            $client->company_name = $this->type === 'business' ? $this->name : $this->company;
            $client->email = $this->email;
            $client->phone = $this->phone;
            $client->website = $this->website;
            $client->tax_id_number = $this->tax_id_number;
            $client->referral = $this->referral;

            // Set address fields
            $client->address = $this->address;
            $client->city = $this->city;
            $client->state = $this->state;
            $client->zip_code = $this->zip_code;
            $client->country = $this->country;

            // Set billing fields
            $client->status = $this->status;
            $client->hourly_rate = $this->hourly_rate ?: null;
            $client->currency_code = $this->currency_code;
            $client->net_terms = $this->net_terms;

            // Set additional fields
            $client->notes = $this->notes;

            $client->save();

            // Attach tags
            if (! empty($this->tags)) {
                $client->tags()->sync($this->tags);
            }

            // Create default location
            $client->locations()->create([
                'name' => 'Main Office',
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'zip_code' => $this->zip_code,
                'country' => $this->country,
                'is_primary' => true,
            ]);
        });

        session()->flash('success', ($this->isLead ? 'Lead' : 'Client').' created successfully.');

        return redirect()->route('clients.index', $this->isLead ? ['lead' => 1] : []);
    }

    public function cancel()
    {
        return redirect()->route('clients.index', $this->isLead ? ['lead' => 1] : []);
    }

    public function render()
    {
        return view('livewire.clients.create-client');
    }
}
