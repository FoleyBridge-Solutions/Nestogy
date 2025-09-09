@if($projects->count() > 0)
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Project Name</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Progress</flux:table.column>
            <flux:table.column>Due Date</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($projects as $project)
                <flux:table.row class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800" onclick="window.location='{{ route('projects.show', $project) }}'">
                    <flux:table.cell>{{ $project->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $project->status === 'completed' ? 'green' : ($project->status === 'in_progress' ? 'blue' : 'zinc') }}" size="sm">
                            {{ str_replace('_', ' ', ucfirst($project->status ?? 'pending')) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $project->progress ?? 0 }}%"></div>
                            </div>
                            <span class="text-xs">{{ $project->progress ?? 0 }}%</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $project->due ? $project->due->format('M d, Y') : 'No due date' }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
@else
    <p class="text-gray-500 dark:text-gray-400">No projects found for this client.</p>
@endif