@extends('layouts.settings')

@section('title', 'Data Management Settings - Nestogy')

@section('settings-title', 'Data Management Settings')
@section('settings-description', 'Configure data retention, privacy, security, and compliance settings for your organization')

@section('settings-content')
<div x-data="{ activeTab: 'retention' }">
    <form method="POST" action="{{ route('settings.data-management.update') }}">
        @csrf
        @method('PUT')

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 px-6 pt-4 overflow-x-auto">
                <button type="button" 
                        @click="activeTab = 'retention'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'retention', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'retention'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Data Retention
                </button>
                <button type="button" 
                        @click="activeTab = 'destruction'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'destruction', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'destruction'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Data Destruction
                </button>
                <button type="button" 
                        @click="activeTab = 'governance'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'governance', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'governance'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Classification & Governance
                </button>
                <button type="button" 
                        @click="activeTab = 'quality'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'quality', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'quality'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Quality & Validation
                </button>
                <button type="button" 
                        @click="activeTab = 'privacy'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'privacy', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'privacy'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Privacy & Protection
                </button>
                <button type="button" 
                        @click="activeTab = 'lineage'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'lineage', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'lineage'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Lineage & Audit
                </button>
                <button type="button" 
                        @click="activeTab = 'migration'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'migration', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'migration'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Migration & Import/Export
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Data Retention Tab -->
            <div x-show="activeTab === 'retention'" x-transition>
                <div class="space-y-6">
                    <!-- Data Retention Policies -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4V7m6 4V7m-4 8h4m-2 4h.01M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"></path>
                                </svg>
                                Data Retention Policies
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_retention_policies_enabled" 
                                           name="data_retention_policies_enabled" 
                                           value="1"
                                           {{ old('data_retention_policies_enabled', $setting->data_retention_policies_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Retention Policies</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="automated_data_archival" 
                                           name="automated_data_archival" 
                                           value="1"
                                           {{ old('automated_data_archival', $setting->automated_data_archival ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Automated Data Archival</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="policy_compliance_monitoring" 
                                           name="policy_compliance_monitoring" 
                                           value="1"
                                           {{ old('policy_compliance_monitoring', $setting->policy_compliance_monitoring ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Monitor Policy Compliance</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Destruction Tab -->
            <div x-show="activeTab === 'destruction'" x-transition>
                <div class="space-y-6">
                    <!-- Data Destruction & Purging -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Data Destruction & Purging
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_destruction_policies_enabled" 
                                           name="data_destruction_policies_enabled" 
                                           value="1"
                                           {{ old('data_destruction_policies_enabled', $setting->data_destruction_policies_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Destruction Policies</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="secure_data_wiping" 
                                           name="secure_data_wiping" 
                                           value="1"
                                           {{ old('secure_data_wiping', $setting->secure_data_wiping ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Secure Data Wiping</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="certificate_of_destruction" 
                                           name="certificate_of_destruction" 
                                           value="1"
                                           {{ old('certificate_of_destruction', $setting->certificate_of_destruction ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Generate Certificates of Destruction</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Classification & Governance Tab -->
            <div x-show="activeTab === 'governance'" x-transition>
                <div class="space-y-6">
                    <!-- Data Classification & Governance -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                Data Classification & Governance
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_classification_enabled" 
                                           name="data_classification_enabled" 
                                           value="1"
                                           {{ old('data_classification_enabled', $setting->data_classification_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Classification</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_governance_framework" 
                                           name="data_governance_framework" 
                                           value="1"
                                           {{ old('data_governance_framework', $setting->data_governance_framework ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Governance Framework</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_stewardship_enabled" 
                                           name="data_stewardship_enabled" 
                                           value="1"
                                           {{ old('data_stewardship_enabled', $setting->data_stewardship_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Stewardship</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quality & Validation Tab -->
            <div x-show="activeTab === 'quality'" x-transition>
                <div class="space-y-6">
                    <!-- Data Quality & Validation -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Data Quality & Validation
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_quality_monitoring" 
                                           name="data_quality_monitoring" 
                                           value="1"
                                           {{ old('data_quality_monitoring', $setting->data_quality_monitoring ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Quality Monitoring</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_validation_rules" 
                                           name="data_validation_rules" 
                                           value="1"
                                           {{ old('data_validation_rules', $setting->data_validation_rules ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Validation Rules</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_anomaly_detection" 
                                           name="data_anomaly_detection" 
                                           value="1"
                                           {{ old('data_anomaly_detection', $setting->data_anomaly_detection ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Anomaly Detection</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Privacy & Protection Tab -->
            <div x-show="activeTab === 'privacy'" x-transition>
                <div class="space-y-6">
                    <!-- Data Privacy & Protection -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                Data Privacy & Protection
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_anonymization_enabled" 
                                           name="data_anonymization_enabled" 
                                           value="1"
                                           {{ old('data_anonymization_enabled', $setting->data_anonymization_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Anonymization</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_pseudonymization_enabled" 
                                           name="data_pseudonymization_enabled" 
                                           value="1"
                                           {{ old('data_pseudonymization_enabled', $setting->data_pseudonymization_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Pseudonymization</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="pii_detection_enabled" 
                                           name="pii_detection_enabled" 
                                           value="1"
                                           {{ old('pii_detection_enabled', $setting->pii_detection_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable PII Detection</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lineage & Audit Tab -->
            <div x-show="activeTab === 'lineage'" x-transition>
                <div class="space-y-6">
                    <!-- Data Lineage & Audit -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                                Data Lineage & Audit
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_lineage_tracking" 
                                           name="data_lineage_tracking" 
                                           value="1"
                                           {{ old('data_lineage_tracking', $setting->data_lineage_tracking ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Lineage Tracking</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_audit_trails" 
                                           name="data_audit_trails" 
                                           value="1"
                                           {{ old('data_audit_trails', $setting->data_audit_trails ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Audit Trails</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="change_data_capture" 
                                           name="change_data_capture" 
                                           value="1"
                                           {{ old('change_data_capture', $setting->change_data_capture ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Change Data Capture</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Migration & Import/Export Tab -->
            <div x-show="activeTab === 'migration'" x-transition>
                <div class="space-y-6">
                    <!-- Data Migration & Import/Export -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                Data Migration & Import/Export
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_migration_tools_enabled" 
                                           name="data_migration_tools_enabled" 
                                           value="1"
                                           {{ old('data_migration_tools_enabled', $setting->data_migration_tools_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Migration Tools</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="bulk_import_export_enabled" 
                                           name="bulk_import_export_enabled" 
                                           value="1"
                                           {{ old('bulk_import_export_enabled', $setting->bulk_import_export_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Bulk Import/Export</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="data_transformation_enabled" 
                                           name="data_transformation_enabled" 
                                           value="1"
                                           {{ old('data_transformation_enabled', $setting->data_transformation_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Data Transformation</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('settings.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 dark:bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-700">Save Settings</button>
        </div>
    </form>
</div>
@endsection
