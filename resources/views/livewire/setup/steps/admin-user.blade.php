<flux:card class="space-y-6">
    <div>
        <flux:heading size="lg" class="flex items-center">
            <svg class="h-5 w-5 text-blue-500 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Administrator Account
        </flux:heading>
        <flux:text class="mt-2">
            Create your administrator account. You'll use this to log in and manage your ERP system.
        </flux:text>
    </div>

    <div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Full Name -->
            <flux:input 
                wire:model.defer="admin_name" 
                label="Full Name" 
                required
                placeholder="John Smith"
                :invalid="$errors->has('admin_name')" />

            <!-- Email Address -->
            <flux:input 
                wire:model.defer="admin_email" 
                type="email" 
                label="Email Address" 
                required
                placeholder="admin@yourcompany.com"
                :invalid="$errors->has('admin_email')" />

            <!-- Password -->
            <flux:input 
                wire:model.defer="admin_password" 
                type="password" 
                label="Password" 
                required
                placeholder="Choose a strong password"
                :invalid="$errors->has('admin_password')" />

            <!-- Confirm Password -->
            <flux:input 
                wire:model.defer="admin_password_confirmation" 
                type="password" 
                label="Confirm Password" 
                required
                placeholder="Confirm your password"
                :invalid="$errors->has('admin_password_confirmation')" />
        </div>

        <!-- Security Options -->
        <div class="mt-8">
            <flux:heading size="sm" class="mb-4">Security Options (Optional)</flux:heading>
            <div class="space-y-4">
                <!-- Two-Factor Authentication -->
                <div class="flex items-start space-x-3">
                    <flux:checkbox wire:model.defer="enable_two_factor" id="enable_two_factor" />
                    <div class="flex-1">
                        <label for="enable_two_factor" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Enable Two-Factor Authentication
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Require 2FA for administrator account login</p>
                    </div>
                </div>

                <!-- Audit Logging -->
                <div class="flex items-start space-x-3">
                    <flux:checkbox wire:model.defer="enable_audit_logging" id="enable_audit_logging" />
                    <div class="flex-1">
                        <label for="enable_audit_logging" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Enable Audit Logging
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Track all system changes and user actions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Requirements -->
        <div class="mt-8 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
            <h4 class="text-sm font-medium text-amber-900 dark:text-amber-100 mb-2">
                <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                Password Requirements
            </h4>
            <ul class="text-sm text-amber-700 dark:text-amber-200 space-y-1">
                <li>• Minimum 8 characters</li>
                <li>• At least one uppercase letter</li>
                <li>• At least one number</li>
                <li>• At least one special character</li>
            </ul>
        </div>
    </div>
</flux:card>