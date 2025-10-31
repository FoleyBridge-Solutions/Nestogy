@extends('layouts.app')

@section('title', 'Create RMM Integration')

@section('content')
<flux:container>
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item href="{{ route('settings.index') }}" icon="home">Settings</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('settings.integrations.rmm.index') }}">RMM Integrations</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6">
        <flux:heading size="xl">Create RMM Integration</flux:heading>
        <flux:subheading>Connect a Remote Monitoring and Management system</flux:subheading>
    </div>

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

    <flux:card>
        <form action="{{ route('settings.integrations.rmm.store') }}" method="POST" class="space-y-6">
            @csrf

            <flux:field>
                <flux:label>Integration Name</flux:label>
                <flux:description>A friendly name for this integration</flux:description>
                <flux:input name="name" value="{{ old('name') }}" required />
            </flux:field>

            <flux:field>
                <flux:label>RMM Type</flux:label>
                <flux:description>Select your RMM system</flux:description>
                <flux:select name="rmm_type" required>
                    <option value="">Select RMM Type</option>
                    @foreach($availableTypes as $typeId => $type)
                        <option value="{{ $typeId }}" {{ old('rmm_type') === $typeId ? 'selected' : '' }}>
                            {{ $type['name'] }}
                        </option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>API URL</flux:label>
                <flux:description>The URL of your RMM API endpoint</flux:description>
                <flux:input name="api_url" type="url" value="{{ old('api_url') }}" required />
            </flux:field>

            <flux:field>
                <flux:label>API Key</flux:label>
                <flux:description>Your RMM API key (will be encrypted)</flux:description>
                <flux:input name="api_key" type="password" value="{{ old('api_key') }}" required />
            </flux:field>

            <flux:field>
                <flux:label>Active</flux:label>
                <flux:description>Enable this integration</flux:description>
                <div class="mt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm">Enable this integration</span>
                    </label>
                </div>
            </flux:field>

            <flux:separator />

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('settings.integrations.rmm.index') }}">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    Create Integration
                </flux:button>
            </div>
        </form>
    </flux:card>
</flux:container>
@endsection
