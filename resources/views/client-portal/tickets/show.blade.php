@extends('client-portal.layouts.app')

@section('title', 'Support Ticket #' . ($ticket->number ?? $ticket->id))

@section('content')
<div class="container mx-auto mx-auto mx-auto px-6">
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
                        @php
                            $statusColors = [
                                'Open' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                'Awaiting Customer' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'In Progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                'Resolved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'Closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            ];
                            $statusColor = $statusColors[$ticket->status] ?? 'bg-gray-100 text-gray-800';
                            
                            $priorityColors = [
                                'Critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                'High' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                'Medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'Low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                            ];
                            $priorityColor = $priorityColors[$ticket->priority] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        
                        <flux:badge class="{{ $statusColor }}">
                            <i class="fas fa-circle text-xs mr-1"></i>
                            {{ $ticket->status ?? 'Open' }}
                        </flux:badge>
                        
                        <flux:badge class="{{ $priorityColor }}">
                            <i class="fas fa-flag mr-1"></i>
                            {{ $ticket->priority ?? 'Medium' }}
                        </flux:badge>
                        
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
                        $statuses = ['Open', 'In Progress', 'Resolved', 'Closed'];
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
            
            @if(in_array($ticket->status, ['Open', 'In Progress', 'Waiting']))
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
        <div class="lg:flex-1 px-6-span-2 space-y-6">
            <!-- Ticket Details Card -->
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        <i class="fas fa-info-circle mr-2"></i>
                        Ticket Details
                    </flux:heading>
                </div>
                <div>
                    <div class="prose prose-sm max-w-none dark:prose-invert">
                        {!! nl2br(e($ticket->details)) !!}
                    </div>
                    
                    @if($ticket->attachments && count($ticket->attachments) > 0)
                        <flux:separator class="my-4" />
                        <div>
                            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Attachments</h6>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($ticket->attachments as $attachment)
                                    <a href="{{ $attachment['url'] ?? '#' }}" class="flex items-center p-2 border border-gray-200 dark:border-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <i class="fas fa-paperclip text-gray-400 mr-2"></i>
                                        <span class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $attachment['name'] ?? 'Attachment' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Conversation Thread -->
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        <i class="fas fa-comments mr-2"></i>
                        Conversation
                    </flux:heading>
                </div>
                <div class="p-0">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($ticket->replies ?? [] as $reply)
                            <div class="p-6 {{ $reply->is_staff ? 'bg-blue-50 dark:bg-gray-800' : '' }}">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-2">
                                            <div>
                                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $reply->user->name ?? 'Support Team' }}
                                                </span>
                                                @if($reply->is_staff)
                                                    <flux:badge size="sm" class="ml-2">Staff</flux:badge>
                                                @endif
                                            </div>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $reply->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <div class="prose prose-sm max-w-none dark:prose-invert">
                                            {!! nl2br(e($reply->message)) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                                No replies yet. Your ticket is awaiting response.
                            </div>
                        @endforelse
                    </div>
                </div>
            </flux:card>

            <!-- Reply Form -->
            @if(!in_array($ticket->status, ['Closed', 'Resolved']))
                <flux:card id="reply" class="space-y-6">
                    <div>
                        <flux:heading size="lg">
                            <i class="fas fa-reply mr-2"></i>
                            Add Reply
                        </flux:heading>
                    </div>
                    <div>
                        <form action="{{ route('client.tickets.reply', $ticket->id) ?? '#' }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <flux:field class="mb-6">
                                <flux:label for="message" required>Your Message</flux:label>
                                <flux:textarea 
                                    id="message" 
                                    name="message" 
                                    rows="5"
                                    placeholder="Type your reply here..."
                                    required>{{ old('message') }}</flux:textarea>
                                <flux:error for="message" />
                            </flux:field>
                            
                            <flux:field class="mb-6">
                                <flux:label for="attachments">Attachments</flux:label>
                                <flux:input 
                                    type="file" 
                                    id="attachments" 
                                    name="attachments[]" 
                                    multiple />
                            </flux:field>
                            
                            <div class="flex justify-between">
                                <flux:button type="button" variant="ghost" onclick="window.location.reload()">
                                    <i class="fas fa-sync mr-2"></i>Refresh
                                </flux:button>
                                <flux:button type="submit" variant="primary">
                                    <i class="fas fa-paper-plane mr-2"></i>Send Reply
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </flux:card>
            @else
                <flux:card class="space-y-6">
                    <div class="text-center py-8">
                        <i class="fas fa-lock text-4xl text-gray-400 mb-6"></i>
                        <p class="text-gray-600 dark:text-gray-400">This ticket is {{ strtolower($ticket->status) }} and cannot accept new replies.</p>
                        @if($ticket->status === 'Resolved')
                            <flux:button href="{{ route('client.tickets.reopen', $ticket->id) ?? '#' }}" variant="primary" class="mt-6">
                                <i class="fas fa-redo mr-2"></i>Reopen Ticket
                            </flux:button>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:flex-1 px-6-span-1 space-y-6">
            <!-- Ticket Information -->
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        <i class="fas fa-info mr-2"></i>
                        Information
                    </flux:heading>
                </div>
                <div>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ticket ID</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $ticket->number ?? $ticket->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $ticket->department ?? 'Support' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Assigned To</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $ticket->assigned_to->name ?? 'Unassigned' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $ticket->updated_at->diffForHumans() }}</dd>
                        </div>
                    </dl>
                </div>
            </flux:card>

            <!-- Quick Actions -->
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        <i class="fas fa-bolt mr-2"></i>
                        Quick Actions
                    </flux:heading>
                </div>
                <div class="space-y-2">
                    @if(!in_array($ticket->status, ['Closed']))
                        <flux:button variant="ghost" class="w-full justify-start" href="{{ route('client.tickets.close', $ticket->id) ?? '#' }}">
                            <i class="fas fa-times-circle mr-2"></i>Close Ticket
                        </flux:button>
                    @endif
                    
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
                <flux:card class="space-y-6">
                    <div>
                        <flux:heading size="lg">
                            <i class="fas fa-book mr-2"></i>
                            Related Articles
                        </flux:heading>
                    </div>
                    <div>
                        <ul class="space-y-2">
                            @foreach($relatedArticles as $article)
                                <li>
                                    <a href="{{ $article->url }}" class="text-sm text-blue-600 hover:underline">
                                        {{ $article->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </flux:card>
            @endif
        </div>
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
@endsection
