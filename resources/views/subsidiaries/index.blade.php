@extends('layouts.app')

@section('title', 'Subsidiary Management')

@section('content')
<flux:container>
    <!-- Header -->
    <flux:card class="mb-6">
        <flux:card.body>
            <flux:between>
                <div>
                    <flux:heading size="xl">Subsidiary Management</flux:heading>
                    <flux:text class="mt-2">Manage your company hierarchy and subsidiary relationships</flux:text>
                </div>
                
                @if($company->canCreateSubsidiaries() && !$company->hasReachedMaxSubsidiaryDepth())
                    <flux:button href="{{ route('subsidiaries.create') }}" variant="primary" icon="plus">
                        Create Subsidiary
                    </flux:button>
                @endif
            </flux:between>
        </flux:card.body>
    </flux:card>

    <!-- Statistics Cards -->
    <flux:grid cols="4" class="mb-6">
        <flux:card>
            <flux:card.body>
                <flux:between>
                    <div>
                        <flux:text size="xs" variant="muted">Total Subsidiaries</flux:text>
                        <flux:heading size="lg">{{ $stats['total_subsidiaries'] }}</flux:heading>
                    </div>
                    <flux:icon name="building-office" class="w-8 h-8 text-blue-500" />
                </flux:between>
            </flux:card.body>
        </flux:card>

        <flux:card>
            <flux:card.body>
                <flux:between>
                    <div>
                        <flux:text size="xs" variant="muted">Direct Subsidiaries</flux:text>
                        <flux:heading size="lg">{{ $stats['direct_subsidiaries'] }}</flux:heading>
                    </div>
                    <flux:icon name="building-office-2" class="w-8 h-8 text-green-500" />
                </flux:between>
            </flux:card.body>
        </flux:card>

        <flux:card>
            <flux:card.body>
                <flux:between>
                    <div>
                        <flux:text size="xs" variant="muted">Current Depth</flux:text>
                        <flux:heading size="lg">{{ $stats['current_depth'] }}</flux:heading>
                    </div>
                    <flux:icon name="queue-list" class="w-8 h-8 text-cyan-500" />
                </flux:between>
            </flux:card.body>
        </flux:card>

        <flux:card>
            <flux:card.body>
                <flux:between>
                    <div>
                        <flux:text size="xs" variant="muted">Max Depth</flux:text>
                        <flux:heading size="lg">{{ $stats['max_depth'] }}</flux:heading>
                    </div>
                    <flux:icon name="chart-bar" class="w-8 h-8 text-yellow-500" />
                </flux:between>
            </flux:card.body>
        </flux:card>
    </flux:grid>

    <!-- Hierarchy Tree -->
    <flux:card class="mb-6">
        <flux:card.header>
            <flux:between>
                <flux:heading size="lg">Company Hierarchy</flux:heading>
                <flux:button.group>
                    <flux:button variant="secondary" size="sm" icon="arrows-expand" id="expandAll">
                        Expand All
                    </flux:button>
                    <flux:button variant="secondary" size="sm" icon="arrows-collapse" id="collapseAll">
                        Collapse All
                    </flux:button>
                </flux:button.group>
            </flux:between>
        </flux:card.header>
        
        <flux:card.body>
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
        </flux:card.body>
    </flux:card>

    <!-- Quick Actions -->
    <flux:card>
        <flux:card.header>
            <flux:heading size="lg">Quick Actions</flux:heading>
        </flux:card.header>
        
        <flux:card.body>
            <flux:grid cols="3">
                <flux:button variant="secondary" class="w-full justify-start" icon="document-plus">
                    Import Subsidiaries
                </flux:button>
                <flux:button variant="secondary" class="w-full justify-start" icon="document-arrow-down">
                    Export Hierarchy
                </flux:button>
                <flux:button variant="secondary" class="w-full justify-start" icon="cog-6-tooth">
                    Manage Permissions
                </flux:button>
            </flux:grid>
        </flux:card.body>
    </flux:card>
</flux:container>

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