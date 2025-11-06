<?php

namespace App\Livewire\Clients;

use App\Domains\Client\Models\Contact;
use App\Domains\Core\Services\NavigationService;
use App\Livewire\BaseIndexComponent;
use Illuminate\Support\Facades\Auth;

class ContactIndex extends BaseIndexComponent
{
    public $clientId;

    public function mount()
    {
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Please select a client first.');
        }

        $this->clientId = $client->id;
        parent::mount();
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
            'mobile',
            'department',
            'title',
        ];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'perPage' => ['except' => 25],
        ];
    }

    protected function getBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Contact::where('client_id', $this->clientId);
    }

    protected function getColumns(): array
    {
        return [
            'name' => [
                'label' => 'Name',
                'sortable' => true,
                'filterable' => false,
            ],
            'email' => [
                'label' => 'Email',
                'sortable' => true,
                'filterable' => false,
            ],
            'phone' => [
                'label' => 'Phone',
                'sortable' => true,
                'filterable' => false,
            ],
            'mobile' => [
                'label' => 'Mobile',
                'sortable' => true,
                'filterable' => false,
            ],
            'department' => [
                'label' => 'Department',
                'sortable' => true,
                'filterable' => false,
            ],
            'title' => [
                'label' => 'Title',
                'sortable' => true,
                'filterable' => false,
            ],
            'primary' => [
                'label' => 'Primary',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    '1' => 'Yes',
                    '0' => 'No',
                ],
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        $client = app(NavigationService::class)->getSelectedClient();

        return [
            'icon' => 'user-group',
            'title' => 'No Contacts',
            'message' => 'Get started by adding your first contact for ' . ($client->name ?? 'this client'),
            'action' => route('clients.contacts.create'),
            'actionLabel' => 'Add First Contact',
        ];
    }

    protected function getRowActions($item): array
    {
        return [
            [
                'label' => 'View',
                'icon' => 'eye',
                'href' => route('clients.contacts.show', $item),
            ],
            [
                'label' => 'Edit',
                'icon' => 'pencil',
                'href' => route('clients.contacts.edit', $item),
            ],
            [
                'label' => 'Delete',
                'icon' => 'trash',
                'variant' => 'danger',
                'wire:click' => "confirmDelete({$item->id})",
                'wire:confirm' => 'Are you sure you want to delete this contact?',
            ],
        ];
    }

    public function confirmDelete($contactId)
    {
        $contact = Contact::find($contactId);

        if ($contact && $contact->client_id === $this->clientId && $contact->company_id === $this->companyId) {
            $contact->delete();
            session()->flash('success', "Contact '{$contact->name}' has been deleted.");
        }
    }
}
