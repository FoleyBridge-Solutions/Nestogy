<div class="space-y-6">
    <!-- Selected Client Card -->
    @if($selectedClient)
        <flux:card class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <flux:icon name="check-circle" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    <div>
                        <flux:text class="font-medium">Currently selected: {{ $selectedClient->name }}</flux:text>
                        @if($selectedClient->company_name)
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">{{ $selectedClient->company_name }}</flux:text>
                        @endif
                    </div>
                </div>
                <flux:button 
                    wire:click="clearSelection"
                    variant="ghost"
                    size="sm"
                    class="text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100"
                >
                    Clear selection
                </flux:button>
            </div>
        </flux:card>
    @endif

    <!-- Header -->
    <flux:card>
        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">Clients</flux:heading>
                    <flux:text class="mt-1 text-gray-600 dark:text-gray-400">
                        Manage your clients and their information
                    </flux:text>
                </div>
                <div class="flex items-center gap-3">
                    <flux:button variant="primary" icon="plus" href="{{ route('clients.create') }}">
                        Add Client
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="pt-4 pb-2">
            <flux:input 
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Search clients by name, company, or email..."
                icon="magnifying-glass"
            />
        </div>
    </flux:card>

    <!-- Clients Table -->
    <flux:card class="overflow-hidden">
        @if($clients->count() > 0)
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Contact</flux:table.column>
                        <flux:table.column>Location</flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($clients as $client)
                            <flux:table.row 
                                wire:key="client-{{ $client->id }}"
                                class="{{ $selectedClientId === $client->id ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500' : '' }}"
                            >
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ strtoupper(substr($client->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <flux:text class="font-medium">{{ $client->name }}</flux:text>
                                            @if($client->company_name)
                                                <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                                                    {{ $client->company_name }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($client->primaryContact)
                                        <div>
                                            <flux:text>{{ $client->primaryContact->name }}</flux:text>
                                            <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                                                {{ $client->primaryContact->email }}
                                            </flux:text>
                                        </div>
                                    @else
                                        <flux:text class="text-gray-400 dark:text-gray-500">No contact</flux:text>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($client->primaryLocation)
                                        <flux:text>{{ $client->primaryLocation->city }}, {{ $client->primaryLocation->state }}</flux:text>
                                    @else
                                        <flux:text class="text-gray-400 dark:text-gray-500">No location</flux:text>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:text>{{ $client->type ?? 'N/A' }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($client->status === 'active')
                                        <flux:badge color="green" size="sm">Active</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">
                                            {{ ucfirst($client->status ?? 'inactive') }}
                                        </flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button 
                                            wire:click="selectAndViewClient({{ $client->id }})"
                                            variant="primary"
                                            size="sm"
                                            wire:loading.attr="disabled"
                                        >
                                            <span wire:loading.remove wire:target="selectAndViewClient({{ $client->id }})">
                                                View
                                            </span>
                                            <span wire:loading wire:target="selectAndViewClient({{ $client->id }})">
                                                Loading...
                                            </span>
                                        </flux:button>
                                        
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                            <flux:menu>
                                                <flux:menu.item icon="pencil" href="{{ route('clients.edit', $client) }}">
                                                    Edit
                                                </flux:menu.item>
                                                <flux:menu.item icon="user-group" href="{{ route('clients.contacts.index', $client) }}">
                                                    Manage Contacts
                                                </flux:menu.item>
                                                <flux:menu.item icon="map-pin" href="{{ route('clients.locations.index', $client) }}">
                                                    Manage Locations
                                                </flux:menu.item>
                                                <flux:separator />
                                                <flux:menu.item icon="ticket" href="{{ route('tickets.create', ['client_id' => $client->id]) }}">
                                                    Create Ticket
                                                </flux:menu.item>
                                                <flux:menu.item icon="document-text" href="{{ route('financial.invoices.create', ['client_id' => $client->id]) }}">
                                                    Create Invoice
                                                </flux:menu.item>
                                                @can('delete', $client)
                                                    <flux:separator />
                                                    <flux:menu.item 
                                                        icon="trash" 
                                                        variant="danger" 
                                                        wire:click="deleteClient({{ $client->id }})"
                                                        wire:confirm="Are you sure you want to delete this client?"
                                                    >
                                                        Delete
                                                    </flux:menu.item>
                                                @endcan
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
            
            <!-- Pagination -->
            @if($clients->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $clients->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="text-center py-16">
                <flux:icon name="user-group" class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" />
                <flux:heading size="lg" class="mb-2">
                    @if($search)
                        No clients found matching "{{ $search }}"
                    @else
                        No clients found
                    @endif
                </flux:heading>
                <flux:text class="text-gray-600 dark:text-gray-400 mb-6">
                    @if($search)
                        Try adjusting your search terms
                    @else
                        Get started by adding your first client.
                    @endif
                </flux:text>
                @if(!$search)
                    <flux:button variant="primary" icon="plus" href="{{ route('clients.create') }}">
                        Add Your First Client
                    </flux:button>
                @endif
            </div>
        @endif
    </flux:card>
</div>