<div>
    <div class="space-y-3" wire:poll.30s="refreshTicketData">
        {{-- Flash Messages --}}
        @if (session()->has('message'))
            <flux:toast variant="success">{{ session('message') }}</flux:toast>
        @endif
        @if (session()->has('error'))
            <flux:toast variant="danger">{{ session('error') }}</flux:toast>
        @endif

    {{-- Ultra-Compact Header --}}
    <flux:card>
        <div class="px-3 py-2">
            {{-- Single Line Header --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    {{-- Ticket Info --}}
                    <span class="font-bold">#{{ $ticket->ticket_number ?? $ticket->number }}</span>
                    <span class="text-gray-400">|</span>
                    <span class="font-medium truncate">{{ $ticket->subject }}</span>

                    {{-- Status & Priority --}}
                    <flux:badge color="{{
                        $ticket->status === 'open' ? 'green' :
                        ($ticket->status === 'in_progress' ? 'blue' :
                        ($ticket->status === 'pending' ? 'amber' :
                        ($ticket->status === 'resolved' ? 'purple' :
                        ($ticket->status === 'closed' ? 'zinc' : 'red'))))
                    }}">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                    </flux:badge>

                    <flux:badge color="{{
                        $ticket->priority === 'critical' ? 'red' :
                        ($ticket->priority === 'urgent' ? 'red' :
                        ($ticket->priority === 'high' ? 'orange' :
                        ($ticket->priority === 'medium' ? 'yellow' :
                        ($ticket->priority === 'low' ? 'gray' : 'zinc'))))
                    }}">
                        {{ ucfirst($ticket->priority) }}
                    </flux:badge>

                    {{-- Key Info Inline --}}
                    <span class="text-gray-400">|</span>
                    <span class="text-sm text-gray-600">
                        <strong>Client:</strong>
                        @if($ticket->client)
                            <a href="{{ route('clients.show', $ticket->client) }}" class="text-blue-600 hover:underline">{{ $ticket->client->name }}</a>
                        @else
                            -
                        @endif
                    </span>
                    <span class="text-gray-400">|</span>
                    <span class="text-sm text-gray-600"><strong>Contact:</strong> {{ $ticket->contact?->name ?? '-' }}</span>
                    <span class="text-gray-400">|</span>
                    <span class="text-sm text-gray-600"><strong>Assignee:</strong> {{ $ticket->assignee?->name ?? 'Unassigned' }}</span>

                    @php
                        $totalMinutes = $ticket->timeLogs->sum('minutes_worked') ?? 0;
                    @endphp
                    @if($totalMinutes > 0)
                        <span class="text-gray-400">|</span>
                        <span class="text-sm text-gray-600"><strong>Time:</strong> {{ floor($totalMinutes / 60) }}h {{ $totalMinutes % 60 }}m</span>
                    @endif

                    @if($ticket->priorityQueue && $ticket->priorityQueue->sla_deadline)
                        @php
                            $deadline = \Carbon\Carbon::parse($ticket->priorityQueue->sla_deadline);
                            $isBreached = now()->isAfter($deadline);
                        @endphp
                        <span class="text-gray-400">|</span>
                        <span class="text-sm {{ $isBreached ? 'text-red-600 font-bold' : 'text-green-600' }}">
                            <strong>SLA:</strong> {{ $isBreached ? 'BREACHED' : $deadline->diffForHumans(null, true) }}
                        </span>
                    @endif

                    <span class="text-gray-400">|</span>
                    <span class="text-sm text-gray-500">{{ $ticket->created_at->diffForHumans() }}</span>
                </div>

                {{-- Timer Display --}}
                @if($activeTimer)
                    <div class="flex items-center gap-2 px-2 py-1 bg-emerald-100 dark:bg-emerald-900/20 rounded text-sm" wire:poll.1s="updateElapsedTime">
                        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400">
                            {{ $elapsedTime }}
                        </span>
                        <button type="button" wire:click="stopTimer" class="inline-flex items-center gap-1 px-2 py-1 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700">
                            <flux:icon.stop class="size-3" />
                            Stop
                        </button>
                    </div>
                @else
                    <button type="button" wire:click="startTimer" class="inline-flex items-center gap-1 px-2 py-1 text-sm font-medium text-white bg-emerald-600 rounded hover:bg-emerald-700">
                        <flux:icon.play class="size-3" />
                        Start Timer
                    </button>
                @endif

                {{-- Actions --}}
                <div class="flex items-center gap-1 ml-2">
                    <flux:button variant="ghost" size="sm" wire:click="toggleWatch">
                        <flux:icon.eye class="size-4 {{ $isWatching ? 'text-yellow-600' : '' }}" />
                    </flux:button>
                    <flux:button variant="ghost" size="sm" href="{{ route('tickets.edit', $ticket) }}">
                        <flux:icon.pencil class="size-4" />
                    </flux:button>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm">
                            <flux:icon.ellipsis-horizontal class="size-4" />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="$set('showStatusChangeModal', true)">
                                <flux:icon.arrow-path class="size-4" />
                                Status
                            </flux:menu.item>
                            <flux:menu.item wire:click="cloneTicket">
                                <flux:icon.document-duplicate class="size-4" />
                                Clone
                            </flux:menu.item>
                            <flux:menu.item href="{{ route('tickets.pdf', $ticket) }}" target="_blank">
                                <flux:icon.document-arrow-down class="size-4" />
                                PDF
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item variant="danger" wire:click="archiveTicket">
                                <flux:icon.archive-box class="size-4" />
                                Archive
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>
    </flux:card>

    {{-- Inline Metrics Bar --}}
    <div class="bg-gray-50 dark:bg-gray-800 px-2 py-1 rounded">
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1">
                    <span class="text-gray-500">Age:</span>
                    <span class="font-medium">{{ $ticket->created_at->diffForHumans(null, true) }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500">Comments:</span>
                    <span class="font-medium">{{ $ticket->comments->count() }}</span>
                    <span class="text-gray-400">({{ $ticket->comments->where('is_internal', false)->count() }} public)</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500">Updates:</span>
                    <span class="font-medium">{{ $ticket->updated_at->diffForHumans() }}</span>
                </div>
                @if($ticket->watchers->count() > 0)
                    <div class="flex items-center gap-1">
                        <span class="text-gray-500">Watchers:</span>
                        <span class="font-medium">{{ $ticket->watchers->count() }}</span>
                    </div>
                @endif
                @if($ticket->resolved_at)
                    <div class="flex items-center gap-1">
                        <span class="text-gray-500">Resolved:</span>
                        <span class="font-medium text-green-600">{{ $ticket->resolved_at->diffForHumans() }}</span>
                    </div>
                @endif
                @if($ticket->reopened_at)
                    <div class="flex items-center gap-1">
                        <span class="text-gray-500">Reopened:</span>
                        <span class="font-medium text-yellow-600">{{ $ticket->reopened_at->diffForHumans() }}</span>
                    </div>
                @endif
            </div>

            {{-- Quick Stats --}}
            <div class="flex items-center gap-4">
                @if($ticket->project)
                    <a href="{{ route('projects.show', $ticket->project) }}" class="text-blue-600 hover:underline">
                        <flux:icon.briefcase class="size-3 inline" /> {{ $ticket->project->name }}
                    </a>
                @endif
                @if($ticket->asset)
                    <a href="{{ route('assets.show', $ticket->asset) }}" class="text-blue-600 hover:underline">
                        <flux:icon.server class="size-3 inline" /> {{ $ticket->asset->name }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-2">
        {{-- Left Column: Main Content (3/4) --}}
        <div class="lg:col-span-3 space-y-2">

            {{-- Description Card --}}
            <flux:card>
                <div class="p-3">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Description</h3>
                        <div class="flex items-center gap-2">
                            @if($ticket->sentiment_label)
                                <span class="text-xs px-2 py-1 rounded {{
                                    $ticket->sentiment_label === 'positive' ? 'bg-green-100 text-green-700' :
                                    ($ticket->sentiment_label === 'negative' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')
                                }}">
                                    Sentiment: {{ ucfirst($ticket->sentiment_label) }}
                                    @if($ticket->sentiment_confidence)
                                        ({{ round($ticket->sentiment_confidence * 100) }}%)
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                        {{ $ticket->details ?? $ticket->description ?? 'No description provided.' }}
                    </div>

                    @if($ticket->resolution_summary)
                        <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 rounded border-l-3 border-green-500">
                            <div class="text-xs font-semibold text-green-800 dark:text-green-300 mb-1">Resolution</div>
                            <div class="text-sm text-green-700 dark:text-green-400">
                                {{ $ticket->resolution_summary }}
                            </div>
                        </div>
                    @endif

                    @if($ticket->vendor_ticket_number)
                        <div class="mt-2 pt-2 border-t text-xs text-gray-500">
                            Vendor Ticket: <span class="font-medium">{{ $ticket->vendor_ticket_number }}</span>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Comments & Activity Timeline --}}
            <flux:card>
                <div class="p-3">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold">Activity Timeline</h3>
                        <div class="flex items-center gap-2 text-xs">
                            <button wire:click="$set('showInternalNotes', !$showInternalNotes)" class="text-gray-500 hover:text-gray-700">
                                {{ $showInternalNotes ?? true ? 'Hide' : 'Show' }} Internal
                            </button>
                        </div>
                    </div>
                    
                    {{-- Compact Comment Form --}}
                    <div class="mb-3">
                        <form wire:submit.prevent="addComment">
                            <flux:textarea
                                wire:model="comment"
                                placeholder="Add comment..."
                                rows="2"
                                class="text-sm"
                            />
                            
                            <div class="flex items-center justify-between mt-2">
                                <div class="flex items-center gap-3 text-xs">
                                    <label class="flex items-center gap-1">
                                        <input type="checkbox" wire:model="internalNote" class="rounded text-xs" />
                                        <span>Internal</span>
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <input type="checkbox" wire:model="timeTracking" class="rounded text-xs" />
                                        <span>Log Time</span>
                                    </label>
                                    @if($ticket->status === 'resolved' || $ticket->status === 'closed')
                                        <label class="flex items-center gap-1">
                                            <input type="checkbox" wire:model="reopenOnComment" class="rounded text-xs" />
                                            <span>Reopen</span>
                                        </label>
                                    @endif
                                </div>

                                <flux:button type="submit" variant="primary" size="xs">
                                    <flux:icon.plus class="size-3" />
                                    Comment
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
                                        @forelse($ticket->comments->sortByDesc('created_at') ?? [] as $comment)
                                            <div class="flex gap-3 {{ $comment->is_internal ? 'bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg' : '' }}">
                                                <flux:avatar size="sm">
                                                    {{ substr($comment->author?->name ?? 'S', 0, 2) }}
                                                </flux:avatar>

                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <span class="font-medium">{{ $comment->author?->name ?? 'System' }}</span>
                                                            <span class="text-sm text-gray-500 ml-2">
                                                                {{ $comment->created_at?->diffForHumans() ?? '' }}
                                                            </span>
                                                            @if($comment->is_internal)
                                                                <flux:badge variant="warning" size="xs" class="ml-2">Internal</flux:badge>
                                                            @endif
                                                            @if($comment->is_system)
                                                                <flux:badge variant="outline" size="xs" class="ml-2">System</flux:badge>
                                                            @endif
                                                        </div>

                                                        @if($comment->author_id === Auth::id() && $comment->created_at?->diffInMinutes(now()) < 30)
                                                            <flux:dropdown>
                                                                <flux:button variant="ghost" size="xs">
                                                                    <flux:icon.ellipsis-horizontal class="size-3" />
                                                                </flux:button>
                                                                <flux:menu>
                                                                    <flux:menu.item wire:click="editComment({{ $comment->id }})">
                                                                        <flux:icon.pencil class="size-3" />
                                                                        Edit
                                                                    </flux:menu.item>
                                                                    <flux:menu.item wire:click="deleteComment({{ $comment->id }})" variant="danger">
                                                                        <flux:icon.trash class="size-3" />
                                                                        Delete
                                                                    </flux:menu.item>
                                                                </flux:menu>
                                                            </flux:dropdown>
                                                        @endif
                                                    </div>

                                                    <div class="mt-1 text-gray-700 dark:text-gray-300">
                                                        {!! nl2br(e($comment->content)) !!}
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
                                            <p class="text-gray-500 text-center py-4">No comments yet. Be the first to comment!</p>
                                        @endforelse
                                    </div>
                                </div>
                            </flux:card>
                        </div>

        {{-- Right Column: Dense Sidebar (1/4) --}}
        <div class="space-y-2">
            {{-- Quick Actions --}}
            <flux:card>
                <div class="p-3">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Quick Actions</h3>
                    
                    <div class="space-y-2">
                        {{-- Status --}}
                        <div>
                            <label class="text-xs text-gray-500">Status</label>
                            <flux:select wire:model="status" wire:change="updateStatus" size="sm">
                                @foreach($statuses as $statusOption)
                                    <option value="{{ $statusOption }}">
                                        {{ ucfirst(str_replace('_', ' ', $statusOption)) }}
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>

                        {{-- Priority --}}
                        <div>
                            <label class="text-xs text-gray-500">Priority</label>
                            <flux:select wire:model="priority" wire:change="updatePriority" size="sm">
                                @foreach($priorities as $priorityOption)
                                    <option value="{{ $priorityOption }}">
                                        {{ ucfirst($priorityOption) }}
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>

                        {{-- Assignee --}}
                        <div>
                            <label class="text-xs text-gray-500">Assignee</label>
                            <flux:select wire:model="assignedTo" wire:change="updateAssignee" size="sm">
                                <option value="">Unassigned</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Dense Information Panel --}}
            <flux:card>
                <div class="p-3">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Details</h3>

                    <dl class="space-y-1 text-xs">
                        <div class="flex justify-between py-1 border-b border-gray-100">
                            <dt class="text-gray-500">Created</dt>
                            <dd class="font-medium">{{ $ticket->created_at->format('M d, g:i A') }}</dd>
                        </div>

                        @if($ticket->scheduled_at)
                            <div class="flex justify-between py-1 border-b border-gray-100">
                                <dt class="text-gray-500">Scheduled</dt>
                                <dd class="font-medium text-blue-600">{{ $ticket->scheduled_at->format('M d, g:i A') }}</dd>
                            </div>
                        @endif

                        @if($ticket->closed_at)
                            <div class="flex justify-between py-1 border-b border-gray-100">
                                <dt class="text-gray-500">Closed</dt>
                                <dd class="font-medium">{{ $ticket->closed_at->format('M d, g:i A') }}</dd>
                            </div>
                        @endif

                        <div class="flex justify-between py-1 border-b border-gray-100">
                            <dt class="text-gray-500">Requester</dt>
                            <dd class="font-medium truncate max-w-[120px]" title="{{ $ticket->requester?->name ?? $ticket->contact?->name ?? 'Unknown' }}">
                                {{ $ticket->requester?->name ?? $ticket->contact?->name ?? 'Unknown' }}
                            </dd>
                        </div>

                        @if($ticket->location)
                            <div class="flex justify-between py-1 border-b border-gray-100">
                                <dt class="text-gray-500">Location</dt>
                                <dd class="font-medium truncate max-w-[120px]">{{ $ticket->location->name }}</dd>
                            </div>
                        @endif

                        @if($ticket->vendor)
                            <div class="flex justify-between py-1 border-b border-gray-100">
                                <dt class="text-gray-500">Vendor</dt>
                                <dd class="font-medium truncate max-w-[120px]">{{ $ticket->vendor->name }}</dd>
                            </div>
                        @endif

                        @if($ticket->invoice)
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-500">Invoice</dt>
                                <dd>
                                    <a href="{{ route('invoices.show', $ticket->invoice) }}" class="text-blue-600 hover:underline font-medium">
                                        #{{ $ticket->invoice->number }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </flux:card>

            {{-- Time & SLA Summary --}}
            <flux:card>
                <div class="p-3">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Time & SLA</h3>
                        <flux:button variant="ghost" size="xs" wire:click="$set('showTimeEntryModal', true)">
                            <flux:icon.plus class="size-3" />
                        </flux:button>
                    </div>

                    @php
                        $totalMinutes = $ticket->timeLogs->sum('minutes_worked') ?? 0;
                        $billableMinutes = $ticket->timeLogs->where('billable', true)->sum('minutes_worked') ?? 0;
                    @endphp

                    <div class="space-y-2">
                        {{-- Time Summary --}}
                        <div class="bg-gray-50 dark:bg-gray-800 rounded p-2">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">Total Time</span>
                                <span class="text-sm font-bold">{{ floor($totalMinutes / 60) }}h {{ $totalMinutes % 60 }}m</span>
                            </div>
                            @if($billableMinutes > 0)
                                <div class="flex justify-between items-center mt-1">
                                    <span class="text-xs text-gray-500">Billable</span>
                                    <span class="text-xs font-medium text-green-600">{{ floor($billableMinutes / 60) }}h {{ $billableMinutes % 60 }}m</span>
                                </div>
                            @endif
                        </div>

                        {{-- SLA Status --}}
                        @if($ticket->priorityQueue && $ticket->priorityQueue->sla_deadline)
                            @php
                                $deadline = \Carbon\Carbon::parse($ticket->priorityQueue->sla_deadline);
                                $now = now();
                                $totalHours = $ticket->created_at->diffInHours($deadline);
                                $elapsedHours = $ticket->created_at->diffInHours($now);
                                $progress = min(100, ($elapsedHours / $totalHours) * 100);
                                $isBreached = $now->isAfter($deadline);
                            @endphp

                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">SLA Progress</span>
                                    <span class="{{ $isBreached ? 'text-red-600 font-bold' : ($progress > 75 ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ round($progress) }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="{{ $isBreached ? 'bg-red-600' : ($progress > 75 ? 'bg-yellow-500' : 'bg-green-500') }} h-1.5 rounded-full"
                                         style="width: {{ min(100, $progress) }}%"></div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $isBreached ? 'Breached' : 'Due' }}: {{ $deadline->format('M d, g:i A') }}
                                </div>
                            </div>
                        @endif

                        {{-- Recent Time Entries --}}
                        @if($ticket->timeLogs->count() > 0)
                            <div class="border-t pt-2 mt-2">
                                <div class="text-xs font-medium text-gray-600 mb-1">Recent Entries</div>
                                @foreach($ticket->timeLogs->take(3) as $log)
                                    <div class="text-xs text-gray-600 py-0.5">
                                        {{ $log->user?->name }}: {{ floor(($log->minutes_worked ?? $log->minutes ?? 0) / 60) }}h {{ ($log->minutes_worked ?? $log->minutes ?? 0) % 60 }}m
                                        @if($log->billable)
                                            <span class="text-green-600">•</span>
                                        @endif
                                    </div>
                                @endforeach
                                @if($ticket->timeLogs->count() > 3)
                                    <a href="#" wire:click="$set('showTimeModal', true)" class="text-xs text-blue-600 hover:underline">
                                        +{{ $ticket->timeLogs->count() - 3 }} more
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>

            {{-- Watchers & Related --}}
            <flux:card>
                <div class="p-3">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Related</h3>

                    <div class="space-y-2">
                        {{-- Watchers --}}
                        @if($ticket->watchers->count() > 0)
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Watchers ({{ $ticket->watchers->count() }})</div>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($ticket->watchers->take(3) as $watcher)
                                        <span class="text-xs px-2 py-0.5 bg-gray-100 rounded">{{ $watcher->user?->name ?? 'Unknown' }}</span>
                                    @endforeach
                                    @if($ticket->watchers->count() > 3)
                                        <span class="text-xs text-gray-500">+{{ $ticket->watchers->count() - 3 }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Related Tickets Count --}}
                        @php
                            $relatedCount = App\Domains\Ticket\Models\Ticket::where('company_id', $ticket->company_id)
                                ->where('id', '!=', $ticket->id)
                                ->where('client_id', $ticket->client_id)
                                ->count();
                        @endphp
                        @if($relatedCount > 0)
                            <div class="text-xs">
                                <a href="{{ route('tickets.index', ['client_id' => $ticket->client_id]) }}" class="text-blue-600 hover:underline">
                                    {{ $relatedCount }} related {{ Str::plural('ticket', $relatedCount) }} for this client
                                </a>
                            </div>
                        @endif

                        {{-- Files Count --}}
                        @php
                            $attachmentCount = $ticket->comments->flatMap->attachments->count();
                        @endphp
                        @if($attachmentCount > 0)
                            <div class="text-xs text-gray-600">
                                <flux:icon.paper-clip class="size-3 inline" /> {{ $attachmentCount }} {{ Str::plural('file', $attachmentCount) }} attached
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Timer Completion Modal --}}
    @livewire('timer-completion-modal')

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

    {{-- Time Entry Modal --}}
    <flux:modal wire:model="showTimeEntryModal" title="Add Time Entry">
        <form wire:submit.prevent="addTimeEntry">
            <div class="space-y-4">
                <flux:input
                    type="number"
                    wire:model="timeSpent"
                    label="Time Spent (hours)"
                    placeholder="e.g., 1.5"
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
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <flux:button variant="ghost" wire:click="$set('showTimeEntryModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Add Entry
                </flux:button>
            </div>
        </form>
    </flux:modal>
    </div>
</div>