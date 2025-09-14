<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <flux:toast variant="success">{{ session('message') }}</flux:toast>
    @endif
    @if (session()->has('error'))
        <flux:toast variant="danger">{{ session('error') }}</flux:toast>
    @endif

    {{-- Header --}}
    <flux:card>
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Ticket #{{ $ticket->ticket_number ?? $ticket->number }}</h1>
                    <h2 class="text-lg text-gray-600 mt-1">{{ $ticket->subject }}</h2>
                    <div class="flex items-center gap-4 mt-3">
                        <flux:badge variant="{{ 
                            $ticket->status === 'open' ? 'success' : 
                            ($ticket->status === 'in_progress' ? 'info' : 
                            ($ticket->status === 'resolved' ? 'warning' : 
                            ($ticket->status === 'closed' ? 'outline' : 'danger')))
                        }}">
                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                        </flux:badge>
                        <flux:badge variant="{{ 
                            $ticket->priority === 'urgent' ? 'danger' : 
                            ($ticket->priority === 'high' ? 'warning' : 
                            ($ticket->priority === 'medium' ? 'info' : 'outline'))
                        }}">
                            {{ ucfirst($ticket->priority) }} Priority
                        </flux:badge>
                        @if($ticket->billable)
                            <flux:badge variant="success">Billable</flux:badge>
                        @endif
                        @if($ticket->onsite)
                            <flux:badge variant="info">On-site</flux:badge>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <flux:button 
                        variant="{{ $isWatching ? 'warning' : 'outline' }}"
                        wire:click="toggleWatch"
                        size="sm"
                    >
                        <flux:icon.eye class="size-4" />
                        {{ $isWatching ? 'Watching' : 'Watch' }}
                    </flux:button>
                    
                    <flux:button variant="outline" href="{{ route('tickets.edit', $ticket) }}" size="sm">
                        <flux:icon.pencil class="size-4" />
                        Edit
                    </flux:button>
                    
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm">
                            <flux:icon.ellipsis-horizontal class="size-4" />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="$set('showStatusChangeModal', true)">
                                <flux:icon.arrow-path class="size-4" />
                                Change Status
                            </flux:menu.item>
                            <flux:menu.item href="{{ route('tickets.pdf', $ticket) }}" target="_blank">
                                <flux:icon.document-arrow-down class="size-4" />
                                Download PDF
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item variant="danger">
                                <flux:icon.trash class="size-4" />
                                Archive Ticket
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Ticket Details --}}
            <flux:card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Description</h3>
                    <div class="prose max-w-none">
                        {!! nl2br(e($ticket->details ?? 'No description provided.')) !!}
                    </div>
                </div>
            </flux:card>

            {{-- Comments Section --}}
            <flux:card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Comments & Activity</h3>
                    
                    {{-- Add Comment Form --}}
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <form wire:submit.prevent="addComment">
                            <flux:textarea
                                wire:model="comment"
                                placeholder="Add a comment..."
                                rows="3"
                                class="mb-3"
                            />
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <flux:checkbox wire:model="internalNote" id="internal-note">
                                        Internal Note
                                    </flux:checkbox>
                                    
                                    <flux:checkbox wire:model="timeTracking" id="time-tracking">
                                        Log Time
                                    </flux:checkbox>
                                </div>
                                
                                <flux:button type="submit" variant="primary" size="sm">
                                    <flux:icon.chat-bubble-left class="size-4" />
                                    Add Comment
                                </flux:button>
                            </div>
                            
                            @if($timeTracking)
                                <div class="grid grid-cols-2 gap-4 mt-4">
                                    <flux:input
                                        type="number"
                                        wire:model="timeSpent"
                                        placeholder="Hours spent"
                                        step="0.25"
                                        min="0"
                                    />
                                    <flux:input
                                        wire:model="timeDescription"
                                        placeholder="Time log description"
                                    />
                                </div>
                            @endif
                            
                            <div class="mt-3">
                                <flux:input
                                    type="file"
                                    wire:model="attachments"
                                    multiple
                                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx"
                                />
                                @error('attachments.*')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </form>
                    </div>
                    
                    {{-- Comments List --}}
                    <div class="space-y-4">
                        @forelse($ticket->comments ?? [] as $comment)
                            <div class="flex gap-3 {{ $comment->is_internal ? 'bg-yellow-50 p-3 rounded' : '' }}">
                                <flux:avatar size="sm">
                                    {{ substr($comment->user?->name ?? 'S', 0, 2) }}
                                </flux:avatar>
                                
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-medium">{{ $comment->user?->name ?? 'System' }}</span>
                                            <span class="text-sm text-gray-500 ml-2">
                                                {{ $comment->created_at?->diffForHumans() ?? '' }}
                                            </span>
                                            @if($comment->is_internal)
                                                <flux:badge variant="warning" size="xs" class="ml-2">Internal</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if($comment->user_id === Auth::id() && $comment->created_at?->diffInMinutes(now()) < 30)
                                            <flux:button
                                                variant="ghost"
                                                size="xs"
                                                wire:click="deleteComment({{ $comment->id }})"
                                                wire:confirm="Delete this comment?"
                                            >
                                                <flux:icon.trash class="size-3" />
                                            </flux:button>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-1 text-gray-700">
                                        {!! nl2br(e($comment->body)) !!}
                                    </div>
                                    
                                    @if($comment->attachments && count($comment->attachments) > 0)
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach($comment->attachments as $attachment)
                                                <a href="{{ Storage::url($attachment->path) }}" 
                                                   target="_blank"
                                                   class="text-blue-600 hover:underline text-sm flex items-center gap-1">
                                                    <flux:icon.paper-clip class="size-3" />
                                                    {{ $attachment->filename }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">No comments yet.</p>
                        @endforelse
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <flux:card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    
                    {{-- Status --}}
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700">Status</label>
                        <flux:select wire:model="status" wire:change="updateStatus" class="mt-1">
                            @foreach($statuses as $statusOption)
                                <option value="{{ $statusOption }}">
                                    {{ ucfirst(str_replace('_', ' ', $statusOption)) }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>
                    
                    {{-- Priority --}}
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700">Priority</label>
                        <flux:select wire:model="priority" wire:change="updatePriority" class="mt-1">
                            @foreach($priorities as $priorityOption)
                                <option value="{{ $priorityOption }}">
                                    {{ ucfirst($priorityOption) }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>
                    
                    {{-- Assignee --}}
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700">Assigned To</label>
                        <flux:select wire:model="assignedTo" wire:change="updateAssignee" class="mt-1">
                            <option value="">Unassigned</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </flux:card>

            {{-- Ticket Information --}}
            <flux:card>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Information</h3>
                    
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Client</dt>
                            <dd class="text-sm">
                                @if($ticket->client)
                                    <a href="{{ route('clients.show', $ticket->client) }}" class="text-blue-600 hover:underline">
                                        {{ $ticket->client->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400">No client</span>
                                @endif
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Contact</dt>
                            <dd class="text-sm">{{ $ticket->contact?->name ?? 'No contact' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Requester</dt>
                            <dd class="text-sm">{{ $ticket->requester?->name ?? 'Unknown' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="text-sm">{{ $ticket->created_at?->format('M d, Y g:i A') ?? '-' }}</dd>
                        </div>
                        
                        @if($ticket->scheduled_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Scheduled</dt>
                                <dd class="text-sm">{{ $ticket->scheduled_at->format('M d, Y g:i A') }}</dd>
                            </div>
                        @endif
                        
                        @if($ticket->closed_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Closed</dt>
                                <dd class="text-sm">{{ $ticket->closed_at->format('M d, Y g:i A') }}</dd>
                            </div>
                        @endif
                        
                        @if($ticket->asset)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Asset</dt>
                                <dd class="text-sm">
                                    <a href="{{ route('assets.show', $ticket->asset) }}" class="text-blue-600 hover:underline">
                                        {{ $ticket->asset->name }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                        
                        @if($ticket->project)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Project</dt>
                                <dd class="text-sm">
                                    <a href="{{ route('projects.show', $ticket->project) }}" class="text-blue-600 hover:underline">
                                        {{ $ticket->project->name }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </flux:card>

            {{-- Time Tracking --}}
            @if($ticket->timeLogs && count($ticket->timeLogs) > 0)
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Time Tracking</h3>
                        
                        <div class="space-y-2">
                            @php
                                $totalMinutes = $ticket->timeLogs->sum('minutes');
                                $hours = floor($totalMinutes / 60);
                                $minutes = $totalMinutes % 60;
                            @endphp
                            
                            <div class="text-2xl font-bold">
                                {{ $hours }}h {{ $minutes }}m
                            </div>
                            <div class="text-sm text-gray-500">Total time logged</div>
                            
                            <div class="border-t pt-3 mt-3 space-y-2">
                                @foreach($ticket->timeLogs->take(5) as $log)
                                    <div class="text-sm">
                                        <span class="font-medium">{{ $log->user?->name }}</span>
                                        <span class="text-gray-500">
                                            {{ floor($log->minutes / 60) }}h {{ $log->minutes % 60 }}m
                                        </span>
                                        @if($log->description)
                                            <div class="text-xs text-gray-500">{{ $log->description }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Watchers --}}
            @if($ticket->watchers && count($ticket->watchers) > 0)
                <flux:card>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Watchers</h3>
                        
                        <div class="flex flex-wrap gap-2">
                            @foreach($ticket->watchers as $watcher)
                                <flux:badge variant="outline">
                                    {{ $watcher->user?->name ?? 'Unknown' }}
                                </flux:badge>
                            @endforeach
                        </div>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>

    {{-- Status Change Modal --}}
    <flux:modal wire:model="showStatusChangeModal" title="Change Ticket Status">
        <form wire:submit.prevent="updateStatus">
            <div class="space-y-4">
                <flux:select wire:model="newStatus" label="New Status" required>
                    <option value="">Select status...</option>
                    @foreach($statuses as $statusOption)
                        <option value="{{ $statusOption }}">
                            {{ ucfirst(str_replace('_', ' ', $statusOption)) }}
                        </option>
                    @endforeach
                </flux:select>
                
                <flux:textarea
                    wire:model="statusChangeReason"
                    label="Reason for Change"
                    placeholder="Explain why you're changing the status..."
                    rows="3"
                    required
                />
            </div>
            
            <div class="flex justify-end gap-2 mt-6">
                <flux:button variant="ghost" wire:click="$set('showStatusChangeModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Update Status
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>