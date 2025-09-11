@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Projects</flux:heading>
                <flux:text>Manage and track all your projects</flux:text>
            </div>
            <div class="flex gap-2">
                {{-- Export button commented until route is available
                <flux:button href="{{ route('projects.export') }}" 
                             variant="subtle"
                             icon="arrow-down-tray">
                    Export
                </flux:button>
                --}}
                <flux:button href="{{ route('projects.create') }}" 
                             variant="primary"
                             icon="plus">
                    New Project
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Stats Cards -->
    @if(isset($statistics))
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <flux:card class="p-4">
            <flux:text size="sm" class="text-gray-600">Total Projects</flux:text>
            <flux:heading size="xl">{{ $statistics['total'] ?? 0 }}</flux:heading>
        </flux:card>
        
        <flux:card class="p-4">
            <flux:text size="sm" class="text-gray-600">Active</flux:text>
            <flux:heading size="xl" class="text-green-600">{{ $statistics['active'] ?? 0 }}</flux:heading>
        </flux:card>
        
        <flux:card class="p-4">
            <flux:text size="sm" class="text-gray-600">Overdue</flux:text>
            <flux:heading size="xl" class="text-red-600">{{ $statistics['overdue'] ?? 0 }}</flux:heading>
        </flux:card>
        
        <flux:card class="p-4">
            <flux:text size="sm" class="text-gray-600">Completed</flux:text>
            <flux:heading size="xl" class="text-blue-600">{{ $statistics['completed'] ?? 0 }}</flux:heading>
        </flux:card>
    </div>
    @endif

    <!-- Projects Table -->
    <flux:card>
        @if($projects->count() > 0)
            <flux:table :paginate="$projects">
                <flux:table.columns>
                    <flux:table.column>Project</flux:table.column>
                    <flux:table.column>Client</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Priority</flux:table.column>
                    <flux:table.column>Due Date</flux:table.column>
                    <flux:table.column>Progress</flux:table.column>
                    <flux:table.column class="w-1"></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($projects as $project)
                        <flux:table.row :key="$project->id">
                            <flux:table.cell>
                                <div>
                                    <div class="font-medium">
                                        {{ $project->name }}
                                    </div>
                                    @if($project->description)
                                        <flux:text size="sm" class="text-gray-500">
                                            {{ Str::limit($project->description, 50) }}
                                        </flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($project->client)
                                    <flux:link href="{{ route('clients.index') }}">
                                        {{ $project->client->name }}
                                    </flux:link>
                                @else
                                    <flux:text class="text-gray-400">-</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColors = [
                                        'planning' => 'zinc',
                                        'active' => 'blue',
                                        'on_hold' => 'yellow',
                                        'completed' => 'green',
                                        'cancelled' => 'red',
                                    ];
                                    $statusColor = $statusColors[$project->status] ?? 'zinc';
                                @endphp
                                <flux:badge color="{{ $statusColor }}" size="sm">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $priorityColors = [
                                        'low' => 'green',
                                        'medium' => 'yellow',
                                        'high' => 'orange',
                                        'critical' => 'red',
                                    ];
                                    $priorityColor = $priorityColors[$project->priority] ?? 'zinc';
                                @endphp
                                <flux:badge color="{{ $priorityColor }}" variant="outline" size="sm">
                                    {{ ucfirst($project->priority) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($project->due)
                                    <flux:text size="sm">
                                        {{ $project->due->format('M d, Y') }}
                                    </flux:text>
                                    @if($project->due->isPast() && $project->status !== 'completed')
                                        <flux:text size="xs" class="text-red-600">Overdue</flux:text>
                                    @elseif($project->due->diffInDays(now()) <= 7 && $project->status !== 'completed')
                                        <flux:text size="xs" class="text-yellow-600">Due soon</flux:text>
                                    @endif
                                @else
                                    <flux:text class="text-gray-400">-</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" 
                                             style="width: {{ $project->progress_percentage ?? 0 }}%"></div>
                                    </div>
                                    <flux:text size="sm">{{ $project->progress_percentage ?? 0 }}%</flux:text>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button href="{{ route('projects.show', $project) }}" 
                                                size="sm" 
                                                variant="ghost" 
                                                icon="eye"
                                                inset="top bottom" />
                                    <flux:button href="{{ route('projects.edit', $project) }}" 
                                                size="sm" 
                                                variant="ghost" 
                                                icon="pencil"
                                                inset="top bottom" />
                                    <flux:dropdown align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" inset="top bottom" />
                                        <flux:menu>
                                            <flux:menu.item icon="document-duplicate" 
                                                           onclick="alert('Duplicate feature coming soon'); return false;">
                                                Duplicate
                                            </flux:menu.item>
                                            <flux:menu.item icon="archive-box" 
                                                           onclick="alert('Archive feature coming soon'); return false;">
                                                Archive
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" 
                                                           variant="danger"
                                                           onclick="alert('Delete feature coming soon'); return false;">
                                                Delete
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <div class="text-center py-12">
                <flux:icon name="folder" variant="outline" class="mx-auto h-12 w-12 text-gray-400" />
                <flux:heading size="lg" class="mt-2">No Projects Found</flux:heading>
                <flux:text class="mt-1">Get started by creating your first project.</flux:text>
                <div class="mt-6">
                    <flux:button href="{{ route('projects.create') }}" 
                                variant="primary"
                                icon="plus">
                        Create First Project
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:card>
</div>
@endsection
