<div x-data="{ activeTab: 'company', showImportModal: false }" @open-import-modal.window="showImportModal = true">
    
    <form method="POST" action="{{ route('settings.general.update') }}">
        @csrf
        @method('PUT')
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'company'"
                        :class="{'border-primary-500 text-blue-600-600': activeTab === 'company', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'company'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Company Information
                </button>
                <button type="button" 
                        @click="activeTab = 'localization'"
                        :class="{'border-primary-500 text-blue-600-600': activeTab === 'localization', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'localization'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Localization
                </button>
                <button type="button" 
                        @click="activeTab = 'branding'"
                        :class="{'border-primary-500 text-blue-600 dark:text-blue-400-600': activeTab === 'branding', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'branding'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Branding & Colors
                </button>
                <button type="button" 
                        @click="activeTab = 'system'"
                        :class="{'border-primary-500 text-blue-600 dark:text-blue-400-600': activeTab === 'system', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'system'}"
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
                        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Company Name
                        </label>
                        <input type="text" 
                               id="company_name"
                               name="company_name"
                               value="{{ old('company_name', $company->name ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="Enter company name">
                    </div>

                    <!-- Business Phone -->
                    <div class="col-span-12-span-2 md:col-span-12-span-1">
                        <label for="business_phone" class="block text-sm font-medium text-gray-700 mb-1">
                            Business Phone
                        </label>
                        <input type="tel" 
                               id="business_phone"
                               name="business_phone"
                               value="{{ old('business_phone', $settings['business_phone'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="(555) 123-4567">
                    </div>

                    <!-- Business Email -->
                    <div class="col-span-12-span-2 md:col-span-12-span-1">
                        <label for="business_email" class="block text-sm font-medium text-gray-700 mb-1">
                            Business Email
                        </label>
                        <input type="email" 
                               id="business_email"
                               name="business_email"
                               value="{{ old('business_email', $settings['business_email'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="info@company.com">
                    </div>

                    <!-- Tax ID -->
                    <div class="col-span-12-span-2 md:col-span-12-span-1">
                        <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Tax ID / EIN
                        </label>
                        <input type="text" 
                               id="tax_id"
                               name="tax_id"
                               value="{{ old('tax_id', $settings['tax_id'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="12-3456789">
                    </div>

                    <!-- Website -->
                    <div class="col-span-12-span-2">
                        <label for="website" class="block text-sm font-medium text-gray-700 mb-1">
                            Website
                        </label>
                        <input type="url" 
                               id="website"
                               name="website"
                               value="{{ old('website', $settings['website'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="https://www.example.com">
                    </div>

                    <!-- Business Address -->
                    <div class="col-span-12-span-2">
                        <label for="business_address" class="block text-sm font-medium text-gray-700 mb-1">
                            Business Address
                        </label>
                        <textarea id="business_address"
                                  name="business_address"
                                  rows="3"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                  placeholder="123 Main St, Suite 100">{{ old('business_address', $settings['business_address'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Localization Tab -->
            <div x-show="activeTab === 'localization'" x-transition>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Timezone -->
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">
                            Timezone
                        </label>
                        <select id="timezone"
                                name="timezone"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @foreach($timezones as $value => $label)
                                <option value="{{ $value }}" {{ old('timezone', $settings['timezone'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Format -->
                    <div>
                        <label for="date_format" class="block text-sm font-medium text-gray-700 mb-1">
                            Date Format
                        </label>
                        <select id="date_format"
                                name="date_format"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @foreach($dateFormats as $value => $label)
                                <option value="{{ $value }}" {{ old('date_format', $settings['date_format'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Time Format -->
                    <div>
                        <label for="time_format" class="block text-sm font-medium text-gray-700 mb-1">
                            Time Format
                        </label>
                        <select id="time_format"
                                name="time_format"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="12" {{ old('time_format', $settings['time_format'] ?? '12') == '12' ? 'selected' : '' }}>12 Hour (1:30 PM)</option>
                            <option value="24" {{ old('time_format', $settings['time_format'] ?? '12') == '24' ? 'selected' : '' }}>24 Hour (13:30)</option>
                        </select>
                    </div>

                    <!-- Currency -->
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">
                            Currency
                        </label>
                        <select id="currency"
                                name="currency"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @foreach($currencies as $code => $name)
                                <option value="{{ $code }}" {{ old('currency', $settings['currency'] ?? 'USD') == $code ? 'selected' : '' }}>{{ $code }} - {{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Language -->
                    <div>
                        <label for="language" class="block text-sm font-medium text-gray-700 mb-1">
                            Language
                        </label>
                        <select id="language"
                                name="language"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="en" {{ old('language', $settings['language'] ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                            <option value="es" {{ old('language', $settings['language'] ?? 'en') == 'es' ? 'selected' : '' }}>Spanish</option>
                            <option value="fr" {{ old('language', $settings['language'] ?? 'en') == 'fr' ? 'selected' : '' }}>French</option>
                            <option value="de" {{ old('language', $settings['language'] ?? 'en') == 'de' ? 'selected' : '' }}>German</option>
                        </select>
                    </div>

                    <!-- Fiscal Year Start -->
                    <div>
                        <label for="fiscal_year_start" class="block text-sm font-medium text-gray-700 mb-1">
                            Fiscal Year Start
                        </label>
                        <select id="fiscal_year_start"
                                name="fiscal_year_start"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
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

                        <!-- Action Buttons -->
                        <div class="mt-6 flex items-center space-x-3">
                            <button type="button" 
                                    @click="saveColors()"
                                    :disabled="saving"
                                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:bg-blue-600-400 text-white rounded-md text-sm font-medium transition-colors">
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
                                <label for="session_timeout" class="block text-sm font-medium text-gray-700 mb-1">
                                    Session Timeout (minutes)
                                </label>
                                <input type="number" 
                                       id="session_timeout"
                                       name="session_timeout"
                                       value="{{ old('session_timeout', $settings['session_timeout'] ?? 30) }}"
                                       min="5"
                                       max="1440"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                       placeholder="30">
                            </div>
                            <div>
                                <label for="max_file_size" class="block text-sm font-medium text-gray-700 mb-1">
                                    Max Upload Size (MB)
                                </label>
                                <input type="number" 
                                       id="max_file_size"
                                       name="max_file_size"
                                       value="{{ old('max_file_size', $settings['max_file_size'] ?? 10) }}"
                                       min="1"
                                       max="100"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
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
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
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
</div>
