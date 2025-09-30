<div>
    <!-- Header with Status Warning -->
    <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Contract: {{ $contract->contract_number }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Modify contract details and billing configuration</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('financial.contracts.show', $contract) }}" 
                       class="px-6 py-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        Cancel
                    </a>
                </div>
            </div>
            
            <!-- Status & Edit Restrictions Alert -->
            @if(!$canEdit)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 15c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Contract Status Restriction</h3>
                            <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                                This contract is currently <strong>{{ ucwords(str_replace('_', ' ', $contract->status)) }}</strong> 
                                and cannot be edited. Only draft and pending review contracts can be modified.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($contract->billingCalculations()->count() > 0)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 15c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Billing History Warning</h3>
                            <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                                This contract has existing billing records. Changes to pricing structures may affect future billing calculations.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Error Messages -->
    @if (session()->has('error'))
        <div class="max-w-7xl mx-auto px-6 mt-6">
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Error</h3>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="max-w-7xl mx-auto px-6 mt-6">
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Please correct the following errors:</h3>
                        <ul class="mt-2 text-sm text-red-700 dark:text-red-400 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-6">
        <form wire:submit.prevent="save">
            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px">
                        <button type="button" 
                                wire:click="$set('activeTab', 'basic')" 
                                class="whitespace-nowrap py-6 px-6 border-b-2 font-medium text-sm transition-colors
                                       {{ $activeTab === 'basic' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            Basic Information
                        </button>
                        <button type="button" 
                                wire:click="$set('activeTab', 'billing')" 
                                class="whitespace-nowrap py-6 px-6 border-b-2 font-medium text-sm transition-colors
                                       {{ $activeTab === 'billing' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            Billing Model
                        </button>
                        <button type="button" 
                                wire:click="$set('activeTab', 'schedules')" 
                                class="whitespace-nowrap py-6 px-6 border-b-2 font-medium text-sm transition-colors
                                       {{ $activeTab === 'schedules' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            Schedules
                            @if($contract->schedules()->count() > 0)
                                <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium text-blue-700 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 rounded-full">
                                    {{ $contract->schedules()->count() }}
                                </span>
                            @endif
                        </button>
                        <button type="button" 
                                wire:click="$set('activeTab', 'assignments')" 
                                class="whitespace-nowrap py-6 px-6 border-b-2 font-medium text-sm transition-colors
                                       {{ $activeTab === 'assignments' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            Assignments
                            @if(($contract->assetAssignments()->count() + $contract->contactAssignments()->count()) > 0)
                                <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-300 rounded-full">
                                    {{ $contract->assetAssignments()->count() + $contract->contactAssignments()->count() }}
                                </span>
                            @endif
                        </button>
                        <button type="button" 
                                wire:click="$set('activeTab', 'content')" 
                                class="whitespace-nowrap py-6 px-6 border-b-2 font-medium text-sm transition-colors
                                       {{ $activeTab === 'content' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            Contract Language
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Basic Information Tab -->
                    @if($activeTab === 'basic')
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <!-- Contract Title -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Contract Title *</label>
                                    <input type="text" 
                                           wire:model.defer="title" 
                                           @if(!$canEdit) disabled @endif
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg 
                                                  {{ $canEdit ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}">
                                    @error('title') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Contract Type -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Contract Type *</label>
                                    <select wire:model.defer="contract_type" 
                                            @if(!$canEdit) disabled @endif
                                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg
                                                   {{ $canEdit ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}">
                                        <option value="">Select contract type...</option>
                                        <option value="one_time_service">One-Time Service</option>
                                        <option value="recurring_service">Recurring Service</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="support">Support</option>
                                        <option value="managed_services">Managed Services</option>
                                    </select>
                                    @error('contract_type') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Client Selection -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Client *</label>
                                    <select wire:model.defer="client_id" 
                                            @if(!$canEdit || $contract->status !== 'draft') disabled @endif
                                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg
                                                   {{ $canEdit && $contract->status === 'draft' ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}">
                                        <option value="">Select client...</option>
                                        @foreach($this->clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('client_id') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                    <textarea wire:model.defer="description" 
                                              rows="4" 
                                              @if(!$canEdit) disabled @endif
                                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg resize-none
                                                     {{ $canEdit ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}"
                                              placeholder="Brief description of the contract scope..."></textarea>
                                    @error('description') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Start Date -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Start Date *</label>
                                    <input type="date" 
                                           wire:model.defer="start_date" 
                                           @if(!$canEdit) disabled @endif
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg
                                                  {{ $canEdit ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}">
                                    @error('start_date') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- End Date -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                                    <input type="date" 
                                           wire:model.defer="end_date" 
                                           @if(!$canEdit) disabled @endif
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg
                                                  {{ $canEdit ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}">
                                    @error('end_date') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Contract Value -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Contract Value (Annual)
                                        @if($billing_model !== 'fixed')
                                            <span class="text-xs font-normal text-gray-500">(Auto-calculated)</span>
                                        @endif
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 dark:text-gray-400">$</span>
                                        <input type="number" 
                                               wire:model.defer="contract_value" 
                                               step="0.01" 
                                               @if(!$canEdit || $billing_model !== 'fixed') disabled readonly @endif
                                               class="w-full pl-7 pr-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg
                                                      {{ $canEdit && $billing_model === 'fixed' ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}">
                                    </div>
                                    @if($billing_model !== 'fixed')
                                        <p class="text-xs text-gray-500 mt-1">
                                            Based on {{ $billing_model === 'per_asset' ? 'asset' : ($billing_model === 'per_contact' ? 'contact' : 'tiered/hybrid') }} assignments
                                        </p>
                                    @endif
                                    @error('contract_value') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Status *</label>
                                    <select wire:model.defer="status" 
                                            @if(!$canEdit) disabled @endif
                                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg
                                                   {{ $canEdit ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}">
                                        <option value="draft">Draft</option>
                                        <option value="pending_review">Pending Review</option>
                                        <option value="active">Active</option>
                                        <option value="expired">Expired</option>
                                        <option value="terminated">Terminated</option>
                                    </select>
                                    @error('status') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Financial Configuration -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                                        <select wire:model.defer="currency_code" 
                                                @if(!$canEdit) disabled @endif
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg
                                                       {{ $canEdit ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}">
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                            <option value="GBP">GBP</option>
                                            <option value="CAD">CAD</option>
                                            <option value="AUD">AUD</option>
                                        </select>
                                        @error('currency_code') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Payment Terms</label>
                                        <input type="text" 
                                               wire:model.defer="payment_terms" 
                                               @if(!$canEdit) disabled @endif
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg
                                                      {{ $canEdit ? 'focus:ring-2 focus:ring-blue-500 focus:border-blue-500' : 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' }}"
                                               placeholder="Net 30">
                                        @error('payment_terms') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Billing Model Tab -->
                    @if($activeTab === 'billing')
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Select Billing Model</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Fixed Price -->
                                <div wire:click="selectBillingModel('fixed')" 
                                     class="relative border-2 rounded-lg p-6 cursor-pointer transition-all
                                            {{ $billing_model === 'fixed' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}
                                            {{ !$canEdit ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    <div class="flex items-center mb-4">
                                        <div class="w-4 h-4 rounded-full border-2 transition-colors
                                                    {{ $billing_model === 'fixed' ? 'border-blue-500 bg-blue-500' : 'border-gray-300 dark:border-gray-600' }}">
                                            @if($billing_model === 'fixed')
                                                <div class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                                            @endif
                                        </div>
                                        <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">Fixed Price</h3>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Single fixed monthly or project fee</p>
                                </div>

                                <!-- Per Asset -->
                                <div wire:click="selectBillingModel('per_asset')" 
                                     class="relative border-2 rounded-lg p-6 cursor-pointer transition-all
                                            {{ $billing_model === 'per_asset' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}
                                            {{ !$canEdit ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    <div class="flex items-center mb-4">
                                        <div class="w-4 h-4 rounded-full border-2 transition-colors
                                                    {{ $billing_model === 'per_asset' ? 'border-blue-500 bg-blue-500' : 'border-gray-300 dark:border-gray-600' }}">
                                            @if($billing_model === 'per_asset')
                                                <div class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                                            @endif
                                        </div>
                                        <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">Per Asset</h3>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Charge per device or asset managed</p>
                                </div>

                                <!-- Per Contact -->
                                <div wire:click="selectBillingModel('per_contact')" 
                                     class="relative border-2 rounded-lg p-6 cursor-pointer transition-all
                                            {{ $billing_model === 'per_contact' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}
                                            {{ !$canEdit ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    <div class="flex items-center mb-4">
                                        <div class="w-4 h-4 rounded-full border-2 transition-colors
                                                    {{ $billing_model === 'per_contact' ? 'border-blue-500 bg-blue-500' : 'border-gray-300 dark:border-gray-600' }}">
                                            @if($billing_model === 'per_contact')
                                                <div class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                                            @endif
                                        </div>
                                        <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">Per Contact</h3>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Charge per user or seat</p>
                                </div>

                                <!-- Tiered -->
                                <div wire:click="selectBillingModel('tiered')" 
                                     class="relative border-2 rounded-lg p-6 cursor-pointer transition-all
                                            {{ $billing_model === 'tiered' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}
                                            {{ !$canEdit ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    <div class="flex items-center mb-4">
                                        <div class="w-4 h-4 rounded-full border-2 transition-colors
                                                    {{ $billing_model === 'tiered' ? 'border-blue-500 bg-blue-500' : 'border-gray-300 dark:border-gray-600' }}">
                                            @if($billing_model === 'tiered')
                                                <div class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                                            @endif
                                        </div>
                                        <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">Tiered Pricing</h3>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Different rates based on volume or service level</p>
                                </div>

                                <!-- Hybrid -->
                                <div wire:click="selectBillingModel('hybrid')" 
                                     class="relative border-2 rounded-lg p-6 cursor-pointer transition-all
                                            {{ $billing_model === 'hybrid' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}
                                            {{ !$canEdit ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    <div class="flex items-center mb-4">
                                        <div class="w-4 h-4 rounded-full border-2 transition-colors
                                                    {{ $billing_model === 'hybrid' ? 'border-blue-500 bg-blue-500' : 'border-gray-300 dark:border-gray-600' }}">
                                            @if($billing_model === 'hybrid')
                                                <div class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                                            @endif
                                        </div>
                                        <h3 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">Hybrid Model</h3>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Combination of multiple billing methods</p>
                                </div>
                            </div>
                        </div>

                        <!-- Current Billing Summary -->
                        @if($billing_model && $billing_model !== 'fixed')
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Billing Calculation Summary</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Assigned Assets</div>
                                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $contract->assetAssignments()->count() }}</div>
                                        @if($billing_model === 'per_asset')
                                            <div class="text-xs text-gray-500 mt-1">
                                                @php
                                                    $monthlyAssetTotal = 0;
                                                    foreach ($contract->assetAssignments as $assignment) {
                                                        $monthlyAssetTotal += $assignment->calculateMonthlyCharges();
                                                    }
                                                @endphp
                                                ${{ number_format($monthlyAssetTotal, 2) }}/month
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Assigned Contacts</div>
                                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $contract->contactAssignments()->count() }}</div>
                                        @if($billing_model === 'per_contact')
                                            <div class="text-xs text-gray-500 mt-1">
                                                @php
                                                    $monthlyContactTotal = 0;
                                                    foreach ($contract->contactAssignments as $assignment) {
                                                        $monthlyContactTotal += $assignment->monthly_rate ?? 0;
                                                    }
                                                @endphp
                                                ${{ number_format($monthlyContactTotal, 2) }}/month
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Value</div>
                                        <div class="text-xs text-gray-500 mb-1">Monthly: ${{ number_format($contract->getMonthlyRecurringRevenue(), 2) }}</div>
                                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">${{ number_format($contract->getAnnualValue(), 2) }}</div>
                                        <div class="text-xs text-gray-500 mt-1">Annual</div>
                                    </div>
                                </div>
                                
                                @if($billing_model === 'hybrid' && $contract->pricing_structure)
                                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">Breakdown:</div>
                                        <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                            @if(isset($contract->pricing_structure['recurring_monthly']))
                                                <div class="flex justify-between">
                                                    <span>Base Monthly Fee:</span>
                                                    <span>${{ number_format($contract->pricing_structure['recurring_monthly'], 2) }}</span>
                                                </div>
                                            @endif
                                            @if($contract->assetAssignments()->count() > 0)
                                                <div class="flex justify-between">
                                                    <span>Per-Asset Charges:</span>
                                                    <span>${{ number_format($monthlyAssetTotal ?? 0, 2) }}</span>
                                                </div>
                                            @endif
                                            @if($contract->contactAssignments()->count() > 0)
                                                <div class="flex justify-between">
                                                    <span>Per-Contact Charges:</span>
                                                    <span>${{ number_format($monthlyContactTotal ?? 0, 2) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <button type="button" wire:click="calculateContractValue" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Recalculate
                                    </button>
                                </div>
                            </div>
                        @endif
                        
                        @if($billing_model === 'fixed')
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Fixed Price Billing</h3>
                                        <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                                            Enter the total contract value manually in the Basic Information tab. This value will not be automatically calculated.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    @endif

                    <!-- Schedules Tab -->
                    @if($activeTab === 'schedules')
                    <div>
                        <livewire:contracts.schedule-manager :contract="$contract" :key="'schedules-'.$contract->id" />
                    </div>
                    @endif

                    <!-- Assignments Tab -->
                    @if($activeTab === 'assignments')
                    <div class="space-y-6">
                        {{-- Billing Model Notice --}}
                        @if(in_array($billing_model, ['per_asset', 'per_contact', 'hybrid']))
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Assignment-Based Billing</h3>
                                        <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                                            This contract uses {{ $billing_model }} billing. Assignments directly affect the contract value calculation.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Fixed Price Billing</h3>
                                        <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                                            This contract uses <strong>{{ $billing_model }}</strong> billing. You can still assign assets and contacts for tracking purposes, but they won't affect the contract value.
                                            To enable automatic billing calculations, change the billing model in the "Billing Model" tab.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-6">
                            {{-- Asset Assignments Component - Always Show --}}
                            <livewire:contracts.asset-assignment-manager :contract="$contract" :key="'asset-assignments-'.$contract->id" />
                            
                            {{-- Contact Assignments Placeholder --}}
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <flux:heading size="base">Contact Assignments</flux:heading>
                                        <flux:text size="sm" class="text-gray-600">
                                            Manage which contacts are covered under this contract
                                        </flux:text>
                                    </div>
                                </div>
                                
                                <flux:callout icon="information-circle" variant="info">
                                    Contact assignment management coming soon. For now, contacts can be assigned from the contract detail page after saving.
                                </flux:callout>
                            </div>
                        </div>

                    </div>
                    @endif

                    <!-- Contract Language Tab -->
                    @if($activeTab === 'content')
                    <div class="h-[800px]">
                        <livewire:contracts.contract-language-editor :contract="$contract" :key="$contract->id" />
                    </div>
                    @endif
                </div>

                <!-- Form Actions -->
                <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
                    <a href="{{ route('financial.contracts.show', $contract) }}" 
                       class="px-6 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" 
                            @if(!$canSave) disabled @endif
                            class="px-6 py-2 text-white rounded-lg transition-colors
                                   {{ $canSave ? 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600' : 'bg-gray-400 dark:bg-gray-600 cursor-not-allowed' }}">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
