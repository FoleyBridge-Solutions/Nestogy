@extends('layouts.app')

@section('title', 'Subsidiary Management')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Subsidiary Management</h1>
            <p class="text-gray-600 mt-2">Manage your company hierarchy and subsidiary relationships</p>
        </div>
        
        @if($company->canCreateSubsidiaries() && !$company->hasReachedMaxSubsidiaryDepth())
            <a href="{{ route('subsidiaries.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <i class="fas fa-plus mr-2"></i> Create Subsidiary
            </a>
        @endif
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white border border-blue-200 rounded-lg shadow-sm hover:shadow-md transition-shadow h-full">
            <div class="p-6 flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-sitemap text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total_subsidiaries'] }}</div>
                    <div class="text-sm text-gray-600">Total Subsidiaries</div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-green-200 rounded-lg shadow-sm hover:shadow-md transition-shadow h-full">
            <div class="p-6 flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-building text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['direct_subsidiaries'] }}</div>
                    <div class="text-sm text-gray-600">Direct Subsidiaries</div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-cyan-200 rounded-lg shadow-sm hover:shadow-md transition-shadow h-full">
            <div class="p-6 flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-layer-group text-2xl text-cyan-600"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['current_depth'] }}</div>
                    <div class="text-sm text-gray-600">Current Depth</div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-yellow-200 rounded-lg shadow-sm hover:shadow-md transition-shadow h-full">
            <div class="p-6 flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-chart-line text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['max_depth'] }}</div>
                    <div class="text-sm text-gray-600">Max Depth</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Hierarchy Tree -->
        <div class="lg:col-span-12-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Company Hierarchy</h2>
                    <div class="flex space-x-2">
                        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" id="expandAll">
                            <i class="fas fa-expand-alt mr-1"></i> Expand All
                        </button>
                        <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors" id="collapseAll">
                            <i class="fas fa-compress-alt mr-1"></i> Collapse All
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    @if($stats['total_subsidiaries'] > 0)
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-sitemap text-4xl mb-4 text-gray-400"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Subsidiary Hierarchy</h3>
                            <p class="text-gray-600 mb-4">Hierarchy visualization will be implemented here.</p>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left max-w-md mx-auto">
                                <div class="text-sm text-blue-800">
                                    <div class="font-medium mb-1"><strong>Total Subsidiaries:</strong> {{ $stats['total_subsidiaries'] }}</div>
                                    <div class="font-medium mb-1"><strong>Direct Subsidiaries:</strong> {{ $stats['direct_subsidiaries'] }}</div>
                                    <div class="font-medium"><strong>Organization Level:</strong> {{ $stats['current_depth'] }}</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-sitemap text-4xl mb-4 text-gray-400"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Subsidiaries Found</h3>
                            <p class="text-gray-600 mb-6">You haven't created any subsidiaries yet.</p>
                            @if($company->canCreateSubsidiaries())
                                <a href="{{ route('subsidiaries.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    Create Your First Subsidiary
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Activity & Quick Actions -->
        <div class="space-y-6">
            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
                </div>
                <div class="p-6">
                    @if(!empty($recentActivity))
                        <div class="space-y-4">
                            @foreach($recentActivity as $activity)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-plus text-green-600 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">Subsidiary Created</p>
                                        <p class="text-sm text-gray-600">
                                            <strong>{{ $activity['company']['name'] }}</strong> was added to the hierarchy
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-500">
                            <i class="fas fa-clock text-2xl mb-2 text-gray-400"></i>
                            <p class="text-sm">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @if($company->canCreateSubsidiaries() && !$company->hasReachedMaxSubsidiaryDepth())
                            <a href="{{ route('subsidiaries.create') }}" class="w-full inline-flex items-center justify-center px-4 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <i class="fas fa-plus mr-2"></i> Create Subsidiary
                            </a>
                        @endif
                        
                        @if($stats['total_subsidiaries'] > 0)
                            <button type="button" class="w-full inline-flex items-center justify-center px-4 py-2 border border-cyan-300 rounded-md text-sm font-medium text-cyan-700 bg-white hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-colors" id="viewPermissions">
                                <i class="fas fa-shield-alt mr-2"></i> Manage Permissions
                            </button>
                            <button type="button" class="w-full inline-flex items-center justify-center px-4 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors" id="viewUsers">
                                <i class="fas fa-users mr-2"></i> Manage Users
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Expand/Collapse hierarchy - placeholder functionality
        const expandBtn = document.getElementById('expandAll');
        const collapseBtn = document.getElementById('collapseAll');
        
        if (expandBtn) {
            expandBtn.addEventListener('click', function() {
                // Future: Expand all hierarchy nodes
                console.log('Expand all hierarchy nodes');
            });
        }
        
        if (collapseBtn) {
            collapseBtn.addEventListener('click', function() {
                // Future: Collapse all hierarchy nodes  
                console.log('Collapse all hierarchy nodes');
            });
        }
        
        // Quick actions
        const permissionsBtn = document.getElementById('viewPermissions');
        const usersBtn = document.getElementById('viewUsers');
        
        if (permissionsBtn) {
            permissionsBtn.addEventListener('click', function() {
                // This would typically open a permissions management modal or redirect
                console.log('Manage permissions clicked');
            });
        }
        
        if (usersBtn) {
            usersBtn.addEventListener('click', function() {
                // This would typically open a users management modal or redirect
                console.log('Manage users clicked');
            });
        }
    });
</script>
@endpush
@endsection
