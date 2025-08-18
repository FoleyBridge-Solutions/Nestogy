@extends('layouts.settings')

@section('title', 'Email Settings - Nestogy')

@section('settings-title', 'Email Settings')
@section('settings-description', 'Configure SMTP, IMAP, and email notification settings')

@section('settings-content')
<div x-data="{ activeTab: 'smtp' }">
    <form method="POST" action="{{ route('settings.email.update') }}">
        @csrf
        @method('PUT')

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'smtp'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'smtp', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'smtp'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    SMTP Configuration
                </button>
                <button type="button" 
                        @click="activeTab = 'imap'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'imap', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'imap'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    IMAP Configuration
                </button>
                <button type="button" 
                        @click="activeTab = 'tickets'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'tickets', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'tickets'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Ticket Email Settings
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- SMTP Configuration Tab -->
            <div x-show="activeTab === 'smtp'" x-transition>
                <div class="space-y-6">
                    <!-- SMTP Configuration -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                SMTP Configuration (Outgoing Mail)
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                                <div class="md:col-span-3">
                                    <label for="smtp_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        SMTP Host
                                    </label>
                                    <input type="text" 
                                           id="smtp_host" 
                                           name="smtp_host" 
                                           value="{{ old('smtp_host', $setting?->smtp_host ?? '') }}"
                                           placeholder="smtp.gmail.com"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('smtp_host') border-red-500 @enderror">
                                    @error('smtp_host')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="smtp_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        SMTP Port
                                    </label>
                                    <input type="number" 
                                           id="smtp_port" 
                                           name="smtp_port" 
                                           value="{{ old('smtp_port', $setting?->smtp_port ?? '') }}"
                                           placeholder="587"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('smtp_port') border-red-500 @enderror">
                                    @error('smtp_port')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-6">
                                <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Encryption
                                </label>
                                <select id="smtp_encryption" 
                                        name="smtp_encryption"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('smtp_encryption') border-red-500 @enderror">
                                    <option value="">None</option>
                                    <option value="tls" {{ old('smtp_encryption', $setting?->smtp_encryption ?? '') == 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ old('smtp_encryption', $setting?->smtp_encryption ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                                @error('smtp_encryption')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="smtp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        SMTP Username
                                    </label>
                                    <input type="text" 
                                           id="smtp_username" 
                                           name="smtp_username" 
                                           value="{{ old('smtp_username', $setting?->smtp_username ?? '') }}"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('smtp_username') border-red-500 @enderror">
                                    @error('smtp_username')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="smtp_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        SMTP Password
                                        @if(!empty($setting?->smtp_password))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 ml-2">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                Saved
                                            </span>
                                        @endif
                                    </label>
                                    <div class="relative">
                                        <input type="password" 
                                               id="smtp_password" 
                                               name="smtp_password" 
                                               placeholder="{{ !empty($setting?->smtp_password) ? 'Leave blank to keep current password' : 'Enter SMTP password' }}"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pr-10 @error('smtp_password') border-red-500 @enderror">
                                        <button type="button" 
                                                onclick="togglePasswordVisibility('smtp_password')"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    @error('smtp_password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="mail_from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        From Email Address
                                    </label>
                                    <input type="email" 
                                           id="mail_from_email" 
                                           name="mail_from_email" 
                                           value="{{ old('mail_from_email', $setting?->mail_from_email ?? '') }}"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('mail_from_email') border-red-500 @enderror">
                                    @error('mail_from_email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="mail_from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        From Name
                                    </label>
                                    <input type="text" 
                                           id="mail_from_name" 
                                           name="mail_from_name" 
                                           value="{{ old('mail_from_name', $setting?->mail_from_name ?? '') }}"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('mail_from_name') border-red-500 @enderror">
                                    @error('mail_from_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IMAP Configuration Tab -->
            <div x-show="activeTab === 'imap'" x-transition>
                <div class="space-y-6">
                    <!-- IMAP Configuration -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H10"></path>
                                </svg>
                                IMAP Configuration (Incoming Mail)
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                                <div class="md:col-span-3">
                                    <label for="imap_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        IMAP Host
                                    </label>
                                    <input type="text" 
                                           id="imap_host" 
                                           name="imap_host" 
                                           value="{{ old('imap_host', $setting?->imap_host ?? '') }}"
                                           placeholder="imap.gmail.com"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('imap_host') border-red-500 @enderror">
                                    @error('imap_host')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="imap_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        IMAP Port
                                    </label>
                                    <input type="number" 
                                           id="imap_port" 
                                           name="imap_port" 
                                           value="{{ old('imap_port', $setting?->imap_port ?? '') }}"
                                           placeholder="993"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('imap_port') border-red-500 @enderror">
                                    @error('imap_port')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-6">
                                <label for="imap_encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Encryption
                                </label>
                                <select id="imap_encryption" 
                                        name="imap_encryption"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('imap_encryption') border-red-500 @enderror">
                                    <option value="">None</option>
                                    <option value="tls" {{ old('imap_encryption', $setting?->imap_encryption ?? '') == 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ old('imap_encryption', $setting?->imap_encryption ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                                @error('imap_encryption')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="imap_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        IMAP Username
                                    </label>
                                    <input type="text" 
                                           id="imap_username" 
                                           name="imap_username" 
                                           value="{{ old('imap_username', $setting?->imap_username ?? '') }}"
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('imap_username') border-red-500 @enderror">
                                    @error('imap_username')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="imap_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        IMAP Password
                                        @if(!empty($setting?->imap_password))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 ml-2">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                Saved
                                            </span>
                                        @endif
                                    </label>
                                    <div class="relative">
                                        <input type="password" 
                                               id="imap_password" 
                                               name="imap_password" 
                                               placeholder="{{ !empty($setting?->imap_password) ? 'Leave blank to keep current password' : 'Enter IMAP password' }}"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pr-10 @error('imap_password') border-red-500 @enderror">
                                        <button type="button" 
                                                onclick="togglePasswordVisibility('imap_password')"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    @error('imap_password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Email Settings Tab -->
            <div x-show="activeTab === 'tickets'" x-transition>
                <div class="space-y-6">
                    <!-- Ticket Email Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                                </svg>
                                Ticket Email Settings
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="mb-6">
                                <label class="flex items-start">
                                    <input type="checkbox" 
                                           id="ticket_email_parse" 
                                           name="ticket_email_parse" 
                                           value="1"
                                           {{ old('ticket_email_parse', $setting?->ticket_email_parse ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 mt-1">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Parse Emails to Create Tickets</span>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Automatically create tickets from incoming emails.</p>
                                    </div>
                                </label>
                            </div>

                            <div class="mb-6">
                                <label for="ticket_new_ticket_notification_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    New Ticket Notification Email
                                </label>
                                <input type="email" 
                                       id="ticket_new_ticket_notification_email" 
                                       name="ticket_new_ticket_notification_email" 
                                       value="{{ old('ticket_new_ticket_notification_email', $setting?->ticket_new_ticket_notification_email ?? '') }}"
                                       placeholder="admin@example.com"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('ticket_new_ticket_notification_email') border-red-500 @enderror">
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Email address to receive notifications about new tickets.
                                </p>
                                @error('ticket_new_ticket_notification_email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Testing Section -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Test Email Configuration</h3>
                
                <!-- Provider Presets -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="email_provider_preset" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Quick Setup (Optional)
                        </label>
                        <select id="email_provider_preset" 
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select email provider...</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose a provider to auto-fill common settings</p>
                    </div>
                    
                    <div>
                        <label for="test_email_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Test Email Address (Optional)
                        </label>
                        <input type="email" 
                               id="test_email_address" 
                               placeholder="test@example.com"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Send a test email to this address</p>
                    </div>
                </div>
                
                <!-- Connection Status -->
                <div id="connection_status" class="hidden">
                    <div class="rounded-md p-4">
                        <div class="flex items-center">
                            <div id="status_icon" class="flex-shrink-0">
                                <!-- Status icon will be inserted here -->
                            </div>
                            <div class="ml-3">
                                <h3 id="status_title" class="text-sm font-medium"></h3>
                                <div id="status_message" class="mt-1 text-sm"></div>
                                <div id="status_details" class="mt-2 text-sm"></div>
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
            <button type="button" 
                    id="test_connection_btn"
                    onclick="testEmailConnection()"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <span id="test_btn_text">Test Connection</span>
                <svg id="test_btn_loading" class="hidden animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 dark:bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save Email Settings
            </button>
        </div>
    </form>
</div>

<script>
// Email configuration management
document.addEventListener('DOMContentLoaded', function() {
    loadEmailProviderPresets();
    
    // Setup provider preset change handler
    document.getElementById('email_provider_preset').addEventListener('change', function() {
        if (this.value) {
            applyProviderPreset(this.value);
        }
    });
});

/**
 * Load email provider presets
 */
async function loadEmailProviderPresets() {
    try {
        const response = await fetch('{{ route('settings.email.provider-presets') }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('email_provider_preset');
            
            // Clear existing options except the first one
            while (select.children.length > 1) {
                select.removeChild(select.lastChild);
            }
            
            // Add provider options
            for (const [key, preset] of Object.entries(data.presets)) {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = preset.name;
                select.appendChild(option);
            }
        }
    } catch (error) {
        console.error('Failed to load email provider presets:', error);
    }
}

/**
 * Apply provider preset configuration
 */
async function applyProviderPreset(presetKey) {
    try {
        const response = await fetch('{{ route('settings.email.provider-presets') }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.presets[presetKey]) {
            const preset = data.presets[presetKey];
            
            // Fill SMTP settings
            document.getElementById('smtp_host').value = preset.smtp_host || '';
            document.getElementById('smtp_port').value = preset.smtp_port || '';
            document.getElementById('smtp_encryption').value = preset.smtp_encryption || '';
            
            // Fill IMAP settings
            document.getElementById('imap_host').value = preset.imap_host || '';
            document.getElementById('imap_port').value = preset.imap_port || '';
            document.getElementById('imap_encryption').value = preset.imap_encryption || '';
            
            // Show instructions if available
            if (preset.instructions) {
                showConnectionStatus('info', 'Provider Settings Applied', preset.instructions);
            }
        }
    } catch (error) {
        console.error('Failed to apply provider preset:', error);
        showConnectionStatus('error', 'Error', 'Failed to apply provider settings');
    }
}

/**
 * Test email connection
 */
async function testEmailConnection() {
    const btn = document.getElementById('test_connection_btn');
    const btnText = document.getElementById('test_btn_text');
    const btnLoading = document.getElementById('test_btn_loading');
    
    // Validate required fields (except password which might be saved)
    const requiredFields = ['smtp_host', 'smtp_port', 'smtp_username'];
    const missingFields = [];
    
    for (const fieldId of requiredFields) {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            missingFields.push(fieldId.replace('smtp_', '').toUpperCase());
        }
    }
    
    // Check password - if empty, ask user to confirm they want to test with saved password
    const passwordField = document.getElementById('smtp_password');
    const hasExistingPassword = {{ !empty($setting?->smtp_password) ? 'true' : 'false' }};
    
    if (!passwordField.value.trim() && !hasExistingPassword) {
        missingFields.push('PASSWORD');
    }
    
    if (missingFields.length > 0) {
        showConnectionStatus('error', 'Missing Required Fields', 
            'Please fill in the following fields: ' + missingFields.join(', '));
        return;
    }
    
    // If password is empty but we have a saved password, inform user
    if (!passwordField.value.trim() && hasExistingPassword) {
        showConnectionStatus('info', 'Using Saved Password', 
            'Testing with your previously saved SMTP password. Enter a new password if you want to test with different credentials.');
    }
    
    // Show loading state
    btn.disabled = true;
    btnText.textContent = 'Testing...';
    btnLoading.classList.remove('hidden');
    hideConnectionStatus();
    
    try {
        const formData = new FormData();
        
        // Collect form data
        formData.append('smtp_host', document.getElementById('smtp_host').value);
        formData.append('smtp_port', document.getElementById('smtp_port').value);
        formData.append('smtp_encryption', document.getElementById('smtp_encryption').value);
        formData.append('smtp_username', document.getElementById('smtp_username').value);
        formData.append('smtp_password', document.getElementById('smtp_password').value);
        formData.append('mail_from_email', document.getElementById('mail_from_email').value);
        formData.append('mail_from_name', document.getElementById('mail_from_name').value);
        
        // Add test email if provided
        const testEmail = document.getElementById('test_email_address').value;
        if (testEmail) {
            formData.append('test_email_address', testEmail);
        }
        
        const response = await fetch('{{ route('settings.email.test-connection') }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showConnectionStatus('success', 'Connection Successful', data.message, data.details);
        } else {
            let errorMessage = data.message || 'Connection test failed';
            
            // Handle validation errors
            if (data.errors) {
                const errorList = Object.values(data.errors).flat();
                errorMessage += ': ' + errorList.join(', ');
            }
            
            showConnectionStatus('error', 'Connection Failed', errorMessage, data.details);
        }
        
    } catch (error) {
        console.error('Email connection test failed:', error);
        showConnectionStatus('error', 'Test Failed', 'An unexpected error occurred during testing');
    } finally {
        // Reset button state
        btn.disabled = false;
        btnText.textContent = 'Test Connection';
        btnLoading.classList.add('hidden');
    }
}

/**
 * Show connection status with appropriate styling
 */
function showConnectionStatus(type, title, message, details = null) {
    const statusDiv = document.getElementById('connection_status');
    const statusIcon = document.getElementById('status_icon');
    const statusTitle = document.getElementById('status_title');
    const statusMessage = document.getElementById('status_message');
    const statusDetails = document.getElementById('status_details');
    
    // Reset classes
    statusDiv.className = 'rounded-md p-4';
    
    // Set type-specific styling and icons
    if (type === 'success') {
        statusDiv.classList.add('bg-green-50', 'border', 'border-green-200');
        statusTitle.className = 'text-sm font-medium text-green-800';
        statusMessage.className = 'mt-1 text-sm text-green-700';
        statusDetails.className = 'mt-2 text-sm text-green-600';
        statusIcon.innerHTML = `
            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        `;
    } else if (type === 'error') {
        statusDiv.classList.add('bg-red-50', 'border', 'border-red-200');
        statusTitle.className = 'text-sm font-medium text-red-800';
        statusMessage.className = 'mt-1 text-sm text-red-700';
        statusDetails.className = 'mt-2 text-sm text-red-600';
        statusIcon.innerHTML = `
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        `;
    } else if (type === 'info') {
        statusDiv.classList.add('bg-blue-50', 'border', 'border-blue-200');
        statusTitle.className = 'text-sm font-medium text-blue-800';
        statusMessage.className = 'mt-1 text-sm text-blue-700';
        statusDetails.className = 'mt-2 text-sm text-blue-600';
        statusIcon.innerHTML = `
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
        `;
    }
    
    // Set content
    statusTitle.textContent = title;
    statusMessage.textContent = message;
    
    // Handle details
    if (details) {
        if (typeof details === 'object') {
            let detailsHtml = '';
            for (const [key, value] of Object.entries(details)) {
                detailsHtml += `<div><strong>${key}:</strong> ${value}</div>`;
            }
            statusDetails.innerHTML = detailsHtml;
        } else {
            statusDetails.textContent = details;
        }
        statusDetails.classList.remove('hidden');
    } else {
        statusDetails.classList.add('hidden');
    }
    
    // Show the status
    statusDiv.classList.remove('hidden');
    
    // Scroll to status
    statusDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Hide connection status
 */
function hideConnectionStatus() {
    document.getElementById('connection_status').classList.add('hidden');
}

/**
 * Toggle password visibility
 */
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const button = event.currentTarget;
    const svg = button.querySelector('svg');
    
    if (field.type === 'password') {
        field.type = 'text';
        svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>';
    } else {
        field.type = 'password';
        svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
    }
}
</script>
@endsection