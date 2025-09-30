@extends('layouts.app')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Active Timers</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                @if(auth()->user()->hasRole('admin'))
                    All running timers across the company
                @else
                    Your currently running timers
                @endif
            </p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="flex gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg px-4 py-3 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">Active Timers</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $statistics['total_active'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg px-4 py-3 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">Today's Hours</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ number_format($statistics['total_time_today']['total_hours'], 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg px-4 py-3 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">Billable Hours</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ number_format($statistics['total_time_today']['billable_hours'], 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Active Timers Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($activeTimers as $timer)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4" 
                 data-timer-id="{{ $timer->id }}"
                 data-started-at="{{ $timer->started_at }}">
                
                <!-- Timer Header -->
                <div class="flex justify-between items-start mb-3">
                    <div class="flex-1">
                        <h3 class="font-medium text-gray-900 dark:text-white">
                            <a href="{{ route('tickets.show', $timer->ticket_id) }}" class="hover:text-blue-600">
                                #{{ $timer->ticket->ticket_number }} - {{ Str::limit($timer->ticket->subject, 40) }}
                            </a>
                        </h3>
                        @if($timer->ticket->client)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $timer->ticket->client->name }}
                            </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if($timer->billable)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Billable
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Timer Info -->
                <div class="space-y-2 mb-4">
                    @if(auth()->user()->hasRole('admin'))
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <flux:icon name="user" class="w-4 h-4 mr-1"/>
                            {{ $timer->user->name }}
                        </div>
                    @endif
                    
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <flux:icon name="clock" class="w-4 h-4 mr-1"/>
                        Started {{ $timer->started_at->diffForHumans() }}
                    </div>
                    
                    @if($timer->description)
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            {{ Str::limit($timer->description, 100) }}
                        </div>
                    @endif
                </div>

                <!-- Timer Display -->
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 mb-4">
                    <div class="text-center">
                        <div class="text-2xl font-mono font-semibold text-gray-900 dark:text-white timer-display" 
                             data-elapsed="{{ $timer->getElapsedTime() }}">
                            {{ gmdate('H:i:s', $timer->getElapsedTime() * 60) }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Elapsed Time</p>
                    </div>
                </div>

                <!-- Timer Controls -->
                <div class="flex gap-2">
                    @if($timer->paused_at)
                        <button onclick="resumeTimer({{ $timer->id }})" 
                                class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <flux:icon name="play" class="w-4 h-4 mr-1"/>
                            Resume
                        </button>
                    @else
                        <button onclick="pauseTimer({{ $timer->id }})" 
                                class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <flux:icon name="pause" class="w-4 h-4 mr-1"/>
                            Pause
                        </button>
                    @endif
                    
                    <button onclick="stopTimer({{ $timer->id }})" 
                            class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <flux:icon name="stop" class="w-4 h-4 mr-1"/>
                        Stop
                    </button>
                    
                    <a href="{{ route('tickets.show', $timer->ticket_id) }}" 
                       class="inline-flex justify-center items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <flux:icon name="arrow-right" class="w-4 h-4"/>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <flux:icon name="clock" class="w-12 h-12 mx-auto text-gray-400 mb-3"/>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No Active Timers</h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        @if(auth()->user()->hasRole('admin'))
                            There are no running timers in the system
                        @else
                            You don't have any running timers
                        @endif
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($activeTimers->hasPages())
        <div class="mt-6">
            {{ $activeTimers->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Update timer displays every second
    setInterval(function() {
        document.querySelectorAll('.timer-display').forEach(function(display) {
            const elapsed = parseInt(display.dataset.elapsed);
            const newElapsed = elapsed + 1;
            display.dataset.elapsed = newElapsed;
            
            const hours = Math.floor(newElapsed / 60);
            const minutes = newElapsed % 60;
            const seconds = 0; // Since we're tracking in minutes
            
            display.textContent = String(hours).padStart(2, '0') + ':' + 
                                 String(minutes).padStart(2, '0') + ':' + 
                                 String(seconds).padStart(2, '0');
        });
    }, 60000); // Update every minute

    // Timer control functions
    function pauseTimer(timerId) {
        fetch(`/tickets/active-timers/${timerId}/pause`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function resumeTimer(timerId) {
        fetch(`/tickets/active-timers/${timerId}/resume`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function stopTimer(timerId) {
        if (confirm('Are you sure you want to stop this timer?')) {
            fetch(`/tickets/active-timers/${timerId}/stop`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    }
</script>
@endpush
@endsection