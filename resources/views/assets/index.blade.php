@extends('layouts.app')

@section('title', 'Assets')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="w-full">
            <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-white">Assets</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('assets.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-plus mr-2"></i> New Asset
                                </a>
                                <div class="relative" x-data="{ open: false }">
                                    <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="open = !open">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" 
                                         class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white dark:bg-gray-800 dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95">
                                        <a href="{{ route('assets.export', request()->query()) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800">
                                            <i class="fas fa-download mr-2"></i> Export to Excel
                                        </a>
                                        <a href="{{ route('assets.import.form') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800">
                                            <i class="fas fa-upload mr-2"></i> Import from Excel
                                        </a>
                                        <a href="{{ route('assets.template.download') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800">
                                            <i class="fas fa-file-download mr-2"></i> Download Template
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('assets.index') }}" class="mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                            <div class="md:col-span-2">
                                <input type="text" name="search" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search assets..." 
                                       value="{{ request('search') }}">
                            </div>
                            <div>
                                <select name="client_id" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Clients</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select name="type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Types</option>
                                    @foreach(App\Models\Asset::TYPES as $type)
                                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select name="status" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Statuses</option>
                                    @foreach(App\Models\Asset::STATUSES as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select name="location_id" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Filter</button>
                        </div>
                    </form>

                    <!-- Bulk Actions -->
                    <form id="bulkActionForm" method="POST" action="{{ route('assets.bulk.update') }}">
                        @csrf
                        <div class="flex items-center justify-between py-3 px-4 bg-gray-50 dark:bg-gray-900 dark:bg-gray-900 rounded-md mb-4" id="bulkActions" style="display: none;">
                            <div class="flex items-center space-x-3">
                                <select name="action" class="block px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="bulkActionSelect">
                                    <option value="">Select Action</option>
                                    <option value="update_location">Update Location</option>
                                    <option value="update_contact">Update Contact</option>
                                    <option value="update_status">Update Status</option>
                                    <option value="archive">Archive Selected</option>
                                </select>
                                <div id="bulkActionParams"></div>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Apply</button>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                <span id="selectedCount">0</span> assets selected
                            </div>
                        </div>

                        <!-- Assets Table -->
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50 dark:bg-gray-900 dark:bg-gray-900">
                                    <tr>
                                        <th scope="col" class="relative px-6 sm:w-12 sm:px-6">
                                            <input type="checkbox" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" id="selectAll">
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <a href="{{ route('assets.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" class="group inline-flex">
                                                Name
                                                @if(request('sort') == 'name')
                                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ml-2 flex-none rounded text-gray-400 group-hover:text-gray-500"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:bg-gray-800 dark:bg-gray-800">
                                    @forelse($assets as $asset)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 dark:hover:bg-gray-700 dark:bg-gray-900">
                                            <td class="relative px-6 sm:w-12 sm:px-6">
                                                <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}" 
                                                       class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 asset-checkbox">
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                <a href="{{ route('assets.show', $asset) }}" class="flex items-center text-gray-900 dark:text-white dark:text-white hover:text-indigo-600">
                                                    <i class="fas fa-{{ $asset->icon }} text-gray-400 mr-2"></i>
                                                    <div>
                                                        <div class="font-medium">{{ $asset->name }}</div>
                                                        @if($asset->description)
                                                            <div class="text-gray-500">{{ Str::limit($asset->description, 50) }}</div>
                                                        @endif
                                                    </div>
                                                </a>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white dark:text-white">{{ $asset->type }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                @if($asset->client)
                                                    <a href="{{ route('clients.show', $asset->client) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        {{ $asset->client->name }}
                                                    </a>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white dark:text-white">
                                                @if($asset->location)
                                                    {{ $asset->location->name }}
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white dark:text-white">
                                                @if($asset->contact)
                                                    {{ $asset->contact->name }}
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    @if($asset->status_color == 'success') bg-green-100 text-green-800
                                                    @elseif($asset->status_color == 'primary') bg-blue-100 text-blue-800
                                                    @elseif($asset->status_color == 'warning') bg-yellow-100 text-yellow-800
                                                    @elseif($asset->status_color == 'danger') bg-red-100 text-red-800
                                                    @else bg-gray-100 dark:bg-gray-800 dark:bg-gray-800 text-gray-800 dark:text-gray-200 dark:text-gray-200
                                                    @endif">
                                                    {{ $asset->status }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-900 dark:text-white dark:text-white">
                                                @if($asset->serial)
                                                    {{ $asset->serial }}
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-900 dark:text-white dark:text-white">
                                                @if($asset->ip)
                                                    {{ $asset->ip }}
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <a href="{{ route('assets.edit', $asset) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        <i class="fas fa-edit"></i>
                                                        <span class="sr-only">Edit {{ $asset->name }}</span>
                                                    </a>
                                                    <div class="relative" x-data="{ open: false }">
                                                        <button @click="open = !open" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                            <span class="sr-only">Open options</span>
                                                        </button>
                                                        <div x-show="open" @click.outside="open = false" 
                                                             class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 dark:bg-gray-800 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                                             x-transition:enter="transition ease-out duration-100"
                                                             x-transition:enter-start="transform opacity-0 scale-95"
                                                             x-transition:enter-end="transform opacity-100 scale-100"
                                                             x-transition:leave="transition ease-in duration-75"
                                                             x-transition:leave-start="transform opacity-100 scale-100"
                                                             x-transition:leave-end="transform opacity-0 scale-95">
                                                            <a href="{{ route('assets.show', $asset) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800">
                                                                <i class="fas fa-eye mr-2"></i> View
                                                            </a>
                                                            <a href="{{ route('assets.qr-code', $asset) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800">
                                                                <i class="fas fa-qrcode mr-2"></i> QR Code
                                                            </a>
                                                            <a href="{{ route('assets.label', $asset) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800">
                                                                <i class="fas fa-tag mr-2"></i> Print Label
                                                            </a>
                                                            <div class="border-t border-gray-100"></div>
                                                            <form action="{{ route('assets.archive', $asset) }}" method="POST" class="block">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-yellow-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800">
                                                                    <i class="fas fa-archive mr-2"></i> Archive
                                                                </button>
                                                            </form>
                                                            @can('delete', $asset)
                                                            <form action="{{ route('assets.destroy', $asset) }}" method="POST" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this asset?');" class="block">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800">
                                                                    <i class="fas fa-trash mr-2"></i> Delete
                                                                </button>
                                                            </form>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-12">
                                                <div class="text-gray-500">
                                                    <i class="fas fa-inbox text-4xl mb-4"></i>
                                                    <p class="text-lg font-medium mb-2">No assets found</p>
                                                    <p class="text-sm mb-4">Get started by creating your first asset.</p>
                                                    <a href="{{ route('assets.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                        <i class="fas fa-plus mr-2"></i> Create First Asset
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <!-- Pagination -->
                    <div class="flex justify-between items-center mt-4">
                        <div>
                            Showing {{ $assets->firstItem() ?? 0 }} to {{ $assets->lastItem() ?? 0 }} of {{ $assets->total() }} assets
                        </div>
                        {{ $assets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.asset-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    const bulkActionParams = document.getElementById('bulkActionParams');

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const checkedCount = document.querySelectorAll('.asset-checkbox:checked').length;
        selectedCount.textContent = checkedCount;
        bulkActions.style.display = checkedCount > 0 ? 'block' : 'none';
    }

    // Dynamic bulk action parameters
    bulkActionSelect.addEventListener('change', function() {
        bulkActionParams.innerHTML = '';
        
        switch(this.value) {
            case 'update_location':
                bulkActionParams.innerHTML = `
                    <select name="location_id" class="block px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                        <option value="">Select Location</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                `;
                break;
            case 'update_contact':
                bulkActionParams.innerHTML = `
                    <select name="contact_id" class="block px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                        <option value="">Select Contact</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                        @endforeach
                    </select>
                `;
                break;
            case 'update_status':
                bulkActionParams.innerHTML = `
                    <select name="status" class="block px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                        <option value="">Select Status</option>
                        @foreach(App\Models\Asset::STATUSES as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                `;
                break;
        }
    });
});
</script>
@endpush