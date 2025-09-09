<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Client;
use App\Domains\Client\Services\ClientService;

class ClientActivityTimeline extends Component
{
    use WithPagination;

    public Client $client;
    public $perPage = 10;
    public $activities = [];
    public $hasMore = true;
    public $page = 1;

    protected $clientService;

    public function mount(Client $client)
    {
        $this->client = $client;
        $this->loadActivities();
    }

    public function loadMore()
    {
        $this->page++;
        $this->loadActivities(true);
    }

    protected function loadActivities($append = false)
    {
        $clientService = app(ClientService::class);
        $limit = $this->page * $this->perPage;
        
        $allActivities = $clientService->getClientActivity($this->client, $limit);
        
        if ($append) {
            $newActivities = $allActivities->slice(($this->page - 1) * $this->perPage, $this->perPage);
            $this->activities = array_merge($this->activities, $newActivities->toArray());
        } else {
            $this->activities = $allActivities->take($this->perPage)->toArray();
        }
        
        // Check if there are more activities to load
        $this->hasMore = $allActivities->count() === $limit;
    }

    public function render()
    {
        return view('livewire.client-activity-timeline');
    }
}