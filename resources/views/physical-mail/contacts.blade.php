@extends('layouts.app')

@section('title', 'Mail Contacts')

@section('content')
<div class="container-fluid">
    <div class="mb-6">
        <flux:heading size="xl">Mail Contacts</flux:heading>
        <flux:text class="text-zinc-500">Manage frequently used mailing addresses</flux:text>
        
        <div class="mt-4">
            <flux:button onclick="addContact()" icon="plus">
                Add Contact
            </flux:button>
        </div>
    </div>

    <!-- Contact List -->
    @php
        $clients = \App\Models\Client::whereNotNull('address')
            ->orderBy('name')
            ->get();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($clients as $client)
            <flux:card>
                <flux:card.header>
                    <flux:card.title>{{ $client->name }}</flux:card.title>
                    @if($client->is_active)
                        <flux:badge variant="green">Active</flux:badge>
                    @else
                        <flux:badge variant="zinc">Inactive</flux:badge>
                    @endif
                </flux:card.header>
                
                <flux:card.body>
                    <div class="space-y-2">
                        @if($client->contact_name)
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-zinc-400 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                                <flux:text size="sm">{{ $client->contact_name }}</flux:text>
                            </div>
                        @endif
                        
                        @if($client->address)
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-zinc-400 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <flux:text size="sm">{{ $client->address }}</flux:text>
                                    @if($client->address_line_2)
                                        <flux:text size="sm">{{ $client->address_line_2 }}</flux:text>
                                    @endif
                                    <flux:text size="sm">
                                        {{ $client->city }}, {{ $client->state }} {{ $client->zip }}
                                    </flux:text>
                                </div>
                            </div>
                        @endif
                        
                        @if($client->phone)
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-zinc-400 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                </svg>
                                <flux:text size="sm">{{ $client->phone }}</flux:text>
                            </div>
                        @endif
                        
                        @if($client->email)
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-zinc-400 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                                <flux:text size="sm">{{ $client->email }}</flux:text>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Recent Mail Count -->
                    @php
                        $mailCount = \App\Domains\PhysicalMail\Models\PhysicalMailOrder::where('client_id', $client->id)->count();
                    @endphp
                    @if($mailCount > 0)
                        <flux:separator class="my-3" />
                        <flux:text size="xs" class="text-zinc-500">
                            {{ $mailCount }} {{ Str::plural('letter', $mailCount) }} sent
                        </flux:text>
                    @endif
                </flux:card.body>
                
                <flux:card.footer class="flex gap-2">
                    <flux:button size="sm" variant="secondary" 
                        onclick="sendMailTo('{{ $client->id }}', '{{ $client->name }}')">
                        Send Mail
                    </flux:button>
                    <flux:button size="sm" variant="ghost" 
                        onclick="editContact('{{ $client->id }}')">
                        Edit
                    </flux:button>
                    <flux:button size="sm" variant="ghost" 
                        onclick="viewHistory('{{ $client->id }}')">
                        History
                    </flux:button>
                </flux:card.footer>
            </flux:card>
        @endforeach
        
        @if($clients->isEmpty())
            <div class="col-span-full">
                <flux:card>
                    <flux:card.body class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-zinc-300 mb-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                        </svg>
                        <flux:text size="lg" class="text-zinc-500 mb-2">No contacts with addresses</flux:text>
                        <flux:text size="sm" class="text-zinc-400">Add client addresses to use them for physical mail</flux:text>
                    </flux:card.body>
                </flux:card>
            </div>
        @endif
    </div>

    <!-- Address Book Import -->
    <div class="mt-12">
        <flux:card>
            <flux:card.header>
                <flux:card.title>Import Addresses</flux:card.title>
                <flux:card.description>
                    Import addresses from CSV file or other sources
                </flux:card.description>
            </flux:card.header>
            <flux:card.body>
                <div class="flex items-center gap-4">
                    <flux:button variant="secondary" onclick="importCSV()">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        Import CSV
                    </flux:button>
                    
                    <flux:button variant="secondary" onclick="syncClients()">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        Sync from CRM
                    </flux:button>
                    
                    <flux:text size="sm" class="text-zinc-500">
                        Download <a href="#" class="text-blue-500 hover:underline">sample CSV template</a>
                    </flux:text>
                </div>
            </flux:card.body>
        </flux:card>
    </div>
</div>

@push('scripts')
<script>
function addContact() {
    alert('Add contact form coming soon!');
}

function editContact(clientId) {
    window.location.href = `/clients/${clientId}/edit`;
}

function sendMailTo(clientId, clientName) {
    window.location.href = `/mail/send?client=${clientId}`;
}

function viewHistory(clientId) {
    window.location.href = `/mail/tracking?client=${clientId}`;
}

function importCSV() {
    alert('CSV import coming soon!');
}

function syncClients() {
    alert('CRM sync coming soon!');
}
</script>
@endpush
@endsection