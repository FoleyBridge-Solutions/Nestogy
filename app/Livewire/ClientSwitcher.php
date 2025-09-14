<?php

namespace App\Livewire;

use App\Models\Client;
use App\Services\ClientFavoriteService;
use App\Services\NavigationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ClientSwitcher extends Component
{
    // Core properties - minimal state
    public $searchQuery = '';

    #[Locked]
    public $selectedClientId = null;

    public $selectedIndex = -1;

    // Protected properties initialized in boot()
    protected ClientFavoriteService $favoriteService;

    protected $user;

    /**
     * Mount - runs once on initial component load
     */
    public function mount()
    {
        // Initialize selected client from session once
        if ($client = NavigationService::getSelectedClient()) {
            $this->selectedClientId = $client->id;
        }
    }

    /**
     * Boot - runs at the beginning of every request
     * Perfect for initializing services and non-persisted properties
     */
    public function boot(ClientFavoriteService $favoriteService)
    {
        $this->favoriteService = $favoriteService;
        $this->user = Auth::user();
    }

    /**
     * Hydrate - runs at the beginning of subsequent requests
     * Use for re-initializing state after component is re-hydrated
     */
    public function hydrate()
    {
        // Ensure selected client is still valid after hydration
        if ($this->selectedClientId && $this->user) {
            $client = Client::find($this->selectedClientId);
            if (! $client || $client->company_id !== $this->user->company_id) {
                $this->selectedClientId = null;
                NavigationService::clearSelectedClient();
            }
        }
    }

    /**
     * Current client - computed property with caching
     */
    #[Computed(persist: true, seconds: 300)]
    public function currentClient()
    {
        if (! $this->selectedClientId) {
            return null;
        }

        return Client::where('id', $this->selectedClientId)
            ->where('company_id', $this->user?->company_id)
            ->withCount(['tickets', 'invoices'])
            ->first();
    }

    /**
     * Favorite clients - computed with short cache
     */
    #[Computed(cache: true, key: 'client-switcher-favorites')]
    public function favoriteClients()
    {
        if (! $this->user) {
            return collect();
        }

        return $this->favoriteService->getFavoriteClients($this->user, 5);
    }

    /**
     * Recent clients - computed with session-based cache
     */
    #[Computed]
    public function recentClients()
    {
        if (! $this->user) {
            return collect();
        }

        // Get recent clients from the service (uses accessed_at field)
        $recentClients = $this->favoriteService->getRecentClients($this->user, 5);
        
        // If no recent clients, show the first 5 active clients as a fallback
        if ($recentClients->isEmpty() && $this->favoriteClients->isEmpty()) {
            return Client::where('company_id', $this->user->company_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'company_name', 'email', 'status']);
        }
        
        // Filter out favorites from recent to avoid duplication
        $favoriteIds = $this->favoriteClients->pluck('id')->toArray();
        return $recentClients->filter(function ($client) use ($favoriteIds) {
            return !in_array($client->id, $favoriteIds);
        });
    }

    /**
     * Search results - computed, no cache needed
     */
    #[Computed]
    public function searchResults()
    {
        if (! $this->user || strlen($this->searchQuery) < 2) {
            return collect();
        }

        $excludeIds = $this->favoriteClients->pluck('id')
            ->merge($this->recentClients->pluck('id'))
            ->unique()
            ->values()
            ->toArray();

        return Client::where('company_id', $this->user->company_id)
            ->where('status', 'active')
            ->when($excludeIds, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->where(function ($q) {
                $searchTerm = '%'.$this->searchQuery.'%';
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('company_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm);
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'company_name', 'email', 'status']);
    }

    /**
     * Updated hook for search query changes
     */
    public function updatedSearchQuery()
    {
        // Reset navigation when search changes
        $this->selectedIndex = -1;

        // Clear computed cache if needed
        if (empty($this->searchQuery)) {
            unset($this->searchResults);
        }
    }

    /**
     * Updating hook to validate property changes
     */
    public function updating($property, $value)
    {
        if ($property === 'selectedIndex') {
            // Ensure selectedIndex stays within bounds
            $maxIndex = $this->getMaxNavigationIndex();
            if ($value > $maxIndex) {
                $this->selectedIndex = $maxIndex;
            } elseif ($value < -1) {
                $this->selectedIndex = -1;
            }
        }
    }

    /**
     * Select a client
     */
    public function selectClient($clientId)
    {
        // Validate client exists and user has access
        $client = Client::where('id', $clientId)
            ->where('company_id', $this->user->company_id)
            ->first();

        if (! $client) {
            $this->addError('client', 'Client not found or access denied.');

            return;
        }

        // Update session and component state
        NavigationService::setSelectedClient($client->id);
        $this->selectedClientId = $client->id;

        // Mark as accessed for recent tracking
        $this->favoriteService->markAsAccessed($client);
        NavigationService::addToRecentClients($client->id);

        // Clear search
        $this->reset('searchQuery', 'selectedIndex');

        // Bust computed caches to reflect new selection
        unset($this->currentClient);
        unset($this->recentClients);

        // Dispatch event
        $this->dispatch('client-selected', clientId: $client->id);

        // Flash message
        session()->flash('message', "Switched to {$client->name}");

        // Navigate
        return $this->redirectRoute('clients.index', [], navigate: true);
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite($clientId = null)
    {
        $clientId = $clientId ?? $this->selectedClientId;

        if (! $clientId) {
            return;
        }

        $client = Client::find($clientId);
        if (! $client || $client->company_id !== $this->user->company_id) {
            return;
        }

        $isFavorite = $this->favoriteService->toggle($this->user, $client);

        // Bust the favorites cache
        unset($this->favoriteClients);
        Cache::forget('client-switcher-favorites');

        // Flash message
        $action = $isFavorite ? 'added to' : 'removed from';
        session()->flash('message', "{$client->name} {$action} favorites");
    }

    /**
     * Clear client selection
     */
    public function clearSelection()
    {
        NavigationService::clearSelectedClient();
        $this->selectedClientId = null;

        // Bust current client cache
        unset($this->currentClient);

        $this->dispatch('client-cleared');

        return $this->redirectRoute('clients.index', navigate: true);
    }

    /**
     * Keyboard navigation
     */
    public function navigateDown()
    {
        $this->selectedIndex++;
        // The updating() hook will ensure it stays in bounds
    }

    public function navigateUp()
    {
        $this->selectedIndex--;
        // The updating() hook will ensure it stays in bounds
    }

    public function selectHighlighted()
    {
        if ($this->selectedIndex < 0) {
            return;
        }

        $client = $this->getClientByIndex($this->selectedIndex);
        if ($client) {
            $this->selectClient($client->id);
        }
    }

    /**
     * Handle keyboard shortcut for favorite selection
     */
    public function selectFavoriteByNumber($number)
    {
        $client = $this->favoriteClients->get($number - 1);
        if ($client) {
            $this->selectClient($client->id);
        }
    }

    /**
     * Event listener for external client changes
     */
    #[On('client-changed')]
    public function handleClientChange($clientId)
    {
        $this->selectedClientId = $clientId;
        unset($this->currentClient);
        unset($this->recentClients);
    }

    /**
     * Event listener for client selection from index page
     */
    #[On('client-selected')]
    public function handleClientSelected($clientId)
    {
        $this->selectedClientId = $clientId;
        unset($this->currentClient);
        unset($this->recentClients);
    }

    /**
     * Event listener for client clearing from index page
     */
    #[On('client-cleared')]
    public function handleClientCleared()
    {
        $this->selectedClientId = null;
        unset($this->currentClient);
        unset($this->recentClients);
    }

    /**
     * Dehydrate - runs at the end of every request
     */
    public function dehydrate()
    {
        // Clean up any temporary data before sending to client
        // Don't send unnecessary data to the browser
        unset($this->favoriteService);
        unset($this->user);
    }

    /**
     * Exception handling
     */
    public function exception($e, $stopPropagation)
    {
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            session()->flash('error', 'Client not found');
            $this->reset('searchQuery', 'selectedIndex');
            $stopPropagation();
        }
    }

    /**
     * Helper methods
     */
    protected function getMaxNavigationIndex()
    {
        if ($this->searchQuery) {
            return $this->searchResults->count() - 1;
        }

        return $this->favoriteClients->count() + $this->recentClients->count() - 1;
    }

    protected function getClientByIndex($index)
    {
        if ($this->searchQuery) {
            return $this->searchResults->get($index);
        }

        $allClients = $this->favoriteClients->concat($this->recentClients);

        return $allClients->get($index);
    }

    /**
     * Check if client is favorited
     */
    public function isClientFavorite($clientId)
    {
        if (! $this->user || ! $clientId) {
            return false;
        }

        $client = Client::find($clientId);
        if (! $client) {
            return false;
        }

        return $this->favoriteService->isFavorite($this->user, $client);
    }

    /**
     * Get client initials for display
     */
    public function getClientInitials($client)
    {
        if (! $client || ! $client->name) {
            return '?';
        }

        return collect(explode(' ', $client->name))
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->implode('');
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.client-switcher');
    }
}
