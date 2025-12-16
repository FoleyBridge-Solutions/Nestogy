@extends('client-portal.layouts.app')

@section('title', 'Support Ticket #' . ($ticket->number ?? $ticket->id))

@section('content')
<!-- Header -->
<div class="mb-6">
        <nav class="flex items-center mb-6">
            <a href="{{ route('client.tickets') }}" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Support Tickets
            </a>
        </nav>
        
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span class="flex items-center">
                            <i class="fas fa-ticket-alt mr-2"></i>
                            Ticket #{{ $ticket->number ?? $ticket->id }}
                        </span>
                        <span>Created {{ $ticket->created_at->format('M j, Y') }} at {{ $ticket->created_at->format('g:i A') }}</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-6">{{ $ticket->subject }}</h1>
                    
                    <!-- Status, Priority & Category Badges -->
                    <div class="flex items-center gap-3">
                        <x-status-badge :model="$ticket" :status="$ticket->status ?? Ticket::STATUS_OPEN">
                            <i class="fas fa-circle text-xs mr-1"></i>
                        </x-status-badge>
                        
                        <x-priority-badge :model="$ticket" :priority="$ticket->priority">
                            <i class="fas fa-flag text-xs mr-1"></i>
                        </x-priority-badge>
                        
                        @if($ticket->category)
                            <flux:badge>
                                <i class="fas fa-tag mr-1"></i>
                                {{ ucfirst($ticket->category) }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
                
                <!-- Progress Indicator -->
                <div class="hidden lg:block">
                    @php
                        use App\Domains\Ticket\Models\Ticket;
                        $statuses = [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED];
                        $statusLabels = [Ticket::STATUS_OPEN => 'Open', Ticket::STATUS_IN_PROGRESS => 'In Progress', Ticket::STATUS_RESOLVED => 'Resolved', Ticket::STATUS_CLOSED => 'Closed'];
                        $currentIndex = array_search($ticket->status, $statuses);
                        $currentIndex = $currentIndex !== false ? $currentIndex : 0;
                    @endphp
                    
                    <div class="flex items-center space-x-2">
                        @foreach($statuses as $index => $status)
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $index <= $currentIndex ? 'bg-blue-600 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-500' }}">
                                    @if($index < $currentIndex)
                                        <i class="fas fa-check text-xs"></i>
                                    @elseif($index == $currentIndex)
                                        <div class="w-2 h-2 bg-white rounded-full"></div>
                                    @else
                                        <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
                                    @endif
                                </div>
                                @if($index < count($statuses) - 1)
                                    <div class="w-12 h-0.5 {{ $index < $currentIndex ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- SLA Response Time -->
            @php
                $responseTimeHours = [
                    'Critical' => 1,
                    'High' => 4, 
                    'Medium' => 24,
                    'Low' => 48
                ];
                $slaHours = $responseTimeHours[$ticket->priority] ?? 24;
                $slaDeadline = $ticket->created_at->addHours($slaHours);
                $now = now();
                
                if ($now > $slaDeadline) {
                    $overdueDuration = $now->diff($slaDeadline);
                    $overdueText = '';
                    if ($overdueDuration->days > 0) {
                        $overdueText = $overdueDuration->days . ' day' . ($overdueDuration->days > 1 ? 's' : '');
                    } elseif ($overdueDuration->h > 0) {
                        $overdueText = $overdueDuration->h . ' hour' . ($overdueDuration->h > 1 ? 's' : '');
                    } else {
                        $overdueText = $overdueDuration->i . ' minute' . ($overdueDuration->i > 1 ? 's' : '');
                    }
                } else {
                    $remainingTime = $now->diff($slaDeadline);
                    $remainingText = '';
                    if ($remainingTime->days > 0) {
                        $remainingText = $remainingTime->days . ' day' . ($remainingTime->days > 1 ? 's' : '');
                    } elseif ($remainingTime->h > 0) {
                        $remainingText = $remainingTime->h . ' hour' . ($remainingTime->h > 1 ? 's' : '');
                    } else {
                        $remainingText = $remainingTime->i . ' minute' . ($remainingTime->i > 1 ? 's' : '');
                    }
                }
            @endphp
            
            @if(in_array($ticket->status, [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING]))
                <div class="mt-6 flex items-center {{ $now > $slaDeadline ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">
                    @if($now > $slaDeadline)
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><strong>Overdue</strong> by {{ $overdueText }}</span>
                    @else
                        <i class="fas fa-clock mr-2"></i>
                        <span>Response expected within <strong>{{ $remainingText }}</strong></span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Primary Content Area -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ticket Details Card -->
            <flux:card>
                <flux:heading size="lg">
                    <i class="fas fa-info-circle mr-2"></i>
                    Ticket Details
                </flux:heading>
                
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    {!! nl2br(e($ticket->details)) !!}
                </div>
                
                @if($ticket->attachments && count($ticket->attachments) > 0)
                    <flux:separator />
                    <div>
                        <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Attachments</h6>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($ticket->attachments as $attachment)
                                <a href="{{ $attachment['url'] ?? '#' }}" class="flex items-center gap-2 p-2 border border-gray-200 dark:border-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <i class="fas fa-paperclip text-gray-400"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $attachment['name'] ?? 'Attachment' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </flux:card>

            <!-- Conversation Thread -->
            <flux:card>
                <flux:heading size="lg">
                    <i class="fas fa-comments mr-2"></i>
                    Conversation
                </flux:heading>
                
                <div class="divide-y divide-gray-200 dark:divide-gray-700 -mx-6">
                    @forelse($ticket->comments ?? [] as $comment)
                        <div class="px-6 py-4 {{ $comment->author_type === 'staff' ? 'bg-blue-50 dark:bg-gray-800' : '' }}">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $comment->author_name }}
                                            </span>
                                            @if($comment->author_type === 'user' || $comment->author_type === 'staff')
                                                <flux:badge size="sm">Staff</flux:badge>
                                            @endif
                                        </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $comment->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300">
                                        {!! nl2br(e($comment->content)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            No replies yet. Your ticket is awaiting response.
                        </div>
                    @endforelse
                </div>
            </flux:card>

            <!-- Reply Form -->
            @if(!in_array($ticket->status, [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED]))
                <flux:card id="reply">
                    <flux:heading size="lg" class="mb-4">
                        <i class="fas fa-reply mr-2"></i>
                        Add Reply
                    </flux:heading>
                    
                    <form action="{{ route('client.tickets.comment', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <input type="file" id="attachments" name="attachments[]" multiple class="hidden" />
                        
                        <flux:editor name="comment" placeholder="Type your reply here..." class="**:data-[slot=content]:min-h-[150px]!">
                            <flux:editor.toolbar>
                                <flux:editor.bold />
                                <flux:editor.italic />
                                <flux:editor.underline />
                                <flux:editor.separator />
                                <flux:editor.bullet />
                                <flux:editor.ordered />
                                <flux:editor.separator />
                                <flux:editor.link />
                                
                                <flux:editor.spacer />
                                
                                <flux:editor.button 
                                    icon="paper-clip" 
                                    tooltip="Attach files"
                                    x-on:click="document.getElementById('attachments').click()" />
                                
                                <flux:button 
                                    type="submit" 
                                    size="sm" 
                                    variant="primary" 
                                    icon="paper-airplane"
                                    class="ml-1">
                                    Send
                                </flux:button>
                            </flux:editor.toolbar>
                            <flux:editor.content />
                        </flux:editor>
                        
                        <flux:error name="comment" />
                        
                        <div id="file-preview" class="mt-3 hidden">
                            <div id="file-list" class="flex flex-wrap gap-2"></div>
                        </div>
                        
                        <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            Press <kbd class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded text-xs font-mono">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded text-xs font-mono">Enter</kbd> to send
                        </div>
                    </form>
                </flux:card>
            @else
                <flux:card>
                    <div class="text-center py-8">
                        <i class="fas fa-lock text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 dark:text-gray-400">This ticket is {{ strtolower($ticket->status) }} and cannot accept new replies.</p>
                        @if($ticket->status === 'Resolved')
                            <p class="mt-4 text-sm text-gray-500">To reopen this ticket, please create a new ticket or contact support.</p>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Ticket Information -->
            <flux:card>
                <flux:heading size="lg">
                    <i class="fas fa-info mr-2"></i>
                    Information
                </flux:heading>
                
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ticket ID</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $ticket->number ?? $ticket->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $ticket->department ?? 'Support' }}</dd>
                    </div>
                    @if(config('portal.tickets.show_assigned_technician', true))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Assigned To</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                @if($ticket->assignee)
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user-circle text-gray-400"></i>
                                        <span>{{ $ticket->assignee->name }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-500">Unassigned</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if(config('portal.tickets.show_estimated_resolution', true) && $ticket->estimated_resolution_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estimated Resolution</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar-check text-gray-400"></i>
                                    <span>{{ $ticket->estimated_resolution_at->format('M j, Y g:i A') }}</span>
                                </div>
                                <span class="text-xs text-gray-500">{{ $ticket->estimated_resolution_at->diffForHumans() }}</span>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $ticket->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </flux:card>

            <!-- Quick Actions -->
            <flux:card>
                <flux:heading size="lg">
                    <i class="fas fa-bolt mr-2"></i>
                    Quick Actions
                </flux:heading>
                
                <div class="space-y-2">
                    <flux:button variant="ghost" class="w-full justify-start" onclick="window.print()">
                        <i class="fas fa-print mr-2"></i>Print Ticket
                    </flux:button>
                    
                    <flux:button variant="ghost" class="w-full justify-start" href="mailto:support@example.com?subject=Re: Ticket {{ $ticket->number ?? $ticket->id }}">
                        <i class="fas fa-envelope mr-2"></i>Email Support
                    </flux:button>
                </div>
            </flux:card>

            <!-- Related Articles -->
            @if(isset($relatedArticles) && count($relatedArticles) > 0)
                <flux:card>
                    <flux:heading size="lg">
                        <i class="fas fa-book mr-2"></i>
                        Related Articles
                    </flux:heading>
                    
                    <ul class="space-y-2">
                        @foreach($relatedArticles as $article)
                            <li>
                                <a href="{{ $article->url }}" class="text-sm text-blue-600 hover:underline">
                                    {{ $article->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </flux:card>
            @endif
        </div>
    </div>

<style>
/* Custom styles for ticket view - minimal necessary styles only */
.prose {
    color: inherit;
}
.prose p {
    margin: 0.5rem 0;
}
</style>

<script>
// Handle file attachment preview
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('attachments');
    const filePreview = document.getElementById('file-preview');
    const fileList = document.getElementById('file-list');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                filePreview.classList.remove('hidden');
                fileList.innerHTML = '';
                
                Array.from(this.files).forEach((file, index) => {
                    const fileTag = document.createElement('div');
                    fileTag.className = 'flex items-center gap-2 px-3 py-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-lg text-sm';
                    fileTag.innerHTML = `
                        <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300">${file.name}</span>
                        <span class="text-xs text-zinc-500">(${formatFileSize(file.size)})</span>
                        <button type="button" onclick="removeFile(${index})" class="ml-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    `;
                    fileList.appendChild(fileTag);
                });
            } else {
                filePreview.classList.add('hidden');
            }
        });
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    window.removeFile = function(index) {
        const dt = new DataTransfer();
        const files = Array.from(fileInput.files);
        files.splice(index, 1);
        files.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
        fileInput.dispatchEvent(new Event('change'));
    };
});
</script>
@endsection
