@props([
    'company' => null,
    'grantedPermissions' => [],
    'receivedPermissions' => [],
    'availableCompanies' => [],
    'editable' => true
])

<div {{ $attributes->merge(['class' => 'permissions-matrix']) }}>
    <!-- Header -->
    <div class="mb-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Permission Management for {{ $company->name }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            Manage permissions granted to and received from other companies in your organization.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Granted Permissions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-medium text-gray-900">Permissions Granted</h4>
                        <p class="mt-1 text-sm text-gray-500">Permissions this company has granted to others</p>
                    </div>
                    @if($editable)
                        <button type="button" 
                                onclick="openGrantPermissionModal()" 
                                class="inline-flex items-center px-6 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Grant Permission
                        </button>
                    @endif
                </div>
            </div>
            
            <div class="px-6 py-8 sm:p-6">
                @if(count($grantedPermissions) > 0)
                    <div class="space-y-4">
                        @foreach($grantedPermissions as $permission)
                            <div class="flex items-center justify-between p-6 border border-gray-200 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-3">
                                            {{ $permission->granteeCompany->name }}
                                        </span>
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-900">{{ ucfirst($permission->permission_type) }}</span>
                                            <span class="text-gray-500">access to</span>
                                            <span class="font-medium text-gray-900">{{ $permission->resource_type === '*' ? 'All Resources' : ucfirst($permission->resource_type) }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        Scope: {{ ucfirst($permission->scope) }}
                                        @if($permission->expires_at)
                                            • Expires: {{ $permission->expires_at->format('M j, Y') }}
                                        @endif
                                        @if($permission->can_delegate)
                                            • <span class="text-green-600">Can Delegate</span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($editable)
                                    <div class="flex items-center space-x-2">
                                        <button onclick="editPermission({{ $permission->id }})" 
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Edit
                                        </button>
                                        <button onclick="revokePermission({{ $permission->id }})" 
                                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            Revoke
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No permissions granted</h3>
                        <p class="mt-1 text-sm text-gray-500">This company has not granted any permissions to other companies.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Received Permissions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                <div>
                    <h4 class="text-lg font-medium text-gray-900">Permissions Received</h4>
                    <p class="mt-1 text-sm text-gray-500">Permissions this company has received from others</p>
                </div>
            </div>
            
            <div class="px-6 py-8 sm:p-6">
                @if(count($receivedPermissions) > 0)
                    <div class="space-y-4">
                        @foreach($receivedPermissions as $permission)
                            <div class="flex items-center justify-between p-6 border border-gray-200 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-3">
                                            {{ $permission->granterCompany->name }}
                                        </span>
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-900">{{ ucfirst($permission->permission_type) }}</span>
                                            <span class="text-gray-500">access to</span>
                                            <span class="font-medium text-gray-900">{{ $permission->resource_type === '*' ? 'All Resources' : ucfirst($permission->resource_type) }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 flex items-center space-x-3">
                                        <span>Scope: {{ ucfirst($permission->scope) }}</span>
                                        @if($permission->expires_at)
                                            <span>Expires: {{ $permission->expires_at->format('M j, Y') }}</span>
                                        @endif
                                        @if($permission->is_inherited)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Inherited
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $permission->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $permission->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No permissions received</h3>
                        <p class="mt-1 text-sm text-gray-500">This company has not received any permissions from other companies.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($editable)
        <!-- Grant Permission Modal -->
        <div id="grantPermissionModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-6 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form id="grantPermissionForm" method="POST" action="{{ route('subsidiaries.grant-permission') }}">
                        @csrf
                        <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-6 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                                        Grant Permission
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label for="grantee_company_id" class="block text-sm font-medium text-gray-700">
                                                Grant Permission To
                                            </label>
                                            <select id="grantee_company_id" name="grantee_company_id" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                @foreach($availableCompanies as $availableCompany)
                                                    <option value="{{ $availableCompany->id }}">{{ $availableCompany->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label for="resource_type" class="block text-sm font-medium text-gray-700">
                                                Resource Type
                                            </label>
                                            <select id="resource_type" name="resource_type" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="*">All Resources</option>
                                                <option value="users">Users</option>
                                                <option value="clients">Clients</option>
                                                <option value="tickets">Tickets</option>
                                                <option value="assets">Assets</option>
                                                <option value="invoices">Invoices</option>
                                                <option value="contracts">Contracts</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="permission_type" class="block text-sm font-medium text-gray-700">
                                                Permission Level
                                            </label>
                                            <select id="permission_type" name="permission_type" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="view">View Only</option>
                                                <option value="create">Create</option>
                                                <option value="edit">Edit</option>
                                                <option value="delete">Delete</option>
                                                <option value="manage">Full Management</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="scope" class="block text-sm font-medium text-gray-700">
                                                Scope
                                            </label>
                                            <select id="scope" name="scope" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="all">All Records</option>
                                                <option value="specific">Specific Records</option>
                                                <option value="filtered">Filtered Access</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="expires_at" class="block text-sm font-medium text-gray-700">
                                                Expiration Date (Optional)
                                            </label>
                                            <input type="date" id="expires_at" name="expires_at" 
                                                   min="{{ date('Y-m-d') }}"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>

                                        <div class="flex items-center">
                                            <input type="checkbox" id="can_delegate" name="can_delegate" value="1"
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="can_delegate" class="ml-2 block text-sm text-gray-900">
                                                Allow delegation to subsidiaries
                                            </label>
                                        </div>

                                        <div>
                                            <label for="notes" class="block text-sm font-medium text-gray-700">
                                                Notes (Optional)
                                            </label>
                                            <textarea id="notes" name="notes" rows="3" 
                                                      placeholder="Add any notes about this permission..."
                                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-6 py-6 sm:px-6 sm:flex sm:flex-flex flex-wrap -mx-4-reverse">
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Grant Permission
                            </button>
                            <button type="button" onclick="closeGrantPermissionModal()" 
                                    class="mt-6 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

@if($editable)
    <script>
        function openGrantPermissionModal() {
            document.getElementById('grantPermissionModal').classList.remove('hidden');
        }

        function closeGrantPermissionModal() {
            document.getElementById('grantPermissionModal').classList.add('hidden');
            document.getElementById('grantPermissionForm').reset();
        }

        function editPermission(permissionId) {
            // Implementation for edit permission modal
            console.log('Edit permission:', permissionId);
        }

        function revokePermission(permissionId) {
            if (confirm('Are you sure you want to revoke this permission?')) {
                fetch(`/subsidiaries/permissions/${permissionId}/revoke`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to revoke permission: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while revoking the permission.');
                });
            }
        }

        // Handle form submission
        document.getElementById('grantPermissionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeGrantPermissionModal();
                    location.reload();
                } else {
                    alert('Failed to grant permission: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while granting the permission.');
            });
        });
    </script>
@endif
