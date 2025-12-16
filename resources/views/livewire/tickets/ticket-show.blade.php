<div wire:poll.30s="refreshTicketData">
    {{-- Main Content Container --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {{-- Left: Main Content Area --}}
        <div class="lg:col-span-2 xl:col-span-3 space-y-6">
            {{-- Header Card --}}
            <flux:card class="space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0 space-y-3">
                        {{-- Ticket Number & Title --}}
                        <div class="flex items-baseline gap-2">
                            <flux:heading size="xl">#{{ $ticket->ticket_number ?? $ticket->number }}</flux:heading>
                            <flux:separator vertical class="h-6" />
                            <flux:heading size="lg">{{ $ticket->subject }}</flux:heading>
                        </div>

                        {{-- Badges Row --}}
                        <div class="flex flex-wrap items-center gap-2">
                            @if($ticket->is_internal)
                                <flux:badge variant="solid" size="sm" color="amber" icon="building-office">
                                    Internal
                                </flux:badge>
                            @endif

                            <flux:badge variant="solid" size="sm" color="{{
                                match(strtolower($ticket->status)) {
                                    'closed' => 'zinc',
                                    'open' => 'blue',
                                    'in_progress', 'in progress' => 'indigo',
                                    'pending', 'on hold' => 'amber',
                                    'resolved' => 'green',
                                    default => 'red'
                                }
                            }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </flux:badge>

                            <flux:badge variant="solid" size="sm" color="{{
                                match(strtolower($ticket->priority)) {
                                    'critical' => 'red',
                                    'urgent' => 'orange',
                                    'high' => 'amber',
                                    'medium' => 'yellow',
                                    'low' => 'zinc',
                                    default => 'zinc'
                                }
                            }}">
                                {{ ucfirst($ticket->priority) }}
                            </flux:badge>

                            <flux:separator vertical class="h-4" />

                            <flux:text size="sm">
                                <strong>Client:</strong>
                                @if($ticket->is_internal)
                                    <span class="text-amber-600 dark:text-amber-400 font-medium">Internal</span>
                                @elseif($ticket->client)
                                    <flux:link href="{{ route('clients.show', $ticket->client) }}">
                                        {{ $ticket->client->name }}
                                    </flux:link>
                                @else
                                    -
                                @endif
                            </flux:text>

                            <flux:separator vertical class="h-4" />

                            <flux:text size="sm">
                                <strong>Contact:</strong> {{ $ticket->contact?->name ?? '-' }}
                            </flux:text>

                            <flux:separator vertical class="h-4" />

                            <flux:text size="sm">
                                <strong>Assignee:</strong> {{ $ticket->assignee?->name ?? 'Unassigned' }}
                            </flux:text>
                        </div>

                        {{-- Metadata Row --}}
                        <div class="flex flex-wrap items-center gap-3">
                            <flux:text size="xs" variant="subtle">
                                <strong>Age:</strong> {{ $ticket->created_at->diffForHumans(null, true) }}
                            </flux:text>
                            <flux:text size="xs" variant="subtle">
                                <strong>Comments:</strong> {{ $ticket->comments->count() }}
                            </flux:text>
                            <flux:text size="xs" variant="subtle">
                                <strong>Updated:</strong> {{ $ticket->updated_at->diffForHumans() }}
                            </flux:text>
                            @php
                                $totalMinutes = $ticket->timeLogs->sum('minutes_worked') ?? 0;
                            @endphp
                            @if($totalMinutes > 0)
                                <flux:text size="xs" variant="subtle">
                                    <strong>Time:</strong> {{ floor($totalMinutes / 60) }}h {{ $totalMinutes % 60 }}m
                                </flux:text>
                            @endif
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-2">
                        @if($activeTimer)
                            <flux:button variant="filled" color="emerald" size="sm" wire:poll.1s="updateElapsedTime">
                                {{ $elapsedTime }}
                            </flux:button>
                            <flux:button variant="danger" size="sm" wire:click="stopTimer">Stop</flux:button>
                        @else
                            <flux:button variant="filled" color="emerald" size="sm" wire:click="startTimer" icon="play">
                                Start Timer
                            </flux:button>
                        @endif

                        <flux:button variant="ghost" size="sm" icon="eye" wire:click="toggleWatch" 
                            class="{{ $isWatching ? 'text-yellow-500' : '' }}" />

                        <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('tickets.edit', $ticket) }}" />

                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                            <flux:menu>
                                <flux:menu.item wire:click="$set('showStatusChangeModal', true)" icon="arrow-path">
                                    Change Status
                                </flux:menu.item>
                                <flux:menu.item wire:click="cloneTicket" icon="document-duplicate">
                                    Clone
                                </flux:menu.item>
                                <flux:menu.item href="{{ route('tickets.pdf', $ticket) }}" target="_blank" icon="document-arrow-down">
                                    PDF
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item wire:click="archiveTicket" icon="archive-box" variant="danger">
                                    Archive
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </flux:card>

            {{-- Description Card --}}
            <flux:card class="space-y-4">
                <flux:heading size="lg">Description</flux:heading>
                <flux:text>{{ $ticket->details ?? $ticket->description ?? 'No description provided.' }}</flux:text>

                @if($ticket->resolution_summary)
                    <flux:separator />
                    <div>
                        <flux:heading size="sm" class="flex items-center gap-2 mb-2">
                            <flux:icon.check-circle class="text-green-600" />
                            Resolution
                        </flux:heading>
                        <flux:text>{{ $ticket->resolution_summary }}</flux:text>
                        <flux:text size="xs" variant="subtle" class="mt-2">
                            Resolved by {{ $ticket->resolver->name ?? 'System' }} • {{ $ticket->resolved_at->format('M d, Y g:i A') }}
                        </flux:text>
                    </div>
                @endif
            </flux:card>

            {{-- Activity Timeline Card --}}
            <flux:card class="space-y-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Activity Timeline</flux:heading>
                    <flux:button variant="ghost" size="xs">Hide Internal</flux:button>
                </div>

                {{-- Comment Form --}}
                <form wire:submit.prevent="addComment" class="space-y-4">
                    <div class="relative">
                        <flux:textarea
                            wire:model.live.debounce.500ms="comment"
                            placeholder="Add comment..."
                            rows="3"
                        />
                        @if($draftSaved)
                            <div class="absolute top-2 right-2">
                                <flux:badge color="green" size="xs">
                                    <flux:icon.check class="size-3" /> Draft saved
                                </flux:badge>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <flux:checkbox wire:model.live="internalNote" label="Internal" />
                            <flux:checkbox wire:model.live="timeTracking" label="Log Time" />
                            @if(in_array($ticket->status, ['resolved', 'closed']))
                                <flux:checkbox wire:model.live="reopenOnComment" label="Reopen" />
                            @endif
                            
                            <flux:button variant="ghost" size="xs" type="button" as="label">
                                <flux:icon.paper-clip /> Choose files
                                <input type="file" wire:model="attachments" multiple 
                                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="hidden" />
                            </flux:button>
                        </div>

                        <flux:button type="submit" variant="primary" size="sm" wire:loading.attr="disabled" wire:target="addComment">
                            <span wire:loading.remove wire:target="addComment" class="flex items-center gap-1">
                                <flux:icon.plus variant="micro" />
                                Comment
                            </span>
                            <span wire:loading wire:target="addComment" class="flex items-center gap-1">
                                <flux:icon.arrow-path variant="micro" class="animate-spin" />
                                Posting...
                            </span>
                        </flux:button>
                    </div>
                    
                    @if($timeTracking)
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                type="number"
                                wire:model="timeSpent"
                                label="Hours"
                                placeholder="1.5"
                                step="0.25"
                                min="0"
                            />
                            <flux:input
                                wire:model="timeDescription"
                                label="Description"
                                placeholder="What did you work on?"
                            />
                        </div>
                    @endif
                    
                    @if($attachments)
                        <div wire:loading wire:target="attachments">
                            <flux:text size="xs" class="flex items-center gap-1">
                                <flux:icon.arrow-path class="animate-spin" /> Uploading files...
                            </flux:text>
                        </div>
                    @endif
                    @error('attachments.*')
                        <flux:text size="xs" variant="danger">{{ $message }}</flux:text>
                    @enderror
                </form>

                <flux:separator />

                {{-- Comments List --}}
                <div class="space-y-4">
                    @forelse($ticket->comments->sortByDesc('created_at') ?? [] as $comment)
                        <flux:card class="group {{ $comment->is_internal ? '!bg-amber-50 dark:!bg-amber-900/10' : '' }}">
                            <div class="flex items-start gap-3">
                                <flux:avatar size="sm">{{ substr($comment->author?->name ?? 'S', 0, 2) }}</flux:avatar>

                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <flux:text variant="strong">{{ $comment->author?->name ?? 'System' }}</flux:text>
                                            <flux:text size="sm" variant="subtle">
                                                {{ $comment->created_at?->format('M d, Y g:i A') ?? '' }}
                                            </flux:text>
                                            @if($comment->is_internal)
                                                <flux:badge color="amber" size="xs">Internal</flux:badge>
                                            @endif
                                            @if($comment->is_system)
                                                <flux:badge color="zinc" size="xs">System</flux:badge>
                                            @endif
                                        </div>

                                        @if($comment->author_id === Auth::id() && $comment->created_at?->diffInMinutes(now()) < 30)
                                            <flux:dropdown>
                                                <flux:button variant="ghost" size="xs" class="opacity-0 group-hover:opacity-100">
                                                    <flux:icon.ellipsis-horizontal />
                                                </flux:button>
                                                <flux:menu>
                                                    <flux:menu.item wire:click="editComment({{ $comment->id }})" icon="pencil">Edit</flux:menu.item>
                                                    <flux:menu.item wire:click="deleteComment({{ $comment->id }})" icon="trash" variant="danger">Delete</flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        @endif
                                    </div>

                                    @if($editingCommentId === $comment->id)
                                        <div class="space-y-2">
                                            <flux:textarea wire:model.live="editingCommentText" rows="3" />
                                            <div class="flex gap-2">
                                                <flux:button wire:click="updateComment" variant="primary" size="xs">Save</flux:button>
                                                <flux:button wire:click="cancelEditComment" variant="ghost" size="xs">Cancel</flux:button>
                                            </div>
                                        </div>
                                    @else
                                        <flux:text>{!! nl2br(e($comment->content)) !!}</flux:text>
                                    @endif

                                    @if($comment->attachments && count($comment->attachments) > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($comment->attachments as $attachment)
                                                <flux:link href="{{ Storage::url($attachment->path) }}" target="_blank">
                                                    <flux:icon.paper-clip class="size-3" /> {{ $attachment->filename }}
                                                </flux:link>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </flux:card>
                    @empty
                        <div class="text-center py-12">
                            <flux:icon.chat-bubble-left-right class="w-12 h-12 mx-auto opacity-20 mb-3" />
                            <flux:text variant="subtle">No comments yet. Be the first to comment!</flux:text>
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>

        {{-- Right: Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Quick Actions Card --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm">Quick Actions</flux:heading>
                
                <div class="space-y-3">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <div class="relative">
                            <flux:select wire:model="status" wire:change="updateStatus">
                                @foreach($statuses as $statusOption)
                                    <option value="{{ $statusOption }}">{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                                @endforeach
                            </flux:select>
                            <div wire:loading wire:target="updateStatus" class="absolute right-2 top-2.5">
                                <flux:icon.arrow-path class="size-3 animate-spin" />
                            </div>
                        </div>
                    </flux:field>

                    <flux:field>
                        <flux:label>Priority</flux:label>
                        <div class="relative">
                            <flux:select wire:model="priority" wire:change="updatePriority">
                                @foreach($priorities as $priorityOption)
                                    <option value="{{ $priorityOption }}">{{ ucfirst($priorityOption) }}</option>
                                @endforeach
                            </flux:select>
                            <div wire:loading wire:target="updatePriority" class="absolute right-2 top-2.5">
                                <flux:icon.arrow-path class="size-3 animate-spin" />
                            </div>
                        </div>
                    </flux:field>

                    <flux:field>
                        <flux:label>Assignee</flux:label>
                        <div class="relative">
                            <flux:select wire:model="assignedTo" wire:change="updateAssignee">
                                <option value="">Unassigned</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                @endforeach
                            </flux:select>
                            <div wire:loading wire:target="updateAssignee" class="absolute right-2 top-2.5">
                                <flux:icon.arrow-path class="size-3 animate-spin" />
                            </div>
                        </div>
                    </flux:field>
                </div>
            </flux:card>

            {{-- Details Card --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm">Details</flux:heading>
                
                <div class="space-y-3">
                    <div>
                        <flux:subheading>Created</flux:subheading>
                        <flux:text size="sm">{{ $ticket->created_at->format('M d, Y g:i A') }}</flux:text>
                    </div>

                    @if($ticket->closed_at)
                        <div>
                            <flux:subheading>Closed</flux:subheading>
                            <flux:text size="sm">{{ $ticket->closed_at->format('M d, Y g:i A') }}</flux:text>
                        </div>
                    @endif

                    <div>
                        <flux:subheading>Requester</flux:subheading>
                        <flux:text size="sm">{{ $ticket->requester?->name ?? 'Unknown' }}</flux:text>
                    </div>

                    @if($ticket->category)
                        <div>
                            <flux:subheading>Category</flux:subheading>
                            <flux:badge size="sm">{{ $ticket->category }}</flux:badge>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Time & SLA Card --}}
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Time & SLA</flux:heading>
                    <flux:button variant="ghost" size="xs" wire:click="$set('showTimeEntryModal', true)" icon="plus" />
                </div>

                @php
                    $totalMinutes = $ticket->timeLogs->sum('minutes_worked') ?? 0;
                    $billableMinutes = $ticket->timeLogs->where('billable', true)->sum('minutes_worked') ?? 0;
                @endphp

                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <flux:text size="sm" variant="subtle">Total Time</flux:text>
                        <flux:text variant="strong">{{ floor($totalMinutes / 60) }}h {{ $totalMinutes % 60 }}m</flux:text>
                    </div>
                    @if($billableMinutes > 0)
                        <div class="flex justify-between items-center">
                            <flux:text size="sm" variant="subtle">Billable</flux:text>
                            <flux:text size="sm" color="green">{{ floor($billableMinutes / 60) }}h {{ $billableMinutes % 60 }}m</flux:text>
                        </div>
                    @endif

                    @if($ticket->priorityQueue && $ticket->priorityQueue->sla_deadline)
                        <flux:separator />
                        @php
                            $deadline = \Carbon\Carbon::parse($ticket->priorityQueue->sla_deadline);
                            $isBreached = now()->isAfter($deadline);
                        @endphp
                        <div>
                            <flux:text size="sm" variant="subtle">SLA: {{ $isBreached ? 'Breached' : 'Due' }}</flux:text>
                            <flux:text size="xs" class="{{ $isBreached ? 'text-red-600' : 'text-green-600' }}">
                                {{ $deadline->format('M d, g:i A') }}
                            </flux:text>
                        </div>
                    @endif

                    @if($ticket->timeLogs->count() > 0)
                        <flux:separator />
                        <flux:subheading>Recent Entries</flux:subheading>
                        @foreach($ticket->timeLogs->take(3) as $log)
                            <div class="flex justify-between">
                                <flux:text size="xs" variant="subtle">{{ $log->user?->name }}</flux:text>
                                <flux:text size="xs">{{ floor(($log->minutes_worked ?? 0) / 60) }}h {{ ($log->minutes_worked ?? 0) % 60 }}m</flux:text>
                            </div>
                        @endforeach
                    @endif
                </div>
            </flux:card>

            {{-- Related Card --}}
            <flux:card class="space-y-4">
                <flux:heading size="sm">Related</flux:heading>

                @if($ticket->watchers->count() > 0)
                    <div>
                        <flux:subheading>Watchers ({{ $ticket->watchers->count() }})</flux:subheading>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($ticket->watchers->take(3) as $watcher)
                                <flux:badge size="xs">{{ $watcher->user?->name }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                @endif

                @php
                    $attachmentCount = $ticket->comments->flatMap->attachments->count();
                    $relatedCount = App\Domains\Ticket\Models\Ticket::where('company_id', $ticket->company_id)
                        ->where('id', '!=', $ticket->id)
                        ->where('client_id', $ticket->client_id)
                        ->count();
                @endphp

                @if($attachmentCount > 0)
                    <flux:text size="sm">
                        <flux:icon.paper-clip class="inline size-3" /> 
                        {{ $attachmentCount }} {{ Str::plural('file', $attachmentCount) }}
                    </flux:text>
                @endif

                @if($relatedCount > 0)
                    <flux:link href="{{ route('tickets.index', ['client_id' => $ticket->client_id]) }}">
                        {{ $relatedCount }} related {{ Str::plural('ticket', $relatedCount) }}
                    </flux:link>
                @endif
            </flux:card>
        </div>
    </div>

    {{-- Modals --}}
    @livewire('timer-completion-modal')

    <flux:modal name="status-change" wire:model="showStatusChangeModal">
        <form wire:submit.prevent="updateStatus" class="space-y-6">
            <flux:heading size="lg">Change Status</flux:heading>
            
            <flux:select wire:model="newStatus" label="New Status" required>
                <option value="">Select status...</option>
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption }}">{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                @endforeach
            </flux:select>

            <flux:textarea
                wire:model="statusChangeReason"
                label="Reason"
                placeholder="Why are you changing the status?"
                rows="3"
                required
            />

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="$set('showStatusChangeModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Update</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="time-entry" wire:model="showTimeEntryModal">
        <form wire:submit.prevent="addTimeEntry" class="space-y-6">
            <flux:heading size="lg">Add Time Entry</flux:heading>
            
            <flux:input
                type="number"
                wire:model="timeSpent"
                label="Hours"
                placeholder="1.5"
                step="0.25"
                min="0"
                required
            />

            <flux:textarea
                wire:model="timeDescription"
                label="Description"
                placeholder="What did you work on?"
                rows="3"
                required
            />

            <flux:checkbox wire:model="billable" label="Billable" />

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="$set('showTimeEntryModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Add Entry</flux:button>
            </div>
        </form>
    </flux:modal>

    @script
    <script>
        $wire.on('draftSavedIndicator', () => {
            setTimeout(() => $wire.set('draftSaved', false), 2000);
        });

        $wire.on('ticketUpdated', (event) => {
            const ticketId = Array.isArray(event) ? event[0].ticketId : event.ticketId;
            if (ticketId === {{ $ticket->id }}) $wire.$refresh();
        });
    </script>
    @endscript
</div>
