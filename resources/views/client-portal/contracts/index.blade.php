@extends('client-portal.layouts.app')

@section('title', 'Contracts')

@section('content')
<!-- Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Contracts</h1>
            <p class="text-gray-600 dark:text-gray-400">View and manage your service contracts and agreements</p>
        </div>
    </div>
</div>

<!-- Filters -->
<flux:card class="mb-6">
    <form method="GET" action="{{ route('client.contracts') }}" class="flex gap-4">
        <flux:field>
            <flux:label>Status</flux:label>
            <flux:select name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
            </flux:select>
        </flux:field>
        
        <flux:field>
            <flux:label>Type</flux:label>
            <flux:select name="type" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service</option>
                <option value="maintenance" {{ request('type') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                <option value="support" {{ request('type') === 'support' ? 'selected' : '' }}>Support</option>
                <option value="project" {{ request('type') === 'project' ? 'selected' : '' }}>Project</option>
                <option value="consulting" {{ request('type') === 'consulting' ? 'selected' : '' }}>Consulting</option>
            </flux:select>
        </flux:field>
    </form>
</flux:card>

<!-- Contracts List -->
<flux:card>
    <div class="mb-4">
        <flux:heading size="lg">Your Contracts</flux:heading>
    </div>
    
    @if($contracts->count() > 0)
        <div class="space-y-4">
            @foreach($contracts as $contract)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $contract->name ?? 'Contract #' . $contract->contract_number }}
                                </h3>
                                @php
                                    $statusVariant = match($contract->status) {
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'expired', 'terminated' => 'danger',
                                        'draft' => 'secondary',
                                        default => 'secondary'
                                    };
                                @endphp
                                <flux:badge variant="{{ $statusVariant }}">
                                    {{ ucfirst($contract->status ?? 'pending') }}
                                </flux:badge>
                                
                                @if($contract->contract_type)
                                    <flux:badge variant="secondary">
                                        {{ ucfirst($contract->contract_type) }}
                                    </flux:badge>
                                @endif
                            </div>
                            
                            @if($contract->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    {{ Str::limit($contract->description, 150) }}
                                </p>
                            @endif
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400">
                                @if($contract->start_date)
                                    <div>
                                        <div class="font-medium text-gray-700 dark:text-gray-300">Start Date</div>
                                        <div>{{ $contract->start_date->format('M j, Y') }}</div>
                                    </div>
                                @endif
                                
                                @if($contract->end_date)
                                    <div>
                                        <div class="font-medium text-gray-700 dark:text-gray-300">End Date</div>
                                        <div>{{ $contract->end_date->format('M j, Y') }}</div>
                                    </div>
                                @endif
                                
                                @if(isset($contract->total_value))
                                    <div>
                                        <div class="font-medium text-gray-700 dark:text-gray-300">Contract Value</div>
                                        <div>${{ number_format($contract->total_value, 2) }}</div>
                                    </div>
                                @endif
                                
                                @if($contract->renewal_date)
                                    <div>
                                        <div class="font-medium text-gray-700 dark:text-gray-300">Renewal Date</div>
                                        <div>{{ $contract->renewal_date->format('M j, Y') }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex gap-2 ml-4">
                            @if(Route::has('client.contracts.show'))
                                <flux:button href="{{ route('client.contracts.show', $contract->id) }}" variant="ghost" size="sm" icon="eye">
                                    View
                                </flux:button>
                            @endif
                            
                            @if($contract->status === 'pending' && $contract->signatures && Route::has('client.contracts.sign'))
                                <flux:button href="{{ route('client.contracts.sign', $contract->id) }}" variant="primary" size="sm" icon="pencil">
                                    Sign
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        @if($contracts->hasPages())
            <div class="mt-6">
                {{ $contracts->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="fas fa-file-contract fa-4x text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">No Contracts Found</h3>
            <p class="text-gray-500 dark:text-gray-400">
                @if(request('status') || request('type'))
                    No contracts match your current filters.
                @else
                    You don't have any contracts at the moment.
                @endif
            </p>
        </div>
    @endif
</flux:card>
@endsection
