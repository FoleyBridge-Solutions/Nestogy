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
});
</script>
@endpush
