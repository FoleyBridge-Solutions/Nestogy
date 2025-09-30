<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;

class ClientNotes extends Component
{
    public Client $client;

    public string $notes = '';

    public bool $saving = false;

    public bool $saved = false;

    public function mount(Client $client)
    {
        $this->client = $client;
        $this->notes = $client->notes ?? '';
    }

    public function updatedNotes()
    {
        $this->saving = true;
        $this->saved = false;

        $this->client->update([
            'notes' => $this->notes,
        ]);

        $this->saving = false;
        $this->saved = true;

        $this->dispatch('notes-saved');
    }

    public function render()
    {
        return view('livewire.client-notes');
    }
}
