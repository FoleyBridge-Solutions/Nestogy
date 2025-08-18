@extends('layouts.app')

@section('title', 'Services')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <x-page-header 
        title="Services"
        subtitle="Manage your service offerings and billing models"
    >
        <x-slot name="actions">
            @can('create', App\Models\Product::class)
                <a href="{{ route('services.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i> Create Service
                </a>
            @endcan
        </x-slot>
    </x-page-header>

    <!-- Filters -->
    <x-content-card>
        <div class="p-6">
            <form method="GET" action="{{ route('services.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div class="md:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="search" name="search" 
                        value="{{ request('search') }}" placeholder="Name, SKU, or description">
                </div>
                <div class="md:col-span-1">
                    <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label for="billing_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Billing Model</label>
                    <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="billing_model" name="billing_model">
                        <option value="">All Models</option>
                        <option value="one_time" {{ request('billing_model') === 'one_time' ? 'selected' : '' }}>One Time</option>
                        <option value="subscription" {{ request('billing_model') === 'subscription' ? 'selected' : '' }}>Subscription</option>
                        <option value="usage_based" {{ request('billing_model') === 'usage_based' ? 'selected' : '' }}>Usage Based</option>
                        <option value="hybrid" {{ request('billing_model') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="is_active" name="is_active">
                        <option value="">All Status</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-1 flex items-end gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    @if(request()->hasAny(['search', 'category_id', 'billing_model', 'unit_type', 'is_active']))
                        <a href="{{ route('services.index') }}" class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </x-content-card>

    <!-- Services Table -->
    @if($services->count() > 0)
        <x-content-card :no-padding="true">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Billing Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                        @foreach($services as $service)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $service->name }}</div>
                                        @if($service->sku)
                                            <div class="text-sm text-gray-500">SKU: {{ $service->sku }}</div>
                                        @endif
                                        @if($service->description)
                                            <div class="text-sm text-gray-500">{{ Str::limit($service->description, 60) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($service->category)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">{{ $service->category->name }}</span>
                                    @else
                                        <span class="text-sm text-gray-500">No category</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($service->price, 2) }}</div>
                                    <div class="text-sm text-gray-500">per {{ $service->unit_type }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($service->billing_model === 'subscription')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Subscription</span>
                                        @if($service->billing_cycle)
                                            <div class="text-sm text-gray-500 mt-1">{{ $service->getBillingCycleDescription() }}</div>
                                        @endif
                                    @elseif($service->billing_model === 'usage_based')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Usage-based</span>
                                    @elseif($service->billing_model === 'hybrid')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Hybrid</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">One-time</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                        {{ ucfirst($service->unit_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($service->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        @can('view', $service)
                                            <a href="{{ route('services.show', $service) }}" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endcan
                                        @can('update', $service)
                                            <a href="{{ route('services.edit', $service) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white dark:text-white">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $service)
                                            <button type="button" class="text-red-600 hover:text-red-900" 
                                                    onclick="confirmDelete('{{ $service->name }}', '{{ route('services.destroy', $service) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($services->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-center">
                        {{ $services->links() }}
                    </div>
                </div>
            @endif
        </x-content-card>
    @else
        <x-content-card>
            <div class="px-6 py-12 text-center">
                <div class="text-gray-500">
                    <i class="fas fa-concierge-bell text-5xl mb-4 text-gray-400"></i>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No services found</h3>
                    <p class="text-gray-500 mb-4">
                        @if(request()->hasAny(['search', 'category_id', 'billing_model', 'unit_type', 'is_active']))
                            Try adjusting your search criteria or <a href="{{ route('services.index') }}" class="text-blue-600 hover:text-blue-500">view all services</a>.
                        @else
                            Create your first service offering to get started.
                        @endif
                    </p>
                    @can('create', App\Models\Product::class)
                        <a href="{{ route('services.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Create Service
                        </a>
                    @endcan
                </div>
            </div>
        </x-content-card>
    @endif
</div>

@push('scripts')
<script>
function confirmDelete(name, url) {
    if (confirm(`Are you sure you want to delete the service "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
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
@endsection