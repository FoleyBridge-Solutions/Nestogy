@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<flux:container>
    <!-- Header Section -->
    <flux:card class="mb-6">
        <flux:card.body>
            <flux:between>
                <div>
                    <flux:heading size="xl">Settings</flux:heading>
                    <flux:text class="mt-2">Manage your company configuration and preferences</flux:text>
                </div>
                <flux:button.group>
                    <flux:button variant="secondary" icon="arrow-down-tray" onclick="exportSettings()">
                        Export Settings
                    </flux:button>
                    <flux:button variant="secondary" icon="arrow-up-tray" onclick="document.getElementById('import-file').click()">
                        Import Settings
                    </flux:button>
                </flux:button.group>
            </flux:between>
            
            <form id="import-form" action="{{ route('settings.import') }}" method="POST" enctype="multipart/form-data" class="hidden">
                @csrf
                <input type="file" id="import-file" name="file" accept=".json" onchange="this.form.submit()">
            </form>
        </flux:card.body>
    </flux:card>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <flux:toast variant="success" dismissible class="mb-6">
            {{ session('success') }}
        </flux:toast>
    @endif

    @if(session('error'))
        <flux:toast variant="danger" dismissible class="mb-6">
            {{ session('error') }}
        </flux:toast>
    @endif

    <!-- Settings Domains Grid -->
    <flux:grid cols="3" class="mb-8">
        @foreach($domains as $domainKey => $domain)
            @php
                $configured = $summary[$domainKey]['configured'] ?? false;
                $lastUpdated = $summary[$domainKey]['last_updated'] ?? null;
            @endphp
            
            <flux:card href="{{ route('settings.domain.index', $domainKey) }}" class="hover:shadow-lg transition-all">
                <flux:card.header>
                    <flux:between>
                        <flux:avatar icon="{{ $domain['icon'] ?? 'cog-6-tooth' }}" size="lg" />
                        @if($configured)
                            <flux:badge variant="success" icon="check">Configured</flux:badge>
                        @endif
                    </flux:between>
                </flux:card.header>
                
                <flux:card.body>
                    <flux:heading size="md">{{ $domain['name'] }}</flux:heading>
                    <flux:text size="sm" class="mt-1">{{ $domain['description'] }}</flux:text>
                    
                    <div class="mt-4">
                        <flux:badge.group>
                            @foreach($domain['categories'] as $category)
                                <flux:badge variant="neutral">
                                    {{ str_replace('_', ' ', ucfirst($category)) }}
                                </flux:badge>
                            @endforeach
                        </flux:badge.group>
                    </div>
                    
                    @if($lastUpdated)
                        <flux:text size="xs" variant="muted" class="mt-4">
                            Last updated {{ $lastUpdated }}
                        </flux:text>
                    @endif
                </flux:card.body>
            </flux:card>
        @endforeach
    </flux:grid>

    <!-- Quick Access Section -->
    <flux:card>
        <flux:card.header>
            <flux:heading size="lg">Quick Access</flux:heading>
        </flux:card.header>
        
        <flux:card.body>
            <flux:grid cols="4">
                <flux:button 
                    href="{{ route('settings.category.show', ['domain' => 'communication', 'category' => 'email']) }}" 
                    variant="ghost" 
                    icon="envelope"
                    class="justify-start">
                    Email Settings
                </flux:button>
                
                <flux:button 
                    href="{{ route('settings.category.show', ['domain' => 'financial', 'category' => 'billing']) }}"
                    variant="ghost"
                    icon="credit-card"
                    class="justify-start">
                    Billing
                </flux:button>
                
                <flux:button 
                    href="{{ route('settings.roles.index') }}"
                    variant="ghost"
                    icon="user-group"
                    class="justify-start">
                    Roles & Permissions
                </flux:button>
                
                <flux:button 
                    href="{{ route('settings.category.show', ['domain' => 'company', 'category' => 'general']) }}"
                    variant="ghost"
                    icon="building-office-2"
                    class="justify-start">
                    Company Info
                </flux:button>
            </flux:grid>
        </flux:card.body>
    </flux:card>
</flux:container>

@push('scripts')
<script>
function exportSettings() {
    window.location.href = '{{ route('settings.export') }}';
}
</script>
@endpush
@endsection