@extends('layouts.settings')

@section('title', 'General Settings - Nestogy')

@section('settings-title', 'General Settings')
@section('settings-description', 'Configure your company information and system preferences')

@section('settings-actions')
<button @click="$dispatch('open-import-modal')" 
        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
    </svg>
    Import
</button>
<a href="{{ route('settings.export') }}" 
   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
    </svg>
    Export
</a>
<a href="{{ route('settings.templates') }}" 
   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
    </svg>
    Templates
</a>
@endsection

@section('settings-content')
<div x-data="{ activeTab: 'company', showImportModal: false }" @open-import-modal.window="showImportModal = true">
    
    <form method="POST" action="{{ route('settings.general.update') }}">
        @csrf
        @method('PUT')
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'company'"
                        :class="{'border-primary-500 text-blue-600 dark:text-blue-400': activeTab === 'company', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'company'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Company Information
                </button>
                <button type="button" 
                        @click="activeTab = 'localization'"
                        :class="{'border-primary-500 text-blue-600 dark:text-blue-400': activeTab === 'localization', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'localization'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Localization
                </button>
                <button type="button" 
                        @click="activeTab = 'branding'"
                        :class="{'border-primary-500 text-blue-600 dark:text-blue-400': activeTab === 'branding', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'branding'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Branding & Colors
                </button>
                <button type="button" 
                        @click="activeTab = 'system'"
                        :class="{'border-primary-500 text-blue-600 dark:text-blue-400': activeTab === 'system', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'system'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    System Preferences
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Company Information Tab -->
            <div x-show="activeTab === 'company'" x-transition>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Company Name -->
                    <div class="col-span-12-span-2 md:col-span-12-span-1">
                        <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Company Name
                        </label>
                        <input type="text" 
                               id="company_name"
                               name="company_name"
                               value="{{ old('company_name', $company->name ?? '') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="Enter company name">
                    </div>

                    <!-- Business Phone -->
                    <div class="col-span-12-span-2 md:col-span-12-span-1">
                        <label for="business_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Business Phone
                        </label>
                        <input type="tel" 
                               id="business_phone"
                               name="business_phone"
                               value="{{ old('business_phone', $settings['business_phone'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="(555) 123-4567">
                    </div>

                    <!-- Business Email -->
                    <div class="col-span-12-span-2 md:col-span-12-span-1">
                        <label for="business_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Business Email
                        </label>
                        <input type="email" 
                               id="business_email"
                               name="business_email"
                               value="{{ old('business_email', $settings['business_email'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="info@company.com">
                    </div>

                    <!-- Tax ID -->
                    <div class="col-span-12-span-2 md:col-span-12-span-1">
                        <label for="tax_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Tax ID / EIN
                        </label>
                        <input type="text" 
                               id="tax_id"
                               name="tax_id"
                               value="{{ old('tax_id', $settings['tax_id'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="12-3456789">
                    </div>

                    <!-- Website -->
                    <div class="col-span-12-span-2">
                        <label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Website
                        </label>
                        <input type="url" 
                               id="website"
                               name="website"
                               value="{{ old('website', $settings['website'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="https://www.example.com">
                    </div>

                    <!-- Business Address -->
                    <div class="col-span-12-span-2">
                        <label for="business_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Business Address
                        </label>
                        <textarea id="business_address"
                                  name="business_address"
                                  rows="3"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                  placeholder="123 Main St, Suite 100">{{ old('business_address', $settings['business_address'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Localization Tab -->
            <div x-show="activeTab === 'localization'" x-transition>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Timezone -->
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Timezone
                        </label>
                        <select id="timezone"
                                name="timezone"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @foreach($timezones as $value => $label)
                                <option value="{{ $value }}" {{ old('timezone', $settings['timezone'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Format -->
                    <div>
                        <label for="date_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Date Format
                        </label>
                        <select id="date_format"
                                name="date_format"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @foreach($dateFormats as $value => $label)
                                <option value="{{ $value }}" {{ old('date_format', $settings['date_format'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Time Format -->
                    <div>
                        <label for="time_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Time Format
                        </label>
                        <select id="time_format"
                                name="time_format"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="12" {{ old('time_format', $settings['time_format'] ?? '12') == '12' ? 'selected' : '' }}>12 Hour (1:30 PM)</option>
                            <option value="24" {{ old('time_format', $settings['time_format'] ?? '12') == '24' ? 'selected' : '' }}>24 Hour (13:30)</option>
                        </select>
                    </div>

                    <!-- Currency -->
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Currency
                        </label>
                        <select id="currency"
                                name="currency"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @foreach($currencies as $code => $name)
                                <option value="{{ $code }}" {{ old('currency', $settings['currency'] ?? 'USD') == $code ? 'selected' : '' }}>{{ $code }} - {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Language -->
                    <div>
                        <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Language
                        </label>
                        <select id="language"
                                name="language"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="en" {{ old('language', $settings['language'] ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                            <option value="es" {{ old('language', $settings['language'] ?? 'en') == 'es' ? 'selected' : '' }}>Spanish</option>
                            <option value="fr" {{ old('language', $settings['language'] ?? 'en') == 'fr' ? 'selected' : '' }}>French</option>
                            <option value="de" {{ old('language', $settings['language'] ?? 'en') == 'de' ? 'selected' : '' }}>German</option>
                        </select>
                    </div>

                    <!-- Fiscal Year Start -->
                    <div>
                        <label for="fiscal_year_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Fiscal Year Start
                        </label>
                        <select id="fiscal_year_start"
                                name="fiscal_year_start"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="1" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '1' ? 'selected' : '' }}>January</option>
                            <option value="2" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '2' ? 'selected' : '' }}>February</option>
                            <option value="3" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '3' ? 'selected' : '' }}>March</option>
                            <option value="4" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '4' ? 'selected' : '' }}>April</option>
                            <option value="5" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '5' ? 'selected' : '' }}>May</option>
                            <option value="6" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '6' ? 'selected' : '' }}>June</option>
                            <option value="7" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '7' ? 'selected' : '' }}>July</option>
                            <option value="8" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '8' ? 'selected' : '' }}>August</option>
                            <option value="9" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '9' ? 'selected' : '' }}>September</option>
                            <option value="10" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '10' ? 'selected' : '' }}>October</option>
                            <option value="11" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '11' ? 'selected' : '' }}>November</option>
                            <option value="12" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '12' ? 'selected' : '' }}>December</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Branding & Colors Tab -->
            <div x-show="activeTab === 'branding'" x-transition>
                <div x-data="colorCustomizer()" x-init="init()">
                    <div class="space-y-8">
                        <!-- Color Presets -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Color Presets</h3>
                            <p class="text-sm text-gray-600 mb-4">Choose a preset theme or customize your own colors below.</p>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                                @foreach($colorPresets as $presetName => $presetColors)
                                <button type="button" 
                                        @click="applyPreset('{{ $presetName }}')"
                                        class="relative p-4 rounded-lg border-2 transition-all hover:shadow-md"
                                        :class="currentPreset === '{{ $presetName }}' ? 'border-primary-500 bg-blue-600-50' : 'border-gray-200 hover:border-gray-300'">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <div class="w-6 h-6 rounded-full border-2 border-white shadow-sm" 
                                             style="background-color: {{ $presetColors['primary']['500'] }}"></div>
                                        <div class="w-4 h-4 rounded-full border border-white shadow-sm" 
                                             style="background-color: {{ $presetColors['primary']['600'] }}"></div>
                                        <div class="w-3 h-3 rounded-full border border-white shadow-sm" 
                                             style="background-color: {{ $presetColors['primary']['700'] }}"></div>
                                    </div>
                                    <div class="text-xs font-medium text-gray-900 capitalize">{{ $presetName }}</div>
                                </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Custom Colors -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Custom Colors</h3>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Primary Colors -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Primary Brand Colors</h4>
                                    <div class="space-y-3">
                                        @foreach(['500' => 'Main', '600' => 'Hover', '700' => 'Active'] as $shade => $label)
                                        <div class="flex items-center space-x-3" x-data="{ 
                                            get colorValue() { 
                                                return this.$parent.colors.primary ? this.$parent.colors.primary['{{ $shade }}'] : '#3b82f6';
                                            },
                                            set colorValue(value) {
                                                this.$parent.updateColor('primary.{{ $shade }}', value);
                                            }
                                        }">
                                            <div class="flex-shrink-0">
                                                <input type="color" 
                                                       x-model="colorValue"
                                                       class="w-10 h-10 rounded border border-gray-300 cursor-pointer">
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium text-gray-700">{{ $label }} ({{ $shade }})</label>
                                                <input type="text" 
                                                       x-model="colorValue"
                                                       class="mt-1 block w-full text-sm font-mono border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                       placeholder="#3b82f6">
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Preview -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Live Preview</h4>
                                    <div class="p-6 bg-gray-50 rounded-lg space-y-4">
                                        <div class="flex space-x-3">
                                            <button type="button" 
                                                    class="px-4 py-2 text-white rounded-md text-sm font-medium transition-colors"
                                                    :style="`background-color: ${colors.primary['500']}; border-color: ${colors.primary['500']}`"
                                                    @mouseover="$event.target.style.backgroundColor = colors.primary['600']"
                                                    @mouseout="$event.target.style.backgroundColor = colors.primary['500']">
                                                Primary Button
                                            </button>
                                            <button type="button" 
                                                    class="px-4 py-2 border rounded-md text-sm font-medium transition-colors bg-white"
                                                    :style="`border-color: ${colors.primary['500']}; color: ${colors.primary['600']}`"
                                                    @mouseover="$event.target.style.backgroundColor = colors.primary['50'] || '#eff6ff'"
                                                    @mouseout="$event.target.style.backgroundColor = 'white'">
                                                Secondary Button
                                            </button>
                                        </div>
                                        <div class="p-4 bg-white rounded-lg border border-gray-200">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <div class="w-3 h-3 rounded-full" 
                                                     :style="`background-color: ${colors.primary['500']}`"></div>
                                                <h5 class="text-sm font-medium text-gray-900">Sample Card</h5>
                                            </div>
                                            <p class="text-sm text-gray-600">This shows how your brand colors will appear in the interface.</p>
                                            <div class="mt-3 pt-3 border-t border-gray-100">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :style="`background-color: ${colors.primary['100'] || colors.primary['500'] + '20'}; color: ${colors.primary['800'] || colors.primary['700']}`">
                                                    Status Badge
                                                </span>
                                            </div>
                                            <div class="mt-3 flex items-center space-x-2">
                                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 rounded-full transition-all duration-300" 
                                                         :style="`background-color: ${colors.primary['500']}; width: 65%`"></div>
                                                </div>
                                                <span class="text-xs text-gray-500">65%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-6 flex items-center space-x-3">
                                <button type="button" 
                                        @click="saveColors()"
                                        :disabled="saving"
                                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 dark:disabled:bg-primary-300 text-white rounded-md text-sm font-medium transition-colors">
                                    <span x-show="!saving">Save Colors</span>
                                    <span x-show="saving">Saving...</span>
                                </button>
                                <button type="button" 
                                        @click="resetColors()"
                                        class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-md text-sm font-medium transition-colors">
                                    Reset to Default
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Preferences Tab -->
            <div x-show="activeTab === 'system'" x-transition>
                <div class="space-y-6">
                    <!-- Feature Toggles -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">System Features</h3>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_client_portal"
                                       value="1"
                                       {{ old('enable_client_portal', $settings['enable_client_portal'] ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Client Portal</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_ticketing"
                                       value="1"
                                       {{ old('enable_ticketing', $settings['enable_ticketing'] ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Ticketing System</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_projects"
                                       value="1"
                                       {{ old('enable_projects', $settings['enable_projects'] ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Project Management</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_invoicing"
                                       value="1"
                                       {{ old('enable_invoicing', $settings['enable_invoicing'] ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Invoicing</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="enable_assets"
                                       value="1"
                                       {{ old('enable_assets', $settings['enable_assets'] ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Asset Management</span>
                            </label>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">System Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="session_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Session Timeout (minutes)
                                </label>
                                <input type="number" 
                                       id="session_timeout"
                                       name="session_timeout"
                                       value="{{ old('session_timeout', $settings['session_timeout'] ?? 30) }}"
                                       min="5"
                                       max="1440"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                       placeholder="30">
                            </div>
                            <div>
                                <label for="max_file_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Max Upload Size (MB)
                                </label>
                                <input type="number" 
                                       id="max_file_size"
                                       name="max_file_size"
                                       value="{{ old('max_file_size', $settings['max_file_size'] ?? 10) }}"
                                       min="1"
                                       max="100"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                       placeholder="10">
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
                    class="px-4 py-2 bg-blue-600 dark:bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save Changes
            </button>
        </div>
        
        <!-- Required Hidden Fields for Form Validation -->
        <input type="hidden" name="company_language" value="{{ old('company_language', $settings['company_language'] ?? 'en') }}">
        <input type="hidden" name="company_currency" value="{{ old('company_currency', $settings['company_currency'] ?? 'USD') }}">
        <input type="hidden" name="theme" value="{{ old('theme', $settings['theme'] ?? 'blue') }}">
        <input type="hidden" name="start_page" value="{{ old('start_page', $settings['start_page'] ?? 'dashboard') }}">
        <input type="hidden" name="timezone" value="{{ old('timezone', $settings['timezone'] ?? 'UTC') }}">
        <input type="hidden" name="date_format" value="{{ old('date_format', $settings['date_format'] ?? 'Y-m-d') }}">
    </form>

    <!-- Import Modal -->
    @include('settings.partials.import-modal')
</div>
@endsection

@push('scripts')
<script>
// Import modal functionality
window.addEventListener('alpine:init', () => {
    Alpine.data('importModal', () => ({
        selectedFile: null,
        selectedFileName: '',
        dragOver: false,
        importing: false,
        
        handleFileSelect(event) {
            const file = event.target.files[0];
            this.validateAndSetFile(file);
        },
        
        handleFileDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            this.validateAndSetFile(file);
        },
        
        validateAndSetFile(file) {
            if (!file) return;
            
            if (!file.name.endsWith('.json')) {
                alert('Please select a valid JSON file');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB');
                return;
            }
            
            this.selectedFile = file;
            this.selectedFileName = file.name;
        },
        
        clearFileSelection() {
            this.selectedFile = null;
            this.selectedFileName = '';
            const fileInput = document.getElementById('settings_file');
            if (fileInput) fileInput.value = '';
        }
    }));
    
    // Color Customizer Component
    window.colorCustomizer = () => ({
        colors: @json($companyColors),
        currentPreset: 'blue',
        saving: false,
        
        init() {
            // Determine current preset based on colors
            this.detectCurrentPreset();
            // Initialize CSS properties
            this.updateCssProperties();
            
            // Watch for changes to colors and update CSS
            this.$watch('colors', () => {
                console.log('Colors changed, updating CSS...');
                this.updateCssProperties();
            }, { deep: true });
        },
        
        detectCurrentPreset() {
            const presets = @json($colorPresets);
            for (const [presetName, presetColors] of Object.entries(presets)) {
                if (this.colors.primary['500'] === presetColors.primary['500']) {
                    this.currentPreset = presetName;
                    break;
                }
            }
        },
        
        updateColor(path, value) {
            // Validate hex color
            if (!/^#[0-9A-Fa-f]{6}$/.test(value)) {
                return;
            }
            
            const keys = path.split('.');
            let obj = this.colors;
            for (let i = 0; i < keys.length - 1; i++) {
                if (!obj[keys[i]]) {
                    obj[keys[i]] = {};
                }
                obj = obj[keys[i]];
            }
            obj[keys[keys.length - 1]] = value;
            
            // Force reactivity by reassigning the colors object
            this.colors = { ...this.colors };
            
            // Update CSS custom properties for global application
            this.updateCssProperties();
            
            // Reset current preset since we're customizing
            this.currentPreset = 'custom';
        },
        
        updateCssProperties() {
            console.log('Updating CSS properties with colors:', this.colors);
            const root = document.documentElement;
            let propertiesSet = 0;
            
            for (const [colorName, shades] of Object.entries(this.colors)) {
                console.log(`Processing color ${colorName}:`, shades);
                if (typeof shades === 'object' && shades !== null) {
                    for (const [shade, value] of Object.entries(shades)) {
                        const propertyName = `--${colorName}-${shade}`;
                        console.log(`Setting ${propertyName} to ${value}`);
                        root.style.setProperty(propertyName, value);
                        propertiesSet++;
                    }
                }
            }
            
            console.log(`Total CSS properties set: ${propertiesSet}`);
            
            // Verify the properties were actually set
            console.log('Current --primary-500:', getComputedStyle(root).getPropertyValue('--primary-500'));
        },
        
        async applyPreset(presetName) {
            console.log('Applying preset:', presetName);
            console.log('Current colors before:', JSON.parse(JSON.stringify(this.colors)));
            this.currentPreset = presetName;
            
            try {
                const response = await fetch('{{ route("settings.colors.preset") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ preset: presetName })
                });
                
                const data = await response.json();
                console.log('Preset response data:', data);
                
                if (data.success) {
                    console.log('New colors from server:', data.colors);
                    
                    // Force a complete replacement of the colors object
                    this.colors = JSON.parse(JSON.stringify(data.colors));
                    console.log('Colors after assignment:', JSON.parse(JSON.stringify(this.colors)));
                    
                    // Update CSS properties immediately
                    this.updateCssProperties();
                    
                    // Force Alpine to detect the change and update all reactive elements
                    this.$nextTick(() => {
                        console.log('After nextTick, colors are:', this.colors);
                        // Force another CSS update to ensure everything is synced
                        this.updateCssProperties();
                        
                        // Trigger a change event to ensure color inputs update
                        this.$el.dispatchEvent(new CustomEvent('colors-updated', { 
                            detail: { colors: this.colors } 
                        }));
                    });
                    
                    // Show success message
                    this.showMessage('Color preset applied successfully!', 'success');
                } else {
                    this.showMessage(data.message || 'Failed to apply preset', 'error');
                }
            } catch (error) {
                console.error('Error applying preset:', error);
                this.showMessage('Failed to apply preset', 'error');
            }
        },
        
        async saveColors() {
            console.log('Saving colors:', this.colors);
            this.saving = true;
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                console.log('CSRF token found:', !!csrfToken);
                
                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }
                
                const url = '{{ route("settings.colors.update") }}';
                console.log('Sending request to:', url);
                
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ colors: this.colors })
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', Object.fromEntries(response.headers.entries()));
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Response error:', errorText);
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success) {
                    this.showMessage('Colors saved successfully!', 'success');
                } else {
                    this.showMessage(data.message || 'Failed to save colors', 'error');
                }
            } catch (error) {
                console.error('Error saving colors:', error);
                this.showMessage(`Failed to save colors: ${error.message}`, 'error');
            } finally {
                this.saving = false;
            }
        },
        
        async resetColors() {
            if (!confirm('Are you sure you want to reset colors to default? This will undo all customizations.')) {
                return;
            }
            
            try {
                const response = await fetch('{{ route("settings.colors.reset") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.colors = data.colors;
                    this.currentPreset = 'blue';
                    this.updateCssProperties();
                    this.showMessage('Colors reset to default successfully!', 'success');
                } else {
                    this.showMessage(data.message || 'Failed to reset colors', 'error');
                }
            } catch (error) {
                console.error('Error resetting colors:', error);
                this.showMessage('Failed to reset colors', 'error');
            }
        },
        
        showMessage(message, type) {
            // Create a simple toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-4 py-2 rounded-md text-white text-sm font-medium z-50 transition-opacity ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }
    });
});
</script>
@endpush
