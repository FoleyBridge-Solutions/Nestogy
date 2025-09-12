@extends('layouts.app')

@section('title', 'Email Provider Settings')

@section('content')
@php
    $sidebarContext = 'settings';
@endphp

<div class="container-fluid h-full flex flex-col">
    <!-- Header -->
    <flux:card class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Email Provider Configuration</flux:heading>
                <flux:text size="sm">Configure how your company connects to email services</flux:text>
            </div>
            <flux:button variant="ghost" size="sm" href="{{ route('settings.index') }}">
                <flux:icon.chevron-left class="w-4 h-4 mr-2" />
                Back to Settings
            </flux:button>
        </div>
    </flux:card>

    <!-- Current Configuration Status -->
    <flux:card class="mb-6">
        <flux:heading size="md" class="mb-4">Current Configuration</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-gray-50 rounded-lg">
                <flux:text size="sm" class="font-medium text-gray-700">Provider Type</flux:text>
                <flux:text class="text-lg font-semibold">
                    @if($company->email_provider_type === 'manual')
                        Manual Configuration
                    @elseif($company->email_provider_type === 'microsoft365')
                        Microsoft 365
                    @elseif($company->email_provider_type === 'google_workspace')
                        Google Workspace
                    @elseif($company->email_provider_type === 'exchange')
                        Exchange Server
                    @elseif($company->email_provider_type === 'custom_oauth')
                        Custom OAuth
                    @else
                        Not Configured
                    @endif
                </flux:text>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <flux:text size="sm" class="font-medium text-gray-700">OAuth Required</flux:text>
                <flux:text class="text-lg font-semibold">
                    @if(in_array($company->email_provider_type, ['microsoft365', 'google_workspace', 'custom_oauth']))
                        <span class="text-green-600">Yes</span>
                    @else
                        <span class="text-gray-600">No</span>
                    @endif
                </flux:text>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <flux:text size="sm" class="font-medium text-gray-700">Status</flux:text>
                <flux:text class="text-lg font-semibold">
                    @if($company->email_provider_type === 'manual')
                        <span class="text-green-600">Ready</span>
                    @elseif(in_array($company->email_provider_type, ['microsoft365', 'google_workspace']))
                        @if(!empty($currentConfig['client_id']) && !empty($currentConfig['client_secret']))
                            <span class="text-green-600">Configured</span>
                        @else
                            <span class="text-yellow-600">Incomplete</span>
                        @endif
                    @else
                        <span class="text-gray-600">Not Configured</span>
                    @endif
                </flux:text>
            </div>
        </div>
    </flux:card>

    <!-- Configuration Form -->
    <form method="POST" action="{{ route('settings.company-email-provider.update') }}" class="max-w-4xl mx-auto">
        @csrf
        @method('PUT')

        <!-- Provider Selection -->
        <flux:card class="mb-6">
            <flux:heading size="md" class="mb-4">Email Provider</flux:heading>

            <flux:field>
                <flux:label for="email_provider_type">Provider Type *</flux:label>
                <flux:select name="email_provider_type" id="email_provider_type" wire:model="email_provider_type" required>
                    @foreach($availableProviders as $key => $provider)
                        <option value="{{ $key }}" {{ $company->email_provider_type === $key ? 'selected' : '' }}>
                            {{ $provider['name'] }}
                        </option>
                    @endforeach
                </flux:select>
                <flux:description>
                    Choose how your company will connect to email services
                </flux:description>
            </flux:field>
        </flux:card>

        <!-- Microsoft 365 Configuration -->
        <div id="microsoft365-config" class="provider-config" style="display: {{ $company->email_provider_type === 'microsoft365' ? 'block' : 'none' }};">
            <flux:card class="mb-6">
                <flux:heading size="md" class="mb-4">Microsoft 365 Configuration</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label for="microsoft_client_id">Application (Client) ID *</flux:label>
                        <flux:input
                            type="text"
                            name="client_id"
                            id="microsoft_client_id"
                            value="{{ $currentConfig['client_id'] ?? '' }}"
                            placeholder="00000000-0000-0000-0000-000000000000"
                            required
                        />
                        <flux:description>
                            From your Azure AD app registration
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label for="microsoft_client_secret">Client Secret *</flux:label>
                        <flux:input
                            type="password"
                            name="client_secret"
                            id="microsoft_client_secret"
                            value="{{ $currentConfig['client_secret'] ?? '' }}"
                            placeholder="Your client secret"
                            required
                        />
                        <flux:description>
                            Keep this secret secure
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label for="microsoft_tenant_id">Tenant ID</flux:label>
                        <flux:input
                            type="text"
                            name="tenant_id"
                            id="microsoft_tenant_id"
                            value="{{ $currentConfig['tenant_id'] ?? 'common' }}"
                            placeholder="common or your tenant ID"
                        />
                        <flux:description>
                            Use 'common' for multi-tenant, or your specific tenant ID
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label for="microsoft_allowed_domains">Allowed Email Domains</flux:label>
                        <flux:input
                            type="text"
                            name="allowed_domains"
                            id="microsoft_allowed_domains"
                            value="{{ isset($currentConfig['allowed_domains']) ? implode(', ', $currentConfig['allowed_domains']) : '' }}"
                            placeholder="@company.com, @subsidiary.com"
                        />
                        <flux:description>
                            Comma-separated list of allowed domains (leave empty to allow all)
                        </flux:description>
                    </flux:field>
                </div>

                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <flux:text size="sm" class="text-blue-800">
                        <strong>Setup Instructions:</strong>
                        <ol class="list-decimal list-inside mt-2 space-y-1">
                            <li>Go to <a href="https://portal.azure.com" target="_blank" class="underline">Azure Portal</a></li>
                            <li>Navigate to Azure Active Directory → App registrations</li>
                            <li>Create a new registration or use existing one</li>
                            <li>Add these redirect URIs: <code class="bg-blue-100 px-1 rounded">{{ route('email.oauth.callback') }}</code></li>
                            <li>Add these API permissions: Mail.ReadWrite, Mail.Send, User.Read, offline_access</li>
                            <li>Grant admin consent for the permissions</li>
                        </ol>
                    </flux:text>
                </div>
            </flux:card>
        </div>

        <!-- Google Workspace Configuration -->
        <div id="google_workspace-config" class="provider-config" style="display: {{ $company->email_provider_type === 'google_workspace' ? 'block' : 'none' }};">
            <flux:card class="mb-6">
                <flux:heading size="md" class="mb-4">Google Workspace Configuration</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label for="google_client_id">Client ID *</flux:label>
                        <flux:input
                            type="text"
                            name="client_id"
                            id="google_client_id"
                            value="{{ $currentConfig['client_id'] ?? '' }}"
                            placeholder="your-client-id.apps.googleusercontent.com"
                            required
                        />
                        <flux:description>
                            From your Google Cloud Console project
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label for="google_client_secret">Client Secret *</flux:label>
                        <flux:input
                            type="password"
                            name="client_secret"
                            id="google_client_secret"
                            value="{{ $currentConfig['client_secret'] ?? '' }}"
                            placeholder="Your client secret"
                            required
                        />
                        <flux:description>
                            Keep this secret secure
                        </flux:description>
                    </flux:field>

                    <flux:field class="md:col-span-2">
                        <flux:label for="google_allowed_domains">Allowed Email Domains</flux:label>
                        <flux:input
                            type="text"
                            name="allowed_domains"
                            id="google_allowed_domains"
                            value="{{ isset($currentConfig['allowed_domains']) ? implode(', ', $currentConfig['allowed_domains']) : '' }}"
                            placeholder="@company.com, @subsidiary.com"
                        />
                        <flux:description>
                            Comma-separated list of allowed domains (leave empty to allow all)
                        </flux:description>
                    </flux:field>
                </div>

                <div class="mt-4 p-4 bg-green-50 rounded-lg">
                    <flux:text size="sm" class="text-green-800">
                        <strong>Setup Instructions:</strong>
                        <ol class="list-decimal list-inside mt-2 space-y-1">
                            <li>Go to <a href="https://console.cloud.google.com" target="_blank" class="underline">Google Cloud Console</a></li>
                            <li>Create a new project or select existing one</li>
                            <li>Enable Gmail API</li>
                            <li>Create OAuth 2.0 credentials</li>
                            <li>Add authorized redirect URI: <code class="bg-green-100 px-1 rounded">{{ route('email.oauth.callback') }}</code></li>
                            <li>Configure OAuth consent screen</li>
                        </ol>
                    </flux:text>
                </div>
            </flux:card>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('settings.index') }}">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary">
                <flux:icon.check class="w-4 h-4 mr-2" />
                Save Configuration
            </flux:button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('email_provider_type').addEventListener('change', function() {
    const provider = this.value;

    // Hide all provider configs
    document.querySelectorAll('.provider-config').forEach(config => {
        config.style.display = 'none';
    });

    // Show selected provider config
    const selectedConfig = document.getElementById(provider + '-config');
    if (selectedConfig) {
        selectedConfig.style.display = 'block';
    }

    // Update required fields
    document.querySelectorAll('[required]').forEach(field => {
        if (field.id.includes('microsoft_') || field.id.includes('google_')) {
            field.required = field.id.includes(provider + '_');
        }
    });
});
</script>
@endpush
@endsection