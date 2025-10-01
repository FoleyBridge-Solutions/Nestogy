@extends('client-portal.layouts.app')

@section('title', 'Profile & Settings')

@section('content')
<!-- Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Profile & Settings</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your account information and preferences</p>
        </div>
    </div>
</div>

<!-- Profile Content -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <!-- Contact Information -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Contact Information</flux:heading>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Name</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $contact->name }}</div>
                    </div>
                    
                    @if($contact->title)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Title</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $contact->title }}</div>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($contact->email)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Email</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $contact->email }}</div>
                        </div>
                    @endif
                    
                    @if($contact->phone)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Phone</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $contact->phone }}</div>
                        </div>
                    @endif
                </div>

                @if($contact->department)
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Department</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $contact->department }}</div>
                    </div>
                @endif
            </div>
        </flux:card>

        <!-- Client Information -->
        @if($client)
            <flux:card>
                <flux:heading size="lg" class="mb-4">Organization</flux:heading>
                
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Company Name</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $client->name }}</div>
                    </div>

                    @if($client->email)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Company Email</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $client->email }}</div>
                        </div>
                    @endif

                    @if($client->phone)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Company Phone</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $client->phone }}</div>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Account Status -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Account Status</flux:heading>
            
            <div class="space-y-3 text-sm">
                <div>
                    <div class="text-gray-600 dark:text-gray-400">Portal Access</div>
                    <div class="font-semibold flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-600"></i>
                        Active
                    </div>
                </div>

                @if($contact->last_login_at)
                    <div>
                        <div class="text-gray-600 dark:text-gray-400">Last Login</div>
                        <div class="font-semibold">{{ $contact->last_login_at->diffForHumans() }}</div>
                    </div>
                @endif
            </div>
        </flux:card>

        <!-- Portal Permissions -->
        @php
            $permissions = $contact->portal_permissions ?? [];
        @endphp
        @if($permissions && count($permissions) > 0)
            <flux:card>
                <flux:heading size="lg" class="mb-4">Your Permissions</flux:heading>
                
                <div class="space-y-2 text-sm">
                    @foreach($permissions as $permission)
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check text-green-600"></i>
                            <span>{{ ucwords(str_replace(['can_', '_'], ['', ' '], $permission)) }}</span>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        @endif
    </div>
</div>
@endsection
