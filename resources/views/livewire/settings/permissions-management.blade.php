<div class="space-y-6" style="min-height: 100vh;">
    {{-- Header --}}
    <div class="flex items-center justify-between" style="background: white; padding: 1rem; border-radius: 0.5rem;">
        <div>
            <flux:heading size="xl">Roles & Permissions</flux:heading>
            <flux:text class="mt-1">Manage user roles, permissions, and access control</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" icon="arrow-down-tray">Export</flux:button>
            <flux:button variant="ghost" icon="question-mark-circle">Help</flux:button>
        </div>
    </div>

    {{-- Simple Tab Navigation --}}
    <flux:card>
        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
            <button 
                wire:click="$set('activeTab', 'overview')" 
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-600 hover:text-zinc-900' }}">
                <div class="flex items-center gap-2">
                    <flux:icon name="chart-bar" variant="micro" />
                    <span>Overview</span>
                </div>
            </button>
            <button 
                wire:click="$set('activeTab', 'roles')" 
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'roles' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-600 hover:text-zinc-900' }}">
                <div class="flex items-center gap-2">
                    <flux:icon name="user-group" variant="micro" />
                    <span>Roles</span>
                </div>
            </button>
            <button 
                wire:click="$set('activeTab', 'matrix')" 
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'matrix' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-600 hover:text-zinc-900' }}">
                <div class="flex items-center gap-2">
                    <flux:icon name="table-cells" variant="micro" />
                    <span>Permission Matrix</span>
                </div>
            </button>
            <button 
                wire:click="$set('activeTab', 'audit')" 
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'audit' ? 'border-blue-500 text-blue-600' : 'border-transparent text-zinc-600 hover:text-zinc-900' }}">
                <div class="flex items-center gap-2">
                    <flux:icon name="document-text" variant="micro" />
                    <span>Audit Log</span>
                </div>
            </button>
        </div>
    </flux:card>

    {{-- Tab Content --}}

    {{-- Overview Tab --}}
    @if($activeTab === 'overview')
            <div class="space-y-6">
                {{-- Statistics Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <flux:card>
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:text variant="muted" size="sm">Total Roles</flux:text>
                                <flux:heading size="lg" class="mt-1">{{ $stats['total_roles'] }}</flux:heading>
                            </div>
                            <flux:icon name="shield-check" class="text-blue-500 size-8" />
                        </div>
                    </flux:card>

                    <flux:card>
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:text variant="muted" size="sm">Total Users</flux:text>
                                <flux:heading size="lg" class="mt-1">{{ $stats['total_users'] }}</flux:heading>
                            </div>
                            <flux:icon name="users" class="text-green-500 size-8" />
                        </div>
                    </flux:card>

                    <flux:card>
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:text variant="muted" size="sm">Total Permissions</flux:text>
                                <flux:heading size="lg" class="mt-1">{{ $stats['total_abilities'] }}</flux:heading>
                            </div>
                            <flux:icon name="key" class="text-purple-500 size-8" />
                        </div>
                    </flux:card>

                    <flux:card>
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:text variant="muted" size="sm">Users Without Roles</flux:text>
                                <flux:heading size="lg" class="mt-1">{{ $stats['users_without_roles'] }}</flux:heading>
                            </div>
                            @if($stats['users_without_roles'] > 0)
                                <flux:icon name="exclamation-triangle" class="text-amber-500 size-8" />
                            @else
                                <flux:icon name="check-circle" class="text-green-500 size-8" />
                            @endif
                        </div>
                    </flux:card>
                </div>

                {{-- Quick Actions --}}
                <flux:card>
                    <flux:heading>Quick Actions</flux:heading>
                    <flux:text class="mt-2 mb-4">Common tasks to manage roles and permissions</flux:text>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <flux:button variant="filled" icon="plus" wire:click="setActiveTab('roles')">
                            Create New Role
                        </flux:button>
                        <flux:button variant="filled" icon="user-plus" wire:click="setActiveTab('users')">
                            Assign Permissions to User
                        </flux:button>
                        <flux:button variant="filled" icon="table-cells" wire:click="setActiveTab('matrix')">
                            View Permission Matrix
                        </flux:button>
                    </div>
                </flux:card>

                {{-- Role Summary Cards --}}
                <div>
                    <flux:heading class="mb-4">Roles Overview</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($roles as $role)
                            <flux:card>
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <flux:heading size="lg">{{ $role['title'] ?? ucfirst($role['name']) }}</flux:heading>
                                        <flux:badge color="blue" class="mt-2">{{ $role['users_count'] }} users</flux:badge>
                                        <flux:badge color="purple" class="mt-2 ml-2">{{ count($role['abilities']) }} permissions</flux:badge>
                                    </div>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="selectRole('{{ $role['name'] }}')" wire:then="setActiveTab('roles')">
                                        Edit
                                    </flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="setActiveTab('users')">
                                        View Users
                                    </flux:button>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                </div>
            </div>
    @endif

    {{-- Roles Tab --}}
    @if($activeTab === 'roles')
        <livewire:settings.roles-list />
    @endif

    {{-- Permission Matrix Tab --}}
    @if($activeTab === 'matrix')
        <livewire:settings.permission-matrix />
    @endif

    {{-- Audit Log Tab --}}
    @if($activeTab === 'audit')
            <flux:card>
                <flux:heading>Audit Log</flux:heading>
                <flux:text class="mt-2 mb-4">Track all permission changes and role assignments</flux:text>
                
                <div class="flex gap-3 mb-4">
                    <flux:input wire:model.live.debounce.300ms="auditSearch" icon="magnifying-glass" placeholder="Search audit log..." class="flex-1" />
                    <flux:select wire:model.live="auditActionFilter" placeholder="Filter by action">
                        <flux:select.option value="">All Actions</flux:select.option>
                        <flux:select.option value="role_assigned">Role Assigned</flux:select.option>
                        <flux:select.option value="role_removed">Role Removed</flux:select.option>
                        <flux:select.option value="permission_changed">Permission Changed</flux:select.option>
                    </flux:select>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Date/Time</flux:table.column>
                        <flux:table.column>Action</flux:table.column>
                        <flux:table.column>Details</flux:table.column>
                        <flux:table.column>Performed By</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->auditLogs as $log)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div class="text-sm">{{ $log->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-zinc-500">{{ $log->created_at->format('h:i A') }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" color="{{ $log->action === 'permission_changed' ? 'blue' : 'purple' }}">
                                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($log->action === 'permission_changed')
                                        <div class="text-sm">
                                            <span class="font-medium">{{ $log->metadata['ability'] ?? 'Unknown' }}</span>
                                            {{ ($log->metadata['granted'] ?? false) ? 'granted to' : 'revoked from' }}
                                            <span class="font-medium">{{ $log->metadata['role'] ?? 'Unknown' }}</span>
                                        </div>
                                    @elseif($log->action === 'role_assigned')
                                        <div class="text-sm">
                                            Role <span class="font-medium">{{ $log->metadata['role'] ?? 'Unknown' }}</span> assigned
                                        </div>
                                    @elseif($log->action === 'role_removed')
                                        <div class="text-sm">
                                            Role <span class="font-medium">{{ $log->metadata['role'] ?? 'Unknown' }}</span> removed
                                        </div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="text-sm">{{ $log->user->name ?? 'System' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $log->user->email ?? 'N/A' }}</div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center py-8">
                                    <flux:text variant="muted">No audit log entries found</flux:text>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
                
                <div class="mt-4">
                    {{ $this->auditLogs->links() }}
                </div>
            </flux:card>
    @endif
</div>
