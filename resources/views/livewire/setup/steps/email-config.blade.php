<flux:card class="space-y-6">
    <div>
        <flux:heading size="lg" class="flex items-center">
            <svg class="h-5 w-5 text-blue-500 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Email Configuration
        </flux:heading>
        <flux:text class="mt-2">
            Configure SMTP settings for sending emails. You can skip this step and configure it later.
        </flux:text>
    </div>

    <div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- SMTP Host -->
            <flux:input 
                wire:model.defer="smtp_host" 
                label="SMTP Host"
                placeholder="smtp.gmail.com"
                :invalid="$errors->has('smtp_host')" />

            <!-- SMTP Port -->
            <flux:input 
                wire:model.defer="smtp_port" 
                type="number"
                label="SMTP Port"
                placeholder="587"
                :invalid="$errors->has('smtp_port')" />

            <!-- Encryption -->
            <flux:select 
                wire:model.defer="smtp_encryption" 
                label="Encryption">
                <flux:select.option value="">None</flux:select.option>
                <flux:select.option value="tls">TLS</flux:select.option>
                <flux:select.option value="ssl">SSL</flux:select.option>
            </flux:select>

            <!-- Username -->
            <flux:input 
                wire:model.defer="smtp_username" 
                label="Username"
                placeholder="your-email@domain.com" />

            <!-- Password -->
            <flux:input 
                wire:model.defer="smtp_password" 
                type="password"
                label="Password / App Password"
                placeholder="Your email password or app password" />

            <!-- From Email -->
            <flux:input 
                wire:model.defer="mail_from_email" 
                type="email"
                label="From Email Address"
                placeholder="noreply@yourcompany.com" />

            <!-- From Name -->
            <flux:input 
                wire:model.defer="mail_from_name" 
                label="From Name"
                placeholder="Your Company Name" />

            <!-- Test SMTP Button -->
            <div class="col-span-2">
                @if(empty($smtp_host) || empty($smtp_port))
                    <button type="button" disabled class="mt-4 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                        Test Connection
                    </button>
                @else
                    <flux:button 
                        wire:click="testSmtpConnection" 
                        variant="secondary"
                        class="mt-4">
                        @if ($smtp_testing)
                            Testing Connection...
                        @else
                            Test SMTP Connection
                        @endif
                    </flux:button>
                @endif

                <!-- SMTP Test Results -->
                @if (!empty($smtp_test_message))
                    <div class="mt-4">
                        <flux:callout 
                            :variant="$smtp_test_success ? 'success' : 'danger'"
                            :icon="$smtp_test_success ? 'check-circle' : 'exclamation-triangle'">
                            {{ $smtp_test_message }}
                        </flux:callout>
                    </div>
                @endif
            </div>
        </div>

        <!-- Email Setup Help -->
        <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Email Setup Help
            </h4>
            <p class="text-sm text-blue-700 dark:text-blue-200">
                <strong>For Gmail:</strong> Use smtp.gmail.com:587 with TLS and generate an App Password.<br>
                <strong>For Office 365:</strong> Use smtp.office365.com:587 with TLS.
            </p>
        </div>
    </div>
</flux:card>