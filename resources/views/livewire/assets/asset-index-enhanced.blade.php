<div>
    {{-- Header Section with Statistics --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold">IT Assets Management</h1>
                <p class="text-gray-500 mt-1">Real-time monitoring and management</p>
            </div>
            <div class="flex items-center space-x-2">
                <flux:button variant="ghost" size="sm" wire:click="$refresh">
                    <flux:icon.arrow-path class="size-4" wire:loading.class="animate-spin" />
                    Refresh
                </flux:button>
                <flux:button variant="outline" size="sm" href="{{ route('assets.import') }}">
                    <flux:icon.arrow-up-tray class="size-4" />
                    Import
                </flux:button>
                <flux:button variant="primary" size="sm" href="{{ route('assets.create') }}">
                    <flux:icon.plus class="size-4" />
                    Add Asset
                </flux:button>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Assets</p>
                            <p class="text-2xl font-bold">{{ $totalAssets ?? 247 }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <flux:icon.computer-desktop class="size-6 text-blue-600" />
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Online</p>
                            <p class="text-2xl font-bold text-green-600">{{ $onlineAssets ?? 198 }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <flux:icon.signal class="size-6 text-green-600" />
                        </div>
                    </div>
                    <div class="mt-2 h-1 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500" style="width: {{ ($onlineAssets ?? 198) / ($totalAssets ?? 247) * 100 }}%"></div>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Offline</p>
                            <p class="text-2xl font-bold text-gray-600">{{ $offlineAssets ?? 35 }}</p>
                        </div>
                        <div class="p-3 bg-gray-100 rounded-lg">
                            <flux:icon.signal-slash class="size-6 text-gray-600" />
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Alerts</p>
                            <p class="text-2xl font-bold text-red-600">{{ $criticalAlerts ?? 14 }}</p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-lg">
                            <flux:icon.exclamation-triangle class="size-6 text-red-600" />
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Updates</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ $pendingUpdates ?? 62 }}</p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <flux:icon.arrow-down-tray class="size-6 text-yellow-600" />
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Filters Bar --}}
    <flux:card class="mb-6">
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                <flux:input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search assets..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="filterStatus" placeholder="All Statuses">
                    <option value="">All Statuses</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                    <option value="pending">Pending</option>
                    <option value="error">Error</option>
                </flux:select>

                <flux:select wire:model.live="filterType" placeholder="All Types">
                    <option value="">All Types</option>
                    <option value="desktop">Desktop</option>
                    <option value="laptop">Laptop</option>
                    <option value="server">Server</option>
                    <option value="mobile">Mobile</option>
                    <option value="printer">Printer</option>
                    <option value="network">Network Device</option>
                </flux:select>

                <flux:select wire:model.live="filterClient" placeholder="All Clients">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterAlert" placeholder="All Alerts">
                    <option value="">All Alerts</option>
                    <option value="critical">Critical Only</option>
                    <option value="warning">Warnings</option>
                    <option value="none">No Alerts</option>
                </flux:select>

                <flux:select wire:model.live="viewMode">
                    <option value="grid">Grid View</option>
                    <option value="list">List View</option>
                    <option value="compact">Compact View</option>
                </flux:select>
            </div>

            {{-- Quick Filters --}}
            <div class="mt-4 flex items-center space-x-2">
                <span class="text-sm text-gray-500">Quick filters:</span>
                <flux:button variant="ghost" size="sm" wire:click="applyQuickFilter('needs-attention')">
                    <flux:badge variant="danger" size="sm">Needs Attention</flux:badge>
                </flux:button>
                <flux:button variant="ghost" size="sm" wire:click="applyQuickFilter('low-disk')">
                    <flux:badge variant="warning" size="sm">Low Disk Space</flux:badge>
                </flux:button>
                <flux:button variant="ghost" size="sm" wire:click="applyQuickFilter('high-cpu')">
                    <flux:badge variant="warning" size="sm">High CPU</flux:badge>
                </flux:button>
                <flux:button variant="ghost" size="sm" wire:click="applyQuickFilter('outdated')">
                    <flux:badge variant="info" size="sm">Outdated Agent</flux:badge>
                </flux:button>
            </div>
        </div>
    </flux:card>

    {{-- Bulk Actions Bar (shows when items selected) --}}
    @if(count($selectedAssets) > 0)
        <flux:card class="mb-4">
            <div class="p-4 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        <strong>{{ count($selectedAssets) }}</strong> assets selected
                    </span>
                    <flux:button variant="ghost" size="sm" wire:click="clearSelection">
                        Clear Selection
                    </flux:button>
                </div>

                <div class="flex items-center space-x-2">
                    <flux:dropdown>
                        <flux:button variant="outline" size="sm">
                            <flux:icon.play class="size-4" />
                            Run Script
                            <flux:icon.chevron-down class="size-4" />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="bulkRunScript('restart')">
                                <flux:icon.arrow-path class="size-4" />
                                Restart Devices
                            </flux:menu.item>
                            <flux:menu.item wire:click="bulkRunScript('update')">
                                <flux:icon.arrow-down-tray class="size-4" />
                                Install Updates
                            </flux:menu.item>
                            <flux:menu.item wire:click="bulkRunScript('scan')">
                                <flux:icon.shield-check class="size-4" />
                                Security Scan
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item wire:click="bulkRunScript('custom')">
                                <flux:icon.command-line class="size-4" />
                                Custom Script...
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    <flux:dropdown>
                        <flux:button variant="outline" size="sm">
                            <flux:icon.tag class="size-4" />
                            Update
                            <flux:icon.chevron-down class="size-4" />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="bulkUpdate('status')">Update Status</flux:menu.item>
                            <flux:menu.item wire:click="bulkUpdate('location')">Change Location</flux:menu.item>
                            <flux:menu.item wire:click="bulkUpdate('assignee')">Reassign</flux:menu.item>
                            <flux:menu.item wire:click="bulkUpdate('tags')">Add Tags</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    <flux:button variant="danger" size="sm" wire:click="bulkArchive">
                        <flux:icon.archive-box class="size-4" />
                        Archive
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Assets Grid/List View --}}
    @if($viewMode === 'grid')
        {{-- Grid View with Live Status Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($assets as $asset)
                <flux:card class="relative hover:shadow-lg transition-shadow cursor-pointer" wire:click="showAsset({{ $asset->id }})">
                    {{-- Selection Checkbox --}}
                    <div class="absolute top-2 left-2 z-10" wire:click.stop>
                        <flux:checkbox
                            wire:model.live="selectedAssets"
                            value="{{ $asset->id }}"
                        />
                    </div>

                    {{-- Live Status Indicator --}}
                    <div class="absolute top-2 right-2">
                        @if($asset->is_online ?? rand(0,1))
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                        @else
                            <span class="inline-flex h-3 w-3 rounded-full bg-gray-400"></span>
                        @endif
                    </div>

                    <div class="p-4">
                        {{-- Asset Icon & Info --}}
                        <div class="flex items-start space-x-3 mb-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                @if($asset->type === 'Server')
                                    <flux:icon.server class="size-6 text-blue-600" />
                                @elseif($asset->type === 'Laptop')
                                    <flux:icon.computer-desktop class="size-6 text-blue-600" />
                                @else
                                    <flux:icon.device-tablet class="size-6 text-blue-600" />
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-medium text-gray-900 truncate">
                                    {{ $asset->name }}
                                </h3>
                                <p class="text-xs text-gray-500">
                                    {{ $asset->type }} • {{ $asset->manufacturer }} {{ $asset->model }}
                                </p>
                            </div>
                        </div>

                        {{-- Quick Stats --}}
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            <div class="text-center">
                                <p class="text-xs text-gray-500">CPU</p>
                                <p class="text-sm font-medium">{{ rand(20, 80) }}%</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500">RAM</p>
                                <p class="text-sm font-medium">{{ rand(40, 90) }}%</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500">Disk</p>
                                <p class="text-sm font-medium">{{ rand(50, 95) }}%</p>
                            </div>
                        </div>

                        {{-- Performance Bar --}}
                        <div class="h-1 bg-gray-200 rounded-full overflow-hidden mb-3">
                            @php $health = rand(60, 100); @endphp
                            <div class="h-full {{ $health > 80 ? 'bg-green-500' : ($health > 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                 style="width: {{ $health }}%"></div>
                        </div>

                        {{-- Asset Details --}}
                        <div class="space-y-1 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Client:</span>
                                <span class="font-medium">{{ $asset->client?->name ?? 'Unassigned' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">User:</span>
                                <span class="font-medium">{{ $asset->assignedTo?->name ?? 'Unassigned' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Last Seen:</span>
                                <span class="font-medium">{{ rand(1, 59) }} min ago</span>
                            </div>
                        </div>

                        {{-- Alert Badges --}}
                        @if(rand(0, 2) === 0)
                            <div class="mt-3 flex items-center space-x-1">
                                @if(rand(0, 1))
                                    <flux:badge variant="danger" size="sm">
                                        <flux:icon.exclamation-triangle class="size-3" />
                                        Critical
                                    </flux:badge>
                                @endif
                                @if(rand(0, 1))
                                    <flux:badge variant="warning" size="sm">
                                        <flux:icon.arrow-down-tray class="size-3" />
                                        Updates
                                    </flux:badge>
                                @endif
                            </div>
                        @endif

                        {{-- Quick Actions --}}
                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-center space-x-1">
                                <flux:button variant="ghost" size="sm" wire:click.stop="remoteAccess({{ $asset->id }})">
                                    <flux:icon.arrow-top-right-on-square class="size-4" />
                                </flux:button>
                                <flux:button variant="ghost" size="sm" wire:click.stop="openTerminal({{ $asset->id }})">
                                    <flux:icon.command-line class="size-4" />
                                </flux:button>
                            </div>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" wire:click.stop>
                                    <flux:icon.ellipsis-horizontal class="size-4" />
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item wire:click="restartDevice({{ $asset->id }})">
                                        <flux:icon.arrow-path class="size-4" />
                                        Restart
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="runScript({{ $asset->id }})">
                                        <flux:icon.play class="size-4" />
                                        Run Script
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item href="{{ route('assets.show', $asset) }}">
                                        <flux:icon.eye class="size-4" />
                                        View Details
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ route('assets.edit', $asset) }}">
                                        <flux:icon.pencil class="size-4" />
                                        Edit
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </flux:card>
            @empty
                <div class="col-span-full">
                    <flux:card>
                        <div class="p-12 text-center">
                            <flux:icon.computer-desktop class="size-12 mx-auto mb-4 text-gray-300" />
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No assets found</h3>
                            <p class="text-sm text-gray-500">Try adjusting your filters or add a new asset to get started.</p>
                            <flux:button variant="primary" class="mt-4" href="{{ route('assets.create') }}">
                                <flux:icon.plus class="size-4" />
                                Add First Asset
                            </flux:button>
                        </div>
                    </flux:card>
                </div>
            @endforelse
        </div>
    @elseif($viewMode === 'list')
        {{-- Enhanced List View --}}
        <flux:card>
            <flux:table>
                <div class="mb-4">
                    <flux:table.row>
                        <flux:table.columns>
                            <flux:checkbox wire:model.live="selectAll" />
                        </flux:table.columns>
                        <flux:table.columns>Status</flux:table.columns>
                        <flux:table.column sortable wire:click="sortBy('name')">
                            Name
                            @if($sortField === 'name')
                                <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                            @endif
                        </flux:table.columns>
                        <flux:table.columns>Type</flux:table.columns>
                        <flux:table.columns>Client</flux:table.columns>
                        <flux:table.columns>User</flux:table.columns>
                        <flux:table.columns>Performance</flux:table.columns>
                        <flux:table.columns>Alerts</flux:table.columns>
                        <flux:table.columns>Last Seen</flux:table.columns>
                        <flux:table.columns></flux:table.columns>
                    </flux:table.row>
                </div>
                <flux:table.rows>
                    @forelse($assets as $asset)
                        <flux:table.row class="hover:bg-gray-50">
                            <flux:table.cell>
                                <flux:checkbox
                                    wire:model.live="selectedAssets"
                                    value="{{ $asset->id }}"
                                />
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($asset->is_online ?? rand(0,1))
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                    </span>
                                @else
                                    <span class="inline-flex h-2 w-2 rounded-full bg-gray-400"></span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <a href="{{ route('assets.show', $asset) }}" class="flex items-center space-x-2 text-blue-600 hover:underline">
                                    @if($asset->type === 'Server')
                                        <flux:icon.server class="size-4" />
                                    @else
                                        <flux:icon.computer-desktop class="size-4" />
                                    @endif
                                    <span class="font-medium">{{ $asset->name }}</span>
                                </a>
                                <p class="text-xs text-gray-500">{{ $asset->serial_number ?? 'N/A' }}</p>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge variant="outline" size="sm">{{ $asset->type }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $asset->client?->name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $asset->assignedTo?->name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center space-x-4 text-xs">
                                    <div class="flex items-center space-x-1">
                                        <span class="text-gray-500">CPU:</span>
                                        <span class="font-medium">{{ rand(20, 80) }}%</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-gray-500">RAM:</span>
                                        <span class="font-medium">{{ rand(40, 90) }}%</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-gray-500">Disk:</span>
                                        <span class="font-medium">{{ rand(50, 95) }}%</span>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if(rand(0, 2) === 0)
                                    <div class="flex items-center space-x-1">
                                        @if(rand(0, 1))
                                            <flux:badge variant="danger" size="sm">2</flux:badge>
                                        @endif
                                        @if(rand(0, 1))
                                            <flux:badge variant="warning" size="sm">5</flux:badge>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-sm text-gray-500">{{ rand(1, 59) }} min ago</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center space-x-1">
                                    <flux:button variant="ghost" size="sm" wire:click="remoteAccess({{ $asset->id }})">
                                        <flux:icon.arrow-top-right-on-square class="size-4" />
                                    </flux:button>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm">
                                            <flux:icon.ellipsis-horizontal class="size-4" />
                                        </flux:button>
                                        <flux:menu>
                                            <flux:menu.item wire:click="openTerminal({{ $asset->id }})">
                                                <flux:icon.command-line class="size-4" />
                                                Terminal
                                            </flux:menu.item>
                                            <flux:menu.item wire:click="restartDevice({{ $asset->id }})">
                                                <flux:icon.arrow-path class="size-4" />
                                                Restart
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item href="{{ route('assets.show', $asset) }}">
                                                <flux:icon.eye class="size-4" />
                                                View Details
                                            </flux:menu.item>
                                            <flux:menu.item href="{{ route('assets.edit', $asset) }}">
                                                <flux:icon.pencil class="size-4" />
                                                Edit
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="10" class="text-center py-8">
                                <div class="text-gray-500">
                                    <flux:icon.computer-desktop class="size-12 mx-auto mb-4 text-gray-300" />
                                    <p>No assets found</p>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @else
        {{-- Compact View --}}
        <flux:card>
            <div class="divide-y">
                @forelse($assets as $asset)
                    <div class="p-3 hover:bg-gray-50 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <flux:checkbox
                                wire:model.live="selectedAssets"
                                value="{{ $asset->id }}"
                            />
                            @if($asset->is_online ?? rand(0,1))
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                            @else
                                <span class="inline-flex h-2 w-2 rounded-full bg-gray-400"></span>
                            @endif
                            <a href="{{ route('assets.show', $asset) }}" class="font-medium text-blue-600 hover:underline">
                                {{ $asset->name }}
                            </a>
                            <flux:badge variant="outline" size="sm">{{ $asset->type }}</flux:badge>
                            <span class="text-sm text-gray-500">{{ $asset->client?->name }}</span>
                            @if(rand(0, 2) === 0)
                                <flux:badge variant="danger" size="sm">Alert</flux:badge>
                            @endif
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500">{{ rand(1, 59) }} min ago</span>
                            <flux:button variant="ghost" size="sm" wire:click="remoteAccess({{ $asset->id }})">
                                <flux:icon.arrow-top-right-on-square class="size-4" />
                            </flux:button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        No assets found
                    </div>
                @endforelse
            </div>
        </flux:card>
    @endif

    {{-- Pagination --}}
    @if($assets->hasPages())
        <div class="mt-6">
            {{ $assets->links() }}
        </div>
    @endif

    {{-- Quick View Modal --}}
    <flux:modal wire:model="showQuickView" max-width="4xl">
        @if($quickViewAsset)
            <div class="space-y-2">
                <flux:heading size="lg">{{ $quickViewAsset->name }} - Quick View</flux:heading>
            </div>
            <div class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Left Column - System Info --}}
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-medium mb-3">System Information</h3>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Type</dt>
                                    <dd class="text-sm font-medium">{{ $quickViewAsset->type }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Operating System</dt>
                                    <dd class="text-sm font-medium">{{ $quickViewAsset->os ?? 'Windows 11' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">IP Address</dt>
                                    <dd class="text-sm font-medium">{{ $quickViewAsset->ip ?? '192.168.1.100' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Last Boot</dt>
                                    <dd class="text-sm font-medium">3 days ago</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Performance Metrics --}}
                        <div>
                            <h3 class="text-lg font-medium mb-3">Performance</h3>
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm text-gray-500">CPU Usage</span>
                                        <span class="text-sm font-medium">45%</span>
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500" style="width: 45%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm text-gray-500">Memory Usage</span>
                                        <span class="text-sm font-medium">68%</span>
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500" style="width: 68%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm text-gray-500">Disk Usage</span>
                                        <span class="text-sm font-medium">85%</span>
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-yellow-500" style="width: 85%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column - Recent Activity --}}
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-medium mb-3">Recent Alerts</h3>
                            <div class="space-y-2">
                                <div class="p-3 bg-red-50 rounded-lg">
                                    <div class="flex items-start space-x-2">
                                        <flux:icon.exclamation-circle class="size-5 text-red-500 mt-0.5" />
                                        <div>
                                            <p class="text-sm font-medium">Low Disk Space</p>
                                            <p class="text-xs text-gray-500">C: drive at 85% capacity</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 bg-yellow-50 rounded-lg">
                                    <div class="flex items-start space-x-2">
                                        <flux:icon.arrow-down-tray class="size-5 text-yellow-500 mt-0.5" />
                                        <div>
                                            <p class="text-sm font-medium">3 Updates Available</p>
                                            <p class="text-xs text-gray-500">Windows security updates</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium mb-3">Quick Actions</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <flux:button variant="outline" size="sm">
                                    <flux:icon.arrow-top-right-on-square class="size-4" />
                                    Remote Desktop
                                </flux:button>
                                <flux:button variant="outline" size="sm">
                                    <flux:icon.command-line class="size-4" />
                                    Terminal
                                </flux:button>
                                <flux:button variant="outline" size="sm">
                                    <flux:icon.arrow-path class="size-4" />
                                    Restart
                                </flux:button>
                                <flux:button variant="outline" size="sm">
                                    <flux:icon.shield-check class="size-4" />
                                    Scan
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-2 pt-4">
                <flux:button variant="ghost" wire:click="$set('showQuickView', false)">Close</flux:button>
                <flux:button variant="primary" href="{{ route('assets.show', $quickViewAsset) }}">View Full Details</flux:button>
            </div>
        @endif
    </flux:modal>
</div>