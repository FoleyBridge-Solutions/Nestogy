@extends('layouts.settings')

@section('title', 'Compliance & Audit Settings - Nestogy')

@section('settings-title', 'Compliance & Audit Settings')
@section('settings-description', 'Configure compliance standards, audit policies, and regulatory requirements')

@section('settings-content')
<div x-data="{ activeTab: 'compliance' }">
    <form method="POST" action="{{ route('settings.compliance-audit.update') }}">
        @csrf
        @method('PUT')
        
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'compliance'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'compliance', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'compliance'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Compliance Standards
                </button>
                <button type="button" 
                        @click="activeTab = 'audit'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'audit', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'audit'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Audit Configuration
                </button>
                <button type="button" 
                        @click="activeTab = 'reporting'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'reporting', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'reporting'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Reporting
                </button>
                <button type="button" 
                        @click="activeTab = 'retention'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'retention', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'retention'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Data Retention
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Compliance Standards Tab -->
            <div x-show="activeTab === 'compliance'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Compliance Frameworks</h3>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="gdpr_compliance"
                                       value="1"
                                       {{ old('gdpr_compliance', $setting->gdpr_compliance ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">GDPR Compliance (General Data Protection Regulation)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="hipaa_compliance"
                                       value="1"
                                       {{ old('hipaa_compliance', $setting->hipaa_compliance ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">HIPAA Compliance (Health Insurance Portability)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="pci_dss_compliance"
                                       value="1"
                                       {{ old('pci_dss_compliance', $setting->pci_dss_compliance ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">PCI DSS Compliance (Payment Card Industry)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="sox_compliance"
                                       value="1"
                                       {{ old('sox_compliance', $setting->sox_compliance ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">SOX Compliance (Sarbanes-Oxley Act)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="iso_27001_compliance"
                                       value="1"
                                       {{ old('iso_27001_compliance', $setting->iso_27001_compliance ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">ISO 27001 Compliance</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Data Privacy Settings</h3>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="data_anonymization_enabled"
                                       value="1"
                                       {{ old('data_anonymization_enabled', $setting->data_anonymization_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Data Anonymization</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="right_to_be_forgotten"
                                       value="1"
                                       {{ old('right_to_be_forgotten', $setting->right_to_be_forgotten ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Support Right to Be Forgotten</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="data_portability_enabled"
                                       value="1"
                                       {{ old('data_portability_enabled', $setting->data_portability_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Data Portability</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="consent_management_enabled"
                                       value="1"
                                       {{ old('consent_management_enabled', $setting->consent_management_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Consent Management</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Configuration Tab -->
            <div x-show="activeTab === 'audit'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Audit Trail Settings</h3>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="audit_all_actions"
                                       value="1"
                                       {{ old('audit_all_actions', $setting->audit_all_actions ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Audit All User Actions</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="audit_file_access"
                                       value="1"
                                       {{ old('audit_file_access', $setting->audit_file_access ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Audit File Access</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="audit_configuration_changes"
                                       value="1"
                                       {{ old('audit_configuration_changes', $setting->audit_configuration_changes ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Audit Configuration Changes</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="audit_api_calls"
                                       value="1"
                                       {{ old('audit_api_calls', $setting->audit_api_calls ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Audit API Calls</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Security Auditing</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="security_scan_frequency" class="block text-sm font-medium text-gray-700 mb-1">
                                    Security Scan Frequency
                                </label>
                                <select id="security_scan_frequency"
                                        name="security_scan_frequency"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="daily" {{ old('security_scan_frequency', $setting->security_scan_frequency ?? 'weekly') == 'daily' ? 'selected' : '' }}>Daily</option>
                                    <option value="weekly" {{ old('security_scan_frequency', $setting->security_scan_frequency ?? 'weekly') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="monthly" {{ old('security_scan_frequency', $setting->security_scan_frequency ?? 'weekly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="quarterly" {{ old('security_scan_frequency', $setting->security_scan_frequency ?? 'weekly') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                </select>
                            </div>
                            <div>
                                <label for="vulnerability_scan_enabled" class="flex items-center mt-6">
                                    <input type="checkbox" 
                                           id="vulnerability_scan_enabled"
                                           name="vulnerability_scan_enabled"
                                           value="1"
                                           {{ old('vulnerability_scan_enabled', $setting->vulnerability_scan_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Vulnerability Scanning</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reporting Tab -->
            <div x-show="activeTab === 'reporting'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Compliance Reporting</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="compliance_report_frequency" class="block text-sm font-medium text-gray-700 mb-1">
                                    Report Generation Frequency
                                </label>
                                <select id="compliance_report_frequency"
                                        name="compliance_report_frequency"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="weekly" {{ old('compliance_report_frequency', $setting->compliance_report_frequency ?? 'monthly') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="monthly" {{ old('compliance_report_frequency', $setting->compliance_report_frequency ?? 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="quarterly" {{ old('compliance_report_frequency', $setting->compliance_report_frequency ?? 'monthly') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                    <option value="annually" {{ old('compliance_report_frequency', $setting->compliance_report_frequency ?? 'monthly') == 'annually' ? 'selected' : '' }}>Annually</option>
                                </select>
                            </div>
                            <div>
                                <label for="report_recipients" class="block text-sm font-medium text-gray-700 mb-1">
                                    Report Recipients (Email)
                                </label>
                                <input type="email" 
                                       id="report_recipients"
                                       name="report_recipients"
                                       value="{{ old('report_recipients', $setting->report_recipients ?? '') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="compliance@example.com">
                                <p class="mt-1 text-xs text-gray-500">Separate multiple emails with commas</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="auto_generate_reports"
                                       value="1"
                                       {{ old('auto_generate_reports', $setting->auto_generate_reports ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Auto-generate Compliance Reports</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="include_remediation_steps"
                                       value="1"
                                       {{ old('include_remediation_steps', $setting->include_remediation_steps ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Include Remediation Steps in Reports</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="executive_summary_enabled"
                                       value="1"
                                       {{ old('executive_summary_enabled', $setting->executive_summary_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Include Executive Summary</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Retention Tab -->
            <div x-show="activeTab === 'retention'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Data Retention Policies</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="audit_log_retention_years" class="block text-sm font-medium text-gray-700 mb-1">
                                    Audit Log Retention (years)
                                </label>
                                <input type="number" 
                                       id="audit_log_retention_years"
                                       name="audit_log_retention_years"
                                       value="{{ old('audit_log_retention_years', $setting->audit_log_retention_years ?? 7) }}"
                                       min="1"
                                       max="10"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="compliance_report_retention_years" class="block text-sm font-medium text-gray-700 mb-1">
                                    Compliance Report Retention (years)
                                </label>
                                <input type="number" 
                                       id="compliance_report_retention_years"
                                       name="compliance_report_retention_years"
                                       value="{{ old('compliance_report_retention_years', $setting->compliance_report_retention_years ?? 7) }}"
                                       min="1"
                                       max="10"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="security_incident_retention_years" class="block text-sm font-medium text-gray-700 mb-1">
                                    Security Incident Retention (years)
                                </label>
                                <input type="number" 
                                       id="security_incident_retention_years"
                                       name="security_incident_retention_years"
                                       value="{{ old('security_incident_retention_years', $setting->security_incident_retention_years ?? 5) }}"
                                       min="1"
                                       max="10"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="customer_data_retention_years" class="block text-sm font-medium text-gray-700 mb-1">
                                    Customer Data Retention (years)
                                </label>
                                <input type="number" 
                                       id="customer_data_retention_years"
                                       name="customer_data_retention_years"
                                       value="{{ old('customer_data_retention_years', $setting->customer_data_retention_years ?? 3) }}"
                                       min="1"
                                       max="10"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="mt-4 space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="auto_purge_old_data"
                                       value="1"
                                       {{ old('auto_purge_old_data', $setting->auto_purge_old_data ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Auto-purge Data After Retention Period</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="archive_before_deletion"
                                       value="1"
                                       {{ old('archive_before_deletion', $setting->archive_before_deletion ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Archive Data Before Deletion</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="legal_hold_enabled"
                                       value="1"
                                       {{ old('legal_hold_enabled', $setting->legal_hold_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Legal Hold Capability</span>
                            </label>
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
