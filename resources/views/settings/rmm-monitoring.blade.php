@extends('layouts.settings')

@section('title', 'RMM & Monitoring Settings - Nestogy')

@section('settings-title', 'RMM & Monitoring Settings')
@section('settings-description', 'Configure remote monitoring and management integrations')

@section('settings-content')
<div x-data="rmmSettings">
    <form method="POST" action="{{ route('settings.rmm-monitoring.update') }}">
        @csrf
        @method('PUT')
        
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'general'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'general', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'general'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    General Settings
                </button>
                <button type="button" 
                        @click="activeTab = 'integrations'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'integrations', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'integrations'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Integrations
                </button>
                <button type="button" 
                        @click="activeTab = 'alerts'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'alerts', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'alerts'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Alerts & Thresholds
                </button>
                <button type="button" 
                        @click="activeTab = 'automation'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'automation', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'automation'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Automation
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- General Settings Tab -->
            <div x-show="activeTab === 'general'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">General RMM Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- RMM Enabled -->
                            <div class="col-span-12-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="rmm_enabled" 
                                           value="1"
                                           {{ old('rmm_enabled', $setting->rmm_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Enable RMM & Monitoring Features</span>
                                </label>
                            </div>

                            <!-- Default Check Interval -->
                            <div>
                                <label for="default_check_interval" class="block text-sm font-medium text-gray-700 mb-1">
                                    Default Check Interval (minutes)
                                </label>
                                <input type="number" 
                                       id="default_check_interval"
                                       name="default_check_interval"
                                       value="{{ old('default_check_interval', $setting->default_check_interval ?? 5) }}"
                                       min="1"
                                       max="60"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Data Retention Days -->
                            <div>
                                <label for="monitoring_data_retention_days" class="block text-sm font-medium text-gray-700 mb-1">
                                    Data Retention (days)
                                </label>
                                <input type="number" 
                                       id="monitoring_data_retention_days"
                                       name="monitoring_data_retention_days"
                                       value="{{ old('monitoring_data_retention_days', $setting->monitoring_data_retention_days ?? 90) }}"
                                       min="7"
                                       max="365"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Auto Discovery -->
                            <div class="col-span-12-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="auto_discovery_enabled" 
                                           value="1"
                                           {{ old('auto_discovery_enabled', $setting->auto_discovery_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Enable Automatic Device Discovery</span>
                                </label>
                            </div>

                            <!-- Agent Auto-Update -->
                            <div class="col-span-12-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="agent_auto_update" 
                                           value="1"
                                           {{ old('agent_auto_update', $setting->agent_auto_update ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Enable Automatic Agent Updates</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrations Tab -->
            <div x-show="activeTab === 'integrations'" x-transition>
                <div class="space-y-6">
                    <!-- ConnectWise Integration -->
                    <div class="border rounded-lg p-4">
                        <h4 class="text-md font-medium text-gray-900 mb-3">ConnectWise Automate</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-12-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="connectwise_enabled" 
                                           value="1"
                                           {{ old('connectwise_enabled', $setting->connectwise_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Enable ConnectWise Integration</span>
                                </label>
                            </div>
                            <div>
                                <label for="connectwise_url" class="block text-sm font-medium text-gray-700 mb-1">
                                    ConnectWise URL
                                </label>
                                <input type="url" 
                                       id="connectwise_url"
                                       name="connectwise_url"
                                       value="{{ old('connectwise_url', $setting->connectwise_url ?? '') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="https://your-instance.connectwise.com">
                            </div>
                            <div>
                                <label for="connectwise_api_key" class="block text-sm font-medium text-gray-700 mb-1">
                                    API Key
                                </label>
                                <input type="password" 
                                       id="connectwise_api_key"
                                       name="connectwise_api_key"
                                       value="{{ old('connectwise_api_key', $setting->connectwise_api_key ?? '') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- N-able Integration -->
                    <div class="border rounded-lg p-4">
                        <h4 class="text-md font-medium text-gray-900 mb-3">N-able N-central</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-12-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="nable_enabled" 
                                           value="1"
                                           {{ old('nable_enabled', $setting->nable_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Enable N-able Integration</span>
                                </label>
                            </div>
                            <div>
                                <label for="nable_server" class="block text-sm font-medium text-gray-700 mb-1">
                                    N-central Server
                                </label>
                                <input type="text" 
                                       id="nable_server"
                                       name="nable_server"
                                       value="{{ old('nable_server', $setting->nable_server ?? '') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="your-server.n-able.com">
                            </div>
                            <div>
                                <label for="nable_api_key" class="block text-sm font-medium text-gray-700 mb-1">
                                    API Key
                                </label>
                                <input type="password" 
                                       id="nable_api_key"
                                       name="nable_api_key"
                                       value="{{ old('nable_api_key', $setting->nable_api_key ?? '') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Datto RMM Integration -->
                    <div class="border rounded-lg p-4">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Datto RMM</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-12-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="datto_rmm_enabled" 
                                           value="1"
                                           {{ old('datto_rmm_enabled', $setting->datto_rmm_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Enable Datto RMM Integration</span>
                                </label>
                            </div>
                            <div>
                                <label for="datto_api_url" class="block text-sm font-medium text-gray-700 mb-1">
                                    API URL
                                </label>
                                <input type="url" 
                                       id="datto_api_url"
                                       name="datto_api_url"
                                       value="{{ old('datto_api_url', $setting->datto_api_url ?? '') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="https://api.dattormm.com">
                            </div>
                            <div>
                                <label for="datto_api_key" class="block text-sm font-medium text-gray-700 mb-1">
                                    API Key
                                </label>
                                <input type="password" 
                                       id="datto_api_key"
                                       name="datto_api_key"
                                       value="{{ old('datto_api_key', $setting->datto_api_key ?? '') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Tactical RMM Integration -->
                    <livewire:tactical-rmm-integration />
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0-1.125-.504-1.125-1.125V11.25a9 9 0 00-9-9z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-white">Tactical RMM</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Open source RMM for IT service providers</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       id="rmm_tactical_enabled" 
                                       x-model="enabled"
                                       @change="toggleIntegration()"
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Configuration Form (shown when enabled) -->
                        <div x-show="enabled" x-transition class="space-y-4">
                            <!-- Connection Status -->
                            <div x-show="connectionStatus" class="p-3 rounded-md" :class="connectionStatus === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg x-show="connectionStatus === 'success'" class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <svg x-show="connectionStatus === 'error'" class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium" :class="connectionStatus === 'success' ? 'text-green-800' : 'text-red-800'" x-text="connectionMessage"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- API URL -->
                            <div>
                                <label for="rmm_tactical_api_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    API URL <span class="text-red-500">*</span>
                                </label>
                                <input type="url" 
                                       id="rmm_tactical_api_url" 
                                       x-model="apiUrl"
                                       :placeholder="integrationSaved ? 'API URL is configured (enter new URL to change)' : 'https://your-tactical-rmm.example.com'"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="!integrationSaved">Enter the full URL to your Tactical RMM server</p>
                                <p class="mt-1 text-xs text-green-600" x-show="integrationSaved">
                                    <svg class="inline w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    API URL configured and encrypted
                                </p>
                            </div>

                            <!-- API Key -->
                            <div>
                                <label for="rmm_tactical_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    API Key <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showApiKey ? 'text' : 'password'" 
                                           id="rmm_tactical_api_key" 
                                           x-model="apiKey"
                                           :placeholder="integrationSaved ? 'API Key is configured (enter new key to change)' : 'Enter your Tactical RMM API key'"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pr-10">
                                    <button type="button" 
                                            @click="showApiKey = !showApiKey"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg x-show="!showApiKey" class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <svg x-show="showApiKey" class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.464 8.464m1.414 1.414L19.07 19.07"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="!integrationSaved">Generate an API key in your Tactical RMM admin panel</p>
                                <p class="mt-1 text-xs text-green-600" x-show="integrationSaved">
                                    <svg class="inline w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    API Key configured and encrypted
                                </p>
                            </div>

                            <!-- Integration Name -->
                            <div>
                                <label for="rmm_tactical_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Integration Name
                                </label>
                                <input type="text" 
                                       id="rmm_tactical_name" 
                                       x-model="integrationName"
                                       placeholder="Tactical RMM Integration"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Friendly name for this integration</p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex space-x-3 pt-4">
                                <button type="button" 
                                        @click="testConnection()"
                                        :disabled="testing || !apiUrl || !apiKey"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="!testing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <svg x-show="testing" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="testing ? 'Testing...' : 'Test Connection'"></span>
                                </button>

                                <button type="button" 
                                        @click="saveIntegration()"
                                        :disabled="saving || !apiUrl || !apiKey"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="!saving" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                    <svg x-show="saving" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="saving ? 'Saving...' : 'Save Integration'"></span>
                                </button>
                            </div>

                            <!-- Sync Options (shown when integration is saved) -->
                            <div x-show="integrationSaved" x-transition class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Client Mapping & Synchronization</h5>
                                
                                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-800">
                                                Before syncing agents, you must map your Nestogy clients to RMM clients to ensure proper data association.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex space-x-3">
                                    <button type="button" 
                                            @click="openClientMappingModal()"
                                            class="inline-flex items-center px-3 py-2 border border-blue-300 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                        </svg>
                                        Manage Client Mapping
                                    </button>

                                    <button type="button" 
                                            @click="syncAgents()"
                                            :disabled="syncing"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                        Sync Agents
                                    </button>

                                    <button type="button" 
                                            @click="syncAlerts()"
                                            :disabled="syncing"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        Sync Alerts
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client Mapping Modal -->
                    <div x-show="clientMappingModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" 
                         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <!-- Modal backdrop -->
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeClientMappingModal()"></div>
                            
                            <!-- Modal -->
                            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                                <!-- Modal Header -->
                                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4">
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Client Mapping Management</h3>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                Map your Nestogy clients to RMM clients to ensure proper agent association during synchronization.
                                            </p>
                                        </div>
                                        <button type="button" @click="closeClientMappingModal()" class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Modal Content -->
                                <div class="bg-white dark:bg-gray-800 px-4 pb-4 sm:p-6">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <!-- Nestogy Clients -->
                                        <div>
                                            <div class="flex items-center justify-between mb-4">
                                                <h4 class="text-md font-medium text-gray-900 dark:text-white">Nestogy Clients</h4>
                                                <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`${nestogyClients.length} clients`"></span>
                                            </div>
                                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg max-h-96 overflow-y-auto">
                                                <template x-for="client in nestogyClients" :key="client.id">
                                                    <div class="p-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                                         @click="selectNestogyClient(client)"
                                                         :class="selectedNestogyClient?.id === client.id ? 'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700' : ''">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="client.display_name"></div>
                                                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="client.name"></div>
                                                            </div>
                                                            <div class="flex flex-col items-end text-xs">
                                                                <span x-show="client.existing_mapping" 
                                                                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 mb-1">
                                                                    Mapped
                                                                </span>
                                                                <span x-show="!client.existing_mapping" 
                                                                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 mb-1">
                                                                    Unmapped
                                                                </span>
                                                                <span x-show="client.existing_mapping" class="text-gray-400 dark:text-gray-500" x-text="client.existing_mapping?.rmm_client_name"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- RMM Clients -->
                                        <div>
                                            <div class="flex items-center justify-between mb-4">
                                                <h4 class="text-md font-medium text-gray-900 dark:text-white">RMM Clients</h4>
                                                <div class="flex items-center space-x-2">
                                                    <button type="button" 
                                                            @click="fetchRmmClients()" 
                                                            :disabled="loadingRmmClients"
                                                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 disabled:opacity-50">
                                                        <svg class="inline w-4 h-4 mr-1" :class="loadingRmmClients ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                        Refresh
                                                    </button>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`${rmmClients.length} clients`"></span>
                                                </div>
                                            </div>
                                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg max-h-96 overflow-y-auto">
                                                <div x-show="loadingRmmClients" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                                    <svg class="animate-spin w-6 h-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Loading RMM clients...
                                                </div>
                                                <template x-for="client in rmmClients" :key="client.id">
                                                    <div class="p-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                                         @click="selectRmmClient(client)"
                                                         :class="selectedRmmClient?.id === client.id ? 'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700' : ''">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="client.name"></div>
                                                                <div class="text-xs text-gray-500 dark:text-gray-400">ID: <span x-text="client.id"></span></div>
                                                            </div>
                                                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                                                <span x-show="client.is_mapped" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                                    Mapped
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <div x-show="rmmClients.length === 0 && !loadingRmmClients" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                                    No RMM clients found. Click refresh to reload.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mapping Actions -->
                                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                                        <div class="flex items-center justify-between">
                                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                                <span x-show="selectedNestogyClient && selectedRmmClient">
                                                    Create mapping: <strong x-text="selectedNestogyClient?.display_name"></strong> 
                                                    → <strong x-text="selectedRmmClient?.name"></strong>
                                                </span>
                                                <span x-show="!selectedNestogyClient || !selectedRmmClient" class="text-gray-400 dark:text-gray-500">
                                                    Select a Nestogy client and RMM client to create a mapping
                                                </span>
                                            </div>
                                            <div class="flex space-x-3">
                                                <button type="button" 
                                                        @click="createMapping()"
                                                        :disabled="!selectedNestogyClient || !selectedRmmClient || creatingMapping"
                                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <svg x-show="!creatingMapping" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                    </svg>
                                                    <svg x-show="creatingMapping" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span x-text="creatingMapping ? 'Creating...' : 'Create Mapping'"></span>
                                                </button>
                                                <button type="button" 
                                                        @click="removeMapping()"
                                                        x-show="selectedNestogyClient?.existing_mapping"
                                                        :disabled="removingMapping"
                                                        class="inline-flex items-center px-3 py-2 border border-red-300 text-sm leading-4 font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                                                    <svg x-show="!removingMapping" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    <svg x-show="removingMapping" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span x-text="removingMapping ? 'Removing...' : 'Remove Mapping'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Footer -->
                                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="button" 
                                            @click="closeClientMappingModal()"
                                            class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts & Thresholds Tab -->
            <div x-show="activeTab === 'alerts'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Alert Thresholds</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- CPU Threshold -->
                            <div>
                                <label for="cpu_alert_threshold" class="block text-sm font-medium text-gray-700 mb-1">
                                    CPU Usage Alert (%)
                                </label>
                                <input type="number" 
                                       id="cpu_alert_threshold"
                                       name="cpu_alert_threshold"
                                       value="{{ old('cpu_alert_threshold', $setting->cpu_alert_threshold ?? 80) }}"
                                       min="50"
                                       max="100"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Memory Threshold -->
                            <div>
                                <label for="memory_alert_threshold" class="block text-sm font-medium text-gray-700 mb-1">
                                    Memory Usage Alert (%)
                                </label>
                                <input type="number" 
                                       id="memory_alert_threshold"
                                       name="memory_alert_threshold"
                                       value="{{ old('memory_alert_threshold', $setting->memory_alert_threshold ?? 85) }}"
                                       min="50"
                                       max="100"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Disk Threshold -->
                            <div>
                                <label for="disk_alert_threshold" class="block text-sm font-medium text-gray-700 mb-1">
                                    Disk Usage Alert (%)
                                </label>
                                <input type="number" 
                                       id="disk_alert_threshold"
                                       name="disk_alert_threshold"
                                       value="{{ old('disk_alert_threshold', $setting->disk_alert_threshold ?? 90) }}"
                                       min="50"
                                       max="100"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Response Time Threshold -->
                            <div>
                                <label for="response_time_threshold" class="block text-sm font-medium text-gray-700 mb-1">
                                    Response Time Alert (ms)
                                </label>
                                <input type="number" 
                                       id="response_time_threshold"
                                       name="response_time_threshold"
                                       value="{{ old('response_time_threshold', $setting->response_time_threshold ?? 1000) }}"
                                       min="100"
                                       max="10000"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Alert Cooldown -->
                            <div>
                                <label for="alert_cooldown_minutes" class="block text-sm font-medium text-gray-700 mb-1">
                                    Alert Cooldown (minutes)
                                </label>
                                <input type="number" 
                                       id="alert_cooldown_minutes"
                                       name="alert_cooldown_minutes"
                                       value="{{ old('alert_cooldown_minutes', $setting->alert_cooldown_minutes ?? 15) }}"
                                       min="5"
                                       max="60"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Max Alerts Per Hour -->
                            <div>
                                <label for="max_alerts_per_hour" class="block text-sm font-medium text-gray-700 mb-1">
                                    Max Alerts Per Hour
                                </label>
                                <input type="number" 
                                       id="max_alerts_per_hour"
                                       name="max_alerts_per_hour"
                                       value="{{ old('max_alerts_per_hour', $setting->max_alerts_per_hour ?? 10) }}"
                                       min="1"
                                       max="100"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Automation Tab -->
            <div x-show="activeTab === 'automation'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Automation Settings</h3>
                        <div class="space-y-4">
                            <!-- Auto-Remediation -->
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="auto_remediation_enabled" 
                                           value="1"
                                           {{ old('auto_remediation_enabled', $setting->auto_remediation_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Enable Automatic Remediation</span>
                                </label>
                            </div>

                            <!-- Auto-Restart Services -->
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="auto_restart_services" 
                                           value="1"
                                           {{ old('auto_restart_services', $setting->auto_restart_services ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Auto-Restart Failed Services</span>
                                </label>
                            </div>

                            <!-- Auto-Clear Temp Files -->
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="auto_clear_temp_files" 
                                           value="1"
                                           {{ old('auto_clear_temp_files', $setting->auto_clear_temp_files ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Auto-Clear Temporary Files When Disk Full</span>
                                </label>
                            </div>

                            <!-- Auto-Update Patches -->
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="auto_update_patches" 
                                           value="1"
                                           {{ old('auto_update_patches', $setting->auto_update_patches ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Auto-Install Critical Security Patches</span>
                                </label>
                            </div>

                            <!-- Script Execution -->
                            <div class="mt-6">
                                <label for="custom_scripts_enabled" class="flex items-center">
                                    <input type="checkbox" 
                                           id="custom_scripts_enabled"
                                           name="custom_scripts_enabled" 
                                           value="1"
                                           {{ old('custom_scripts_enabled', $setting->custom_scripts_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Enable Custom Script Execution</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200">
            <a href="{{ route('settings.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rmmSettings', () => ({
        activeTab: 'general'
    }));

    // Tactical RMM Integration Alpine.js Component
    Alpine.data('tacticalRmmIntegration', () => ({
        enabled: false,
        apiUrl: '',
        apiKey: '',
        integrationName: 'Tactical RMM Integration',
        showApiKey: false,
        testing: false,
        saving: false,
        syncing: false,
        connectionStatus: null,
        connectionMessage: '',
        integrationSaved: false,
        clientMappingModal: false,
        // Client mapping properties
        nestogyClients: [],
        rmmClients: [],
        selectedNestogyClient: null,
        selectedRmmClient: null,
        loadingNestogyClients: false,
        loadingRmmClients: false,
        creatingMapping: false,
        removingMapping: false,

        async init() {
            // Load existing integration data
            await this.loadExistingIntegration();
        },

        async loadExistingIntegration() {
            try {
                const response = await fetch('/api/rmm/integrations', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.integrations && data.integrations.length > 0) {
                        const integration = data.integrations[0];
                        this.enabled = integration.is_active;
                        this.integrationName = integration.name;
                        this.integrationSaved = true;
                        
                        // Show placeholders for saved credentials (don't expose actual values)
                        if (integration.has_api_url) {
                            this.apiUrl = '';
                        }
                        if (integration.has_api_key) {
                            this.apiKey = '';
                        }
                    }
                }
            } catch (error) {
                console.error('Failed to load existing integration:', error);
            }
        },

        toggleIntegration() {
            if (!this.enabled) {
                this.integrationSaved = false;
                this.connectionStatus = null;
            }
        },

        async testConnection() {
            this.testing = true;
            this.connectionStatus = null;

            try {
                let response;
                
                // Check if we're using saved credentials (placeholder values)
                const usingSavedCredentials = this.integrationSaved && (!this.apiUrl || !this.apiKey);

                if (usingSavedCredentials) {
                    // Test with existing saved integration
                    console.log('Testing connection with saved credentials');
                    
                    // First get the integration ID
                    const integrationsResponse = await fetch('/api/rmm/integrations', {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    
                    const integrationsData = await integrationsResponse.json();
                    if (!integrationsData.integrations || integrationsData.integrations.length === 0) {
                        throw new Error('No saved integration found');
                    }
                    
                    const integrationId = integrationsData.integrations[0].id;
                    
                    response = await fetch(`/api/rmm/integrations/${integrationId}/test-connection`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                } else {
                    // Test with new credentials
                    if (!this.apiUrl || !this.apiKey) {
                        showNotification('Please enter both API URL and API Key', 'warning');
                        return;
                    }
                    
                    console.log('Testing connection with new credentials');
                    
                    const requestData = {
                        api_url: this.apiUrl,
                        api_key: this.apiKey,
                        rmm_type: 'TRMM'
                    };
                    
                    response = await fetch('/api/rmm/test-connection', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(requestData)
                    });
                }

                const data = await response.json();

                console.log('Response:', data);

                if (data.success) {
                    this.connectionStatus = 'success';
                    this.connectionMessage = data.message || 'Connection successful!';
                } else {
                    this.connectionStatus = 'error';
                    this.connectionMessage = data.message || 'Connection failed';
                    
                    // Log validation errors if they exist
                    if (data.errors) {
                        console.error('Validation errors:', data.errors);
                    }
                }
            } catch (error) {
                this.connectionStatus = 'error';
                this.connectionMessage = 'Failed to test connection: ' + error.message;
            } finally {
                this.testing = false;
            }
        },

        async saveIntegration() {
            if (!this.apiUrl || !this.apiKey) {
                showNotification('Please enter both API URL and API Key', 'warning');
                return;
            }

            this.saving = true;

            try {
                const response = await fetch('/api/rmm/integrations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        rmm_type: 'TRMM',
                        name: this.integrationName,
                        api_url: this.apiUrl,
                        api_key: this.apiKey,
                        is_active: this.enabled
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.integrationSaved = true;
                    this.connectionStatus = 'success';
                    this.connectionMessage = 'Integration saved successfully!';
                    
                    // Show success message
                    this.showMessage('RMM Integration saved successfully!', 'success');
                } else {
                    this.connectionStatus = 'error';
                    this.connectionMessage = data.message || 'Failed to save integration';
                }
            } catch (error) {
                this.connectionStatus = 'error';
                this.connectionMessage = 'Failed to save integration: ' + error.message;
            } finally {
                this.saving = false;
            }
        },

        async syncAgents() {
            if (!this.integrationSaved) return;

            this.syncing = true;

            try {
                const response = await fetch('/api/rmm/sync-agents', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showMessage('Agent sync job queued successfully!', 'success');
                } else {
                    this.showMessage('Failed to sync agents: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                this.showMessage('Failed to sync agents: ' + error.message, 'error');
            } finally {
                this.syncing = false;
            }
        },

        async syncAlerts() {
            if (!this.integrationSaved) return;

            this.syncing = true;

            try {
                const response = await fetch('/api/rmm/sync-alerts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showMessage('Alert sync job queued successfully!', 'success');
                } else {
                    this.showMessage('Failed to sync alerts: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                this.showMessage('Failed to sync alerts: ' + error.message, 'error');
            } finally {
                this.syncing = false;
            }
        },

        async openClientMappingModal() {
            console.log('Opening client mapping modal...');
            this.clientMappingModal = true;
            // Load client data when modal opens
            await this.fetchNestogyClients();
            await this.fetchRmmClients();
        },

        closeClientMappingModal() {
            this.clientMappingModal = false;
        },

        async fetchNestogyClients() {
            this.loadingNestogyClients = true;
            try {
                const response = await fetch('/api/rmm/clients/nestogy', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    // Transform the client data to include existing_mapping property
                    this.nestogyClients = (data.clients || []).map(client => {
                        // Check if client has any active mappings
                        const mapping = client.rmm_client_mappings && client.rmm_client_mappings.length > 0 
                            ? client.rmm_client_mappings[0] // Take the first active mapping
                            : null;
                        
                        return {
                            ...client,
                            display_name: client.company_name || client.name,
                            existing_mapping: mapping ? {
                                rmm_client_id: mapping.rmm_client_id,
                                rmm_client_name: mapping.rmm_client_name
                            } : null
                        };
                    });
                } else {
                    console.error('Failed to fetch Nestogy clients');
                }
            } catch (error) {
                console.error('Error fetching Nestogy clients:', error);
            } finally {
                this.loadingNestogyClients = false;
            }
        },

        async fetchRmmClients() {
            this.loadingRmmClients = true;
            try {
                const response = await fetch('/api/rmm/clients/rmm', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    // Mark RMM clients as mapped based on existing mappings
                    this.rmmClients = (data.clients || []).map(rmmClient => {
                        const isMapped = this.nestogyClients.some(nestogyClient => 
                            nestogyClient.existing_mapping?.rmm_client_id === rmmClient.id.toString()
                        );
                        return {
                            ...rmmClient,
                            is_mapped: isMapped
                        };
                    });
                } else {
                    console.error('Failed to fetch RMM clients');
                }
            } catch (error) {
                console.error('Error fetching RMM clients:', error);
            } finally {
                this.loadingRmmClients = false;
            }
        },

        selectNestogyClient(client) {
            this.selectedNestogyClient = client;
            this.selectedRmmClient = null; // Clear RMM selection when changing Nestogy client
        },

        selectRmmClient(client) {
            this.selectedRmmClient = client;
        },

        async createMapping() {
            if (!this.selectedNestogyClient || !this.selectedRmmClient) return;

            this.creatingMapping = true;

            try {
                const response = await fetch('/api/rmm/client-mappings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        client_id: this.selectedNestogyClient.id,
                        rmm_client_id: String(this.selectedRmmClient.id),
                        rmm_client_name: this.selectedRmmClient.name
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Update the client with the new mapping
                    const clientIndex = this.nestogyClients.findIndex(c => c.id === this.selectedNestogyClient.id);
                    if (clientIndex !== -1) {
                        this.nestogyClients[clientIndex].existing_mapping = {
                            rmm_client_id: this.selectedRmmClient.id,
                            rmm_client_name: this.selectedRmmClient.name
                        };
                    }

                    // Update RMM client as mapped
                    const rmmClientIndex = this.rmmClients.findIndex(c => c.id === this.selectedRmmClient.id);
                    if (rmmClientIndex !== -1) {
                        this.rmmClients[rmmClientIndex].is_mapped = true;
                    }

                    // Clear selections
                    this.selectedNestogyClient = null;
                    this.selectedRmmClient = null;

                    // Show success message
                    this.showMessage('Client mapping created successfully!', 'success');
                } else {
                    this.showMessage('Failed to create mapping: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                this.showMessage('Failed to create mapping: ' + error.message, 'error');
            } finally {
                this.creatingMapping = false;
            }
        },

        async removeMapping() {
            if (!this.selectedNestogyClient?.existing_mapping) return;

            this.removingMapping = true;

            try {
                // Use client_id since we're deleting by client ID, not mapping ID
                const response = await fetch(`/api/rmm/client-mappings/${this.selectedNestogyClient.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Remove mapping from Nestogy client
                    const clientIndex = this.nestogyClients.findIndex(c => c.id === this.selectedNestogyClient.id);
                    if (clientIndex !== -1) {
                        this.nestogyClients[clientIndex].existing_mapping = null;
                    }

                    // Update RMM client as unmapped
                    const rmmClientIndex = this.rmmClients.findIndex(c => c.id === this.selectedNestogyClient.existing_mapping.rmm_client_id);
                    if (rmmClientIndex !== -1) {
                        this.rmmClients[rmmClientIndex].is_mapped = false;
                    }

                    this.selectedNestogyClient = null;
                    this.selectedRmmClient = null;

                    this.showMessage('Client mapping removed successfully!', 'success');
                } else {
                    this.showMessage('Failed to remove mapping: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                this.showMessage('Failed to remove mapping: ' + error.message, 'error');
            } finally {
                this.removingMapping = false;
            }
        },

        showMessage(message, type) {
            const messageEl = document.createElement('div');
            messageEl.className = `fixed top-4 right-4 px-4 py-2 rounded-md shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            messageEl.textContent = message;
            document.body.appendChild(messageEl);

            setTimeout(() => {
                messageEl.remove();
            }, 3000);
        }
    }));
});

// Helper function for notifications (if not already defined)
function showNotification(message, type) {
    const messageEl = document.createElement('div');
    messageEl.className = `fixed top-4 right-4 px-4 py-2 rounded-md shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'warning' ? 'bg-yellow-500 text-white' : 
        'bg-red-500 text-white'
    }`;
    messageEl.textContent = message;
    document.body.appendChild(messageEl);

    setTimeout(() => {
        messageEl.remove();
    }, 3000);
}
</script>
@endpush
