@extends('layouts.app')

@section('title', 'Edit RMM Integration')

@section('content')
<flux:container>
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item href="{{ route('settings.index') }}" icon="home">Settings</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('settings.integrations.rmm.index') }}">RMM Integrations</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('settings.integrations.rmm.show', $integration) }}">{{ $integration->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6">
        <flux:heading size="xl">Edit RMM Integration</flux:heading>
        <flux:subheading>Update {{ $integration->name }} configuration</flux:subheading>
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
        <form action="{{ route('settings.integrations.rmm.update', $integration) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <flux:field>
                <flux:label>Integration Name</flux:label>
                <flux:description>A friendly name for this integration</flux:description>
                <flux:input name="name" value="{{ old('name', $integration->name) }}" required />
            </flux:field>

            <flux:field>
                <flux:label>RMM Type</flux:label>
                <flux:description>RMM system type (cannot be changed)</flux:description>
                <flux:input value="{{ $integration->rmm_type }}" disabled />
            </flux:field>

            <flux:field>
                <flux:label>API URL</flux:label>
                <flux:description>The URL of your RMM API endpoint (leave blank to keep current)</flux:description>
                <flux:input name="api_url" type="url" value="{{ old('api_url') }}" placeholder="Leave blank to keep current" />
            </flux:field>

            <flux:field>
                <flux:label>API Key</flux:label>
                <flux:description>Your RMM API key (leave blank to keep current, will be encrypted)</flux:description>
                <flux:input name="api_key" type="password" value="{{ old('api_key') }}" placeholder="Leave blank to keep current" />
            </flux:field>

            <flux:field>
                <flux:label>Active</flux:label>
                <flux:description>Enable this integration</flux:description>
                <div class="mt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $integration->is_active ? 'checked' : '' }} class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm">Enable this integration</span>
                    </label>
                </div>
            </flux:field>

            <flux:separator />

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('settings.integrations.rmm.show', $integration) }}">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    Update Integration
                </flux:button>
            </div>
        </form>
    </flux:card>
</flux:container>
@endsection
