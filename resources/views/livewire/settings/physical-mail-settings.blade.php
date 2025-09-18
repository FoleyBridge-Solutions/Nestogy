<div>
    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">PostGrid Integration Settings</flux:heading>
            <flux:text class="mt-2">Configure your PostGrid API settings for sending physical mail</flux:text>
        </div>

        <form wire:submit="save" class="space-y-6">
            <!-- API Configuration -->
            <flux:fieldset>
                <flux:legend>API Configuration</flux:legend>
                
                <div class="space-y-4">
                    <!-- Environment & Mode Display -->
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:label>Environment & API Mode</flux:label>
                            <flux:text size="sm" class="text-zinc-500">
                                @if($environment === 'production')
                                    Production environment - {{ $isTestMode ? 'using TEST API' : 'using LIVE API' }}
                                @else
                                    {{ ucfirst($environment) }} environment - automatically using TEST API
                                @endif
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge variant="{{ $environment === 'production' ? 'green' : 'zinc' }}">
                                {{ strtoupper($environment) }}
                            </flux:badge>
                            <flux:badge variant="{{ $isTestMode ? 'amber' : 'green' }}">
                                {{ $isTestMode ? 'TEST API' : 'LIVE API' }}
                            </flux:badge>
                        </div>
                    </div>

                    @if($environment === 'production')
                        <flux:checkbox wire:model.live="forceTestMode" label="Force test mode in production (no real mail will be sent)" />
                    @endif

                    <!-- Test API Key -->
                    <flux:field>
                        <flux:label>Test API Key <span class="text-red-500">*</span></flux:label>
                        <flux:input 
                            type="password" 
                            wire:model.defer="testKey"
                            placeholder="test_sk_..."
                        />
                        <flux:error name="testKey" />
                        <flux:description>
                            Your PostGrid test API key (starts with test_sk_)
                        </flux:description>
                    </flux:field>
                    
                    <!-- Live API Key -->
                    <flux:field>
                        <flux:label>Live API Key</flux:label>
                        <flux:input 
                            type="password" 
                            wire:model.defer="liveKey"
                            placeholder="live_sk_... (optional)"
                        />
                        <flux:error name="liveKey" />
                        <flux:description>
                            Your PostGrid live API key for production use (starts with live_sk_)
                        </flux:description>
                    </flux:field>
                    
                    <!-- Webhook Secret -->
                    <flux:field>
                        <flux:label>Webhook Secret</flux:label>
                        <flux:input 
                            type="password" 
                            wire:model.defer="webhookSecret"
                            placeholder="Optional webhook secret"
                        />
                        <flux:description>
                            Secret for validating PostGrid webhook requests
                        </flux:description>
                    </flux:field>
                    
                    <!-- Webhook URL -->
                    <flux:field>
                        <flux:label>Webhook URL</flux:label>
                        <flux:input 
                            value="{{ url('/api/webhooks/postgrid') }}" 
                            readonly
                        />
                        <flux:description>
                            Configure this URL in your PostGrid dashboard for webhook events
                        </flux:description>
                    </flux:field>
                </div>
            </flux:fieldset>
            
            <!-- Default From Address -->
            <flux:fieldset>
                <flux:legend>Default From Address</flux:legend>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Company Name <span class="text-red-500">*</span></flux:label>
                            <flux:input 
                                wire:model.defer="fromCompanyName"
                                placeholder="Your Company Name"
                            />
                            <flux:error name="fromCompanyName" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Contact Name</flux:label>
                            <flux:input 
                                wire:model.defer="fromContactName"
                                placeholder="John Doe (optional)"
                            />
                        </flux:field>
                    </div>
                    
                    <flux:field>
                        <flux:label>Address Line 1 <span class="text-red-500">*</span></flux:label>
                        <flux:input 
                            wire:model.defer="fromAddressLine1"
                            placeholder="123 Main Street"
                        />
                        <flux:error name="fromAddressLine1" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Address Line 2</flux:label>
                        <flux:input 
                            wire:model.defer="fromAddressLine2"
                            placeholder="Suite 100 (optional)"
                        />
                    </flux:field>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <flux:field>
                            <flux:label>City <span class="text-red-500">*</span></flux:label>
                            <flux:input 
                                wire:model.defer="fromCity"
                                placeholder="New York"
                            />
                            <flux:error name="fromCity" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>State <span class="text-red-500">*</span></flux:label>
                            <flux:input 
                                wire:model.defer="fromState"
                                placeholder="NY"
                                maxlength="2"
                                style="text-transform: uppercase"
                            />
                            <flux:error name="fromState" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>ZIP Code <span class="text-red-500">*</span></flux:label>
                            <flux:input 
                                wire:model.defer="fromZip"
                                placeholder="10001"
                            />
                            <flux:error name="fromZip" />
                        </flux:field>
                    </div>
                </div>
            </flux:fieldset>
            
            <!-- Default Mail Options -->
            <flux:fieldset>
                <flux:legend>Default Mail Options</flux:legend>
                
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Default Mailing Class</flux:label>
                        <flux:select wire:model.defer="defaultMailingClass">
                            <flux:select.option value="first_class">First Class Mail</flux:select.option>
                            <flux:select.option value="standard_class">Standard Class Mail</flux:select.option>
                        </flux:select>
                    </flux:field>
                    
                    <flux:checkbox wire:model.defer="defaultColorPrinting" label="Color printing by default" />
                    
                    <flux:checkbox wire:model.defer="defaultDoubleSided" label="Double-sided printing by default" />
                </div>
            </flux:fieldset>
            
            <!-- Usage Statistics -->
            <flux:fieldset>
                <flux:legend>Usage Statistics</flux:legend>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <flux:text size="xs" class="text-zinc-500">Total Sent</flux:text>
                        <flux:heading size="lg">{{ $stats['total'] }}</flux:heading>
                    </div>
                    <div>
                        <flux:text size="xs" class="text-zinc-500">This Month</flux:text>
                        <flux:heading size="lg">{{ $stats['month'] }}</flux:heading>
                    </div>
                    <div>
                        <flux:text size="xs" class="text-zinc-500">In Transit</flux:text>
                        <flux:heading size="lg">{{ $stats['pending'] }}</flux:heading>
                    </div>
                    <div>
                        <flux:text size="xs" class="text-zinc-500">Delivered</flux:text>
                        <flux:heading size="lg">{{ $stats['delivered'] }}</flux:heading>
                    </div>
                </div>
            </flux:fieldset>
            
            <!-- Test Connection -->
            <flux:fieldset>
                <flux:legend>Connection Test</flux:legend>
                
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text>Test PostGrid Connection</flux:text>
                        <flux:text size="sm" class="text-zinc-500">
                            Verify that your API keys are working correctly
                        </flux:text>
                    </div>
                    <flux:button type="button" variant="filled" wire:click="testConnection" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                        <span wire:loading wire:target="testConnection">Testing...</span>
                    </flux:button>
                </div>
                
                @if($testConnectionResult)
                    <div class="mt-4">
                        @if($testConnectionResult['success'])
                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center gap-2 text-green-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <strong>Connection successful!</strong>
                                </div>
                                <p class="mt-1 text-sm text-green-600">
                                    PostGrid API is working correctly in {{ $testConnectionResult['mode'] }} mode.
                                </p>
                            </div>
                        @else
                            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center gap-2 text-red-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    <strong>Connection failed</strong>
                                </div>
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $testConnectionResult['error'] ?? 'Unable to connect to PostGrid API' }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            </flux:fieldset>

            <!-- Save Button -->
            <div class="flex items-center gap-4 pt-4 border-t">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save Settings</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
                
                @if($hasChanges)
                    <flux:text size="sm" class="text-amber-600">
                        You have unsaved changes
                    </flux:text>
                @endif
            </div>
        </form>
    </flux:card>

    <!-- Success notification -->
    <div x-data="{ show: false }"
         x-on:saved.window="show = true; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition
         class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
        Settings saved successfully!
    </div>
</div>