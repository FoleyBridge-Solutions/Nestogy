@extends('layouts.app')

@section('title', 'Subsidiary Management')

@php
$pageTitle = 'Subsidiaries';
$pageSubtitle = 'Manage company subsidiaries and organizational units';
$pageActions = [
    [
        'label' => 'Add Subsidiary',
        'href' => route('subsidiaries.create'),
        'icon' => 'plus',
        'variant' => 'primary',
    ],
];
@endphp

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <flux:card class="mb-6">
        <div class="flex items-start justify-between">
            <div>
                <flux:heading size="xl">Subsidiary Management</flux:heading>
                <flux:text class="mt-2">Manage your company hierarchy and subsidiary relationships</flux:text>
            </div>
            
            @if($company->canCreateSubsidiaries() && !$company->hasReachedMaxSubsidiaryDepth())
                <flux:button href="{{ route('subsidiaries.create') }}" variant="primary" icon="plus">
                    Create Subsidiary
                </flux:button>
            @endif
        </div>
    </flux:card>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="xs" variant="muted">Total Subsidiaries</flux:text>
                    <flux:heading size="lg">{{ $stats['total_subsidiaries'] }}</flux:heading>
                </div>
                <flux:icon name="building-office" class="w-8 h-8 text-blue-500" />
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="xs" variant="muted">Direct Subsidiaries</flux:text>
                    <flux:heading size="lg">{{ $stats['direct_subsidiaries'] }}</flux:heading>
                </div>
                <flux:icon name="building-office-2" class="w-8 h-8 text-green-500" />
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="xs" variant="muted">Current Depth</flux:text>
                    <flux:heading size="lg">{{ $stats['current_depth'] }}</flux:heading>
                </div>
                <flux:icon name="queue-list" class="w-8 h-8 text-cyan-500" />
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text size="xs" variant="muted">Max Depth</flux:text>
                    <flux:heading size="lg">{{ $stats['max_depth'] }}</flux:heading>
                </div>
                <flux:icon name="chart-bar" class="w-8 h-8 text-yellow-500" />
            </div>
        </flux:card>
    </div>

    <!-- Hierarchy Tree -->
    <flux:card class="mb-6">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Company Hierarchy</flux:heading>
                <flux:button.group>
                    <flux:button variant="outline" size="sm" icon="arrows-pointing-out" id="expandAll">
                        Expand All
                    </flux:button>
                    <flux:button variant="outline" size="sm" icon="arrows-pointing-in" id="collapseAll">
                        Collapse All
                    </flux:button>
                </flux:button.group>
            </div>
        </div>
        
        @if($stats['total_subsidiaries'] > 0)
            <div class="text-center py-8">
                <flux:icon name="building-office-2" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <flux:heading size="md">Subsidiary Hierarchy</flux:heading>
                <flux:text class="mt-2 mb-4">Hierarchy visualization will be implemented here.</flux:text>
                
                <div class="max-w-md mx-auto bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-left text-sm text-blue-800">
                        <div class="mb-1"><strong>Total Subsidiaries:</strong> {{ $stats['total_subsidiaries'] }}</div>
                        <div class="mb-1"><strong>Direct Subsidiaries:</strong> {{ $stats['direct_subsidiaries'] }}</div>
                        <div><strong>Organization Level:</strong> {{ $stats['current_depth'] }}</div>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <flux:icon name="building-office-2" class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                <flux:heading size="md">No Subsidiaries Yet</flux:heading>
                <flux:text class="mt-2 mb-4">Create your first subsidiary to build your company hierarchy</flux:text>
                
                @if($company->canCreateSubsidiaries())
                    <flux:button href="{{ route('subsidiaries.create') }}" variant="primary" icon="plus">
                        Create First Subsidiary
                    </flux:button>
                @else
                    <flux:text variant="danger">You don't have permission to create subsidiaries</flux:text>
                @endif
            </div>
        @endif
    </flux:card>

    <!-- Quick Actions -->
    <flux:card>
        <div class="mb-6">
            <flux:heading size="lg">Quick Actions</flux:heading>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <flux:button variant="outline" class="w-full justify-start" icon="document-plus">
                Import Subsidiaries
            </flux:button>
            <flux:button variant="outline" class="w-full justify-start" icon="document-arrow-down">
                Export Hierarchy
            </flux:button>
            <flux:button variant="outline" class="w-full justify-start" icon="cog-6-tooth">
                Manage Permissions
            </flux:button>
        </div>
    </flux:card>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('expandAll')?.addEventListener('click', function() {
        // Expand all tree nodes logic
    });
    
    document.getElementById('collapseAll')?.addEventListener('click', function() {
        // Collapse all tree nodes logic
    });
});
</script>
@endpush
@endsection