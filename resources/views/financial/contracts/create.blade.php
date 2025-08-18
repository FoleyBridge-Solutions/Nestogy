@extends('layouts.app')

@php
$activeDomain = 'financial';
$activeItem = 'contracts';
$breadcrumbs = [
    ['name' => 'Financial', 'route' => 'financial.contracts.index'],
    ['name' => 'Contracts', 'route' => 'financial.contracts.index'],
    ['name' => 'Create Contract', 'active' => true]
];
@endphp

@section('title', 'Create Contract')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800" x-data="contractCreator()">
    <!-- Modern Header -->
    <div class="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Create New Contract</h1>
                    <p class="mt-1 text-lg text-gray-600 dark:text-gray-300">Build intelligent contracts with templates, automation, and real-time collaboration</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- AI Assistant -->
                    <button class="inline-flex items-center px-4 py-2 border border-purple-300 dark:border-purple-600 rounded-lg text-sm font-medium text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-900/50 hover:bg-purple-100 dark:hover:bg-purple-800/50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        AI Assistant
                    </button>
                    
                    <!-- Save as Draft -->
                    <button @click="saveDraft()" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Save Draft
                    </button>
                    
                    <!-- Cancel -->
                    <a href="{{ route('financial.contracts.index') }}" 
                       class="inline-flex items-center px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Enhanced Progress Steps -->
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6">
                <!-- Progress Bar -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Progress</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="Math.round((currentStep / 4) * 100) + '% complete'"></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-2 rounded-full transition-all duration-500 ease-out" 
                             :style="`width: ${(currentStep / 4) * 100}%`"></div>
                    </div>
                </div>
                
                <!-- Step Navigation -->
                <div class="grid grid-cols-4 gap-1">
                    <!-- Step 1: Template Selection -->
                    <button @click="goToStep(1)" 
                            :disabled="!canGoToStep(1)"
                            class="relative p-4 rounded-lg border transition-all duration-200 hover:shadow-md"
                            :class="{
                                'bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-blue-200 dark:border-blue-600': currentStep === 1,
                                'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-600': currentStep > 1,
                                'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-600 cursor-not-allowed': !canGoToStep(1) && currentStep !== 1,
                                'hover:bg-gray-100 dark:hover:bg-gray-700': canGoToStep(1) && currentStep !== 1
                            }">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full mx-auto mb-3 transition-colors"
                             :class="{
                                 'bg-gradient-to-r from-blue-600 to-purple-600 text-white': currentStep === 1,
                                 'bg-green-500 text-white': currentStep > 1,
                                 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400': currentStep < 1
                             }">
                            <span x-show="currentStep <= 1" class="text-sm font-bold">1</span>
                            <svg x-show="currentStep > 1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-semibold" :class="currentStep === 1 ? 'text-blue-900 dark:text-blue-300' : currentStep > 1 ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">Template Selection</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Choose your foundation</p>
                        </div>
                        <div x-show="currentStep === 1" class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full"></div>
                    </button>
                    
                    <!-- Step 2: Basic Info -->
                    <button @click="goToStep(2)" 
                            :disabled="!canGoToStep(2)"
                            class="relative p-4 rounded-lg border transition-all duration-200 hover:shadow-md"
                            :class="{
                                'bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-blue-200 dark:border-blue-600': currentStep === 2,
                                'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-600': currentStep > 2,
                                'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-600 cursor-not-allowed': !canGoToStep(2) && currentStep !== 2,
                                'hover:bg-gray-100 dark:hover:bg-gray-700': canGoToStep(2) && currentStep !== 2
                            }">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full mx-auto mb-3 transition-colors"
                             :class="{
                                 'bg-gradient-to-r from-blue-600 to-purple-600 text-white': currentStep === 2,
                                 'bg-green-500 text-white': currentStep > 2,
                                 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400': currentStep < 2
                             }">
                            <span x-show="currentStep <= 2" class="text-sm font-bold">2</span>
                            <svg x-show="currentStep > 2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-semibold" :class="currentStep === 2 ? 'text-blue-900 dark:text-blue-300' : currentStep > 2 ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">Basic Information</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Contract essentials</p>
                        </div>
                        <div x-show="currentStep === 2" class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full"></div>
                    </button>
                    
                    <!-- Step 3: Configuration -->
                    <button @click="goToStep(3)" 
                            :disabled="!canGoToStep(3)"
                            class="relative p-4 rounded-lg border transition-all duration-200 hover:shadow-md"
                            :class="{
                                'bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-blue-200 dark:border-blue-600': currentStep === 3,
                                'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-600': currentStep > 3,
                                'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-600 cursor-not-allowed': !canGoToStep(3) && currentStep !== 3,
                                'hover:bg-gray-100 dark:hover:bg-gray-700': canGoToStep(3) && currentStep !== 3
                            }">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full mx-auto mb-3 transition-colors"
                             :class="{
                                 'bg-gradient-to-r from-blue-600 to-purple-600 text-white': currentStep === 3,
                                 'bg-green-500 text-white': currentStep > 3,
                                 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400': currentStep < 3
                             }">
                            <span x-show="currentStep <= 3" class="text-sm font-bold">3</span>
                            <svg x-show="currentStep > 3" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-semibold" :class="currentStep === 3 ? 'text-blue-900 dark:text-blue-300' : currentStep > 3 ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">Configuration</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Terms & automation</p>
                        </div>
                        <div x-show="currentStep === 3" class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full"></div>
                    </button>
                    
                    <!-- Step 4: Review -->
                    <button @click="goToStep(4)" 
                            :disabled="!canGoToStep(4)"
                            class="relative p-4 rounded-lg border transition-all duration-200 hover:shadow-md"
                            :class="{
                                'bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-blue-200 dark:border-blue-600': currentStep === 4,
                                'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-600': currentStep > 4,
                                'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-600 cursor-not-allowed': !canGoToStep(4) && currentStep !== 4,
                                'hover:bg-gray-100 dark:hover:bg-gray-700': canGoToStep(4) && currentStep !== 4
                            }">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full mx-auto mb-3 transition-colors"
                             :class="{
                                 'bg-gradient-to-r from-blue-600 to-purple-600 text-white': currentStep === 4,
                                 'bg-green-500 text-white': currentStep > 4,
                                 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400': currentStep < 4
                             }">
                            <span x-show="currentStep <= 4" class="text-sm font-bold">4</span>
                            <svg x-show="currentStep > 4" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="text-center">
                            <h4 class="text-sm font-semibold" :class="currentStep === 4 ? 'text-blue-900 dark:text-blue-300' : currentStep > 4 ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">Review & Create</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Final verification</p>
                        </div>
                        <div x-show="currentStep === 4" class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full"></div>
                    </button>
                </div>
                
                <!-- Smart Suggestions -->
                <div x-show="suggestions.length > 0" class="mt-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-700">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-purple-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-purple-900 dark:text-purple-300 mb-2">AI Suggestions</h4>
                            <ul class="text-sm text-purple-800 dark:text-purple-300 space-y-1">
                                <template x-for="suggestion in suggestions" :key="suggestion.id">
                                    <li class="flex items-center cursor-pointer hover:text-purple-900 dark:hover:text-purple-200" @click="applySuggestion(suggestion)">
                                        <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                        </svg>
                                        <span x-text="suggestion.text"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Smart Form Container -->
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <form action="{{ route('financial.contracts.store') }}" method="POST" @submit="prepareSubmission">
            @csrf
            
            <!-- Step 1: Template Selection & Basic Info -->
            <div x-show="currentStep === 1" x-transition class="space-y-6">
                <!-- Template Selection -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Choose a Template (Optional)</h3>
                    <p class="text-sm text-gray-600 mb-6">Select a pre-configured template to get started quickly, or create a custom contract from scratch.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        <!-- No Template Option -->
                        <div class="border-2 rounded-lg p-4 cursor-pointer transition-all"
                             :class="!selectedTemplate ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
                             @click="selectTemplate(null)">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-gray-900 dark:text-white">Custom Contract</h4>
                                <div class="w-4 h-4 rounded-full border-2 transition-colors"
                                     :class="!selectedTemplate ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                                    <div x-show="!selectedTemplate" class="w-2 h-2 bg-white dark:bg-gray-800 rounded-full mx-auto mt-0.5"></div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">Create a contract from scratch with your own terms and content</p>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">Manual Setup</span>
                            </div>
                        </div>

                        <!-- Template Options -->
                        @foreach($templates as $template)
                        <div class="border-2 rounded-lg p-4 cursor-pointer transition-all"
                             :class="selectedTemplate && selectedTemplate.id === {{ $template->id }} ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
                             @click="selectTemplate(@js($template))">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $template->name }}</h4>
                                <div class="w-4 h-4 rounded-full border-2 transition-colors"
                                     :class="selectedTemplate && selectedTemplate.id === {{ $template->id }} ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                                    <div x-show="selectedTemplate && selectedTemplate.id === {{ $template->id }}" 
                                         class="w-2 h-2 bg-white dark:bg-gray-800 rounded-full mx-auto mt-0.5"></div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">{{ $template->description ?: 'Professional contract template' }}</p>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-700">
                                    {{ ucfirst(str_replace('_', ' ', $template->template_type)) }}
                                </span>
                                @if($template->is_programmable)
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-purple-100 text-purple-700">
                                        Programmable
                                    </span>
                                @endif
                                @if($template->billing_model !== 'fixed')
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-700">
                                        @switch($template->billing_model)
                                            @case('per_asset') Per Device @break
                                            @case('per_contact') Per Seat @break
                                            @case('tiered') Tiered @break
                                            @case('hybrid') Hybrid @break
                                        @endswitch
                                    </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Template Info Display -->
                    <div x-show="selectedTemplate" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-medium text-blue-900 mb-2">Selected Template Features</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-blue-700">Billing Model:</span>
                                <span class="ml-2 text-blue-900" x-text="selectedTemplate ? getBillingModelLabel(selectedTemplate.billing_model) : ''"></span>
                            </div>
                            <div x-show="selectedTemplate && selectedTemplate.variable_fields && selectedTemplate.variable_fields.length > 0">
                                <span class="text-blue-700">Variable Fields:</span>
                                <span class="ml-2 text-blue-900" x-text="selectedTemplate && selectedTemplate.variable_fields ? selectedTemplate.variable_fields.length + ' fields' : '0 fields'"></span>
                            </div>
                            <div x-show="selectedTemplate && selectedTemplate.automation_settings">
                                <span class="text-blue-700">Automation:</span>
                                <span class="ml-2 text-blue-900" x-text="getAutomationLabel(selectedTemplate.automation_settings)"></span>
                            </div>
                        </div>
                        
                        <!-- Automation Features -->
                        <div x-show="selectedTemplate && selectedTemplate.automation_settings && hasAutomationFeatures(selectedTemplate.automation_settings)" class="mt-4 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                            <h5 class="font-medium text-purple-900 mb-2">🤖 Automation Features Enabled</h5>
                            <div class="text-sm text-purple-800 space-y-1">
                                <div x-show="selectedTemplate.automation_settings?.auto_assign_new_assets">
                                    ✓ Auto-assign new client assets to this contract
                                </div>
                                <div x-show="selectedTemplate.automation_settings?.auto_assign_new_contacts">
                                    ✓ Auto-assign new client contacts to this contract
                                </div>
                                <div x-show="selectedTemplate.automation_settings?.auto_generate_invoices">
                                    ✓ Auto-generate invoices based on usage calculations
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Contract Info -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Contract Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contract Title *</label>
                            <input type="text" name="title" x-model="form.title" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400"
                                   placeholder="e.g., IT Support Agreement - Company ABC">
                            @error('title')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>

                        <!-- Contract Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contract Type *</label>
                            <select name="contract_type" x-model="form.contract_type" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400">
                                <option value="">Select type...</option>
                                <option value="one_time_service">One-time Service</option>
                                <option value="recurring_service">Recurring Service</option>
                                <option value="maintenance">Maintenance Agreement</option>
                                <option value="support">Support Agreement</option>
                                <option value="managed_services">Managed Services</option>
                            </select>
                            @error('contract_type')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <!-- Client Selection -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client *</label>
                        <select name="client_id" x-model="form.client_id" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400">
                            <option value="">Select client...</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                        @error('client_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Description -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <textarea name="description" x-model="form.description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400"
                                  placeholder="Brief description of the contract scope and objectives..."></textarea>
                        @error('description')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <!-- Step 2: Contract Details -->
            <div x-show="currentStep === 2" x-transition class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contract Details</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Start Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date *</label>
                        <input type="date" name="start_date" x-model="form.start_date" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400">
                        @error('start_date')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- End Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                        <input type="date" name="end_date" x-model="form.end_date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave blank for open-ended contracts</p>
                        @error('end_date')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Contract Value (only for fixed billing) -->
                    <div x-show="!selectedTemplate || selectedTemplate.billing_model === 'fixed'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contract Value *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 sm:text-sm">$</span>
                            </div>
                            <input type="number" step="0.01" name="contract_value" x-model="form.contract_value" 
                                   :required="!selectedTemplate || selectedTemplate.billing_model === 'fixed'"
                                   class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0.00">
                        </div>
                        @error('contract_value')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                    
                    <!-- Usage-Based Billing Notice -->
                    <div x-show="selectedTemplate && selectedTemplate.billing_model !== 'fixed'" class="col-span-2">
                        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <div>
                                    <h4 class="font-medium text-purple-900 dark:text-purple-300">Usage-Based Billing</h4>
                                    <p class="text-sm text-purple-700 dark:text-purple-300">Contract value will be calculated automatically based on assigned assets and contacts. No fixed value needed.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Currency -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                        <select name="currency" x-model="form.currency"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400">
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="GBP">GBP - British Pound</option>
                            <option value="CAD">CAD - Canadian Dollar</option>
                        </select>
                        @error('currency')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>

                <!-- Payment Terms -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Terms</label>
                    <select name="payment_terms" x-model="form.payment_terms"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400">
                        <option value="">Select payment terms...</option>
                        <option value="net_15">Net 15 days</option>
                        <option value="net_30">Net 30 days</option>
                        <option value="net_45">Net 45 days</option>
                        <option value="net_60">Net 60 days</option>
                        <option value="due_on_receipt">Due on receipt</option>
                        <option value="advance_payment">Advance payment required</option>
                    </select>
                    @error('payment_terms')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                </div>
            </div>

            <!-- Step 3: Billing Configuration & Content -->
            <div x-show="currentStep === 3" x-transition class="space-y-6">
                <!-- Billing Model Selection (if using template) -->
                <div x-show="selectedTemplate && selectedTemplate.billing_model !== 'fixed'">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Billing Configuration</h3>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            This template uses <strong x-text="selectedTemplate ? getBillingModelLabel(selectedTemplate.billing_model) : ''"></strong> billing.
                            Configure the billing parameters below.
                        </p>
                    </div>

                    <!-- Asset Assignment (for per-asset billing) -->
                    <div x-show="selectedTemplate && ['per_asset', 'hybrid'].includes(selectedTemplate.billing_model)" class="mb-6">
                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Asset Assignment</h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Select which client assets will be covered under this contract</p>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="billingConfig.auto_assign_assets" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Automatically assign all current client assets</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="billingConfig.auto_assign_new_assets" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Automatically assign future client assets</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Assignment (for per-contact billing) -->
                    <div x-show="selectedTemplate && ['per_contact', 'hybrid'].includes(selectedTemplate.billing_model)" class="mb-6">
                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Contact Assignment</h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Select which client contacts will have portal access under this contract</p>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="billingConfig.auto_assign_contacts" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Automatically assign all current client contacts</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="billingConfig.auto_assign_new_contacts" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Automatically assign future client contacts</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Variable Fields (if using template with variables) -->
                <div x-show="selectedTemplate && selectedTemplate.variable_fields && selectedTemplate.variable_fields.length > 0">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Template Variables</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Fill in the variable values for this contract</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="field in (selectedTemplate ? selectedTemplate.variable_fields : [])" :key="field.name">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" 
                                       x-text="field.name.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) + (field.required ? ' *' : '')"></label>
                                <input :type="field.type === 'currency' ? 'number' : field.type"
                                       :step="field.type === 'currency' ? '0.01' : null"
                                       x-model="variableValues[field.name]"
                                       :placeholder="field.default_value || 'Enter ' + field.name"
                                       :required="field.required"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400">
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Contract Content -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contract Content</h3>
                    
                    <div x-show="selectedTemplate">
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-600 rounded-lg p-4 mb-4">
                            <p class="text-sm text-green-700 dark:text-green-300">
                                Content will be generated from the selected template with your variable values.
                                You can preview and edit the content after creating the contract.
                            </p>
                        </div>
                    </div>

                    <div x-show="!selectedTemplate">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contract Content *</label>
                        <textarea name="content" x-model="form.content" rows="12" 
                                  :required="!selectedTemplate"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                  placeholder="Enter your contract terms and conditions..."></textarea>
                        @error('content')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <!-- Step 4: Review -->
            <div x-show="currentStep === 4" x-transition class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Review Contract</h3>
                
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Contract Summary -->
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Contract Summary</h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-600 dark:text-gray-400">Title:</dt>
                                    <dd class="text-gray-900 dark:text-white" x-text="form.title"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-600 dark:text-gray-400">Type:</dt>
                                    <dd class="text-gray-900 dark:text-white" x-text="form.contract_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-600 dark:text-gray-400">Value:</dt>
                                    <dd class="text-gray-900 dark:text-white" x-text="'$' + parseFloat(form.contract_value || 0).toLocaleString()"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-600 dark:text-gray-400">Start Date:</dt>
                                    <dd class="text-gray-900 dark:text-white" x-text="form.start_date"></dd>
                                </div>
                                <div class="flex justify-between" x-show="form.end_date">
                                    <dt class="text-gray-600 dark:text-gray-400">End Date:</dt>
                                    <dd class="text-gray-900 dark:text-white" x-text="form.end_date"></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Template & Billing Info -->
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Template & Billing</h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-600 dark:text-gray-400">Template:</dt>
                                    <dd class="text-gray-900 dark:text-white" x-text="selectedTemplate ? selectedTemplate.name : 'Custom Contract'"></dd>
                                </div>
                                <div x-show="selectedTemplate" class="flex justify-between">
                                    <dt class="text-gray-600 dark:text-gray-400">Billing Model:</dt>
                                    <dd class="text-gray-900 dark:text-white" x-text="selectedTemplate ? getBillingModelLabel(selectedTemplate.billing_model) : ''"></dd>
                                </div>
                                <div x-show="selectedTemplate && selectedTemplate.variable_fields && selectedTemplate.variable_fields.length > 0" class="flex justify-between">
                                    <dt class="text-gray-600 dark:text-gray-400">Variables:</dt>
                                    <dd class="text-gray-900 dark:text-white" x-text="Object.keys(variableValues).length + ' configured'"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Final Review -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300">Ready to Create</h4>
                            <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                Review the information above and click "Create Contract" to proceed. 
                                You'll be able to edit the contract content and send it for approval after creation.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden form fields for complex data -->
            <input type="hidden" name="template_id" x-model="selectedTemplate ? selectedTemplate.id : ''">
            <input type="hidden" name="variable_values" x-model="variableValuesJson">
            <input type="hidden" name="billing_config" x-model="billingConfigJson">

                <!-- Enhanced Navigation -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 rounded-b-xl border-t border-gray-200 dark:border-gray-700 mt-6">
                    <div class="flex items-center justify-between">
                        <!-- Left: Back Button -->
                        <button type="button" @click="previousStep()" 
                                x-show="currentStep > 1"
                                class="inline-flex items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Back
                        </button>
                        
                        <!-- Center: Step Indicator -->
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Step <span x-text="currentStep"></span> of 4
                        </div>
                        
                        <!-- Right: Action Buttons -->
                        <div class="flex items-center space-x-3">
                            <!-- Save Draft -->
                            <button type="button" @click="saveDraft()" 
                                    class="inline-flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                Save Draft
                            </button>
                            
                            <!-- Next/Create Button -->
                            <button type="button" @click="nextStep()" 
                                    x-show="currentStep < 4"
                                    :disabled="!canProceed()"
                                    class="inline-flex items-center px-6 py-2 text-white rounded-lg transition-all duration-200 transform hover:scale-105"
                                    :class="canProceed() ? 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 shadow-lg' : 'bg-gray-400 cursor-not-allowed'">
                                Continue
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                            
                            <button type="submit" 
                                    x-show="currentStep === 4"
                                    :disabled="!canProceed()"
                                    class="inline-flex items-center px-8 py-2 text-white rounded-lg transition-all duration-200 transform hover:scale-105"
                                    :class="canProceed() ? 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 shadow-lg' : 'bg-gray-400 cursor-not-allowed'">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Create Contract
                            </button>
                        </div>
                    </div>
                    
                    <!-- Progress Details -->
                    <div class="mt-4 flex items-center justify-center space-x-6 text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex items-center">
                            <div class="w-2 h-2 rounded-full mr-2" :class="currentStep >= 1 ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"></div>
                            Template Selected
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 rounded-full mr-2" :class="currentStep >= 2 ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"></div>
                            Basic Info Complete
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 rounded-full mr-2" :class="currentStep >= 3 ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"></div>
                            Configuration Set
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 rounded-full mr-2" :class="currentStep >= 4 ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"></div>
                            Ready to Create
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function contractCreator() {
    return {
        currentStep: 1,
        selectedTemplate: null,
        suggestions: [],
        form: {
            title: '',
            contract_type: '',
            client_id: '',
            description: '',
            start_date: '',
            end_date: '',
            contract_value: '',
            currency: 'USD',
            payment_terms: '',
            content: ''
        },
        variableValues: {},
        billingConfig: {
            auto_assign_assets: false,
            auto_assign_new_assets: false,
            auto_assign_contacts: false,
            auto_assign_new_contacts: false
        },
        
        // Initialize
        init() {
            this.generateSuggestions();
            this.autoSave();
        },
        
        get variableValuesJson() {
            return JSON.stringify(this.variableValues);
        },
        
        get billingConfigJson() {
            return JSON.stringify(this.billingConfig);
        },
        
        selectTemplate(template) {
            this.selectedTemplate = template;
            
            // Initialize variable values if template has variables
            if (template && template.variable_fields) {
                this.variableValues = {};
                template.variable_fields.forEach(field => {
                    this.variableValues[field.name] = field.default_value || '';
                });
            }
        },
        
        getBillingModelLabel(model) {
            const labels = {
                'fixed': 'Fixed Price',
                'per_asset': 'Per Asset/Device',
                'per_contact': 'Per Contact/Seat',
                'tiered': 'Tiered Pricing',
                'hybrid': 'Hybrid Model'
            };
            return labels[model] || model;
        },
        
        getAutomationLabel(settings) {
            if (!settings) return 'None';
            
            const features = [];
            if (settings.auto_assign_new_assets) features.push('Assets');
            if (settings.auto_assign_new_contacts) features.push('Contacts');
            if (settings.auto_generate_invoices) features.push('Invoices');
            
            return features.length > 0 ? features.join(', ') : 'None';
        },
        
        hasAutomationFeatures(settings) {
            if (!settings) return false;
            return settings.auto_assign_new_assets || 
                   settings.auto_assign_new_contacts || 
                   settings.auto_generate_invoices;
        },
        
        // Enhanced navigation
        nextStep() {
            if (this.canProceed()) {
                this.currentStep++;
                this.generateSuggestions();
                this.saveProgress();
            }
        },
        
        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.generateSuggestions();
            }
        },
        
        goToStep(step) {
            if (this.canGoToStep(step)) {
                this.currentStep = step;
                this.generateSuggestions();
            }
        },
        
        canGoToStep(step) {
            // Allow going to previous completed steps or next step if current is valid
            if (step <= this.currentStep) return true;
            if (step === this.currentStep + 1) return this.canProceed();
            return false;
        },
        
        canProceed() {
            switch (this.currentStep) {
                case 1:
                    return this.form.title && this.form.contract_type && this.form.client_id;
                case 2:
                    // For programmable contracts, contract_value is not required
                    const requiresValue = !this.selectedTemplate || this.selectedTemplate.billing_model === 'fixed';
                    return this.form.start_date && (!requiresValue || this.form.contract_value);
                case 3:
                    // Check if template variables are filled if using template
                    if (this.selectedTemplate && this.selectedTemplate.variable_fields) {
                        const requiredFields = this.selectedTemplate.variable_fields.filter(f => f.required);
                        return requiredFields.every(field => this.variableValues[field.name]);
                    }
                    // Check if content is provided if not using template
                    if (!this.selectedTemplate) {
                        return this.form.content;
                    }
                    return true;
                case 4:
                    return true;
                default:
                    return true;
            }
        },
        
        // AI and Smart Features
        generateSuggestions() {
            this.suggestions = [];
            
            switch (this.currentStep) {
                case 1:
                    if (!this.selectedTemplate && this.form.client_id) {
                        this.suggestions.push({
                            id: 'template_suggestion',
                            text: 'Consider using a Managed Services template for this client type',
                            action: () => this.suggestTemplate('managed_services')
                        });
                    }
                    break;
                case 2:
                    if (this.form.title && !this.form.start_date) {
                        this.suggestions.push({
                            id: 'start_date',
                            text: 'Set start date to next Monday for standard deployment',
                            action: () => this.setStartDate(this.getNextMonday())
                        });
                    }
                    break;
                case 3:
                    if (this.selectedTemplate && this.selectedTemplate.billing_model !== 'fixed') {
                        this.suggestions.push({
                            id: 'automation',
                            text: 'Enable auto-assignment for new client assets',
                            action: () => this.billingConfig.auto_assign_new_assets = true
                        });
                    }
                    break;
            }
        },
        
        applySuggestion(suggestion) {
            if (suggestion.action) {
                suggestion.action();
                this.suggestions = this.suggestions.filter(s => s.id !== suggestion.id);
            }
        },
        
        suggestTemplate(templateType) {
            // In real implementation, this would select the appropriate template
            console.log('Suggesting template:', templateType);
        },
        
        setStartDate(date) {
            this.form.start_date = date;
        },
        
        getNextMonday() {
            const today = new Date();
            const nextMonday = new Date(today);
            nextMonday.setDate(today.getDate() + ((1 + 7 - today.getDay()) % 7));
            return nextMonday.toISOString().split('T')[0];
        },
        
        // Auto-save functionality
        saveProgress() {
            const progressData = {
                currentStep: this.currentStep,
                selectedTemplate: this.selectedTemplate,
                form: this.form,
                variableValues: this.variableValues,
                billingConfig: this.billingConfig
            };
            localStorage.setItem('contract_creation_progress', JSON.stringify(progressData));
        },
        
        loadProgress() {
            const saved = localStorage.getItem('contract_creation_progress');
            if (saved) {
                const data = JSON.parse(saved);
                this.currentStep = data.currentStep || 1;
                this.selectedTemplate = data.selectedTemplate;
                this.form = { ...this.form, ...data.form };
                this.variableValues = data.variableValues || {};
                this.billingConfig = { ...this.billingConfig, ...data.billingConfig };
            }
        },
        
        saveDraft() {
            // Save as draft functionality
            const draftData = {
                ...this.form,
                template_id: this.selectedTemplate?.id,
                variable_values: this.variableValues,
                billing_config: this.billingConfig,
                status: 'draft'
            };
            
            fetch('/api/contracts/draft', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(draftData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification('Draft saved successfully!', 'success');
                    // Clear auto-save data
                    localStorage.removeItem('contract_creation_progress');
                } else {
                    this.showNotification('Error saving draft: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('Error saving draft', 'error');
            });
        },
        
        autoSave() {
            // Auto-save every 30 seconds
            setInterval(() => {
                if (this.hasChanges()) {
                    this.saveProgress();
                }
            }, 30000);
        },
        
        hasChanges() {
            return this.form.title || this.form.client_id || this.selectedTemplate;
        },
        
        showNotification(message, type = 'info') {
            // Create and show a notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 5000);
        },
        
        prepareSubmission(event) {
            // Clear auto-save data on successful submission
            localStorage.removeItem('contract_creation_progress');
            // All data is bound via x-model, form will submit with complete data
        }
    }
}
</script>
@endsection