@extends('layouts.app')

@section('title', 'Clients')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage your client relationships and information</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('clients.import.form') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Import
                    </a>
                    <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Client
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs for Customers and Leads -->
    <div class="bg-white shadow rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <a href="{{ route('clients.index') }}" class="{{ !request('lead') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Customers
                </a>
                <a href="{{ route('clients.leads') }}" class="{{ request('lead') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Leads
                </a>
            </nav>
        </div>
    </div>

    <!-- DataTable Container -->
    <div class="bg-white shadow overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ request('lead') ? 'Leads' : 'Customers' }} List
                </h3>
                <div class="flex items-center space-x-2">
                    <!-- Export -->
                    <a href="{{ route('clients.export', ['lead' => request('lead')]) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <table id="clientsTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tags</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- DataTables will populate this -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Delete Client</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Are you sure you want to delete this client? This action cannot be undone.</p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmDelete" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600">Delete</button>
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Convert Lead Modal -->
<div id="convertModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Convert Lead to Customer</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Are you sure you want to convert this lead to a customer?</p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmConvert" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-green-600">Convert</button>
                <button onclick="closeConvertModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
<script>
let clientToDelete = null;
let leadToConvert = null;
let dataTable;

$(document).ready(function() {
    // Initialize DataTable
    dataTable = $('#clientsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("clients.data") }}',
            data: function(d) {
                d.lead = '{{ request("lead") }}';
            }
        },
        columns: [
            {
                data: 'name',
                name: 'name',
                render: function(data, type, row) {
                    let html = '<div class="flex items-center">';
                    html += '<div class="flex-shrink-0 h-10 w-10">';
                    if (row.avatar) {
                        html += '<img class="h-10 w-10 rounded-full" src="' + row.avatar + '" alt="' + data + '">';
                    } else {
                        html += '<div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">';
                        html += '<span class="text-sm font-medium text-gray-700">' + data.substring(0, 2).toUpperCase() + '</span>';
                        html += '</div>';
                    }
                    html += '</div>';
                    html += '<div class="ml-4">';
                    html += '<div class="text-sm font-medium text-gray-900">';
                    html += '<a href="/clients/' + row.id + '" class="hover:text-blue-600">' + data + '</a>';
                    html += '</div>';
                    if (row.company) {
                        html += '<div class="text-sm text-gray-500">' + row.company + '</div>';
                    }
                    html += '</div>';
                    html += '</div>';
                    return html;
                }
            },
            {data: 'email', name: 'email'},
            {data: 'phone', name: 'phone'},
            {
                data: 'type',
                name: 'type',
                render: function(data) {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' + 
                           (data ? data.charAt(0).toUpperCase() + data.slice(1) : 'Individual') + 
                           '</span>';
                }
            },
            {
                data: 'is_active',
                name: 'is_active',
                render: function(data) {
                    if (data) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>';
                    } else {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>';
                    }
                }
            },
            {
                data: 'tags',
                name: 'tags',
                orderable: false,
                render: function(data) {
                    if (!data || data.length === 0) return '';
                    let html = '';
                    data.forEach(function(tag) {
                        html += '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mr-1">' + tag.name + '</span>';
                    });
                    return html;
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let html = '<div class="flex items-center space-x-2">';
                    html += '<a href="/clients/' + row.id + '" class="text-blue-600 hover:text-blue-900">View</a>';
                    html += '<a href="/clients/' + row.id + '/edit" class="text-indigo-600 hover:text-indigo-900">Edit</a>';
                    if (row.lead) {
                        html += '<button onclick="convertLead(' + row.id + ')" class="text-green-600 hover:text-green-900">Convert</button>';
                    }
                    html += '<button onclick="deleteClient(' + row.id + ')" class="text-red-600 hover:text-red-900">Delete</button>';
                    html += '</div>';
                    return html;
                }
            }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            search: "Search clients:",
            lengthMenu: "Show _MENU_ clients per page",
            info: "Showing _START_ to _END_ of _TOTAL_ clients",
            infoEmpty: "No clients found",
            infoFiltered: "(filtered from _MAX_ total clients)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });
});

// Delete client
function deleteClient(clientId) {
    clientToDelete = clientId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    clientToDelete = null;
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    if (clientToDelete) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/clients/${clientToDelete}`;
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        const tokenField = document.createElement('input');
        tokenField.type = 'hidden';
        tokenField.name = '_token';
        tokenField.value = '{{ csrf_token() }}';
        
        form.appendChild(methodField);
        form.appendChild(tokenField);
        document.body.appendChild(form);
        form.submit();
    }
});

// Convert lead
function convertLead(leadId) {
    leadToConvert = leadId;
    document.getElementById('convertModal').classList.remove('hidden');
}

function closeConvertModal() {
    document.getElementById('convertModal').classList.add('hidden');
    leadToConvert = null;
}

document.getElementById('confirmConvert').addEventListener('click', function() {
    if (leadToConvert) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/clients/${leadToConvert}/convert`;
        
        const tokenField = document.createElement('input');
        tokenField.type = 'hidden';
        tokenField.name = '_token';
        tokenField.value = '{{ csrf_token() }}';
        
        form.appendChild(tokenField);
        document.body.appendChild(form);
        form.submit();
    }
});

// Close modals when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

document.getElementById('convertModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeConvertModal();
    }
});
</script>
@endpush