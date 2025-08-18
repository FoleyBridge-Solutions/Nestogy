@extends('layouts.app')

@section('title', 'Create Calendar Event')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="col-12">
            <div class="flex justify-between items-center mb-4">
                <h1 class="h3 mb-0">Create Calendar Event</h1>
                <a href="{{ route('clients.calendar-events.standalone.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Events
                </a>
            </div>

            <div class="flex flex-wrap -mx-4">
                <div class="col-lg-8 col-xl-6">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <form method="POST" action="{{ route('clients.calendar-events.standalone.store') }}">
                                @csrf

                                <!-- Client Selection -->
                                <div class="mb-3">
                                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-600">*</span></label>
                                    <select name="client_id" 
                                            id="client_id" 
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('client_id') is-invalid @enderror" 
                                            required>
                                        <option value="">Select a client...</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" 
                                                    {{ old('client_id', $selectedClientId) == $client->id ? 'selected' : '' }}>
                                                {{ $client->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Event Title -->
                                <div class="mb-3">
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Event Title <span class="text-red-600">*</span></label>
                                    <input type="text" 
                                           name="title" 
                                           id="title" 
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('title') is-invalid @enderror" 
                                           value="{{ old('title') }}" 
                                           required 
                                           maxlength="255"
                                           placeholder="Enter event title">
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Event Type -->
                                <div class="mb-3">
                                    <label for="event_type" class="form-label">Event Type <span class="text-danger">*</span></label>
                                    <select name="event_type" 
                                            id="event_type" 
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('event_type') is-invalid @enderror" 
                                            required>
                                        <option value="">Select event type...</option>
                                        @foreach($types as $key => $value)
                                            <option value="{{ $key }}" {{ old('event_type') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('event_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" 
                                              id="description" 
                                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Event description...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Location -->
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" 
                                           name="location" 
                                           id="location" 
                                           class="form-control @error('location') is-invalid @enderror" 
                                           value="{{ old('location') }}" 
                                           maxlength="255"
                                           placeholder="Event location">
                                    @error('location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Date and Time -->
                                <div class="row">
                                    <div class="md:w-1/2 px-4">
                                        <div class="mb-3">
                                            <label for="start_datetime" class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                                            <input type="datetime-local" 
                                                   name="start_datetime" 
                                                   id="start_datetime" 
                                                   class="form-control @error('start_datetime') is-invalid @enderror" 
                                                   value="{{ old('start_datetime') }}" 
                                                   required>
                                            @error('start_datetime')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="md:w-1/2 px-4">
                                        <div class="mb-3">
                                            <label for="end_datetime" class="form-label">End Date & Time <span class="text-danger">*</span></label>
                                            <input type="datetime-local" 
                                                   name="end_datetime" 
                                                   id="end_datetime" 
                                                   class="form-control @error('end_datetime') is-invalid @enderror" 
                                                   value="{{ old('end_datetime') }}" 
                                                   required>
                                            @error('end_datetime')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- All Day Event -->
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="all_day" 
                                               id="all_day" 
                                               value="1" 
                                               {{ old('all_day') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="all_day">
                                            All Day Event
                                        </label>
                                    </div>
                                </div>

                                <!-- Status and Priority -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select name="status" 
                                                    id="status" 
                                                    class="form-select @error('status') is-invalid @enderror" 
                                                    required>
                                                @foreach($statuses as $key => $value)
                                                    <option value="{{ $key }}" {{ old('status', 'scheduled') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                            <select name="priority" 
                                                    id="priority" 
                                                    class="form-select @error('priority') is-invalid @enderror" 
                                                    required>
                                                @foreach($priorities as $key => $value)
                                                    <option value="{{ $key }}" {{ old('priority', 'medium') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('priority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Attendees -->
                                <div class="mb-3">
                                    <label for="attendees" class="form-label">Attendees</label>
                                    <input type="text" 
                                           name="attendees" 
                                           id="attendees" 
                                           class="form-control @error('attendees') is-invalid @enderror" 
                                           value="{{ old('attendees') }}" 
                                           placeholder="Enter attendee emails separated by commas">
                                    <div class="form-text">Separate multiple email addresses with commas</div>
                                    @error('attendees')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Reminder -->
                                <div class="mb-3">
                                    <label for="reminder_minutes" class="form-label">Reminder</label>
                                    <select name="reminder_minutes" id="reminder_minutes" class="form-select @error('reminder_minutes') is-invalid @enderror">
                                        <option value="">No Reminder</option>
                                        @foreach($reminderOptions as $key => $value)
                                            <option value="{{ $key }}" {{ old('reminder_minutes') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('reminder_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Additional notes...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-save mr-2"></i>Create Event
                                    </button>
                                    <a href="{{ route('clients.calendar-events.standalone.index') }}" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-xl-6">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Event Guidelines
                            </h5>
                        </div>
                        <div class="p-6">
                            <div class="small text-gray-600">
                                <h6>Event Types:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Meeting:</strong> General business meetings</li>
                                    <li><strong>Appointment:</strong> Scheduled client appointments</li>
                                    <li><strong>Consultation:</strong> Advisory or consultation sessions</li>
                                    <li><strong>Training:</strong> Training or educational sessions</li>
                                    <li><strong>Maintenance:</strong> System or equipment maintenance</li>
                                    <li><strong>Support:</strong> Technical support sessions</li>
                                    <li><strong>Follow-up:</strong> Follow-up calls or meetings</li>
                                </ul>

                                <h6 class="mt-3">Priority Levels:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>High:</strong> Urgent events requiring immediate attention</li>
                                    <li><strong>Medium:</strong> Standard priority events</li>
                                    <li><strong>Low:</strong> Low priority or optional events</li>
                                </ul>

                                <h6 class="mt-3">Tips:</h6>
                                <ul class="list-unstyled">
                                    <li>• Use descriptive titles for easy identification</li>
                                    <li>• Include location details for on-site events</li>
                                    <li>• Set reminders for important events</li>
                                    <li>• Add attendee emails for notifications</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle all-day checkbox
    const allDayCheck = document.getElementById('all_day');
    const startDateTime = document.getElementById('start_datetime');
    const endDateTime = document.getElementById('end_datetime');

    allDayCheck.addEventListener('change', function() {
        if (this.checked) {
            // Convert to date inputs for all-day events
            const startDate = startDateTime.value ? startDateTime.value.split('T')[0] : '';
            const endDate = endDateTime.value ? endDateTime.value.split('T')[0] : '';
            
            startDateTime.type = 'date';
            endDateTime.type = 'date';
            
            if (startDate) startDateTime.value = startDate;
            if (endDate) endDateTime.value = endDate;
        } else {
            // Convert back to datetime inputs
            startDateTime.type = 'datetime-local';
            endDateTime.type = 'datetime-local';
        }
    });

    // Auto-update end time when start time changes
    startDateTime.addEventListener('change', function() {
        if (!allDayCheck.checked && this.value && !endDateTime.value) {
            const startTime = new Date(this.value);
            const endTime = new Date(startTime.getTime() + (60 * 60 * 1000)); // Add 1 hour
            
            const year = endTime.getFullYear();
            const month = String(endTime.getMonth() + 1).padStart(2, '0');
            const day = String(endTime.getDate()).padStart(2, '0');
            const hours = String(endTime.getHours()).padStart(2, '0');
            const minutes = String(endTime.getMinutes()).padStart(2, '0');
            
            endDateTime.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
    });
});
</script>
@endpush