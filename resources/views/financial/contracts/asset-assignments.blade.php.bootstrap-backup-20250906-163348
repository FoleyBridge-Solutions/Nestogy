@extends('layouts.app')

@php
$activeDomain = 'financial';
$activeItem = 'contracts';
$breadcrumbs = [
    ['name' => 'Financial', 'route' => 'financial.contracts.index'],
    ['name' => 'Contracts', 'route' => 'financial.contracts.index'],
    ['name' => $contract->title, 'route' => 'financial.contracts.show', 'params' => $contract],
    ['name' => 'Asset Assignments', 'active' => true]
];
@endphp

@section('title', 'Asset Assignments - ' . $contract->title)

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="assetAssignmentManager(@json($contract), @json($availableAssets), @json($assignedAssets))">
    <!-- Header -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Asset Assignments</h1>
                <p class="text-gray-600 mt-1">Manage device assignments for contract: <strong>{{ $contract->title }}</strong></p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('financial.contracts.edit', $contract) }}" 
                   class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                    Back to Contract
                </a>
            </div>
        </div>
        
        <!-- Contract Billing Info -->
        @if($contract->billing_model !== 'fixed')
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-blue-800">Billing Model: {{ ucwords(str_replace('_', ' ', $contract->billing_model)) }}</h3>
                        <p class="text-sm text-blue-700">
                            Asset assignments directly impact billing calculations for this contract.
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-blue-900">{{ $assignedAssets->count() }} Assets</div>
                        <div class="text-sm text-blue-700">Currently Assigned</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Assignment Controls -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-medium text-gray-900">Assignment Management</h2>
            <div class="flex items-center gap-3">
                <button @click="showBulkAssignModal = true" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Bulk Assign
                </button>
                <button @click="showAssetFilters = !showAssetFilters" 
                        :class="showAssetFilters ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filters
                </button>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="space-y-4">
            <!-- Search Bar -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" x-model="searchTerm" @input="filterAssets"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" 
                       placeholder="Search assets by name, type, or IP address...">
            </div>

            <!-- Advanced Filters -->
            <div x-show="showAssetFilters" x-transition class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asset Type</label>
                    <select x-model="filters.assetType" @change="filterAssets" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Types</option>
                        <option value="workstation">Workstation</option>
                        <option value="server">Server</option>
                        <option value="network_device">Network Device</option>
                        <option value="mobile_device">Mobile Device</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assignment Status</label>
                    <select x-model="filters.assignmentStatus" @change="filterAssets" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Assets</option>
                        <option value="assigned">Assigned to Contract</option>
                        <option value="unassigned">Not Assigned</option>
                        <option value="other_contract">Assigned to Other Contract</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Operating System</label>
                    <select x-model="filters.operatingSystem" @change="filterAssets" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All OS</option>
                        <option value="windows">Windows</option>
                        <option value="linux">Linux</option>
                        <option value="macos">macOS</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Online Status</label>
                    <select x-model="filters.onlineStatus" @change="filterAssets" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Status</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Asset Assignment Grid -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                    Client Assets
                    <span class="text-sm font-normal text-gray-500">
                        (<span x-text="filteredAssets.length"></span> of <span x-text="allAssets.length"></span> assets)
                    </span>
                </h3>
                <div class="flex items-center gap-2 text-sm">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-green-500 rounded"></div>
                        <span class="text-gray-600">Assigned</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-gray-300 rounded"></div>
                        <span class="text-gray-600">Available</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                        <span class="text-gray-600">Other Contract</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Asset Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="asset in filteredAssets" :key="asset.id">
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                         :class="getAssetCardClass(asset)"
                         @click="toggleAssetAssignment(asset)">
                        
                        <!-- Asset Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 truncate" x-text="asset.hostname || asset.name"></h4>
                                <p class="text-sm text-gray-500" x-text="asset.ip_address || 'No IP'"></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <!-- Assignment Status Indicator -->
                                <div class="w-3 h-3 rounded-full" 
                                     :class="getAssetStatusColor(asset)"></div>
                                <!-- Assignment Checkbox -->
                                <input type="checkbox" 
                                       :checked="isAssetAssigned(asset)"
                                       :disabled="!canAssignAsset(asset)"
                                       @click.stop="toggleAssetAssignment(asset)"
                                       class="rounded text-blue-600">
                            </div>
                        </div>

                        <!-- Asset Info -->
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Type:</span>
                                <span class="font-medium capitalize" x-text="asset.asset_type.replace('_', ' ')"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">OS:</span>
                                <span class="font-medium" x-text="asset.operating_system || 'Unknown'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                      :class="asset.is_online ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                      x-text="asset.is_online ? 'Online' : 'Offline'"></span>
                            </div>
                            <div x-show="asset.current_contract && asset.current_contract.id !== contract.id" 
                                 class="flex items-center justify-between">
                                <span class="text-gray-600">Contract:</span>
                                <span class="text-xs text-yellow-700 truncate" x-text="asset.current_contract.title"></span>
                            </div>
                        </div>

                        <!-- Billing Rate Info -->
                        <div x-show="isAssetAssigned(asset) && getAssetBillingRate(asset)" 
                             class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Billing Rate:</span>
                                <span class="font-medium text-green-600" 
                                      x-text="'$' + getAssetBillingRate(asset) + '/month'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="filteredAssets.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No assets found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    <span x-show="searchTerm || Object.values(filters).some(f => f)">Try adjusting your search or filters.</span>
                    <span x-show="!searchTerm && !Object.values(filters).some(f => f)">No assets available for this client.</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Summary Panel -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Assignment Summary</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600" x-text="assignedAssets.length"></div>
                <div class="text-sm text-gray-600">Total Assigned</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600" x-text="getAssetCountByType('workstation')"></div>
                <div class="text-sm text-gray-600">Workstations</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600" x-text="getAssetCountByType('server')"></div>
                <div class="text-sm text-gray-600">Servers</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600" x-text="calculateMonthlyBilling()"></div>
                <div class="text-sm text-gray-600">Monthly Billing</div>
            </div>
        </div>

        <!-- Save Changes -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-600">
                <span x-show="hasChanges" class="text-orange-600">You have unsaved changes</span>
                <span x-show="!hasChanges" class="text-green-600">All changes saved</span>
            </div>
            <button @click="saveAssignments" 
                    :disabled="!hasChanges"
                    :class="hasChanges ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'"
                    class="px-6 py-2 text-white rounded-lg transition-colors">
                <svg x-show="!saving" class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <svg x-show="saving" class="animate-spin w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="m100 50c0 28-22 50-50 50s-50-22-50-50 22-50 50-50"></path>
                </svg>
                <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
            </button>
        </div>
    </div>

    <!-- Bulk Assignment Modal -->
    <div x-show="showBulkAssignModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div @click.away="showBulkAssignModal = false"
             class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Bulk Asset Assignment</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assignment Action</label>
                    <select x-model="bulkAction" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="assign">Assign to Contract</option>
                        <option value="unassign">Remove from Contract</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset Criteria</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="bulkCriteria.allUnassigned" class="rounded">
                            <span class="ml-2 text-sm">All unassigned assets</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="bulkCriteria.workstationsOnly" class="rounded">
                            <span class="ml-2 text-sm">Workstations only</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="bulkCriteria.serversOnly" class="rounded">
                            <span class="ml-2 text-sm">Servers only</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="bulkCriteria.onlineOnly" class="rounded">
                            <span class="ml-2 text-sm">Online assets only</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button @click="showBulkAssignModal = false" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Cancel
                </button>
                <button @click="performBulkAssignment" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Apply Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function assetAssignmentManager(contract, availableAssets, assignedAssets) {
    return {
        contract: contract,
        allAssets: availableAssets || [],
        assignedAssets: assignedAssets || [],
        filteredAssets: [],
        searchTerm: '',
        showAssetFilters: false,
        showBulkAssignModal: false,
        saving: false,
        hasChanges: false,
        
        filters: {
            assetType: '',
            assignmentStatus: '',
            operatingSystem: '',
            onlineStatus: ''
        },
        
        bulkAction: 'assign',
        bulkCriteria: {
            allUnassigned: false,
            workstationsOnly: false,
            serversOnly: false,
            onlineOnly: false
        },
        
        init() {
            this.filteredAssets = [...this.allAssets];
            this.filterAssets();
        },
        
        filterAssets() {
            let assets = [...this.allAssets];
            
            // Search filter
            if (this.searchTerm) {
                const search = this.searchTerm.toLowerCase();
                assets = assets.filter(asset => 
                    (asset.hostname || asset.name || '').toLowerCase().includes(search) ||
                    (asset.ip_address || '').toLowerCase().includes(search) ||
                    (asset.asset_type || '').toLowerCase().includes(search)
                );
            }
            
            // Type filter
            if (this.filters.assetType) {
                assets = assets.filter(asset => asset.asset_type === this.filters.assetType);
            }
            
            // Assignment status filter
            if (this.filters.assignmentStatus) {
                switch (this.filters.assignmentStatus) {
                    case 'assigned':
                        assets = assets.filter(asset => this.isAssetAssigned(asset));
                        break;
                    case 'unassigned':
                        assets = assets.filter(asset => !asset.current_contract);
                        break;
                    case 'other_contract':
                        assets = assets.filter(asset => 
                            asset.current_contract && asset.current_contract.id !== this.contract.id);
                        break;
                }
            }
            
            // OS filter
            if (this.filters.operatingSystem) {
                assets = assets.filter(asset => 
                    (asset.operating_system || '').toLowerCase().includes(this.filters.operatingSystem.toLowerCase()));
            }
            
            // Online status filter
            if (this.filters.onlineStatus) {
                assets = assets.filter(asset => 
                    this.filters.onlineStatus === 'online' ? asset.is_online : !asset.is_online);
            }
            
            this.filteredAssets = assets;
        },
        
        isAssetAssigned(asset) {
            return this.assignedAssets.some(assigned => assigned.id === asset.id);
        },
        
        canAssignAsset(asset) {
            return !asset.current_contract || asset.current_contract.id === this.contract.id;
        },
        
        getAssetCardClass(asset) {
            if (this.isAssetAssigned(asset)) {
                return 'border-green-500 bg-green-50';
            } else if (asset.current_contract && asset.current_contract.id !== this.contract.id) {
                return 'border-yellow-500 bg-yellow-50';
            }
            return 'border-gray-200 hover:border-blue-300';
        },
        
        getAssetStatusColor(asset) {
            if (this.isAssetAssigned(asset)) {
                return 'bg-green-500';
            } else if (asset.current_contract && asset.current_contract.id !== this.contract.id) {
                return 'bg-yellow-500';
            }
            return 'bg-gray-300';
        },
        
        getAssetBillingRate(asset) {
            // This would come from the contract's billing rules
            const rates = {
                'workstation': 15.00,
                'server': 45.00,
                'network_device': 25.00,
                'mobile_device': 10.00
            };
            return rates[asset.asset_type] || 0;
        },
        
        toggleAssetAssignment(asset) {
            if (!this.canAssignAsset(asset)) return;
            
            if (this.isAssetAssigned(asset)) {
                // Remove from assigned
                this.assignedAssets = this.assignedAssets.filter(a => a.id !== asset.id);
            } else {
                // Add to assigned
                this.assignedAssets.push(asset);
            }
            
            this.hasChanges = true;
        },
        
        getAssetCountByType(type) {
            return this.assignedAssets.filter(asset => asset.asset_type === type).length;
        },
        
        calculateMonthlyBilling() {
            let total = 0;
            this.assignedAssets.forEach(asset => {
                total += this.getAssetBillingRate(asset);
            });
            return '$' + total.toFixed(2);
        },
        
        performBulkAssignment() {
            let assetsToProcess = this.allAssets.filter(asset => {
                let include = true;
                
                if (this.bulkCriteria.allUnassigned && asset.current_contract) {
                    include = false;
                }
                if (this.bulkCriteria.workstationsOnly && asset.asset_type !== 'workstation') {
                    include = false;
                }
                if (this.bulkCriteria.serversOnly && asset.asset_type !== 'server') {
                    include = false;
                }
                if (this.bulkCriteria.onlineOnly && !asset.is_online) {
                    include = false;
                }
                
                return include && this.canAssignAsset(asset);
            });
            
            if (this.bulkAction === 'assign') {
                assetsToProcess.forEach(asset => {
                    if (!this.isAssetAssigned(asset)) {
                        this.assignedAssets.push(asset);
                    }
                });
            } else {
                assetsToProcess.forEach(asset => {
                    this.assignedAssets = this.assignedAssets.filter(a => a.id !== asset.id);
                });
            }
            
            this.hasChanges = true;
            this.showBulkAssignModal = false;
            this.filterAssets();
        },
        
        async saveAssignments() {
            if (!this.hasChanges) return;
            
            this.saving = true;
            
            try {
                const response = await fetch(`/api/contracts/${this.contract.id}/asset-assignments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        asset_ids: this.assignedAssets.map(asset => asset.id)
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.hasChanges = false;
                    // Show success message
                } else {
                    alert('Error saving assignments: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving assignments:', error);
                alert('Error saving assignments');
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endsection