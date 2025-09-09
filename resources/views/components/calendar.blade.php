@props(['events' => '/api/calendar/events', 'height' => '600px'])

<div x-data="calendar()" class="calendar-component">
    <div x-ref="calendar" style="height: {{ $height }};"></div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('calendar', () => ({
        calendar: null,
        
        init() {
            this.calendar = new Calendar(this.$refs.calendar, {
                plugins: [
                    window.FullCalendarPlugins.dayGridPlugin,
                    window.FullCalendarPlugins.timeGridPlugin,
                    window.FullCalendarPlugins.interactionPlugin,
                    window.FullCalendarPlugins.bootstrap5Plugin
                ],
                themeSystem: 'bootstrap5',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                initialView: 'dayGridMonth',
                height: '{{ $height }}',
                editable: true,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                weekends: true,
                events: '{{ $events }}',
                eventClick: (info) => {
                    this.handleEventClick(info);
                },
                select: (info) => {
                    this.handleDateSelect(info);
                },
                eventDrop: (info) => {
                    this.handleEventDrop(info);
                },
                eventResize: (info) => {
                    this.handleEventResize(info);
                }
            });
            
            this.calendar.render();
        },
        
        handleEventClick(info) {
            const event = info.event;
            
            Swal.fire({
                title: event.title,
                html: `
                    <div class="text-start">
                        <p><strong>Start:</strong> ${event.start.toLocaleString()}</p>
                        ${event.end ? `<p><strong>End:</strong> ${event.end.toLocaleString()}</p>` : ''}
                        ${event.extendedProps.description ? `<p><strong>Description:</strong> ${event.extendedProps.description}</p>` : ''}
                        ${event.extendedProps.location ? `<p><strong>Location:</strong> ${event.extendedProps.location}</p>` : ''}
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Edit',
                cancelButtonText: 'Close',
                showDenyButton: true,
                denyButtonText: 'Delete',
                denyButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.editEvent(event);
                } else if (result.isDenied) {
                    this.deleteEvent(event);
                }
            });
        },
        
        handleDateSelect(info) {
            Swal.fire({
                title: 'Create New Event',
                html: `
                    <div class="mb-6">
                        <label for="event-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="event-title" placeholder="Event title">
                    </div>
                    <div class="mb-6">
                        <label for="event-description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="event-description" rows="3" placeholder="Event description"></textarea>
                    </div>
                    <div class="mb-6">
                        <label for="event-location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                        <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="event-location" placeholder="Event location">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Create Event',
                preConfirm: () => {
                    const title = document.getElementById('event-title').value;
                    const description = document.getElementById('event-description').value;
                    const location = document.getElementById('event-location').value;
                    
                    if (!title) {
                        Swal.showValidationMessage('Please enter an event title');
                        return false;
                    }
                    
                    return { title, description, location };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    this.createEvent(info, result.value);
                }
            });
            
            this.calendar.unselect();
        },
        
        handleEventDrop(info) {
            this.updateEvent(info.event);
        },
        
        handleEventResize(info) {
            this.updateEvent(info.event);
        },
        
        async createEvent(dateInfo, eventData) {
            try {
                const response = await fetch('/api/calendar/events', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        title: eventData.title,
                        description: eventData.description,
                        location: eventData.location,
                        start: dateInfo.start.toISOString(),
                        end: dateInfo.end ? dateInfo.end.toISOString() : null,
                        all_day: dateInfo.allDay
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.calendar.addEvent({
                        id: result.event.id,
                        title: result.event.title,
                        start: result.event.start,
                        end: result.event.end,
                        allDay: result.event.all_day,
                        extendedProps: {
                            description: result.event.description,
                            location: result.event.location
                        }
                    });
                    
                    Swal.fire('Success!', 'Event created successfully', 'success');
                } else {
                    Swal.fire('Error!', result.message || 'Failed to create event', 'error');
                }
            } catch (error) {
                Swal.fire('Error!', 'Failed to create event', 'error');
            }
        },
        
        async updateEvent(event) {
            try {
                const response = await fetch(`/api/calendar/events/${event.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        start: event.start.toISOString(),
                        end: event.end ? event.end.toISOString() : null,
                        all_day: event.allDay
                    })
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    Swal.fire('Error!', result.message || 'Failed to update event', 'error');
                    event.revert();
                }
            } catch (error) {
                Swal.fire('Error!', 'Failed to update event', 'error');
                event.revert();
            }
        },
        
        async deleteEvent(event) {
            try {
                const response = await fetch(`/api/calendar/events/${event.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    event.remove();
                    Swal.fire('Deleted!', 'Event has been deleted', 'success');
                } else {
                    Swal.fire('Error!', result.message || 'Failed to delete event', 'error');
                }
            } catch (error) {
                Swal.fire('Error!', 'Failed to delete event', 'error');
            }
        },
        
        editEvent(event) {
            // Implement edit functionality
            window.location.href = `/calendar/events/${event.id}/edit`;
        }
    }));
});
</script>
