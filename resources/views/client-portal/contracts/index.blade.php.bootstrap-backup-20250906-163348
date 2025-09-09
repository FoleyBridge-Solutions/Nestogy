@extends('client-portal.layouts.app')

@section('title', 'Contracts')

@section('content')
<div class="portal-container">
    <!-- Header -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-d-flex portal-justify-content-between portal-align-items-center">
                <div>
                    <h1 class="portal-text-3xl portal-mb-0 text-gray-800">Contracts</h1>
                    <p class="text-gray-600">View and manage your service contracts and agreements</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Options -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-card portal-shadow">
                <div class="portal-card-body">
                    <form method="GET" action="{{ route('client.contracts') }}" class="portal-d-flex portal-align-items-center space-x-4">
                        <div class="portal-col-auto">
                            <label for="status" class="portal-text-sm portal-font-medium text-gray-700 portal-mr-2">
                                Status:
                            </label>
                            <select name="status" id="status" class="portal-form-control" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                            </select>
                        </div>
                        
                        <div class="portal-col-auto">
                            <label for="type" class="portal-text-sm portal-font-medium text-gray-700 portal-mr-2">
                                Type:
                            </label>
                            <select name="type" id="type" class="portal-form-control" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service</option>
                                <option value="maintenance" {{ request('type') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="support" {{ request('type') === 'support' ? 'selected' : '' }}>Support</option>
                                <option value="project" {{ request('type') === 'project' ? 'selected' : '' }}>Project</option>
                                <option value="consulting" {{ request('type') === 'consulting' ? 'selected' : '' }}>Consulting</option>
                            </select>
                        </div>
                        
                        @if(request('status') || request('type'))
                        <div class="portal-col-auto">
                            <a href="{{ route('client.contracts') }}" class="portal-btn portal-btn-outline-secondary portal-btn-sm">
                                Clear Filters
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="portal-row">
        <div class="portal-col-12">
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-file-contract portal-mr-2"></i>Your Contracts
                        @if($contracts->total() > 0)
                            <span class="portal-text-sm portal-font-normal text-gray-600">
                                ({{ $contracts->total() }} total)
                            </span>
                        @endif
                    </h6>
                </div>
                <div class="portal-card-body">
                    @if($contracts->count() > 0)
                    <div class="portal-table-responsive">
                        <table class="portal-table portal-min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Contract
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Type
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Value
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Start Date
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        End Date
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="portal-divide-y portal-divide-gray-200">
                                @foreach($contracts as $contract)
                                <tr class="portal-table-row">
                                    <td class="px-4 py-4">
                                        <div class="portal-text-sm portal-font-medium text-gray-900">
                                            {{ $contract->title }}
                                        </div>
                                        <div class="portal-text-xs text-gray-500">
                                            #{{ $contract->contract_number }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm">
                                        {{ ucwords(str_replace('_', ' ', $contract->contract_type ?? 'service')) }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full
                                            @if($contract->status === 'active') bg-green-100 text-green-800
                                            @elseif($contract->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($contract->status === 'draft') bg-blue-100 text-blue-800
                                            @elseif($contract->status === 'expired') bg-red-100 text-red-800
                                            @elseif($contract->status === 'terminated') bg-gray-100 text-gray-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($contract->status ?? 'active') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm">
                                        @if($contract->contract_value)
                                            ${{ number_format($contract->contract_value, 2) }}
                                            @if($contract->currency_code && $contract->currency_code !== 'USD')
                                                <span class="portal-text-xs text-gray-500">{{ $contract->currency_code }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm text-gray-500">
                                        @if($contract->start_date)
                                            {{ $contract->start_date->format('M j, Y') }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm text-gray-500">
                                        @if($contract->end_date)
                                            {{ $contract->end_date->format('M j, Y') }}
                                            @if($contract->end_date->isPast())
                                                <span class="portal-text-xs text-red-600">(Expired)</span>
                                            @elseif($contract->end_date->diffInDays() <= 30)
                                                <span class="portal-text-xs text-orange-600">(Expiring Soon)</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm">
                                        <div class="portal-d-flex space-x-2">
                                            <a href="{{ route('client.contracts.show', $contract) ?? '#' }}" 
                                               class="portal-btn portal-btn-sm portal-btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($contract->document_path)
                                            <a href="{{ route('client.contracts.download', $contract) ?? '#' }}" 
                                               class="portal-btn portal-btn-sm portal-btn-outline-secondary">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($contracts->hasPages())
                        <div class="px-4 py-3 portal-border-t">
                            {{ $contracts->appends(request()->query())->links() }}
                        </div>
                    @endif

                    @else
                    <div class="text-center py-8">
                        <div class="portal-mb-4">
                            <i class="fas fa-file-contract fa-3x text-gray-300"></i>
                        </div>
                        <h3 class="portal-text-lg portal-font-medium text-gray-900 portal-mb-2">
                            @if(request('status') || request('type'))
                                No Contracts Found
                            @else
                                No Contracts
                            @endif
                        </h3>
                        <p class="portal-text-sm text-gray-500 portal-mb-4">
                            @if(request('status') || request('type'))
                                No contracts match your current filters. Try adjusting the filters above.
                            @else
                                You don't have any contracts yet. Contracts will appear here once they are created.
                            @endif
                        </p>
                        @if(request('status') || request('type'))
                        <a href="{{ route('client.contracts') }}" class="portal-btn portal-btn-outline-primary">
                            Clear Filters
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    @if($contracts->count() > 0)
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-chart-bar portal-mr-2"></i>Contract Summary
                    </h6>
                </div>
                <div class="portal-card-body">
                    <div class="portal-row">
                        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
                            <div class="text-center">
                                <div class="portal-text-2xl portal-font-bold text-green-600">
                                    {{ $contracts->where('status', 'active')->count() }}
                                </div>
                                <div class="portal-text-sm text-gray-600">Active Contracts</div>
                            </div>
                        </div>
                        
                        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
                            <div class="text-center">
                                <div class="portal-text-2xl portal-font-bold text-blue-600">
                                    ${{ number_format($contracts->where('status', 'active')->sum('contract_value'), 2) }}
                                </div>
                                <div class="portal-text-sm text-gray-600">Total Active Value</div>
                            </div>
                        </div>
                        
                        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
                            <div class="text-center">
                                <div class="portal-text-2xl portal-font-bold text-orange-600">
                                    {{ $contracts->filter(function($contract) { return $contract->end_date && $contract->end_date->diffInDays() <= 30; })->count() }}
                                </div>
                                <div class="portal-text-sm text-gray-600">Expiring Soon</div>
                            </div>
                        </div>
                        
                        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
                            <div class="text-center">
                                <div class="portal-text-2xl portal-font-bold text-red-600">
                                    {{ $contracts->filter(function($contract) { return $contract->end_date && $contract->end_date->isPast(); })->count() }}
                                </div>
                                <div class="portal-text-sm text-gray-600">Expired</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.space-x-2 > * + * { margin-left: 0.5rem; }
.space-x-4 > * + * { margin-left: 1rem; }

.portal-form-control {
    width: auto;
    min-width: 120px;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    background-color: white;
}

.portal-form-control:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
}

/* Badge colors for status indicators */
.bg-green-100 { background-color: #dcfce7; } .text-green-800 { color: #166534; }
.bg-yellow-100 { background-color: #fef3c7; } .text-yellow-800 { color: #92400e; }
.bg-red-100 { background-color: #fee2e2; } .text-red-800 { color: #991b1b; }
.bg-blue-100 { background-color: #dbeafe; } .text-blue-800 { color: #1e40af; }
.bg-orange-100 { background-color: #fed7aa; } .text-orange-800 { color: #9a3412; }
.bg-gray-100 { background-color: #f3f4f6; } .text-gray-800 { color: #1f2937; }

/* Hover effects */
.portal-table-row:hover {
    background-color: #f9fafb;
}

/* Summary card styles */
.text-green-600 { color: #059669; }
.text-blue-600 { color: #2563eb; }
.text-orange-600 { color: #ea580c; }
.text-red-600 { color: #dc2626; }
</style>
@endpush