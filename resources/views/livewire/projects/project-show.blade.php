<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <flux:toast variant="success">{{ session('message') }}</flux:toast>
    @endif

    {{-- Header --}}
    <flux:card>
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
                    @if($project->project_number)
                        <p class="text-gray-500">Project #{{ $project->project_number }}</p>
                    @endif
                    @if($project->description)
                        <p class="text-gray-600 mt-2">{{ $project->description }}</p>
                    @endif
                    <div class="flex items-center gap-4 mt-3">
                        <flux:badge variant="{{ 
                            $project->status === 'active' || $project->status === 'in_progress' ? 'success' : 
                            ($project->status === 'completed' ? 'info' : 
                            ($project->status === 'on_hold' ? 'warning' : 
                            ($project->status === 'cancelled' ? 'danger' : 'outline')))
                        }}">
                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                        </flux:badge>
                        @if($project->priority)
                            <flux:badge variant="{{ 
                                $project->priority === 'urgent' ? 'danger' : 
                                ($project->priority === 'high' ? 'warning' : 
                                ($project->priority === 'medium' ? 'info' : 'outline'))
                            }}">
                                {{ ucfirst($project->priority) }} Priority
                            </flux:badge>
                        @endif
                        @if($project->budget)
                            <flux:badge variant="outline">
                                Budget: ${{ number_format($project->budget, 2) }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <flux:dropdown>
                        <flux:button variant="outline" size="sm">
                            Status: {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            <flux:icon.chevron-down class="size-4" />
                        </flux:button>
                        <flux:menu>
                            @foreach($statuses as $status)
                                <flux:menu.item wire:click="updateProjectStatus('{{ $status }}')">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                    
                    <flux:button variant="outline" href="{{ route('projects.edit', $project) }}" size="sm">
                        <flux:icon.pencil class="size-4" />
                        Edit
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:card>

    {{-- Progress Bar --}}
    @if($project->progress !== null)
        <flux:card>
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">Project Progress</span>
                    <span class="text-sm text-gray-600">{{ $project->progress }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500" style="width: {{ $project->progress }}%"></div>
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Tabs --}}
    <flux:tab.group default="summary">
        <flux:tabs>
            <flux:tab name="summary">Summary</flux:tab>
            <flux:tab name="tasks">Tasks ({{ $project->tasks->count() }})</flux:tab>
            <flux:tab name="tickets">Tickets ({{ $project->tickets->count() }})</flux:tab>
            <flux:tab name="invoices">Invoices ({{ $project->invoices->count() }})</flux:tab>
            <flux:tab name="files">Files ({{ $project->files->count() }})</flux:tab>
            <flux:tab name="notes">Notes ({{ $project->notes->count() }})</flux:tab>
            <flux:tab name="activity">Activity</flux:tab>
        </flux:tabs>

        {{-- Summary Tab --}}
        <flux:tab.panel name="summary">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Project Details --}}
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Project Details</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Client</dt>
                                <dd class="text-sm">
                                    @if($project->client)
                                        <a href="{{ route('clients.show', $project->client) }}" class="text-blue-600 hover:underline">
                                            {{ $project->client->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">No client</span>
                                    @endif
                                </dd>
                            </div>
                            
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Project Manager</dt>
                                <dd class="text-sm">{{ $project->manager?->name ?? 'Unassigned' }}</dd>
                            </div>
                            
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                                <dd class="text-sm">{{ $project->start_date?->format('M d, Y') ?? 'Not set' }}</dd>
                            </div>
                            
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                                <dd class="text-sm">
                                    {{ $project->due_date?->format('M d, Y') ?? 'Not set' }}
                                    @if($project->due_date && $project->due_date->isPast() && !in_array($project->status, ['completed', 'cancelled']))
                                        <flux:badge variant="danger" size="xs">Overdue</flux:badge>
                                    @endif
                                </dd>
                            </div>
                            
                            @if($project->budget)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Budget</dt>
                                    <dd class="text-sm">${{ number_format($project->budget, 2) }}</dd>
                                </div>
                            @endif
                            
                            @if($project->actual_cost)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Actual Cost</dt>
                                    <dd class="text-sm">${{ number_format($project->actual_cost, 2) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </flux:card>

                {{-- Team Members --}}
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Team Members</h3>
                        @if($project->teamMembers && count($project->teamMembers) > 0)
                            <div class="space-y-2">
                                @foreach($project->teamMembers as $member)
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm">
                                            {{ substr($member->name, 0, 2) }}
                                        </flux:avatar>
                                        <div>
                                            <div class="font-medium">{{ $member->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $member->email }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">No team members assigned</p>
                        @endif
                    </div>
                </flux:card>
            </div>
        </flux:tab.panel>

        {{-- Tasks Tab --}}
        <flux:tab.panel name="tasks">
            <flux:card>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Tasks</h3>
                        <flux:button variant="primary" size="sm" wire:click="$set('showTaskModal', true)">
                            <flux:icon.plus class="size-4" />
                            Add Task
                        </flux:button>
                    </div>
                    
                    @if($project->tasks && count($project->tasks) > 0)
                        <div class="space-y-2">
                            @foreach($project->tasks as $task)
                                <div class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50">
                                    <flux:checkbox 
                                        wire:click="toggleTaskStatus({{ $task->id }})"
                                        :checked="$task->status === 'completed'"
                                    />
                                    <div class="flex-1">
                                        <div class="{{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                                            {{ $task->name }}
                                        </div>
                                        @if($task->description)
                                            <div class="text-xs text-gray-500">{{ $task->description }}</div>
                                        @endif
                                        <div class="flex items-center gap-4 mt-1">
                                            @if($task->assigned_to)
                                                <span class="text-xs text-gray-500">
                                                    <flux:icon.user class="size-3 inline" />
                                                    {{ $task->assignee?->name }}
                                                </span>
                                            @endif
                                            @if($task->due_date)
                                                <span class="text-xs text-gray-500">
                                                    <flux:icon.calendar class="size-3 inline" />
                                                    {{ $task->due_date->format('M d, Y') }}
                                                </span>
                                            @endif
                                            <flux:badge variant="{{ 
                                                $task->priority === 'urgent' ? 'danger' : 
                                                ($task->priority === 'high' ? 'warning' : 
                                                ($task->priority === 'medium' ? 'info' : 'outline'))
                                            }}" size="xs">
                                                {{ ucfirst($task->priority) }}
                                            </flux:badge>
                                        </div>
                                    </div>
                                    <flux:button 
                                        variant="ghost" 
                                        size="xs"
                                        wire:click="deleteTask({{ $task->id }})"
                                        wire:confirm="Delete this task?"
                                    >
                                        <flux:icon.trash class="size-3" />
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No tasks yet</p>
                    @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        {{-- Tickets Tab --}}
        <flux:tab.panel name="tickets">
            <flux:card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Related Tickets</h3>
                    @if($project->tickets && count($project->tickets) > 0)
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Ticket #</flux:table.column>
                                <flux:table.column>Subject</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                                <flux:table.column>Priority</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($project->tickets as $ticket)
                                    <flux:table.row>
                                        <flux:table.cell>#{{ $ticket->ticket_number ?? $ticket->number }}</flux:table.cell>
                                        <flux:table.cell>{{ $ticket->subject }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge variant="{{ 
                                                $ticket->status === 'open' ? 'success' : 
                                                ($ticket->status === 'closed' ? 'outline' : 'info')
                                            }}">
                                                {{ ucfirst($ticket->status) }}
                                            </flux:badge>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge variant="{{ 
                                                $ticket->priority === 'urgent' ? 'danger' : 
                                                ($ticket->priority === 'high' ? 'warning' : 'outline')
                                            }}">
                                                {{ ucfirst($ticket->priority) }}
                                            </flux:badge>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button variant="ghost" size="sm" href="{{ route('tickets.show', $ticket) }}">
                                                <flux:icon.eye class="size-4" />
                                            </flux:button>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @else
                        <p class="text-gray-500 text-center py-4">No related tickets</p>
                    @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        {{-- Invoices Tab --}}
        <flux:tab.panel name="invoices">
            <flux:card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Project Invoices</h3>
                    @if($project->invoices && count($project->invoices) > 0)
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Invoice #</flux:table.column>
                                <flux:table.column>Date</flux:table.column>
                                <flux:table.column>Amount</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                                <flux:table.column></flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($project->invoices as $invoice)
                                    <flux:table.row>
                                        <flux:table.cell>{{ $invoice->invoice_number ?? $invoice->number }}</flux:table.cell>
                                        <flux:table.cell>{{ $invoice->invoice_date?->format('M d, Y') ?? '-' }}</flux:table.cell>
                                        <flux:table.cell>${{ number_format($invoice->total ?? 0, 2) }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge variant="{{ 
                                                $invoice->status === 'paid' ? 'success' : 
                                                ($invoice->status === 'overdue' ? 'danger' : 'warning')
                                            }}">
                                                {{ ucfirst($invoice->status) }}
                                            </flux:badge>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button variant="ghost" size="sm" href="{{ route('financial.invoices.show', $invoice) }}">
                                                <flux:icon.eye class="size-4" />
                                            </flux:button>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @else
                        <p class="text-gray-500 text-center py-4">No invoices yet</p>
                    @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        {{-- Files Tab --}}
        <flux:tab.panel name="files">
            <flux:card>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Files</h3>
                        <div>
                            <flux:input
                                type="file"
                                wire:model="files"
                                multiple
                                id="file-upload"
                                class="hidden"
                            />
                            <flux:button 
                                variant="primary" 
                                size="sm"
                                onclick="document.getElementById('file-upload').click()"
                            >
                                <flux:icon.arrow-up-tray class="size-4" />
                                Upload Files
                            </flux:button>
                            @if($files)
                                <flux:button 
                                    variant="success" 
                                    size="sm"
                                    wire:click="uploadFiles"
                                    class="ml-2"
                                >
                                    Save {{ count($files) }} Files
                                </flux:button>
                            @endif
                        </div>
                    </div>
                    
                    @if($project->files && count($project->files) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($project->files as $file)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <flux:icon.document class="size-5 text-gray-400" />
                                        <div>
                                            <div class="font-medium">{{ $file->name }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ round($file->size / 1024, 2) }} KB • 
                                                Uploaded {{ $file->created_at?->diffForHumans() ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                    <flux:button 
                                        variant="ghost" 
                                        size="sm"
                                        href="{{ Storage::url($file->path) }}"
                                        target="_blank"
                                    >
                                        <flux:icon.arrow-down-tray class="size-4" />
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No files uploaded</p>
                    @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        {{-- Notes Tab --}}
        <flux:tab.panel name="notes">
            <flux:card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Project Notes</h3>
                    
                    {{-- Add Note Form --}}
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <flux:textarea
                            wire:model="noteContent"
                            placeholder="Add a note..."
                            rows="3"
                            class="mb-3"
                        />
                        <div class="flex items-center justify-between">
                            <flux:checkbox wire:model="noteIsPrivate">
                                Private Note
                            </flux:checkbox>
                            <flux:button 
                                variant="primary" 
                                size="sm"
                                wire:click="addNote"
                            >
                                <flux:icon.pencil class="size-4" />
                                Add Note
                            </flux:button>
                        </div>
                    </div>
                    
                    {{-- Notes List --}}
                    @if($project->notes && count($project->notes) > 0)
                        <div class="space-y-3">
                            @foreach($project->notes as $note)
                                <div class="p-4 border rounded-lg {{ $note->is_private ? 'bg-yellow-50' : '' }}">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="font-medium">{{ $note->user?->name ?? 'Unknown' }}</span>
                                                <span class="text-xs text-gray-500">{{ $note->created_at?->diffForHumans() ?? '' }}</span>
                                                @if($note->is_private)
                                                    <flux:badge variant="warning" size="xs">Private</flux:badge>
                                                @endif
                                            </div>
                                            <div class="text-gray-700">{{ $note->content }}</div>
                                        </div>
                                        @if($note->user_id === Auth::id())
                                            <flux:button
                                                variant="ghost"
                                                size="xs"
                                                wire:click="deleteNote({{ $note->id }})"
                                                wire:confirm="Delete this note?"
                                            >
                                                <flux:icon.trash class="size-3" />
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No notes yet</p>
                    @endif
                </div>
            </flux:card>
        </flux:tab.panel>

        {{-- Activity Tab --}}
        <flux:tab.panel name="activity">
            <flux:card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Activity Log</h3>
                    @if($project->activities && count($project->activities) > 0)
                        <div class="space-y-3">
                            @foreach($project->activities as $activity)
                                <div class="flex gap-3">
                                    <div class="mt-1">
                                        <div class="size-2 bg-blue-500 rounded-full"></div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm">
                                            <span class="font-medium">{{ $activity->user?->name ?? 'System' }}</span>
                                            {{ $activity->description }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $activity->created_at?->diffForHumans() ?? '' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No activity yet</p>
                    @endif
                </div>
            </flux:card>
        </flux:tab.panel>
    </flux:tab.group>

    {{-- Task Modal --}}
    <flux:modal wire:model="showTaskModal" title="Create Task">
        <form wire:submit.prevent="createTask">
            <div class="space-y-4">
                <flux:input
                    wire:model="taskName"
                    label="Task Name"
                    placeholder="Enter task name..."
                    required
                />
                
                <flux:textarea
                    wire:model="taskDescription"
                    label="Description"
                    placeholder="Task description..."
                    rows="3"
                />
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        type="date"
                        wire:model="taskDueDate"
                        label="Due Date"
                    />
                    
                    <flux:select wire:model="taskPriority" label="Priority">
                        @foreach($priorities as $priority)
                            <flux:option value="{{ $priority }}">{{ ucfirst($priority) }}</flux:option>
                        @endforeach
                    </flux:select>
                </div>
                
                <flux:select wire:model="taskAssignedTo" label="Assign To">
                    <flux:option value="">Unassigned</flux:option>
                    @foreach($technicians as $tech)
                        <flux:option value="{{ $tech->id }}">{{ $tech->name }}</flux:option>
                    @endforeach
                </flux:select>
            </div>
            
            <div class="flex justify-end gap-2 mt-6">
                <flux:button variant="ghost" wire:click="$set('showTaskModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Create Task
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>