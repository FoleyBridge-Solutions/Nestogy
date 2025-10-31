@extends('layouts.app')

@section('title', $integration->name)

@section('content')
<flux:container>
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item href="{{ route('settings.index') }}" icon="home">Settings</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('settings.integrations.rmm.index') }}">RMM Integrations</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $integration->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $integration->name }}</flux:heading>
            <flux:subheading>{{ $integration->rmm_type }} Integration</flux:subheading>
        </div>
        <div class="flex gap-3">
            <flux:button href="{{ route('settings.integrations.rmm.edit', $integration) }}" icon="pencil">
                Edit
            </flux:button>
            <form action="{{ route('settings.integrations.rmm.destroy', $integration) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this integration?')">
                @csrf
                @method('DELETE')
                <flux:button type="submit" variant="danger" icon="trash">
                    Delete
                </flux:button>
            </form>
        </div>
    </div>

    <livewire:integration.rmm-integration-show :integration="$integration" />
</flux:container>
@endsection
