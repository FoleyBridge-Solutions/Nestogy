@extends('layouts.app')

@section('title', 'Edit Ticket')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Ticket #{{ $ticket->number }}</h3>
                </div>
                <form action="{{ route('tickets.update', $ticket) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_id">Client <span class="text-danger">*</span></label>
                                    <select name="client_id" id="client_id" class="form-control @error('client_id') is-invalid @enderror" required>
                                        <option value="">Select Client</option>
                                        @foreach(\App\Models\Client::where('company_id', auth()->user()->company_id)->orderBy('name')->get() as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id', $ticket->client_id) == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_id">Contact</label>
                                    <select name="contact_id" id="contact_id" class="form-control @error('contact_id') is-invalid @enderror">
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
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="subject">Subject <span class="text-danger">*</span></label>
                                    <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" 
                                           value="{{ old('subject', $ticket->subject) }}" required>
                                    @error('subject')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="priority">Priority <span class="text-danger">*</span></label>
                                    <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror" required>
                                        <option value="Low" {{ old('priority', $ticket->priority) == 'Low' ? 'selected' : '' }}>Low</option>
                                        <option value="Medium" {{ old('priority', $ticket->priority) == 'Medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="High" {{ old('priority', $ticket->priority) == 'High' ? 'selected' : '' }}>High</option>
                                        <option value="Critical" {{ old('priority', $ticket->priority) == 'Critical' ? 'selected' : '' }}>Critical</option>
                                        <option value="Critical" {{ old('priority', $ticket->priority) == 'Critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                    @error('priority')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                        <option value="Open" {{ old('status', $ticket->status) == 'Open' ? 'selected' : '' }}>Open</option>
                                        <option value="In Progress" {{ old('status', $ticket->status) == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="On Hold" {{ old('status', $ticket->status) == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                                        <option value="Resolved" {{ old('status', $ticket->status) == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                                        <option value="Closed" {{ old('status', $ticket->status) == 'Closed' ? 'selected' : '' }}>Closed</option>
                                    </select>
                                    @error('status')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="assigned_to">Assign To</label>
                                    <select name="assigned_to" id="assigned_to" class="form-control @error('assigned_to') is-invalid @enderror">
                                        <option value="">Unassigned</option>
                                        @foreach(\App\Models\User::where('company_id', auth()->user()->company_id)->where('status', 1)->orderBy('name')->get() as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to', $ticket->assigned_to) == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="details">Details <span class="text-danger">*</span></label>
                                    <textarea name="details" id="details" rows="8" class="form-control @error('details') is-invalid @enderror" required>{{ old('details', $ticket->details) }}</textarea>
                                    @error('details')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="asset_id">Asset</label>
                                    <select name="asset_id" id="asset_id" class="form-control @error('asset_id') is-invalid @enderror">
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
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vendor_id">Vendor</label>
                                    <select name="vendor_id" id="vendor_id" class="form-control @error('vendor_id') is-invalid @enderror">
                                        <option value="">Select Vendor</option>
                                        @foreach(\App\Models\Vendor::where('company_id', auth()->user()->company_id)->orderBy('name')->get() as $vendor)
                                            <option value="{{ $vendor->id }}" {{ old('vendor_id', $ticket->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vendor_ticket_number">Vendor Ticket Number</label>
                                    <input type="text" name="vendor_ticket_number" id="vendor_ticket_number" 
                                           class="form-control @error('vendor_ticket_number') is-invalid @enderror" 
                                           value="{{ old('vendor_ticket_number', $ticket->vendor_ticket_number) }}">
                                    @error('vendor_ticket_number')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="billable">Billable</label>
                                    <select name="billable" id="billable" class="form-control @error('billable') is-invalid @enderror">
                                        <option value="0" {{ old('billable', $ticket->billable) == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('billable', $ticket->billable) == '1' ? 'selected' : '' }}>Yes</option>
                                    </select>
                                    @error('billable')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="schedule">Schedule</label>
                                    <input type="datetime-local" name="schedule" id="schedule" 
                                           class="form-control @error('schedule') is-invalid @enderror" 
                                           value="{{ old('schedule', $ticket->schedule ? $ticket->schedule->format('Y-m-d\TH:i') : '') }}">
                                    @error('schedule')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="onsite">Onsite</label>
                                    <select name="onsite" id="onsite" class="form-control @error('onsite') is-invalid @enderror">
                                        <option value="0" {{ old('onsite', $ticket->onsite) == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('onsite', $ticket->onsite) == '1' ? 'selected' : '' }}>Yes</option>
                                    </select>
                                    @error('onsite')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Ticket</button>
                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-secondary">Cancel</a>
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