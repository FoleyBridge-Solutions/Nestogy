@extends('layouts.app')

@section('title', 'Calendar View')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Calendar View</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Schedule and track ticket-related events, meetings, and deadlines.</p>
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" 
                                onclick="toggleCalendarView()"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span id="viewToggleText">{{ $view === 'month' ? 'Week View' : 'Month View' }}</span>
                        </button>
                        <a href="{{ route('tickets.calendar.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Event
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Navigation & Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <button type="button" 
                                onclick="navigateCalendar(-1)"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="-ml-0.5 mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Previous
                        </button>
                        
                        <button type="button" 
                                onclick="goToToday()"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Today
                        </button>
                        
                        <button type="button" 
                                onclick="navigateCalendar(1)"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                            <svg class="-mr-0.5 ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <h2 class="text-xl font-semibold text-gray-900 ml-6" id="calendarTitle">
                            {{ $currentDate->format('F Y') }}
                        </h2>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <label class="text-sm text-gray-700">Show:</label>
                            <div class="flex items-center space-x-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" 
                                           name="show_tickets" 
                                           checked 
                                           onchange="toggleEventType('tickets', this.checked)"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Tickets</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" 
                                           name="show_events" 
                                           checked 
                                           onchange="toggleEventType('events', this.checked)"
                                           class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-sm text-gray-700">Events</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" 
                                           name="show_deadlines" 
                                           checked 
                                           onchange="toggleEventType('deadlines', this.checked)"
                                           class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    <span class="ml-2 text-sm text-gray-700">Deadlines</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Events</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ $calendarStats['total_events'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Today's Items</dt>
                                        <dd class="text-lg font-medium text-blue-600">{{ $calendarStats['today_events'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Overdue Items</dt>
                                        <dd class="text-lg font-medium text-red-600">{{ $calendarStats['overdue_events'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                        <dd class="text-lg font-medium text-green-600">{{ $calendarStats['completed_events'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                @if($view === 'month')
                    <!-- Month View -->
                    <div class="calendar-grid">
                        <!-- Days of week header -->
                        <div class="grid grid-cols-7 gap-px mb-2">
                            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                                <div class="bg-gray-50 py-2 text-center text-sm font-medium text-gray-700">{{ $day }}</div>
                            @endforeach
                        </div>

                        <!-- Calendar days -->
                        <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-lg overflow-hidden">
                            @foreach($calendarDays as $day)
                                <div class="bg-white min-h-32 p-2 {{ $day['isCurrentMonth'] ? '' : 'bg-gray-50' }} {{ $day['isToday'] ? 'ring-2 ring-indigo-500' : '' }}">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium {{ $day['isCurrentMonth'] ? 'text-gray-900' : 'text-gray-400' }}">
                                            {{ $day['date']->format('j') }}
                                        </span>
                                        @if($day['hasEvents'])
                                            <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                        @endif
                                    </div>
                                    
                                    <!-- Events for this day -->
                                    @if(isset($day['events']) && count($day['events']) > 0)
                                        <div class="space-y-1">
                                            @foreach(array_slice($day['events'], 0, 3) as $event)
                                                <div class="px-2 py-1 text-xs rounded cursor-pointer hover:opacity-80 @if($event['type'] === 'ticket') bg-blue-100 text-blue-700 @elseif($event['type'] === 'event') bg-green-100 text-green-700 @elseif($event['type'] === 'deadline') bg-red-100 text-red-700 @else bg-gray-100 text-gray-700 @endif"
                                                    onclick="showEventDetails({{ json_encode($event) }})">
                                                    <div class="truncate">{{ $event['title'] }}</div>
                                                    @if($event['time'])
                                                        <div class="truncate opacity-75">{{ $event['time'] }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if(count($day['events']) > 3)
                                                <div class="text-xs text-gray-500 text-center">
                                                    +{{ count($day['events']) - 3 }} more
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <!-- Week View -->
                    <div class="week-view">
                        <!-- Week header -->
                        <div class="grid grid-cols-8 gap-px mb-4">
                            <div class="py-2"></div>
                            @foreach($weekDays as $day)
                                <div class="py-2 text-center">
                                    <div class="text-sm font-medium text-gray-900">{{ $day['name'] }}</div>
                                    <div class="text-lg {{ $day['isToday'] ? 'font-bold text-indigo-600' : 'text-gray-700' }}">
                                        {{ $day['date']->format('j') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Time slots -->
                        <div class="grid grid-cols-8 gap-px bg-gray-200 rounded-lg overflow-hidden">
                            @for($hour = 0; $hour < 24; $hour++)
                                <div class="bg-white p-2 text-right text-sm text-gray-500 border-r">
                                    {{ sprintf('%02d:00', $hour) }}
                                </div>
                                @foreach($weekDays as $day)
                                    <div class="bg-white min-h-16 p-1 border-r border-b border-gray-100 relative">
                                        @if(isset($weekEvents[$day['date']->format('Y-m-d')][$hour]))
                                            @foreach($weekEvents[$day['date']->format('Y-m-d')][$hour] as $event)
                                                <div class="absolute inset-1 px-2 py-1 text-xs rounded cursor-pointer hover:opacity-80 @if($event['type'] === 'ticket') bg-blue-100 text-blue-700 @elseif($event['type'] === 'event') bg-green-100 text-green-700 @elseif($event['type'] === 'deadline') bg-red-100 text-red-700 @else bg-gray-100 text-gray-700 @endif"
                                                    onclick="showEventDetails({{ json_encode($event) }})">
                                                    <div class="truncate font-medium">{{ $event['title'] }}</div>
                                                    @if($event['time'])
                                                        <div class="truncate opacity-75">{{ $event['time'] }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                @endforeach
                            @endfor
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Upcoming Events Sidebar (shown below calendar on mobile) -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Upcoming Events</h3>
            </div>
            <div class="p-4">
                @if(count($upcomingEvents) > 0)
                    <div class="space-y-3">
                        @foreach($upcomingEvents as $event)
                            <div class="flex items-center p-3 rounded-lg border hover:bg-gray-50 cursor-pointer"
                                 onclick="showEventDetails({{ json_encode($event) }})">
                                <div class="flex-shrink-0">
                                    <div class="w-3 h-3 rounded-full @if($event['type'] === 'ticket') bg-blue-500 @elseif($event['type'] === 'event') bg-green-500 @elseif($event['type'] === 'deadline') bg-red-500 @else bg-gray-500 @endif"></div>
                                </div>
                                <div class="ml-3 flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $event['title'] }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($event['date'])->format('M j, Y') }}
                                        @if($event['time'])
                                            at {{ $event['time'] }}
                                        @endif
                                    </div>
                                    @if($event['description'])
                                        <div class="text-sm text-gray-600 mt-1">{{ Str::limit($event['description'], 60) }}</div>
                                    @endif
                                </div>
                                <div class="ml-3 flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming events</h3>
                        <p class="mt-1 text-sm text-gray-500">Schedule events, meetings, or set deadlines to see them here.</p>
                        <div class="mt-6">
                            <a href="{{ route('tickets.calendar.create') }}" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add Event
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div id="eventModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeEventModal()"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Event Details</h3>
                        <div class="mt-4" id="modalContent">
                            <!-- Event details will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-flex flex-wrap -mx-4-reverse">
                <button type="button" 
                        onclick="closeEventModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                    Close
                </button>
                <button type="button" 
                        id="editEventBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                    Edit Event
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentView = '{{ $view }}';
let currentDate = new Date('{{ $currentDate->format('Y-m-d') }}');

function toggleCalendarView() {
    const newView = currentView === 'month' ? 'week' : 'month';
    window.location.href = `{{ route('tickets.calendar.index') }}?view=${newView}&date=${currentDate.toISOString().split('T')[0]}`;
}

function navigateCalendar(direction) {
    const newDate = new Date(currentDate);
    if (currentView === 'month') {
        newDate.setMonth(newDate.getMonth() + direction);
    } else {
        newDate.setDate(newDate.getDate() + (direction * 7));
    }
    
    window.location.href = `{{ route('tickets.calendar.index') }}?view=${currentView}&date=${newDate.toISOString().split('T')[0]}`;
}

function goToToday() {
    window.location.href = `{{ route('tickets.calendar.index') }}?view=${currentView}&date=${new Date().toISOString().split('T')[0]}`;
}

function toggleEventType(type, show) {
    const elements = document.querySelectorAll(`[data-event-type="${type}"]`);
    elements.forEach(el => {
        el.style.display = show ? 'block' : 'none';
    });
}

function showEventDetails(event) {
    document.getElementById('modalTitle').textContent = event.title;
    
    const content = `
        <div class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500">Type</dt>
                <dd class="mt-1 text-sm text-gray-900 capitalize">${event.type}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Date & Time</dt>
                <dd class="mt-1 text-sm text-gray-900">${event.date}${event.time ? ' at ' + event.time : ''}</dd>
            </div>
            ${event.description ? `
            <div>
                <dt class="text-sm font-medium text-gray-500">Description</dt>
                <dd class="mt-1 text-sm text-gray-900">${event.description}</dd>
            </div>
            ` : ''}
            ${event.ticket_id ? `
            <div>
                <dt class="text-sm font-medium text-gray-500">Related Ticket</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <a href="/tickets/${event.ticket_id}" class="text-indigo-600 hover:text-indigo-500">
                        #${event.ticket_number || event.ticket_id}
                    </a>
                </dd>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('modalContent').innerHTML = content;
    document.getElementById('editEventBtn').onclick = () => {
        window.location.href = `/tickets/calendar/${event.id}/edit`;
    };
    
    document.getElementById('eventModal').classList.remove('hidden');
}

function closeEventModal() {
    document.getElementById('eventModal').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEventModal();
    }
});
</script>
@endsection