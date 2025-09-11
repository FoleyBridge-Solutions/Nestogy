<div class="grid gap-6 p-6">
    <!-- Project Header -->
    <flux:card>
        <div class="flex justify-between items-start">
            <div>
                <flux:heading size="xl">{{ $project->name }}</flux:heading>
                <div class="flex gap-2 mt-2">
                    <flux:badge color="{{ $project->status === 'completed' ? 'emerald' : ($project->status === 'in_progress' ? 'blue' : 'gray') }}">
                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                    </flux:badge>
                    <flux:badge color="{{ $project->priority === 'high' ? 'red' : ($project->priority === 'medium' ? 'amber' : 'gray') }}">
                        {{ ucfirst($project->priority) }} Priority
                    </flux:badge>
                </div>
                <div class="flex gap-4 mt-3 text-sm text-gray-600">
                    @if($project->client)
                        <div class="flex items-center gap-1">
                            <flux:icon name="building-office" variant="mini" />
                            <span>{{ $project->client->name }}</span>
                        </div>
                    @endif
                    @if($project->manager)
                        <div class="flex items-center gap-1">
                            <flux:icon name="user" variant="mini" />
                            <span>{{ $project->manager->name }}</span>
                        </div>
                    @endif
                    @if($project->due_date)
                        <div class="flex items-center gap-1">
                            <flux:icon name="calendar" variant="mini" />
                            <span>Due {{ $project->due_date->format('M d, Y') }}</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-1">
                        <flux:icon name="users" variant="mini" />
                        <span>{{ $project->members->count() }} Team Members</span>
                    </div>
                </div>
            </div>
            
            <flux:dropdown>
                <flux:button variant="ghost" size="sm" icon-trailing="chevron-down">
                    Actions
                </flux:button>
                
                <flux:menu>
                    @can('update', $project)
                        <flux:menu.item icon="pencil-square" href="{{ route('projects.edit', $project) }}">
                            Edit Project
                        </flux:menu.item>
                    @endcan
                    
                    <flux:menu.separator />
                    
                    @can('delete', $project)
                        <flux:menu.item icon="archive-box" wire:click="archiveProject">
                            Archive Project
                        </flux:menu.item>
                        <flux:menu.item icon="trash" class="text-red-600" wire:click="$set('showDeleteConfirmModal', true)">
                            Delete Project
                        </flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:card>

    <!-- Progress Bar -->
    @if($project->progress > 0)
    <flux:card>
        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-sm font-semibold text-gray-700">Overall Progress</span>
                <p class="text-xs text-gray-500 mt-1">Project completion status</p>
            </div>
            <span class="text-2xl font-bold text-indigo-600">{{ $project->progress }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-3 rounded-full transition-all duration-500" 
                 style="width: {{ $project->progress }}%"></div>
        </div>
    </flux:card>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600 mb-1">Health Score</div>
                    <div class="text-2xl font-bold">{{ $health['overall'] ?? 100 }}%</div>
                    <div class="text-xs text-{{ ($health['overall'] ?? 100) >= 80 ? 'green' : (($health['overall'] ?? 100) >= 60 ? 'yellow' : 'red') }}-600">
                        {{ ($health['overall'] ?? 100) >= 80 ? 'Good' : (($health['overall'] ?? 100) >= 60 ? 'At Risk' : 'Critical') }}
                    </div>
                </div>
                <div class="text-{{ ($health['overall'] ?? 100) >= 80 ? 'green' : (($health['overall'] ?? 100) >= 60 ? 'yellow' : 'red') }}-500">
                    <flux:icon name="check-circle" class="w-8 h-8" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600 mb-1">Budget Used</div>
                    <div class="text-2xl font-bold">
                        @if($project->budget && $project->actual_cost)
                            {{ number_format(($project->actual_cost / $project->budget) * 100, 0) }}%
                        @else
                            0%
                        @endif
                    </div>
                    <div class="text-xs text-gray-600">
                        @if($project->budget)
                            ${{ number_format($project->actual_cost ?? 0, 2) }} / ${{ number_format($project->budget, 2) }}
                        @else
                            No budget set
                        @endif
                    </div>
                </div>
                <div class="text-blue-500">
                    <flux:icon name="currency-dollar" class="w-8 h-8" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600 mb-1">Tasks</div>
                    <div class="text-2xl font-bold">{{ $project->tasks->where('status', 'completed')->count() }}/{{ $project->tasks->count() }}</div>
                    <div class="text-xs text-gray-600">
                        {{ $project->tasks->count() > 0 ? number_format(($project->tasks->where('status', 'completed')->count() / $project->tasks->count()) * 100, 0) : 0 }}% Complete
                    </div>
                </div>
                <div class="text-purple-500">
                    <flux:icon name="clipboard-document-list" class="w-8 h-8" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600 mb-1">Time Logged</div>
                    <div class="text-2xl font-bold">
                        {{ number_format($project->timeEntries->sum('hours'), 1) }}h
                    </div>
                    <div class="text-xs text-gray-600">
                        @if($project->estimated_hours)
                            Est: {{ $project->estimated_hours }}h
                        @else
                            No estimate
                        @endif
                    </div>
                </div>
                <div class="text-green-500">
                    <flux:icon name="clock" class="w-8 h-8" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Main Content Tabs -->
    <flux:card>
        <flux:tab.group>
            <flux:tabs>
                <flux:tab name="overview">Overview</flux:tab>
                <flux:tab name="tasks">Tasks & Milestones</flux:tab>
                <flux:tab name="team">Team</flux:tab>
                <flux:tab name="timeline">Timeline</flux:tab>
                <flux:tab name="budget">Budget</flux:tab>
                <flux:tab name="activity">Activity</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="overview" class="mt-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Project Details & Activity -->
                    <div class="lg:col-span-2 space-y-6">
                        <flux:card>
                            <flux:heading size="lg" class="mb-4">Project Details</flux:heading>
                            
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-sm text-gray-600">Project Code</span>
                                        <div class="font-medium">{{ $project->prefix }}-{{ $project->number }}</div>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-600">Category</span>
                                        <div class="font-medium">{{ $project->category ?? 'General' }}</div>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-600">Start Date</span>
                                        <div class="font-medium">
                                            {{ $project->start_date ? $project->start_date->format('M d, Y') : 'Not set' }}
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-600">Due Date</span>
                                        <div class="font-medium">
                                            {{ $project->due_date ? $project->due_date->format('M d, Y') : 'Not set' }}
                                        </div>
                                    </div>
                                </div>
                                
                                @if($project->description)
                                <div class="pt-3 border-t">
                                    <span class="text-sm text-gray-600">Description</span>
                                    <div class="mt-2 text-sm">{{ $project->description }}</div>
                                </div>
                                @endif
                            </div>
                        </flux:card>
                    </div>

                    <!-- Milestones & Health -->
                    <div class="space-y-6">
                        <flux:card>
                            <flux:heading size="lg" class="mb-4">Milestones</flux:heading>
                            
                            <div class="space-y-2">
                                @forelse($project->milestones as $milestone)
                                <div class="flex items-center gap-2">
                                    <flux:icon name="{{ $milestone->completed ? 'check-circle' : 'circle' }}" 
                                              class="w-5 h-5 {{ $milestone->completed ? 'text-green-500' : 'text-gray-400' }}" />
                                    <div class="flex-1">
                                        <div class="text-sm font-medium">{{ $milestone->name }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $milestone->due_date ? $milestone->due_date->format('M d') : 'No due date' }}
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-sm text-gray-500">No milestones defined</div>
                                @endforelse
                            </div>
                        </flux:card>
                    </div>
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="tasks" class="mt-6">
                <div class="flex justify-between items-center mb-4">
                    <flux:heading size="lg">Tasks</flux:heading>
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="$set('showNewTaskModal', true)">
                        New Task
                    </flux:button>
                </div>
                
                @if($project->tasks->count() > 0)
                <div class="space-y-2">
                    @foreach($project->tasks as $task)
                    <flux:card>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="font-medium">{{ $task->name }}</div>
                                @if($task->description)
                                <div class="text-sm text-gray-500">{{ Str::limit($task->description, 100) }}</div>
                                @endif
                                <div class="flex gap-2 mt-2">
                                    <flux:badge color="{{ $task->priority === 'high' ? 'red' : ($task->priority === 'medium' ? 'amber' : 'gray') }}" size="sm">
                                        {{ ucfirst($task->priority) }}
                                    </flux:badge>
                                    <flux:badge color="{{ $task->status === 'completed' ? 'emerald' : ($task->status === 'in_progress' ? 'blue' : 'gray') }}" size="sm">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </flux:badge>
                                    @if($task->due_date)
                                    <span class="text-xs text-gray-500">Due {{ $task->due_date->format('M d') }}</span>
                                    @endif
                                </div>
                            </div>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="updateTaskStatus({{ $task->id }}, 'completed')">
                                        Mark Complete
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="updateTaskStatus({{ $task->id }}, 'in_progress')">
                                        Mark In Progress
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" class="text-red-600" wire:click="deleteTask({{ $task->id }})" wire:confirm="Are you sure?">
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </flux:card>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-gray-500">
                    <flux:icon name="clipboard-document-list" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                    <p>No tasks found</p>
                    <flux:button variant="primary" size="sm" class="mt-2" wire:click="$set('showNewTaskModal', true)">
                        Create First Task
                    </flux:button>
                </div>
                @endif
            </flux:tab.panel>

            <flux:tab.panel name="team" class="mt-6">
                <div class="flex justify-between items-center mb-4">
                    <flux:heading size="lg">Team Members</flux:heading>
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="$set('showAddMemberModal', true)">
                        Add Member
                    </flux:button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($project->members as $member)
                    <flux:card>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                    <flux:icon name="user" class="w-6 h-6 text-gray-500" />
                                </div>
                                <div>
                                    <div class="font-medium">{{ $member->user->name }}</div>
                                    <div class="text-sm text-gray-600">{{ $member->role ?? 'Team Member' }}</div>
                                </div>
                            </div>
                            @can('update', $project)
                            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="removeMember({{ $member->id }})" wire:confirm="Remove this team member?" />
                            @endcan
                        </div>
                    </flux:card>
                    @empty
                    <div class="col-span-full text-center py-8 text-gray-500">
                        <flux:icon name="users" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                        <p>No team members assigned</p>
                        <flux:button variant="primary" size="sm" class="mt-2" wire:click="$set('showAddMemberModal', true)">
                            Add First Member
                        </flux:button>
                    </div>
                    @endforelse
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="timeline" class="mt-6">
                <div class="h-[800px]">
                    <livewire:project.timeline :project="$project" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="budget" class="mt-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Budget Overview</flux:heading>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Total Budget</span>
                                <span class="font-medium">${{ number_format($project->budget ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Spent</span>
                                <span class="font-medium">${{ number_format($project->actual_cost ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Remaining</span>
                                <span class="font-medium">
                                    ${{ number_format(($project->budget ?? 0) - ($project->actual_cost ?? 0), 2) }}
                                </span>
                            </div>
                        </div>
                    </flux:card>

                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Recent Expenses</flux:heading>
                        
                        <div class="space-y-2">
                            @forelse($project->expenses->take(5) as $expense)
                            <div class="flex justify-between py-2 border-b last:border-0">
                                <div>
                                    <div class="text-sm font-medium">{{ $expense->description }}</div>
                                    <div class="text-xs text-gray-500">{{ $expense->created_at->format('M d, Y') }}</div>
                                </div>
                                <div class="font-medium">${{ number_format($expense->amount, 2) }}</div>
                            </div>
                            @empty
                            <div class="text-sm text-gray-500 text-center py-4">No expenses recorded</div>
                            @endforelse
                        </div>
                    </flux:card>
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="activity" class="mt-6">
                <flux:heading size="lg" class="mb-4">Project Activity</flux:heading>
                
                <div class="space-y-4">
                    @forelse($activity ?? [] as $act)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <flux:icon name="{{ $act['type'] === 'task' ? 'clipboard-document-list' : ($act['type'] === 'comment' ? 'chat-bubble-left' : 'document') }}" variant="mini" />
                        </div>
                        <div class="flex-1">
                            <div class="text-sm">
                                <span class="font-medium">{{ $act['user'] ?? 'System' }}</span>
                                {{ $act['title'] ?? '' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ isset($act['timestamp']) ? \Carbon\Carbon::parse($act['timestamp'])->format('M d, Y \a\t g:i A') : '' }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500">
                        <flux:icon name="clock" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                        <p>No activity recorded</p>
                    </div>
                    @endforelse
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="timeline" class="mt-6">
                @livewire('project.timeline', ['project' => $project])
            </flux:tab.panel>
        </flux:tab.group>
    </flux:card>

    <!-- New Task Modal -->
    <flux:modal wire:model="showNewTaskModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Create New Task</flux:heading>
                <flux:text>Add a new task to this project</flux:text>
            </div>
            
            <flux:input label="Task Name" wire:model="taskName" />
            
            <flux:textarea label="Description" wire:model="taskDescription" rows="3" />
            
            <flux:select label="Assignee" wire:model="taskAssignee">
                <flux:select.option value="">Unassigned</flux:select.option>
                @foreach($teamMembers as $user)
                <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                @endforeach
            </flux:select>
            
            <div class="grid grid-cols-2 gap-4">
                <flux:select label="Priority" wire:model="taskPriority">
                    <flux:select.option value="low">Low</flux:select.option>
                    <flux:select.option value="medium">Medium</flux:select.option>
                    <flux:select.option value="high">High</flux:select.option>
                </flux:select>
                
                <flux:select label="Status" wire:model="taskStatus">
                    <flux:select.option value="todo">To Do</flux:select.option>
                    <flux:select.option value="in_progress">In Progress</flux:select.option>
                    <flux:select.option value="review">Review</flux:select.option>
                    <flux:select.option value="completed">Completed</flux:select.option>
                </flux:select>
            </div>
            
            <flux:input type="date" label="Due Date" wire:model="taskDueDate" />
            
            <div class="flex">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showNewTaskModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createTask">Create Task</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Add Member Modal -->
    <flux:modal wire:model="showAddMemberModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Team Member</flux:heading>
                <flux:text>Add a new member to this project team</flux:text>
            </div>
            
            <flux:select label="Select User" wire:model="selectedUserId">
                <flux:select.option value="">Choose a user</flux:select.option>
                @foreach($availableUsers as $user)
                <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                @endforeach
            </flux:select>
            
            <flux:select label="Role" wire:model="memberRole">
                <flux:select.option value="member">Member</flux:select.option>
                <flux:select.option value="developer">Developer</flux:select.option>
                <flux:select.option value="lead">Lead</flux:select.option>
                <flux:select.option value="observer">Observer</flux:select.option>
            </flux:select>
            
            <div class="flex">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showAddMemberModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="addTeamMember">Add Member</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-confirm" wire:model="showDeleteConfirmModal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Project?</flux:heading>
                <flux:text class="mt-2">
                    <p>You're about to delete this project.</p>
                    <p>This action cannot be undone and will permanently delete all associated tasks, files, and data.</p>
                </flux:text>
            </div>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showDeleteConfirmModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="deleteProject">Delete Project</flux:button>
            </div>
        </div>
    </flux:modal>
</div>