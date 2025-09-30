<div>
    <flux:container>
        <!-- Breadcrumb -->
        <flux:breadcrumbs class="mb-6">
            <flux:breadcrumbs.item href="{{ route('settings.index') }}" icon="home">
                Settings
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('settings.domain.index', 'communication') }}">
                Communication
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>
                Email Configuration
            </flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <!-- Header -->
        <flux:card class="mb-6">
            <flux:heading size="xl">Email Configuration</flux:heading>
            <flux:text class="mt-2">Configure email sending settings and providers</flux:text>
        </flux:card>

        <!-- Success Message -->
        @if (session()->has('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="font-medium text-green-800 dark:text-green-200">{{ session('success') }}</div>
            </div>
        @endif

        <!-- Error Message -->
        @if (session()->has('error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="font-medium text-red-800 dark:text-red-200">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Info Message -->
        @if (session()->has('info'))
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="font-medium text-blue-800 dark:text-blue-200">{{ session('info') }}</div>
            </div>
        @endif

        <!-- Test Result Messages -->
        @if (session()->has('test_success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="flex items-center gap-2 text-green-800 dark:text-green-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <strong>{{ session('test_success') }}</strong>
                </div>
            </div>
        @endif

        @if (session()->has('test_error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-center gap-2 text-red-800 dark:text-red-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <strong>{{ session('test_error') }}</strong>
                </div>
            </div>
        @endif

        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="font-medium text-red-800 dark:text-red-200 mb-2">Please correct the following errors:</div>
                <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Settings Form -->
        <flux:card>
            <form wire:submit="save" class="space-y-6">
                <!-- Mail Driver Selection -->
                <flux:field>
                    <flux:label>Mail Driver</flux:label>
                    <select wire:model.live="driver" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="smtp">SMTP</option>
                        <option value="smtp2go">SMTP2GO</option>
                        <option value="mailgun">Mailgun</option>
                        <option value="sendgrid">SendGrid</option>
                        <option value="ses">Amazon SES</option>
                        <option value="postmark">Postmark</option>
                        <option value="log">Log (Testing)</option>
                    </select>
                    @error('driver') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>

                <!-- SMTP Settings -->
                @if($driver === 'smtp')
                    <div>
                        <flux:heading size="md" class="mb-4">SMTP Configuration</flux:heading>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label>SMTP Host</flux:label>
                                    <flux:input type="text" wire:model="smtp_host" placeholder="smtp.mailgun.org" />
                                    @error('smtp_host') <flux:error>{{ $message }}</flux:error> @enderror
                                </flux:field>
                                
                                <flux:field>
                                    <flux:label>SMTP Port</flux:label>
                                    <flux:input type="number" wire:model="smtp_port" placeholder="587" />
                                    @error('smtp_port') <flux:error>{{ $message }}</flux:error> @enderror
                                </flux:field>
                                
                                <flux:field>
                                    <flux:label>SMTP Username</flux:label>
                                    <flux:input type="text" wire:model="smtp_username" />
                                    @error('smtp_username') <flux:error>{{ $message }}</flux:error> @enderror
                                </flux:field>
                                
                                <flux:field>
                                    <flux:label>SMTP Password</flux:label>
                                    <flux:input type="password" wire:model="smtp_password" placeholder="{{ !empty($smtp_password) ? 'Password is set' : 'Enter password' }}" />
                                    @error('smtp_password') <flux:error>{{ $message }}</flux:error> @enderror
                                </flux:field>
                                
                                <flux:field>
                                    <flux:label>Encryption</flux:label>
                                    <select wire:model="smtp_encryption" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800">
                                        <option value="none">None</option>
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                    </select>
                                    @error('smtp_encryption') <flux:error>{{ $message }}</flux:error> @enderror
                                </flux:field>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- SMTP2GO Settings -->
                @if($driver === 'smtp2go')
                    <div>
                        <flux:heading size="md" class="mb-4">SMTP2GO Configuration</flux:heading>
                        
                        <div class="space-y-6">
                            <flux:field>
                                <flux:label>SMTP2GO API Key</flux:label>
                                <flux:input type="password" wire:model="api_key" placeholder="{{ !empty($api_key) ? 'API key is set' : 'Enter SMTP2GO API key' }}" />
                                <flux:text size="xs" variant="muted" class="mt-1">You can get your API key from your SMTP2GO dashboard</flux:text>
                                @error('api_key') <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>
                            
                            <flux:card>
                                <flux:heading size="sm">About SMTP2GO</flux:heading>
                                <flux:text size="sm" class="mt-2">SMTP2GO provides reliable email delivery with:</flux:text>
                                <ul class="list-disc list-inside mt-2 text-sm">
                                    <li>High deliverability rates</li>
                                    <li>Real-time analytics and tracking</li>
                                    <li>Dedicated IPs available</li>
                                    <li>24/7 support</li>
                                </ul>
                            </flux:card>
                        </div>
                    </div>
                @endif

                <!-- API Settings for other providers -->
                @if(in_array($driver, ['mailgun', 'sendgrid', 'ses', 'postmark']))
                    <div>
                        <flux:heading size="md" class="mb-4">API Configuration</flux:heading>
                        
                        <div class="space-y-6">
                            <flux:field>
                                <flux:label>API Key</flux:label>
                                <flux:input type="password" wire:model="api_key" placeholder="{{ !empty($api_key) ? 'API key is set' : 'Enter API key' }}" />
                                @error('api_key') <flux:error>{{ $message }}</flux:error> @enderror
                            </flux:field>
                            
                            @if(in_array($driver, ['mailgun', 'ses']))
                                <flux:field>
                                    <flux:label>{{ $driver === 'mailgun' ? 'Domain' : 'Region' }}</flux:label>
                                    <flux:input type="text" wire:model="api_domain" placeholder="{{ $driver === 'ses' ? 'us-east-1' : 'mg.yourdomain.com' }}" />
                                    @error('api_domain') <flux:error>{{ $message }}</flux:error> @enderror
                                </flux:field>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Sender Information -->
                <flux:separator class="my-6" />
                
                <flux:heading size="md" class="mb-4">Sender Information</flux:heading>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label required>From Name</flux:label>
                            <flux:input type="text" wire:model="from_name" required />
                            @error('from_name') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>
                        
                        <flux:field>
                            <flux:label required>From Email</flux:label>
                            <flux:input type="email" wire:model="from_email" required />
                            @error('from_email') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>
                    </div>
                    
                    <flux:field>
                        <flux:label>Reply-To Email (optional)</flux:label>
                        <flux:input type="email" wire:model="reply_to" />
                        @error('reply_to') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                </div>

                <!-- Email Features -->
                <flux:separator class="my-6" />
                
                <flux:heading size="md" class="mb-4">Email Features</flux:heading>
                
                <div class="space-y-6">
                    <div class="space-y-3">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="track_opens" class="rounded border-zinc-300 dark:border-zinc-600">
                            <span>Track email opens</span>
                        </label>
                        
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="track_clicks" class="rounded border-zinc-300 dark:border-zinc-600">
                            <span>Track link clicks</span>
                        </label>
                        
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="auto_retry_failed" class="rounded border-zinc-300 dark:border-zinc-600">
                            <span>Automatically retry failed emails</span>
                        </label>
                    </div>
                    
                    <flux:field>
                        <flux:label>Maximum Retry Attempts</flux:label>
                        <flux:input type="number" wire:model="max_retry_attempts" min="1" max="10" />
                        @error('max_retry_attempts') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                </div>

                <!-- Test Email -->
                <flux:separator class="my-6" />
                
                <flux:heading size="md" class="mb-4">Test Configuration</flux:heading>
                
                <flux:field>
                    <flux:label>Test Email Address</flux:label>
                    <flux:input type="email" wire:model="test_email" />
                    <flux:text size="xs" variant="muted" class="mt-1">A test email will be sent to this address when you click "Test Configuration"</flux:text>
                    @error('test_email') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
                
                <flux:separator />
                
                <div class="flex items-center justify-between">
                    <flux:button.group>
                        <flux:button type="button" icon="beaker" wire:click="testConfiguration">
                            Test Configuration
                        </flux:button>
                        
                        <flux:button type="button" icon="arrow-uturn-left" wire:click="resetToDefaults" wire:confirm="Are you sure you want to reset these settings to defaults?">
                            Reset to Defaults
                        </flux:button>
                    </flux:button.group>
                    
                    <flux:button.group>
                        <flux:button variant="ghost" href="{{ route('settings.domain.index', 'communication') }}">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary" icon="check">
                            Save Settings
                        </flux:button>
                    </flux:button.group>
                </div>
            </form>
        </flux:card>
    </flux:container>
</div>
