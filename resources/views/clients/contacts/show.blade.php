@extends('layouts.app')

@section('title', $contact->name . ' - Contact Details')

@section('content')
<div class="container-fluid max-w-5xl">
    <!-- Header -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <flux:avatar size="xl">
                    {{ substr($contact->name, 0, 1) }}
                </flux:avatar>
                <div>
                    <flux:heading size="xl">{{ $contact->name }}</flux:heading>
                    @if($contact->title)
                        <flux:text size="lg">{{ $contact->title }}</flux:text>
                    @endif
                    <div class="flex items-center gap-2 mt-2">
                        @if($contact->primary)
                            <flux:badge color="blue">Primary Contact</flux:badge>
                        @endif
                        @if($contact->billing)
                            <flux:badge color="green">Billing</flux:badge>
                        @endif
                        @if($contact->technical)
                            <flux:badge color="purple">Technical</flux:badge>
                        @endif
                        @if($contact->important)
                            <flux:badge color="amber">Important</flux:badge>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" icon="arrow-left" href="{{ route('clients.contacts.index') }}">
                    Back to Contacts
                </flux:button>
                <flux:button variant="primary" icon="pencil" href="{{ route('clients.contacts.edit', $contact) }}">
                    Edit Contact
                </flux:button>
            </div>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Main Information -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Contact Information -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Contact Information</flux:heading>
                
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Email</dt>
                        <dd class="mt-1">
                            @if($contact->email)
                                <flux:link href="mailto:{{ $contact->email }}" class="flex items-center gap-1">
                                    <flux:icon.envelope variant="micro" />
                                    {{ $contact->email }}
                                </flux:link>
                            @else
                                <flux:text>-</flux:text>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Phone</dt>
                        <dd class="mt-1">
                            @if($contact->phone)
                                <div class="flex items-center gap-1">
                                    <flux:icon.phone variant="micro" />
                                    <flux:text>{{ $contact->phone }}</flux:text>
                                    @if($contact->extension)
                                        <flux:text size="sm" class="text-zinc-500">ext. {{ $contact->extension }}</flux:text>
                                    @endif
                                </div>
                            @else
                                <flux:text>-</flux:text>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Mobile</dt>
                        <dd class="mt-1">
                            @if($contact->mobile)
                                <div class="flex items-center gap-1">
                                    <flux:icon.device-phone-mobile variant="micro" />
                                    <flux:text>{{ $contact->mobile }}</flux:text>
                                </div>
                            @else
                                <flux:text>-</flux:text>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Department</dt>
                        <dd class="mt-1">
                            <flux:text>{{ $contact->department ?: '-' }}</flux:text>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Role</dt>
                        <dd class="mt-1">
                            <flux:text>{{ $contact->role ?: '-' }}</flux:text>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Client</dt>
                        <dd class="mt-1">
                            <flux:link href="{{ route('clients.index') }}">
                                {{ $client->name }}
                            </flux:link>
                        </dd>
                    </div>
                </dl>
            </flux:card>

            <!-- Notes -->
            @if($contact->notes)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Notes</flux:heading>
                    <flux:text class="whitespace-pre-wrap">{{ $contact->notes }}</flux:text>
                </flux:card>
            @endif

            <!-- Portal Access -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Portal Access</flux:heading>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:text class="font-medium">Portal Access Status</flux:text>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ $contact->has_portal_access ? 'This contact can access the client portal' : 'This contact cannot access the client portal' }}
                            </flux:text>
                        </div>
                        @if($contact->has_portal_access)
                            <flux:badge color="green" size="lg">
                                <flux:icon.check-circle variant="micro" />
                                Active
                            </flux:badge>
                        @else
                            <flux:badge color="zinc" size="lg">
                                <flux:icon.x-circle variant="micro" />
                                Inactive
                            </flux:badge>
                        @endif
                    </div>

                    @if($contact->has_portal_access)
                        <flux:separator />
                        
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Authentication Method</dt>
                                <dd class="mt-1">
                                    <flux:text>{{ ucfirst($contact->auth_method ?? 'Not set') }}</flux:text>
                                </dd>
                            </div>

                            @if($contact->email_verified_at)
                                <div>
                                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Email Verified</dt>
                                    <dd class="mt-1">
                                        <flux:badge color="green" size="sm">
                                            <flux:icon.check variant="micro" />
                                            Verified
                                        </flux:badge>
                                    </dd>
                                </div>
                            @endif

                            @if($contact->last_login_at)
                                <div>
                                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Last Login</dt>
                                    <dd class="mt-1">
                                        <flux:text>{{ $contact->last_login_at->diffForHumans() }}</flux:text>
                                    </dd>
                                </div>
                            @endif

                            @if($contact->session_timeout_minutes)
                                <div>
                                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Session Timeout</dt>
                                    <dd class="mt-1">
                                        <flux:text>{{ $contact->session_timeout_minutes }} minutes</flux:text>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    @endif
                </div>
            </flux:card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Quick Actions -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>
                
                <div class="space-y-2">
                    @if($contact->email)
                        <flux:button variant="subtle" class="w-full justify-start" icon="envelope" href="mailto:{{ $contact->email }}">
                            Send Email
                        </flux:button>
                    @endif
                    
                    @if($contact->phone)
                        <flux:button variant="subtle" class="w-full justify-start" icon="phone" href="tel:{{ $contact->phone }}">
                            Call Phone
                        </flux:button>
                    @endif
                    
                    @if($contact->mobile)
                        <flux:button variant="subtle" class="w-full justify-start" icon="device-phone-mobile" href="tel:{{ $contact->mobile }}">
                            Call Mobile
                        </flux:button>
                    @endif
                    
                    <flux:separator />
                    
                    <flux:button variant="subtle" class="w-full justify-start" icon="pencil" href="{{ route('clients.contacts.edit', $contact) }}">
                        Edit Contact
                    </flux:button>
                    
                    <form method="POST" action="{{ route('clients.contacts.destroy', $contact) }}" 
                          onsubmit="return confirm('Are you sure you want to delete this contact?');">
                        @csrf
                        @method('DELETE')
                        <flux:button type="submit" variant="danger" class="w-full justify-start" icon="trash">
                            Delete Contact
                        </flux:button>
                    </form>
                </div>
            </flux:card>

            <!-- Metadata -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Information</flux:heading>
                
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Created</dt>
                        <dd class="mt-1">
                            <flux:text size="sm">{{ $contact->created_at->format('M d, Y g:i A') }}</flux:text>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Last Updated</dt>
                        <dd class="mt-1">
                            <flux:text size="sm">{{ $contact->updated_at->format('M d, Y g:i A') }}</flux:text>
                        </dd>
                    </div>
                    
                    @if($contact->created_by)
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Created By</dt>
                            <dd class="mt-1">
                                <flux:text size="sm">{{ $contact->creator->name ?? 'System' }}</flux:text>
                            </dd>
                        </div>
                    @endif
                </dl>
            </flux:card>
        </div>
    </div>
</div>
@endsection