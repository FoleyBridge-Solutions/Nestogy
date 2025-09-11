@extends('layouts.app')

@section('title', 'Edit Ticket #' . $ticket->ticket_number)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Edit Ticket #{{ $ticket->ticket_number }}</flux:heading>
                <flux:text>Update ticket information and status</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button href="{{ route('tickets.show', $ticket) }}" 
                            variant="ghost"
                            icon="eye">
                    View Ticket
                </flux:button>
                <flux:button href="{{ route('tickets.index') }}" 
                            variant="ghost"
                            icon="arrow-left">
                    Back to Tickets
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Edit Form -->
    <form action="{{ route('tickets.update', $ticket) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Main Content (2 columns) -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Basic Information -->
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Basic Information</flux:heading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label for="client_id" required>Client</flux:label>
                            <flux:select name="client_id" id="client_id" required value="{{ old('client_id', $ticket->client_id) }}">
                                <flux:select.option value="">Select Client</flux:select.option>
                                @foreach(\App\Models\Client::where('company_id', auth()->user()->company_id)->orderBy('name')->get() as $client)
                                    <flux:select.option value="{{ $client->id }}">
                                        {{ $client->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="client_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label for="contact_id">Contact</flux:label>
                            <flux:select name="contact_id" id="contact_id" value="{{ old('contact_id', $ticket->contact_id) }}">
                                <flux:select.option value="">Select Contact</flux:select.option>
                                @if($ticket->client)
                                    @foreach($ticket->client->contacts as $contact)
                                        <flux:select.option value="{{ $contact->id }}">
                                            {{ $contact->name }}
                                        </flux:select.option>
                                    @endforeach
                                @endif
                            </flux:select>
                            <flux:error name="contact_id" />
                        </flux:field>
                    </div>

                    <flux:field class="mt-4">
                        <flux:label for="subject" required>Subject</flux:label>
                        <flux:input type="text" name="subject" id="subject" 
                                   value="{{ old('subject', $ticket->subject) }}" 
                                   required />
                        <flux:error name="subject" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label for="details" required>Details</flux:label>
                        <flux:textarea name="details" id="details" rows="8" required>{{ old('details', $ticket->details) }}</flux:textarea>
                        <flux:error name="details" />
                    </flux:field>
                </flux:card>

                <!-- Additional Information -->
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Additional Information</flux:heading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:field>
                            <flux:label for="asset_id">Asset</flux:label>
                            <flux:select name="asset_id" id="asset_id" value="{{ old('asset_id', $ticket->asset_id) }}">
                                <flux:select.option value="">Select Asset</flux:select.option>
                                @if($ticket->client)
                                    @foreach($ticket->client->assets as $asset)
                                        <flux:select.option value="{{ $asset->id }}">
                                            {{ $asset->name }} ({{ $asset->type }})
                                        </flux:select.option>
                                    @endforeach
                                @endif
                            </flux:select>
                            <flux:error name="asset_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label for="vendor_id">Vendor</flux:label>
                            <flux:select name="vendor_id" id="vendor_id" value="{{ old('vendor_id', $ticket->vendor_id) }}">
                                <flux:select.option value="">Select Vendor</flux:select.option>
                                @foreach(\App\Models\Vendor::where('company_id', auth()->user()->company_id)->orderBy('name')->get() as $vendor)
                                    <flux:select.option value="{{ $vendor->id }}">
                                        {{ $vendor->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="vendor_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label for="vendor_ticket_number">Vendor Ticket Number</flux:label>
                            <flux:input type="text" name="vendor_ticket_number" id="vendor_ticket_number" 
                                       value="{{ old('vendor_ticket_number', $ticket->vendor_ticket_number) }}" />
                            <flux:error name="vendor_ticket_number" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <flux:field>
                            <flux:label for="due_date">Due Date</flux:label>
                            <flux:input type="datetime-local" name="due_date" id="due_date" 
                                       value="{{ old('due_date', $ticket->due_date ? $ticket->due_date->format('Y-m-d\TH:i') : '') }}" />
                            <flux:error name="due_date" />
                        </flux:field>

                        <flux:field>
                            <flux:label for="scheduled_date">Scheduled Date</flux:label>
                            <flux:input type="datetime-local" name="scheduled_date" id="scheduled_date" 
                                       value="{{ old('scheduled_date', $ticket->scheduled_date ? $ticket->scheduled_date->format('Y-m-d\TH:i') : '') }}" />
                            <flux:error name="scheduled_date" />
                        </flux:field>
                    </div>
                </flux:card>
            </div>

            <!-- Sidebar (1 column) -->
            <div class="lg:col-span-1 space-y-4">
                <!-- Status & Priority -->
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Status & Assignment</flux:heading>
                    
                    <flux:field>
                        <flux:label for="status" required>Status</flux:label>
                        <flux:select name="status" id="status" required value="{{ old('status', $ticket->status) }}">
                            <flux:select.option value="new">New</flux:select.option>
                            <flux:select.option value="open">Open</flux:select.option>
                            <flux:select.option value="in_progress">In Progress</flux:select.option>
                            <flux:select.option value="pending">Pending</flux:select.option>
                            <flux:select.option value="resolved">Resolved</flux:select.option>
                            <flux:select.option value="closed">Closed</flux:select.option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label for="priority" required>Priority</flux:label>
                        <flux:select name="priority" id="priority" required value="{{ old('priority', $ticket->priority) }}">
                            <flux:select.option value="Low">Low</flux:select.option>
                            <flux:select.option value="Medium">Medium</flux:select.option>
                            <flux:select.option value="High">High</flux:select.option>
                            <flux:select.option value="Critical">Critical</flux:select.option>
                        </flux:select>
                        <flux:error name="priority" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label for="assigned_to">Assign To</flux:label>
                        <flux:select name="assigned_to" id="assigned_to" value="{{ old('assigned_to', $ticket->assigned_to) }}">
                            <flux:select.option value="">Unassigned</flux:select.option>
                            @foreach(\App\Models\User::where('company_id', auth()->user()->company_id)->where('status', 1)->orderBy('name')->get() as $user)
                                <flux:select.option value="{{ $user->id }}">
                                    {{ $user->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="assigned_to" />
                    </flux:field>
                </flux:card>

                <!-- Billing Settings -->
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Billing Settings</flux:heading>
                    
                    <flux:field>
                        <flux:label for="billable">Billable</flux:label>
                        <flux:select name="billable" id="billable" value="{{ old('billable', $ticket->billable) }}">
                            <flux:select.option value="0">No</flux:select.option>
                            <flux:select.option value="1">Yes</flux:select.option>
                        </flux:select>
                        <flux:error name="billable" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label for="invoice_id">Invoice</flux:label>
                        <flux:select name="invoice_id" id="invoice_id" value="{{ old('invoice_id', $ticket->invoice_id) }}">
                            <flux:select.option value="">Select Invoice</flux:select.option>
                            @if($ticket->client)
                                @foreach($ticket->client->invoices as $invoice)
                                    <flux:select.option value="{{ $invoice->id }}">
                                        #{{ $invoice->invoice_number }} - {{ $invoice->date->format('M d, Y') }}
                                    </flux:select.option>
                                @endforeach
                            @endif
                        </flux:select>
                        <flux:error name="invoice_id" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label for="rate">Rate</flux:label>
                        <flux:input type="number" name="rate" id="rate" step="0.01" 
                                   value="{{ old('rate', $ticket->rate ?? 0) }}" />
                        <flux:error name="rate" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label for="estimated_hours">Estimated Hours</flux:label>
                        <flux:input type="number" name="estimated_hours" id="estimated_hours" step="0.25" 
                                   value="{{ old('estimated_hours', $ticket->estimated_hours) }}" />
                        <flux:error name="estimated_hours" />
                    </flux:field>
                </flux:card>

                <!-- Tags -->
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Tags</flux:heading>
                    
                    <flux:field>
                        <flux:label for="tags">Tags (comma separated)</flux:label>
                        <flux:input type="text" name="tags" id="tags" 
                                   placeholder="bug, urgent, api"
                                   value="{{ old('tags', is_array($ticket->tags) ? implode(', ', $ticket->tags) : '') }}" />
                        <flux:description>Enter tags separated by commas</flux:description>
                        <flux:error name="tags" />
                    </flux:field>
                </flux:card>
            </div>
        </div>

        <!-- Form Actions -->
        <flux:card class="mt-4">
            <div class="flex items-center justify-between">
                <flux:text size="sm" class="flex items-center gap-1">
                    <flux:icon name="information-circle" variant="mini" />
                    Last updated {{ $ticket->updated_at->diffForHumans() }}
                </flux:text>
                <div class="flex gap-2">
                    <flux:button href="{{ route('tickets.show', $ticket) }}" 
                                variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" 
                                variant="primary"
                                icon="check">
                        Update Ticket
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </form>
</div>
@endsection