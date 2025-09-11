<div class="w-full">
    <flux:card>
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Assets</flux:heading>
                <div class="flex items-center gap-2">
                    <flux:button variant="primary" icon="plus" href="{{ route('assets.create') }}">
                        New Asset
                    </flux:button>
                    <flux:dropdown>
                        <flux:button variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="arrow-down-tray" wire:click="exportToExcel">
                                Export to Excel
                            </flux:menu.item>
                            <flux:menu.item icon="arrow-up-tray" href="{{ route('assets.import.form') }}">
                                Import from Excel
                            </flux:menu.item>
                            <flux:menu.item icon="document-arrow-down" href="{{ route('assets.template.download') }}">
                                Download Template
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Filters -->
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div class="md:col-span-12-span-2">
                        <flux:input 
                            type="search" 
                            wire:model.live.debounce.300ms="search" 
                            placeholder="Search assets..."
                            icon="magnifying-glass"
                        />
                    </div>
                    <div>
                        <flux:select wire:model.live="clientId">
                            <flux:select.option value="">All Clients</flux:select.option>
                            @foreach($clients as $client)
                                <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:select wire:model.live="type">
                            <flux:select.option value="">All Types</flux:select.option>
                            @foreach($types as $assetType)
                                <flux:select.option value="{{ $assetType }}">{{ $assetType }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:select wire:model.live="status">
                            <flux:select.option value="">All Statuses</flux:select.option>
                            @foreach($statuses as $assetStatus)
                                <flux:select.option value="{{ $assetStatus }}">{{ $assetStatus }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:select wire:model.live="locationId">
                            <flux:select.option value="">All Locations</flux:select.option>
                            @foreach($locations as $location)
                                <flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                
                @if($search || $clientId || $type || $status || $locationId)
                    <div class="mt-4">
                        <flux:button variant="ghost" size="sm" wire:click="resetFilters">
                            Clear Filters
                        </flux:button>
                    </div>
                @endif
            </div>
            
            <!-- Assets Table -->
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Asset Info</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Client</flux:table.column>
                    <flux:table.column>Location</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column class="w-1"></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($assets as $asset)
                        <flux:table.row wire:key="asset-{{ $asset->id }}">
                            <flux:table.cell>
                                <div>
                                    <flux:link href="{{ route('assets.show', $asset) }}" class="font-medium">
                                        {{ $asset->name }}
                                    </flux:link>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($asset->serial)
                                            <flux:text size="sm" class="text-gray-500">S/N: {{ $asset->serial }}</flux:text>
                                        @endif
                                        @if($asset->make || $asset->model)
                                            <flux:text size="sm" class="text-gray-500">
                                                {{ $asset->make }} {{ $asset->model }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="zinc" size="sm">{{ $asset->type }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($asset->client)
                                    <flux:link href="{{ route('clients.show', $asset->client) }}">
                                        {{ $asset->client->name }}
                                    </flux:link>
                                @else
                                    <flux:text class="text-gray-400">-</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($asset->location)
                                    {{ $asset->location->name }}
                                @else
                                    <flux:text class="text-gray-400">-</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColors = [
                                        'Ready To Deploy' => 'blue',
                                        'Deployed' => 'green',
                                        'Archived' => 'zinc',
                                        'Broken - Pending Repair' => 'orange',
                                        'Broken - Not Repairable' => 'red',
                                        'Out for Repair' => 'yellow',
                                        'Lost/Stolen' => 'red',
                                        'Unknown' => 'zinc'
                                    ];
                                    $statusColor = $statusColors[$asset->status] ?? 'zinc';
                                @endphp
                                <flux:badge color="{{ $statusColor }}" size="sm">
                                    {{ $asset->status }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button href="{{ route('assets.show', $asset) }}" 
                                                size="sm" 
                                                variant="ghost" 
                                                icon="eye"
                                                inset="top bottom" />
                                    <flux:button href="{{ route('assets.edit', $asset) }}" 
                                                size="sm" 
                                                variant="ghost" 
                                                icon="pencil"
                                                inset="top bottom" />
                                    <flux:dropdown align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" inset="top bottom" />
                                        <flux:menu>
                                            @if($asset->status !== 'Deployed')
                                                <flux:menu.item icon="arrow-right-circle" href="{{ route('assets.checkinout', ['asset' => $asset, 'action' => 'checkout']) }}">
                                                    Check Out
                                                </flux:menu.item>
                                            @else
                                                <flux:menu.item icon="arrow-left-circle" href="{{ route('assets.checkinout', ['asset' => $asset, 'action' => 'checkin']) }}">
                                                    Check In
                                                </flux:menu.item>
                                            @endif
                                            <flux:menu.separator />
                                            <flux:menu.item 
                                                icon="trash" 
                                                wire:click="deleteAsset({{ $asset->id }})"
                                                wire:confirm="Are you sure you want to delete this asset?"
                                                variant="danger">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                <div class="text-center py-12">
                                    <flux:icon name="computer-desktop" variant="outline" class="mx-auto h-12 w-12 text-gray-400" />
                                    <flux:heading size="lg" class="mt-2">No Assets Found</flux:heading>
                                    <flux:text class="mt-1">
                                        @if($search || $clientId || $type || $status || $locationId)
                                            No assets match your current filters.
                                        @else
                                            Get started by adding your first asset.
                                        @endif
                                    </flux:text>
                                    <div class="mt-6">
                                        @if(!($search || $clientId || $type || $status || $locationId))
                                            <flux:button variant="primary" icon="plus" href="{{ route('assets.create') }}">
                                                Add First Asset
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            
            <!-- Pagination -->
            <div class="mt-4">
                {{ $assets->links() }}
            </div>
        </div>
    </flux:card>
</div>
