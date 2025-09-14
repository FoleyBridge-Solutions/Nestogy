<div>
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <flux:toast>{{ session('message') }}</flux:toast>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Assets</h1>
            <p class="text-gray-500">Manage IT assets and equipment</p>
        </div>
        <div class="flex gap-2">
            <flux:button variant="outline" href="{{ route('assets.import') }}">
                <flux:icon.arrow-up-tray class="size-4" />
                Import
            </flux:button>
            <flux:button variant="primary" href="{{ route('assets.create') }}">
                <flux:icon.plus class="size-4" />
                Create Asset
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Search --}}
                <flux:input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search assets..."
                    icon="magnifying-glass"
                />

                {{-- Type Filter --}}
                <flux:select wire:model.live="type" placeholder="All Types">
                    <flux:option value="">All Types</flux:option>
                    @foreach($types as $typeOption)
                        <flux:option value="{{ $typeOption }}">{{ $typeOption }}</flux:option>
                    @endforeach
                </flux:select>

                {{-- Status Filter --}}
                <flux:select wire:model.live="status" placeholder="All Statuses">
                    <flux:option value="">All Statuses</flux:option>
                    @foreach($statuses as $statusOption)
                        <flux:option value="{{ $statusOption }}">{{ $statusOption }}</flux:option>
                    @endforeach
                </flux:select>

                {{-- Client Filter --}}
                <flux:select wire:model.live="clientId" placeholder="All Clients">
                    <flux:option value="">All Clients</flux:option>
                    @foreach($clients as $client)
                        <flux:option value="{{ $client->id }}">{{ $client->name }}</flux:option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                {{-- Assigned To Filter --}}
                <flux:select wire:model.live="assignedTo" placeholder="All Users">
                    <flux:option value="">All Users</flux:option>
                    @foreach($users as $user)
                        <flux:option value="{{ $user->id }}">{{ $user->name }}</flux:option>
                    @endforeach
                </flux:select>

                {{-- Location Filter --}}
                <flux:select wire:model.live="locationId" placeholder="All Locations">
                    <flux:option value="">All Locations</flux:option>
                    @foreach($locations as $location)
                        <flux:option value="{{ $location->id }}">{{ $location->name }}</flux:option>
                    @endforeach
                </flux:select>

                {{-- Per Page --}}
                <flux:select wire:model.live="perPage">
                    <flux:option value="10">10 per page</flux:option>
                    <flux:option value="25">25 per page</flux:option>
                    <flux:option value="50">50 per page</flux:option>
                    <flux:option value="100">100 per page</flux:option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Bulk Actions --}}
    @if(count($selectedAssets) > 0)
        <flux:card class="mb-4">
            <div class="p-4 flex items-center gap-4">
                <span class="text-sm text-gray-600">{{ count($selectedAssets) }} selected</span>
                
                <flux:dropdown>
                    <flux:button variant="outline" size="sm">
                        Update Status
                        <flux:icon.chevron-down class="size-4" />
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="bulkUpdateStatus('Active')">
                            <flux:icon.check-circle class="size-4 text-green-500" />
                            Active
                        </flux:menu.item>
                        <flux:menu.item wire:click="bulkUpdateStatus('Inactive')">
                            <flux:icon.x-circle class="size-4 text-gray-500" />
                            Inactive
                        </flux:menu.item>
                        <flux:menu.item wire:click="bulkUpdateStatus('In Repair')">
                            <flux:icon.wrench class="size-4 text-yellow-500" />
                            In Repair
                        </flux:menu.item>
                        <flux:menu.item wire:click="bulkUpdateStatus('Disposed')">
                            <flux:icon.trash class="size-4 text-red-500" />
                            Disposed
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:card>
    @endif

    {{-- Assets Table --}}
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
                <flux:table.column 
                    sortable 
                    wire:click="sortBy('asset_tag')"
                    class="cursor-pointer"
                >
                    Asset Tag
                    @if($sortField === 'asset_tag')
                        <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                    @endif
                </flux:table.column>
                <flux:table.column 
                    sortable 
                    wire:click="sortBy('name')"
                    class="cursor-pointer"
                >
                    Name
                    @if($sortField === 'name')
                        <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                    @endif
                </flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Client</flux:table.column>
                <flux:table.column>Assigned To</flux:table.column>
                <flux:table.column>Location</flux:table.column>
                <flux:table.column 
                    sortable 
                    wire:click="sortBy('status')"
                    class="cursor-pointer"
                >
                    Status
                    @if($sortField === 'status')
                        <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                    @endif
                </flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            
            <flux:table.rows>
                @forelse($assets as $asset)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:checkbox 
                                wire:model.live="selectedAssets" 
                                value="{{ $asset->id }}"
                            />
                        </flux:table.cell>
                        <flux:table.cell>
                            <a href="{{ route('assets.show', $asset) }}" class="text-blue-600 hover:underline">
                                {{ $asset->asset_tag ?? 'N/A' }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <div class="font-medium">{{ $asset->name }}</div>
                                @if($asset->manufacturer || $asset->model)
                                    <div class="text-xs text-gray-500">
                                        {{ $asset->manufacturer }} {{ $asset->model }}
                                    </div>
                                @endif
                                @if($asset->serial_number)
                                    <div class="text-xs text-gray-500">SN: {{ $asset->serial_number }}</div>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="outline">{{ $asset->type }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $asset->client?->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $asset->assignedTo?->name ?? 'Unassigned' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $asset->location?->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="{{ 
                                $asset->status === 'Active' ? 'success' : 
                                ($asset->status === 'Inactive' ? 'outline' : 
                                ($asset->status === 'In Repair' ? 'warning' : 
                                ($asset->status === 'Disposed' ? 'danger' : 'info')))
                            }}">
                                {{ $asset->status }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm">
                                    <flux:icon.ellipsis-horizontal class="size-4" />
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item href="{{ route('assets.show', $asset) }}">
                                        <flux:icon.eye class="size-4" />
                                        View
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ route('assets.edit', $asset) }}">
                                        <flux:icon.pencil class="size-4" />
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ route('assets.checkinout', $asset) }}">
                                        <flux:icon.arrow-right-circle class="size-4" />
                                        Check In/Out
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item 
                                        wire:click="archiveAsset({{ $asset->id }})"
                                        wire:confirm="Are you sure you want to archive this asset?"
                                        variant="danger"
                                    >
                                        <flux:icon.trash class="size-4" />
                                        Archive
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="text-center py-8">
                            <div class="text-gray-500">
                                <flux:icon.computer-desktop class="size-12 mx-auto mb-4 text-gray-300" />
                                <p>No assets found</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        
        @if($assets->hasPages())
            <div class="p-4 border-t">
                {{ $assets->links() }}
            </div>
        @endif
    </flux:card>
</div>