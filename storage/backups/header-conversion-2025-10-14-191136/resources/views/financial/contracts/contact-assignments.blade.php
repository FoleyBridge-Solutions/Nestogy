@extends('layouts.app')

@php
$activeDomain = 'financial';
$activeItem = 'contracts';
@endphp

@section('title', 'Contact Assignments - ' . $contract->title)

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="contactAssignmentManager(@json($contract), @json($availableContacts), @json($assignedContacts), @json($accessTiers))">
    <!-- Header -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Contact Assignments</h1>
                <p class="text-gray-600 mt-1">Manage portal access assignments for contract: <strong>{{ $contract->title }}</strong></p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('financial.contracts.edit', $contract) }}" 
                   class="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                    Back to Contract
                </a>
            </div>
        </div>
        
        <!-- Contract Billing Info -->
        @if(in_array($contract->billing_model, ['per_contact', 'hybrid']))
            <div class="mt-6 bg-purple-50 border border-purple-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-purple-800">Billing Model: {{ ucwords(str_replace('_', ' ', $contract->billing_model)) }}</h3>
                        <p class="text-sm text-purple-700">
                            Contact assignments and access tiers directly impact billing calculations.
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-purple-900">{{ $assignedContacts->count() }} Contacts</div>
                        <div class="text-sm text-purple-700">Currently Assigned</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Access Tiers Configuration -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-medium text-gray-900">Access Tiers & Pricing</h2>
            <button @click="showTierManagementModal = true" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Manage Tiers
            </button>
        </div>

        <!-- Access Tiers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <template x-for="tier in accessTiers" :key="tier.id">
                <div class="border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-medium text-gray-900" x-text="tier.name"></h3>
                        <span class="text-lg font-bold text-green-600" x-text="'$' + parseFloat(tier.rate || 0).toFixed(2)"></span>
                    </div>
                    <p class="text-sm text-gray-600 mb-6" x-text="tier.description || 'Portal access tier'"></p>
                    
                    <!-- Permissions List -->
                    <div class="space-y-1">
                        <template x-for="permission in (tier.permissions || '').split(',').filter(p => p.trim())" :key="permission">
                            <div class="flex items-center text-xs text-gray-500">
                                <svg class="w-3 h-3 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span x-text="permission.trim()"></span>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Assigned Count -->
                    <div class="mt-6 pt-3 border-t border-gray-200">
                        <div class="text-sm text-gray-600">
                            <span x-text="getContactCountForTier(tier.id)"></span> contacts assigned
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- No Tiers State -->
            <div x-show="accessTiers.length === 0" class="flex-1 px-6-span-3 text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No access tiers configured</h3>
                <p class="mt-1 text-sm text-gray-500">Create access tiers to manage contact portal permissions and billing.</p>
                <button @click="showTierManagementModal = true" 
                        class="mt-6 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Create First Tier
                </button>
            </div>
        </div>
    </div>

    <!-- Assignment Controls -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-medium text-gray-900">Contact Management</h2>
            <div class="flex items-center gap-3">
                <button @click="showBulkAssignModal = true" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Bulk Assign
                </button>
                <button @click="showContactFilters = !showContactFilters" 
                        :class="showContactFilters ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
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
                <input type="text" x-model="searchTerm" @input="filterContacts"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" 
                       placeholder="Search contacts by name, email, or title...">
            </div>

            <!-- Advanced Filters -->
            <div x-show="showContactFilters" x-transition class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6 bg-gray-50 rounded-lg">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assignment Status</label>
                    <select x-model="filters.assignmentStatus" @change="filterContacts" 
                            class="w-full px-6 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Contacts</option>
                        <option value="assigned">Assigned to Contract</option>
                        <option value="unassigned">Not Assigned</option>
                        <option value="other_contract">Assigned to Other Contract</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Access Tier</label>
                    <select x-model="filters.accessTier" @change="filterContacts" 
                            class="w-full px-6 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Tiers</option>
                        <template x-for="tier in accessTiers" :key="tier.id">
                            <option :value="tier.id" x-text="tier.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Type</label>
                    <select x-model="filters.contactType" @change="filterContacts" 
                            class="w-full px-6 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Types</option>
                        <option value="primary">Primary Contact</option>
                        <option value="technical">Technical Contact</option>
                        <option value="billing">Billing Contact</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Portal Status</label>
                    <select x-model="filters.portalStatus" @change="filterContacts" 
                            class="w-full px-6 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="">All Status</option>
                        <option value="active">Active Portal User</option>
                        <option value="inactive">Inactive Portal User</option>
                        <option value="never_logged_in">Never Logged In</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Assignment Grid -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-6 py-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                    Client Contacts
                    <span class="text-sm font-normal text-gray-500">
                        (<span x-text="filteredContacts.length"></span> of <span x-text="allContacts.length"></span> contacts)
                    </span>
                </h3>
                <div class="flex items-center gap-2 text-sm">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-purple-500 rounded"></div>
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
            <!-- Contact Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="contact in filteredContacts" :key="contact.id">
                    <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow"
                         :class="getContactCardClass(contact)">
                        
                        <!-- Contact Header -->
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 truncate" x-text="contact.name"></h4>
                                <p class="text-sm text-gray-500 truncate" x-text="contact.email"></p>
                                <p class="text-xs text-gray-400" x-text="contact.title || 'No title'"></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <!-- Assignment Status Indicator -->
                                <div class="w-3 h-3 rounded-full" 
                                     :class="getContactStatusColor(contact)"></div>
                                <!-- Assignment Checkbox -->
                                <input type="checkbox" 
                                       :checked="isContactAssigned(contact)"
                                       :disabled="!canAssignContact(contact)"
                                       @change="toggleContactAssignment(contact, $event)"
                                       class="rounded text-purple-600">
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Type:</span>
                                <span class="font-medium capitalize" x-text="contact.contact_type || 'other'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Phone:</span>
                                <span class="font-medium" x-text="contact.phone || 'N/A'"></span>
                            </div>
                            <div x-show="contact.current_contract && contact.current_contract.id !== contract.id" 
                                 class="flex items-center justify-between">
                                <span class="text-gray-600">Contract:</span>
                                <span class="text-xs text-yellow-700 truncate" x-text="contact.current_contract.title"></span>
                            </div>
                        </div>

                        <!-- Access Tier Selection -->
                        <div x-show="isContactAssigned(contact)" class="mt-6 pt-3 border-t border-gray-200">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Access Tier</label>
                            <select x-model="getContactAssignment(contact).access_tier_id" 
                                    @change="updateContactTier(contact, $event.target.value)"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded">
                                <option value="">Select tier...</option>
                                <template x-for="tier in accessTiers" :key="tier.id">
                                    <option :value="tier.id" x-text="tier.name + ' ($' + parseFloat(tier.rate || 0).toFixed(2) + ')'"></option>
                                </template>
                            </select>
                            
                            <!-- Billing Rate Display -->
                            <div x-show="getContactAssignment(contact).access_tier_id" class="mt-2 text-xs text-purple-600">
                                Rate: <span x-text="'$' + getContactBillingRate(contact).toFixed(2) + '/month'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="filteredContacts.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM9 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No contacts found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    <span x-show="searchTerm || Object.values(filters).some(f => f)">Try adjusting your search or filters.</span>
                    <span x-show="!searchTerm && !Object.values(filters).some(f => f)">No contacts available for this client.</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Summary Panel -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Assignment Summary</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600" x-text="assignedContacts.length"></div>
                <div class="text-sm text-gray-600">Total Assigned</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600" x-text="getContactCountByTier('admin')"></div>
                <div class="text-sm text-gray-600">Admin Access</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600" x-text="getContactCountByTier('standard')"></div>
                <div class="text-sm text-gray-600">Standard Access</div>
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
                    :class="hasChanges ? 'bg-purple-600 hover:bg-purple-700' : 'bg-gray-400 cursor-not-allowed'"
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

    <!-- Tier Management Modal -->
    <div x-show="showTierManagementModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div @click.away="showTierManagementModal = false"
             class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Manage Access Tiers</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-6">Configure access tiers and pricing for contact portal access.</p>
                <!-- Tier management interface would go here -->
                <div class="text-center py-8 text-gray-500">
                    <p>Access tier management interface</p>
                    <p class="text-sm">This would allow creating/editing access tiers with permissions and pricing</p>
                </div>
            </div>
            <div class="px-6 py-6 border-t border-gray-200 flex justify-end">
                <button @click="showTierManagementModal = false" 
                        class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Close
                </button>
            </div>
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
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Bulk Contact Assignment</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assignment Action</label>
                    <select x-model="bulkAction" class="w-full px-6 py-2 border border-gray-300 rounded-lg">
                        <option value="assign">Assign to Contract</option>
                        <option value="unassign">Remove from Contract</option>
                    </select>
                </div>
                <div x-show="bulkAction === 'assign'">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Access Tier</label>
                    <select x-model="bulkAssignTier" class="w-full px-6 py-2 border border-gray-300 rounded-lg">
                        <option value="">Select tier...</option>
                        <template x-for="tier in accessTiers" :key="tier.id">
                            <option :value="tier.id" x-text="tier.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact Criteria</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="bulkCriteria.allUnassigned" class="rounded">
                            <span class="ml-2 text-sm">All unassigned contacts</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="bulkCriteria.primaryOnly" class="rounded">
                            <span class="ml-2 text-sm">Primary contacts only</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="bulkCriteria.technicalOnly" class="rounded">
                            <span class="ml-2 text-sm">Technical contacts only</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="bulkCriteria.activePortalUsers" class="rounded">
                            <span class="ml-2 text-sm">Active portal users only</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6 border-t border-gray-200 flex justify-end gap-3">
                <button @click="showBulkAssignModal = false" 
                        class="px-6 py-2 text-gray-600 hover:text-gray-800">
                    Cancel
                </button>
                <button @click="performBulkAssignment" 
                        class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Apply Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function contactAssignmentManager(contract, availableContacts, assignedContacts, accessTiers) {
    return {
        contract: contract,
        allContacts: availableContacts || [],
        assignedContacts: assignedContacts || [],
        accessTiers: accessTiers || [],
        filteredContacts: [],
        searchTerm: '',
        showContactFilters: false,
        showTierManagementModal: false,
        showBulkAssignModal: false,
        saving: false,
        hasChanges: false,
        
        filters: {
            assignmentStatus: '',
            accessTier: '',
            contactType: '',
            portalStatus: ''
        },
        
        bulkAction: 'assign',
        bulkAssignTier: '',
        bulkCriteria: {
            allUnassigned: false,
            primaryOnly: false,
            technicalOnly: false,
            activePortalUsers: false
        },
        
        init() {
            this.filteredContacts = [...this.allContacts];
            this.filterContacts();
        },
        
        filterContacts() {
            let contacts = [...this.allContacts];
            
            // Search filter
            if (this.searchTerm) {
                const search = this.searchTerm.toLowerCase();
                contacts = contacts.filter(contact => 
                    (contact.name || '').toLowerCase().includes(search) ||
                    (contact.email || '').toLowerCase().includes(search) ||
                    (contact.title || '').toLowerCase().includes(search)
                );
            }
            
            // Assignment status filter
            if (this.filters.assignmentStatus) {
                switch (this.filters.assignmentStatus) {
                    case 'assigned':
                        contacts = contacts.filter(contact => this.isContactAssigned(contact));
                        break;
                    case 'unassigned':
                        contacts = contacts.filter(contact => !contact.current_contract);
                        break;
                    case 'other_contract':
                        contacts = contacts.filter(contact => 
                            contact.current_contract && contact.current_contract.id !== this.contract.id);
                        break;
                }
            }
            
            // Access tier filter
            if (this.filters.accessTier) {
                contacts = contacts.filter(contact => {
                    const assignment = this.getContactAssignment(contact);
                    return assignment && assignment.access_tier_id == this.filters.accessTier;
                });
            }
            
            // Contact type filter
            if (this.filters.contactType) {
                contacts = contacts.filter(contact => contact.contact_type === this.filters.contactType);
            }
            
            this.filteredContacts = contacts;
        },
        
        isContactAssigned(contact) {
            return this.assignedContacts.some(assigned => assigned.contact_id === contact.id);
        },
        
        canAssignContact(contact) {
            return !contact.current_contract || contact.current_contract.id === this.contract.id;
        },
        
        getContactAssignment(contact) {
            return this.assignedContacts.find(assigned => assigned.contact_id === contact.id);
        },
        
        getContactCardClass(contact) {
            if (this.isContactAssigned(contact)) {
                return 'border-purple-500 bg-purple-50';
            } else if (contact.current_contract && contact.current_contract.id !== this.contract.id) {
                return 'border-yellow-500 bg-yellow-50';
            }
            return 'border-gray-200 hover:border-purple-300';
        },
        
        getContactStatusColor(contact) {
            if (this.isContactAssigned(contact)) {
                return 'bg-purple-500';
            } else if (contact.current_contract && contact.current_contract.id !== this.contract.id) {
                return 'bg-yellow-500';
            }
            return 'bg-gray-300';
        },
        
        toggleContactAssignment(contact, event) {
            if (!this.canAssignContact(contact)) return;
            
            if (event.target.checked) {
                // Add to assigned
                this.assignedContacts.push({
                    contact_id: contact.id,
                    contract_id: this.contract.id,
                    access_tier_id: null
                });
            } else {
                // Remove from assigned
                this.assignedContacts = this.assignedContacts.filter(a => a.contact_id !== contact.id);
            }
            
            this.hasChanges = true;
        },
        
        updateContactTier(contact, tierId) {
            const assignment = this.getContactAssignment(contact);
            if (assignment) {
                assignment.access_tier_id = tierId;
                this.hasChanges = true;
            }
        },
        
        getContactBillingRate(contact) {
            const assignment = this.getContactAssignment(contact);
            if (assignment && assignment.access_tier_id) {
                const tier = this.accessTiers.find(t => t.id == assignment.access_tier_id);
                return tier ? parseFloat(tier.rate || 0) : 0;
            }
            return 0;
        },
        
        getContactCountForTier(tierId) {
            return this.assignedContacts.filter(a => a.access_tier_id == tierId).length;
        },
        
        getContactCountByTier(tierName) {
            const tier = this.accessTiers.find(t => t.name.toLowerCase().includes(tierName.toLowerCase()));
            return tier ? this.getContactCountForTier(tier.id) : 0;
        },
        
        calculateMonthlyBilling() {
            let total = 0;
            this.assignedContacts.forEach(assignment => {
                if (assignment.access_tier_id) {
                    const tier = this.accessTiers.find(t => t.id == assignment.access_tier_id);
                    if (tier) {
                        total += parseFloat(tier.rate || 0);
                    }
                }
            });
            return '$' + total.toFixed(2);
        },
        
        performBulkAssignment() {
            let contactsToProcess = this.allContacts.filter(contact => {
                let include = true;
                
                if (this.bulkCriteria.allUnassigned && contact.current_contract) {
                    include = false;
                }
                if (this.bulkCriteria.primaryOnly && contact.contact_type !== 'primary') {
                    include = false;
                }
                if (this.bulkCriteria.technicalOnly && contact.contact_type !== 'technical') {
                    include = false;
                }
                if (this.bulkCriteria.activePortalUsers && !contact.is_portal_active) {
                    include = false;
                }
                
                return include && this.canAssignContact(contact);
            });
            
            if (this.bulkAction === 'assign') {
                contactsToProcess.forEach(contact => {
                    if (!this.isContactAssigned(contact)) {
                        this.assignedContacts.push({
                            contact_id: contact.id,
                            contract_id: this.contract.id,
                            access_tier_id: this.bulkAssignTier || null
                        });
                    }
                });
            } else {
                contactsToProcess.forEach(contact => {
                    this.assignedContacts = this.assignedContacts.filter(a => a.contact_id !== contact.id);
                });
            }
            
            this.hasChanges = true;
            this.showBulkAssignModal = false;
            this.filterContacts();
        },
        
        async saveAssignments() {
            if (!this.hasChanges) return;
            
            this.saving = true;
            
            try {
                const response = await fetch(`/api/contracts/${this.contract.id}/contact-assignments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        assignments: this.assignedContacts
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
