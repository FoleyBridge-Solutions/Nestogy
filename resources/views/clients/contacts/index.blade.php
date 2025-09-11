@extends('layouts.app')

@section('title', $client->name . ' - Contacts')

@section('content')
<div class="container-fluid">
    <!-- Client Context Card -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:avatar size="lg" class="bg-blue-500">
                    {{ substr($client->name, 0, 2) }}
                </flux:avatar>
                <div>
                    <flux:heading size="lg">{{ $client->name }}</flux:heading>
                    <flux:text>Managing contacts for this client</flux:text>
                </div>
            </div>
            <div class="flex gap-2">
                 <flux:button variant="ghost" href="{{ route('clients.index') }}">
                     Back to Dashboard
                 </flux:button>
                <flux:button variant="subtle" href="{{ route('clients.switch') }}">
                    Switch Client
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Header Card -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Contacts</flux:heading>
                <flux:text>Manage contacts for {{ $client->name }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button 
                    variant="subtle" 
                    icon="arrow-down-tray"
                    href="{{ route('clients.contacts.export', request()->query()) }}"
                >
                    Export CSV
                </flux:button>
                <flux:button 
                    variant="primary" 
                    icon="plus"
                    href="{{ route('clients.contacts.create') }}"
                >
                    Add Contact
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Filters Card -->
    <flux:card class="mb-4">
        <form method="GET" action="{{ route('clients.contacts.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input 
                    name="search" 
                    placeholder="Search contacts..." 
                    icon="magnifying-glass"
                    value="{{ request('search') }}"
                />
                
                <flux:select name="type" placeholder="All Types" value="{{ request('type') }}">
                    <flux:select.option value="">All Types</flux:select.option>
                    <flux:select.option value="primary">Primary Contacts</flux:select.option>
                    <flux:select.option value="billing">Billing Contacts</flux:select.option>
                    <flux:select.option value="technical">Technical Contacts</flux:select.option>
                    <flux:select.option value="important">Important Contacts</flux:select.option>
                </flux:select>
                
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">
                        Apply Filters
                    </flux:button>
                    @if(request()->hasAny(['search', 'type']))
                        <flux:button 
                            variant="ghost" 
                            href="{{ route('clients.contacts.index') }}"
                        >
                            Clear
                        </flux:button>
                    @endif
                </div>
            </div>
        </form>
    </flux:card>

    <!-- Contacts Table -->
    <flux:card>
        @if($contacts->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Contact Info</flux:table.column>
                    <flux:table.column>Role</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Portal Access</flux:table.column>
                    <flux:table.column class="w-20"></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($contacts as $contact)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:avatar size="sm">
                                        {{ substr($contact->name, 0, 1) }}
                                    </flux:avatar>
                                    <div>
                                        <div class="font-medium">{{ $contact->name }}</div>
                                        @if($contact->title)
                                            <flux:text size="sm">{{ $contact->title }}</flux:text>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="space-y-1">
                                    @if($contact->email)
                                        <div class="flex items-center gap-1">
                                            <flux:icon.envelope variant="micro" class="text-zinc-400" />
                                            <flux:link href="mailto:{{ $contact->email }}" class="text-sm">
                                                {{ $contact->email }}
                                            </flux:link>
                                        </div>
                                    @endif
                                    @if($contact->phone)
                                        <div class="flex items-center gap-1">
                                            <flux:icon.phone variant="micro" class="text-zinc-400" />
                                            <flux:text size="sm">{{ $contact->phone }}</flux:text>
                                        </div>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $contact->role ?? '-' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @if($contact->primary)
                                        <flux:badge color="blue" size="sm">Primary</flux:badge>
                                    @endif
                                    @if($contact->billing)
                                        <flux:badge color="green" size="sm">Billing</flux:badge>
                                    @endif
                                    @if($contact->technical)
                                        <flux:badge color="purple" size="sm">Technical</flux:badge>
                                    @endif
                                    @if($contact->important)
                                        <flux:badge color="amber" size="sm">Important</flux:badge>
                                    @endif
                                    @if(!$contact->primary && !$contact->billing && !$contact->technical && !$contact->important)
                                        <flux:badge color="zinc" size="sm">General</flux:badge>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($contact->has_portal_access)
                                    <flux:badge color="green" size="sm">
                                        <flux:icon.check variant="micro" />
                                        Active
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">No Access</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item 
                                            icon="eye"
                                            href="{{ route('clients.contacts.show', $contact) }}"
                                        >
                                            View
                                        </flux:menu.item>
                                        <flux:menu.item 
                                            icon="pencil"
                                            href="{{ route('clients.contacts.edit', $contact) }}"
                                        >
                                            Edit
                                        </flux:menu.item>
                                        <flux:separator />
                                        <form method="POST" action="{{ route('clients.contacts.destroy', $contact) }}" 
                                              onsubmit="return confirm('Are you sure you want to delete this contact?');">
                                            @csrf
                                            @method('DELETE')
                                            <flux:menu.item 
                                                icon="trash"
                                                type="submit"
                                                variant="danger"
                                            >
                                                Delete
                                            </flux:menu.item>
                                        </form>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            
            @if($contacts->hasPages())
                <div class="mt-4">
                    {{ $contacts->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <flux:icon.user-group class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No contacts found</flux:heading>
                <flux:text class="mt-2">
                    @if(request()->hasAny(['search', 'type']))
                        No contacts match your filters. Try adjusting your search criteria.
                    @else
                        Get started by adding your first contact for {{ $client->name }}.
                    @endif
                </flux:text>
                <div class="mt-6">
                    @if(request()->hasAny(['search', 'type']))
                        <flux:button variant="subtle" href="{{ route('clients.contacts.index') }}">
                            Clear Filters
                        </flux:button>
                    @else
                        <flux:button variant="primary" icon="plus" href="{{ route('clients.contacts.create') }}">
                            Add First Contact
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:card>
</div>
@endsection