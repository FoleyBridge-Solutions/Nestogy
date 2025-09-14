<div class="space-y-6">
    {{-- Client Selection Status --}}
    @if($selectedClient)
        <flux:card class="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <flux:icon.check-circle class="size-5 text-blue-500" />
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Currently working with: <strong>{{ $selectedClient->name }}</strong>
                    </p>
                </div>
                <div class="flex gap-2">
                    <flux:button variant="ghost" size="sm" wire:click="clearSelection">
                        Clear Selection
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @else
        <flux:card class="bg-yellow-50 dark:bg-yellow-950 border-yellow-200 dark:border-yellow-800">
            <div class="flex items-center gap-3">
                <flux:icon.exclamation-triangle class="size-5 text-yellow-500" />
                <p class="text-sm text-yellow-700 dark:text-yellow-200">
                    <strong>No client selected.</strong> Please select a client below to access client-specific features.
                </p>
            </div>
        </flux:card>
    @endif

    {{-- Page Header --}}
    <flux:card>
        <flux:card.header>
            <flux:card.title>
                {{ $selectedClient ? 'Select Different Client' : 'Select Client' }}
            </flux:card.title>
            <flux:card.description>
                {{ $selectedClient 
                    ? 'Choose a different client to work with, or manage client relationships' 
                    : 'Choose a client to access their contacts, locations, documents and other information' 
                }}
            </flux:card.description>
        </flux:card.header>
        
        <div class="flex justify-end gap-3 px-6 pb-4">
            <flux:button variant="outline" href="{{ route('clients.import.form') }}">
                <flux:icon.arrow-up-tray class="size-4" />
                Import
            </flux:button>
            <flux:button variant="primary" href="{{ route('clients.create') }}">
                <flux:icon.plus class="size-4" />
                Add Client
            </flux:button>
        </div>
    </flux:card>

    {{-- Tabs for Customers and Leads --}}
    <flux:tabs wire:model="showLeads">
        <flux:tab name="customers" :value="false">Customers</flux:tab>
        <flux:tab name="leads" :value="true">Leads</flux:tab>
    </flux:tabs>

    {{-- Filters and Search --}}
    <flux:card>
        <div class="p-6 space-y-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <flux:input 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search clients..."
                        icon="magnifying-glass"
                    />
                </div>
                
                <flux:select wire:model.live="type" placeholder="All Types">
                    <flux:option value="">All Types</flux:option>
                    <flux:option value="individual">Individual</flux:option>
                    <flux:option value="company">Company</flux:option>
                </flux:select>
                
                <flux:select wire:model.live="status" placeholder="All Status">
                    <flux:option value="">All Status</flux:option>
                    <flux:option value="active">Active</flux:option>
                    <flux:option value="inactive">Inactive</flux:option>
                </flux:select>
                
                <flux:select wire:model.live="perPage">
                    <flux:option value="10">10 per page</flux:option>
                    <flux:option value="25">25 per page</flux:option>
                    <flux:option value="50">50 per page</flux:option>
                    <flux:option value="100">100 per page</flux:option>
                </flux:select>
                
                <flux:button variant="outline" wire:click="exportCsv">
                    <flux:icon.arrow-down-tray class="size-4" />
                    Export
                </flux:button>
            </div>
        </div>
    </flux:card>

    {{-- Clients Table --}}
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>
                        <flux:button 
                            variant="ghost" 
                            size="sm" 
                            wire:click="sortBy('name')"
                            class="font-medium"
                        >
                            Name
                            @if($sortField === 'name')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-3" />
                                @else
                                    <flux:icon.chevron-down class="size-3" />
                                @endif
                            @endif
                        </flux:button>
                    </flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Phone</flux:table.column>
                    <flux:table.column>
                        <flux:button 
                            variant="ghost" 
                            size="sm" 
                            wire:click="sortBy('type')"
                            class="font-medium"
                        >
                            Type
                            @if($sortField === 'type')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-3" />
                                @else
                                    <flux:icon.chevron-down class="size-3" />
                                @endif
                            @endif
                        </flux:button>
                    </flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Tags</flux:table.column>
                    <flux:table.column>
                        <flux:button 
                            variant="ghost" 
                            size="sm" 
                            wire:click="sortBy('created_at')"
                            class="font-medium"
                        >
                            Created
                            @if($sortField === 'created_at')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-3" />
                                @else
                                    <flux:icon.chevron-down class="size-3" />
                                @endif
                            @endif
                        </flux:button>
                    </flux:table.column>
                    <flux:table.column></flux:table.column>
            </flux:table.columns>
            
            <flux:table.rows>
                @forelse($clients as $client)
                    <flux:table.row :key="$client->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm">
                                    {{ substr($client->name, 0, 2) }}
                                </flux:avatar>
                                <div>
                                    <div class="font-medium">{{ $client->name }}</div>
                                    @if($client->company)
                                        <div class="text-sm text-gray-500">{{ $client->company }}</div>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            {{ $client->email ?? $client->primaryContact?->email ?? '-' }}
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            {{ $client->phone ?? $client->primaryContact?->phone ?? '-' }}
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:badge variant="{{ $client->type === 'company' ? 'primary' : 'outline' }}">
                                {{ ucfirst($client->type ?? 'individual') }}
                            </flux:badge>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:badge variant="{{ $client->is_active ? 'success' : 'danger' }}">
                                {{ $client->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            @foreach($client->tags as $tag)
                                <flux:badge variant="info" size="sm">{{ $tag->name }}</flux:badge>
                            @endforeach
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            {{ $client->created_at->format('M d, Y') }}
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @if($selectedClient && $selectedClient->id === $client->id)
                                    <flux:badge variant="success">Current</flux:badge>
                                @else
                                    <flux:button 
                                        variant="ghost" 
                                        size="sm" 
                                        wire:click="selectClient({{ $client->id }})"
                                    >
                                        Select
                                    </flux:button>
                                @endif
                                
                                @if($client->lead)
                                    <flux:button 
                                        variant="ghost" 
                                        size="sm" 
                                        wire:click="confirmConvert({{ $client->id }})"
                                        class="text-green-600 hover:text-green-700"
                                    >
                                        Convert
                                    </flux:button>
                                @endif
                                
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm">
                                        <flux:icon.ellipsis-horizontal class="size-4" />
                                    </flux:button>
                                    
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('clients.show', $client) }}">
                                            <flux:icon.eye class="size-4" />
                                            View
                                        </flux:menu.item>
                                        
                                        <flux:menu.item href="{{ route('clients.edit', $client) }}">
                                            <flux:icon.pencil class="size-4" />
                                            Edit
                                        </flux:menu.item>
                                        
                                        <flux:menu.separator />
                                        
                                        <flux:menu.item 
                                            wire:click="confirmDelete({{ $client->id }})"
                                            variant="danger"
                                        >
                                            <flux:icon.trash class="size-4" />
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-8">
                            <div class="text-gray-500">
                                @if($search)
                                    No clients found matching "{{ $search }}"
                                @else
                                    No {{ $showLeads ? 'leads' : 'clients' }} found
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        
        @if($clients->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $clients->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" max-width="sm">
        <flux:modal.header>
            <flux:modal.title>Delete Client</flux:modal.title>
        </flux:modal.header>
        
        <flux:modal.body>
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-red-100 dark:bg-red-900 rounded-full">
                    <flux:icon.exclamation-triangle class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <p>Are you sure you want to delete this client? This action cannot be undone.</p>
            </div>
        </flux:modal.body>
        
        <flux:modal.footer>
            <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">
                Cancel
            </flux:button>
            <flux:button variant="danger" wire:click="deleteClient">
                Delete Client
            </flux:button>
        </flux:modal.footer>
    </flux:modal>

    {{-- Convert Lead Modal --}}
    <flux:modal wire:model="showConvertModal" max-width="sm">
        <flux:modal.header>
            <flux:modal.title>Convert Lead to Customer</flux:modal.title>
        </flux:modal.header>
        
        <flux:modal.body>
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-full">
                    <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400" />
                </div>
                <p>Are you sure you want to convert this lead to a customer?</p>
            </div>
        </flux:modal.body>
        
        <flux:modal.footer>
            <flux:button variant="ghost" wire:click="$set('showConvertModal', false)">
                Cancel
            </flux:button>
            <flux:button variant="success" wire:click="convertLead">
                Convert to Customer
            </flux:button>
        </flux:modal.footer>
    </flux:modal>
</div>