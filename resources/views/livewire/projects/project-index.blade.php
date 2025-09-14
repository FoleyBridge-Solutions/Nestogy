<div>
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <flux:toast>{{ session('message') }}</flux:toast>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Projects</h1>
            <p class="text-gray-500">Manage projects and deliverables</p>
        </div>
        <flux:button variant="primary" href="{{ route('projects.create') }}">
            <flux:icon.plus class="size-4" />
            Create Project
        </flux:button>
    </div>

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Search --}}
                <flux:input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search projects..."
                    icon="magnifying-glass"
                />

                {{-- Status Filter --}}
                <flux:select wire:model.live="status" placeholder="All Statuses">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $statusOption)
                        <option value="{{ $statusOption }}">{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                    @endforeach
                </flux:select>

                {{-- Client Filter --}}
                <flux:select wire:model.live="clientId" placeholder="All Clients">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </flux:select>

                {{-- Priority Filter --}}
                <flux:select wire:model.live="priority" placeholder="All Priorities">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $priorityOption)
                        <option value="{{ $priorityOption }}">{{ ucfirst($priorityOption) }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                {{-- Manager Filter --}}
                <flux:select wire:model.live="managerId" placeholder="All Managers">
                    <option value="">All Managers</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </flux:select>

                {{-- Per Page --}}
                <flux:select wire:model.live="perPage">
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Bulk Actions --}}
    @if(count($selectedProjects) > 0)
        <flux:card class="mb-4">
            <div class="p-4 flex items-center gap-4">
                <span class="text-sm text-gray-600">{{ count($selectedProjects) }} selected</span>
                
                <flux:dropdown>
                    <flux:button variant="outline" size="sm">
                        Update Status
                        <flux:icon.chevron-down class="size-4" />
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="bulkUpdateStatus('active')">
                            <flux:icon.play class="size-4 text-green-500" />
                            Active
                        </flux:menu.item>
                        <flux:menu.item wire:click="bulkUpdateStatus('on_hold')">
                            <flux:icon.pause class="size-4 text-yellow-500" />
                            On Hold
                        </flux:menu.item>
                        <flux:menu.item wire:click="bulkUpdateStatus('completed')">
                            <flux:icon.check-circle class="size-4 text-blue-500" />
                            Completed
                        </flux:menu.item>
                        <flux:menu.item wire:click="bulkUpdateStatus('cancelled')">
                            <flux:icon.x-circle class="size-4 text-red-500" />
                            Cancelled
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:card>
    @endif

    {{-- Projects Table --}}
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
                <flux:table.column 
                    sortable 
                    wire:click="sortBy('name')"
                    class="cursor-pointer"
                >
                    Project
                    @if($sortField === 'name')
                        <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                    @endif
                </flux:table.column>
                <flux:table.column>Client</flux:table.column>
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
                <flux:table.column 
                    sortable 
                    wire:click="sortBy('priority')"
                    class="cursor-pointer"
                >
                    Priority
                    @if($sortField === 'priority')
                        <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                    @endif
                </flux:table.column>
                <flux:table.column>Progress</flux:table.column>
                <flux:table.column>Manager</flux:table.column>
                <flux:table.column 
                    sortable 
                    wire:click="sortBy('due_date')"
                    class="cursor-pointer"
                >
                    Due Date
                    @if($sortField === 'due_date')
                        <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                    @endif
                </flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            
            <flux:table.rows>
                @forelse($projects as $project)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:checkbox 
                                wire:model.live="selectedProjects" 
                                value="{{ $project->id }}"
                            />
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <a href="{{ route('projects.show', $project) }}" class="font-medium text-blue-600 hover:underline">
                                    {{ $project->name }}
                                </a>
                                @if($project->project_number)
                                    <div class="text-xs text-gray-500">#{{ $project->project_number }}</div>
                                @endif
                                @if($project->description)
                                    <div class="text-xs text-gray-500 mt-1">{{ Str::limit($project->description, 60) }}</div>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $project->client?->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="{{ 
                                $project->status === 'active' || $project->status === 'in_progress' ? 'success' : 
                                ($project->status === 'completed' ? 'info' : 
                                ($project->status === 'on_hold' ? 'warning' : 
                                ($project->status === 'cancelled' ? 'danger' : 'outline')))
                            }}">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($project->priority)
                                <flux:badge variant="{{ 
                                    $project->priority === 'urgent' ? 'danger' : 
                                    ($project->priority === 'high' ? 'warning' : 
                                    ($project->priority === 'medium' ? 'info' : 'outline'))
                                }}">
                                    {{ ucfirst($project->priority) }}
                                </flux:badge>
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($project->progress !== null)
                                <div class="flex items-center gap-2">
                                    <div class="w-20 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $project->progress }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ $project->progress }}%</span>
                                </div>
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $project->manager?->name ?? 'Unassigned' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($project->due_date)
                                <div class="text-sm">
                                    {{ $project->due_date->format('M d, Y') }}
                                    @if($project->due_date->isPast() && !in_array($project->status, ['completed', 'cancelled']))
                                        <flux:badge variant="danger" size="xs">Overdue</flux:badge>
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm">
                                    <flux:icon.ellipsis-horizontal class="size-4" />
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item href="{{ route('projects.show', $project) }}">
                                        <flux:icon.eye class="size-4" />
                                        View
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ route('projects.edit', $project) }}">
                                        <flux:icon.pencil class="size-4" />
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item 
                                        wire:click="archiveProject({{ $project->id }})"
                                        wire:confirm="Are you sure you want to archive this project?"
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
                                <flux:icon.folder class="size-12 mx-auto mb-4 text-gray-300" />
                                <p>No projects found</p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        
        @if($projects->hasPages())
            <div class="p-4 border-t">
                {{ $projects->links() }}
            </div>
        @endif
    </flux:card>
</div>