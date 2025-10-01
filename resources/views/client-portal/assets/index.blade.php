@extends('client-portal.layouts.app')

@section('title', 'IT Assets')

@section('content')
<!-- Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">IT Assets</h1>
            <p class="text-gray-600 dark:text-gray-400">View and monitor your IT assets and equipment</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">
                    Total Assets
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['total_assets'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-server fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-green-600 dark:text-green-400 uppercase mb-1">
                    Active Assets
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['active_assets'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-yellow-600 dark:text-yellow-400 uppercase mb-1">
                    Under Warranty
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['under_warranty'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-shield-alt fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-red-600 dark:text-red-400 uppercase mb-1">
                    Needs Attention
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['needs_maintenance'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>
</div>

<!-- Assets List -->
<flux:card>
    <div class="mb-4">
        <flux:heading size="lg">Your IT Assets</flux:heading>
    </div>
    
    @if($assets->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Asset Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Serial Number
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Warranty
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($assets as $asset)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $asset->name }}
                                        </div>
                                        @if($asset->manufacturer || $asset->model)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $asset->manufacturer }} {{ $asset->model }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $asset->type ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-mono text-gray-600 dark:text-gray-400">
                                    {{ $asset->serial_number ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusVariant = match($asset->status) {
                                        'active', 'deployed' => 'success',
                                        'in_stock' => 'secondary',
                                        'maintenance', 'repair' => 'warning',
                                        'retired', 'disposed' => 'danger',
                                        default => 'secondary'
                                    };
                                @endphp
                                <flux:badge variant="{{ $statusVariant }}">
                                    {{ ucfirst($asset->status ?? 'unknown') }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($asset->warranty_expiration)
                                    @php
                                        $isExpired = \Carbon\Carbon::parse($asset->warranty_expiration)->isPast();
                                    @endphp
                                    <div class="text-sm {{ $isExpired ? 'text-red-600' : 'text-green-600' }}">
                                        {{ \Carbon\Carbon::parse($asset->warranty_expiration)->format('M j, Y') }}
                                        @if($isExpired)
                                            <span class="text-xs">(Expired)</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if(Route::has('client.assets.show'))
                                    <flux:button href="{{ route('client.assets.show', $asset->id) }}" variant="ghost" size="sm" icon="eye">
                                        View
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($assets->hasPages())
            <div class="mt-6">
                {{ $assets->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="fas fa-server fa-4x text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">No Assets Found</h3>
            <p class="text-gray-500 dark:text-gray-400">You don't have any IT assets registered at the moment.</p>
        </div>
    @endif
</flux:card>
@endsection
