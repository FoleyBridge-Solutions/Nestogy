@extends('layouts.app')

@section('title', 'Create Calendar Event')

@section('content')
<div class="w-full px-6">
    <div class="flex flex-wrap -mx-4">
        <div class="flex-1 px-6-12">
            <div class="flex justify-between items-center mb-6">
                <h1 class="h3 mb-0">Create Calendar Event</h1>
                <a href="{{ route('clients.calendar-events.standalone.index') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Events
                </a>
            </div>

            <div class="flex flex-wrap -mx-4">
                <div class="lg:w-2/3 px-4 flex-1 px-6-xl-6">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <form method="POST" action="{{ route('clients.calendar-events.standalone.store') }}">
                                @csrf

                                <!-- Client Selection -->
                                <div class="mb-6">
                                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-600">*</span></label>
                                    <select name="client_id" 
                                            id="client_id" 
                                            class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('client_id') border-red-500 @enderror" 
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
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Event Title -->
                                <div class="mb-6">
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Event Title <span class="text-red-600">*</span></label>
                                    <input type="text" 
                                           name="title" 
                                           id="title" 
                                           class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('title') border-red-500 @enderror" 
                                           value="{{ old('title') }}" 
                                           required 
                                           maxlength="255"
                                           placeholder="Enter event title">
                                    @error('title')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Event Type -->
                                <div class="mb-6">
                                    <label for="event_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Type <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <select name="event_type" 
                                            id="event_type" 
                                            class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('event_type') border-red-500 @enderror" 
                                            required>
                                        <option value="">Select event type...</option>
                                        @foreach($types as $key => $value)
                                            <option value="{{ $key }}" {{ old('event_type') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('event_type')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div class="mb-6">
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                    <textarea name="description" 
                                              id="description" 
                                              class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-500 @enderror" 
                                              rows="3" 
                                              placeholder="Event description...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Location -->
                                <div class="mb-6">
                                    <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                                    <input type="text" 
                                           name="location" 
                                           id="location" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('location') border-red-500 @enderror" 
                                           value="{{ old('location') }}" 
                                           maxlength="255"
                                           placeholder="Event location">
                                    @error('location')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Date and Time -->
                                <div class="flex flex-wrap -mx-4">
                                    <div class="md:w-1/2 px-6">
                                        <div class="mb-6">
                                            <label for="start_datetime" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date & Time <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <input type="datetime-local" 
                                                   name="start_datetime" 
                                                   id="start_datetime" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('start_datetime') border-red-500 @enderror" 
                                                   value="{{ old('start_datetime') }}" 
                                                   required>
                                            @error('start_datetime')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="md:w-1/2 px-6">
                                        <div class="mb-6">
                                            <label for="end_datetime" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date & Time <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <input type="datetime-local" 
                                                   name="end_datetime" 
                                                   id="end_datetime" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('end_datetime') border-red-500 @enderror" 
                                                   value="{{ old('end_datetime') }}" 
                                                   required>
                                            @error('end_datetime')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- All Day Event -->
                                <div class="mb-6">
                                    <div class="flex items-center">
                                        <input class="flex items-center-input" 
                                               type="checkbox" 
                                               name="all_day" 
                                               id="all_day" 
                                               value="1" 
                                               {{ old('all_day') ? 'checked' : '' }}>
                                        <label class="flex items-center-label" for="all_day">
                                            All Day Event
                                        </label>
                                    </div>
                                </div>

                                <!-- Status and Priority -->
                                <div class="flex flex-wrap -mx-4">
                                    <div class="flex-1 px-6-md-6">
                                        <div class="mb-6">
                                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <select name="status" 
                                                    id="status" 
                                                    class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('status') border-red-500 @enderror" 
                                                    required>
                                                @foreach($statuses as $key => $value)
                                                    <option value="{{ $key }}" {{ old('status', 'scheduled') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('status')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-6">
                                        <div class="mb-6">
                                            <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <select name="priority" 
                                                    id="priority" 
                                                    class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('priority') border-red-500 @enderror" 
                                                    required>
                                                @foreach($priorities as $key => $value)
                                                    <option value="{{ $key }}" {{ old('priority', 'medium') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('priority')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Attendees -->
                                <div class="mb-6">
                                    <label for="attendees" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Attendees</label>
                                    <input type="text" 
                                           name="attendees" 
                                           id="attendees" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('attendees') border-red-500 @enderror" 
                                           value="{{ old('attendees') }}" 
                                           placeholder="Enter attendee emails separated by commas">
                                    <div class="form-text">Separate multiple email addresses with commas</div>
                                    @error('attendees')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Reminder -->
                                <div class="mb-6">
                                    <label for="reminder_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reminder</label>
                                    <select name="reminder_minutes" id="reminder_minutes" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('reminder_minutes') border-red-500 @enderror">
                                        <option value="">No Reminder</option>
                                        @foreach($reminderOptions as $key => $value)
                                            <option value="{{ $key }}" {{ old('reminder_minutes') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('reminder_minutes')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="mb-6">
                                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('notes') border-red-500 @enderror" 
                                              rows="3" 
                                              placeholder="Additional notes...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-save mr-2"></i>Create Event
                                    </button>
                                    <a href="{{ route('clients.calendar-events.standalone.index') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="lg:w-1/3 px-4 flex-1 px-6-xl-6">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-6 border-b border-gray-200 bg-gray-50">
                            <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                                <i class="fas fa-info-circle mr-2"></i>Event Guidelines
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

                                <h6 class="mt-6">Priority Levels:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>High:</strong> Urgent events requiring immediate attention</li>
                                    <li><strong>Medium:</strong> Standard priority events</li>
                                    <li><strong>Low:</strong> Low priority or optional events</li>
                                </ul>

                                <h6 class="mt-6">Tips:</h6>
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
