@extends('layouts.app')

@section('title', 'RMM Integrations')

@section('content')
<flux:container>
    <div class="mb-6">
        <flux:heading size="xl">RMM Integrations</flux:heading>
        <flux:subheading>Manage your Remote Monitoring and Management integrations</flux:subheading>
    </div>

    <div class="flex justify-end mb-6">
        <flux:button href="{{ route('settings.integrations.rmm.create') }}" icon="plus" variant="primary">
            Add RMM Integration
        </flux:button>
    </div>

    @if($integrations->isEmpty())
        <flux:card>
            <div class="text-center py-12">
                <flux:icon.server-stack class="w-16 h-16 mx-auto mb-4 text-zinc-400" />
                <flux:heading size="lg" class="mb-2">No RMM Integrations</flux:heading>
                <flux:text class="mb-4">Get started by adding your first RMM integration</flux:text>
                <flux:button href="{{ route('settings.integrations.rmm.create') }}" icon="plus" variant="primary">
                    Add RMM Integration
                </flux:button>
            </div>
        </flux:card>
    @else
        <div class="space-y-4">
            @foreach($integrations as $integration)
                <flux:card>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                <flux:icon.server-stack class="w-6 h-6" />
                            </div>
                            <div>
                                <flux:heading size="md">{{ $integration->name }}</flux:heading>
                                <flux:text class="text-sm">
                                    Type: {{ $integration->rmm_type }} • 
                                    {{ $integration->total_agents ?? 0 }} agents
                                    @if($integration->last_sync_at)
                                        • Last sync: {{ $integration->last_sync_at->diffForHumans() }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:badge :color="$integration->is_active ? 'green' : 'zinc'">
                                {{ $integration->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                            <flux:button 
                                variant="ghost" 
                                size="sm"
                                href="{{ route('settings.integrations.rmm.show', $integration) }}"
                                icon="arrow-right"
                            >
                                Manage
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $integrations->links() }}
        </div>
    @endif
</flux:container>
@endsection
