@extends('layouts.app')

@php
$activeDomain = 'financial';
$activeItem = 'contract-templates';
$breadcrumbs = [
    ['name' => 'Financial', 'route' => 'financial.contracts.index'],
    ['name' => 'Contract Templates', 'route' => 'financial.contracts.templates.index'],
    ['name' => $template->name, 'active' => true]
];
@endphp

@section('title', 'Template: ' . $template->name)

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="templateViewer(@json($template))">
    <!-- Header -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $template->name }}</h1>
                <p class="text-gray-600 mt-1">{{ $template->description ?: 'No description provided' }}</p>
                
                <!-- Meta Info -->
                <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        {{ ucfirst(str_replace('_', ' ', $template->template_type)) }}
                    </span>
                    
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        @switch($template->billing_model)
                            @case('fixed') Fixed Price
                            @break
                            @case('per_asset') Per Asset
                            @break
                            @case('per_contact') Per Contact
                            @break
                            @case('tiered') Tiered
                            @break
                            @case('hybrid') Hybrid
                            @break
                            @default {{ ucfirst($template->billing_model) }}
                        @endswitch
                    </span>
                    
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        {{ $template->formatted_usage_count }}
                    </span>
                    
                    @if($template->is_programmable)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Programmable
                        </span>
                    @endif
                </div>
            </div>
            
            <!-- Status Badge -->
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if($template->status === 'active') bg-green-100 text-green-800
                    @elseif($template->status === 'draft') bg-yellow-100 text-yellow-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ ucfirst($template->status) }}
                </span>
                
                <!-- Actions -->
                <div class="flex gap-2">
                    <a href="{{ route('financial.contracts.templates.edit', $template) }}" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Template
                    </a>
                    
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="p-2 text-gray-400 hover:text-gray-600 rounded">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                            <button @click="duplicateTemplate()" 
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Duplicate Template
                            </button>
                            <div class="border-t border-gray-100 my-1"></div>
                            <form action="{{ route('financial.contracts.templates.destroy', $template) }}" 
                                  method="POST" 
                                  onsubmit="return confirm('Are you sure? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    Delete Template
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Template Content and Preview -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Tab Navigation -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        <button @click="activeTab = 'preview'" 
                                :class="activeTab === 'preview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Preview
                        </button>
                        <button @click="activeTab = 'source'" 
                                :class="activeTab === 'source' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Source Content
                        </button>
                        <button @click="activeTab = 'billing'" 
                                :class="activeTab === 'billing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Billing Configuration
                        </button>
                        @if($template->automation_settings)
                        <button @click="activeTab = 'automation'" 
                                :class="activeTab === 'automation' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            🤖 Automation
                        </button>
                        @endif
                    </nav>
                </div>
                
                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Preview Tab -->
                    <div x-show="activeTab === 'preview'" class="space-y-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Contract Preview</h3>
                            <button @click="updatePreview()" 
                                    class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                Update Preview
                            </button>
                        </div>
                        
                        <!-- Variable Input Form -->
                        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                            <h4 class="font-medium text-gray-900">Variable Values (for preview)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="field in variableFields" :key="field.name">
                                    <div>
                                        <label :for="'var_' + field.name" 
                                               class="block text-sm font-medium text-gray-700 mb-1"
                                               x-text="field.name.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())"></label>
                                        <input :type="field.type === 'currency' ? 'number' : field.type" 
                                               :id="'var_' + field.name"
                                               x-model="variableValues[field.name]"
                                               :placeholder="field.default_value || 'Enter ' + field.name"
                                               class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Generated Preview -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="prose max-w-none" x-html="previewContent"></div>
                        </div>
                    </div>
                    
                    <!-- Source Content Tab -->
                    <div x-show="activeTab === 'source'">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Template Source</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <pre class="text-sm font-mono whitespace-pre-wrap text-gray-800">{{ $template->template_content }}</pre>
                        </div>
                        
                        @if($template->getVariableFields())
                            <div class="mt-6">
                                <h4 class="font-medium text-gray-900 mb-3">Available Variables</h4>
                                <div class="bg-white border border-gray-200 rounded-lg divide-y">
                                    @foreach($template->getVariableFields() as $field)
                                    <div class="p-3 flex items-center justify-between">
                                        <div>
                                            <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{{{ $field['name'] ?? 'unnamed' }}}}</code>
                                            <span class="ml-2 text-sm text-gray-600">
                                                {{ ucfirst($field['type'] ?? 'text') }}
                                                @if($field['required'] ?? false)
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </span>
                                        </div>
                                        @if($field['default_value'] ?? null)
                                            <span class="text-sm text-gray-500">Default: {{ $field['default_value'] }}</span>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Billing Configuration Tab -->
                    <div x-show="activeTab === 'billing'" class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-900">Billing Configuration</h3>
                        
                        <!-- Billing Model Overview -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-medium text-blue-900 mb-2">Billing Model: 
                                @switch($template->billing_model)
                                    @case('fixed') Fixed Price
                                    @break
                                    @case('per_asset') Per Asset/Device
                                    @break
                                    @case('per_contact') Per Contact/Seat
                                    @break
                                    @case('tiered') Tiered Pricing
                                    @break
                                    @case('hybrid') Hybrid Model
                                    @break
                                    @default {{ ucfirst($template->billing_model) }}
                                @endswitch
                            </h4>
                            <p class="text-sm text-blue-700">
                                @switch($template->billing_model)
                                    @case('fixed')
                                        This template uses traditional fixed-price billing with set monthly/annual fees.
                                        @break
                                    @case('per_asset')
                                        This template bills based on the number of managed devices (workstations, servers, etc.).
                                        @break
                                    @case('per_contact')
                                        This template bills based on the number of contacts/seats with portal access.
                                        @break
                                    @case('tiered')
                                        This template uses volume-based pricing with multiple tiers and thresholds.
                                        @break
                                    @case('hybrid')
                                        This template combines multiple billing models for complex billing scenarios.
                                        @break
                                @endswitch
                            </p>
                        </div>
                        
                        <!-- Asset Billing Rules -->
                        @if($template->supportsAssetBilling() && $template->asset_billing_rules)
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Asset Billing Rules</h4>
                                <div class="bg-white border border-gray-200 rounded-lg divide-y">
                                    @foreach($template->asset_billing_rules as $assetType => $rules)
                                        <div class="p-4 flex items-center justify-between">
                                            <div>
                                                <span class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $assetType)) }}</span>
                                                @if(isset($rules['services']))
                                                    <div class="text-sm text-gray-600 mt-1">
                                                        Services: {{ implode(', ', $rules['services']) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="text-right">
                                                <div class="font-medium text-gray-900">${{ number_format($rules['rate'] ?? 0, 2) }}/month</div>
                                                @if(isset($rules['setup_fee']) && $rules['setup_fee'] > 0)
                                                    <div class="text-sm text-gray-600">${{ number_format($rules['setup_fee'], 2) }} setup</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <!-- Contact Access Tiers -->
                        @if($template->supportsContactBilling() && $template->contact_access_tiers)
                            <div>
                                <h4 class="font-medium text-gray-900 mb-3">Contact Access Tiers</h4>
                                <div class="bg-white border border-gray-200 rounded-lg divide-y">
                                    @foreach($template->contact_access_tiers as $tier)
                                        <div class="p-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="font-medium text-gray-900">{{ $tier['name'] ?? 'Unnamed Tier' }}</span>
                                                <span class="font-medium text-gray-900">${{ number_format($tier['rate'] ?? 0, 2) }}/month</span>
                                            </div>
                                            @if($tier['permissions'] ?? null)
                                                <div class="text-sm text-gray-600">
                                                    Permissions: {{ $tier['permissions'] }}
                                                </div>
                                            @endif
                                            @if($tier['limits'] ?? null)
                                                <div class="text-sm text-gray-600">
                                                    Limits: {{ $tier['limits'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <!-- Pricing Calculator -->
                        @if($template->billing_model !== 'fixed')
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-3">Pricing Calculator</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    @if($template->supportsAssetBilling())
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Assets</label>
                                            <input type="number" x-model="calculatorInputs.assets" min="0" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </div>
                                    @endif
                                    @if($template->supportsContactBilling())
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Contacts</label>
                                            <input type="number" x-model="calculatorInputs.contacts" min="0" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </div>
                                    @endif
                                </div>
                                <div class="bg-white border border-gray-200 rounded-lg p-4">
                                    <div class="text-lg font-medium text-gray-900">
                                        Estimated Monthly Cost: <span x-text="'$' + calculateEstimate().toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Automation Tab -->
                    @if($template->automation_settings)
                    <div x-show="activeTab === 'automation'" class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-900">Automation Configuration</h3>
                        
                        <!-- Automation Overview -->
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <svg class="w-6 h-6 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <h4 class="font-medium text-purple-900">Automated Workflows</h4>
                            </div>
                            <p class="text-sm text-purple-700 mb-4">This template includes automated behaviors that trigger when contracts are activated or when new assets/contacts are added to clients.</p>
                        </div>
                        
                        <!-- Active Automation Rules -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @if($template->automation_settings['auto_assign_new_assets'] ?? false)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-green-800 font-medium text-sm">Asset Auto-Assignment</span>
                                    </div>
                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Active</span>
                                </div>
                                <p class="text-sm text-green-700">Automatically assigns new client assets to contracts using this template</p>
                            </div>
                            @endif
                            
                            @if($template->automation_settings['auto_assign_new_contacts'] ?? false)
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM9 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <span class="text-blue-800 font-medium text-sm">Contact Auto-Assignment</span>
                                    </div>
                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">Active</span>
                                </div>
                                <p class="text-sm text-blue-700">Automatically assigns new client contacts to contracts using this template</p>
                            </div>
                            @endif
                            
                            @if($template->automation_settings['auto_generate_invoices'] ?? false)
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-orange-800 font-medium text-sm">Auto-Invoice Generation</span>
                                    </div>
                                    <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full">Active</span>
                                </div>
                                <p class="text-sm text-orange-700">Automatically generates invoices based on calculated usage each month</p>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Automation Workflow -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Automation Workflow</h4>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                        <span class="text-xs font-medium text-blue-600">1</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-gray-900">Contract Activation</div>
                                        <div class="text-sm text-gray-600">When a contract using this template is activated, existing assets and contacts are automatically assigned based on the automation rules.</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                        <span class="text-xs font-medium text-green-600">2</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-gray-900">Ongoing Automation</div>
                                        <div class="text-sm text-gray-600">When new assets or contacts are added to clients with active contracts, they are automatically assigned and included in billing calculations.</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                        <span class="text-xs font-medium text-orange-600">3</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-gray-900">Monthly Processing</div>
                                        <div class="text-sm text-gray-600">At the end of each month, usage is calculated and invoices are generated automatically for contracts with this template.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Custom Formulas -->
                        @if($template->calculation_formulas)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Custom Calculation Formulas</h4>
                            <div class="bg-gray-800 text-gray-100 p-3 rounded font-mono text-sm overflow-x-auto">
                                <pre>{{ json_encode($template->calculation_formulas, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Template Stats -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Template Statistics</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Usage</span>
                        <span class="font-medium">{{ $template->usage_count }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Active Contracts</span>
                        <span class="font-medium">{{ $template->contracts()->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Last Used</span>
                        <span class="font-medium text-sm">{{ $template->last_usage_description }}</span>
                    </div>
                    @if($template->success_rate)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Success Rate</span>
                            <span class="font-medium">{{ number_format($template->success_rate, 1) }}%</span>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Recent Contracts -->
            @if($template->contracts()->count() > 0)
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Contracts</h3>
                    <div class="space-y-3">
                        @foreach($template->contracts()->latest()->take(5)->get() as $contract)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <div class="font-medium text-sm">{{ $contract->title }}</div>
                                    <div class="text-xs text-gray-600">{{ $contract->client->name ?? 'Unknown Client' }}</div>
                                </div>
                                <a href="{{ route('financial.contracts.show', $contract) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Template Info -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Template Information</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-600">Created:</span>
                        <span class="ml-2">{{ $template->created_at->format('M j, Y') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Updated:</span>
                        <span class="ml-2">{{ $template->updated_at->format('M j, Y') }}</span>
                    </div>
                    @if($template->creator)
                        <div>
                            <span class="text-gray-600">Created by:</span>
                            <span class="ml-2">{{ $template->creator->name }}</span>
                        </div>
                    @endif
                    @if($template->category)
                        <div>
                            <span class="text-gray-600">Category:</span>
                            <span class="ml-2">{{ $template->category }}</span>
                        </div>
                    @endif
                    <div>
                        <span class="text-gray-600">Version:</span>
                        <span class="ml-2">{{ $template->version }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function templateViewer(template) {
    return {
        activeTab: 'preview',
        template: template,
        variableFields: template.variable_fields || [],
        variableValues: {},
        previewContent: '',
        calculatorInputs: {
            assets: 1,
            contacts: 1
        },
        
        init() {
            // Initialize variable values with defaults
            this.variableFields.forEach(field => {
                this.variableValues[field.name] = field.default_value || '';
            });
            this.updatePreview();
        },
        
        updatePreview() {
            let content = this.template.template_content;
            
            // Replace variables with values
            Object.keys(this.variableValues).forEach(key => {
                const value = this.variableValues[key] || `{{${key}}}`;
                const regex = new RegExp(`{{${key}}}`, 'g');
                content = content.replace(regex, value);
            });
            
            // Convert line breaks to HTML
            content = content.replace(/\n/g, '<br>');
            
            this.previewContent = content;
        },
        
        calculateEstimate() {
            let total = 0;
            
            if (this.template.supports_asset_billing && this.template.asset_billing_rules) {
                // For simplicity, use the first asset rule rate
                const firstRule = Object.values(this.template.asset_billing_rules)[0];
                if (firstRule && firstRule.rate) {
                    total += firstRule.rate * this.calculatorInputs.assets;
                }
            }
            
            if (this.template.supports_contact_billing && this.template.contact_access_tiers) {
                // For simplicity, use the first tier rate
                const firstTier = this.template.contact_access_tiers[0];
                if (firstTier && firstTier.rate) {
                    total += firstTier.rate * this.calculatorInputs.contacts;
                }
            }
            
            return total;
        },
        
        duplicateTemplate() {
            // This would implement template duplication
            alert('Template duplication feature coming soon!');
        }
    }
}
</script>
@endsection