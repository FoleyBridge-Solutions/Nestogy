@extends('layouts.settings')

@section('title', 'Backup & Recovery Settings - Nestogy')

@section('settings-title', 'Backup & Recovery Settings')
@section('settings-description', 'Configure backup policies, disaster recovery, and business continuity settings')

@section('settings-content')
<div x-data="{ activeTab: 'backup' }">
    <form method="POST" action="{{ route('settings.backup-recovery.update') }}">
        @csrf
        @method('PUT')
        
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'backup'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'backup', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'backup'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Backup Policies
                </button>
                <button type="button" 
                        @click="activeTab = 'disaster'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'disaster', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'disaster'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Disaster Recovery
                </button>
                <button type="button" 
                        @click="activeTab = 'protection'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'protection', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'protection'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Ransomware Protection
                </button>
                <button type="button" 
                        @click="activeTab = 'testing'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'testing', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'testing'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Testing & Validation
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Backup Policies Tab -->
            <div x-show="activeTab === 'backup'" x-transition>
                <div class="space-y-6">
                    <!-- Backup Policies -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                </svg>
                                Backup Policies
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="automated_backups_enabled" 
                                           name="automated_backups_enabled" 
                                           value="1"
                                           {{ old('automated_backups_enabled', $setting->automated_backups_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Automated Backups</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="incremental_backups_enabled" 
                                           name="incremental_backups_enabled" 
                                           value="1"
                                           {{ old('incremental_backups_enabled', $setting->incremental_backups_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Incremental Backups</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="differential_backups_enabled" 
                                           name="differential_backups_enabled" 
                                           value="1"
                                           {{ old('differential_backups_enabled', $setting->differential_backups_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Differential Backups</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Cloud Backup Settings -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                </svg>
                                Cloud Backup Settings
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="cloud_backup_enabled" 
                                           name="cloud_backup_enabled" 
                                           value="1"
                                           {{ old('cloud_backup_enabled', $setting->cloud_backup_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Cloud Backup</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="multi_cloud_backup" 
                                           name="multi_cloud_backup" 
                                           value="1"
                                           {{ old('multi_cloud_backup', $setting->multi_cloud_backup ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Multi-Cloud Backup</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="backup_encryption_enabled" 
                                           name="backup_encryption_enabled" 
                                           value="1"
                                           {{ old('backup_encryption_enabled', $setting->backup_encryption_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Backup Encryption</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Disaster Recovery Tab -->
            <div x-show="activeTab === 'disaster'" x-transition>
                <div class="space-y-6">
                    <!-- Disaster Recovery -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                Disaster Recovery
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="disaster_recovery_enabled" 
                                           name="disaster_recovery_enabled" 
                                           value="1"
                                           {{ old('disaster_recovery_enabled', $setting->disaster_recovery_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Disaster Recovery Planning</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="automated_failover_enabled" 
                                           name="automated_failover_enabled" 
                                           value="1"
                                           {{ old('automated_failover_enabled', $setting->automated_failover_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Automated Failover</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="hot_standby_enabled" 
                                           name="hot_standby_enabled" 
                                           value="1"
                                           {{ old('hot_standby_enabled', $setting->hot_standby_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Hot Standby Systems</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Data Replication -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                Data Replication
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="real_time_replication" 
                                           name="real_time_replication" 
                                           value="1"
                                           {{ old('real_time_replication', $setting->real_time_replication ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Real-Time Data Replication</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="cross_region_replication" 
                                           name="cross_region_replication" 
                                           value="1"
                                           {{ old('cross_region_replication', $setting->cross_region_replication ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Cross-Region Replication</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="database_clustering_enabled" 
                                           name="database_clustering_enabled" 
                                           value="1"
                                           {{ old('database_clustering_enabled', $setting->database_clustering_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Database Clustering</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ransomware Protection Tab -->
            <div x-show="activeTab === 'protection'" x-transition>
                <div class="space-y-6">
                    <!-- Ransomware Protection -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Ransomware Protection
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="ransomware_protection_enabled" 
                                           name="ransomware_protection_enabled" 
                                           value="1"
                                           {{ old('ransomware_protection_enabled', $setting->ransomware_protection_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Ransomware Protection</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="immutable_backups_enabled" 
                                           name="immutable_backups_enabled" 
                                           value="1"
                                           {{ old('immutable_backups_enabled', $setting->immutable_backups_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Immutable Backups</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="air_gapped_backups" 
                                           name="air_gapped_backups" 
                                           value="1"
                                           {{ old('air_gapped_backups', $setting->air_gapped_backups ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Air-Gapped Backups</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Business Continuity -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Business Continuity
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="business_continuity_planning" 
                                           name="business_continuity_planning" 
                                           value="1"
                                           {{ old('business_continuity_planning', $setting->business_continuity_planning ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Business Continuity Planning</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="emergency_communication_enabled" 
                                           name="emergency_communication_enabled" 
                                           value="1"
                                           {{ old('emergency_communication_enabled', $setting->emergency_communication_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Emergency Communication</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="alternate_site_operations" 
                                           name="alternate_site_operations" 
                                           value="1"
                                           {{ old('alternate_site_operations', $setting->alternate_site_operations ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Alternate Site Operations</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testing & Validation Tab -->
            <div x-show="activeTab === 'testing'" x-transition>
                <div class="space-y-6">
                    <!-- Testing & Validation -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Testing & Validation
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="backup_testing_enabled" 
                                           name="backup_testing_enabled" 
                                           value="1"
                                           {{ old('backup_testing_enabled', $setting->backup_testing_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Automated Backup Testing</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="recovery_simulation_enabled" 
                                           name="recovery_simulation_enabled" 
                                           value="1"
                                           {{ old('recovery_simulation_enabled', $setting->recovery_simulation_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Recovery Simulations</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="compliance_reporting_enabled" 
                                           name="compliance_reporting_enabled" 
                                           value="1"
                                           {{ old('compliance_reporting_enabled', $setting->compliance_reporting_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Compliance Reporting</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200">
            <a href="{{ route('settings.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">Save Settings</button>
        </div>
    </form>
</div>
@endsection