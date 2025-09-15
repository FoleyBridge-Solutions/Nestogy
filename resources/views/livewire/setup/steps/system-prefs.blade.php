<flux:card class="space-y-6">
    <div>
        <flux:heading size="lg" class="flex items-center">
            <svg class="h-5 w-5 text-blue-500 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            System Preferences
        </flux:heading>
        <flux:text class="mt-2">
            Configure system-wide preferences and regional settings for your ERP system.
        </flux:text>
    </div>

    <div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Timezone -->
            <flux:select 
                wire:model.defer="timezone" 
                label="Timezone" 
                required
                :invalid="$errors->has('timezone')">
                @foreach(\App\Models\Setting::getAvailableTimezones() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <!-- Date Format -->
            <flux:select 
                wire:model.defer="date_format" 
                label="Date Format">
                @foreach(\App\Models\Setting::getAvailableDateFormats() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <!-- Theme -->
            <flux:select 
                wire:model.defer="theme" 
                label="Theme">
                @foreach(\App\Models\Setting::getAvailableThemes() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <!-- Language -->
            <flux:select 
                wire:model.defer="company_language" 
                label="Language">
                @foreach(\App\Models\Setting::getAvailableLanguages() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <!-- Default Payment Terms -->
            <flux:input 
                wire:model.defer="default_net_terms" 
                type="number"
                min="0"
                max="365"
                label="Default Payment Terms (Days)"
                placeholder="30"
                :invalid="$errors->has('default_net_terms')" />

            <!-- Default Hourly Rate -->
            <flux:input 
                wire:model.defer="default_hourly_rate" 
                type="number"
                min="0"
                step="0.01"
                label="Default Hourly Rate ($)"
                placeholder="150.00"
                :invalid="$errors->has('default_hourly_rate')" />
        </div>

        <!-- Module Selection -->
        <div class="mt-8">
            <flux:heading size="sm" class="mb-4">Enable Modules</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Ticketing System -->
                <div class="flex items-start space-x-3">
                    <flux:checkbox wire:model.defer="modules.ticketing" id="module_ticketing" />
                    <div class="flex-1">
                        <label for="module_ticketing" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Ticketing System
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Support ticket management</p>
                    </div>
                </div>

                <!-- Invoicing -->
                <div class="flex items-start space-x-3">
                    <flux:checkbox wire:model.defer="modules.invoicing" id="module_invoicing" />
                    <div class="flex-1">
                        <label for="module_invoicing" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Invoicing
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Invoice and billing management</p>
                    </div>
                </div>

                <!-- Asset Management -->
                <div class="flex items-start space-x-3">
                    <flux:checkbox wire:model.defer="modules.assets" id="module_assets" />
                    <div class="flex-1">
                        <label for="module_assets" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Asset Management
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Track client assets and inventory</p>
                    </div>
                </div>

                <!-- Project Management -->
                <div class="flex items-start space-x-3">
                    <flux:checkbox wire:model.defer="modules.projects" id="module_projects" />
                    <div class="flex-1">
                        <label for="module_projects" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Project Management
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Manage projects and tasks</p>
                    </div>
                </div>

                <!-- Contract Management -->
                <div class="flex items-start space-x-3">
                    <flux:checkbox wire:model.defer="modules.contracts" id="module_contracts" />
                    <div class="flex-1">
                        <label for="module_contracts" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Contract Management
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Manage client contracts and SLAs</p>
                    </div>
                </div>

                <!-- Reporting -->
                <div class="flex items-start space-x-3">
                    <flux:checkbox wire:model.defer="modules.reporting" id="module_reporting" />
                    <div class="flex-1">
                        <label for="module_reporting" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Reporting
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Business intelligence and reports</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</flux:card>