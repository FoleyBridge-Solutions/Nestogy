@extends('client-portal.layouts.app')

@section('title', 'Projects')

@section('content')
<!-- Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Projects</h1>
            <p class="text-gray-600 dark:text-gray-400">View and track your ongoing projects</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">
                    Total Projects
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['total_projects'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-project-diagram fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-yellow-600 dark:text-yellow-400 uppercase mb-1">
                    In Progress
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['in_progress'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-tasks fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-green-600 dark:text-green-400 uppercase mb-1">
                    Completed
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['completed'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-red-600 dark:text-red-400 uppercase mb-1">
                    Overdue
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['overdue'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>
</div>

<!-- Projects List -->
<flux:card>
    <div class="mb-4">
        <flux:heading size="lg">Your Projects</flux:heading>
    </div>
    
    @if($projects->count() > 0)
        <div class="space-y-4">
            @foreach($projects as $project)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $project->name }}
                                </h3>
                                <flux:badge variant="{{ $project->status === 'completed' ? 'success' : ($project->status === 'in_progress' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst(str_replace('_', ' ', $project->status ?? 'pending')) }}
                                </flux:badge>
                            </div>
                            
                            @if($project->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    {{ Str::limit($project->description, 150) }}
                                </p>
                            @endif
                            
                            <div class="flex items-center gap-6 text-sm text-gray-600 dark:text-gray-400">
                                @if($project->start_date)
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-calendar-start"></i>
                                        <span>Started: {{ $project->start_date->format('M j, Y') }}</span>
                                    </div>
                                @endif
                                
                                @if($project->due_date)
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>Due: {{ $project->due_date->format('M j, Y') }}</span>
                                    </div>
                                @endif
                                
                                @if($project->completion_percentage !== null)
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-percentage"></i>
                                        <span>{{ $project->completion_percentage }}% Complete</span>
                                    </div>
                                @endif
                            </div>
                            
                            @if($project->completion_percentage !== null)
                                <div class="mt-3">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $project->completion_percentage }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        @if(Route::has('client.projects.show'))
                            <flux:button href="{{ route('client.projects.show', $project->id) }}" variant="ghost" size="sm" icon="arrow-right">
                                View Details
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        @if($projects->hasPages())
            <div class="mt-6">
                {{ $projects->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="fas fa-project-diagram fa-4x text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">No Projects Found</h3>
            <p class="text-gray-500 dark:text-gray-400">You don't have any projects at the moment.</p>
        </div>
    @endif
</flux:card>
@endsection
