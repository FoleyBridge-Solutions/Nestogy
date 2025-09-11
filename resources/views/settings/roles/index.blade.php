@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Roles & Permissions</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage user roles and their permissions. Create custom roles for your MSP workflows.
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button type="button" 
                            onclick="openTemplateModal()"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-5L9 9H7v8z"></path>
                        </svg>
                        Use Template
                    </button>
                    <a href="{{ route('settings.roles.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Role
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @foreach($roleStats as $roleId => $stats)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium">{{ $stats['count'] }}</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    {{ explode(' - ', $stats['name'])[0] }}
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $stats['count'] }} {{ Str::plural('user', $stats['count']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Roles List -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">System Roles</h3>
            <p class="mt-1 text-sm text-gray-500">Manage permissions for each role in your organization.</p>
        </div>
        
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($roles as $role)
                <li>
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center min-w-0 flex-1">
                                <div class="flex-shrink-0">
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
                                        {{ $role->title }}
                                    </span>
                                </div>
                                <div class="ml-4 min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $role->title }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $role->description ?? 'No description provided' }} • 
                                        <span class="font-medium">{{ $role->abilities->count() }}</span> permissions
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @php
                                    $userCount = 0;
                                    foreach ($roleStats as $stat) {
                                        if ($stat['bouncer_role'] === $role->name) {
                                            $userCount = $stat['count'];
                                            break;
                                        }
                                    }
                                @endphp
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $userCount }} {{ Str::plural('user', $userCount) }}
                                </span>
                                
                                <!-- Actions dropdown -->
                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                    <button @click="open = !open" 
                                            class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>
                                    
                                    <div x-show="open" 
                                         @click.away="open = false"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                                        <div class="py-1">
                                            <a href="{{ route('settings.roles.show', $role->name) }}" 
                                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                View Details
                                            </a>
                                            @if(!in_array($role->name, ['super-admin', 'admin']))
                                                <a href="{{ route('settings.roles.edit', $role->name) }}" 
                                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Edit Permissions
                                                </a>
                                                <button onclick="duplicateRole('{{ $role->name }}', '{{ $role->title }}')"
                                                        class="w-full text-left block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Duplicate Role
                                                </button>
                                            @endif
                                            @if(!in_array($role->name, ['super-admin', 'admin', 'tech', 'accountant']))
                                                <button onclick="deleteRole('{{ $role->name }}', '{{ $role->title }}')"
                                                        class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Delete Role
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<!-- MSP Role Templates Modal -->
<div id="templateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">MSP Role Templates</h3>
                <button onclick="closeTemplateModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $templates = [
                        'help-desk' => ['title' => 'Help Desk Technician', 'icon' => '📞', 'description' => 'Front-line support role for handling customer inquiries and basic technical issues'],
                        'field-tech' => ['title' => 'Field Technician', 'icon' => '🔧', 'description' => 'On-site technical support with asset and network management capabilities'],
                        'network-admin' => ['title' => 'Network Administrator', 'icon' => '🌐', 'description' => 'Advanced technical role with network, security, and infrastructure management'],
                        'security-specialist' => ['title' => 'Security Specialist', 'icon' => '🔒', 'description' => 'Focused on security assessments, compliance, and risk management'],
                        'project-manager' => ['title' => 'Project Manager', 'icon' => '📊', 'description' => 'Manages client projects, timelines, and resource allocation'],
                        'client-manager' => ['title' => 'Client Relationship Manager', 'icon' => '🤝', 'description' => 'Manages client relationships, contracts, and business development'],
                        'billing-admin' => ['title' => 'Billing Administrator', 'icon' => '💰', 'description' => 'Manages invoicing, payments, and financial reporting'],
                    ];
                @endphp
                
                @foreach($templates as $key => $template)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                         onclick="selectTemplate('{{ $key }}', '{{ $template['title'] }}')">
                        <div class="flex items-start">
                            <div class="text-2xl mr-3">{{ $template['icon'] }}</div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $template['title'] }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $template['description'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Template Form Modal -->
<div id="templateFormModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <form method="POST" action="{{ route('settings.roles.apply-template') }}">
            @csrf
            <input type="hidden" name="template" id="selectedTemplate">
            
            <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Create Role from Template</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400" id="templateDescription"></p>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label for="templateName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role Name</label>
                    <input type="text" name="name" id="templateName" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="templateTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Display Title</label>
                    <input type="text" name="title" id="templateTitle" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeTemplateFormModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                    Create Role
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openTemplateModal() {
    document.getElementById('templateModal').classList.remove('hidden');
}

function closeTemplateModal() {
    document.getElementById('templateModal').classList.add('hidden');
}

function selectTemplate(templateKey, templateTitle) {
    closeTemplateModal();
    
    document.getElementById('selectedTemplate').value = templateKey;
    document.getElementById('templateTitle').value = templateTitle;
    document.getElementById('templateName').value = templateKey.replace('-', '_');
    
    // Set description based on template
    const descriptions = {
        'help-desk': 'Front-line support role for handling customer inquiries and basic technical issues',
        'field-tech': 'On-site technical support with asset and network management capabilities',
        'network-admin': 'Advanced technical role with network, security, and infrastructure management',
        'security-specialist': 'Focused on security assessments, compliance, and risk management',
        'project-manager': 'Manages client projects, timelines, and resource allocation',
        'client-manager': 'Manages client relationships, contracts, and business development',
        'billing-admin': 'Manages invoicing, payments, and financial reporting',
    };
    
    document.getElementById('templateDescription').textContent = descriptions[templateKey] || '';
    document.getElementById('templateFormModal').classList.remove('hidden');
}

function closeTemplateFormModal() {
    document.getElementById('templateFormModal').classList.add('hidden');
}

function duplicateRole(roleName, roleTitle) {
    const newName = prompt(`Enter a name for the duplicated role (based on "${roleTitle}"):`);
    const newTitle = prompt(`Enter a display title for the duplicated role:`);
    
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

function deleteRole(roleName, roleTitle) {
    if (confirm(`Are you sure you want to delete the role "${roleTitle}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/settings/roles/${roleName}`;
        
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
