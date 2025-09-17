@extends('layouts.settings')

@section('title', 'Physical Mail Settings')

@section('settings-content')
<div class="container-fluid">
    <flux:card>
        <flux:card.header>
            <flux:card.title>PostGrid Integration Settings</flux:card.title>
            <flux:card.description>
                Configure your PostGrid API settings for sending physical mail
            </flux:card.description>
        </flux:card.header>
        
        <flux:card.body class="space-y-6">
            <!-- API Configuration -->
            <flux:fieldset>
                <flux:legend>API Configuration</flux:legend>
                
                <div class="space-y-4">
                    @php
                        $testMode = config('physical_mail.postgrid.test_mode');
                        $testKey = config('physical_mail.postgrid.test_key');
                        $hasLiveKey = !empty(config('physical_mail.postgrid.live_key'));
                    @endphp
                    
                    <!-- Mode Toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:label>API Mode</flux:label>
                            <flux:text size="sm" class="text-zinc-500">
                                Test mode uses test API keys and doesn't send real mail
                            </flux:text>
                        </div>
                        <flux:switch :value="$testMode" disabled />
                    </div>
                    
                    <!-- Test API Key -->
                    <flux:field>
                        <flux:label>Test API Key</flux:label>
                        <flux:input 
                            type="password" 
                            value="{{ Str::limit($testKey, 20, '...') }}" 
                            disabled
                        />
                        <flux:description>
                            Current test key: {{ Str::limit($testKey, 10) }}...
                        </flux:description>
                    </flux:field>
                    
                    <!-- Live API Key -->
                    <flux:field>
                        <flux:label>Live API Key</flux:label>
                        <flux:input 
                            type="password" 
                            placeholder="{{ $hasLiveKey ? 'Key is set' : 'Not configured' }}"
                            disabled
                        />
                        <flux:description>
                            @if($hasLiveKey)
                                Live key is configured. Contact admin to update.
                            @else
                                Live key not set. Add POSTGRID_LIVE_KEY to .env file.
                            @endif
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
            
            <!-- Default Settings -->
            <flux:fieldset>
                <flux:legend>Default Mail Settings</flux:legend>
                
                <div class="space-y-4">
                    <!-- From Address -->
                    <flux:field>
                        <flux:label>Default From Address</flux:label>
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input 
                                placeholder="Company Name"
                                value="{{ config('nestogy.company_name') }}"
                                disabled
                            />
                            <flux:input 
                                placeholder="Contact Name"
                                disabled
                            />
                        </div>
                    </flux:field>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input 
                            placeholder="Address Line 1"
                            value="{{ config('nestogy.company_address_line1') }}"
                            disabled
                        />
                        <flux:input 
                            placeholder="Address Line 2"
                            disabled
                        />
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <flux:input 
                            placeholder="City"
                            value="{{ config('nestogy.company_city') }}"
                            disabled
                        />
                        <flux:input 
                            placeholder="State"
                            value="{{ config('nestogy.company_state') }}"
                            maxlength="2"
                            disabled
                        />
                        <flux:input 
                            placeholder="ZIP"
                            value="{{ config('nestogy.company_postal_code') }}"
                            disabled
                        />
                    </div>
                    
                    <!-- Default Options -->
                    <flux:field>
                        <flux:label>Default Mailing Class</flux:label>
                        <flux:select disabled>
                            <flux:option value="first_class" selected>First Class</flux:option>
                            <flux:option value="standard_class">Standard Class</flux:option>
                        </flux:select>
                    </flux:field>
                    
                    <flux:checkbox checked disabled>
                        Color printing by default
                    </flux:checkbox>
                    
                    <flux:checkbox disabled>
                        Double-sided printing by default
                    </flux:checkbox>
                </div>
            </flux:fieldset>
            
            <!-- Usage Statistics -->
            <flux:fieldset>
                <flux:legend>Usage Statistics</flux:legend>
                
                @php
                    $stats = [
                        'total' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::count(),
                        'month' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::whereMonth('created_at', now()->month)->count(),
                        'pending' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::whereIn('status', ['pending', 'processing'])->count(),
                        'delivered' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::where('status', 'delivered')->count(),
                    ];
                @endphp
                
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
                    <flux:button variant="secondary" onclick="testConnection()">
                        Test Connection
                    </flux:button>
                </div>
                
                <div id="test-result" class="hidden mt-4">
                    <!-- Test results will appear here -->
                </div>
            </flux:fieldset>
        </flux:card.body>
        
        <flux:card.footer>
            <flux:text size="sm" class="text-zinc-500">
                Note: Most settings are configured in your .env file for security. Contact your system administrator to make changes.
            </flux:text>
        </flux:card.footer>
    </flux:card>
</div>

@push('scripts')
<script>
function testConnection() {
    const resultDiv = document.getElementById('test-result');
    resultDiv.classList.remove('hidden');
    resultDiv.innerHTML = '<flux:spinner /> Testing connection...';
    
    fetch('/api/physical-mail/test-connection', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-2 text-green-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <strong>Connection successful!</strong>
                    </div>
                    <p class="mt-1 text-sm text-green-600">PostGrid API is working correctly in ${data.mode} mode.</p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-2 text-red-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <strong>Connection failed</strong>
                    </div>
                    <p class="mt-1 text-sm text-red-600">${data.error || 'Unable to connect to PostGrid API'}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700">Error testing connection: ${error.message}</p>
            </div>
        `;
    });
}
</script>
@endpush
@endsection