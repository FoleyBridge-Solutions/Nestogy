@extends('layouts.app')

@section('title', 'All Contacts')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">All Contacts</h1>
                <div>
                    <flux:button variant="primary" href="{{ route('clients.index') }}">
                        Select Client First
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <flux:card>
        <div class="p-4">
            <p class="text-muted mb-4">
                Please select a client first to view and manage their contacts.
            </p>
            
            @if($contacts->count() > 0)
                <h5 class="mb-3">Recent Contacts Across All Clients</h5>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Client</flux:table.column>
                        <flux:table.column>Email</flux:table.column>
                        <flux:table.column>Phone</flux:table.column>
                        <flux:table.column>Role</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($contacts as $contact)
                            <flux:table.row>
                                <flux:table.cell>
                                    {{ $contact->name }}
                                    @if($contact->primary)
                                        <flux:badge color="blue" size="sm" class="ml-2">Primary</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:link href="{{ route('clients.show', $contact->client) }}">
                                        {{ $contact->client->name }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:link href="mailto:{{ $contact->email }}">{{ $contact->email }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>{{ $contact->phone ?? 'N/A' }}</flux:table.cell>
                                <flux:table.cell>{{ $contact->role ?? 'Contact' }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:button variant="ghost" size="sm" href="{{ route('clients.contacts.edit', $contact) }}">
                                        Edit
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                
                <div class="mt-4">
                    {{ $contacts->links() }}
                </div>
            @else
                <flux:button variant="primary" size="lg" href="{{ route('clients.index') }}" class="mt-4">
                    Browse Clients
                </flux:button>
            @endif
        </div>
    </flux:card>
</div>
@endsection