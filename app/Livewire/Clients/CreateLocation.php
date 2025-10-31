<?php

namespace App\Livewire\Clients;

use App\Domains\Client\Models\Location;
use App\Domains\Client\Models\Contact;
use App\Domains\Core\Services\NavigationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateLocation extends Component
{
    public $name = '';
    public $description = '';
    public $address_line_1 = '';
    public $address_line_2 = '';
    public $city = '';
    public $state = '';
    public $zip_code = '';
    public $country = 'US';
    public $phone = '';
    public $primary = false;
    public $contact_id = '';
    public $contacts = [];
    public $client;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'address_line_1' => 'required|string|max:255',
        'address_line_2' => 'nullable|string|max:255',
        'city' => 'required|string|max:255',
        'state' => 'nullable|string|max:100',
        'zip_code' => 'nullable|string|max:20',
        'country' => 'required|string|max:100',
        'phone' => 'nullable|string|max:50',
        'primary' => 'boolean',
        'contact_id' => 'nullable|exists:client_contacts,id',
    ];

    public function mount()
    {
        $this->client = NavigationService::getSelectedClient();
        $this->contacts = $this->client ? Contact::where('client_id', $this->client->id)->orderBy('name')->get() : collect();
    }

    public function save()
    {
        $this->validate();
        $data = $this->only(array_keys($this->rules));

        // Address composition
        $data['address'] = $this->address_line_1 . ($this->address_line_2 ? ', '.$this->address_line_2 : '');
        $data['is_primary'] = $this->primary;

        DB::transaction(function () use ($data) {
            if ($data['is_primary']) {
                $this->client->locations()->update(['is_primary' => false]);
            }
            $location = $this->client->locations()->create([
                'name' => $data['name'],
                'description' => $data['description'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip_code'],
                'country' => $data['country'],
                'phone' => $data['phone'],
                'is_primary' => $data['is_primary'],
                'contact_id' => $data['contact_id'],
            ]);
        });

        session()->flash('success', 'Location created successfully.');
        $this->emit('locationCreated');
        return redirect()->route('clients.locations.index', $this->client);
    }

    public function render()
    {
        return view('livewire.clients.create-location', [
            'client' => $this->client,
            'contacts' => $this->contacts,
        ]);
    }
}
