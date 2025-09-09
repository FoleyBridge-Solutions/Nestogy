@extends('layouts.settings')

@section('title', 'Ticketing & Service Desk Settings - Nestogy')

@section('settings-title', 'Ticketing & Service Desk Settings')
@section('settings-description', 'Configure ticket management, SLA rules, and service desk settings')

@section('settings-content')
<div x-data="{ activeTab: 'general' }">
    <form method="POST" action="{{ route('settings.ticketing-service-desk.update') }}">
        @csrf
        @method('PUT')
        
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'general'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'general', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'general'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    General Settings
                </button>
                <button type="button" 
                        @click="activeTab = 'sla'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'sla', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'sla'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    SLA Rules
                </button>
                <button type="button" 
                        @click="activeTab = 'automation'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'automation', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'automation'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Automation
                </button>
                <button type="button" 
                        @click="activeTab = 'categories'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'categories', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'categories'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Categories & Priorities
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- General Settings Tab -->
            <div x-show="activeTab === 'general'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ticket System Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Ticket Prefix -->
                            <div>
                                <label for="ticket_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Ticket Number Prefix
                                </label>
                                <input type="text" 
                                       id="ticket_prefix"
                                       name="ticket_prefix"
                                       value="{{ old('ticket_prefix', $setting->ticket_prefix ?? 'TKT') }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="TKT">
                            </div>

                            <!-- Starting Number -->
                            <div>
                                <label for="ticket_starting_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Starting Ticket Number
                                </label>
                                <input type="number" 
                                       id="ticket_starting_number"
                                       name="ticket_starting_number"
                                       value="{{ old('ticket_starting_number', $setting->ticket_starting_number ?? 1000) }}"
                                       min="1"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Auto-close Days -->
                            <div>
                                <label for="auto_close_resolved_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Auto-close Resolved Tickets (days)
                                </label>
                                <input type="number" 
                                       id="auto_close_resolved_days"
                                       name="auto_close_resolved_days"
                                       value="{{ old('auto_close_resolved_days', $setting->auto_close_resolved_days ?? 7) }}"
                                       min="0"
                                       max="30"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Set to 0 to disable auto-close</p>
                            </div>

                            <!-- Max Attachments -->
                            <div>
                                <label for="max_attachments_per_ticket" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Max Attachments Per Ticket
                                </label>
                                <input type="number" 
                                       id="max_attachments_per_ticket"
                                       name="max_attachments_per_ticket"
                                       value="{{ old('max_attachments_per_ticket', $setting->max_attachments_per_ticket ?? 10) }}"
                                       min="1"
                                       max="50"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="mt-4 space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_client_portal_tickets"
                                       value="1"
                                       {{ old('enable_client_portal_tickets', $setting->enable_client_portal_tickets ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Allow Clients to Create Tickets via Portal</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_email_to_ticket"
                                       value="1"
                                       {{ old('enable_email_to_ticket', $setting->enable_email_to_ticket ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Enable Email-to-Ticket Creation</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_ticket_merging"
                                       value="1"
                                       {{ old('enable_ticket_merging', $setting->enable_ticket_merging ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Enable Ticket Merging</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_recurring_tickets"
                                       value="1"
                                       {{ old('enable_recurring_tickets', $setting->enable_recurring_tickets ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Enable Recurring Tickets</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLA Rules Tab -->
            <div x-show="activeTab === 'sla'" x-transition>
                <div class="space-y-6">
                    <!-- SLA Overview Section -->
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Service Level Agreements</h3>
                            <button type="button" 
                                    onclick="openSLAModal()"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create SLA
                            </button>
                        </div>
                        
                        <!-- SLA Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total SLAs</div>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $slas->count() }}</div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Default SLA</div>
                                <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $defaultSLA->name ?? 'None Set' }}</div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Clients with SLA</div>
                                <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $clientsWithSLA }}/{{ $totalClients }}</div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">SLA Compliance</div>
                                <div class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ $slaMetrics['response_sla_percentage'] ?? 0 }}%</div>
                            </div>
                        </div>

                        <!-- Existing SLAs List -->
                        @if($slas->count() > 0)
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Current SLAs</h4>
                                </div>
                                <div class="divide-y divide-gray-200">
                                    @foreach($slas as $sla)
                                        <div class="px-4 py-3 flex items-center justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <h5 class="text-sm font-medium text-gray-900">{{ $sla->name }}</h5>
                                                    @if($sla->is_default)
                                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Default</span>
                                                    @endif
                                                    @if(!$sla->is_active)
                                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">{{ $sla->description }}</p>
                                                <div class="text-xs text-gray-400 mt-1">
                                                    Response: {{ $sla->critical_response_minutes }}m / {{ $sla->high_response_minutes }}m / {{ $sla->medium_response_minutes }}m / {{ $sla->low_response_minutes }}m
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs text-gray-500">{{ $sla->clients()->count() }} clients</span>
                                                <button type="button" 
                                                        onclick="editSLA({{ $sla->id }})"
                                                        class="text-blue-600 hover:text-blue-700 text-sm font-medium">Edit</button>
                                                @if(!$sla->is_default)
                                                    <button type="button" 
                                                            onclick="deleteSLA({{ $sla->id }})"
                                                            class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No SLAs configured</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by creating your first SLA.</p>
                                <div class="mt-6">
                                    <button type="button" 
                                            onclick="openSLAModal()"
                                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        Create SLA
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Client SLA Assignment Section -->
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h4 class="text-sm font-medium text-gray-900">Client SLA Assignments</h4>
                            <p class="text-xs text-gray-500 mt-1">Manage which SLA applies to each client. Clients without a specific SLA will use the default.</p>
                        </div>
                        <div class="p-4">
                            <a href="{{ route('settings.slas.clients') }}" 
                               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Manage Client Assignments
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Automation Tab -->
            <div x-show="activeTab === 'automation'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Ticket Automation</h3>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="auto_assign_tickets"
                                       value="1"
                                       {{ old('auto_assign_tickets', $setting->auto_assign_tickets ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Auto-assign Tickets Based on Rules</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="auto_escalate_overdue"
                                       value="1"
                                       {{ old('auto_escalate_overdue', $setting->auto_escalate_overdue ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Auto-escalate Overdue Tickets</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="auto_reply_enabled"
                                       value="1"
                                       {{ old('auto_reply_enabled', $setting->auto_reply_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Send Auto-reply on Ticket Creation</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="notify_on_customer_reply"
                                       value="1"
                                       {{ old('notify_on_customer_reply', $setting->notify_on_customer_reply ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Notify Technician on Customer Reply</span>
                            </label>
                        </div>

                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-3">Escalation Settings</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="escalation_level_1_hours" class="block text-sm font-medium text-gray-700 mb-1">
                                        Level 1 Escalation (hours)
                                    </label>
                                    <input type="number" 
                                           id="escalation_level_1_hours"
                                           name="escalation_level_1_hours"
                                           value="{{ old('escalation_level_1_hours', $setting->escalation_level_1_hours ?? 24) }}"
                                           min="1"
                                           max="168"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="escalation_level_2_hours" class="block text-sm font-medium text-gray-700 mb-1">
                                        Level 2 Escalation (hours)
                                    </label>
                                    <input type="number" 
                                           id="escalation_level_2_hours"
                                           name="escalation_level_2_hours"
                                           value="{{ old('escalation_level_2_hours', $setting->escalation_level_2_hours ?? 48) }}"
                                           min="2"
                                           max="336"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories & Priorities Tab -->
            <div x-show="activeTab === 'categories'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Default Categories</h3>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600">Default ticket categories (customize in Ticket Settings)</p>
                            <ul class="list-disc list-inside text-sm text-gray-700">
                                <li>Hardware Issue</li>
                                <li>Software Issue</li>
                                <li>Network Issue</li>
                                <li>Security Issue</li>
                                <li>Service Request</li>
                                <li>Other</li>
                            </ul>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Priority Levels</h3>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600">Standard priority levels</p>
                            <ul class="list-disc list-inside text-sm text-gray-700">
                                <li class="text-red-600">Critical - System Down</li>
                                <li class="text-orange-600">High - Major Impact</li>
                                <li class="text-yellow-600">Medium - Moderate Impact</li>
                                <li class="text-green-600">Low - Minor Impact</li>
                            </ul>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Status Workflow</h3>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600">Default ticket status workflow</p>
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded">New</span>
                                <span>→</span>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded">In Progress</span>
                                <span>→</span>
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded">Awaiting Customer</span>
                                <span>→</span>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded">Resolved</span>
                                <span>→</span>
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded">Closed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('settings.index') }}" 
               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 dark:bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save Settings
            </button>
        </div>
    </form>

    <!-- SLA CRUD Modal -->
    <div id="slaModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <form id="slaForm" method="POST">
                    @csrf
                    <input type="hidden" id="slaMethod" name="_method" value="POST">
                    <input type="hidden" id="slaId" name="sla_id" value="">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                    Create New SLA
                                </h3>
                                
                                <!-- SLA Form Fields -->
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Basic Information -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="sla_name" class="block text-sm font-medium text-gray-700">SLA Name</label>
                                            <input type="text" id="sla_name" name="name" required 
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="sla_coverage_type" class="block text-sm font-medium text-gray-700">Coverage Type</label>
                                            <select id="sla_coverage_type" name="coverage_type" required
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <option value="business_hours">Business Hours Only</option>
                                                <option value="24/7">24/7 Coverage</option>
                                                <option value="custom">Custom Schedule</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="sla_description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea id="sla_description" name="description" rows="2"
                                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                    </div>

                                    <!-- Response Times -->
                                    <div>
                                        <h4 class="text-md font-medium text-gray-900 mb-3">Response Times (minutes)</h4>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div>
                                                <label for="critical_response_minutes" class="block text-sm font-medium text-red-700">Critical</label>
                                                <input type="number" id="critical_response_minutes" name="critical_response_minutes" 
                                                       value="60" min="5" max="1440" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="high_response_minutes" class="block text-sm font-medium text-orange-700">High</label>
                                                <input type="number" id="high_response_minutes" name="high_response_minutes" 
                                                       value="240" min="15" max="2880" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="medium_response_minutes" class="block text-sm font-medium text-yellow-700">Medium</label>
                                                <input type="number" id="medium_response_minutes" name="medium_response_minutes" 
                                                       value="480" min="30" max="4320" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="low_response_minutes" class="block text-sm font-medium text-green-700">Low</label>
                                                <input type="number" id="low_response_minutes" name="low_response_minutes" 
                                                       value="1440" min="60" max="10080" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Resolution Times -->
                                    <div>
                                        <h4 class="text-md font-medium text-gray-900 mb-3">Resolution Times (minutes)</h4>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div>
                                                <label for="critical_resolution_minutes" class="block text-sm font-medium text-red-700">Critical</label>
                                                <input type="number" id="critical_resolution_minutes" name="critical_resolution_minutes" 
                                                       value="240" min="30" max="2880" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="high_resolution_minutes" class="block text-sm font-medium text-orange-700">High</label>
                                                <input type="number" id="high_resolution_minutes" name="high_resolution_minutes" 
                                                       value="1440" min="120" max="4320" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="medium_resolution_minutes" class="block text-sm font-medium text-yellow-700">Medium</label>
                                                <input type="number" id="medium_resolution_minutes" name="medium_resolution_minutes" 
                                                       value="4320" min="480" max="10080" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="low_resolution_minutes" class="block text-sm font-medium text-green-700">Low</label>
                                                <input type="number" id="low_resolution_minutes" name="low_resolution_minutes" 
                                                       value="10080" min="1440" max="20160" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Business Hours -->
                                    <div id="businessHoursSection">
                                        <h4 class="text-md font-medium text-gray-900 mb-3">Business Hours</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label for="business_hours_start" class="block text-sm font-medium text-gray-700">Start Time</label>
                                                <input type="time" id="business_hours_start" name="business_hours_start" 
                                                       value="09:00" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="business_hours_end" class="block text-sm font-medium text-gray-700">End Time</label>
                                                <input type="time" id="business_hours_end" name="business_hours_end" 
                                                       value="17:00" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                                                <select id="timezone" name="timezone" required
                                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                    <option value="UTC">UTC</option>
                                                    <option value="America/New_York">Eastern Time</option>
                                                    <option value="America/Chicago">Central Time</option>
                                                    <option value="America/Denver">Mountain Time</option>
                                                    <option value="America/Los_Angeles">Pacific Time</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Days</label>
                                            <div class="flex flex-wrap gap-3">
                                                @foreach(['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'] as $value => $label)
                                                    <label class="flex items-center">
                                                        <input type="checkbox" name="business_days[]" value="{{ $value }}" 
                                                               {{ in_array($value, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']) ? 'checked' : '' }}
                                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                        <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SLA Options -->
                                    <div class="space-y-4">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="is_default" name="is_default" value="1"
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <label for="is_default" class="ml-3 text-sm text-gray-700">Set as Default SLA</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="escalation_enabled" name="escalation_enabled" value="1" checked
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <label for="escalation_enabled" class="ml-3 text-sm text-gray-700">Enable Escalation</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="notify_on_breach" name="notify_on_breach" value="1" checked
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <label for="notify_on_breach" class="ml-3 text-sm text-gray-700">Notify on SLA Breach</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-flex flex-wrap -mx-4-reverse">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save SLA
                        </button>
                        <button type="button" onclick="closeSLAModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// SLA Modal Management
function openSLAModal(slaId = null) {
    const modal = document.getElementById('slaModal');
    const form = document.getElementById('slaForm');
    const title = document.getElementById('modal-title');
    
    if (slaId) {
        // Edit mode
        title.textContent = 'Edit SLA';
        form.action = `/settings/slas/${slaId}`;
        document.getElementById('slaMethod').value = 'PUT';
        document.getElementById('slaId').value = slaId;
        
        // Load SLA data via AJAX
        loadSLAData(slaId);
    } else {
        // Create mode
        title.textContent = 'Create New SLA';
        form.action = '/settings/slas';
        document.getElementById('slaMethod').value = 'POST';
        document.getElementById('slaId').value = '';
        form.reset();
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeSLAModal() {
    const modal = document.getElementById('slaModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function editSLA(slaId) {
    openSLAModal(slaId);
}

function deleteSLA(slaId) {
    if (confirm('Are you sure you want to delete this SLA? Any clients using this SLA will be reassigned to the default SLA.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/settings/slas/${slaId}`;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        form.appendChild(methodInput);
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function loadSLAData(slaId) {
    fetch(`/settings/slas/${slaId}/edit`)
        .then(response => response.json())
        .then(data => {
            // Populate form fields with SLA data
            document.getElementById('sla_name').value = data.name || '';
            document.getElementById('sla_description').value = data.description || '';
            document.getElementById('sla_coverage_type').value = data.coverage_type || 'business_hours';
            
            // Response times
            document.getElementById('critical_response_minutes').value = data.critical_response_minutes || 60;
            document.getElementById('high_response_minutes').value = data.high_response_minutes || 240;
            document.getElementById('medium_response_minutes').value = data.medium_response_minutes || 480;
            document.getElementById('low_response_minutes').value = data.low_response_minutes || 1440;
            
            // Resolution times
            document.getElementById('critical_resolution_minutes').value = data.critical_resolution_minutes || 240;
            document.getElementById('high_resolution_minutes').value = data.high_resolution_minutes || 1440;
            document.getElementById('medium_resolution_minutes').value = data.medium_resolution_minutes || 4320;
            document.getElementById('low_resolution_minutes').value = data.low_resolution_minutes || 10080;
            
            // Business hours
            document.getElementById('business_hours_start').value = data.business_hours_start || '09:00';
            document.getElementById('business_hours_end').value = data.business_hours_end || '17:00';
            document.getElementById('timezone').value = data.timezone || 'UTC';
            
            // Business days
            const businessDays = data.business_days || ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            document.querySelectorAll('input[name="business_days[]"]').forEach(checkbox => {
                checkbox.checked = businessDays.includes(checkbox.value);
            });
            
            // Options
            document.getElementById('is_default').checked = data.is_default || false;
            document.getElementById('escalation_enabled').checked = data.escalation_enabled !== false;
            document.getElementById('notify_on_breach').checked = data.notify_on_breach !== false;
        })
        .catch(error => {
            console.error('Error loading SLA data:', error);
            alert('Error loading SLA data. Please try again.');
        });
}

// Coverage type handling
document.getElementById('sla_coverage_type').addEventListener('change', function() {
    const businessHoursSection = document.getElementById('businessHoursSection');
    if (this.value === '24/7') {
        businessHoursSection.style.display = 'none';
    } else {
        businessHoursSection.style.display = 'block';
    }
});

// Close modal when clicking outside
document.getElementById('slaModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSLAModal();
    }
});
</script>

@endsection
