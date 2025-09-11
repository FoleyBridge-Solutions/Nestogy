@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->number)

@section('content')
<div class="w-full max-w-7xl mx-auto">
    <!-- Ticket Header -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <flux:heading size="xl">Ticket #{{ $ticket->number }}</flux:heading>
                @if($ticket->is_resolved)
                    <flux:badge color="emerald" size="sm">
                        <flux:icon name="check-circle" variant="mini" class="mr-1" />
                        Resolved
                    </flux:badge>
                @endif
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                @if($ticket->is_resolved && !$ticket->isClosed())
                    @can('reopen', $ticket)
                        <flux:modal.trigger name="reopen-modal">
                            <flux:button variant="ghost" size="sm" icon="arrow-path">
                                Reopen Ticket
                            </flux:button>
                        </flux:modal.trigger>
                    @endcan
                @elseif(!$ticket->is_resolved && !$ticket->isClosed())
                    @can('resolve', $ticket)
                        <flux:modal.trigger name="resolve-modal">
                            <flux:button variant="primary" size="sm" icon="check-circle">
                                Resolve Ticket
                            </flux:button>
                        </flux:modal.trigger>
                    @endcan
                @endif
                
                @can('update', $ticket)
                    <flux:button href="{{ route('tickets.edit', $ticket) }}" variant="ghost" size="sm" icon="pencil">
                        Edit
                    </flux:button>
                @endcan

                <!-- Quick Actions Dropdown -->
                @if(!$ticket->isClosed())
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon-trailing="chevron-down">
                            Quick Actions
                        </flux:button>
                        
                        <flux:menu>
                            @can('assign', $ticket)
                                <flux:menu.item icon="user-plus" flux:modal.open="assign-technician">
                                    Assign Technician
                                </flux:menu.item>
                            @endcan
                            
                            @can('updatePriority', $ticket)
                                <flux:menu.item icon="flag">
                                    Change Priority
                                </flux:menu.item>
                            @endcan
                            
                            @can('schedule', $ticket)
                                <flux:menu.item icon="calendar">
                                    Schedule Work
                                </flux:menu.item>
                            @endcan
                            
                            <flux:menu.separator />
                            
                            <flux:menu.item icon="printer" onclick="window.print()">
                                Print Ticket
                            </flux:menu.item>
                            
                            @can('merge', $ticket)
                                <flux:menu.item icon="arrows-pointing-in">
                                    Merge with Another Ticket
                                </flux:menu.item>
                            @endcan
                            
                            @can('clone', $ticket)
                                <flux:menu.item icon="document-duplicate">
                                    Clone Ticket
                                </flux:menu.item>
                            @endcan
                        </flux:menu>
                    </flux:dropdown>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ticket Subject & Details Card -->
            <flux:card>
                <div class="space-y-4">
                    <!-- Subject with Status Badges -->
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <flux:heading size="lg" class="flex-1">{{ $ticket->subject }}</flux:heading>
                        <div class="flex items-center gap-2">
                            <flux:badge 
                                :color="match(strtolower($ticket->priority)) {
                                    'low' => 'green',
                                    'medium' => 'amber',
                                    'high' => 'orange',
                                    'critical' => 'red',
                                    default => 'zinc'
                                }"
                                size="sm">
                                {{ $ticket->priority }}
                            </flux:badge>
                            <flux:badge 
                                :color="match(strtolower($ticket->status)) {
                                    'open' => 'blue',
                                    'in progress' => 'indigo',
                                    'on hold' => 'amber',
                                    'waiting' => 'purple',
                                    'closed' => 'zinc',
                                    default => 'gray'
                                }"
                                size="sm">
                                {{ $ticket->status }}
                            </flux:badge>
                        </div>
                    </div>

                    <flux:separator />

                    <!-- Ticket Details -->
                    <div>
                        <flux:heading size="sm" class="mb-3">Details</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-800">
                            <flux:text class="whitespace-pre-wrap">{{ $ticket->details ?: 'No details provided.' }}</flux:text>
                        </div>
                    </div>

                    <!-- Resolution Summary (if resolved) -->
                    @if($ticket->is_resolved && $ticket->resolution_summary)
                        <div>
                            <flux:heading size="sm" class="mb-3 flex items-center gap-2">
                                <flux:icon name="check-circle" variant="mini" class="text-emerald-600" />
                                Resolution
                            </flux:heading>
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 border border-emerald-200 dark:border-emerald-800">
                                <flux:text class="whitespace-pre-wrap">{{ $ticket->resolution_summary }}</flux:text>
                                <div class="mt-2 flex items-center gap-4">
                                    <flux:text size="sm" variant="subtle">
                                        Resolved by {{ $ticket->resolver->name ?? 'System' }}
                                    </flux:text>
                                    <flux:text size="sm" variant="subtle">
                                        {{ $ticket->resolved_at->format('M d, Y g:i A') }}
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Comments Section -->
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">Comments</flux:heading>
                    <flux:text size="sm" variant="subtle">
                        {{ $ticket->comments->count() }} {{ Str::plural('comment', $ticket->comments->count()) }}
                    </flux:text>
                </div>

                <div class="space-y-4">
                    @forelse($ticket->comments()->with(['author', 'timeEntry'])->orderBy('created_at', 'desc')->get() as $comment)
                        <div class="group relative {{ $comment->visibility == 'internal' ? 'bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800' : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800' }} border rounded-lg overflow-hidden">
                            <!-- Comment Header -->
                            <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 border-b {{ $comment->visibility == 'internal' ? 'border-amber-200 dark:border-amber-800' : 'border-zinc-200 dark:border-zinc-800' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                            <flux:icon name="user" variant="mini" class="text-zinc-600 dark:text-zinc-400" />
                                        </div>
                                        <div>
                                            <flux:text class="font-medium">
                                                {{ $comment->author->name ?? 'System' }}
                                            </flux:text>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <flux:text size="xs" variant="subtle">
                                                    {{ $comment->created_at->format('M d, Y g:i A') }}
                                                </flux:text>
                                                @if($comment->visibility == 'internal')
                                                    <flux:badge color="amber" size="xs">Internal</flux:badge>
                                                @endif
                                                @if($comment->is_resolution)
                                                    <flux:badge color="emerald" size="xs">Resolution</flux:badge>
                                                @endif
                                                @if($comment->source != 'manual')
                                                    <flux:badge color="zinc" size="xs">{{ ucfirst($comment->source) }}</flux:badge>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if($comment->timeEntry)
                                        <flux:badge color="blue" size="sm">
                                            <flux:icon name="clock" variant="mini" class="mr-1" />
                                            {{ number_format($comment->timeEntry->hours_worked, 2) }}h
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Comment Body -->
                            <div class="p-4">
                                <flux:text class="whitespace-pre-wrap">{{ $comment->content }}</flux:text>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <flux:icon name="chat-bubble-left-right" variant="outline" class="w-12 h-12 mx-auto text-zinc-400 mb-3" />
                            <flux:text variant="subtle">No comments yet.</flux:text>
                        </div>
                    @endforelse
                </div>
            </flux:card>

            <!-- Add Comment Form (if not closed) -->
            @if(!$ticket->isClosed())
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Add Comment</flux:heading>

                    <form action="{{ route('tickets.comments.store', $ticket) }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <flux:textarea 
                            name="content" 
                            label="Comment"
                            rows="4" 
                            placeholder="Enter your comment..."
                            required />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <flux:select name="visibility" label="Visibility">
                                <flux:select.option value="public">Public (visible to client)</flux:select.option>
                                <flux:select.option value="internal">Internal (staff only)</flux:select.option>
                            </flux:select>

                            <flux:input 
                                type="number" 
                                name="time_minutes" 
                                label="Time Spent (minutes)"
                                placeholder="90"
                                min="0"
                                max="480" />
                        </div>

                        <flux:checkbox name="billable" label="Mark time as billable" checked />

                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary">
                                <flux:icon name="plus" variant="mini" />
                                Add Comment
                            </flux:button>
                        </div>
                    </form>
                </flux:card>
            @else
                <flux:card>
                    <div class="text-center py-4">
                        <flux:icon name="lock-closed" class="w-8 h-8 mx-auto text-zinc-400 mb-2" />
                        <flux:text variant="subtle">This ticket is closed and cannot accept new comments.</flux:text>
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- Right Column: Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            @if(!$ticket->isClosed())
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Quick Actions</flux:heading>
                    <div class="space-y-2">
                        @can('assign', $ticket)
                            <flux:button 
                                variant="ghost" 
                                size="sm" 
                                class="w-full justify-start"
                                flux:modal.open="assign-technician"
                            >
                                <flux:icon name="user-plus" variant="mini" />
                                Assign Technician
                            </flux:button>
                        @endcan
                        
                        @can('updatePriority', $ticket)
                            <flux:button variant="ghost" size="sm" class="w-full justify-start">
                                <flux:icon name="flag" variant="mini" />
                                Change Priority
                            </flux:button>
                        @endcan
                        
                        @can('schedule', $ticket)
                            <flux:button variant="ghost" size="sm" class="w-full justify-start">
                                <flux:icon name="calendar" variant="mini" />
                                Schedule Work
                            </flux:button>
                        @endcan
                        
                        <flux:button variant="ghost" size="sm" class="w-full justify-start">
                            <flux:icon name="printer" variant="mini" />
                            Print Ticket
                        </flux:button>
                    </div>
                </flux:card>
            @endif

            <!-- Time Tracking -->
            @livewire('ticket-time-tracker', ['ticket' => $ticket])

            <!-- Ticket Information -->
            <flux:card>
                <flux:heading size="sm" class="mb-4">Information</flux:heading>
                
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Client</dt>
                        <dd class="mt-1">
                            <a href="{{ route('clients.index', ['client' => $ticket->client_id]) }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                {{ $ticket->client->name }}
                            </a>
                        </dd>
                    </div>

                    @if($ticket->contact)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Contact</dt>
                            <dd class="mt-1">{{ $ticket->contact->name }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Created</dt>
                        <dd class="mt-1 text-sm">{{ $ticket->created_at->format('M d, Y g:i A') }}</dd>
                    </div>

                    @if($ticket->is_resolved)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Resolved</dt>
                            <dd class="mt-1 text-sm">{{ $ticket->resolved_at->format('M d, Y g:i A') }}</dd>
                        </div>
                    @endif

                    @if($ticket->closed_at)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Closed</dt>
                            <dd class="mt-1 text-sm">{{ $ticket->closed_at->format('M d, Y g:i A') }}</dd>
                        </div>
                    @endif
                </dl>
            </flux:card>

            <!-- Assignment Information -->
            <flux:card>
                <flux:heading size="sm" class="mb-4">Assignment</flux:heading>
                
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Created By</dt>
                        <dd class="mt-1">{{ $ticket->creator->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Assigned To</dt>
                        <dd class="mt-1">
                            @if($ticket->assignee)
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <flux:icon name="user" variant="micro" class="text-zinc-600 dark:text-zinc-400" />
                                    </div>
                                    {{ $ticket->assignee->name }}
                                </div>
                            @else
                                <span class="text-zinc-400 italic">Unassigned</span>
                            @endif
                        </dd>
                    </div>

                    @if($ticket->category)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Category</dt>
                            <dd class="mt-1">
                                <flux:badge size="sm">{{ $ticket->category }}</flux:badge>
                            </dd>
                        </div>
                    @endif
                </dl>
            </flux:card>

            <!-- Time Tracking Summary -->
            @if($ticket->timeEntries->count() > 0)
                <flux:card>
                    <flux:heading size="sm" class="mb-4">Time Tracking</flux:heading>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <flux:text size="sm" variant="subtle">Total Time</flux:text>
                            <flux:text class="font-medium">{{ number_format($ticket->getTotalTimeWorked(), 2) }}h</flux:text>
                        </div>
                        <div class="flex justify-between items-center">
                            <flux:text size="sm" variant="subtle">Billable Time</flux:text>
                            <flux:text class="font-medium text-emerald-600">{{ number_format($ticket->getBillableTimeWorked(), 2) }}h</flux:text>
                        </div>
                        <flux:separator />
                        <div class="text-center">
                            <flux:button href="{{ route('tickets.time-tracking.index', ['ticket' => $ticket->id]) }}" variant="ghost" size="sm" class="w-full">
                                View Time Entries
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>
</div>

<!-- Resolve Modal -->
@can('resolve', $ticket)
<flux:modal name="resolve-modal" class="max-w-lg">
    <form action="{{ route('tickets.resolve', $ticket) }}" method="POST">
        @csrf
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Resolve Ticket</flux:heading>
            </div>
            
            <div class="space-y-4">
                <flux:textarea 
                    name="resolution_summary" 
                    label="Resolution Summary"
                    rows="4"
                    placeholder="Describe how the issue was resolved..."
                    required />
                
                <flux:checkbox 
                    name="allow_client_reopen" 
                    label="Allow client to reopen this ticket"
                    checked />
                
                <flux:select name="send_notification" label="Client Notification">
                    <flux:select.option value="1">Send resolution email to client</flux:select.option>
                    <flux:select.option value="0">Do not notify client</flux:select.option>
                </flux:select>
            </div>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Resolve Ticket</flux:button>
            </div>
        </div>
    </form>
</flux:modal>
@endcan

<!-- Reopen Modal -->
@can('reopen', $ticket)
<flux:modal name="reopen-modal" class="max-w-lg">
    <form action="{{ route('tickets.reopen', $ticket) }}" method="POST">
        @csrf
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Reopen Ticket</flux:heading>
            </div>
            
            <div class="space-y-4">
                <flux:textarea 
                    name="reason" 
                    label="Reason for Reopening (Optional)"
                    rows="3"
                    placeholder="Explain why the ticket needs to be reopened..." />
                
                <flux:text size="sm" variant="subtle">
                    The ticket will be reopened and set to "Open" status. The assigned technician will be notified.
                </flux:text>
            </div>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    <flux:icon name="arrow-path" variant="mini" />
                    Reopen Ticket
                </flux:button>
            </div>
        </div>
    </form>
</flux:modal>
@endcan

<!-- Include Assignment Modal -->
@include('tickets.assign-modal', ['ticket' => $ticket])

@endsection
    

