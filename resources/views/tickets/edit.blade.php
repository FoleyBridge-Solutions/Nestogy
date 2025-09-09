@extends('layouts.app')

@section('title', 'Edit Ticket')

@section('content')
<div class="w-full px-6">
    <div class="flex flex-wrap -mx-4">
        <div class="flex-1 px-6-12">
            <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 dark:bg-gray-900">
                    <h3 class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title">Edit Ticket #{{ $ticket->number }}</h3>
                </div>
                <form action="{{ route('tickets.update', $ticket) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-6">
                        <div class="flex flex-wrap -mx-4">
                            <div class="md:w-1/2 px-6">
                                <div class="mb-6">
                                    <label for="client_id">Client <span class="text-red-600">*</span></label>
                                    <select name="client_id" id="client_id" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('client_id') border-red-500 @enderror" required>
                                        <option value="">Select Client</option>
                                        @foreach(\App\Models\Client::where('company_id', auth()->user()->company_id)->orderBy('name')->get() as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id', $ticket->client_id) == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="md:w-1/2 px-6">
                                <div class="mb-6">
                                    <label for="contact_id">Contact</label>
                                    <select name="contact_id" id="contact_id" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('contact_id') border-red-500 @enderror">
                                        <option value="">Select Contact</option>
                                        @if($ticket->client)
                                            @foreach($ticket->client->contacts as $contact)
                                                <option value="{{ $contact->id }}" {{ old('contact_id', $ticket->contact_id) == $contact->id ? 'selected' : '' }}>
                                                    {{ $contact->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('contact_id')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-md-12">
                                <div class="mb-6">
                                    <label for="subject">Subject <span class="text-red-600">*</span></label>
                                    <input type="text" name="subject" id="subject" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('subject') border-red-500 @enderror" 
                                           value="{{ old('subject', $ticket->subject) }}" required>
                                    @error('subject')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-md-4">
                                <div class="mb-6">
                                    <label for="priority">Priority <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <select name="priority" id="priority" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('priority') border-red-500 @enderror" required>
                                        <option value="Low" {{ old('priority', $ticket->priority) == 'Low' ? 'selected' : '' }}>Low</option>
                                        <option value="Medium" {{ old('priority', $ticket->priority) == 'Medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="High" {{ old('priority', $ticket->priority) == 'High' ? 'selected' : '' }}>High</option>
                                        <option value="Critical" {{ old('priority', $ticket->priority) == 'Critical' ? 'selected' : '' }}>Critical</option>
                                        <option value="Critical" {{ old('priority', $ticket->priority) == 'Critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                    @error('priority')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-4">
                                <div class="mb-6">
                                    <label for="status">Status <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <select name="status" id="status" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('status') border-red-500 @enderror" required>
                                        <option value="Open" {{ old('status', $ticket->status) == 'Open' ? 'selected' : '' }}>Open</option>
                                        <option value="In Progress" {{ old('status', $ticket->status) == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="On Hold" {{ old('status', $ticket->status) == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                                        <option value="Resolved" {{ old('status', $ticket->status) == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                                        <option value="Closed" {{ old('status', $ticket->status) == 'Closed' ? 'selected' : '' }}>Closed</option>
                                    </select>
                                    @error('status')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-4">
                                <div class="mb-6">
                                    <label for="assigned_to">Assign To</label>
                                    <select name="assigned_to" id="assigned_to" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('assigned_to') border-red-500 @enderror">
                                        <option value="">Unassigned</option>
                                        @foreach(\App\Models\User::where('company_id', auth()->user()->company_id)->where('status', 1)->orderBy('name')->get() as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to', $ticket->assigned_to) == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-md-12">
                                <div class="mb-6">
                                    <label for="details">Details <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <textarea name="details" id="details" rows="8" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('details') border-red-500 @enderror" required>{{ old('details', $ticket->details) }}</textarea>
                                    @error('details')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-md-4">
                                <div class="mb-6">
                                    <label for="asset_id">Asset</label>
                                    <select name="asset_id" id="asset_id" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('asset_id') border-red-500 @enderror">
                                        <option value="">Select Asset</option>
                                        @if($ticket->client)
                                            @foreach($ticket->client->assets as $asset)
                                                <option value="{{ $asset->id }}" {{ old('asset_id', $ticket->asset_id) == $asset->id ? 'selected' : '' }}>
                                                    {{ $asset->name }} ({{ $asset->type }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('asset_id')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-4">
                                <div class="mb-6">
                                    <label for="vendor_id">Vendor</label>
                                    <select name="vendor_id" id="vendor_id" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('vendor_id') border-red-500 @enderror">
                                        <option value="">Select Vendor</option>
                                        @foreach(\App\Models\Vendor::where('company_id', auth()->user()->company_id)->orderBy('name')->get() as $vendor)
                                            <option value="{{ $vendor->id }}" {{ old('vendor_id', $ticket->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-4">
                                <div class="mb-6">
                                    <label for="vendor_ticket_number">Vendor Ticket Number</label>
                                    <input type="text" name="vendor_ticket_number" id="vendor_ticket_number" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('vendor_ticket_number') border-red-500 @enderror" 
                                           value="{{ old('vendor_ticket_number', $ticket->vendor_ticket_number) }}">
                                    @error('vendor_ticket_number')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-md-4">
                                <div class="mb-6">
                                    <label for="billable">Billable</label>
                                    <select name="billable" id="billable" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('billable') border-red-500 @enderror">
                                        <option value="0" {{ old('billable', $ticket->billable) == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('billable', $ticket->billable) == '1' ? 'selected' : '' }}>Yes</option>
                                    </select>
                                    @error('billable')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-4">
                                <div class="mb-6">
                                    <label for="schedule">Schedule</label>
                                    <input type="datetime-local" name="schedule" id="schedule" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('schedule') border-red-500 @enderror" 
                                           value="{{ old('schedule', $ticket->schedule ? $ticket->schedule->format('Y-m-d\TH:i') : '') }}">
                                    @error('schedule')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-4">
                                <div class="mb-6">
                                    <label for="onsite">Onsite</label>
                                    <select name="onsite" id="onsite" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('onsite') border-red-500 @enderror">
                                        <option value="0" {{ old('onsite', $ticket->onsite) == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('onsite', $ticket->onsite) == '1' ? 'selected' : '' }}>Yes</option>
                                    </select>
                                    @error('onsite')
                                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-footer">
                        <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Update Ticket</button>
                        <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center px-6 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load contacts when client is selected
    $('#client_id').on('change', function() {
        var clientId = $(this).val();
        var contactSelect = $('#contact_id');
        var assetSelect = $('#asset_id');
        
        // Clear and reset contacts
        contactSelect.html('<option value="">Select Contact</option>');
        assetSelect.html('<option value="">Select Asset</option>');
        
        if (clientId) {
            // Load contacts
            $.get('/api/clients/' + clientId + '/contacts', function(data) {
                $.each(data, function(index, contact) {
                    contactSelect.append('<option value="' + contact.id + '">' + contact.name + '</option>');
                });
            });
            
            // Load assets
            $.get('/api/clients/' + clientId + '/assets', function(data) {
                $.each(data, function(index, asset) {
                    assetSelect.append('<option value="' + asset.id + '">' + asset.name + ' (' + asset.type + ')</option>');
                });
            });
        }
    });
});
</script>
@endpush
