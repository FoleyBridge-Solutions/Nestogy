<?php

namespace App\Livewire\Financial;

use App\Domains\Financial\Models\PlaidItem;
use App\Domains\Financial\Services\PlaidService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BankConnectionManager extends Component
{
    public $items;
    public $showDeleteModal = false;
    public $itemToDelete = null;
    public $linkToken = null;
    public $isLoading = false;

    protected $listeners = ['refreshConnections' => '$refresh'];

    public function mount()
    {
        $this->loadConnections();
    }

    public function loadConnections()
    {
        $this->items = PlaidItem::where('company_id', Auth::user()->company_id)
            ->with(['accounts', 'bankTransactions'])
            ->withCount(['bankTransactions', 'bankTransactions as unreconciled_count' => function ($query) {
                $query->where('is_reconciled', false)->where('is_ignored', false);
            }])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function initiateConnection()
    {
        $this->isLoading = true;

        try {
            $plaidService = app(PlaidService::class);
            $result = $plaidService->createLinkToken(Auth::user()->company_id);
            
            $this->linkToken = $result['link_token'];
            $this->dispatch('openPlaidLink', linkToken: $this->linkToken);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to initialize bank connection: ' . $e->getMessage());
            $this->isLoading = false;
        }
    }

    public function syncConnection($itemId)
    {
        try {
            $item = PlaidItem::findOrFail($itemId);
            
            if ($item->company_id !== Auth::user()->company_id) {
                throw new \Exception('Unauthorized');
            }

            $plaidService = app(PlaidService::class);
            $count = $plaidService->syncTransactions($item);
            $plaidService->updateBalances($item);

            session()->flash('success', "Synced {$count} transactions successfully!");
            $this->loadConnections();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to sync connection: ' . $e->getMessage());
        }
    }

    public function confirmDelete($itemId)
    {
        $this->itemToDelete = $itemId;
        $this->showDeleteModal = true;
    }

    public function deleteConnection()
    {
        try {
            $item = PlaidItem::findOrFail($this->itemToDelete);
            
            if ($item->company_id !== Auth::user()->company_id) {
                throw new \Exception('Unauthorized');
            }

            $plaidService = app(PlaidService::class);
            $plaidService->removeItem($item);

            session()->flash('success', 'Bank connection removed successfully!');
            $this->showDeleteModal = false;
            $this->itemToDelete = null;
            $this->loadConnections();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove connection: ' . $e->getMessage());
        }
    }

    public function reauthorize($itemId)
    {
        $this->isLoading = true;

        try {
            $plaidService = app(PlaidService::class);
            $result = $plaidService->createLinkToken(Auth::user()->company_id);
            
            $this->linkToken = $result['link_token'];
            $this->dispatch('openPlaidLink', linkToken: $this->linkToken, update: true, itemId: $itemId);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to initialize reauthorization: ' . $e->getMessage());
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.financial.bank-connection-manager');
    }
}
