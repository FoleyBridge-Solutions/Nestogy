@extends('layouts.app')

@section('title', 'Role Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $role->title }}</h1>
                    <div class="mt-1 flex items-center space-x-2">
                        @php
                            $roleColors = [
                                'super-admin' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                'admin' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                'tech' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                'accountant' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            ];
                            $colorClass = $roleColors[$role->name] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                            {{ $role->name }}
                        </span>
                        <span class="text-sm text-gray-500">•</span>
                        <span class="text-sm text-gray-500">{{ $role->abilities->count() }} permissions</span>
                    </div>
                </div>
                <div class="flex space-x-3">
                    @if(!in_array($role->name, ['super-admin', 'admin']))
                        <a href="{{ route('settings.roles.edit', $role->name) }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit Role
                        </a>
                    @endif
                    <a href="{{ route('settings.roles.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Roles
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Role Information -->
        <div class="md:col-span-12-span-2">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Role Information</h3>
                </div>
                <div class="px-4 py-5 sm:px-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Role Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $role->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Display Title</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->title }}</dd>
                        </div>
                        @if($role->description)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->description }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">System Role</dt>
                            <dd class="mt-1">
                                @if(in_array($role->name, ['super-admin', 'admin', 'tech', 'accountant']))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        Yes - Protected from deletion
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                        No - Custom role
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="space-y-6">
            <!-- Permission Count -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $role->abilities->count() }}</div>
                            <div class="text-sm text-gray-500">Permissions</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Count -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            @php
                                $userCount = 0;
                                foreach($roleStats as $stat) {
                                    if($stat['bouncer_role'] === $role->name) {
                                        $userCount = $stat['count'];
                                        break;
                                    }
                                }
                            @endphp
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $userCount }}</div>
                            <div class="text-sm text-gray-500">{{ Str::plural('User', $userCount) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                </div>
                <div class="px-4 py-5 sm:px-6 space-y-3">
                    @if(!in_array($role->name, ['super-admin', 'admin']))
                        <button onclick="duplicateRole('{{ $role->name }}', '{{ $role->title }}')"
                                class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Duplicate Role
                        </button>
                    @endif
                    <a href="{{ route('users.index') }}?role={{ $role->name }}"
                       class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        View Users
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions Breakdown -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Permissions Breakdown</h3>
            <p class="mt-1 text-sm text-gray-500">All permissions assigned to this role, organized by category.</p>
        </div>
        <div class="px-4 py-5 sm:px-6">
            @if($role->abilities->count() > 0)
                @php
                    $abilitiesByCategory = $role->abilities->groupBy(function($ability) {
                        $parts = explode('.', $ability->name);
                        return ucfirst($parts[0]);
                    });
                @endphp
                
                <div class="space-y-6">
                    @foreach($abilitiesByCategory as $category => $abilities)
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg">
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $category }}</h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ $abilities->count() }} {{ Str::plural('permission', $abilities->count()) }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($abilities as $ability)
                                        <div class="flex items-center p-2 bg-gray-50 dark:bg-gray-700 rounded-md">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <div>
                                                <span class="text-sm text-gray-900 dark:text-white">
                                                    {{ $ability->title ?? ucwords(str_replace(['_', '-', '.'], [' ', ' ', ' - '], $ability->name)) }}
                                                </span>
                                                <p class="text-xs text-gray-500">{{ $ability->name }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No permissions assigned</h3>
                    <p class="mt-1 text-sm text-gray-500">This role currently has no permissions assigned to it.</p>
                    @if(!in_array($role->name, ['super-admin', 'admin']))
                        <div class="mt-6">
                            <a href="{{ route('settings.roles.edit', $role->name) }}" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Add Permissions
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function duplicateRole(roleName, roleTitle) {
    const newName = prompt(`Enter a name for the duplicated role (based on "${roleTitle}"):`, `${roleName}-copy`);
    const newTitle = prompt(`Enter a display title for the duplicated role:`, `${roleTitle} (Copy)`);
    
    if (newName && newTitle) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/settings/roles/${roleName}/duplicate`;
        
        form.innerHTML = `
            @csrf
            <input type="hidden" name="name" value="${newName}">
            <input type="hidden" name="title" value="${newTitle}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
